<?php
require_once __DIR__ . '/includes/functions.php'; // For session, isLoggedIn, isAdmin, redirect

if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/index.php');
    } else {
        redirect('user/index.php');
    }
} else {
    // If not logged in, show a simple landing page or redirect to login
    // For now, let's redirect to login to simplify the entry flow.
    redirect('auth/login.php');
}

// Alternatively, to show a landing page content:
/*
include_once __DIR__ . '/includes/header.php';
?>
<div class="text-center">
    <h1 class="text-3xl font-bold mb-4">Welcome to Movie Magic!</h1>
    <p class="mb-6">Your one-stop destination for booking movie tickets.</p>
    <div class="space-x-4">
        <a href="auth/login.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Login
        </a>
        <a href="auth/register.php" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
            Register
        </a>
    </div>
    <p class="mt-8">Or browse movies as a guest (feature to be implemented).</p>
</div>
<?php
include_once __DIR__ . '/includes/footer.php';
*/
?>