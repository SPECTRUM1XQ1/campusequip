<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/Reservation.php';

/**
 * Builds a user's notification feed from data the system already has
 * (reservations, checkouts, fines and the transaction log), so there is no
 * separate notifications table to keep in sync.
 *
 * Every item: id (stable string), tone (info|success|warning|danger), icon,
 * title, message, ts (unix seconds, when it happened) and href (optional link).
 */
class Notification
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** $user is the row from User::getById() (role + the four can_* flags). */
    public function forUser(array $user): array
    {
        // Cancel approved reservations whose pick-up window has ended, so they show up as
        // "expired" (borrower) and drop out of "Ready for Handover" (staff) straight away.
        (new Reservation())->expireLapsedPickups();

        $items = ($user['role'] === 'borrower')
            ? $this->forBorrower((int) $user['user_id'])
            : $this->forStaff($user);

        usort($items, fn($a, $b) => $b['ts'] <=> $a['ts']);
        return array_slice($items, 0, 20);
    }

    private function forBorrower(int $userId): array
    {
        $items = [];

        // Things staff did with the borrower's requests (last 30 days).
        $stm = $this->db->prepare(
            "SELECT tl.log_id, tl.action_type, tl.remarks, UNIX_TIMESTAMP(tl.action_timestamp) AS ts,
                    ec.model_name, f.amount_due, r.start_at, r.end_at
             FROM transaction_logs tl
             JOIN reservations r ON r.reservation_id = tl.reservation_id
             JOIN equipment_catalog ec ON ec.catalog_id = r.catalog_id
             LEFT JOIN fines f ON f.fine_id = tl.fine_id
             WHERE r.borrower_id = :uid
               AND tl.action_type IN ('approved','declined','cancelled','handover','returned','returned_late','fine_charged','fine_paid','fine_waived')
               AND (tl.action_type <> 'cancelled' OR tl.remarks LIKE 'Pick-up window expired%')
               AND tl.action_timestamp >= (NOW() - INTERVAL 30 DAY)
             ORDER BY tl.action_timestamp DESC
             LIMIT 20"
        );
        $stm->execute([':uid' => $userId]);

        foreach ($stm->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $model = $row['model_name'];
            $ts     = (int) $row['ts'];
            $amount = $row['amount_due'] !== null ? '₱' . number_format((float) $row['amount_due'], 2) : '';
            switch ($row['action_type']) {
                case 'approved':
                    $item = ['success', 'ph-check-circle', 'Request Approved', "Your request for {$model} was approved. Bring your ID to the lab desk to pick it up.", APP_BASE . '/public/borrower/my_requests.php#pending'];
                    break;
                case 'cancelled': // only the automatic "pick-up window expired" ones get here
                    $window = Reservation::pickupWindow($row['start_at'], $row['end_at']);
                    $ts     = $window['end'];
                    $item = ['danger', 'ph-timer', 'Pick-Up Window Expired', "You can no longer pick up {$model}: the pick-up window ended " . date('M j, g:i A', $window['end']) . ' and the reservation was cancelled. You can submit a new request from the catalog.', APP_BASE . '/public/borrower/my_requests.php#history'];
                    break;
                case 'declined':
                    $why = trim((string) $row['remarks']);
                    $item = ['danger', 'ph-x-circle', 'Request Declined', "Your request for {$model} was declined." . ($why !== '' ? " Reason: {$why}" : ''), APP_BASE . '/public/borrower/my_requests.php#history'];
                    break;
                case 'handover':
                    $item = ['info', 'ph-package', 'Equipment Handed Over', "{$model} is now checked out to you.", APP_BASE . '/public/borrower/my_requests.php#borrowed'];
                    break;
                case 'returned':
                    $item = ['success', 'ph-arrow-u-down-left', 'Return Recorded', "{$model} was returned. Thank you!", APP_BASE . '/public/borrower/my_requests.php#history'];
                    break;
                case 'returned_late':
                    $item = ['warning', 'ph-arrow-u-down-left', 'Late Return Recorded', "{$model} was returned after its due date.", APP_BASE . '/public/borrower/my_requests.php#history'];
                    break;
                case 'fine_charged':
                    $item = ['warning', 'ph-receipt', 'Fine Charged', "A fine" . ($amount ? " of {$amount}" : '') . " was charged for {$model}. Please settle it at the lab desk.", APP_BASE . '/public/borrower/my_requests.php#history'];
                    break;
                case 'fine_paid':
                    $item = ['success', 'ph-receipt', 'Fine Paid', "Your fine for {$model} was marked as paid.", APP_BASE . '/public/borrower/my_requests.php#history'];
                    break;
                default: // fine_waived
                    $item = ['info', 'ph-receipt', 'Fine Waived', "Your fine for {$model} was waived.", APP_BASE . '/public/borrower/my_requests.php#history'];
            }
            $items[] = $this->make('log-' . $row['log_id'], $item, $ts);
        }

        // Approved items whose pick-up window closes within 24 hours.
        $stm = $this->db->prepare(
            "SELECT r.reservation_id, r.start_at, r.end_at, ec.model_name
             FROM reservations r
             JOIN equipment_catalog ec ON ec.catalog_id = r.catalog_id
             WHERE r.borrower_id = :uid AND r.status = 'approved'"
        );
        $stm->execute([':uid' => $userId]);
        foreach ($stm->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $window = Reservation::pickupWindow($row['start_at'], $row['end_at']);
            if ($window['start'] <= time() && $window['end'] - time() <= 24 * 3600) {
                $items[] = $this->make('pickup-' . $row['reservation_id'],
                    ['warning', 'ph-timer', 'Pick-Up Reminder', "Pick up {$row['model_name']} before " . date('M j, g:i A', $window['end']) . ' or the reservation will be cancelled.', APP_BASE . '/public/borrower/my_requests.php#pending'],
                    $window['end'] - 24 * 3600);
            }
        }

        // Loans that are due within 48 hours or already overdue.
        $stm = $this->db->prepare(
            "SELECT c.checkout_id, UNIX_TIMESTAMP(c.due_at) AS due_ts, (NOW() > c.due_at) AS is_overdue, ec.model_name
             FROM checkouts c
             JOIN reservations r ON r.reservation_id = c.reservation_id
             JOIN equipment_catalog ec ON ec.catalog_id = r.catalog_id
             WHERE r.borrower_id = :uid AND c.returned_at IS NULL
               AND c.due_at <= (NOW() + INTERVAL 48 HOUR)"
        );
        $stm->execute([':uid' => $userId]);

        foreach ($stm->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $due = date('M j, g:i A', (int) $row['due_ts']);
            if ((int) $row['is_overdue'] === 1) {
                $items[] = $this->make('overdue-' . $row['checkout_id'],
                    ['danger', 'ph-warning-circle', 'Overdue Item', "{$row['model_name']} was due {$due}. Please return it to the lab desk as soon as possible; late fines may apply.", APP_BASE . '/public/borrower/my_requests.php#borrowed'],
                    (int) $row['due_ts']);
            } else {
                $items[] = $this->make('due-' . $row['checkout_id'],
                    ['warning', 'ph-clock', 'Return Reminder', "{$row['model_name']} is due back {$due}.", APP_BASE . '/public/borrower/my_requests.php#borrowed'],
                    (int) $row['due_ts'] - 48 * 3600);
            }
        }

        return $items;
    }

    private function forStaff(array $user): array
    {
        $items = [];
        $isAdmin = ($user['role'] === 'admin');
        $can = fn(string $flag) => $isAdmin || (int) ($user[$flag] ?? 0) === 1;

        // Admins use the combined Check-In & Return page; staff have one page per job.
        $handoverUrl = $isAdmin ? APP_URL . '/manage/checkouts.php' : APP_URL . '/staff/handover.php';
        $returnsUrl  = $isAdmin ? APP_URL . '/manage/checkouts.php' : APP_URL . '/staff/return.php';

        if ($can('can_approve_requests')) {
            $rows = $this->db->query(
                "SELECT r.reservation_id, UNIX_TIMESTAMP(r.requested_at) AS ts, ec.model_name,
                        CONCAT(u.first_name, ' ', u.last_name) AS borrower
                 FROM reservations r
                 JOIN equipment_catalog ec ON ec.catalog_id = r.catalog_id
                 JOIN users u ON u.user_id = r.borrower_id
                 WHERE r.status = 'pending'
                 ORDER BY r.requested_at DESC LIMIT 10"
            )->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $items[] = $this->make('pending-' . $row['reservation_id'],
                    ['warning', 'ph-hourglass', 'New Request', "{$row['borrower']} requested {$row['model_name']}.", APP_BASE . '/public/manage/requests.php'],
                    (int) $row['ts']);
            }
        }

        if ($can('can_confirm_pickup')) {
            $rows = $this->db->query(
                "SELECT r.reservation_id, COALESCE(UNIX_TIMESTAMP(r.reviewed_at), UNIX_TIMESTAMP(r.requested_at)) AS ts,
                        ec.model_name, CONCAT(u.first_name, ' ', u.last_name) AS borrower
                 FROM reservations r
                 JOIN equipment_catalog ec ON ec.catalog_id = r.catalog_id
                 JOIN users u ON u.user_id = r.borrower_id
                 WHERE r.status = 'approved'
                 ORDER BY ts DESC LIMIT 10"
            )->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $items[] = $this->make('ready-' . $row['reservation_id'],
                    ['info', 'ph-package', 'Ready for Handover', "{$row['model_name']} is approved and waiting for {$row['borrower']} to pick up.", $handoverUrl],
                    (int) $row['ts']);
            }
        }

        if ($can('can_inspect_returns')) {
            $rows = $this->db->query(
                "SELECT c.checkout_id, UNIX_TIMESTAMP(c.due_at) AS ts, ec.model_name,
                        CONCAT(u.first_name, ' ', u.last_name) AS borrower
                 FROM checkouts c
                 JOIN reservations r ON r.reservation_id = c.reservation_id
                 JOIN equipment_catalog ec ON ec.catalog_id = r.catalog_id
                 JOIN users u ON u.user_id = r.borrower_id
                 WHERE c.returned_at IS NULL AND c.due_at < NOW()
                 ORDER BY c.due_at ASC LIMIT 10"
            )->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $items[] = $this->make('overdue-' . $row['checkout_id'],
                    ['danger', 'ph-warning-circle', 'Overdue Return', "{$row['model_name']} borrowed by {$row['borrower']} is overdue (due " . date('M j, g:i A', (int) $row['ts']) . ').', $returnsUrl],
                    (int) $row['ts']);
            }
        }

        // The items above are things still WAITING for someone, so once everything is handled the
        // feed would be empty. This adds what other people did in the last 7 days (shown only for
        // the jobs this user has), so staff and admins also see activity, not just a to-do list.
        $types = [];
        if ($can('can_approve_requests')) { array_push($types, 'requested', 'cancelled'); }
        if ($can('can_confirm_pickup'))   { array_push($types, 'handover', 'cancelled'); }
        if ($can('can_inspect_returns'))  { array_push($types, 'returned', 'returned_late', 'fine_paid'); }
        $types = array_values(array_unique($types));

        if ($types) {
            $in  = implode(',', array_fill(0, count($types), '?'));
            $stm = $this->db->prepare(
                "SELECT tl.log_id, tl.action_type, tl.remarks, UNIX_TIMESTAMP(tl.action_timestamp) AS ts,
                        ec.model_name, CONCAT(b.first_name, ' ', b.last_name) AS borrower,
                        CONCAT(a.first_name, ' ', a.last_name) AS actor, f.amount_due
                 FROM transaction_logs tl
                 JOIN reservations r ON r.reservation_id = tl.reservation_id
                 JOIN equipment_catalog ec ON ec.catalog_id = r.catalog_id
                 JOIN users b ON b.user_id = r.borrower_id
                 LEFT JOIN users a ON a.user_id = tl.actor_id
                 LEFT JOIN fines f ON f.fine_id = tl.fine_id
                 WHERE tl.action_type IN ($in)
                   AND tl.action_timestamp >= (NOW() - INTERVAL 7 DAY)
                   AND (tl.actor_id IS NULL OR tl.actor_id <> ?)
                   AND NOT (tl.action_type = 'requested' AND r.status = 'pending')   -- still pending: already listed above
                 ORDER BY tl.action_timestamp DESC, tl.log_id DESC
                 LIMIT 15"
            );
            $stm->execute(array_merge($types, [(int) $user['user_id']]));
            $logsUrl = APP_URL . '/manage/logs.php';

            foreach ($stm->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $model = $row['model_name'];
                $who   = $row['borrower'];
                $by    = $row['actor'] ? " by {$row['actor']}" : '';
                $parts = match ($row['action_type']) {
                    'requested'     => ['info', 'ph-hourglass', 'Request Received', "{$who} requested {$model}.", APP_URL . '/manage/requests.php'],
                    'cancelled'     => str_starts_with((string) $row['remarks'], Reservation::EXPIRED_REASON)
                        ? ['warning', 'ph-timer', 'Pick-Up Expired', "{$who} did not collect {$model} in time. The reservation was cancelled.", $logsUrl]
                        : ['warning', 'ph-x-circle', 'Request Cancelled', "{$who} cancelled the request for {$model}.", $logsUrl],
                    'handover'      => ['success', 'ph-check-circle', 'Item Handed Over', "{$model} was handed to {$who}{$by}.", $logsUrl],
                    'returned'      => ['success', 'ph-arrow-u-down-left', 'Item Returned', "{$who} returned {$model}{$by}.", $logsUrl],
                    'returned_late' => ['warning', 'ph-clock-countdown', 'Late Return', "{$who} returned {$model} late{$by}.", $logsUrl],
                    'fine_paid'     => ['success', 'ph-coins', 'Fine Paid', "{$who} paid \u{20B1}" . number_format((float) $row['amount_due'], 2) . " for {$model}.", $logsUrl],
                };
                $items[] = $this->make('log-' . $row['log_id'], $parts, (int) $row['ts']);
            }
        }

        return $items;
    }

    private function make(string $id, array $parts, int $ts): array
    {
        [$tone, $icon, $title, $message, $href] = $parts;
        return ['id' => $id, 'tone' => $tone, 'icon' => $icon, 'title' => $title, 'message' => $message, 'ts' => $ts, 'href' => $href];
    }
}
