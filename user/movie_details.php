<?php
require_once __DIR__ . '/includes/auth_check_user.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$movie_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$movie = null;
$showtimes = [];
$category = $max_age = '';

if ($movie_id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM movies WHERE id = ?');
    $stmt->execute([$movie_id]);
    $movie = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($movie) {
        $category = $movie['category'] ?? '';
        $max_age = $movie['max_age'] ?? '';
        $stmt = $pdo->prepare('SELECT s.*, t.name AS theatre_name FROM showtimes s JOIN theatres t ON s.theatre_id = t.id WHERE s.movie_id = ? AND s.show_datetime > NOW() ORDER BY s.show_datetime');
        $stmt->execute([$movie_id]);
        $showtimes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Handle rating submission
$rating_success = '';
$rating_error = '';
$user_id = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rate_movie'])) {
    $rating = (int) ($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    if ($rating < 1 || $rating > 5) {
        $rating_error = 'Please select a rating between 1 and 5.';
    } else {
        // Check if user already rated
        $stmt = $pdo->prepare('SELECT id FROM movie_ratings WHERE user_id = ? AND movie_id = ?');
        $stmt->execute([$user_id, $movie_id]);
        if ($stmt->fetch()) {
            $rating_error = 'You have already rated this movie.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO movie_ratings (user_id, movie_id, rating, comment) VALUES (?, ?, ?, ?)');
            $stmt->execute([$user_id, $movie_id, $rating, $comment]);
            $rating_success = 'Thank you for rating!';
        }
    }
}
// Fetch average rating and count
$stmt = $pdo->prepare('SELECT AVG(rating) AS avg_rating, COUNT(*) AS num_ratings FROM movie_ratings WHERE movie_id = ?');
$stmt->execute([$movie_id]);
$rating_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$avg_rating = $rating_stats['avg_rating'] ? round($rating_stats['avg_rating'], 1) : null;
$num_ratings = $rating_stats['num_ratings'] ?? 0;
// Fetch user's own rating
$stmt = $pdo->prepare('SELECT rating, comment FROM movie_ratings WHERE user_id = ? AND movie_id = ?');
$stmt->execute([$user_id, $movie_id]);
$user_rating = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<div class="flex min-h-screen overflow-hidden">
    <div class="ml-[240px] pt-20 w-full min-h-screen p-0 bg-[#18181c] relative">
    <?php if ($movie): ?>
            <?php
            $cover = !empty($movie['cover_image_path']) ? '/movie_ticket_booking/uploads/posters/' . escape(basename($movie['cover_image_path'])) : (!empty($movie['poster_image_path']) ? '/movie_ticket_booking/uploads/posters/' . escape(basename($movie['poster_image_path'])) : null);
            $poster = !empty($movie['poster_image_path']) ? '/movie_ticket_booking/uploads/posters/' . escape(basename($movie['poster_image_path'])) : null;
            ?>
            <?php if ($cover): ?>
                <div class="absolute inset-0 z-0">
                    <img src="<?php echo $cover; ?>" alt="Background Cover" class="w-full h-full object-cover blur-md scale-110"
                        style="filter: blur(12px) brightness(0.7);">
                    <!-- Gradient overlay: only on left, fading to right -->
                    <div class="absolute inset-0 pointer-events-none"
                        style="background: linear-gradient(90deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.7) 35%, rgba(0,0,0,0.2) 70%, rgba(0,0,0,0) 100%);">
                        </div>
                </div>
            <?php endif; ?>
            <div
                class="relative z-10 max-w-6xl mx-auto flex flex-col md:flex-row items-center md:items-start gap-16 py-12 px-6">
                <!-- Left: Details -->
                <div class="flex-1 text-left text-white max-w-xl">
                    <h1 class="text-4xl md:text-5xl font-extrabold mb-4 drop-shadow-lg">
                        <?php echo escape($movie['title']); ?>
                    </h1>
                    <div class="flex items-center gap-4 text-gray-300 mb-3 text-lg">
                        <?php if ($movie['release_date']): ?>
                            <span><?php echo date('Y', strtotime($movie['release_date'])); ?></span>
                        <?php endif; ?>
                        <?php if ($max_age): ?>
                            <span
                                class="border border-gray-400 rounded px-2 py-0.5 text-xs font-bold bg-black/40"><?php echo escape($max_age); ?>+</span>
                        <?php endif; ?>
                        <?php if ($movie['duration_minutes']): ?>
                            <span><?php echo escape($movie['duration_minutes']); ?> min</span>
                        <?php endif; ?>
                        <?php if ($category): ?>
                            <span
                                class="bg-blue-700/60 rounded px-2 py-0.5 text-xs font-bold"><?php echo escape($category); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="mb-4 text-gray-200 text-base">
                        <?php echo escape($movie['description']); ?>
                    </div>
                    <div class="mb-2 text-gray-400 text-sm">
                        <span class="font-semibold text-white">Director:</span> <?php echo escape($movie['director']); ?>
                    </div>
                    <div class="mb-2 text-gray-400 text-sm">
                        <span class="font-semibold text-white">Cast:</span> <?php echo escape($movie['cast']); ?>
                    </div>
                    <div class="mb-2 text-gray-400 text-sm">
                        <span class="font-semibold text-white">Genre:</span> <?php echo escape($movie['genre']); ?>
                    </div>
                    <!-- Ratings -->
                    <div class="flex items-center gap-2 mb-4 mt-4">
                        <?php if ($avg_rating): ?>
                            <div
                                class="flex items-center bg-[#23232b] px-4 py-2 rounded-full shadow text-yellow-400 font-bold text-lg">
                                <span class="mr-2 flex items-center">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <svg class="w-6 h-6 <?php echo $i <= round($avg_rating) ? 'text-yellow-400' : 'text-gray-600'; ?>"
                                            fill="currentColor" viewBox="0 0 20 20">
                                            <polygon
                                                points="9.9,1.1 7.6,6.6 1.6,7.3 6.1,11.2 4.8,17.1 9.9,14.1 15,17.1 13.7,11.2 18.2,7.3 12.2,6.6 " />
                                        </svg>
                                    <?php endfor; ?>
                                </span>
                                <span class="ml-2 text-white"><?php echo $avg_rating; ?>/5</span>
                                <span class="ml-2 text-gray-400 text-sm">(<?php echo $num_ratings; ?>
                                    rating<?php echo $num_ratings == 1 ? '' : 's'; ?>)</span>
                            </div>
                        <?php else: ?>
                            <div
                                class="flex items-center bg-[#23232b] px-4 py-2 rounded-full shadow text-gray-400 font-bold text-lg">
                                <span>No ratings yet</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- Rating form -->
                    <div class="mb-6">
                        <h2 class="text-xl font-semibold mb-2">Rate this Movie</h2>
                        <?php if ($rating_success): ?>
                            <div class="bg-green-900 text-green-300 px-4 py-2 rounded mb-4">
                                <?php echo escape($rating_success); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($rating_error): ?>
                            <div class="bg-red-900 text-red-300 px-4 py-2 rounded mb-4"><?php echo escape($rating_error); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($user_rating): ?>
                            <div class="mb-4 p-4 rounded-lg bg-green-900/60 border border-green-700 flex flex-col items-start">
                                <div class="flex items-center mb-1">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <svg class="w-6 h-6 <?php echo $i <= $user_rating['rating'] ? 'text-yellow-400' : 'text-gray-600'; ?>"
                                            fill="currentColor" viewBox="0 0 20 20">
                                            <polygon
                                                points="9.9,1.1 7.6,6.6 1.6,7.3 6.1,11.2 4.8,17.1 9.9,14.1 15,17.1 13.7,11.2 18.2,7.3 12.2,6.6 " />
                                        </svg>
                                    <?php endfor; ?>
                                    <span class="ml-2 text-green-300 font-bold">You rated:
                                        <?php echo escape($user_rating['rating']); ?>/5</span>
                                </div>
                                <?php if ($user_rating['comment']): ?>
                                    <div class="text-gray-300 italic mt-1">"<?php echo escape($user_rating['comment']); ?>"</div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <form method="POST" class="space-y-4">
                                <div class="flex items-center gap-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <button type="button" class="star-btn" data-value="<?php echo $i; ?>">
                                            <svg class="w-10 h-10 text-gray-600 transition-colors" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <polygon
                                                    points="9.9,1.1 7.6,6.6 1.6,7.3 6.1,11.2 4.8,17.1 9.9,14.1 15,17.1 13.7,11.2 18.2,7.3 12.2,6.6 " />
                                            </svg>
                                        </button>
                                    <?php endfor; ?>
                                    <input type="hidden" name="rating" id="rating-input" value="0">
                                </div>
                                <div>
                                    <textarea name="comment" rows="2" placeholder="Optional comment..."
                                        class="w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded text-white focus:ring-red-500 focus:border-red-500"></textarea>
                                </div>
                                <button type="submit" name="rate_movie"
                                    class="w-full py-2 rounded bg-red-600 hover:bg-red-700 text-white font-bold transition">Submit
                                    Rating</button>
                            </form>
                            <script>
                                // Interactive star rating
                                const starBtns = document.querySelectorAll('.star-btn');
                                const ratingInput = document.getElementById('rating-input');
                                let selected = 0;
                                starBtns.forEach((btn, idx) => {
                                    btn.addEventListener('mouseenter', () => {
                                        highlightStars(idx + 1);
                                    });
                                    btn.addEventListener('mouseleave', () => {
                                        highlightStars(selected);
                                    });
                                    btn.addEventListener('click', (e) => {
                                        e.preventDefault();
                                        selected = idx + 1;
                                        ratingInput.value = selected;
                                        highlightStars(selected);
                                    });
                                });
                                function highlightStars(count) {
                                    starBtns.forEach((btn, i) => {
                                        btn.querySelector('svg').classList.toggle('text-yellow-400', i < count);
                                        btn.querySelector('svg').classList.toggle('text-gray-600', i >= count);
                                    });
                                }
                            </script>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Right: Poster -->
                <div class="flex-shrink-0 w-64 md:w-80 mt-8 md:mt-0 flex justify-center items-start md:ml-16 ml-0">
                    <?php if ($poster): ?>
                        <img src="<?php echo $poster; ?>" alt="Poster"
                            class="w-full h-auto max-h-[500px] object-cover rounded-lg shadow-2xl border-4 border-[#23232b]/80">
                    <?php elseif ($cover): ?>
                        <img src="<?php echo $cover; ?>" alt="Cover"
                            class="w-full h-auto max-h-[500px] object-cover rounded-lg shadow-2xl border-4 border-[#23232b]/80">
                    <?php else: ?>
                        <div
                            class="w-full h-[400px] bg-gray-700 flex items-center justify-center text-gray-400 rounded-lg mb-2">
                            No Image</div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Showtimes/Booking section below both columns -->
            <div class="relative z-10 max-w-6xl mx-auto mt-12 mb-8">
                <div class="card-magic bg-[#23232b]/80 p-4">
            <h2 class="text-xl font-semibold mb-4">Available Showtimes</h2>
            <?php if ($showtimes): ?>
                <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-700">
                        <thead>
                            <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Theatre</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Date & Time
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Price (FCFA)
                                        </th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                                <tbody class="bg-[#23232b] divide-y divide-gray-800">
                            <?php foreach ($showtimes as $show): ?>
                                <tr>
                                            <td class="px-4 py-2 text-white"><?php echo escape($show['theatre_name']); ?></td>
                                            <td class="px-4 py-2 text-white"><?php echo escape($show['show_datetime']); ?></td>
                                            <td class="px-4 py-2 text-white">
                                                <?php echo number_format($show['price_per_seat'], 0, '.', ' '); ?>
                                            </td>
                                    <td class="px-4 py-2">
                                        <a href="select_seats.php?showtime_id=<?php echo $show['id']; ?>"
                                                    class="btn-magic">Book Now</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                        <div class="text-gray-400">No showtimes available for this movie.</div>
            <?php endif; ?>
                </div>
        </div>
    <?php else: ?>
            <div class="max-w-2xl mx-auto card-magic text-center">
                <div class="text-red-400 font-semibold">Movie not found.</div>
        </div>
    <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>