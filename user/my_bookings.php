<?php
require_once __DIR__ . '/includes/auth_check_user.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$user_id = $_SESSION['user_id'];
$sql = 'SELECT b.*, m.title AS movie_title, t.name AS theatre_name, s.show_datetime
        FROM bookings b
        JOIN showtimes s ON b.showtime_id = s.id
        JOIN movies m ON s.movie_id = m.id
        JOIN theatres t ON s.theatre_id = t.id
        WHERE b.user_id = ?
        ORDER BY b.booking_timestamp DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="flex">
    <div class="w-full min-h-screen p-8 bg-[#18181c] pt-20 md:ml-[240px]">
        <h1 class="text-2xl font-bold text-white mb-8">My Bookings</h1>
        <div class="card-magic">
            <table class="min-w-full divide-y divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Movie</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Theatre</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Date & Time</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Seats</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Total (FCFA)</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-[#23232b] divide-y divide-gray-800">
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td class="px-4 py-2 text-white"><?php echo escape($booking['movie_title']); ?></td>
                            <td class="px-4 py-2 text-white"><?php echo escape($booking['theatre_name']); ?></td>
                            <td class="px-4 py-2 text-white"><?php echo escape($booking['show_datetime']); ?></td>
                            <td class="px-4 py-2 text-white"><?php echo escape($booking['booked_seats']); ?></td>
                            <td class="px-4 py-2 text-white">
                                <?php echo number_format($booking['total_amount'], 0, '.', ' '); ?>
                            </td>
                            <td class="px-4 py-2">
                                <span class="inline-block px-2 py-1 rounded text-xs font-semibold
                                    <?php
                                    if ($booking['payment_status'] === 'confirmed')
                                        echo 'bg-green-900 text-green-300';
                                    elseif ($booking['payment_status'] === 'pending')
                                        echo 'bg-yellow-900 text-yellow-300';
                                    else
                                        echo 'bg-red-900 text-red-300';
                                    ?>"><?php echo escape(ucfirst($booking['payment_status'])); ?></span>
                                <?php if ($booking['payment_status'] === 'confirmed'): ?>
                                    <a href="download_ticket.php?booking_id=<?php echo $booking['id']; ?>"
                                        class="ml-2 btn-magic px-3 py-1 text-xs">Download Ticket</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="6" class="text-gray-400 italic py-4">You have no bookings yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>