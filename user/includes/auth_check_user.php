<?php
// Ensure functions.php is loaded.
require_once __DIR__ . '/../../includes/functions.php';

if (!isLoggedIn()) {
    // $_SESSION['error_message'] = "You must be logged in to access this page.";
    redirect('/movie_ticket_booking/auth/login.php?error=login_required');
}
?>