<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/TransactionLog.php';

class Reservation
{
    private PDO $db;
    private TransactionLog $logger;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->logger = new TransactionLog();
    }
    /** Stored in decline_reason when a reservation is cancelled because its pick-up window ran out. */
    public const EXPIRED_REASON = 'Pick-up window expired';

    /**
     * When a borrower may collect an approved item: from the reservation start for
     * PICKUP_WINDOW_DAYS, but never later than the loan's end date.
     * Returns unix timestamps ['start' => ..., 'end' => ...].
     * (expireLapsedPickups() below uses the same rule in SQL - keep them in sync.)
     */
    public static function pickupWindow(string $startAt, string $endAt): array
    {
        $days  = defined('PICKUP_WINDOW_DAYS') ? (int) PICKUP_WINDOW_DAYS : 3;
        $start = strtotime($startAt);
        return ['start' => $start, 'end' => min(strtotime('+' . $days . ' days', $start), strtotime($endAt))];
    }

    /**
     * Cancels every approved reservation whose pick-up window has ended, which frees the
     * unit for other bookings (hasDateConflict() only looks at approved / checked_out).
     * Run it on a schedule with scripts/expire_pickups.php; it is also called whenever a page that shows pick-ups loads.
     * Returns how many reservations were expired.
     */
    public function expireLapsedPickups(): int
    {
        // Also runs from scripts/expire_pickups.php (cron / Task Scheduler), so expiry no longer
        // depends on someone opening a page. Calling it from pages stays as a safety net.
        $days  = defined('PICKUP_WINDOW_DAYS') ? (int) PICKUP_WINDOW_DAYS : 3;
        $count = 0;

        try {
            $ids = $this->db->query(
                "SELECT reservation_id FROM reservations
                 WHERE status = 'approved'
                   AND NOW() >= LEAST(DATE_ADD(start_at, INTERVAL {$days} DAY), end_at)"
            )->fetchAll(PDO::FETCH_COLUMN);

            foreach ($ids as $id) {
                $this->db->beginTransaction();

                $stm = $this->db->prepare('SELECT item_id, borrower_id, status FROM reservations WHERE reservation_id = :id FOR UPDATE');
                $stm->execute([':id' => $id]);
                $row = $stm->fetch(PDO::FETCH_ASSOC);

                // Someone may have handed it over a moment ago.
                if (!$row || $row['status'] !== 'approved') {
                    $this->db->rollBack();
                    continue;
                }

                $stm = $this->db->prepare(
                    "UPDATE reservations SET status = 'cancelled', decline_reason = :reason
                     WHERE reservation_id = :id AND status = 'approved'"
                );
                $stm->execute([':reason' => self::EXPIRED_REASON, ':id' => $id]);

                // Automatic action: no human did this, so the actor is NULL (shown as "System").
                $this->logger->record(
                    (int) $id,
                    $row['item_id'] !== null ? (int) $row['item_id'] : null,
                    null,
                    null,
                    'cancelled',
                    self::EXPIRED_REASON . ' (automatic): the item was not picked up in time.'
                );

                $this->db->commit();
                $count++;
            }
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Reservation::expireLapsedPickups failed: ' . $e->getMessage());
        }

        return $count;
    }

    /**
     * A loan runs in whole days: it starts at 00:00 on the first day and is due at LOAN_DUE_TIME
     * on the last day. (Storing a bare date meant "due at 00:00", so a borrower returning at 9am
     * on the due date was already late and fined.)
     * Returns ['start_at', 'end_at', 'days'] or null when a date cannot be read / end is not after start.
     */
    public static function loanWindow(string $startAt, string $endAt): ?array
    {
        $start = strtotime($startAt);
        $end   = strtotime($endAt);
        if ($start === false || $end === false) {
            return null;
        }
        $startDate = date('Y-m-d', $start);
        $endDate   = date('Y-m-d', $end);
        if ($endDate <= $startDate) {
            return null;
        }
        $dueTime = defined('LOAN_DUE_TIME') ? LOAN_DUE_TIME : '17:00:00';

        return [
            'start_at' => $startDate . ' 00:00:00',
            'end_at'   => $endDate . ' ' . $dueTime,
            'days'     => (int) (new DateTime($startDate))->diff(new DateTime($endDate))->days,
        ];
    }

    public function create(int $borrowerId, int $catalogId, string $startAt, string $endAt, string $purpose): array
    {
        if (strtotime($startAt) === false || strtotime($endAt) === false) {
            return ['success' => false, 'message' => 'Invalid date format.'];
        }
        $window = self::loanWindow($startAt, $endAt);
        if ($window === null) {
            return ['success' => false, 'message' => 'End date must be after the start date.'];
        }
        $startAt = $window['start_at'];
        $endAt   = $window['end_at'];
        $days    = $window['days'];

        try {
            $stm = $this->db->prepare(
                'SELECT max_loan_days, is_archived, (DATE(:start_at) < CURDATE()) AS start_in_past
                 FROM equipment_catalog
                 WHERE catalog_id = :catalog_id'
            );
            $stm->execute([':start_at' => $startAt, ':catalog_id' => $catalogId]);
            $catalog = $stm->fetch(PDO::FETCH_ASSOC);

            if (!$catalog) {
                return ['success' => false, 'message' => 'Equipment model not found.'];
            }
            if ((int) $catalog['is_archived'] === 1) {
                return ['success' => false, 'message' => 'This equipment is no longer available for reservation.'];
            }
            if ((int) $catalog['start_in_past'] === 1) {
                return ['success' => false, 'message' => 'The start date cannot be in the past.'];
            }
            if ($days > (int) $catalog['max_loan_days']) {
                return ['success' => false, 'message' => 'This equipment can only be borrowed for up to ' . (int) $catalog['max_loan_days'] . ' day(s).'];
            }

            $this->db->beginTransaction();

            // Serialise requests for this model: two borrowers asking for the last unit at the same
            // moment must not both pass the capacity check below.
            $lock = $this->db->prepare('SELECT catalog_id FROM equipment_catalog WHERE catalog_id = :id FOR UPDATE');
            $lock->execute([':id' => $catalogId]);

            // Units still in service (retired ones can never be lent; maintenance is temporary).
            $stm = $this->db->prepare("SELECT COUNT(*) FROM serialized_items WHERE catalog_id = :id AND status <> 'retired'");
            $stm->execute([':id' => $catalogId]);
            $units = (int) $stm->fetchColumn();
            if ($units === 0) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'There are no units of this equipment in service right now.'];
            }

            // Requests that are still pending count too: they are first in line for a unit,
            // so a model can never be promised to more people than it has units.
            $overlap = "status IN ('pending', 'approved', 'checked_out') AND start_at < :end_at AND end_at > :start_at";

            $stm = $this->db->prepare(
                "SELECT COUNT(*) FROM reservations
                 WHERE borrower_id = :b AND catalog_id = :c AND $overlap"
            );
            $stm->execute([':b' => $borrowerId, ':c' => $catalogId, ':start_at' => $startAt, ':end_at' => $endAt]);
            if ((int) $stm->fetchColumn() > 0) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'You already have a request or loan for this equipment on overlapping dates.'];
            }

            $stm = $this->db->prepare("SELECT COUNT(*) FROM reservations WHERE catalog_id = :c AND $overlap");
            $stm->execute([':c' => $catalogId, ':start_at' => $startAt, ':end_at' => $endAt]);
            if ((int) $stm->fetchColumn() >= $units) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'All units of this equipment are already requested or booked for those dates. Please choose different dates.'];
            }

            $stm = $this->db->prepare(
                "INSERT INTO reservations (catalog_id, borrower_id, status, purpose, start_at, end_at, terms_accepted)
                 VALUES (:catalog_id, :borrower_id, 'pending', :purpose, :start_at, :end_at, 1)"
            );
            $stm->execute([
                ':catalog_id'  => $catalogId,
                ':borrower_id' => $borrowerId,
                ':purpose'     => $purpose,
                ':start_at'    => $startAt,
                ':end_at'      => $endAt,
            ]);

            $reservationId = (int) $this->db->lastInsertId();

            $this->logger->record($reservationId, null, null, $borrowerId, 'requested', $purpose);

            $this->db->commit();
            return ['success' => true, 'reservation_id' => $reservationId];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Reservation::create failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not create the reservation. Please try again.'];
        }
    }
    public function listForBorrower(int $borrowerId): array
    {
        $stm = $this->db->prepare(
            'SELECT r.*, ec.model_name
             FROM reservations r
             JOIN equipment_catalog ec ON ec.catalog_id = r.catalog_id
             WHERE r.borrower_id = :borrower_id
             ORDER BY r.requested_at DESC'
        );
        $stm->execute([':borrower_id' => $borrowerId]);
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * Everything the borrower's "Borrowing & Requests" page needs, one row per
     * reservation: model, assigned serial, checkout/return details, fines and
     * (for returns with missing accessories) the accessory names.
     */
    public function listForBorrowerDetailed(int $borrowerId): array
    {
        $stm = $this->db->prepare(
            "SELECT r.reservation_id, r.status, r.purpose, r.start_at, r.end_at,
                    r.decline_reason, r.requested_at,
                    ec.model_name, ec.max_loan_days, ec.late_fine_rate,
                    CASE WHEN c.checkout_id IS NOT NULL AND c.returned_at IS NULL AND NOW() > c.due_at
                         THEN GREATEST(1, CEIL(TIMESTAMPDIFF(SECOND, c.due_at, NOW()) / 86400)) ELSE 0 END AS days_late,
                    si.serial_number,
                    c.checkout_id, c.checked_out_at, c.due_at, c.returned_at,
                    c.return_condition, c.damage_notes,
                    (c.checkout_id IS NOT NULL AND c.returned_at IS NULL AND NOW() > c.due_at) AS is_overdue,
                    COALESCE(fx.fine_total, 0)  AS fine_total,
                    COALESCE(fx.fine_unpaid, 0) AS fine_unpaid
             FROM reservations r
             JOIN equipment_catalog ec ON ec.catalog_id = r.catalog_id
             LEFT JOIN serialized_items si ON si.item_id = r.item_id
             LEFT JOIN checkouts c ON c.reservation_id = r.reservation_id
             LEFT JOIN (
                 SELECT checkout_id,
                        SUM(CASE WHEN payment_status <> 'waived' THEN amount_due ELSE 0 END) AS fine_total,
                        SUM(CASE WHEN payment_status = 'unpaid' THEN amount_due ELSE 0 END)  AS fine_unpaid
                 FROM fines
                 GROUP BY checkout_id
             ) fx ON fx.checkout_id = c.checkout_id
             WHERE r.borrower_id = :borrower_id
             ORDER BY r.requested_at DESC, r.reservation_id DESC"
        );
        $stm->execute([':borrower_id' => $borrowerId]);
        $rows = $stm->fetchAll(PDO::FETCH_ASSOC);

        // Names of accessories reported missing at return (one extra query for all rows).
        $checkoutIds = [];
        foreach ($rows as $row) {
            if ($row['return_condition'] === 'missing_accessories' && $row['checkout_id'] !== null) {
                $checkoutIds[] = (int) $row['checkout_id'];
            }
        }
        $missing = [];
        if (!empty($checkoutIds)) {
            $placeholders = implode(',', array_fill(0, count($checkoutIds), '?'));
            $m = $this->db->prepare(
                "SELECT cma.checkout_id, a.accessory_name
                 FROM checkout_missing_accessories cma
                 JOIN accessories a ON a.accessory_id = cma.accessory_id
                 WHERE cma.checkout_id IN ($placeholders)
                 ORDER BY a.accessory_name ASC"
            );
            $m->execute($checkoutIds);
            foreach ($m->fetchAll(PDO::FETCH_ASSOC) as $mr) {
                $missing[(int) $mr['checkout_id']][] = $mr['accessory_name'];
            }
        }
        foreach ($rows as &$row) {
            $row['missing_accessories'] = $missing[(int) ($row['checkout_id'] ?? 0)] ?? [];
        }
        unset($row);

        return $rows;
    }
    public function listPending(): array
    {
        $stm = $this->db->query(
            "SELECT r.*, ec.model_name, u.first_name, u.last_name
             FROM reservations r
             JOIN equipment_catalog ec ON ec.catalog_id = r.catalog_id
             JOIN users u ON u.user_id = r.borrower_id
             WHERE r.status = 'pending'
             ORDER BY r.requested_at ASC"
        );
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }
    // In src/Reservation.php

/**
 * Returns pending reservations with borrower details and available serialized units for immediate assignment.
 */
    public function listPendingDetailed(): array
    {
        $stm = $this->db->query(
            "SELECT r.reservation_id, r.catalog_id, r.borrower_id, r.status, r.purpose,
                    r.start_at, r.end_at, r.requested_at,
                    ec.model_name, ec.max_loan_days,
                    CONCAT(u.first_name, ' ', COALESCE(CONCAT(u.middle_name, ' '), ''), u.last_name) AS borrower_name,
                    u.email AS borrower_email, u.phone AS borrower_phone, u.id_number AS borrower_id_number
             FROM reservations r
             JOIN equipment_catalog ec ON ec.catalog_id = r.catalog_id
             JOIN users u ON u.user_id = r.borrower_id
             WHERE r.status = 'pending'
             ORDER BY r.requested_at ASC"
        );
        $requests = $stm->fetchAll(PDO::FETCH_ASSOC);
        if (empty($requests)) {
            return [];
        }

        // Two queries for ALL requests (was one query per request). A unit is offered when it is in
        // service and no approved / checked-out booking overlaps the requested dates, so a unit that
        // is out today can still be assigned to a request that starts after it is due back.
        $catalogIds   = array_values(array_unique(array_map('intval', array_column($requests, 'catalog_id'))));
        $placeholders = implode(',', array_fill(0, count($catalogIds), '?'));

        $stm = $this->db->prepare(
            "SELECT item_id, catalog_id, serial_number, storage_location
             FROM serialized_items
             WHERE catalog_id IN ($placeholders) AND status NOT IN ('maintenance', 'retired')
             ORDER BY serial_number ASC"
        );
        $stm->execute($catalogIds);
        $unitsByModel = [];
        foreach ($stm->fetchAll(PDO::FETCH_ASSOC) as $u) {
            $unitsByModel[(int) $u['catalog_id']][] = $u;
        }

        $stm = $this->db->prepare(
            "SELECT item_id, start_at, end_at
             FROM reservations
             WHERE catalog_id IN ($placeholders) AND item_id IS NOT NULL AND status IN ('approved', 'checked_out')"
        );
        $stm->execute($catalogIds);
        $bookings = [];
        foreach ($stm->fetchAll(PDO::FETCH_ASSOC) as $bk) {
            $bookings[(int) $bk['item_id']][] = [strtotime($bk['start_at']), strtotime($bk['end_at'])];
        }

        foreach ($requests as &$req) {
            $reqStart = strtotime($req['start_at']);
            $reqEnd   = strtotime($req['end_at']);
            $req['available_units'] = [];

            foreach ($unitsByModel[(int) $req['catalog_id']] ?? [] as $u) {
                $busy = false;
                foreach ($bookings[(int) $u['item_id']] ?? [] as [$bStart, $bEnd]) {
                    if ($bStart < $reqEnd && $bEnd > $reqStart) {
                        $busy = true;
                        break;
                    }
                }
                if (!$busy) {
                    $req['available_units'][] = [
                        'item_id'          => $u['item_id'],
                        'serial_number'    => $u['serial_number'],
                        'storage_location' => $u['storage_location'],
                    ];
                }
            }
        }
        unset($req);

        return $requests;
    }

    /**
     * Returns approved reservations awaiting staff handover.
     */
    public function listReadyForCheckout(): array
    {
        $stm = $this->db->query(
            "SELECT r.reservation_id, r.item_id, r.start_at, r.end_at, r.reviewed_at,
                    ec.model_name, si.serial_number, si.storage_location,
                    CONCAT(u.first_name, ' ', u.last_name) AS borrower_name,
                    u.id_number AS borrower_id_number,
                    CONCAT(staff.first_name, ' ', staff.last_name) AS assigned_by_name
             FROM reservations r
             JOIN equipment_catalog ec ON ec.catalog_id = r.catalog_id
             JOIN serialized_items si ON si.item_id = r.item_id
             JOIN users u ON u.user_id = r.borrower_id
             LEFT JOIN users staff ON staff.user_id = r.reviewed_by
             WHERE r.status = 'approved'
             ORDER BY r.start_at ASC"
        );
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countPending(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM reservations WHERE status = 'pending'")->fetchColumn();
    }
    public function hasDateConflict(int $itemId, string $startAt, string $endAt): bool
    {
        $stm = $this->db->prepare(
            "SELECT reservation_id FROM reservations
             WHERE item_id = :item_id
               AND status IN ('approved', 'checked_out')
               AND start_at < :end_at
               AND end_at > :start_at"
        );
        $stm->execute([
            ':item_id'  => $itemId,
            ':start_at' => $startAt,
            ':end_at'   => $endAt,
        ]);
        return (bool) $stm->fetch(PDO::FETCH_ASSOC);
    }

    public function approve(int $reservationId, int $itemId, int $reviewerId): array
    {
        try {
            $this->db->beginTransaction();

            // Lock the reservation so two staff can't approve it at once.
            $stm = $this->db->prepare('SELECT * FROM reservations WHERE reservation_id = :id FOR UPDATE');
            $stm->execute([':id' => $reservationId]);
            $reservation = $stm->fetch(PDO::FETCH_ASSOC);

            if (!$reservation) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Reservation not found.'];
            }
            if ($reservation['status'] !== 'pending') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Reservation is not in pending status.'];
            }

            // A request whose pick-up window is already over would be cancelled again straight away.
            $window = self::pickupWindow($reservation['start_at'], $reservation['end_at']);
            if (time() >= $window['end']) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'The pick-up window for this request has already ended, so it can no longer be approved. Please decline it.'];
            }

            $stm = $this->db->prepare('SELECT catalog_id, serial_number, status FROM serialized_items WHERE item_id = :id FOR UPDATE');
            $stm->execute([':id' => $itemId]);
            $item = $stm->fetch(PDO::FETCH_ASSOC);

            if (!$item || (int) $item['catalog_id'] !== (int) $reservation['catalog_id']) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Invalid unit selection for this reservation.'];
            }
            if (in_array($item['status'], ['maintenance', 'retired'], true)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'That unit is under maintenance or retired and cannot be assigned.'];
            }

            if ($this->hasDateConflict($itemId, $reservation['start_at'], $reservation['end_at'])) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'That unit is already booked for an overlapping date range.'];
            }

            $stm = $this->db->prepare(
                "UPDATE reservations
                 SET status = 'approved', item_id = :item_id, reviewed_by = :reviewed_by, reviewed_at = NOW()
                 WHERE reservation_id = :id"
            );
            $stm->execute([
                ':item_id'     => $itemId,
                ':reviewed_by' => $reviewerId,
                ':id'          => $reservationId,
            ]);

            $this->logger->record($reservationId, $itemId, null, $reviewerId, 'approved', null, $item['serial_number']);

            $this->db->commit();
            return ['success' => true];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Reservation::approve failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not approve the reservation. Please try again.'];
        }
    }
    public function decline(int $reservationId, int $reviewerId, string $reason): array
    {
        try {
            $this->db->beginTransaction();

            $stm = $this->db->prepare(
                "UPDATE reservations
                 SET status = 'declined', decline_reason = :reason, reviewed_by = :reviewed_by, reviewed_at = NOW()
                 WHERE reservation_id = :id AND status = 'pending'"
            );
            $stm->execute([
                ':reason'      => $reason,
                ':reviewed_by' => $reviewerId,
                ':id'          => $reservationId,
            ]);

            if ($stm->rowCount() === 0) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Reservation not found or already processed.'];
            }

            $this->logger->record($reservationId, null, null, $reviewerId, 'declined', $reason);

            $this->db->commit();
            return ['success' => true];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Reservation::decline failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not decline the reservation. Please try again.'];
        }
    }
    public function cancel(int $reservationId, int $borrowerId): array
    {
        try {
            $this->db->beginTransaction();

            $stm = $this->db->prepare(
                'SELECT item_id, status FROM reservations
                 WHERE reservation_id = :id AND borrower_id = :borrower_id
                 FOR UPDATE'
            );
            $stm->execute([
                ':id'          => $reservationId,
                ':borrower_id' => $borrowerId,
            ]);
            $reservation = $stm->fetch(PDO::FETCH_ASSOC);

            if (!$reservation || !in_array($reservation['status'], ['pending', 'approved'], true)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Reservation cannot be cancelled.'];
            }

            $stm = $this->db->prepare("UPDATE reservations SET status = 'cancelled' WHERE reservation_id = :id");
            $stm->execute([':id' => $reservationId]);

            $itemId = $reservation['item_id'] !== null ? (int) $reservation['item_id'] : null;
            $this->logger->record($reservationId, $itemId, null, $borrowerId, 'cancelled');

            $this->db->commit();
            return ['success' => true];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Reservation::cancel failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not cancel the reservation. Please try again.'];
        }
    }
}