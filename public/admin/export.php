<?php
// Admin "Export Records": the transaction log (every request, approval, handover, return and fine entry).
// Filter it by borrower, equipment, status and dates, look at it page by page, then print it or download it as an
// Excel file. There is no table picker on purpose: the page only ever receives filter values, never database
// table or column names.
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../src/Export.php';
require_once __DIR__ . '/../../src/XlsxWriter.php';
requireRole('admin');

$pageTitle = 'Export Records';
$pageStyles = ['pages/admin-export'];

// Accept only real YYYY-MM-DD dates.
function cleanDate($value): ?string
{
    if (!is_string($value) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
        return null;
    }
    return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $value : null;
}

$export  = new Export();
$options = $export->filterOptions();

$borrower  = (int) ($_GET['borrower'] ?? 0);
$equipment = (int) ($_GET['equipment'] ?? 0);
$status    = (string) ($_GET['status'] ?? '');
$from      = cleanDate($_GET['from'] ?? null);
$to        = cleanDate($_GET['to'] ?? null);
if (!isset($options['borrowers'][$borrower]))  $borrower  = 0;
if (!isset($options['equipment'][$equipment])) $equipment = 0;
if (!isset(Export::STATUSES[$status]))         $status    = '';
if ($from !== null && $to !== null && $from > $to) {
    [$from, $to] = [$to, $from];
}

// Only filters that are actually set go into links, so URLs stay short.
$filters = array_filter([
    'borrower'  => $borrower ?: null,
    'equipment' => $equipment ?: null,
    'status'    => $status ?: null,
    'from'      => $from,
    'to'        => $to,
], fn($v) => $v !== null);
$activeCount = count($filters);

$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$rangeText = ($from || $to)
    ? ('Requested ' . ($from ? 'from ' . $from : '') . ($from && $to ? ' ' : '') . ($to ? 'to ' . $to : ''))
    : 'All dates';
$filterText = [];
if ($borrower)  $filterText[] = 'Borrower: ' . $options['borrowers'][$borrower];
if ($equipment) $filterText[] = 'Equipment: ' . $options['equipment'][$equipment];
if ($status)    $filterText[] = 'Status: ' . Export::STATUSES[$status];
$filterText[] = $rangeText;

// ---- Excel download (.xlsx): every row that matches the filters, not just the current page ----
if (($_GET['format'] ?? '') === 'xlsx') {
    $all = $export->report($filters);
    $filename = 'campusequip-transaction-log-' . date('Ymd-His') . '.xlsx';
    try {
        $bytes = XlsxWriter::build(
            'Transaction Log',
            $all['headers'],
            $all['rows'],
            ['Fine (PHP)'] // written as real numbers so Excel can total them
        );
    } catch (RuntimeException $e) {
        http_response_code(500);
        exit('Excel export failed: ' . $e->getMessage());
    }
    XlsxWriter::download($filename, $bytes);
    exit;
}

