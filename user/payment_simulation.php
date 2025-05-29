<?php
require_once __DIR__ . '/includes/auth_check_user.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

$booking_id = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;
$booking = null;
$error = '';

if ($booking_id > 0) {
    $stmt = $pdo->prepare('SELECT b.*, s.movie_id, s.show_datetime, s.price_per_seat, m.title FROM bookings b JOIN showtimes s ON b.showtime_id = s.id JOIN movies m ON s.movie_id = m.id WHERE b.id = ? AND b.user_id = ?');
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
}
if (!$booking) {
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="container mx-auto px-4 py-8"><div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md text-center"><div class="text-red-600 font-semibold">Booking not found.</div></div></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

if ($booking['payment_status'] === 'confirmed') {
    header('Location: booking_confirmation.php?booking_id=' . $booking_id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $status = $_POST['action'] === 'success' ? 'confirmed' : 'failed';
        $stmt = $pdo->prepare('UPDATE bookings SET payment_status = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$status, $booking_id, $_SESSION['user_id']]);
        if ($status === 'confirmed') {
            // Generate transaction_ref if not set
            if (empty($booking['transaction_ref'])) {
                $transaction_ref = strtoupper(bin2hex(random_bytes(8)));
                $stmt = $pdo->prepare('UPDATE bookings SET transaction_ref = ? WHERE id = ?');
                $stmt->execute([$transaction_ref, $booking_id]);
            }
            header('Location: booking_confirmation.php?booking_id=' . $booking_id);
            exit;
        } else {
            $error = 'Payment failed. Please try again or contact support.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="flex">
    <div class="w-full min-h-screen p-8 bg-[#18181c] pt-20 md:ml-[240px]">
        <div class="max-w-2xl mx-auto card-magic">
            <h1 class="text-xl font-bold mb-4 text-white">Payment Simulation</h1>
            <div class="mb-2 text-gray-300">Movie: <?php echo escape($booking['title']); ?></div>
            <div class="mb-2 text-gray-300">Showtime: <?php echo escape($booking['show_datetime']); ?></div>
            <div class="mb-2 text-gray-300">Seats: <?php echo escape($booking['booked_seats']); ?></div>
            <div class="mb-2 text-gray-300">Total Amount:
                <?php echo number_format($booking['total_amount'], 0, '.', ' '); ?> FCFA
            </div>
            <?php if ($error): ?>
                <div class="text-red-400 font-semibold mb-4"><?php echo escape($error); ?></div>
            <?php endif; ?>
            <form method="POST" class="flex gap-4 mt-6">
                <button type="submit" name="action" value="success"
                    class="flex-1 btn-magic bg-green-600 hover:bg-green-700">Simulate Payment
                    Success</button>
                <button type="submit" name="action" value="fail"
                    class="flex-1 btn-magic bg-red-600 hover:bg-red-700">Simulate Payment Failure</button>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>