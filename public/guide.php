<?php
define('APP_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../src/Settings.php';
// Every signed-in role can open the guide. Each role sees the guide for the pages it can actually use.

$pageTitle  = 'User Guide';
$pageStyles = ['pages/guide'];

$h    = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$role = $_SESSION['role'] ?? 'borrower';
$base = APP_BASE . '/public';

// Admins can read all three guides (to know what staff and borrowers see). Everyone else gets their own.
$views = ['borrower' => 'Borrower', 'staff' => 'Staff', 'admin' => 'Administrator'];
$view  = $role;
if ($role === 'admin' && isset($_GET['view']) && isset($views[$_GET['view']])) {
    $view = $_GET['view'];
}

$dueTime    = date('g:i A', strtotime(LOAN_DUE_TIME));
$deskHours  = trim(Settings::get('desk_hours'));
$deskContact = trim(Settings::get('desk_contact'));

// ---- Topics. 'perm' = staff permission needed (shown only to staff who have it). ----
$topics = [
    'borrower' => [
        ['id' => 'find', 'icon' => 'ph-book-open', 'title' => 'Find equipment', 'page' => 'Equipment Catalog', 'href' => "$base/borrower/catalog.php",
         'intro' => 'See what the school owns and what is free right now.',
         'steps' => ['Open <strong>Equipment Catalog</strong> from the menu on the left.',
                     'Type in the search box at the top of the page to look for an item, or use the filters.',
                     'Each card shows how many units are available. If none are, you can still save the item for later.']],
        ['id' => 'reserve', 'icon' => 'ph-calendar-check', 'title' => 'Reserve an item', 'page' => 'Equipment Catalog', 'href' => "$base/borrower/catalog.php",
         'intro' => 'A reservation is a request. It is not yours until the staff approve it.',
         'steps' => ['On an available item, press <strong>Reserve</strong>.',
                     'In the <strong>New Reservation Request</strong> window, choose your dates and write what the equipment is for.',
                     'Press <strong>Submit</strong>. Your request now waits for the equipment staff.']],
        ['id' => 'track', 'icon' => 'ph-bell-ringing', 'title' => 'Follow your request', 'page' => 'Home', 'href' => "$base/borrower/dashboard.php",
         'intro' => 'You do not have to ask at the desk. The system tells you.',
         'steps' => ['<strong>Home</strong> lists your <em>Pending Requests</em> and the ones that are <em>Approved, ready at the lab desk</em>.',
                     'The bell icon at the top shows a number when something new happens, such as an approval or a decline.',
                     '<strong>Borrowing History</strong> keeps every request you ever made. While a request is still waiting you can press <strong>Cancel Request</strong>.']],
        ['id' => 'pickup', 'icon' => 'ph-package', 'title' => 'Pick it up', 'page' => 'Home', 'href' => "$base/borrower/dashboard.php",
         'intro' => 'Approved items are collected at the equipment desk.',
         'steps' => ['Go to the equipment desk with your <strong>Student ID</strong>, within ' . PICKUP_WINDOW_DAYS . ' days of the approval.',
                     'The staff confirm the handover in the system.',
                     'The item moves to <strong>Currently Possessed</strong> on Home, with a countdown to your due date.']],
        ['id' => 'return', 'icon' => 'ph-arrow-u-down-left', 'title' => 'Return it on time', 'page' => 'Home', 'href' => "$base/borrower/dashboard.php",
         'intro' => 'Bring it back by ' . $dueTime . ' on your due date.',
         'steps' => ['Bring the item and all its accessories to the equipment desk.',
                     'The staff inspect it and record the return. You do not press anything in the system.',
                     'A late return, damage or a missing accessory can lead to a fine.']],
        ['id' => 'fines', 'icon' => 'ph-receipt', 'title' => 'Check your fines', 'page' => 'Penalties & Fines', 'href' => "$base/borrower/fines.php",
         'intro' => 'Every fine is itemized, so you can see what it is for.',
         'steps' => ['Open <strong>Penalties &amp; Fines</strong> to see what you owe and why.',
                     'Pay at the equipment desk. The staff record the payment, and the fine changes to <em>Paid</em>.']],
        ['id' => 'saved', 'icon' => 'ph-bookmark-simple', 'title' => 'Save items for later', 'page' => 'Saved Item', 'href' => "$base/borrower/wishlist.php",
         'intro' => 'A short list of the equipment you plan to borrow.',
         'steps' => ['Press the bookmark on an item in the catalog to save it.',
                     'Open <strong>Saved Item</strong>. Items that are free now sit on one side, with a <strong>Reserve</strong> button. Items that are not free say why.',
                     'Press <strong>Remove</strong> on an item to take it off the list.']],
    ],
    'staff' => [
        ['id' => 'dash', 'icon' => 'ph-house', 'title' => 'Start from the Dashboard', 'page' => 'Dashboard', 'href' => "$base/manage/dashboard.php",
         'intro' => 'A quick look at the day.',
         'steps' => ['<strong>Pending Reservation Requests</strong> shows what is waiting for you.',
                     '<strong>Equipment Availability Breakdown</strong> shows what is free, borrowed or in maintenance.',
                     '<strong>Recent Logs</strong> shows the latest actions in the system.']],
        ['id' => 'requests', 'icon' => 'ph-hourglass', 'title' => 'Approve or decline requests', 'page' => 'Pending & Request', 'href' => "$base/manage/requests.php", 'perm' => 'can_approve_requests',
         'intro' => 'Every request needs a decision.',
         'steps' => ['Open <strong>Pending &amp; Request</strong> and press <strong>Review &amp; Assign</strong> on a request.',
                     'To accept: choose which physical unit (by serial number) goes to the borrower, then press <strong>Approve</strong>.',
                     'To refuse: press <strong>Decline</strong> and give the reason. The borrower gets a notification either way.']],
        ['id' => 'handover', 'icon' => 'ph-package', 'title' => 'Hand over equipment', 'page' => 'Handover', 'href' => "$base/staff/handover.php", 'perm' => 'can_confirm_pickup',
         'intro' => 'When an approved borrower comes to the desk.',
         'steps' => ['Open <strong>Handover</strong>. <em>Ready for Handover</em> lists approved requests.',
                     'Check the borrower\'s Student ID, then press <strong>Handover</strong> on their request.',
                     'Confirm in the <strong>Confirm Handover</strong> window. The loan starts and the due date is set.']],
        ['id' => 'return', 'icon' => 'ph-arrow-u-down-left', 'title' => 'Process a return', 'page' => 'Process Return', 'href' => "$base/staff/return.php", 'perm' => 'can_inspect_returns',
         'intro' => 'Inspect the item before you record it as returned.',
         'steps' => ['Open <strong>Process Return</strong>. <em>Active Returns</em> lists items that are out.',
                     'Press <strong>Return</strong> and record the condition: good, damaged, or missing accessories.',
                     'Late returns are charged automatically. For damage or missing parts, add a fine in <strong>Fines &amp; Penalties</strong>.']],
        ['id' => 'fines', 'icon' => 'ph-receipt', 'title' => 'Manage fines', 'page' => 'Fines & Penalties', 'href' => "$base/manage/fines.php", 'perm' => 'can_inspect_returns',
         'intro' => 'Charge, collect or forgive.',
         'steps' => ['<strong>Charge Fine</strong>: choose the loan, the type, the amount and the reason.',
                     '<strong>Record Payment</strong> when a borrower pays at the desk.',
                     '<strong>Waive Fine</strong> when it should not be collected. Everything is written to the Transaction Logs.']],
        ['id' => 'catalog', 'icon' => 'ph-book-open', 'title' => 'Maintain the equipment catalog', 'page' => 'Equipment Catalog', 'href' => "$base/manage/catalog.php", 'perm' => 'can_manage_catalog',
         'intro' => 'The list of equipment models borrowers can reserve.',
         'steps' => ['<strong>Add New Item</strong> creates an equipment model. <strong>Add Category</strong> creates a new group for it.',
                     'Use the edit button on a model to change its details, or the accessories button to list what comes with it.',
                     'Models are not deleted, so the history stays complete. Archive a model to stop it being offered.']],
        ['id' => 'serialized', 'icon' => 'ph-barcode', 'title' => 'Register and update units', 'page' => 'Serialized Items', 'href' => "$base/manage/serialized.php", 'perm' => 'can_manage_catalog',
         'intro' => 'Each physical item has its own serial number.',
         'steps' => ['<strong>Register New Unit</strong> adds a physical item to a model.',
                     'Use the filter at the top to show one model at a time.',
                     'Edit a unit to change its status: <em>Available</em>, <em>Maintenance</em> or <em>Retired</em>.']],
        ['id' => 'logs', 'icon' => 'ph-clock-counter-clockwise', 'title' => 'Read the Transaction Logs', 'page' => 'Transaction Logs', 'href' => "$base/manage/logs.php",
         'intro' => 'A permanent record of every action. Entries cannot be edited.',
         'steps' => ['Use the filter buttons (Requests, Approved, Handovers, Returns, Fines...) to narrow the list.',
                     'The table is wide: swipe sideways with two fingers to read every column.',
                     'When you reach the end of the table, make a new swipe to go to the next page.']],
    ],
    'admin' => [
        ['id' => 'dash', 'icon' => 'ph-house', 'title' => 'Start from the Dashboard', 'page' => 'Dashboard', 'href' => "$base/manage/dashboard.php",
         'intro' => 'A quick look at the day.',
         'steps' => ['<strong>Pending Reservation Requests</strong> shows what is waiting for a decision.',
                     '<strong>Equipment Availability Breakdown</strong> shows what is free, borrowed or in maintenance.',
                     '<strong>Recent Logs</strong> shows the latest actions in the system.']],
        ['id' => 'requests', 'icon' => 'ph-hourglass', 'title' => 'Approve or decline requests', 'page' => 'Pending & Request', 'href' => "$base/manage/requests.php",
         'intro' => 'Every request needs a decision.',
         'steps' => ['Press <strong>Review &amp; Assign</strong> on a request.',
                     'To accept: choose the physical unit (by serial number), then press <strong>Approve</strong>.',
                     'To refuse: press <strong>Decline</strong> and give the reason. The borrower is notified either way.']],
        ['id' => 'checkouts', 'icon' => 'ph-arrows-left-right', 'title' => 'Hand over and take back equipment', 'page' => 'Check-In & Return', 'href' => "$base/manage/checkouts.php",
         'intro' => 'Administrators have both desk jobs on one page.',
         'steps' => ['<em>Ready for Handover</em>: check the Student ID, then press <strong>Handover</strong> and confirm.',
                     '<em>Active Returns</em>: press <strong>Return</strong> and record the condition of the item.',
                     'Staff members see these as two separate pages, <strong>Handover</strong> and <strong>Process Return</strong>.']],
        ['id' => 'fines', 'icon' => 'ph-receipt', 'title' => 'Manage fines', 'page' => 'Fines & Penalties', 'href' => "$base/manage/fines.php",
         'intro' => 'Charge, collect or forgive.',
         'steps' => ['<strong>Charge Fine</strong>: loan, type, amount and reason.',
                     '<strong>Record Payment</strong> when a borrower pays. <strong>Waive Fine</strong> when it should not be collected.']],
        ['id' => 'catalog', 'icon' => 'ph-book-open', 'title' => 'Equipment Catalog and Serialized Items', 'page' => 'Equipment Catalog', 'href' => "$base/manage/catalog.php",
         'intro' => 'Models are what borrowers reserve. Units are the physical items with serial numbers.',
         'steps' => ['In <strong>Equipment Catalog</strong>: <strong>Add New Item</strong>, <strong>Add Category</strong>, edit a model or manage its accessories.',
                     'In <strong>Serialized Items</strong>: <strong>Register New Unit</strong> and edit a unit\'s status (Available, Maintenance, Retired).']],
        ['id' => 'logs', 'icon' => 'ph-clock-counter-clockwise', 'title' => 'Transaction Logs', 'page' => 'Transaction Logs', 'href' => "$base/manage/logs.php",
         'intro' => 'A permanent record of every action. Entries cannot be edited.',
         'steps' => ['Filter by action type, and swipe sideways to read every column.',
                     'Once the table is at its end, make a new swipe to change page.']],
        ['id' => 'users', 'icon' => 'ph-users', 'title' => 'Manage users and permissions', 'page' => 'User Management', 'href' => "$base/admin/users.php",
         'intro' => 'Decide who can do what.',
         'steps' => ['Open <strong>User Management</strong> and press edit on a person.',
                     'Choose their role: borrower, staff or administrator.',
                     'For staff, tick only the jobs they should do: approve or decline requests, handover, return, manage the catalog. The menu changes for them right away.']],
        ['id' => 'export', 'icon' => 'ph-export', 'title' => 'Export records', 'page' => 'Export Records', 'href' => "$base/admin/export.php",
         'intro' => 'A report of the transaction log for printing or Excel.',
         'steps' => ['Set the filters (dates, borrower, equipment). The table updates by itself.',
                     'Press <strong>Print</strong> for a printable report, or <strong>Export to Excel</strong> for a spreadsheet.',
                     'Both include everything that matches the filters, not only the page you see.']],
        ['id' => 'settings', 'icon' => 'ph-gear', 'title' => 'System settings', 'page' => 'System Settings', 'href' => "$base/admin/settings.php",
         'intro' => 'Messages and switches for everyone.',
         'steps' => ['<strong>Maintenance mode</strong> pauses new reservations and shows your message.',
                     'The announcements appear as a banner for borrowers and for staff.',
                     '<strong>Desk hours</strong> and <strong>desk contact</strong> are shown on the Sign In page, so borrowers know when and where to go.']],
    ],
];

$list = [];
foreach ($topics[$view] as $t) {
    // A real staff member only sees the jobs they have been given (an administrator reading the staff guide sees all).
    if ($view === 'staff' && $role === 'staff' && isset($t['perm']) && !hasPermission($t['perm'])) continue;
    $list[] = $t;
}

$statuses = [
    ['Pending', 'pending', 'The request is waiting for the staff to decide.'],
    ['Approved', 'approved', 'Accepted. Come to the equipment desk to collect it.'],
    ['Declined', 'declined', 'Refused by the staff. The reason is in your notification.'],
    ['Cancelled', 'declined', 'The borrower cancelled the request before pick-up.'],
    ['Checked out', 'approved', 'The item is with the borrower and has a due date.'],
    ['Returned', 'approved', 'Back at the desk and inspected.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../includes/html-head.php'; ?>
</head>
<body>
    <div class="dashboard-layout">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php include __DIR__ . '/../includes/navigation/sidebar-admin.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../includes/navigation/topbar.php'; ?>

            <section class="requests-page guide-page">
                <p class="page-note">
                    <?php if ($view === 'borrower'): ?>
                        How to borrow equipment with <?= $h(APP_NAME) ?>, from finding an item to bringing it back.
                    <?php elseif ($view === 'staff'): ?>
                        How the equipment staff handle requests, handovers, returns and fines.
                    <?php else: ?>
                        How to run <?= $h(APP_NAME) ?>: the desk jobs, the catalog, users, reports and settings.
                    <?php endif; ?>
                </p>

                <?php if ($role === 'admin'): ?>
                    <div class="filter-group guide-views" role="group" aria-label="Choose a guide">
                        <span class="filter-label">Guide for:</span>
                        <?php foreach (['admin', 'staff', 'borrower'] as $v): ?>
                            <a class="filter-pill <?= $view === $v ? 'active' : '' ?>" href="?view=<?= $h($v) ?>"<?= $view === $v ? ' aria-current="page"' : '' ?>><?= $h($views[$v]) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="guide-layout">
                    <nav class="guide-toc" aria-label="On this page">
                        <p class="guide-toc-title">On this page</p>
                        <a href="#around">Finding your way around</a>
                        <?php foreach ($list as $t): ?>
                            <a href="#<?= $h($t['id']) ?>"><?= $h($t['title']) ?></a>
                        <?php endforeach; ?>
                        <?php if ($view !== 'admin'): ?><a href="#words">What the words mean</a><?php endif; ?>
                        <a href="#rules">Rules to remember</a>
                    </nav>

                    <div class="guide-content">
                        <article class="guide-card" id="around">
                            <header class="guide-card-head">
                                <span class="guide-ico"><i class="ph ph-compass" aria-hidden="true"></i></span>
                                <div>
                                    <h2>Finding your way around</h2>
                                    <p>Every page of <?= $h(APP_NAME) ?> has the same four parts.</p>
                                </div>
                            </header>
                            <dl class="guide-parts">
                                <div><dt><i class="ph ph-sidebar-simple" aria-hidden="true"></i>Menu on the left</dt>
                                    <dd>Your pages, grouped by job. The page you are on is highlighted. On a phone, open it with the list button at the top left.</dd></div>
                                <div><dt><i class="ph ph-magnifying-glass" aria-hidden="true"></i>Search box</dt>
                                    <dd>At the top of each page. Type to narrow down the list you are looking at.</dd></div>
                                <div><dt><i class="ph ph-bell-ringing" aria-hidden="true"></i>Bell</dt>
                                    <dd>Shows a number when there is something new for you. Press it to read your notifications.</dd></div>
                                <div><dt><i class="ph ph-user-circle" aria-hidden="true"></i>Your account and Log out</dt>
                                    <dd>At the bottom of the menu: your name, your role, and the Log out button. Always log out on a shared computer.</dd></div>
                                <div><dt><i class="ph ph-arrows-horizontal" aria-hidden="true"></i>Long lists and tables</dt>
                                    <dd>Use <strong>Previous</strong> and <strong>Next</strong> under a list, or swipe it sideways. A wide table scrolls sideways first. At its end, make a new swipe to change page.</dd></div>
                            </dl>
                        </article>

                        <?php foreach ($list as $t): ?>
                            <article class="guide-card" id="<?= $h($t['id']) ?>">
                                <header class="guide-card-head">
                                    <span class="guide-ico"><i class="ph <?= $h($t['icon']) ?>" aria-hidden="true"></i></span>
                                    <div>
                                        <h2><?= $h($t['title']) ?></h2>
                                        <p><?= $t['intro'] ?></p>
                                    </div>
                                    <?php $canOpen = $view === $role || ($role === 'admin' && $view === 'staff' && !in_array($t['id'], ['handover', 'return'], true)); ?>
                                    <?php if ($canOpen): ?>
                                        <a class="btn btn-outline btn-sm guide-open" href="<?= $h($t['href']) ?>">Open <?= $h($t['page']) ?></a>
                                    <?php endif; ?>
                                </header>
                                <ol class="guide-steps">
                                    <?php foreach ($t['steps'] as $step): ?>
                                        <li><?= $step ?></li>
                                    <?php endforeach; ?>
                                </ol>
                            </article>
                        <?php endforeach; ?>

                        <?php if ($view !== 'admin'): ?>
                            <article class="guide-card" id="words">
                                <header class="guide-card-head">
                                    <span class="guide-ico"><i class="ph ph-tag" aria-hidden="true"></i></span>
                                    <div><h2>What the words mean</h2><p>The status tags you will see on requests.</p></div>
                                </header>
                                <ul class="guide-words">
                                    <?php foreach ($statuses as [$name, $cls, $what]): ?>
                                        <li><span class="status-badge <?= $h($cls) ?>"><?= $h($name) ?></span><span><?= $h($what) ?></span></li>
                                    <?php endforeach; ?>
                                </ul>
                            </article>
                        <?php endif; ?>

                        <article class="guide-card" id="rules">
                            <header class="guide-card-head">
                                <span class="guide-ico"><i class="ph ph-scales" aria-hidden="true"></i></span>
                                <div><h2>Rules to remember</h2><p>The same rules apply to everyone.</p></div>
                            </header>
                            <ul class="guide-rules">
                                <li>Items are due back by <strong><?= $h($dueTime) ?></strong> on the due date.</li>
                                <li>Most items can be kept up to <strong><?= (int) DEFAULT_MAX_LOAN_DAYS ?> days</strong>. Each item shows its own maximum when you reserve.</li>
                                <li>An approved request must be collected within <strong><?= (int) PICKUP_WINDOW_DAYS ?> days</strong>.</li>
                                <li>A late return is fined <strong>&#8369;<?= number_format(DEFAULT_LATE_FINE_RATE, 2) ?> per day</strong> by default. Damage or missing accessories are fined by the staff.</li>
                                <li>The borrower is responsible for what they borrow until it is returned and inspected.</li>
                            </ul>
                            <?php if ($deskHours !== '' || $deskContact !== ''): ?>
                                <p class="guide-desk"><i class="ph ph-storefront" aria-hidden="true"></i>
                                    <span><strong>Equipment desk</strong>
                                    <?php if ($deskHours !== ''): ?> &middot; <?= $h($deskHours) ?><?php endif; ?>
                                    <?php if ($deskContact !== ''): ?> &middot; <?= $h($deskContact) ?><?php endif; ?></span></p>
                            <?php endif; ?>
                        </article>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/navigation/footer.php'; ?>
</body>
</html>
