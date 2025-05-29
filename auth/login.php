<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
$login_identifier = ''; // Can be username or email

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('../admin/index.php');
    } else {
        redirect('../user/index.php'); // Or a general user dashboard/homepage
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = "CSRF token validation failed. Please try again.";
    } else {
        $login_identifier = trim($_POST['login_identifier']);
        $password = $_POST['password'];

        if (empty($login_identifier)) {
            $errors[] = "Username or Email is required.";
        }
        if (empty($password)) {
            $errors[] = "Password is required.";
        }

        if (empty($errors)) {
            try {
                // Check if it's an email or username
                $sql = "SELECT id, username, email, password_hash, role FROM users WHERE username = :identifier OR email = :identifier";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':identifier', $login_identifier);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password_hash'])) {
                    // Check if user is a Google user (skip OTP for Google login)
                    $stmt2 = $pdo->prepare('SELECT google_id FROM users WHERE id = ?');
                    $stmt2->execute([$user['id']]);
                    $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
                    if (!empty($row2['google_id'])) {
                        // Google user: log in directly
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_role'] = $user['role'];
                        if ($user['role'] === 'admin') {
                            redirect('/movie_ticket_booking/admin/index.php');
                        } else {
                            redirect('/movie_ticket_booking/user/index.php');
                        }
                    } else {
                        // Not a Google user: require OTP
                        $otp_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                        $otp_expires = date('Y-m-d H:i:s', time() + 600); // 10 min
                        $stmt3 = $pdo->prepare('UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?');
                        $stmt3->execute([$otp_code, $otp_expires, $user['id']]);
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
                        } catch (Exception $e) {
                            $errors[] = 'Failed to send OTP email. Please try again.';
                        }
                        // Store user ID in session for OTP verification
                        $_SESSION['otp_user_id'] = $user['id'];
                        redirect('verify_otp.php');
                    }
                } else {
                    $errors[] = "Invalid username/email or password.";
                }
            } catch (PDOException $e) {
                // Log $e->getMessage(); for actual debugging
                $errors[] = "Database error during login. Please try again later.";
            }
        }
    }
}

$csrf_token = generateCsrfToken();
include_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-[#18181c]">
    <div class="card-magic w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center text-white">Login to Your Account</h2>
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-900 text-green-300 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline"><?php echo escape($_SESSION['success_message']); ?></span>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="bg-red-900 text-red-300 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Login Failed!</strong>
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo escape($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form action="login.php" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo escape($csrf_token); ?>">
            <div>
                <label for="login_identifier" class="block text-sm font-medium text-gray-200">Username or Email</label>
                <input type="text" name="login_identifier" id="login_identifier"
                    value="<?php echo escape($login_identifier); ?>" required
                    class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white placeholder-gray-400 focus:ring-red-500 focus:border-red-500 sm:text-sm">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-200">Password</label>
                <input type="password" name="password" id="password" required
                    class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white placeholder-gray-400 focus:ring-red-500 focus:border-red-500 sm:text-sm">
            </div>
            <div>
                <button type="submit" class="w-full btn-magic">Login</button>
            </div>
        </form>
        <div class="mt-6 flex flex-col gap-2">
            <a href="register.php" class="w-full btn-magic bg-gray-700 hover:bg-gray-600 text-gray-200">Register</a>
            <a href="forgot_password.php" class="w-full btn-magic bg-gray-700 hover:bg-gray-600 text-gray-200">Forgot
                Password?</a>
        </div>
        <div class="mt-6">
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-700"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-[#23232b] text-gray-400">Or continue with</span>
                </div>
            </div>
            <div class="mt-6">
                <a href="google_login.php"
                    class="w-full flex items-center justify-center px-4 py-2 border border-red-700 rounded-md shadow-sm text-sm font-medium text-white bg-[#18181c] hover:bg-[#23232b] hover:border-red-500 transition"
                    style="gap:0.5rem;">
                    <svg class="w-5 h-5 mr-2" aria-hidden="true" focusable="false" data-prefix="fab" data-icon="google"
                        role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 488 512">
                        <path fill="currentColor"
                            d="M488 261.8C488 403.3 381.5 512 244 512 110.5 512 0 398.8 0 256S110.5 0 244 0c70.7 0 129.4 28.7 172.4 72.4l-66.4 64.5C328.5 112.3 289.6 96 244 96c-88.6 0-160.1 71.5-160.1 160s71.5 160 160.1 160c97.1 0 131.2-69.5 136.2-103.8H244v-74.6h236.1c2.6 14.8 3.9 30.9 3.9 47.4z">
                        </path>
                    </svg>
                    <span>Login with Google</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>