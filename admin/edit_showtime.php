<?php
require_once __DIR__ . '/includes/auth_check_admin.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$errors = [];

// Fetch showtime
$stmt = $pdo->prepare('SELECT * FROM showtimes WHERE id = ?');
$stmt->execute([$id]);
$showtime = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$showtime) {
    echo '<div class="container mx-auto px-4 py-8"><div class="bg-red-100 text-red-700 px-4 py-2 rounded">Showtime not found.</div></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Fetch movies and theatres for dropdowns
$movies = $pdo->query('SELECT id, title FROM movies ORDER BY title ASC')->fetchAll(PDO::FETCH_ASSOC);
$theatres = $pdo->query('SELECT id, name FROM theatres ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);

$movie_id = $showtime['movie_id'];
$theatre_id = $showtime['theatre_id'];
$show_datetime = $showtime['show_datetime'];
$price_per_seat = $showtime['price_per_seat'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'CSRF token validation failed.';
    } else {
        $movie_id = (int) ($_POST['movie_id'] ?? 0);
        $theatre_id = (int) ($_POST['theatre_id'] ?? 0);
        $show_datetime = trim($_POST['show_datetime'] ?? '');
        $price_per_seat = trim($_POST['price_per_seat'] ?? '');
        if ($movie_id <= 0) {
            $errors[] = 'Please select a movie.';
        }
        if ($theatre_id <= 0) {
            $errors[] = 'Please select a theatre.';
        }
        if ($show_datetime === '') {
            $errors[] = 'Show date and time is required.';
        }
        if ($price_per_seat === '' || !is_numeric($price_per_seat) || $price_per_seat < 0) {
            $errors[] = 'Price per seat must be a non-negative number.';
        }
        if (empty($errors)) {
            $stmt = $pdo->prepare('UPDATE showtimes SET movie_id = ?, theatre_id = ?, show_datetime = ?, price_per_seat = ? WHERE id = ?');
            $stmt->execute([$movie_id, $theatre_id, $show_datetime, $price_per_seat, $id]);
            $_SESSION['success'] = 'Showtime updated successfully!';
            header('Location: manage_showtimes.php');
            exit;
        }
    }
}
$csrf_token = generateCsrfToken();
?>
<div class="flex">
    <div class="ml-[240px] w-full min-h-screen p-8 bg-[#18181c]">
        <div class="max-w-lg mx-auto card-magic">
            <h1 class="text-2xl font-bold text-white mb-6">Edit Showtime</h1>
            <?php if ($errors): ?>
                <div class="bg-red-900 text-red-300 px-4 py-2 rounded mb-4">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo escape($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo escape($csrf_token); ?>">
                <div>
                    <label class="block text-sm font-medium text-gray-200 mb-1">Movie <span
                            class="text-red-500">*</span></label>
                    <select name="movie_id"
                        class="w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded text-white focus:ring-red-500 focus:border-red-500">
                        <option value="">Select a movie</option>
                        <?php foreach ($movies as $movie): ?>
                            <option value="<?php echo $movie['id']; ?>" <?php if ($movie_id == $movie['id'])
                                   echo 'selected'; ?>><?php echo escape($movie['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-200 mb-1">Theatre <span
                            class="text-red-500">*</span></label>
                    <select name="theatre_id"
                        class="w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded text-white focus:ring-red-500 focus:border-red-500">
                        <option value="">Select a theatre</option>
                        <?php foreach ($theatres as $theatre): ?>
                            <option value="<?php echo $theatre['id']; ?>" <?php if ($theatre_id == $theatre['id'])
                                   echo 'selected'; ?>><?php echo escape($theatre['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-200 mb-1">Date & Time <span
                            class="text-red-500">*</span></label>
                    <input type="datetime-local" name="show_datetime"
                        value="<?php echo escape(str_replace(' ', 'T', $show_datetime)); ?>"
                        class="w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded text-white focus:ring-red-500 focus:border-red-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-200 mb-1">Price per seat (FCFA) <span
                            class="text-red-500">*</span></label>
                    <input type="number" name="price_per_seat" min="0" step="1"
                        value="<?php echo escape($price_per_seat); ?>"
                        class="w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded text-white focus:ring-red-500 focus:border-red-500">
                </div>
                <div class="flex flex-col md:flex-row gap-2 pt-2">
                    <button type="submit" class="w-full btn-magic">Update Showtime</button>
                    <a href="manage_showtimes.php"
                        class="w-full btn-magic bg-gray-700 hover:bg-gray-600 text-gray-200">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>