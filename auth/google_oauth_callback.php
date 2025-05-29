<?php
require_once __DIR__ . '/../includes/db_connect.php';     // For $pdo
require_once __DIR__ . '/../includes/functions.php'; // Includes autoloader, .env, session_start

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
$client->addScope("email");
$client->addScope("profile");
$client->addScope("openid");

$errors = [];

if (isset($_GET['code'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        if (isset($token['error'])) {
            // Error fetching access token
            $errors[] = "Error fetching access token: " . htmlspecialchars($token['error_description'] ?? $token['error']);
        } else {
            $client->setAccessToken($token['access_token']);

            // Get profile info
            $google_oauth = new Google_Service_Oauth2($client);
            $google_account_info = $google_oauth->userinfo->get();

            $google_id = $google_account_info->getId();
            $email = $google_account_info->getEmail();
            $name = $google_account_info->getName(); // Full name
            // $givenName = $google_account_info->getGivenName();
            // $familyName = $google_account_info->getFamilyName();

            // Check if user exists by google_id
            $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ?");
            $stmt->execute([$google_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                // User not found by google_id, check by email as a fallback or for linking
                $stmt_email = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt_email->execute([$email]);
                $user_by_email = $stmt_email->fetch(PDO::FETCH_ASSOC);

                if ($user_by_email) {
                    // User exists with this email, link Google ID to existing account
                    $user = $user_by_email;
                    $update_stmt = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                    if (!$update_stmt->execute([$google_id, $user['id']])) {
                        $errors[] = "Could not link Google account to existing email.";
                        // Potentially log this error and proceed without linking if non-critical
                    }
                } else {
                    // New user, create an account
                    // Generate a unique username if necessary, or use email part
                    $username_parts = explode('@', $email);
                    $base_username = preg_replace('/[^a-zA-Z0-9_.]/', '', $username_parts[0]);
                    $username = $base_username;
                    $counter = 1;
                    // Ensure username is unique
                    while (true) {
                        $stmt_check_username = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                        $stmt_check_username->execute([$username]);
                        if (!$stmt_check_username->fetch()) {
                            break; // Unique username found
                        }
                        $username = $base_username . $counter;
                        $counter++;
                    }

                    // Password is not set directly, as authentication is via Google
                    // Store a placeholder or NULL if your schema allows. For now, a strong random hash.
                    $placeholder_password_hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

                    $insert_stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, google_id, role) VALUES (?, ?, ?, ?, 'user')");
                    if ($insert_stmt->execute([$username, $email, $placeholder_password_hash, $google_id])) {
                        $user_id = $pdo->lastInsertId();
                        $user = ['id' => $user_id, 'username' => $username, 'role' => 'user', 'email' => $email, 'google_id' => $google_id];
                    } else {
                        $errors[] = "Could not create new user account.";
                    }
                }
            }

            if ($user && empty($errors)) {
                // Log the user in
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username']; // Or $name from Google
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['google_logged_in'] = true; // Optional: flag for Google login

                if ($user['role'] === 'admin') {
                    redirect('../admin/index.php');
                } else {
                    redirect('../user/index.php'); // Or your main user page
                }
            }
        }
    } catch (Exception $e) {
        $errors[] = "Google OAuth Error: " . $e->getMessage();
    }
} else if (isset($_GET['error'])) {
    $errors[] = "Google OAuth Error: " . htmlspecialchars($_GET['error']);
}

// If there were errors, display them (or redirect to login with error)
if (!empty($errors)) {
    // You might want to redirect to login.php with an error message in session
    // For now, just outputting errors on this callback page (not ideal for UX)
    include_once __DIR__ . '/../includes/header.php';
    echo '<div class="bg-gray-100 flex items-center justify-center min-h-screen">';
    echo '<div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">';
    echo '<h2 class="text-2xl font-bold mb-6 text-center text-red-600">Google Login Failed</h2>';
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">';
    echo '<strong class="font-bold">Errors:</strong>';
    echo '<ul class="list-disc list-inside">';
    foreach ($errors as $error) {
        echo '<li>' . escape($error) . '</li>';
    }
    echo '</ul></div>';
    echo '<p class="mt-6 text-center"><a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">&larr; Back to Login</a></p>';
    echo '</div></div>';
    include_once __DIR__ . '/../includes/footer.php';
    exit();
}
?>