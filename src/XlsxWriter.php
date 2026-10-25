<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

/**
 * Tiny dependency-free .xlsx writer (pure PHP: no Composer and no zip extension needed).
 * Makes one sheet: bold shaded header row, frozen header, filter arrows, auto column widths.
 *
 * Text is stored as plain text, so Excel never runs a cell as a formula (no "=" / "+" / "-" / "@"
 * trick needed) and IDs such as 0123456 keep their leading zeros.
 */
class XlsxWriter
{
    /**
     * @param string   $sheetName   Tab name (cleaned to Excel's rules).
     * @param string[] $headers     Header row.
     * @param array[]  $rows        Data rows (same column count as $headers).
     * @param string[] $moneyHeaders Header names whose cells are written as numbers (0.00).
     * @return string The finished .xlsx file contents.
     */
    public static function build(string $sheetName, array $headers, array $rows, array $moneyHeaders = []): string
    {
        $moneyCols = [];
        foreach ($headers as $i => $title) {
            if (in_array($title, $moneyHeaders, true)) {
                $moneyCols[$i] = true;
            }
        }

        // Column widths from the longest value (capped so a long purpose/remarks cell isn't huge).
        $widths = [];
        foreach ($headers as $i => $title) {
            $widths[$i] = self::len((string) $title);
        }
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $len = self::len((string) $cell);
                if ($len > ($widths[$i] ?? 0)) {
                    $widths[$i] = $len;
                }
            }
        }

        $lastCol = self::col(max(count($headers) - 1, 0));
        $lastRow = count($rows) + 1;

        $xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<dimension ref="A1:' . $lastCol . $lastRow . '"/>';
        $xml .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="A2" sqref="A2"/></sheetView></sheetViews>';
        $xml .= '<sheetFormatPr defaultRowHeight="15"/>';
        $xml .= '<cols>';
        foreach ($widths as $i => $w) {
            $w = min(max($w + 3, 10), 60);
            $xml .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
        }
        $xml .= '</cols><sheetData>';

        // Header row (style 1 = bold on grey).
        $xml .= '<row r="1">';
        foreach ($headers as $i => $title) {
            $xml .= self::textCell(self::col($i) . '1', (string) $title, 1);
        }
        $xml .= '</row>';

        // Data rows.
        foreach ($rows as $r => $row) {
            $n = $r + 2;
            $xml .= '<row r="' . $n . '">';
            foreach (array_values($row) as $i => $cell) {
                $ref = self::col($i) . $n;
                if (isset($moneyCols[$i]) && $cell !== '' && is_numeric($cell)) {
                    $xml .= '<c r="' . $ref . '" s="2"><v>' . (float) $cell . '</v></c>';
                } elseif ($cell === '' || $cell === null) {
                    continue; // leave empty cells empty
                } else {
                    $xml .= self::textCell($ref, (string) $cell, 0);
                }
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData>';
        $xml .= '<autoFilter ref="A1:' . $lastCol . $lastRow . '"/>';
        $xml .= '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>';
        $xml .= '<pageSetup orientation="landscape"/>';
        $xml .= '</worksheet>';

        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE2E8F0"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="2" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . self::esc(self::sheetName($sheetName)) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '<definedNames><definedName name="_xlnm._FilterDatabase" localSheetId="0" hidden="1">'
            . self::esc("'" . str_replace("'", "''", self::sheetName($sheetName)) . "'!" . '$A$1:$' . $lastCol . '$' . $lastRow)
            . '</definedName></definedNames></workbook>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';

        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';

        return self::zip([
            '[Content_Types].xml'        => $contentTypes,
            '_rels/.rels'                => $rootRels,
            'xl/workbook.xml'            => $workbook,
            'xl/_rels/workbook.xml.rels' => $workbookRels,
            'xl/styles.xml'              => $styles,
            'xl/worksheets/sheet1.xml'   => $xml,
        ]);
    }

    /** Sends the finished file to the browser as a download. */
    public static function download(string $filename, string $bytes): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($bytes));
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $bytes;
    }

    /**
     * Minimal ZIP writer in plain PHP, so the PHP "zip" extension is NOT needed.
     * Uses deflate when zlib is available, otherwise stores the files uncompressed.
     *
     * @param array<string,string> $files path => contents
     */
    private static function zip(array $files): string
    {
        $time = (int) date('G') << 11 | (int) date('i') << 5 | (int) date('s') >> 1;
        $day  = ((int) date('Y') - 1980) << 9 | (int) date('n') << 5 | (int) date('j');

        $data = '';
        $central = '';
        $count = 0;
        foreach ($files as $name => $content) {
            $crc  = crc32($content);
            $size = strlen($content);
            $packed = function_exists('gzdeflate') ? gzdeflate($content, 6) : false;
            if ($packed !== false && strlen($packed) < $size) {
                $method = 8;
            } else {
                $method = 0;
                $packed = $content;
            }
            $csize  = strlen($packed);
            $offset = strlen($data);

            $data .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, $method, $time, $day, $crc, $csize, $size, strlen($name), 0)
                . $name . $packed;
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, $method, $time, $day, $crc, $csize, $size,
                strlen($name), 0, 0, 0, 0, 0, $offset) . $name;
            $count++;
        }

        return $data . $central
            . pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, strlen($central), strlen($data), 0);
    }

    private static function textCell(string $ref, string $text, int $style): string
    {
        return '<c r="' . $ref . '" t="inlineStr"' . ($style ? ' s="' . $style . '"' : '') . '><is><t xml:space="preserve">'
            . self::esc($text) . '</t></is></c>';
    }

    private static function len(string $s): int
    {
        return function_exists('mb_strlen') ? mb_strlen($s) : strlen($s);
    }

    /** 0 => A, 25 => Z, 26 => AA ... */
    private static function col(int $i): string
    {
        $name = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $name = chr(65 + ($i - 1) % 26) . $name;
        }
        return $name;
    }

    /** Escapes for XML and drops control characters Excel refuses to open. */
    private static function esc(string $s): string
    {
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s) ?? '';
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /** Excel tab names: max 31 characters, none of  : \ / ? * [ ] */
    private static function sheetName(string $name): string
    {
        $name = trim(str_replace([':', '\\', '/', '?', '*', '[', ']'], ' ', $name));
        $name = $name !== '' ? $name : 'Sheet1';
        return function_exists('mb_substr') ? mb_substr($name, 0, 31) : substr($name, 0, 31);
    }
}
