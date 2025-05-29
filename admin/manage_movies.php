<?php
require_once __DIR__ . '/includes/auth_check_admin.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Fetch all movies
$movies = [];
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']); // Clear messages

try {
    $stmt = $pdo->query("SELECT id, title, director, release_date, duration_minutes, poster_image_path FROM movies ORDER BY release_date DESC, title ASC");
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $page_error = "Error fetching movies: " . $e->getMessage();
}

?>
<div class="flex">
    <div class="w-full min-h-screen p-8 bg-[#18181c] pt-20 md:ml-[240px]">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-white">Manage Movies</h1>
            <a href="add_movie.php" class="btn-magic flex items-center gap-2"><i class="fas fa-plus"></i> Add New
                Movie</a>
        </div>
        <?php if ($success_message): ?>
            <div class="bg-green-900 text-green-300 px-4 py-2 rounded mb-4" role="alert">
                <p class="font-bold">Success</p>
                <p><?php echo escape($success_message); ?></p>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="bg-red-900 text-red-300 px-4 py-2 rounded mb-4" role="alert">
                <p class="font-bold">Error</p>
                <p><?php echo escape($error_message); ?></p>
            </div>
        <?php endif; ?>
        <?php if (isset($page_error)): ?>
            <div class="bg-red-900 text-red-300 px-4 py-2 rounded mb-4" role="alert">
                <p class="font-bold">Page Error</p>
                <p><?php echo escape($page_error); ?></p>
            </div>
        <?php endif; ?>
        <div class="card-magic overflow-x-auto">
            <?php if (empty($movies) && !isset($page_error)): ?>
                <div class="p-6 text-center text-gray-400">
                    <p class="text-xl mb-2">No movies found.</p>
                    <p>You can start by adding a new movie using the button above.</p>
                </div>
            <?php elseif (!empty($movies)): ?>
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr class="bg-[#23232b] text-gray-400 uppercase text-sm">
                            <th class="px-4 py-2 text-left text-xs font-medium">Cover</th>
                            <th class="py-3 px-6 text-left">Title</th>
                            <th class="py-3 px-6 text-left">Director</th>
                            <th class="py-3 px-6 text-left">Release Date</th>
                            <th class="py-3 px-6 text-center">Duration (min)</th>
                            <th class="py-3 px-6 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-200 text-sm font-light bg-[#23232b] divide-y divide-gray-800">
                        <?php foreach ($movies as $movie): ?>
                            <tr class="hover:bg-[#28282f]">
                                <td class="px-4 py-2">
                                    <?php if (!empty($movie['cover_image_path'])): ?>
                                        <img src="/movie_ticket_booking/uploads/posters/<?php echo escape(basename($movie['cover_image_path'])); ?>"
                                            alt="Cover" style="max-width: 60px; max-height: 90px;">
                                    <?php elseif (!empty($movie['poster_image_path'])): ?>
                                        <img src="/movie_ticket_booking/uploads/posters/<?php echo escape(basename($movie['poster_image_path'])); ?>"
                                            alt="Poster" style="max-width: 60px; max-height: 90px;">
                                    <?php else: ?>
                                        <span class="text-gray-500">No Image</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-6 text-left font-semibold text-white"><?php echo escape($movie['title']); ?>
                                </td>
                                <td class="py-3 px-6 text-left text-gray-300"><?php echo escape($movie['director'] ?? 'N/A'); ?>
                                </td>
                                <td class="py-3 px-6 text-left text-gray-300">
                                    <?php echo escape($movie['release_date'] ? date('M j, Y', strtotime($movie['release_date'])) : 'N/A'); ?>
                                </td>
                                <td class="py-3 px-6 text-center text-gray-300">
                                    <?php echo escape($movie['duration_minutes'] ?? 'N/A'); ?>
                                </td>
                                <td class="py-3 px-6 text-center">
                                    <div class="flex item-center justify-center space-x-2">
                                        <a href="edit_movie.php?id=<?php echo $movie['id']; ?>"
                                            class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-50"
                                            title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete_movie.php?id=<?php echo $movie['id']; ?>"
                                            onclick="return confirm('Are you sure you want to delete this movie: <?php echo escape(addslashes($movie['title'])); ?>?');"
                                            class="w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-opacity-50"
                                            title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Include Font Awesome for icons -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>