<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
$success = '';

if (!isset($_SESSION['otp_user_id'])) {
    redirect('login.php');
}
$user_id = $_SESSION['otp_user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['resend'])) {
        // Resend OTP
        $stmt = $pdo->prepare('SELECT username, email FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $otp_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otp_expires = date('Y-m-d H:i:s', time() + 600);
            $stmt2 = $pdo->prepare('UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?');
            $stmt2->execute([$otp_code, $otp_expires, $user_id]);
            // Send OTP email
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
                $mail->addAddress($user['email'], $user['username']);
                $mail->Subject = 'Your OTP Code for Movie Magic Login';
                $mail->Body = "Dear {$user['username']},\n\nYour OTP code is: {$otp_code}\nIt expires in 10 minutes.\n\nIf you did not request this, please ignore this email.";
                $mail->send();
                $success = 'A new OTP has been sent to your email.';
            } catch (Exception $e) {
                $errors[] = 'Failed to resend OTP email. Please try again.';
            }
        }
    } else {
        $otp = trim($_POST['otp'] ?? '');
        if (empty($otp)) {
            $errors[] = 'Please enter the OTP.';
        } else {
            $stmt = $pdo->prepare('SELECT id, username, role, otp_code, otp_expires_at FROM users WHERE id = ?');
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && $user['otp_code'] === $otp && strtotime($user['otp_expires_at']) > time()) {
                // OTP correct and not expired
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                // Clear OTP
                $stmt2 = $pdo->prepare('UPDATE users SET otp_code = NULL, otp_expires_at = NULL WHERE id = ?');
                $stmt2->execute([$user_id]);
                unset($_SESSION['otp_user_id']);
                if ($user['role'] === 'admin') {
                    redirect('../admin/index.php');
                } else {
                    redirect('../user/index.php');
                }
            } else {
                $errors[] = 'Invalid or expired OTP. Please try again or resend.';
            }
        }
    }
}
$csrf_token = generateCsrfToken();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen flex items-center justify-center bg-[#18181c]">
    <div class="card-magic w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center text-white">Enter OTP</h2>
        <?php if (!empty($success)): ?>
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
        <form action="verify_otp.php" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo escape($csrf_token); ?>">
            <div>
                <label for="otp" class="block text-sm font-medium text-gray-200">OTP Code</label>
                <input type="text" name="otp" id="otp" maxlength="6" required pattern="[0-9]{6}"
                    class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white placeholder-gray-400 focus:ring-red-500 focus:border-red-500 sm:text-sm">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 btn-magic">Verify OTP</button>
                <button type="submit" name="resend" value="1"
                    class="flex-1 btn-magic bg-gray-700 hover:bg-gray-600 text-gray-200" id="resend-btn">Resend
                    OTP</button>
            </div>
        </form>
        <div class="mt-6">
            <a href="login.php" class="w-full btn-magic bg-gray-700 hover:bg-gray-600 text-gray-200">Back to Login</a>
        </div>
    </div>
</div>
<script>
    document.getElementById('resend-btn').addEventListener('click', function () {
    document.getElementById('otp').removeAttribute('required');
    });
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>