<?php
require_once __DIR__ . '/includes/auth_check_user.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Fetch user info
$stmt = $pdo->prepare('SELECT username, email, phone, address, profile_picture FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo '<div class="container mx-auto px-4 py-8"><div class="bg-red-100 text-red-700 px-4 py-2 rounded">User not found.</div></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
$username = $user['username'];
$email = $user['email'];
$phone = $user['phone'];
$address = $user['address'];
$profile_picture = $user['profile_picture'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'CSRF token validation failed.';
    } else {
        $new_username = trim($_POST['username'] ?? '');
        $new_phone = trim($_POST['phone'] ?? '');
        $new_address = trim($_POST['address'] ?? '');
        $new_profile_picture = $profile_picture;
        if ($new_username === '') {
            $errors[] = 'Username is required.';
        }
        // Check for duplicate username (exclude self)
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? AND id != ?');
        $stmt->execute([$new_username, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Username already exists.';
        }
        // Handle profile picture upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_picture'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowed_types)) {
                $errors[] = 'Profile picture must be a JPG, PNG, or GIF image.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Profile picture must be less than 2MB.';
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
                $upload_dir = __DIR__ . '/../uploads/profile_pics/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $target = $upload_dir . $filename;
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    // Delete old picture if exists
                    if ($profile_picture && file_exists($upload_dir . $profile_picture)) {
                        @unlink($upload_dir . $profile_picture);
                    }
                    $new_profile_picture = $filename;
                } else {
                    $errors[] = 'Failed to upload profile picture.';
                }
            }
        }
        if (empty($errors)) {
            $stmt = $pdo->prepare('UPDATE users SET username = ?, phone = ?, address = ?, profile_picture = ? WHERE id = ?');
            $stmt->execute([$new_username, $new_phone, $new_address, $new_profile_picture, $user_id]);
            $_SESSION['username'] = $new_username;
            $username = $new_username;
            $phone = $new_phone;
            $address = $new_address;
            $profile_picture = $new_profile_picture;
            $success = 'Profile updated successfully!';
        }
    }
}
$csrf_token = generateCsrfToken();
$profile_pic_url = $profile_picture ? '/movie_ticket_booking/uploads/profile_pics/' . $profile_picture : null;
?>
<div class="flex">
    <div class="w-full min-h-screen p-8 bg-[#18181c] pt-20 md:ml-[240px]">
        <h1 class="text-2xl font-bold text-white mb-8">My Profile</h1>
        <?php if ($success): ?>
            <div class="bg-green-900 text-green-300 px-4 py-2 rounded mb-4"><?php echo escape($success); ?></div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="bg-red-900 text-red-300 px-4 py-2 rounded mb-4">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo escape($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="card-magic max-w-lg mx-auto">
            <input type="hidden" name="csrf_token" value="<?php echo escape($csrf_token); ?>">
            <div class="mb-4 flex flex-col items-center">
                <?php if ($profile_pic_url): ?>
                    <img id="profile_preview" src="<?php echo escape($profile_pic_url); ?>" alt="Profile Picture"
                        class="w-24 h-24 rounded-full object-cover mb-2 border-2 border-red-600">
                <?php else: ?>
                    <img id="profile_preview"
                        class="w-24 h-24 rounded-full object-cover mb-2 border-2 border-red-600 hidden" />
                    <div class="w-24 h-24 rounded-full bg-gray-700 flex items-center justify-center text-gray-400 mb-2">No
                        Image</div>
                <?php endif; ?>
                <label class="block text-sm font-medium text-gray-400 mb-1">Profile Picture</label>
                <input type="file" name="profile_picture" accept="image/*" class="mt-1"
                    onchange="previewImage(event, 'profile_preview')">
                <span class="text-xs text-gray-500">JPG, PNG, GIF. Max 2MB.</span>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-400 mb-1">Username <span
                        class="text-red-500">*</span></label>
                <input type="text" name="username" value="<?php echo escape($username); ?>"
                    class="w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded text-white">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-400 mb-1">Email</label>
                <input type="email" value="<?php echo escape($email); ?>"
                    class="w-full px-3 py-2 bg-gray-800 border border-[#333] rounded text-gray-400 cursor-not-allowed"
                    disabled>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-400 mb-1">Phone</label>
                <input type="text" name="phone" value="<?php echo escape($phone); ?>"
                    class="w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded text-white">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-400 mb-1">Address</label>
                <input type="text" name="address" value="<?php echo escape($address); ?>"
                    class="w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded text-white">
            </div>
            <button type="submit" class="btn-magic w-full">Update Profile</button>
        </form>
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