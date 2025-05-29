<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
$username = '';
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = "CSRF token validation failed. Please try again.";
    } else {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Basic Validations
        if (empty($username)) {
            $errors[] = "Username is required.";
        }
        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }
        if (empty($password)) {
            $errors[] = "Password is required.";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        }
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }

        // If no basic validation errors, check database
        if (empty($errors)) {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = "Username already taken. Please choose another.";
            }

            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Email already registered. Please use another or login.";
            }

            // If still no errors, proceed with registration
            if (empty($errors)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                try {
                    $sql = "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'user')";
                    $stmt = $pdo->prepare($sql);
                    if ($stmt->execute([$username, $email, $password_hash])) {
                        $_SESSION['success_message'] = "Registration successful! Please login.";
                        redirect('login.php');
                    } else {
                        $errors[] = "Error creating account. Please try again later.";
                    }
                } catch (PDOException $e) {
                    // Log error $e->getMessage() for actual debugging
                    $errors[] = "Database error during registration. Please try again later.";
                }
            }
        }
    }
}

// Generate CSRF token for the form
$csrf_token = generateCsrfToken();

include_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-[#18181c]">
    <div class="card-magic w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center text-white">Register New Account</h2>
        <?php if (!empty($errors)): ?>
            <div class="bg-red-900 text-red-300 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo escape($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form action="register.php" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo escape($csrf_token); ?>">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-200">Username</label>
                <input type="text" name="username" id="username" value="<?php echo escape($username); ?>" required
                    class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white placeholder-gray-400 focus:ring-red-500 focus:border-red-500 sm:text-sm">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-200">Email</label>
                <input type="email" name="email" id="email" value="<?php echo escape($email); ?>" required
                    class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white placeholder-gray-400 focus:ring-red-500 focus:border-red-500 sm:text-sm">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-200">Password (min 8
                    characters)</label>
                <input type="password" name="password" id="password" required
                    class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white placeholder-gray-400 focus:ring-red-500 focus:border-red-500 sm:text-sm">
            </div>
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-200">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required
                    class="mt-1 block w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded-md text-white placeholder-gray-400 focus:ring-red-500 focus:border-red-500 sm:text-sm">
            </div>
            <div>
                <button type="submit" class="w-full btn-magic">Register</button>
            </div>
        </form>
        <div class="mt-6">
            <a href="login.php" class="w-full btn-magic bg-gray-700 hover:bg-gray-600 text-gray-200">Back to Login</a>
        </div>
        <p class="mt-6 text-center text-sm text-gray-400">
            Already have an account?
            <a href="login.php" class="font-medium text-red-400 hover:text-red-300">Login here</a>.
        </p>
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
                    <span>Signup with Google</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>