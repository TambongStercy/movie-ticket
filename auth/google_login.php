<?php
require_once __DIR__ . '/../includes/functions.php'; // Includes autoloader, .env, session_start

// Initialize Google Client
$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
$client->addScope("email");
$client->addScope("profile");
$client->addScope("openid");

// Generate the Auth URL
$auth_url = $client->createAuthUrl();

// Redirect to Google's OAuth server
header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
exit();
?>