<?php
/**
 * CampusEquip integration tests (replaces the old root test.php, which wrote to the REAL database
 * and was reachable from a browser).
 *
 *   CAMPUSEQUIP_DB=campusequipdb_test php tests/run_tests.php
 *
 * Safety: CLI only, and it refuses to run unless the database name ends in "_test". It rebuilds that
 * database from database/campusequipdb.sql each run, so it can never touch your real data.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only.\n"); }

$dbName = getenv('CAMPUSEQUIP_DB') ?: '';
if (!preg_match('/_test$/', $dbName)) {
    fwrite(STDERR, "Refusing to run: set CAMPUSEQUIP_DB to a database whose name ends in _test (got '{$dbName}').\n");
    exit(2);
}

ob_start(); // sessions are exercised below; keep any "headers already sent" noise out of the way
define('APP_INIT', true);
require_once __DIR__ . '/../config/config.php';

// ---- rebuild the test database from the project's own schema ----------------------------
$root = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$root->exec("DROP DATABASE IF EXISTS `{$dbName}`");
$root->exec(str_replace('campusequipdb', $dbName, file_get_contents(__DIR__ . '/../database/campusequipdb.sql')));

require_once __DIR__ . '/../src/Reservation.php';
require_once __DIR__ . '/../src/Checkout.php';
require_once __DIR__ . '/../src/Fine.php';
require_once __DIR__ . '/../src/User.php';
require_once __DIR__ . '/../src/Notification.php';
require_once __DIR__ . '/../src/TransactionLog.php';
require_once __DIR__ . '/../src/Dashboard.php';
require_once __DIR__ . '/../src/Permissions.php';

$db = Database::getConnection();
$pass = 0; $fail = 0;
function check(string $name, bool $ok, string $detail = ''): void {
    global $pass, $fail;
    $ok ? $pass++ : $fail++;
    fwrite(STDOUT, ($ok ? "  PASS  " : "  FAIL  ") . $name . (!$ok && $detail !== '' ? "   -> {$detail}" : '') . "\n");
}
function section(string $t): void { fwrite(STDOUT, "\n== {$t}\n"); }
function row(PDO $db, string $sql, array $p = []) { $s = $db->prepare($sql); $s->execute($p); return $s->fetch(PDO::FETCH_ASSOC); }
function val(PDO $db, string $sql, array $p = []) { $s = $db->prepare($sql); $s->execute($p); return $s->fetchColumn(); }

// ---- fixtures ---------------------------------------------------------------------------
$hash = password_hash('x', PASSWORD_DEFAULT);
$mkUser = function (string $first, string $role, string $idn) use ($db, $hash): int {
    $db->prepare("INSERT INTO users (last_name, first_name, middle_name, email, password_hash, role, id_number) VALUES ('T', :f, '', :e, :h, :r, :i)")
       ->execute([':f' => $first, ':e' => strtolower($first) . '@t.test', ':h' => $hash, ':r' => $role, ':i' => $idn]);
    return (int) $db->lastInsertId();
};
$admin = $mkUser('Admin', 'admin', '1'); $staff = $mkUser('Staff', 'staff', '2');
$b1 = $mkUser('Bea', 'borrower', '3'); $b2 = $mkUser('Ben', 'borrower', '4'); $b3 = $mkUser('Bo', 'borrower', '5');
$db->exec("INSERT INTO staff_permissions (user_id, can_approve_requests, can_inspect_returns, can_confirm_pickup, can_manage_catalog) VALUES ({$staff},1,1,1,0)");
$db->exec("INSERT INTO categories (category_name) VALUES ('Laptops')");
$db->exec("INSERT INTO equipment_catalog (model_name, category_id, max_loan_days, late_fine_rate) VALUES ('Model A', 1, 3, 5.00), ('Model B', 1, 3, 5.00)");
foreach ([['SN-A1', 1], ['SN-A2', 1], ['SN-B1', 2]] as [$sn, $cat]) {
    $db->exec("INSERT INTO serialized_items (catalog_id, serial_number) VALUES ({$cat}, '{$sn}')");
}
$A = 1; $B = 2; $unitA1 = 1; $unitA2 = 2; $unitB1 = 3;

$res = new Reservation(); $co = new Checkout(); $fine = new Fine();
$d0 = date('Y-m-d', strtotime('+10 days')); $d3 = date('Y-m-d', strtotime('+13 days'));
$d5 = date('Y-m-d', strtotime('+15 days')); $d20 = date('Y-m-d', strtotime('+20 days')); $d22 = date('Y-m-d', strtotime('+22 days'));

// ---- #3 due time ------------------------------------------------------------------------
section('Loan dates (fix: due at 17:00, not midnight)');
$w = Reservation::loanWindow($d0, $d3);
check('3-day loan: due time is LOAN_DUE_TIME', $w && $w['end_at'] === "$d3 " . LOAN_DUE_TIME && $w['days'] === 3, json_encode($w));
check('start is 00:00 of the first day', $w && $w['start_at'] === "$d0 00:00:00");
check('same-day loan is rejected', Reservation::loanWindow($d0, $d0) === null);
check('garbage date is rejected', $res->create($b1, $A, 'not-a-date', $d3, '')['success'] === false);

// ---- #2 capacity ------------------------------------------------------------------------
section('Availability when requesting (fix: capacity + duplicate guard)');
$r1 = $res->create($b1, $A, $d0, $d3, 'lab');
check('first request succeeds', $r1['success'] === true, json_encode($r1));
check('stored end_at is 17:00', val($db, 'SELECT TIME(end_at) FROM reservations WHERE reservation_id = ?', [$r1['reservation_id']]) === LOAN_DUE_TIME);
$dup = $res->create($b1, $A, $d0, $d3, 'again');
check('same borrower cannot hold the model twice for overlapping dates', $dup['success'] === false && str_contains($dup['message'], 'already have'), json_encode($dup));
$r2 = $res->create($b2, $A, $d0, $d3, 'x');
check('second borrower gets the 2nd unit', $r2['success'] === true);
$r3 = $res->create($b3, $A, $d0, $d3, 'x');
check('third borrower is refused: only 2 units exist', $r3['success'] === false && str_contains($r3['message'], 'All units'), json_encode($r3));
check('different dates are still bookable', $res->create($b3, $A, $d20, $d22, 'x')['success'] === true);
check('over max_loan_days is refused', $res->create($b3, $B, $d0, $d5, 'x')['success'] === false);
$db->exec("UPDATE serialized_items SET status = 'retired' WHERE item_id = {$unitB1}");
check('model with no in-service units is refused', $res->create($b3, $B, $d0, $d3, 'x')['success'] === false);
$db->exec("UPDATE serialized_items SET status = 'available' WHERE item_id = {$unitB1}");

// ---- approval + date-aware unit list (#2, #12) -------------------------------------------
section('Approval and the unit list (fix: dates decide availability, 2 queries not N+1)');
check('approve request 1 with unit A1', $res->approve($r1['reservation_id'], $unitA1, $admin)['success'] === true);
$conflict = $res->approve($r2['reservation_id'], $unitA1, $admin);
check('same unit for overlapping dates is refused', $conflict['success'] === false);
$pending = $res->listPendingDetailed();
$p2 = array_values(array_filter($pending, fn($r) => (int) $r['reservation_id'] === $r2['reservation_id']))[0] ?? null;
$offered = $p2 ? array_column($p2['available_units'], 'serial_number') : [];
check('unit list for request 2 excludes the booked unit A1', $p2 && $offered === ['SN-A2'], json_encode($offered));
$p3 = array_values(array_filter($pending, fn($r) => $r['borrower_id'] == $b3))[0] ?? null;
check('later request is offered BOTH units (dates do not overlap)', $p3 && count($p3['available_units']) === 2);
check('approve request 2 with unit A2', $res->approve($r2['reservation_id'], $unitA2, $admin)['success'] === true);

// ---- handover / late return / multiple fines (#4) ----------------------------------------
section('Fines (fix: late + damage on the same loan)');
$h = $co->handover($r1['reservation_id'], $staff, true);
check('handover succeeds', $h['success'] === true, json_encode($h));
$coId = (int) $h['checkout_id'];
check('checkout due_at = reservation end_at (17:00)', val($db, 'SELECT TIME(due_at) FROM checkouts WHERE checkout_id = ?', [$coId]) === LOAN_DUE_TIME);
check('reservations.pickup_at column is gone', (int) val($db, "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'reservations' AND column_name = 'pickup_at'") === 0);
$db->exec("UPDATE checkouts SET due_at = NOW() - INTERVAL 51 HOUR WHERE checkout_id = {$coId}");
$ret = $co->processReturn($coId, $staff, 'damaged', 'Cracked screen');
check('late return succeeds and charges 3 days x 5.00', $ret['success'] && $ret['is_late'] && (float) $ret['late_fine'] === 15.0, json_encode($ret));
$lateRow = row($db, "SELECT * FROM fines WHERE checkout_id = ? AND fine_type = 'late'", [$coId]);
check('late fine stores type, days and rate', $lateRow && (int) $lateRow['days_late'] === 3 && (float) $lateRow['rate_applied'] === 5.0 && $lateRow['created_at'] !== null);
$dmg = $fine->charge($coId, $staff, 50.00, 'Cracked screen', 'damage');
check('a damage fine CAN now be added next to the unpaid late fine', $dmg['success'] === true, json_encode($dmg));
check('double-click duplicate is refused', $fine->charge($coId, $staff, 50.00, 'Cracked screen', 'damage')['success'] === false);
check('a second late fine is refused', $fine->charge($coId, $staff, 15.00, 'again', 'late')['success'] === false);
check('unknown fine type is refused', $fine->charge($coId, $staff, 1.00, 'x', 'bogus')['success'] === false);
check('loan now carries 2 fines', count($fine->getByCheckout($coId)) === 2);
$t = Fine::totals($fine->listAllDetailed(), []);
check('Fine::totals: 2 unpaid = 65.00', $t['unpaidCount'] === 2 && abs($t['unpaidTotal'] - 65.0) < 0.001, json_encode($t));
check('returned loan is still offered for charging', in_array($coId, array_column($fine->listChargeableCheckouts(), 'checkout_id')));
check('paying one fine leaves the other unpaid', $fine->markPaid((int) $dmg['fine_id'], $staff)['success'] && Fine::totals($fine->listAllDetailed())['unpaidCount'] === 1);

// ---- notifications (#14b broken links) ---------------------------------------------------
section('Notification links point at pages that exist');
$staffRow = (new User())->getById($staff); $adminRow = (new User())->getById($admin);
$appUrlPath = APP_URL; $publicDir = realpath(__DIR__ . '/../public');
$linksOk = true; $seen = 0;
foreach ([$staffRow, $adminRow] as $u) {
    foreach ((new Notification())->forUser($u) as $n) {
        if (!empty($n['href'])) {
            $seen++;
            $file = $publicDir . substr(strtok($n['href'], '?#'), strlen($appUrlPath));
            if (!is_file($file)) { $linksOk = false; fwrite(STDOUT, "         missing: {$n['href']}\n"); }
        }
    }
}
check("all {$seen} notification hrefs resolve to real files", $linksOk && $seen > 0);

// ---- #5 audit log: system actor ----------------------------------------------------------
section('Automatic expiry is logged as the System (fix)');
$db->exec("INSERT INTO reservations (catalog_id, item_id, borrower_id, status, start_at, end_at, terms_accepted, reviewed_by, reviewed_at)
           VALUES ({$B}, {$unitB1}, {$b3}, 'approved', NOW() - INTERVAL 5 DAY, NOW() + INTERVAL 1 DAY, 1, {$admin}, NOW())");
$expId = (int) $db->lastInsertId();
check('expireLapsedPickups cancels it', $res->expireLapsedPickups() === 1);
check('reservation is cancelled with the expiry reason', val($db, 'SELECT status FROM reservations WHERE reservation_id = ?', [$expId]) === 'cancelled');
$logRow = row($db, "SELECT * FROM transaction_logs WHERE reservation_id = ? AND action_type = 'cancelled'", [$expId]);
check('log row has NULL actor (not the borrower)', $logRow && $logRow['actor_id'] === null);
$names = array_column((new TransactionLog())->listAll(), 'actor_name', 'log_id');
check('the log page shows "System" for it', ($names[$logRow['log_id']] ?? null) === 'System');
check('expiry run twice does nothing more', $res->expireLapsedPickups() === 0);

// ---- #6 one permission model -------------------------------------------------------------
section('Permissions (fix: one shared definition)');
check('admin has every flag', !in_array(false, Permissions::resolve('admin', null), true));
check('borrower has none', !in_array(true, Permissions::resolve('borrower', ['can_approve_requests' => 1]), true));
check('staff gets exactly the flags set', Permissions::allows($staffRow, 'can_approve_requests') && !Permissions::allows($staffRow, 'can_manage_catalog'));
check('page-side lookup agrees with API-side', Permissions::forUserId($db, 'staff', $staff) === Permissions::resolve('staff', $staffRow));
try { Permissions::allows($staffRow, 'can_typo'); check('unknown flag throws', false); } catch (InvalidArgumentException $e) { check('unknown flag throws (typo cannot silently deny)', true); }

// ---- #1 CSRF -----------------------------------------------------------------------------
section('CSRF token');
session_save_path(sys_get_temp_dir());
$tok = csrf_token();
check('token is 64 hex chars and stable within a session', preg_match('/^[a-f0-9]{64}$/', $tok) === 1 && csrf_token() === $tok);
check('correct token verifies', csrf_verify($tok));
check('wrong / empty token is rejected', !csrf_verify('nope') && !csrf_verify(''));

// ---- #7 dashboards -----------------------------------------------------------------------
section('Dashboard class');
$dash = new Dashboard();
$s = $dash->adminStats(true);
check('adminStats returns the KPI keys', isset($s['pendingRequests'], $s['activeLoans'], $s['overdueItems'], $s['availableEquipment'], $s['unpaidFines']['total']));
check('unpaid fines figure matches the fines table', abs((float) $s['unpaidFines']['total'] - 15.0) < 0.001 && (int) $s['unpaidFines']['count'] === 1, json_encode($s['unpaidFines']));
check('staff without fine access get zeros', $dash->adminStats(false)['unpaidFines'] === ['total' => 0, 'count' => 0]);
$bs = $dash->borrowerSummary($b1);
check('borrowerSummary: owed = accruing + unpaid (15.00 unpaid late fine)', abs($bs['owedTotal'] - 15.0) < 0.001 && $bs['unpaidCount'] === 1 && $bs['paidCount'] === 1, json_encode(array_intersect_key($bs, array_flip(['owedTotal','unpaidCount','paidCount']))));

// ---- #11 / #9 links in the code base -----------------------------------------------------
section('Static checks');
$bad = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/..', FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    $p = $f->getPathname();
    if (!preg_match('/\.(php|js)$/', $p) || str_contains($p, '/tests/') || str_contains($p, '/bruno/')) continue;
    $src = file_get_contents($p);
    if ($p !== realpath(__DIR__ . '/../config/config.php') && preg_match('#[\'"]/campusequip/#', $src)) $bad[] = "hardcoded /campusequip in " . basename($p);
    if (preg_match_all('#(?:APP_URL|APP_BASE \. \'/public)\s*\.?\s*\'?(/[a-z_/]+\.php)#', $src, $m)) {
        foreach ($m[1] as $path) { if (!is_file(__DIR__ . '/../public' . $path)) $bad[] = "dead link {$path} in " . basename($p); }
    }
}
check('no hardcoded folder name outside config.php; no dead page links', empty($bad), implode('; ', $bad));
check('old unreferenced pages are gone', !is_file(__DIR__ . '/../public/borrower/home.php') && !is_file(__DIR__ . '/../test.php'));

$root->exec("DROP DATABASE `{$dbName}`");
ob_end_flush();
fwrite(STDOUT, "\n" . ($fail === 0 ? "ALL {$pass} CHECKS PASSED" : "{$fail} FAILED, {$pass} passed") . "\n");
exit($fail === 0 ? 0 : 1);
