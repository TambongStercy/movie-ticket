<?php
require_once __DIR__ . '/includes/auth_check_admin.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$csrf_token = $_GET['csrf_token'] ?? '';

if ($id <= 0) {
    $_SESSION['error'] = 'Invalid showtime ID.';
    redirect('manage_showtimes.php');
}
// For simplicity, allow GET with confirm dialog, but check CSRF if present
if ($csrf_token && !validateCsrfToken($csrf_token)) {
    $_SESSION['error'] = 'CSRF token validation failed.';
    redirect('manage_showtimes.php');
}
// Optionally, check if showtime is used in bookings before deleting
$stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE showtime_id = ?');
$stmt->execute([$id]);
if ($stmt->fetchColumn() > 0) {
    $_SESSION['error'] = 'Cannot delete: Showtime has bookings.';
    redirect('manage_showtimes.php');
}
$stmt = $pdo->prepare('DELETE FROM showtimes WHERE id = ?');
$stmt->execute([$id]);
$_SESSION['success'] = 'Showtime deleted successfully!';
redirect('manage_showtimes.php');