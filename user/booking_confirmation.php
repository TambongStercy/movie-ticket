<?php
require_once __DIR__ . '/includes/auth_check_user.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$booking_id = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;
$booking = null;
$email_sent = false;
$email_error = '';
$should_download = false;

if ($booking_id > 0) {
    // Fetch booking, showtime, movie, and user email
    $stmt = $pdo->prepare('SELECT b.*, s.show_datetime, m.title, m.poster_image_path, u.email, u.username FROM bookings b JOIN showtimes s ON b.showtime_id = s.id JOIN movies m ON s.movie_id = m.id JOIN users u ON b.user_id = u.id WHERE b.id = ? AND b.user_id = ? AND b.payment_status = "confirmed"');
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($booking) {
        // 1. Generate transaction_ref if not set
        if (empty($booking['transaction_ref'])) {
            $transaction_ref = strtoupper(bin2hex(random_bytes(8)));
            $stmt = $pdo->prepare('UPDATE bookings SET transaction_ref = ? WHERE id = ?');
            $stmt->execute([$transaction_ref, $booking_id]);
            $booking['transaction_ref'] = $transaction_ref;
        }
        // 2. Generate PDF ticket using the reusable function
        $pdf_content = generateMovieTicketPDF($booking);
        // 3. Send email with PDF
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['SMTP_USER'];
            $mail->Password = $_ENV['SMTP_PASS'];
            $mail->SMTPSecure = $_ENV['SMTP_SECURE'] === 'true' ? PHPMailer::ENCRYPTION_SMTPS : false;
            $mail->Port = (int) $_ENV['SMTP_PORT'];
            if (isset($_ENV['SMTP_FROM'])) {
                $mail->setFrom($_ENV['SMTP_FROM'], $_ENV['SMTP_FROM_NAME'] ?? 'Movie Magic');
            } else {
                $mail->setFrom($_ENV['SMTP_USER'], 'Movie Magic');
            }
            $mail->addAddress($booking['email'], $booking['username']);
            $mail->Subject = 'Your Movie Ticket - ' . $booking['title'];
            $mail->Body = "Dear {$booking['username']},\n\nAttached is your movie ticket.\nTransaction Ref: {$booking['transaction_ref']}\nEnjoy your movie!\n\nMovie Magic";
            $mail->addStringAttachment($pdf_content, 'ticket_' . $booking['transaction_ref'] . '.pdf');
            $mail->send();
            $email_sent = true;
            $should_download = true;
        } catch (Exception $e) {
            $email_error = 'Ticket email could not be sent. Error: ' . $mail->ErrorInfo;
        }
    }
}

if ($should_download) {
    // Remove forced download, now handled by download_ticket.php
    // header('Content-Type: application/pdf');
    // header('Content-Disposition: attachment; filename="ticket_' . $booking['transaction_ref'] . '.pdf"');
    // echo $pdf_content;
    // exit;
}
?>
<div class="flex">
    <div class="w-full min-h-screen p-8 bg-[#18181c] pt-20 md:ml-[240px]">
        <div class="max-w-2xl mx-auto card-magic text-center">
            <?php if ($booking): ?>
                <h1 class="text-2xl font-bold mb-4 text-green-400">Booking Confirmed!</h1>
                <div class="mb-2 text-gray-300">Movie: <?php echo escape($booking['title']); ?></div>
                <div class="mb-2 text-gray-300">Showtime: <?php echo escape($booking['show_datetime']); ?></div>
                <div class="mb-2 text-gray-300">Seats: <?php echo escape($booking['booked_seats']); ?></div>
                <div class="mb-2 text-gray-300">Total Paid:
                    <?php echo number_format($booking['total_amount'], 0, '.', ' '); ?> FCFA
                </div>
                <div class="mb-2 text-gray-300">Transaction Ref: <span
                        class="font-mono text-yellow-400"><?php echo escape($booking['transaction_ref']); ?></span></div>
                <?php if ($email_sent): ?>
                    <div class="bg-green-900 text-green-300 px-4 py-2 rounded mb-2 mt-4">Your ticket has been sent to your email
                        (<?php echo escape($booking['email']); ?>).</div>
                    <a href="download_ticket.php?booking_id=<?php echo $booking_id; ?>"
                        class="inline-block mt-2 btn-magic">Download Ticket PDF</a>
                <?php elseif ($email_error): ?>
                    <div class="bg-red-900 text-red-300 px-4 py-2 rounded mb-2 mt-4"><?php echo escape($email_error); ?></div>
                <?php endif; ?>
                <a href="my_bookings.php" class="inline-block mt-6 btn-magic">View My Bookings</a>
            <?php else: ?>
                <div class="text-red-400 font-semibold">Booking not found or not confirmed.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>