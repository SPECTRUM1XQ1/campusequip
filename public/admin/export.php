<?php
// Admin "Export Records": pick a table, look at it first, then print it or download it as CSV.
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../src/Export.php';
requireRole('admin');

$pageTitle = 'Export Records';

$table = $_GET['table'] ?? 'reservations';
if (!isset(Export::TABLES[$table])) {
    $table = 'reservations';
}

// Accept only real YYYY-MM-DD dates.
function cleanDate($value): ?string
{
    if (!is_string($value) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
        return null;
    }
    return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $value : null;
}
$from = cleanDate($_GET['from'] ?? null);
$to   = cleanDate($_GET['to'] ?? null);
if ($from !== null && $to !== null && $from > $to) {
    [$from, $to] = [$to, $from];
}

$data = (new Export())->get($table, $from, $to);

// ---- CSV download ----
if (($_GET['format'] ?? '') === 'csv') {
    $filename = 'campusequip-' . $table . '-' . date('Ymd-His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel / Sheets read the characters correctly
    fputcsv($out, $data['headers'], ',', '"', '');
    foreach ($data['rows'] as $row) {
        $row = array_map(function ($cell) {
            // Stop spreadsheet formulas: a cell starting with = + - @ would be run by Excel / Sheets.
            return ($cell !== '' && strpos("=+-@\t\r", $cell[0]) !== false) ? "'" . $cell : $cell;
        }, $row);
        fputcsv($out, $row, ',', '"', '');
    }
    fclose($out);
    exit;
}

$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$rangeText = ($from || $to)
    ? ('From ' . ($from ?: 'the beginning') . ' to ' . ($to ?: 'today'))
    : 'All dates';
$query = array_filter(['table' => $table, 'from' => $from, 'to' => $to]);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include __DIR__ . '/../../includes/html-head.php'; ?>
</head>

<body>

    <div class="dashboard-layout">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php include __DIR__ . '/../../includes/sidebar-admin.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../../includes/topbar.php'; ?>

            <section class="requests-page">
                <p class="page-note no-print">1. Choose a table &nbsp;&rarr;&nbsp; 2. Look it over &nbsp;&rarr;&nbsp; 3. Print it or download it as CSV (opens in Excel / Google Sheets).</p>

                <!-- Printed header (only shows on paper) -->
                <div class="print-only">
                    <h2><?= $h(APP_NAME) ?> &mdash; <?= $h($data['title']) ?></h2>
                    <p><?= $h($rangeText) ?> &middot; <?= count($data['rows']) ?> records &middot; Generated <?= $h(date('Y-m-d H:i')) ?> by <?= $h($_SESSION['full_name'] ?? 'Admin') ?></p>
                </div>

                <!-- 1. Which table -->
                <div class="requests-toolbar no-print">
                    <div class="filter-group" role="group" aria-label="Choose a table">
                        <span class="filter-label">Table:</span>
                        <?php foreach (Export::TABLES as $key => $label): ?>
                            <a class="filter-pill<?= $key === $table ? ' active' : '' ?>" style="text-decoration: none;"
                               href="?<?= $h(http_build_query(array_filter(['table' => $key, 'from' => $from, 'to' => $to]))) ?>"><?= $h($label) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Date range + actions -->
                <div class="requests-toolbar no-print">
                    <form method="get" class="export-range" style="display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap;">
                        <input type="hidden" name="table" value="<?= $h($table) ?>">
                        <div>
                            <label class="modal-label" for="from">From</label>
                            <input type="date" class="input-field date-compact" id="from" name="from" value="<?= $h($from) ?>">
                        </div>
                        <div>
                            <label class="modal-label" for="to">To</label>
                            <input type="date" class="input-field date-compact" id="to" name="to" value="<?= $h($to) ?>">
                        </div>
                        <button type="submit" class="btn btn-outline btn-sm btn-rect"><i class="ph ph-funnel" aria-hidden="true"></i><span class="btn-text">Apply</span></button>
                        <?php if ($from || $to): ?>
                            <a class="btn btn-outline btn-sm btn-rect" style="text-decoration: none;" href="?table=<?= $h($table) ?>"><i class="ph ph-eraser" aria-hidden="true"></i><span class="btn-text">Clear</span></a>
                        <?php endif; ?>
                    </form>

                    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                        <button type="button" class="btn btn-outline btn-sm btn-rect" onclick="window.print()"><i class="ph ph-printer" aria-hidden="true"></i><span class="btn-text">Print</span></button>
                        <a class="btn btn-primary btn-sm btn-rect" style="text-decoration: none;"
                           href="?<?= $h(http_build_query($query + ['format' => 'csv'])) ?>"><i class="ph ph-download-simple" aria-hidden="true"></i><span class="btn-text">Download CSV</span></a>
                    </div>
                </div>

                <p class="results-count no-print"><strong><?= count($data['rows']) ?></strong> <?= $h(strtolower($data['title'])) ?> &middot; <?= $h($rangeText) ?></p>

                <!-- 2. The table itself -->
                <div class="table-card export-table">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <?php foreach ($data['headers'] as $title): ?>
                                    <th scope="col"><?= $h($title) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data['rows'])): ?>
                                <tr>
                                    <td colspan="<?= count($data['headers']) ?>" style="text-align:center; padding: 2.5rem;" class="text-muted">
                                        No records found<?= ($from || $to) ? ' for this date range' : '' ?>.
                                    </td>
                                </tr>
                            <?php else: foreach ($data['rows'] as $row): ?>
                                <tr>
                                    <?php foreach ($row as $cell): ?>
                                        <td><?= $h($cell !== '' ? $cell : '—') ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>

</html>
