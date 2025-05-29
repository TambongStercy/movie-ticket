<?php
session_start(); // Start session BEFORE loading .env and autoloader

// Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load .env file variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../'); // Points to the project root
$dotenv->load();

// Function to sanitize output (prevent XSS)
function escape($html)
{
    return htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Function to check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Function to check if user is an admin
function isAdmin()
{
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Redirect function
function redirect($url)
{
    header("Location: " . $url);
    exit();
}

// CSRF Token Generation
function generateCsrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF Token Validation
function validateCsrfToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Generate a styled movie ticket PDF (landscape)
function generateMovieTicketPDF($booking)
{
    $pdf = new \FPDF('L', 'mm', array(110, 210)); // Landscape, custom ticket size
    $pdf->AddPage();
    // Background color (maroon)
    $pdf->SetFillColor(128, 44, 54);
    $pdf->Rect(0, 0, 210, 110, 'F');
    // Border (gold)
    $pdf->SetDrawColor(218, 165, 32);
    $pdf->SetLineWidth(2);
    $pdf->Rect(5, 5, 200, 90, 'D');
    // Poster on left
    $poster_x = 12;
    $poster_y = 15;
    $poster_w = 40;
    $poster_h = 60;
    $poster_path = null;
    if (!empty($booking['poster_image_path']) && file_exists(__DIR__ . '/../uploads/posters/' . basename($booking['poster_image_path']))) {
        $poster_path = __DIR__ . '/../uploads/posters/' . basename($booking['poster_image_path']);
        $pdf->Image($poster_path, $poster_x, $poster_y, $poster_w, $poster_h);
    } else {
        $pdf->SetXY($poster_x, $poster_y + 25);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell($poster_w, 10, 'No Poster', 0, 0, 'C');
    }
    // Ticket text (right side)
    $pdf->SetXY(60, 18);
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 8, 'MOVIE THEATER', 0, 1);
    $pdf->SetX(60);
    $pdf->SetFont('Arial', 'B', 22);
    $pdf->SetTextColor(255, 215, 0);
    $pdf->Cell(0, 10, 'TICKET', 0, 1);
    $pdf->SetX(60);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 7, 'ADMIT ONE', 0, 1);
    // Movie details
    $pdf->SetX(60);
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 6, 'Movie: ' . $booking['title'], 0, 1);
    $pdf->SetX(60);
    $pdf->Cell(0, 6, 'Showtime: ' . $booking['show_datetime'], 0, 1);
    $pdf->SetX(60);
    $pdf->Cell(0, 6, 'Seats: ' . $booking['booked_seats'], 0, 1);
    $pdf->SetX(60);
    $pdf->Cell(0, 6, 'Total Paid: ' . number_format($booking['total_amount'], 0, '.', ' ') . ' FCFA', 0, 1);
    $pdf->SetX(60);
    $pdf->Cell(0, 6, 'Name: ' . $booking['username'], 0, 1);
    // Transaction Ref (immediately after name, no SetY)
    $pdf->SetX(60);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(255, 215, 0);
    $pdf->Cell(0, 8, 'Transaction Ref: ' . $booking['transaction_ref'], 0, 1);
    // Footer
    // $pdf->SetY(85);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 8, 'Thank you for booking with Movie Magic!', 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
    return $pdf->Output('S');
}
?>