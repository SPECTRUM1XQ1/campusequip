<?php
// Small helpers for showing a reservation's pick-up window (see Reservation::pickupWindow()).
// Needs src/Reservation.php to be loaded.

if (!function_exists('pickupWindowFor')) {
    /**
     * $row needs start_at and end_at. $format is a PHP date() format for the text.
     * 'open' / 'close' have no timezone suffix, so the browser reads them as local time,
     * the same way it reads the date the borrower picked.
     */
    function pickupWindowFor(array $row, string $format = 'M d, Y g:i A'): array
    {
        $w = Reservation::pickupWindow($row['start_at'], $row['end_at']);
        return [
            'start' => $w['start'],
            'end'   => $w['end'],
            'text'  => date($format, $w['start']) . ' &ndash; ' . date($format, $w['end']),
            'open'  => date('Y-m-d\TH:i:s', $w['start']),
            'close' => date('Y-m-d\TH:i:s', $w['end']),
        ];
    }

    /**
     * The live countdown pill (filled in by assets/js/countdown.js).
     * $onExpire: 'remove' removes the whole card when the window ends (staff pages);
     * $expiredText replaces the default "Pick-up window ended" label.
     */
    function pickupCountdownTag(array $window, string $onExpire = '', string $expiredText = ''): string
    {
        return '<span class="pickup-countdown"'
            . ' data-open="' . $window['open'] . '" data-close="' . $window['close'] . '"'
            . ($onExpire !== '' ? ' data-on-expire="' . htmlspecialchars($onExpire, ENT_QUOTES) . '"' : '')
            . ($expiredText !== '' ? ' data-expired-text="' . htmlspecialchars($expiredText, ENT_QUOTES) . '"' : '')
            . '>&hellip;</span>';
    }
}

if (!function_exists('dueCountdownTag')) {
    /**
     * Countdown pill for an item that is out on loan (filled in by assets/js/countdown.js):
     * "Due in 2d 04:12:30" while time is left (amber under 6 hours), then "Overdue by 1 day" in red.
     * $dueAt is the checkout's due_at. Like the pick-up pill, the date has no timezone suffix,
     * so the browser reads it as local time.
     */
    function dueCountdownTag(string $dueAt): string
    {
        return '<span class="due-countdown" data-due="' . date('Y-m-d\\TH:i:s', strtotime($dueAt)) . '">&hellip;</span>';
    }
}
