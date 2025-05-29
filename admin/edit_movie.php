<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth_check_admin.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$movie_id = null;
$movie = null;
$errors = [];
$success_message = '';

// Check if ID is provided for editing
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $movie_id = (int) $_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
        $stmt->execute([$movie_id]);
        $movie = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$movie) {
            redirect('manage_movies.php?error=Movie not found');
        }
    } catch (PDOException $e) {
        $errors[] = "Error fetching movie: " . $e->getMessage();
    }
} else {
    redirect('manage_movies.php?error=No movie ID specified');
}

$category = $movie['category'] ?? '';
$max_age = $movie['max_age'] ?? '';
$cover_image_path = $movie['cover_image_path'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $movie_id) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'CSRF token mismatch. Please try again.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $director = trim($_POST['director'] ?? '');
        $cast = trim($_POST['cast'] ?? '');
        $genre = trim($_POST['genre'] ?? '');
        $duration_minutes = filter_input(INPUT_POST, 'duration_minutes', FILTER_VALIDATE_INT);
        $release_date = trim($_POST['release_date'] ?? '');
        $current_poster_path = $movie['poster_image_path'] ?? '';
        $category = trim($_POST['category'] ?? '');
        $max_age = trim($_POST['max_age'] ?? '');
        $cover_image_path = $movie['cover_image_path'] ?? '';

        // Basic validation
        if (empty($title))
            $errors[] = "Title is required.";
        if ($duration_minutes === false || $duration_minutes <= 0)
            $errors[] = "Duration must be a positive number.";
        if (empty($release_date))
            $errors[] = "Release date is required.";
        // Add more validation as needed (e.g., date format)

        $poster_image_path = $current_poster_path; // Keep current poster by default

        // Handle file upload
        if (isset($_FILES['poster_image']) && $_FILES['poster_image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/posters/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['poster_image']['type'];
            $file_size = $_FILES['poster_image']['size'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($file_type, $allowed_types)) {
                $errors[] = "Invalid file type. Only JPG, PNG, GIF allowed.";
            } elseif ($file_size > $max_size) {
                $errors[] = "File is too large. Maximum 5MB.";
            } else {
                $file_extension = pathinfo($_FILES['poster_image']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid('poster_', true) . '.' . $file_extension;
                $destination = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['poster_image']['tmp_name'], $destination)) {
                    // Delete old poster if a new one is uploaded and old one exists
                    if ($current_poster_path && file_exists($upload_dir . basename($current_poster_path))) {
                        unlink($upload_dir . basename($current_poster_path));
                    }
                    $poster_image_path = 'uploads/posters/' . $new_filename; // Relative path for DB
                } else {
                    $errors[] = "Failed to upload poster image.";
                }
            }
        }

        // Handle cover image upload
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/posters/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['cover_image']['type'];
            $file_size = $_FILES['cover_image']['size'];
            $max_size = 5 * 1024 * 1024; // 5MB
            if (!in_array($file_type, $allowed_types)) {
                $errors[] = "Invalid cover image type. Only JPG, PNG, GIF allowed.";
            } elseif ($file_size > $max_size) {
                $errors[] = "Cover image is too large. Maximum 5MB.";
            } else {
                $file_extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid('cover_', true) . '.' . $file_extension;
                $destination = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $destination)) {
                    // Delete old cover if a new one is uploaded and old one exists
                    if ($movie['cover_image_path'] && file_exists($upload_dir . basename($movie['cover_image_path']))) {
                        unlink($upload_dir . basename($movie['cover_image_path']));
                    }
                    $cover_image_path = $new_filename;
                } else {
                    $errors[] = "Failed to upload cover image.";
                }
            }
        }

        if (empty($errors)) {
            try {
                $sql = "UPDATE movies SET 
                            title = :title, 
                            description = :description, 
                            director = :director, 
                            cast = :cast, 
                            genre = :genre, 
                            category = :category, 
                            max_age = :max_age, 
                            duration_minutes = :duration_minutes, 
                            release_date = :release_date, 
                            poster_image_path = :poster_image_path, 
                            cover_image_path = :cover_image_path 
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':title' => $title,
                    ':description' => $description,
                    ':director' => $director,
                    ':cast' => $cast,
                    ':genre' => $genre,
                    ':category' => $category,
                    ':max_age' => $max_age !== '' ? $max_age : null,
                    ':duration_minutes' => $duration_minutes,
                    ':release_date' => $release_date,
                    ':poster_image_path' => $poster_image_path,
                    ':cover_image_path' => $cover_image_path,
                    ':id' => $movie_id
                ]);
                $success_message = "Movie updated successfully! You will be redirected shortly.";
                // Refresh movie data after update
                $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
                $stmt->execute([$movie_id]);
                $movie = $stmt->fetch(PDO::FETCH_ASSOC);

                header("Refresh: 3; url=manage_movies.php?success=Movie updated");
                exit;

            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

