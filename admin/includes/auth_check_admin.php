<?php
// Ensure functions.php is loaded. Since functions.php starts the session,
// this needs to be included before any session checks.
// The path needs to go up two levels from admin/includes/ to the project root, then to includes/
require_once __DIR__ . '/../../includes/functions.php';

if (!isAdmin()) { // isAdmin() checks if logged in AND if role is 'admin'
    // Optionally, store a message for the login page
    // $_SESSION['error_message'] = "You must be logged in as an administrator to access this page.";
    redirect('/movie_ticket_booking/auth/login.php?error=admin_required'); // Corrected redirect path
}
?>