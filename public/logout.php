<?php
require_once __DIR__ . '/../app/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf_token($_POST['csrf_token'] ?? '');
        logout();
    } catch (Exception $e) {
        // Just ignore and force logout anyway if token fails on logout
        logout();
    }
}
header('Location: ' . BASE_URL . 'login.php');
exit;
