<?php
require_once __DIR__ . '/includes/auth_check_admin.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Fetch all showtimes with movie and theatre info
$stmt = $pdo->query('SELECT s.*, m.title AS movie_title, t.name AS theatre_name FROM showtimes s JOIN movies m ON s.movie_id = m.id JOIN theatres t ON s.theatre_id = t.id ORDER BY s.show_datetime DESC');
$showtimes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="flex">
    <div class="w-full min-h-screen p-8 bg-[#18181c] pt-20 md:ml-[240px]">
        <h1 class="text-2xl font-bold text-white mb-6">Manage Showtimes</h1>
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="bg-green-900 text-green-300 px-4 py-2 rounded mb-4">
                <?php echo escape($_SESSION['success']);
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="bg-red-900 text-red-300 px-4 py-2 rounded mb-4">
                <?php echo escape($_SESSION['error']);
                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        <div class="mb-4">
            <a href="add_showtime.php" class="btn-magic">Add New Showtime</a>
        </div>
        <div class="card-magic p-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Movie</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Theatre</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Date & Time</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Price (FCFA)</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-[#23232b] divide-y divide-gray-800 text-gray-200">
                    <?php foreach ($showtimes as $show): ?>
                        <tr class="hover:bg-[#28282f]">
                            <td class="px-4 py-2 font-semibold text-white"><?php echo escape($show['movie_title']); ?></td>
                            <td class="px-4 py-2 text-gray-300"><?php echo escape($show['theatre_name']); ?></td>
                            <td class="px-4 py-2 text-gray-300"><?php echo escape($show['show_datetime']); ?></td>
                            <td class="px-4 py-2 text-gray-300">
                                <?php echo number_format($show['price_per_seat'], 0, '.', ' '); ?>
                            </td>
                            <td class="px-4 py-2">
                                <a href="edit_showtime.php?id=<?php echo $show['id']; ?>"
                                    class="text-blue-400 hover:underline mr-4">Edit</a>
                                <a href="delete_showtime.php?id=<?php echo $show['id']; ?>"
                                    class="text-red-400 hover:underline"
                                    onclick="return confirm('Are you sure you want to delete this showtime?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($showtimes)): ?>
                        <tr>
                            <td colspan="5" class="text-gray-400 italic py-4">No showtimes found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>