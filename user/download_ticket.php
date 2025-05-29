<?php
require_once __DIR__ . '/includes/auth_check_user.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

$booking_id = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;
$booking = null;

if ($booking_id > 0) {
    $stmt = $pdo->prepare('SELECT b.*, s.show_datetime, m.title, m.poster_image_path, u.email, u.username FROM bookings b JOIN showtimes s ON b.showtime_id = s.id JOIN movies m ON s.movie_id = m.id JOIN users u ON b.user_id = u.id WHERE b.id = ? AND b.user_id = ? AND b.payment_status = "confirmed"');
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$booking) {
    http_response_code(403);
    echo 'Ticket not available.';
    exit;
}

$pdf_content = generateMovieTicketPDF($booking);
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="ticket_' . $booking['transaction_ref'] . '.pdf"');
echo $pdf_content;
exit;