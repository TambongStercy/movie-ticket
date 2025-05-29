<?php
require_once __DIR__ . '/includes/auth_check_admin.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$csrf_token = $_GET['csrf_token'] ?? '';

if ($id <= 0) {
    $_SESSION['error'] = 'Invalid theatre ID.';
    redirect('manage_theatres.php');
}
// For simplicity, allow GET with confirm dialog, but check CSRF if present
if ($csrf_token && !validateCsrfToken($csrf_token)) {
    $_SESSION['error'] = 'CSRF token validation failed.';
    redirect('manage_theatres.php');
}
// Optionally, check if theatre is used in showtimes before deleting
$stmt = $pdo->prepare('SELECT COUNT(*) FROM showtimes WHERE theatre_id = ?');
$stmt->execute([$id]);
if ($stmt->fetchColumn() > 0) {
    $_SESSION['error'] = 'Cannot delete: Theatre is used in showtimes.';
    redirect('manage_theatres.php');
}
$stmt = $pdo->prepare('DELETE FROM theatres WHERE id = ?');
$stmt->execute([$id]);
$_SESSION['success'] = 'Theatre deleted successfully!';
redirect('manage_theatres.php');