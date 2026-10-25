<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

/**
 * Sign-up / login field rules. The browser (assets/js/auth.js) applies the same rules while the person
 * types, but this class is the real check: every method returns an error message, or null when the value is fine.
 *
 * Limits match the users table: last_name 30, first_name 20, middle_name 20.
 */
class Validation
{
    public const LAST_NAME_MAX   = 30;
    public const FIRST_NAME_MAX  = 20;
    public const MIDDLE_NAME_MAX = 20;
    public const EMAIL_MAX       = 50;
    public const ID_MAX          = 17;   // 03-01-2526-049080
    public const PHONE_LEN       = 11;   // 09171234567
    public const PASSWORD_MIN    = 8;
    public const PASSWORD_MAX    = 64;
    public const IDENTIFIER_MAX  = 50;   // login box: an email or an ID number

    /** Character count that is safe for accents (no mbstring needed). */
    private static function len(string $s): int
    {
        return (int) preg_match_all('/./us', $s);
    }

    /** Trim and collapse repeated spaces. */
    public static function cleanName(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    /** Letters only (plus single spaces, hyphen, apostrophe and period, e.g. "Dela Cruz", "Mary-Ann", "C."). */
    public static function name(string $value, string $label, int $max, bool $required = true): ?string
    {
        if ($value === '') {
            return $required ? "$label is required." : null;
        }
        if (self::len($value) > $max) {
            return "$label must be $max characters or fewer.";
        }
        if (!preg_match('/^\p{L}[\p{L} .\'’\-]*$/u', $value)) {
            return "$label can only contain letters.";
        }
        $letters = (int) preg_match_all('/\p{L}/u', $value);
        if ($required && $letters < 2) {
            return "$label must have at least 2 letters.";
        }
        return null;
    }

    /** 14 digits typed without dashes become 03-01-2526-049080. Anything else is returned unchanged. */
    public static function normalizeId(string $value): string
    {
        if (preg_match('/^(\d{2})(\d{2})(\d{4})(\d{6})$/', $value, $m)) {
            return $m[1] . '-' . $m[2] . '-' . $m[3] . '-' . $m[4];
        }
        return $value;
    }

    /** PHINMA ID format: 2-2-4-6 digits, e.g. 03-01-2526-049080. */
    public static function idNumber(string $value): ?string
    {
        if ($value === '') {
            return 'Student / Employee ID is required.';
        }
        if (!preg_match('/^\d{2}-\d{2}-\d{4}-\d{6}$/', $value)) {
            return 'Student / Employee ID must look like 03-01-2526-049080.';
        }
        return null;
    }

    /** PHINMA email format: juca.peralta.up@phinmaed.com (lower case). */
    public static function email(string $value): ?string
    {
        if ($value === '') {
            return 'Email is required.';
        }
        if (strlen($value) > self::EMAIL_MAX) {
            return 'Email must be ' . self::EMAIL_MAX . ' characters or fewer.';
        }
        if (!preg_match('/^[a-z]{2,4}\.[a-z]+[0-9]{0,3}\.up@phinmaed\.com$/', $value)) {
            return 'Use your PHINMA email, like juca.peralta.up@phinmaed.com.';
        }
        return null;
    }

    /** "+639171234567", "639171234567" and "9171234567" all become 09171234567. */
    public static function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';
        if (strlen($digits) === 12 && strpos($digits, '63') === 0) {
            return '0' . substr($digits, 2);
        }
        if (strlen($digits) === 10 && $digits[0] === '9') {
            return '0' . $digits;
        }
        return $digits;
    }

    /** Philippine mobile number: 11 digits starting with 09. */
    public static function phone(string $value): ?string
    {
        if (!preg_match('/^09\d{9}$/', $value)) {
            return 'Phone number must be a Philippine mobile number like 09171234567.';
        }
        return null;
    }

    /** 8 to 64 characters, no spaces. */
    public static function password(string $value): ?string
    {
        if (preg_match('/\s/', $value)) {
            return 'Password cannot contain spaces.';
        }
        if (strlen($value) < self::PASSWORD_MIN) {
            return 'Password must be at least ' . self::PASSWORD_MIN . ' characters.';
        }
        if (strlen($value) > self::PASSWORD_MAX) {
            return 'Password must be ' . self::PASSWORD_MAX . ' characters or fewer.';
        }
        return null;
    }
}
