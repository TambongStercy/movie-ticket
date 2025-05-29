<?php
require_once __DIR__ . '/includes/auth_check_admin.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php'; // For CSRF, escape, redirect
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$errors = [];
$title = $description = $director = $cast = $genre = $duration_minutes = $release_date = $category = $max_age = '';

// Define the target directory for uploads relative to this script's location
// __DIR__ is /admin, so ../uploads/posters/
$upload_dir = __DIR__ . '/../uploads/posters/';
// $upload_dir_public_path = '/movie_ticket_booking/uploads/posters/'; // Path for displaying in HTML (not used in this script directly for output)

// Ensure upload directory exists and is writable
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        $errors[] = "Failed to create upload directory. Please check permissions.";
    }
}
// Check writability *after* attempting to create, and only if it now exists
if (is_dir($upload_dir) && !is_writable($upload_dir)) {
    $errors[] = "Upload directory is not writable. Please check permissions: " . realpath($upload_dir);
}

$cover_image_path = null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($errors)) { // Only proceed if no initial dir errors
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = "CSRF token validation failed. Please try again.";
    } else {
        // Sanitize and retrieve form data
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $director = trim($_POST['director']);
        $cast = trim($_POST['cast']);
        $genre = trim($_POST['genre']);
        $duration_minutes = trim($_POST['duration_minutes']);
        $release_date = trim($_POST['release_date']);
        $category = trim($_POST['category'] ?? '');
        $max_age = trim($_POST['max_age'] ?? '');
        $poster_image_path = null;

        // Basic Validations
        if (empty($title)) {
            $errors[] = "Title is required.";
        }
        if (!empty($duration_minutes) && !filter_var($duration_minutes, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            $errors[] = "Duration must be a positive integer.";
        }
        if (!empty($release_date)) {
            $date_parts = explode('-', $release_date);
            if (count($date_parts) !== 3 || !checkdate((int) $date_parts[1], (int) $date_parts[2], (int) $date_parts[0])) {
                $errors[] = "Invalid release date format. Please use YYYY-MM-DD.";
            }
        }

        // File Upload Handling
        if (isset($_FILES['poster_image']) && $_FILES['poster_image']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES['poster_image']['tmp_name'];
            $file_name = $_FILES['poster_image']['name'];
            $file_size = $_FILES['poster_image']['size'];
            // $file_type = $_FILES['poster_image']['type']; // Don't rely solely on this
            $file_name_parts = explode('.', $file_name);
            $file_ext = strtolower(end($file_name_parts));

            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            $max_file_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($file_ext, $allowed_extensions)) {
                $errors[] = "Invalid file type. Allowed types: JPG, JPEG, PNG, GIF.";
            }
            if ($file_size > $max_file_size) {
                $errors[] = "File size exceeds the limit of 5MB.";
            }

            if (empty($errors)) { // Proceed with upload if no file errors
                // Generate a unique filename to prevent overwriting and for security
                $poster_image_path = uniqid('poster_', true) . '.' . $file_ext;
                $destination_path = $upload_dir . $poster_image_path;

                if (!move_uploaded_file($file_tmp_path, $destination_path)) {
                    $errors[] = "Failed to move uploaded file. Check server permissions for: " . realpath($upload_dir);
                    $poster_image_path = null; // Reset if move failed
                }
            }
        } elseif (isset($_FILES['poster_image']) && $_FILES['poster_image']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['poster_image']['error'] != UPLOAD_ERR_OK) {
            // Provide more specific upload error messages
            switch ($_FILES['poster_image']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $errors[] = "The uploaded file exceeds the upload_max_filesize directive in php.ini.";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errors[] = "The uploaded file was only partially uploaded.";
                    break;
                // UPLOAD_ERR_NO_FILE is handled by not setting $poster_image_path, so no specific error message unless it's required.
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errors[] = "Missing a temporary folder for uploads.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errors[] = "Failed to write file to disk.";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $errors[] = "A PHP extension stopped the file upload.";
                    break;
                default:
                    $errors[] = "Unknown upload error. Code: " . $_FILES['poster_image']['error'];
                    break;
            }
        }

        // File Upload Handling for Cover Image
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES['cover_image']['tmp_name'];
            $file_name = $_FILES['cover_image']['name'];
            $file_size = $_FILES['cover_image']['size'];
            $file_name_parts = explode('.', $file_name);
            $file_ext = strtolower(end($file_name_parts));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            $max_file_size = 5 * 1024 * 1024; // 5MB
            if (!in_array($file_ext, $allowed_extensions)) {
                $errors[] = "Invalid cover image type. Allowed: JPG, JPEG, PNG, GIF.";
            }
            if ($file_size > $max_file_size) {
                $errors[] = "Cover image size exceeds 5MB.";
            }
            if (empty($errors)) {
                $cover_image_path = uniqid('cover_', true) . '.' . $file_ext;
                $destination_path = $upload_dir . $cover_image_path;
                if (!move_uploaded_file($file_tmp_path, $destination_path)) {
                    $errors[] = "Failed to move uploaded cover image.";
                    $cover_image_path = null;
                }
            }
        } elseif (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['cover_image']['error'] != UPLOAD_ERR_OK) {
            $errors[] = "Error uploading cover image.";
        }

        // If no errors after all validations, insert into database
        if (empty($errors)) {
            try {
                $sql = "INSERT INTO movies (title, description, director, cast, genre, category, max_age, duration_minutes, release_date, poster_image_path, cover_image_path) 
                        VALUES (:title, :description, :director, :cast, :genre, :category, :max_age, :duration_minutes, :release_date, :poster_image_path, :cover_image_path)";
                $stmt = $pdo->prepare($sql);

                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':description', $description); // PDO::PARAM_STR by default if variable is string or null
                $stmt->bindParam(':director', $director);
                $stmt->bindParam(':cast', $cast);
                $stmt->bindParam(':genre', $genre);
                $stmt->bindParam(':category', $category);
                $max_age_to_db = ($max_age !== '' ? $max_age : null);
                $stmt->bindParam(':max_age', $max_age_to_db);

                $duration_to_db = !empty($duration_minutes) ? (int) $duration_minutes : null;
                // Explicitly bind type for null or int
                if ($duration_to_db === null) {
                    $stmt->bindParam(':duration_minutes', $duration_to_db, PDO::PARAM_NULL);
                } else {
                    $stmt->bindParam(':duration_minutes', $duration_to_db, PDO::PARAM_INT);
                }

                $release_date_to_db = !empty($release_date) ? $release_date : null;
                // Explicitly bind type for null or string
                if ($release_date_to_db === null) {
                    $stmt->bindParam(':release_date', $release_date_to_db, PDO::PARAM_NULL);
                } else {
                    $stmt->bindParam(':release_date', $release_date_to_db, PDO::PARAM_STR);
                }

                // Explicitly bind type for null or string
                if ($poster_image_path === null) {
                    $stmt->bindParam(':poster_image_path', $poster_image_path, PDO::PARAM_NULL);
                } else {
                    $stmt->bindParam(':poster_image_path', $poster_image_path, PDO::PARAM_STR);
                }

                // Explicitly bind type for null or string
                if ($cover_image_path === null) {
                    $stmt->bindParam(':cover_image_path', $cover_image_path, PDO::PARAM_NULL);
                } else {
                    $stmt->bindParam(':cover_image_path', $cover_image_path, PDO::PARAM_STR);
                }

                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Movie '" . escape($title) . "' added successfully!";
                    header('Location: manage_movies.php');
                    exit;
                } else {
                    $errors[] = "Failed to add movie to database.";
                    // If poster or cover image was uploaded but DB insert failed, consider deleting the uploaded files
                    if ($poster_image_path && file_exists($upload_dir . $poster_image_path)) {
                        unlink($upload_dir . $poster_image_path);
                    }
                    if ($cover_image_path && file_exists($upload_dir . $cover_image_path)) {
                        unlink($upload_dir . $cover_image_path);
                    }
                }
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
                if ($poster_image_path && file_exists($upload_dir . $poster_image_path)) {
                    unlink($upload_dir . $poster_image_path);
                }
                if ($cover_image_path && file_exists($upload_dir . $cover_image_path)) {
                    unlink($upload_dir . $cover_image_path);
                }
            }
        }
    }
}

