<?php 
require_once __DIR__ . '/../User.php';

class AuthenController {
    private $user;
    public function __construct() {
        $this->user = new User();
    }
    public function handleLogin(array $data): array {
        $emal = filter_var(trim($data['email']), FILTER_SANITIZE_STRING);
        $password = trim($data['password']?? '');
        //Validate Empty Fields
        if (empty($emal) || empty($password)) {
            return ['status' => 'error', 'message' => 'Please fill in all required fields'];
        }
        //Validate Email Conctrcution
        if(!filter_var($emal, FILTER_VALIDATE_EMAIL)) {
            return ['status'=> 'error', 'message'=> 'Please Enter a Valid Constitutional email address'];
        }
        //perform authecication 
        if ($this->user->login($emal, $password)) {
            $redirectURL = ($_SESSION['role'] === 'admin')
            ? '../admin/dashboard.php'
            : '../staff/';
            //TODO:staff redirect
            return ['status'=> 'success','message'=> 'Log In Successfully!', 'redirect' => $redirectURL];
        }
        return ['status'=> 'error','message'=> 'Invalid email or password'];
    }

    //Siign Up Request
    public function handleSignUp(array $data): array {
        $last_name = htmlspecialchars(trim($data['last_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $first_name = htmlspecialchars(trim($data['first_name'] ??''), ENT_QUOTES, 'UTF-8');
        $middle_name = htmlspecialchars(trim($data[''] ??''), ENT_QUOTES, 'UTF-8');   
        $email = filter_var(trim($data['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $pasword = trim($data['password']??'');
        $id_number = htmlspecialchars(trim($data['id_number']??''), ENT_QUOTES, 'UFT-8');
        $phone = htmlspecialchars(trim($data['phone'] ?? ''), ENT_QUOTES, 'UTF-8');

        if (empty($full_name) || empty($email) || empty($pasword)) {
            return ['status'=> 'error','message'=> 'Please fill in all required fields'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status'=> 'error','message'=> 'Invalid Email Format'];
        }

        if (strlen($pasword) < 8) {
            return ['status'=> 'error','message'=> 'Password Must 8 Characters'];
        }
        $data = [$last_name, $first_name, $middle_name, $email, $id_number, $phone];
        try {
            $register = $this->user->register($data);
            if ($register) {
                return ['status'=> 'success','message'=> 'Account Created', 'redirect' => 'login.php?success=register'];
            }
        } catch (Exception $e) {
            return ['status'=> 'error','message'=> $e->getMessage()];
        }
        return ['status'=> 'success','message'=> 'Failed to Register Account'];
    }
}
?>