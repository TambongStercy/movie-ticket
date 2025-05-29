<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        $errors[] = 'Please enter your email.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
            $stmt2 = $pdo->prepare('UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?');
            $stmt2->execute([$token, $expires, $user['id']]);
            // Send email
            $reset_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/reset_password.php?token=' . $token;
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = $_ENV['EMAIL_SERVICE'] ?? $_ENV['SMTP_HOST'];
                $mail->SMTPAuth = true;
                $mail->Username = $_ENV['EMAIL_USER'];
                $mail->Password = $_ENV['EMAIL_PASSWORD'];
                $mail->SMTPSecure = $_ENV['SMTP_SECURE'] === 'true' ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : false;
                $mail->Port = (int) ($_ENV['SMTP_PORT'] ?? 25);
                $mail->setFrom($_ENV['EMAIL_FROM'], 'Movie Magic');
                $mail->addAddress($email, $user['username']);
                $mail->Subject = 'Password Reset Request - Movie Magic';
                $mail->Body = "Dear {$user['username']},\n\nTo reset your password, click the link below:\n{$reset_link}\n\nThis link will expire in 1 hour. If you did not request this, please ignore this email.";
                $mail->send();
            } catch (Exception $e) {
                error_log("Email sending failed: " . $mail->ErrorInfo);
                // Do not reveal error to user
            }
            $success = 'A password reset link has been sent to your email.';
        } else {
            $errors[] = 'No account found with that email address.';
        }
    }
}
$csrf_token = generateCsrfToken();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen flex items-center justify-center bg-[#18181c]">
    <div class="card-magic w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center text-white">Forgot Password</h2>
        <?php if ($success): ?>
            <div class="bg-green-900 text-green-300 px-4 py-3 rounded relative mb-4" role="alert">
                <?php echo escape($success); ?>
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
        <form action="forgot_password.php" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo escape($csrf_token); ?>">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-200">Email</label>
                <input type="email" name="email" id="email" required
                    class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white placeholder-gray-400 focus:ring-red-500 focus:border-red-500 sm:text-sm">
            </div>
            <button type="submit" class="w-full btn-magic">Send Reset Link</button>
        </form>
        <div class="mt-6">
            <a href="login.php" class="w-full btn-magic bg-gray-700 hover:bg-gray-600 text-gray-200">Back to Login</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>