$csrf_token = generateCsrfToken();
?>
<div class="flex">
    <div class="ml-[240px] w-full min-h-screen p-8 bg-[#18181c]">
        <div class="max-w-3xl mx-auto card-magic">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-white">Add New Movie</h1>
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
            <form action="add_movie.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo escape($csrf_token); ?>">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-200">Title <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" value="<?php echo escape($title); ?>" required
                        class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white focus:ring-red-500 focus:border-red-500 sm:text-sm">
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-200">Description</label>
                    <textarea name="description" id="description" rows="4"
                        class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white focus:ring-red-500 focus:border-red-500 sm:text-sm"><?php echo escape($description); ?></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="director" class="block text-sm font-medium text-gray-200">Director</label>
                        <input type="text" name="director" id="director" value="<?php echo escape($director); ?>"
                            class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white focus:ring-red-500 focus:border-red-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="release_date" class="block text-sm font-medium text-gray-200">Release Date</label>
                        <input type="date" name="release_date" id="release_date"
                            value="<?php echo escape($release_date); ?>"
                            class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white focus:ring-red-500 focus:border-red-500 sm:text-sm">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="genre" class="block text-sm font-medium text-gray-200">Genre
                            (comma-separated)</label>
                        <input type="text" name="genre" id="genre" value="<?php echo escape($genre); ?>"
                            class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white focus:ring-red-500 focus:border-red-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="duration_minutes" class="block text-sm font-medium text-gray-200">Duration
                            (minutes)</label>
                        <input type="number" name="duration_minutes" id="duration_minutes"
                            value="<?php echo escape($duration_minutes); ?>"
                            class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white focus:ring-red-500 focus:border-red-500 sm:text-sm">
                    </div>
                </div>
                <div>
                    <label for="cast" class="block text-sm font-medium text-gray-200">Cast (comma-separated)</label>
                    <textarea name="cast" id="cast" rows="3"
                        class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white focus:ring-red-500 focus:border-red-500 sm:text-sm"><?php echo escape($cast); ?></textarea>
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
                <div>
                    <label for="poster_image" class="block text-sm font-medium text-gray-200">Poster Image (Max 5MB:
                        JPG, PNG, GIF)</label>
                    <input type="file" name="poster_image" id="poster_image"
                        class="mt-1 block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-[#23232b] file:text-red-400 hover:file:bg-[#18181c]"
                        accept="image/*" onchange="previewImage(event, 'poster_preview')">
                    <img id="poster_preview" class="mt-2 max-h-40 rounded shadow hidden" />
                </div>
                <div>
                    <label for="cover_image" class="block text-sm font-medium text-gray-200">Cover Image (Max 5MB: JPG,
                        PNG, GIF)</label>
                    <input type="file" name="cover_image" id="cover_image"
                        class="mt-1 block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-[#23232b] file:text-red-400 hover:file:bg-[#18181c]"
                        accept="image/*" onchange="previewImage(event, 'cover_preview')">
                    <img id="cover_preview" class="mt-2 max-h-40 rounded shadow hidden" />
                </div>
                <div class="pt-2">
                    <button type="submit" class="w-full btn-magic">Add Movie</button>
                </div>
            </form>
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