<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
$success = '';
$show_form = false;
$token = $_GET['token'] ?? '';

if ($token) {
    $stmt = $pdo->prepare('SELECT id, username, password_reset_expires FROM users WHERE password_reset_token = ?');
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && strtotime($user['password_reset_expires']) > time()) {
        $show_form = true;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            if (empty($password) || empty($confirm)) {
                $errors[] = 'Please enter and confirm your new password.';
            } elseif (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters.';
            } elseif ($password !== $confirm) {
                $errors[] = 'Passwords do not match.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt2 = $pdo->prepare('UPDATE users SET password_hash = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?');
                $stmt2->execute([$hash, $user['id']]);
                $success = 'Your password has been reset. You can now <a href="login.php" class="text-blue-600 underline">login</a>.';
                $show_form = false;
            }
        }
    } else {
        $errors[] = 'Invalid or expired reset link.';
    }
} else {
    $errors[] = 'Invalid or expired reset link.';
}
$csrf_token = generateCsrfToken();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen flex items-center justify-center bg-[#18181c]">
    <div class="card-magic w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center text-white">Reset Password</h2>
        <?php if ($success): ?>
            <div class="bg-green-900 text-green-300 px-4 py-3 rounded relative mb-4" role="alert">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="bg-red-900 text-red-300 px-4 py-3 rounded relative mb-4" role="alert">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo escape($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if ($show_form): ?>
            <form action="reset_password.php?token=<?php echo urlencode($token); ?>" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo escape($csrf_token); ?>">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-200">New Password</label>
                    <input type="password" name="password" id="password" required minlength="8"
                        class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white placeholder-gray-400 focus:ring-red-500 focus:border-red-500 sm:text-sm">
                </div>
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-200">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" required minlength="8"
                        class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white placeholder-gray-400 focus:ring-red-500 focus:border-red-500 sm:text-sm">
                </div>
                <button type="submit" class="w-full btn-magic">Reset Password</button>
            </form>
        <?php endif; ?>
        <div class="mt-6">
            <a href="login.php" class="w-full btn-magic bg-gray-700 hover:bg-gray-600 text-gray-200">Back to Login</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>