<?php
require_once __DIR__ . '/includes/auth_check_user.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
$booking_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'CSRF token mismatch. Please try again.';
    } else {
        $showtime_id = isset($_POST['showtime_id']) ? (int) $_POST['showtime_id'] : 0;
        $selected_seats = isset($_POST['selected_seats']) ? $_POST['selected_seats'] : [];
        if ($showtime_id <= 0 || empty($selected_seats)) {
            $errors[] = 'Please select at least one seat.';
        } else {
            // Fetch showtime info
            $stmt = $pdo->prepare('SELECT * FROM showtimes WHERE id = ?');
            $stmt->execute([$showtime_id]);
            $showtime = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$showtime) {
                $errors[] = 'Invalid showtime.';
            } else {
                // Check if seats are already booked
                $stmt = $pdo->prepare('SELECT booked_seats FROM bookings WHERE showtime_id = ? AND payment_status = "confirmed"');
                $stmt->execute([$showtime_id]);
                $already_booked = [];
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $already_booked = array_merge($already_booked, explode(',', $row['booked_seats']));
                }
                foreach ($selected_seats as $seat) {
                    // Accept only seat names like A1, B2, etc.
                    if (!preg_match('/^[A-Z][0-9]+$/', $seat)) {
                        $errors[] = 'Invalid seat: ' . escape($seat);
                    }
                    if (in_array($seat, $already_booked)) {
                        $errors[] = 'Seat ' . escape($seat) . ' is already booked.';
                    }
                }
                if (empty($errors)) {
                    $user_id = $_SESSION['user_id'];
                    $num_seats = count($selected_seats);
                    $total_amount = $num_seats * $showtime['price_per_seat'];
                    $booked_seats_str = implode(',', $selected_seats);
                    $stmt = $pdo->prepare('INSERT INTO bookings (user_id, showtime_id, num_seats, booked_seats, total_amount, payment_status) VALUES (?, ?, ?, ?, ?, ?)');
                    if ($stmt->execute([$user_id, $showtime_id, $num_seats, $booked_seats_str, $total_amount, 'pending'])) {
                        $booking_id = $pdo->lastInsertId();
                        header('Location: payment_simulation.php?booking_id=' . $booking_id);
                        exit;
                    } else {
                        $errors[] = 'Failed to create booking. Please try again.';
                    }
                }
            }
        }
    }
}
if (!empty($errors)) {
    // Show errors and a back link
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="container mx-auto px-4 py-8">';
    echo '<div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md text-center">';
    echo '<div class="text-red-600 font-semibold mb-4">' . implode('<br>', array_map('escape', $errors)) . '</div>';
    echo '<a href="javascript:history.back()" class="text-blue-600 hover:underline">Go Back</a>';
    echo '</div></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}