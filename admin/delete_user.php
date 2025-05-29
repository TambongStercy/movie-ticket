<?php
require_once __DIR__ . '/includes/auth_check_admin.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$csrf_token = $_GET['csrf_token'] ?? '';

if ($id <= 0) {
    $_SESSION['error'] = 'Invalid user ID.';
    redirect('manage_users.php');
}
if ($id == $_SESSION['user_id']) {
    $_SESSION['error'] = 'You cannot delete your own account.';
    redirect('manage_users.php');
}
if ($csrf_token && !validateCsrfToken($csrf_token)) {
    $_SESSION['error'] = 'CSRF token validation failed.';
    redirect('manage_users.php');
}
$stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
$stmt->execute([$id]);
$_SESSION['success'] = 'User deleted successfully!';
redirect('manage_users.php');