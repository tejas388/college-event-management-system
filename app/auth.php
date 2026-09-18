<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// CSRF Protection Functions
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        throw new Exception("Invalid CSRF token. Please refresh the page and try again.");
    }
    return true;
}

function find_user_by_email($email) {
    $stmt = db()->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function find_user_by_user_id($user_id) {
    $stmt = db()->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function signup($name, $user_id, $role, $department, $year, $email, $password) {
    if (empty($name) || empty($user_id) || empty($email) || empty($password)) {
        throw new Exception("All required fields must be filled.");
    }

    if (!in_array($role, ['student', 'teacher', 'organizer'])) {
        throw new Exception("Invalid role selected.");
    }

    if (strlen($password) < 8) {
        throw new Exception("Password must be at least 8 characters.");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format.");
    }

    if (find_user_by_email($email)) {
        throw new Exception("Email is already registered.");
    }

    if (find_user_by_user_id($user_id)) {
        throw new Exception("User ID / USN is already registered.");
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    if ($role === 'teacher' || $role === 'organizer') {
        $year = null;
    } elseif (empty($year)) {
        throw new Exception("Year is required for students.");
    }

    $stmt = db()->prepare("
        INSERT INTO users
        (name, user_id, role, department, year, email, password_hash)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $name,
        $user_id,
        $role,
        $department,
        $year,
        $email,
        $hash
    ]);

    return db()->lastInsertId();
}

function login($user_id, $password) {
    $stmt = db()->prepare("SELECT * FROM users WHERE user_id = ? OR email = ?");
    $stmt->execute([$user_id, $user_id]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Prevent session fixation
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'         => $user['id'],
            'name'       => $user['name'],
            'user_id'    => $user['user_id'],
            'department' => $user['department'],
            'year'       => $user['year'],
            'email'      => $user['email'],
            'role'       => $user['role']
        ];
        return true;
    }
    return false;
}

function logout() {
    session_unset();
    session_destroy();
    session_start();
    session_regenerate_id(true);
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login() {
    if (!current_user()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function authorize($roles) {
    $u = current_user();
    if (!$u || !in_array($u['role'], $roles)) {
        http_response_code(403);
        die("Forbidden: You do not have permission to access this resource.");
    }
}