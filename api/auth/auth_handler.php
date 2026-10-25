<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/User.php';
require_once __DIR__ . '/../../src/Validation.php';

app_session_start();

header('Content-Type: application/json');

// Wrapping the whole dispatch in try/catch means a DB error (wrong column
// name, constraint violation, connection drop, etc.) always comes back as
// clean JSON — never a raw PHP error page that breaks res.json() on the
// frontend and shows as "Could not reach the server."
try {
    // Login/signup/logout are POSTs too, so they need the CSRF token like every other endpoint.
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        exit;
    }
    if (!csrf_verify()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Your session has expired. Please refresh the page and try again.']);
        exit;
    }

    $action = $_POST['action'] ?? '';
    $user   = new User();
    switch ($action) {

        case 'signup':
            // Sent to User::register() with the SAME keys it actually reads —
            // last_name / first_name / middle_name, matching the users table's
            // real columns. Do not combine these into full_name here; that
            // was the bug that just broke this.
            $lastName   = Validation::cleanName((string) ($_POST['last_name'] ?? ''));
            $firstName  = Validation::cleanName((string) ($_POST['first_name'] ?? ''));
            $middleName = Validation::cleanName((string) ($_POST['middle_name'] ?? ''));
            $email      = strtolower(trim((string) ($_POST['email'] ?? '')));
            $idNumber   = Validation::normalizeId(trim((string) ($_POST['id_number'] ?? '')));
            $phone      = Validation::normalizePhone(trim((string) ($_POST['phone'] ?? '')));
            $password   = (string) ($_POST['password'] ?? '');
            $confirm    = (string) ($_POST['confirm_password'] ?? '');

            if ($firstName === '' || $lastName === '' || $email === '' || $idNumber === '' || $password === '') {
                echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
                exit;
            }

            // Same rules the sign-up form applies while typing (see src/Validation.php).
            $error = Validation::name($lastName, 'Last name', Validation::LAST_NAME_MAX)
                ?? Validation::name($firstName, 'First name', Validation::FIRST_NAME_MAX)
                ?? Validation::name($middleName, 'Middle name', Validation::MIDDLE_NAME_MAX, false)
                ?? Validation::idNumber($idNumber)
                ?? ($phone !== '' ? Validation::phone($phone) : null)
                ?? Validation::email($email)
                ?? Validation::password($password);
            if ($error === null && $password !== $confirm) {
                $error = 'Passwords do not match.';
            }
            if ($error !== null) {
                echo json_encode(['success' => false, 'message' => $error]);
                exit;
            }

            echo json_encode($user->register([
                'last_name'   => $lastName,
                'first_name'  => $firstName,
                'middle_name' => $middleName,
                'email'       => $email,
                'password'    => $password,
                'id_number'   => $idNumber,
                'phone'       => $phone,
            ]));
            break;

        case 'login':
            // "identifier" is whatever was typed into the single sign-in field —
            // could be an email or a Student/Employee ID, User::login() checks both.
            $identifier = trim((string) ($_POST['identifier'] ?? ''));
            $password   = (string) ($_POST['password'] ?? '');

            if ($identifier === '' || $password === '') {
                echo json_encode(['success' => false, 'message' => 'Please enter your email/ID and password.']);
                exit;
            }
            if (strlen($identifier) > Validation::IDENTIFIER_MAX || preg_match('/\s/', $identifier)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email/ID or password.']);
                exit;
            }
            if (preg_match('/\s/', $password) || strlen($password) > Validation::PASSWORD_MAX) {
                echo json_encode(['success' => false, 'message' => 'Passwords cannot contain spaces.']);
                exit;
            }
            // 14 digits typed without dashes are looked up as 03-01-2526-049080; emails are not case sensitive.
            $identifier = Validation::normalizeId($identifier);
            if (strpos($identifier, '@') !== false) {
                $identifier = strtolower($identifier);
            }

            echo json_encode($user->login($identifier, $password));
            break;

        case 'logout':
            $_SESSION = [];
            session_destroy();
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }
} catch (Throwable $e) {
    error_log('Auth handler error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again later.']);
}
