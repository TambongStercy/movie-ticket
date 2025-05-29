<?php
require_once __DIR__ . '/includes/auth_check_user.php'; // Protect page
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Fetch all movies
$stmt = $pdo->query('SELECT * FROM movies ORDER BY release_date DESC, title ASC');
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch 3 random movies for the hero carousel
$hero_movies = [];
if (count($movies) > 0) {
    $keys = array_rand($movies, min(3, count($movies)));
    if (is_array($keys)) {
        foreach ($keys as $k)
            $hero_movies[] = $movies[$k];
    } else {
        $hero_movies[] = $movies[$keys];
    }
}

// Fetch top 3 rated movies (by avg rating)
$top_rated = [];
if (count($movies) > 2) {
    $stmt = $pdo->query('SELECT m.*, AVG(r.rating) AS avg_rating, COUNT(r.id) AS num_ratings FROM movies m LEFT JOIN movie_ratings r ON m.id = r.movie_id GROUP BY m.id ORDER BY avg_rating DESC, num_ratings DESC LIMIT 3');
    $top_rated = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all genres (unique, from movies table)
$genres = [];
foreach ($movies as $movie) {
    if (!empty($movie['genre'])) {
        foreach (explode(',', $movie['genre']) as $g) {
            $g = trim($g);
            if ($g && !in_array($g, $genres))
                $genres[] = $g;
        }
    }
}

?>
<div class="flex">
    <!-- Sidebar is fixed, so add left margin to main content on md: and up -->
    <div class="w-full min-h-screen p-0 bg-[#18181c] pt-20 md:ml-[240px]">
        <!-- Hero Carousel -->
        <?php if ($hero_movies): ?>
            <div
                class="relative w-full max-w-6xl mx-auto h-[380px] md:h-[440px] rounded-2xl overflow-hidden mb-12 flex items-stretch">
                <div id="hero-carousel" class="relative w-full h-full">
                    <?php foreach ($hero_movies as $i => $movie):
                        $cover = !empty($movie['cover_image_path']) ? '/movie_ticket_booking/uploads/posters/' . escape(basename($movie['cover_image_path'])) : (!empty($movie['poster_image_path']) ? '/movie_ticket_booking/uploads/posters/' . escape(basename($movie['poster_image_path'])) : null);
                        ?>
                        <div class="hero-slide absolute inset-0 w-full h-full transition-opacity duration-700 flex items-center <?php echo $i === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0'; ?>"
                            data-index="<?php echo $i; ?>">
                            <?php if ($cover): ?>
                                <img src="<?php echo $cover; ?>" alt="Cover"
                                    class="w-full h-full object-cover object-center absolute inset-0 z-0" />
                                <div class="absolute inset-0 bg-gradient-to-r from-black/95 via-black/80 to-transparent z-10"></div>
                            <?php else: ?>
                                <div class="w-full h-full bg-gray-700 flex items-center justify-center text-gray-400 text-3xl">No
                                    Image</div>
                            <?php endif; ?>
                            <div class="relative z-20 flex flex-col justify-center h-full pl-12 pr-8 max-w-2xl">
                                <h2 class="text-5xl md:text-6xl font-extrabold text-white mb-6 drop-shadow-lg leading-tight">
                                    <?php echo escape($movie['title']); ?>
                                </h2>
                                <div class="flex items-center gap-4 text-gray-200 mb-6 text-xl font-semibold">
                                    <?php if ($movie['release_date']): ?><span><?php echo date('Y', strtotime($movie['release_date'])); ?></span><?php endif; ?>
                                    <?php if (!empty($movie['category'])): ?><span
                                            class="bg-blue-800 text-white rounded px-3 py-1 text-sm font-bold"><?php echo escape($movie['category']); ?></span><?php endif; ?>
                                    <?php if (!empty($movie['duration_minutes'])): ?><span><?php echo escape($movie['duration_minutes']); ?>
                                            min</span><?php endif; ?>
                                </div>
                                <div>
                                    <a href="movie_details.php?id=<?php echo $movie['id']; ?>"
                                        class="inline-flex mt-2 px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-base font-bold shadow-lg transition">View
                                        Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <!-- Carousel controls -->
                    <button id="hero-prev"
                        class="absolute left-4 top-1/2 -translate-y-1/2 z-30 bg-black/40 hover:bg-black/70 text-white rounded-full w-10 h-10 flex items-center justify-center"><svg
                            class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg></button>
                    <button id="hero-next"
                        class="absolute right-4 top-1/2 -translate-y-1/2 z-30 bg-black/40 hover:bg-black/70 text-white rounded-full w-10 h-10 flex items-center justify-center"><svg
                            class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg></button>
                </div>
            </div>
        <?php endif; ?>
        <!-- Top Rated -->
        <?php if (count($movies) > 2): ?>
            <div class="max-w-6xl mx-auto mb-10">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-white">Top Rated</h2>
                    <a href="search.php?sort=rating" class="text-red-400 hover:underline text-sm">See More</a>
                </div>
                <div class="flex gap-6 overflow-x-auto pb-2">
                    <?php foreach ($top_rated as $movie):
                        $poster = !empty($movie['poster_image_path']) ? '/movie_ticket_booking/uploads/posters/' . escape(basename($movie['poster_image_path'])) : null;
                        $avg_rating = $movie['avg_rating'] ? round($movie['avg_rating'], 1) : null;
                        ?>
                        <div
                            class="relative min-w-[220px] max-w-[220px] bg-[#23232b] rounded-lg overflow-hidden shadow-lg flex-shrink-0">
                            <?php if ($poster): ?>
                                <img src="<?php echo $poster; ?>" alt="Poster" class="w-full h-56 object-cover">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>
                            <?php else: ?>
                                <div class="w-full h-56 bg-gray-700 flex items-center justify-center text-gray-400">No Image</div>
                            <?php endif; ?>
                            <div class="absolute bottom-0 left-0 w-full p-4">
                                <h4 class="text-lg font-bold text-white mb-1"><?php echo escape($movie['title']); ?></h4>
                                <div class="flex items-center gap-2 text-yellow-400 text-sm mb-1">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <svg class="w-4 h-4 <?php echo $i <= round($avg_rating) ? 'text-yellow-400' : 'text-gray-600'; ?>"
                                            fill="currentColor" viewBox="0 0 20 20">
                                            <polygon
                                                points="9.9,1.1 7.6,6.6 1.6,7.3 6.1,11.2 4.8,17.1 9.9,14.1 15,17.1 13.7,11.2 18.2,7.3 12.2,6.6 " />
                                        </svg>
                                    <?php endfor; ?>
                                    <span class="ml-2 text-white"><?php echo $avg_rating ? $avg_rating : 'N/A'; ?>/5</span>
                                </div>
                                <a href="movie_details.php?id=<?php echo $movie['id']; ?>" class="btn-magic text-xs">View
                                    Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Genres -->
            <?php if ($genres): ?>
                <div class="max-w-6xl mx-auto mb-10">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-2xl font-bold text-white">Genres</h2>
                        <a href="search.php" class="text-red-400 hover:underline text-sm">See More</a>
                    </div>
                    <div class="flex gap-3 flex-wrap">
                        <?php foreach ($genres as $genre): ?>
                            <a href="search.php?genre=<?php echo urlencode($genre); ?>"
                                class="px-4 py-2 rounded-full bg-[#23232b] text-gray-200 hover:bg-red-600 hover:text-white transition text-sm font-semibold shadow">#<?php echo escape($genre); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <!-- Popular/All Movies -->
        <div class="max-w-6xl mx-auto mb-10">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-white">Popular on Movie Magic</h2>
                <a href="search.php" class="text-red-400 hover:underline text-sm">See More</a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
                <?php foreach ($movies as $movie):
                    $poster = !empty($movie['poster_image_path']) ? '/movie_ticket_booking/uploads/posters/' . escape(basename($movie['poster_image_path'])) : null;
                    ?>
                    <div class="relative bg-[#23232b] rounded-lg overflow-hidden shadow-lg flex flex-col items-stretch"
                        style="width:220px; min-width:220px; max-width:220px; height:340px;">
                        <?php if ($poster): ?>
                            <img src="<?php echo $poster; ?>" alt="Poster"
                                class="absolute inset-0 w-full h-full object-cover z-0" style="height:340px; width:220px;" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent z-10"></div>
                        <?php else: ?>
                            <div class="w-full h-full bg-gray-700 flex items-center justify-center text-gray-400">No Image</div>
                        <?php endif; ?>
                        <div class="relative z-20 mt-auto p-4">
                            <h4 class="text-lg font-bold text-white mb-1 truncate"><?php echo escape($movie['title']); ?>
                            </h4>
                            <div class="flex items-center gap-2 text-gray-300 text-xs mb-1">
                                <?php if (!empty($movie['genre'])): ?><span><?php echo escape($movie['genre']); ?></span><?php endif; ?>
                                <?php if (!empty($movie['duration_minutes'])): ?><span><?php echo escape($movie['duration_minutes']); ?>
                                        min</span><?php endif; ?>
                            </div>
                            <a href="movie_details.php?id=<?php echo $movie['id']; ?>" class="btn-magic text-xs">View
                                Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<script>
    // Simple carousel logic
    (function () {
        const slides = document.querySelectorAll('.hero-slide');
        let current = 0;
        function showSlide(idx) {
            slides.forEach((slide, i) => {
                slide.classList.toggle('opacity-100', i === idx);
                slide.classList.toggle('z-10', i === idx);
                slide.classList.toggle('opacity-0', i !== idx);
                slide.classList.toggle('z-0', i !== idx);
            });
        }
        document.getElementById('hero-prev').onclick = function () {
            current = (current - 1 + slides.length) % slides.length;
            showSlide(current);
        };
        document.getElementById('hero-next').onclick = function () {
            current = (current + 1) % slides.length;
            showSlide(current);
        };
        // Auto-advance every 7s
        setInterval(function () {
            current = (current + 1) % slides.length;
            showSlide(current);
        }, 7000);
    })();
</script>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>