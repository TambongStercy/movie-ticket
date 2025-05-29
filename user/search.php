<?php
require_once __DIR__ . '/includes/auth_check_user.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Get all genres for filter
$genre_list = [];
$stmt = $pdo->query('SELECT genre FROM movies');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (!empty($row['genre'])) {
        foreach (explode(',', $row['genre']) as $g) {
            $g = trim($g);
            if ($g && !in_array($g, $genre_list))
                $genre_list[] = $g;
        }
    }
}

// Handle filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$genre = isset($_GET['genre']) ? trim($_GET['genre']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : '';

$sql = 'SELECT m.*, AVG(r.rating) AS avg_rating FROM movies m LEFT JOIN movie_ratings r ON m.id = r.movie_id';
$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(m.title LIKE ?)';
    $params[] = "%$search%";
}
if ($genre !== '' && in_array($genre, $genre_list)) {
    $where[] = '(m.genre LIKE ?)';
    $params[] = "%$genre%";
}
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' GROUP BY m.id';
if ($sort === 'rating') {
    $sql .= ' ORDER BY avg_rating DESC, m.title ASC';
} else {
    $sql .= ' ORDER BY m.release_date DESC, m.title ASC';
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="flex">
    <div class="w-full min-h-screen p-8 bg-[#18181c] pt-20 md:ml-[240px]">
        <div class="max-w-6xl mx-auto mb-8">
            <h1 class="text-3xl font-bold text-white mb-6">Search & Filter Movies</h1>
            <form method="GET" class="flex flex-col md:flex-row gap-4 mb-6 items-end">
                <div class="flex-1">
                    <label class="block text-sm text-gray-400 mb-1">Title</label>
                    <input type="text" name="search" value="<?php echo escape($search); ?>"
                        placeholder="Search by title..."
                        class="w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Genre</label>
                    <select name="genre" class="px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white">
                        <option value="">All Genres</option>
                        <?php foreach ($genre_list as $g): ?>
                            <option value="<?php echo escape($g); ?>" <?php if ($genre === $g)
                                   echo 'selected'; ?>>
                                <?php echo escape($g); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Sort By</label>
                    <select name="sort" class="px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white">
                        <option value="">Default</option>
                        <option value="rating" <?php if ($sort === 'rating')
                            echo 'selected'; ?>>Top Rated</option>
                    </select>
                </div>
                <button type="submit" class="btn-magic">Apply</button>
                <a href="search.php" class="ml-2 text-gray-400 hover:underline">Reset</a>
            </form>
            <?php if ($genre_list): ?>
                <div class="mb-6 flex flex-wrap gap-2">
                    <?php foreach ($genre_list as $g): ?>
                        <a href="search.php?genre=<?php echo urlencode($g); ?>" class="px-4 py-1 rounded-full bg-[#23232b] text-gray-200 hover:bg-red-600 hover:text-white transition text-xs font-semibold shadow <?php if ($genre === $g)
                               echo 'bg-red-600 text-white'; ?>">#<?php echo escape($g); ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="max-w-6xl mx-auto">
            <?php if ($movies): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
                    <?php foreach ($movies as $movie):
                        $poster = !empty($movie['poster_image_path']) ? '/movie_ticket_booking/uploads/posters/' . escape(basename($movie['poster_image_path'])) : null;
                        $avg_rating = $movie['avg_rating'] ? round($movie['avg_rating'], 1) : null;
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
                                <div class="flex items-center gap-2 text-yellow-400 text-xs mb-1">
                                    <?php if ($avg_rating): ?>
                                        <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                            <polygon
                                                points="9.9,1.1 7.6,6.6 1.6,7.3 6.1,11.2 4.8,17.1 9.9,14.1 15,17.1 13.7,11.2 18.2,7.3 12.2,6.6 " />
                                        </svg>
                                        <span class="ml-1 text-white"><?php echo $avg_rating; ?>/5</span>
                                    <?php endif; ?>
                                </div>
                                <a href="movie_details.php?id=<?php echo $movie['id']; ?>" class="btn-magic text-xs">View
                                    Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-gray-400 italic text-center py-12 text-lg">No movies found matching your criteria.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>