$csrf_token = generateCsrfToken();
$page_title = "Edit Movie";
?>
<div class="flex">
    <div class="ml-[240px] pt-20 w-full min-h-screen p-8 bg-[#18181c]">
        <div class="max-w-3xl mx-auto card-magic">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-white">Edit Movie: <?php echo escape($movie['title'] ?? 'N/A'); ?>
                </h2>
                <a href="manage_movies.php" class="btn-magic">&larr; Back to Manage Movies</a>
            </div>
            <?php if (!empty($errors)): ?>
                <div class="bg-red-900 text-red-300 px-4 py-3 rounded relative mb-6" role="alert">
                    <strong class="font-bold">Please correct the following errors:</strong>
                    <ul class="mt-2 list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo escape($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="bg-green-900 text-green-300 px-4 py-3 rounded relative mb-6" role="alert">
                    <?php echo escape($success_message); ?>
                </div>
            <?php endif; ?>
            <?php if ($movie): ?>
                <form action="edit_movie.php?id=<?php echo $movie_id; ?>" method="POST" enctype="multipart/form-data"
                    class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo escape($csrf_token); ?>">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-200">Title <span
                                class="text-red-500">*</span></label>
                        <input type="text"
                            class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white focus:ring-red-500 focus:border-red-500 sm:text-sm"
                            id="title" name="title" value="<?php echo escape($movie['title'] ?? ''); ?>" required>
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-200">Description</label>
                        <textarea
                            class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white focus:ring-red-500 focus:border-red-500 sm:text-sm"
                            id="description" name="description"
                            rows="5"><?php echo escape($movie['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="director" class="block text-sm font-medium text-gray-200">Director</label>
                            <input type="text"
                                class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white focus:ring-red-500 focus:border-red-500 sm:text-sm"
                                id="director" name="director" value="<?php echo escape($movie['director'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="release_date" class="block text-sm font-medium text-gray-200">Release Date</label>
                            <input type="date"
                                class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white focus:ring-red-500 focus:border-red-500 sm:text-sm"
                                id="release_date" name="release_date"
                                value="<?php echo escape($movie['release_date'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="genre" class="block text-sm font-medium text-gray-200">Genre
                                (comma-separated)</label>
                            <input type="text"
                                class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white focus:ring-red-500 focus:border-red-500 sm:text-sm"
                                id="genre" name="genre" value="<?php echo escape($movie['genre'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="duration_minutes" class="block text-sm font-medium text-gray-200">Duration
                                (minutes)</label>
                            <input type="number"
                                class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white focus:ring-red-500 focus:border-red-500 sm:text-sm"
                                id="duration_minutes" name="duration_minutes"
                                value="<?php echo escape($movie['duration_minutes'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div>
                        <label for="cast" class="block text-sm font-medium text-gray-200">Cast (comma-separated)</label>
                        <input type="text"
                            class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white focus:ring-red-500 focus:border-red-500 sm:text-sm"
                            id="cast" name="cast" value="<?php echo escape($movie['cast'] ?? ''); ?>">
                    </div>
                    <div>
                        <label for="poster_image" class="block text-sm font-medium text-gray-200">Poster Image (Max 5MB:
                            JPG, PNG, GIF)</label>
                        <input type="file"
                            class="mt-1 block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-[#23232b] file:text-red-400 hover:file:bg-[#18181c]"
                            id="poster_image" name="poster_image" accept="image/*"
                            onchange="previewImage(event, 'poster_preview')">
                        <img id="poster_preview"
                            class="mt-2 max-h-40 rounded shadow <?php echo !empty($movie['poster_image_path']) ? '' : 'hidden'; ?>"
                            src="<?php echo !empty($movie['poster_image_path']) ? '/movie_ticket_booking/uploads/posters/' . escape(basename($movie['poster_image_path'])) : ''; ?>" />
                    </div>
                    <div>
                        <label for="cover_image" class="block text-sm font-medium text-gray-200">Cover Image (Max 5MB: JPG,
                            PNG, GIF)</label>
                        <input type="file"
                            class="mt-1 block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-[#23232b] file:text-red-400 hover:file:bg-[#18181c]"
                            id="cover_image" name="cover_image" accept="image/*"
                            onchange="previewImage(event, 'cover_preview')">
                        <img id="cover_preview"
                            class="mt-2 max-h-40 rounded shadow <?php echo !empty($movie['cover_image_path']) ? '' : 'hidden'; ?>"
                            src="<?php echo !empty($movie['cover_image_path']) ? '/movie_ticket_booking/uploads/posters/' . escape(basename($movie['cover_image_path'])) : ''; ?>" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-200">Category</label>
                            <select name="category" id="category"
                                class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white focus:ring-red-500 focus:border-red-500 sm:text-sm">
                                <option value="">Select category</option>
                                <option value="Cartoon" <?php if ($category === 'Cartoon')
                                    echo 'selected'; ?>>Cartoon
                                </option>
                                <option value="Anime" <?php if ($category === 'Anime')
                                    echo 'selected'; ?>>Anime</option>
                                <option value="TV Show" <?php if ($category === 'TV Show')
                                    echo 'selected'; ?>>TV Show
                                </option>
                                <option value="Movie" <?php if ($category === 'Movie')
                                    echo 'selected'; ?>>Movie</option>
                                <option value="Other" <?php if ($category === 'Other')
                                    echo 'selected'; ?>>Other</option>
                            </select>
                        </div>
                        <div>
                            <label for="max_age" class="block text-sm font-medium text-gray-200">Maximum Age</label>
                            <input type="number" name="max_age" id="max_age" value="<?php echo escape($max_age); ?>"
                                class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white focus:ring-red-500 focus:border-red-500 sm:text-sm"
                                min="0">
                        </div>
                    </div>
                    <div class="pt-2 flex flex-col md:flex-row gap-2">
                        <button type="submit" class="w-full btn-magic">Update Movie</button>
                        <a href="manage_movies.php"
                            class="w-full btn-magic bg-gray-700 hover:bg-gray-600 text-gray-200">Cancel</a>
                    </div>
                </form>
            <?php else: ?>
                <p class="text-red-400">Movie data could not be loaded.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
    function previewImage(event, id) {
        const input = event.target;
        const preview = document.getElementById(id);
        if (input.files && input.files[0]) {
            preview.src = URL.createObjectURL(input.files[0]);
            preview.classList.remove('hidden');
        } else {
            preview.src = '';
            preview.classList.add('hidden');
        }
    }
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>