// ---- Printable report (?print=1): a clean A4 landscape document. The page opens the browser's print
// dialog by itself; choose "Save as PDF" there to get a PDF file. Every matching row is included. ----
if (isset($_GET['print'])) {
    $all  = $export->report($filters);
    $rows = $all['rows'];

    $nice = fn($v) => $v === '' ? '—' : date('M j, Y g:i A', strtotime($v));
    $actionCounts = [];
    $fineTotal = 0.0;
    foreach ($rows as $r) {
        $actionCounts[$r[Export::COL_ACTION]] = ($actionCounts[$r[Export::COL_ACTION]] ?? 0) + 1;
        if ($r[Export::COL_FINE] !== '') {
            $fineTotal += (float) $r[Export::COL_FINE];
        }
    }
    $rangeLabel = ($from || $to)
        ? 'Requested ' . ($from ? date('M j, Y', strtotime($from)) : 'the beginning') . ' to ' . ($to ? date('M j, Y', strtotime($to)) : 'today')
        : 'All dates';
    $criteria = [
        'Borrower'  => $borrower ? $options['borrowers'][$borrower] : 'All',
        'Equipment' => $equipment ? $options['equipment'][$equipment] : 'All',
        'Status'    => $status ? Export::STATUSES[$status] : 'All',
        'Period'    => $rangeLabel,
    ];
    $preparedBy = $_SESSION['full_name'] ?? 'Administrator';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transaction Log Report - <?= $h(date('Y-m-d')) ?></title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", Arial, sans-serif; font-size: 11px; color: #0f172a; background: #e2e8f0; }
        .toolbar { position: sticky; top: 0; display: flex; gap: 0.5rem; align-items: center; justify-content: space-between;
                   padding: 0.6rem 1rem; background: #1e3a8a; color: #fff; }
        .toolbar button { padding: 0.4rem 0.9rem; border: 0; border-radius: 0.375rem; font-weight: 600; cursor: pointer; }
        .toolbar .primary { background: #fff; color: #1e3a8a; }
        .toolbar .ghost { background: transparent; color: #fff; border: 1px solid rgba(255,255,255,.6); }
        .sheet { width: 277mm; max-width: 100%; margin: 1rem auto; padding: 10mm; background: #fff; }
        table.letterhead { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.letterhead td { border: 0; padding: 0; vertical-align: middle; background: none; }
        .logo { width: 38px; height: 38px; display: block; }
        .brand { font-size: 20px; font-weight: 700; letter-spacing: .04em; color: #1e3a8a; line-height: 1.1; }
        .tagline { font-size: 9.5px; color: #64748b; letter-spacing: .03em; }
        .meta { text-align: right; font-size: 10px; color: #475569; line-height: 1.6; }
        .rule { height: 0; border-top: 3px solid #1e3a8a; border-bottom: 1px solid #1e3a8a; padding-top: 2px; margin: 8px 0 14px; }
        .title { text-align: center; margin: 0 0 12px; }
        .title h1 { margin: 0; font-size: 19px; letter-spacing: .14em; text-transform: uppercase; color: #0f172a; }
        .title p { margin: 3px 0 0; font-size: 10.5px; color: #475569; }
        table.details { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 10.5px; }
        table.details th { width: 11%; text-align: left; background: #f1f5f9; color: #475569; font-size: 9.5px; text-transform: uppercase;
                           letter-spacing: .05em; padding: 5px 8px; border: 1px solid #cbd5e1; }
        table.details td { width: 39%; padding: 5px 8px; border: 1px solid #cbd5e1; background: #fff; font-weight: 600; word-break: break-word; }
        table.summary { width: 100%; border-collapse: collapse; margin-bottom: 14px; border: 1px solid #1e3a8a; }
        table.summary td { text-align: center; padding: 7px 4px; border-left: 1px solid #cbd5e1; background: #f8fafc; }
        table.summary td:first-child { border-left: 0; background: #1e3a8a; color: #fff; }
        table.summary td span { display: block; font-size: 8.5px; text-transform: uppercase; letter-spacing: .06em; color: #64748b; }
        table.summary td:first-child span { color: #c7d2fe; }
        table.summary td strong { font-size: 15px; line-height: 1.3; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }          /* header repeats on every printed page */
        tr { break-inside: avoid; }
        th { background: #1e3a8a; color: #fff; text-align: left; font-size: 9.5px; padding: 5px 6px; }
        td { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; word-break: break-word; }
        tbody tr:nth-child(even) td { background: #f8fafc; }
        td small { display: block; color: #64748b; font-size: 9px; }
        .num { text-align: right; white-space: nowrap; }
        .st { font-weight: 600; }
        .st-Overdue { color: #b91c1c; } .st-Returned { color: #15803d; } .st-Checked { color: #1d4ed8; }
        .empty { text-align: center; padding: 18px; color: #64748b; }
        .sign { display: flex; justify-content: space-between; gap: 40px; margin-top: 26px; break-inside: avoid; }
        .sign div { flex: 1; border-top: 1px solid #0f172a; padding-top: 3px; font-size: 10px; text-align: center; }
        .foot { margin-top: 10px; font-size: 9px; color: #64748b; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { width: auto; margin: 0; padding: 0; }
            th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tbody tr:nth-child(even) td, table.summary td, table.details th, .rule { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <span>Print preview &mdash; in the print window, set <strong>Destination</strong> to <strong>Save as PDF</strong> to get a PDF file.</span>
        <span>
            <button type="button" class="primary" id="doPrint">Print / Save as PDF</button>
            <button type="button" class="ghost" id="doClose">Close</button>
        </span>
    </div>

    <main class="sheet">
        <table class="letterhead">
            <tr>
                <td style="width:46px"><img class="logo" src="<?= APP_BASE ?>/public/assets/images/logo-icon.svg" alt=""></td>
                <td>
                    <div class="brand"><?= $h(strtoupper(APP_NAME)) ?></div>
                    <div class="tagline">Equipment Reservation &amp; Asset Management</div>
                </td>
                <td class="meta">
                    Date generated: <strong><?= $h(date('F j, Y')) ?></strong><br>
                    Time: <?= $h(date('g:i A')) ?>
                </td>
            </tr>
        </table>
        <div class="rule"></div>

        <div class="title">
            <h1>Transaction Log Report</h1>
            <p><?= $h($rangeLabel) ?></p>
        </div>

        <table class="details" aria-label="Report filters">
            <tr>
                <th>Borrower</th><td><?= $h($criteria['Borrower']) ?></td>
                <th>Equipment</th><td><?= $h($criteria['Equipment']) ?></td>
            </tr>
            <tr>
                <th>Status</th><td><?= $h($criteria['Status']) ?></td>
                <th>Prepared by</th><td><?= $h($preparedBy) ?></td>
            </tr>
        </table>

        <table class="summary" aria-label="Summary">
            <tr>
                <td><span>Total entries</span><strong><?= (int) $all['total'] ?></strong></td>
                <?php foreach ($actionCounts as $label => $n): ?>
                    <td><span><?= $h($label) ?></span><strong><?= (int) $n ?></strong></td>
                <?php endforeach; ?>
                <td><span>Fines in log</span><strong>&#8369;<?= number_format($fineTotal, 2) ?></strong></td>
            </tr>
        </table>

        <table>
            <thead>
                <tr>
                    <th>Log</th><th>Timestamp</th><th>Borrower</th><th>Equipment</th>
                    <th>Action</th><th>Performed by</th><th class="num">Fine (PHP)</th><th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="8" class="empty">No log entries match these filters.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><?= $h($r[Export::COL_LOG]) ?><?php if ($r[Export::COL_REQ] !== ''): ?><br><small><?= $h($r[Export::COL_REQ]) ?></small><?php endif; ?></td>
                        <td><?= $h($nice($r[Export::COL_TIME])) ?></td>
                        <td><?= $h($r[Export::COL_BORROWER] !== '' ? $r[Export::COL_BORROWER] : '—') ?><?php if ($r[Export::COL_ID] !== ''): ?><br><small><?= $h($r[Export::COL_ID]) ?></small><?php endif; ?></td>
                        <td><?= $h($r[Export::COL_EQUIPMENT] !== '' ? $r[Export::COL_EQUIPMENT] : '—') ?><?php if ($r[Export::COL_SERIAL] !== ''): ?><br><small><?= $h($r[Export::COL_SERIAL]) ?></small><?php endif; ?></td>
                        <td><?= $h($r[Export::COL_ACTION]) ?></td>
                        <td><?= $h($r[Export::COL_ACTOR]) ?></td>
                        <td class="num"><?= $r[Export::COL_FINE] !== '' ? $h(number_format((float) $r[Export::COL_FINE], 2)) : '—' ?></td>
                        <td><?= $h($r[Export::COL_REMARKS] !== '' ? $r[Export::COL_REMARKS] : '—') ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <div class="sign">
            <div>Prepared by: <?= $h($preparedBy) ?></div>
            <div>Noted by</div>
        </div>
        <p class="foot">End of report &middot; <?= (int) $all['total'] ?> entr<?= $all['total'] === 1 ? 'y' : 'ies' ?> &middot; <?= $h(APP_NAME) ?></p>
    </main>

    <script>
        document.getElementById('doPrint').addEventListener('click', function () { window.print(); });
        document.getElementById('doClose').addEventListener('click', function () { window.close(); });
        window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });
    </script>
</body>
</html>
    <?php
    exit;
}

// ---- Normal page: every matching entry; the page cuts them into pages in the browser (filters.js) ----
$data  = $export->report($filters);
$total = $data['total'];

// Badge colour of each action (same colours as the Transaction Log page).
$badgeStyles = [
    'Requested' => 'pending', 'Approved' => 'approved', 'Declined' => 'declined', 'Cancelled' => 'declined',
    'Handover' => 'approved', 'Returned' => 'approved', 'Returned late' => 'returned',
    'Fine charged' => 'declined', 'Fine paid' => 'approved', 'Fine waived' => 'pending',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include __DIR__ . '/../../includes/html-head.php'; ?>
</head>

<body>

    <div class="dashboard-layout">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php include __DIR__ . '/../../includes/navigation/sidebar-admin.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../../includes/navigation/topbar.php'; ?>

            <section class="requests-page" id="exportPage">
                <p class="page-note no-print">The transaction log. Set the filters you need &mdash; the table updates by itself. Then print it or download it as an Excel file (everything that matches, not just this page).</p>

                <!-- Toolbar: filters toggle (same look as the filter choices on other pages) + actions -->
                <div class="requests-toolbar no-print">
                    <button type="button" class="filter-pill export-filter-toggle" id="exportFilterToggle" aria-expanded="true" aria-controls="exportFilters">
                        <i class="ph ph-funnel" aria-hidden="true"></i><span>Filters</span>
                        <span class="export-filter-count" id="exportFilterCount"<?= $activeCount ? '' : ' hidden' ?>><?= (int) $activeCount ?></span>
                        <i class="ph ph-caret-down export-filter-caret" aria-hidden="true"></i>
                    </button>

                    <div class="admin-export-s3">
                        <a class="btn btn-outline btn-sm btn-rect admin-export-s1" id="exportPrint" target="_blank" rel="noopener"
                           href="?<?= $h(http_build_query($filters + ['print' => 1])) ?>"><i class="ph ph-printer" aria-hidden="true"></i><span class="btn-text">Print</span></a>
                        <a class="btn btn-primary btn-sm btn-rect admin-export-s1" id="exportExcel"
                           href="?<?= $h(http_build_query($filters + ['format' => 'xlsx'])) ?>"><i class="ph ph-microsoft-excel-logo" aria-hidden="true"></i><span class="btn-text">Export to Excel</span></a>
                    </div>
                </div>

                <!-- Filters: no Apply button, they apply as soon as something is picked. The panel stays open until the admin closes it. -->
                <form method="get" class="export-filters no-print" id="exportFilters" autocomplete="off">
                    <div class="export-filter-field">
                        <label class="modal-label" for="f-borrower">Borrower</label>
                        <select class="input-field" id="f-borrower" name="borrower" data-ce-dropdown>
                            <option value="">All borrowers</option>
                            <?php foreach ($options['borrowers'] as $id => $label): ?>
                                <option value="<?= (int) $id ?>"<?= $id === $borrower ? ' selected' : '' ?>><?= $h($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="export-filter-field">
                        <label class="modal-label" for="f-equipment">Equipment</label>
                        <select class="input-field" id="f-equipment" name="equipment" data-ce-dropdown>
                            <option value="">All equipment</option>
                            <?php foreach ($options['equipment'] as $id => $label): ?>
                                <option value="<?= (int) $id ?>"<?= $id === $equipment ? ' selected' : '' ?>><?= $h($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="export-filter-field">
                        <label class="modal-label" for="f-from">Requested from</label>
                        <input type="date" class="input-field date-compact" id="f-from" name="from" value="<?= $h($from) ?>">
                    </div>
                    <div class="export-filter-field">
                        <label class="modal-label" for="f-to">Requested to</label>
                        <input type="date" class="input-field date-compact" id="f-to" name="to" value="<?= $h($to) ?>">
                    </div>

                    <!-- Status: a few short choices, so they are pills like the filters on the other pages -->
                    <div class="export-filter-field export-filter-status">
                        <span class="modal-label" id="f-status-label">Status</span>
                        <div class="export-status-row">
                            <div class="export-status-pills" role="group" aria-labelledby="f-status-label" id="exportStatusPills">
                                <button type="button" class="filter-pill<?= $status === '' ? ' active' : '' ?>" data-value="">All</button>
                                <?php foreach ($options['statuses'] as $key => $label): ?>
                                    <button type="button" class="filter-pill<?= $key === $status ? ' active' : '' ?>" data-value="<?= $h($key) ?>" title="<?= $h($label) ?>"><?= $h(trim(explode(' (', $label)[0])) ?></button>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="f-status" name="status" value="<?= $h($status) ?>">
                            <a class="btn btn-outline btn-sm btn-rect admin-export-s1 export-clear" id="exportClear" href="?"<?= $activeCount ? '' : ' hidden' ?>><i class="ph ph-eraser" aria-hidden="true"></i><span class="btn-text">Clear filters</span></a>
                        </div>
                    </div>
                </form>

                <!-- Results: this block is swapped in place when a filter changes -->
                <div id="exportResults" aria-live="polite">
                    <p class="results-count no-print">
                        <strong><?= (int) $total ?></strong> log entr<?= $total === 1 ? 'y' : 'ies' ?> &middot; <?= $h(implode(' · ', $filterText)) ?>
                    </p>

                    <div hidden data-list data-items="#exportBody tr[data-row]" data-page-size="10" data-pager="#exportPager" data-pager-always
                         data-empty="#exportEmptyRow"></div>

                    <div class="table-card export-table">
                        <table class="data-table export-log-table">
                            <thead>
                                <tr>
                                    <th scope="col">Log</th>
                                    <th scope="col">Timestamp</th>
                                    <th scope="col">Borrower</th>
                                    <th scope="col">Equipment</th>
                                    <th scope="col">Action</th>
                                    <th scope="col">Performed by</th>
                                    <th scope="col">Fine</th>
                                    <th scope="col">Remarks / Notes</th>
                                </tr>
                            </thead>
                            <tbody id="exportBody">
                                <?php if (empty($data['rows'])): ?>
                                    <tr>
                                        <td colspan="8" class="text-muted admin-export-s4">
                                            No log entries match these filters.
                                        </td>
                                    </tr>
                                <?php else: foreach ($data['rows'] as $r): ?>
                                    <tr data-row>
                                        <td class="cell-strong"><?= $h($r[Export::COL_LOG]) ?><?php if ($r[Export::COL_REQ] !== ''): ?><br><small class="export-sub"><?= $h($r[Export::COL_REQ]) ?></small><?php endif; ?></td>
                                        <td><?= $h(substr($r[Export::COL_TIME], 0, 10)) ?><br><small class="export-sub"><?= $h(substr($r[Export::COL_TIME], 11)) ?></small></td>
                                        <td><?= $h($r[Export::COL_BORROWER] !== '' ? $r[Export::COL_BORROWER] : '—') ?><?php if ($r[Export::COL_ID] !== ''): ?><br><small class="export-sub"><?= $h($r[Export::COL_ID]) ?></small><?php endif; ?></td>
                                        <td><?= $h($r[Export::COL_EQUIPMENT] !== '' ? $r[Export::COL_EQUIPMENT] : '—') ?><?php if ($r[Export::COL_SERIAL] !== ''): ?><br><small class="export-sub"><?= $h($r[Export::COL_SERIAL]) ?></small><?php endif; ?></td>
                                        <td>
                                            <span class="status-badge <?= $badgeStyles[$r[Export::COL_ACTION]] ?? 'pending' ?>"><?= $h($r[Export::COL_ACTION]) ?></span>
                                            <?php if ($r[Export::COL_REQ_STATUS] !== ''): ?><br><small class="export-sub">Request: <?= $h($r[Export::COL_REQ_STATUS]) ?></small><?php endif; ?>
                                        </td>
                                        <td><?= $h($r[Export::COL_ACTOR]) ?></td>
                                        <td class="cell-strong"><?= $r[Export::COL_FINE] !== '' ? '&#8369;' . $h(number_format((float) $r[Export::COL_FINE], 2)) : '—' ?></td>
                                        <td><?= $h($r[Export::COL_REMARKS] !== '' ? $r[Export::COL_REMARKS] : '—') ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                <tr id="exportEmptyRow" style="display:none;">
                                    <td colspan="8" class="text-muted admin-export-s4">
                                        No log entries match your search.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pager no-print" id="exportPager"></div>
                </div>
            </section>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/navigation/footer.php'; ?>
    <script src="<?= APP_BASE ?>/public/assets/js/filters.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/filters.js') ?: 1 ?>"></script>
    <script src="<?= APP_BASE ?>/public/assets/js/pages/admin-export.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/pages/admin-export.js') ?: 1 ?>"></script>
</body>

</html>
