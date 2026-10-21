<?php
if (!defined('APP_INIT')) { http_response_code(403); exit; }

/**
 * Small output helpers shared by every page. Loaded by includes/auth_check.php.
 */

if (!function_exists('e')) {
    /** Escape text for HTML (element content and quoted attributes). */
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('js_arg')) {
    /**
     * A value as a JavaScript string literal that is safe inside a double-quoted HTML attribute:
     *   onclick="openThing(<?= js_arg($name) ?>)"
     * json_encode() does the JavaScript escaping (quotes, backslashes, newlines) and htmlspecialchars()
     * does the HTML escaping. (addslashes() only handles quotes and backslashes, so a newline in a
     * name would break the script.) Note: the output already contains its own quotes.
     */
    function js_arg($value): string
    {
        $json = json_encode((string) $value, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        return htmlspecialchars($json === false ? '""' : $json, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('edit_button')) {
    /**
     * The one Edit button used everywhere (UI_GUIDE.md: "Edit always looks the same").
     * Fixed look: btn btn-outline btn-sm btn-rect btn-reveal + pencil icon + "Edit" label,
     * with title and aria-label so the icon-only state is still understandable.
     *
     *   <?= edit_button(['catalog-id' => $id, 'catalog-action' => 'edit']) ?>
     *
     * @param array<string,scalar|null> $data  data-* attributes (key without the "data-" prefix); values are escaped
     * @param string $hookClass                optional extra class that page JavaScript binds to
     */
    function edit_button(array $data = [], string $hookClass = ''): string
    {
        $attrs = '';
        foreach ($data as $key => $value) {
            $attrs .= ' data-' . e($key) . '="' . e($value ?? '') . '"';
        }
        $class = 'btn btn-outline btn-sm btn-rect btn-reveal' . ($hookClass !== '' ? ' ' . e($hookClass) : '');
        return '<button type="button" class="' . $class . '" title="Edit" aria-label="Edit"' . $attrs . '>'
            . '<i class="ph ph-pencil-simple" aria-hidden="true"></i><span class="btn-label">Edit</span></button>';
    }
}
