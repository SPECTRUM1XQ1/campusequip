<?php
define('APP_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Wrapping the whole dispatch in try/catch means a DB error (wrong column
// name, constraint violation, connection drop, etc.) always comes back as
// clean JSON — never a raw PHP error page that breaks res.json() on the
// frontend and shows as "Could not reach the server."
try {
    $action = $_POST['action'] ?? '';
    $user   = new User();

    switch ($action) {

        case 'signup':
            // Sent to User::register() with the SAME keys it actually reads —
            // last_name / first_name / middle_name, matching the users table's
            // real columns. Do not combine these into full_name here; that
            // was the bug that just broke this.
            $lastName   = trim($_POST['last_name'] ?? '');
            $firstName  = trim($_POST['first_name'] ?? '');
            $middleName = trim($_POST['middle_name'] ?? '');
            $email      = trim($_POST['email'] ?? '');
            $idNumber   = trim($_POST['id_number'] ?? '');
            $phone      = trim($_POST['phone'] ?? '');
            $password   = $_POST['password'] ?? '';
            $confirm    = $_POST['confirm_password'] ?? '';

            if ($firstName === '' || $lastName === '' || $email === '' || $idNumber === '' || $password === '') {
                echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
                exit;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
                exit;
            }
            if (strlen($password) < 8) {
                echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
                exit;
            }
            if ($password !== $confirm) {
                echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
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
            $identifier = trim($_POST['identifier'] ?? '');
            $password   = $_POST['password'] ?? '';

            if ($identifier === '' || $password === '') {
                echo json_encode(['success' => false, 'message' => 'Please enter your email/ID and password.']);
                exit;
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
