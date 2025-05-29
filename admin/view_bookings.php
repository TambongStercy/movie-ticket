<?php
require_once __DIR__ . '/includes/auth_check_admin.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Fetch all bookings with user, movie, theatre, showtime info
$sql = 'SELECT b.*, u.username, m.title AS movie_title, t.name AS theatre_name, s.show_datetime
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN showtimes s ON b.showtime_id = s.id
        JOIN movies m ON s.movie_id = m.id
        JOIN theatres t ON s.theatre_id = t.id
        ORDER BY b.booking_timestamp DESC';
$stmt = $pdo->query($sql);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="flex">
    <div class="w-full min-h-screen p-8 bg-[#18181c] pt-20 md:ml-[240px]">
        <h1 class="text-2xl font-bold text-white mb-6">All Bookings</h1>
        <div class="card-magic p-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">User</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Movie</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Theatre</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Date & Time</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Seats</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Total (FCFA)</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Ticket</th>
                    </tr>
                </thead>
                <tbody class="bg-[#23232b] divide-y divide-gray-800 text-gray-200">
                    <?php foreach ($bookings as $booking): ?>
                        <tr class="hover:bg-[#28282f]">
                            <td class="px-4 py-2 text-white"><?php echo escape($booking['username']); ?></td>
                            <td class="px-4 py-2 text-gray-300"><?php echo escape($booking['movie_title']); ?></td>
                            <td class="px-4 py-2 text-gray-300"><?php echo escape($booking['theatre_name']); ?></td>
                            <td class="px-4 py-2 text-gray-300"><?php echo escape($booking['show_datetime']); ?></td>
                            <td class="px-4 py-2 text-gray-300"><?php echo escape($booking['booked_seats']); ?></td>
                            <td class="px-4 py-2 text-gray-300">
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
                            </td>
                            <td class="px-4 py-2">
                                <?php if ($booking['payment_status'] === 'confirmed'): ?>
                                    <a href="/movie_ticket_booking/user/download_ticket.php?booking_id=<?php echo $booking['id']; ?>"
                                        class="btn-magic inline-block px-3 py-1 text-sm rounded" target="_blank">Download
                                        Ticket</a>
                                <?php else: ?>
                                    <span class="text-gray-500 text-xs italic">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="8" class="text-gray-400 italic py-4">No bookings found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>