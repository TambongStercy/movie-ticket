<?php
require_once __DIR__ . '/includes/auth_check_admin.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$errors = [];

// Fetch user
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo '<div class="container mx-auto px-4 py-8"><div class="bg-red-100 text-red-700 px-4 py-2 rounded">User not found.</div></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$username = $user['username'];
$email = $user['email'];
$role = $user['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'CSRF token validation failed.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $password = $_POST['password'] ?? '';
        if ($username === '') {
            $errors[] = 'Username is required.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required.';
        }
        if (!in_array($role, ['user', 'admin'])) {
            $errors[] = 'Invalid role.';
        }
        // Check for duplicate username/email (exclude current user)
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?');
        $stmt->execute([$username, $email, $id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Username or email already exists.';
        }
        if (empty($errors)) {
            if ($password !== '') {
                if (strlen($password) < 8) {
                    $errors[] = 'Password must be at least 8 characters.';
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('UPDATE users SET username = ?, email = ?, password_hash = ?, role = ? WHERE id = ?');
                    $stmt->execute([$username, $email, $password_hash, $role, $id]);
                }
            } else {
                $stmt = $pdo->prepare('UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?');
                $stmt->execute([$username, $email, $role, $id]);
            }
            if (empty($errors)) {
                $_SESSION['success'] = 'User updated successfully!';
                header('Location: manage_users.php');
                exit;
            }
        }
    }
}
$csrf_token = generateCsrfToken();
?>
<div class="flex">
    <div class="ml-[240px] pt-20 w-full min-h-screen p-8 bg-[#18181c]">
        <div class="max-w-lg mx-auto card-magic">
            <h1 class="text-2xl font-bold text-white mb-6">Edit User</h1>
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
                    <label class="block text-sm font-medium text-gray-200 mb-1">Username <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="username" value="<?php echo escape($username); ?>"
                        class="w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded text-white focus:ring-red-500 focus:border-red-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-200 mb-1">Email <span
                            class="text-red-500">*</span></label>
                    <input type="email" name="email" value="<?php echo escape($email); ?>"
                        class="w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded text-white focus:ring-red-500 focus:border-red-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-200 mb-1">Password <span
                            class="text-gray-400">(leave blank to keep current)</span></label>
                    <input type="password" name="password"
                        class="w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded text-white focus:ring-red-500 focus:border-red-500">
                    <span class="text-xs text-gray-400">At least 8 characters if changing</span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-200 mb-1">Role <span
                            class="text-red-500">*</span></label>
                    <select name="role"
                        class="w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded text-white focus:ring-red-500 focus:border-red-500">
                        <option value="user" <?php if ($role === 'user')
                            echo 'selected'; ?>>User</option>
                        <option value="admin" <?php if ($role === 'admin')
                            echo 'selected'; ?>>Admin</option>
                    </select>
                </div>
                <div class="flex flex-col md:flex-row gap-2 pt-2">
                    <button type="submit" class="w-full btn-magic">Update User</button>
                    <a href="manage_users.php"
                        class="w-full btn-magic bg-gray-700 hover:bg-gray-600 text-gray-200">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>