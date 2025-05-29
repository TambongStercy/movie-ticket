Okay, this is a great project! It covers many essential web development concepts. Let's break it down into manageable steps.

**I. Project Setup & Core Structure**

1.  **Environment:**
    *   Web server (Apache or Nginx) with PHP installed. XAMPP, WAMP, MAMP, or Laragon are good local development environments.
    *   MySQL database.
    *   A code editor (VS Code, Sublime Text, PhpStorm).

2.  **Directory Structure (Example):**

    ```
    movie_ticket_booking/
    ├── admin/
    │   ├── index.php                 (Admin Dashboard)
    │   ├── manage_movies.php
    │   ├── add_movie.php
    │   ├── edit_movie.php
    │   ├── manage_showtimes.php
    │   ├── add_showtime.php
    │   ├── edit_showtime.php
    │   ├── view_bookings.php
    │   └── includes/
    │       ├── header.php
    │       ├── footer.php
    │       └── auth_check_admin.php
    ├── auth/
    │   ├── login.php
    │   ├── register.php
    │   ├── logout.php
    │   └── google_oauth.php          (Handles Google OAuth callback)
    ├── css/
    │   └── style.css
    ├── images/
    │   └── (site logos, default poster, etc.)
    ├── includes/
    │   ├── db_connect.php            (Database connection)
    │   ├── functions.php             (Common helper functions)
    │   ├── header.php
    │   ├── footer.php
    │   └── auth_check_user.php
    ├── js/
    │   └── script.js                 (Optional, for client-side enhancements)
    ├── user/
    │   ├── index.php                 (User Dashboard / Movie Listing)
    │   ├── movie_details.php
    │   ├── book_ticket.php
    │   ├── select_seats.php
    │   ├── payment_simulation.php
    │   ├── booking_confirmation.php
    │   └── my_bookings.php
    ├── uploads/                      (For movie posters - ensure correct permissions)
    │   └── posters/
    ├── index.php                     (Main entry point, redirects or shows homepage)
    └── .htaccess                     (For URL rewriting, security headers - optional but good)
    ```

3.  **Database Design (MySQL):**

    *   **`users` table:**
        *   `id` (INT, PK, AUTO_INCREMENT)
        *   `username` (VARCHAR(50), UNIQUE, NOT NULL)
        *   `email` (VARCHAR(100), UNIQUE, NOT NULL)
        *   `password_hash` (VARCHAR(255), NOT NULL)
        *   `role` (ENUM('user', 'admin'), DEFAULT 'user')
        *   `google_id` (VARCHAR(255), NULL, UNIQUE) -- For Google OAuth
        *   `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

    *   **`movies` table:**
        *   `id` (INT, PK, AUTO_INCREMENT)
        *   `title` (VARCHAR(255), NOT NULL)
        *   `description` (TEXT, NULL)
        *   `director` (VARCHAR(100), NULL)
        *   `cast` (TEXT, NULL) -- Comma-separated or JSON
        *   `genre` (VARCHAR(100), NULL) -- Comma-separated or link to a genres table
        *   `duration_minutes` (INT, NULL)
        *   `release_date` (DATE, NULL)
        *   `poster_image_path` (VARCHAR(255), NULL) -- Path relative to `uploads/posters/`
        *   `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
        *   `updated_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

    *   **`theatres` table (Simplified - assuming one cinema complex with multiple screens):**
        *   `id` (INT, PK, AUTO_INCREMENT)
        *   `name` (VARCHAR(100), NOT NULL) -- e.g., "Screen 1", "Screen 2"
        *   `capacity` (INT, NOT NULL) -- Total seats in this screen
        *   `seat_layout` (JSON, NULL) -- Optional: to store seat arrangement e.g., `{"rows": 10, "cols": 15, "disabled_seats": ["A1", "C5"]}`

    *   **`showtimes` table:**
        *   `id` (INT, PK, AUTO_INCREMENT)
        *   `movie_id` (INT, FK references `movies(id)`)
        *   `theatre_id` (INT, FK references `theatres(id)`) -- Or screen_id
        *   `show_datetime` (DATETIME, NOT NULL)
        *   `price_per_seat` (DECIMAL(10, 2), NOT NULL)
        *   `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

    *   **`bookings` table:**
        *   `id` (INT, PK, AUTO_INCREMENT)
        *   `user_id` (INT, FK references `users(id)`)
        *   `showtime_id` (INT, FK references `showtimes(id)`)
        *   `num_seats` (INT, NOT NULL)
        *   `booked_seats` (TEXT, NOT NULL) -- Comma-separated list of seat identifiers (e.g., "A1,A2,B5")
        *   `total_amount` (DECIMAL(10, 2), NOT NULL)
        *   `booking_timestamp` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
        *   `payment_status` (ENUM('pending', 'confirmed', 'failed'), DEFAULT 'pending') -- For simulated payment

**II. Core PHP Files & Logic**

1.  **`includes/db_connect.php`:**
    ```php
    <?php
    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'your_db_user');
    define('DB_PASSWORD', 'your_db_password');
    define('DB_NAME', 'movie_booking_db');

    // Using PDO for better security (prevents SQL injection with prepared statements)
    try {
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e){
        die("ERROR: Could not connect. " . $e->getMessage());
    }
    ?>
    ```

2.  **`includes/functions.php`:** (Common functions)
    ```php
    <?php
    session_start(); // Start session at the beginning of common functions

    // Function to sanitize output (prevent XSS)
    function escape($html) {
        return htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    // Function to check if user is logged in
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    // Function to check if user is an admin
    function isAdmin() {
        return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }

    // Redirect function
    function redirect($url) {
        header("Location: " . $url);
        exit();
    }

    // CSRF Token Generation
    function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // CSRF Token Validation
    function validateCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    ?>
    ```

3.  **`includes/header.php` (Example):**
    ```php
    <?php require_once __DIR__ . '/functions.php'; // Ensure session_start() is called ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Movie Magic</title>
        <link rel="stylesheet" href="/movie_ticket_booking/css/style.css"> <?php // Adjust path ?>
    </head>
    <body>
        <header>
            <h1><a href="/movie_ticket_booking/index.php">Movie Magic</a></h1>
            <nav>
                <a href="/movie_ticket_booking/index.php">Home</a>
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <a href="/movie_ticket_booking/admin/index.php">Admin Dashboard</a>
                    <?php else: ?>
                        <a href="/movie_ticket_booking/user/my_bookings.php">My Bookings</a>
                    <?php endif; ?>
                    <a href="/movie_ticket_booking/auth/logout.php">Logout (<?php echo escape($_SESSION['username']); ?>)</a>
                <?php else: ?>
                    <a href="/movie_ticket_booking/auth/login.php">Login</a>
                    <a href="/movie_ticket_booking/auth/register.php">Register</a>
                <?php endif; ?>
            </nav>
        </header>
        <main>
    ```

4.  **`includes/footer.php` (Example):**
    ```php
        </main>
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Movie Magic. All rights reserved.</p>
        </footer>
        <script src="/movie_ticket_booking/js/script.js"></script> <?php // Adjust path ?>
    </body>
    </html>
    <?php
    // Close PDO connection if it was opened in the script
    // if (isset($pdo)) { $pdo = null; } // Better to manage connection per script
    ?>
    ```

**III. Key Feature Implementation Steps**

1.  **User Authentication (Basic):**
    *   **`auth/register.php`:**
        *   HTML form (username, email, password, confirm password).
        *   Include CSRF token field.
        *   PHP:
            *   Validate inputs (not empty, email format, password match, password strength).
            *   Check if username/email already exists.
            *   Hash password: `password_hash($password, PASSWORD_DEFAULT)`.
            *   INSERT into `users` table using prepared statements.
            *   Redirect to login.
    *   **`auth/login.php`:**
        *   HTML form (username/email, password).
        *   Include CSRF token field.
        *   PHP:
            *   Validate inputs.
            *   SELECT user from `users` based on username/email.
            *   Verify password: `password_verify($password, $user['password_hash'])`.
            *   If successful, set session variables: `$_SESSION['user_id']`, `$_SESSION['username']`, `$_SESSION['user_role']`.
            *   Regenerate session ID: `session_regenerate_id(true)`.
            *   Redirect to user dashboard or admin dashboard based on role.
    *   **`auth/logout.php`:**
        *   `session_start();`
        *   `$_SESSION = array();`
        *   `session_destroy();`
        *   Redirect to homepage or login page.

2.  **Admin Panel - Movie Management (CRUD):**
    *   **`admin/includes/auth_check_admin.php`:**
        ```php
        <?php
        require_once __DIR__ . '/../../includes/functions.php'; // Adjust path
        if (!isAdmin()) {
            redirect('/movie_ticket_booking/auth/login.php?error=admin_required'); // Redirect to login
        }
        ?>
        ```
    *   Include `auth_check_admin.php` at the top of all admin pages.
    *   **`admin/manage_movies.php`:**
        *   List all movies from `movies` table.
        *   Links to add, edit, delete movies.
    *   **`admin/add_movie.php`:**
        *   Form for movie details (title, description, director, poster upload, etc.).
        *   CSRF token.
        *   PHP:
            *   Validate inputs.
            *   Handle file upload securely (check type, size, rename, move to `uploads/posters/`).
            *   INSERT into `movies` table (prepared statements).
    *   **`admin/edit_movie.php?id=<movie_id>`:**
        *   Fetch movie details by `id`.
        *   Pre-fill form.
        *   CSRF token.
        *   PHP: Handle updates, including new poster upload if provided.
    *   **(Delete functionality):** Typically a POST request to a script that deletes the movie (and its poster file), often confirmed with JavaScript. Ensure CSRF protection.

3.  **Admin Panel - Showtime Management (CRUD):**
    *   Similar structure to Movie Management.
    *   **`admin/manage_showtimes.php`:** List showtimes, link to add/edit/delete.
    *   **`admin/add_showtime.php`:**
        *   Form: Dropdown for movies (SELECT `id`, `title` FROM `movies`), dropdown for theatres/screens, datetime picker, price.
        *   CSRF token.
        *   PHP: Validate, INSERT into `showtimes`.
    *   **`admin/edit_showtime.php?id=<showtime_id>`:** Similar to edit movie.

4.  **User - Movie Listing & Search:**
    *   **`user/index.php` (or main `index.php`):**
        *   Display a grid/list of current movies (fetch from `movies` table).
        *   Each movie links to `user/movie_details.php?id=<movie_id>`.
        *   Search bar: Form with `method="GET"`.
        *   PHP: If `$_GET['search_query']` is set, modify SQL query: `SELECT * FROM movies WHERE title LIKE ? OR genre LIKE ?`. Use `"%{$search_query}%"` with prepared statements.
    *   **`user/movie_details.php?id=<movie_id>`:**
        *   Display movie details (poster, description, cast, etc.).
        *   List available showtimes for this movie (SELECT from `showtimes` WHERE `movie_id` = ? AND `show_datetime` > NOW() ORDER BY `show_datetime`).
        *   Each showtime has a "Book Now" button linking to `user/select_seats.php?showtime_id=<showtime_id>`.

5.  **Booking System:**
    *   **`user/select_seats.php?showtime_id=<showtime_id>`:**
        *   Requires user login (`auth_check_user.php`).
        *   Fetch showtime details, theatre capacity/layout.
        *   Fetch already booked seats for this showtime (SELECT `booked_seats` FROM `bookings` WHERE `showtime_id` = ? AND `payment_status` = 'confirmed'). Parse the comma-separated strings.
        *   Display a seat map (HTML table, divs, or SVG). Clickable seats.
        *   JavaScript to handle seat selection, update selected count, and total price.
        *   Hidden form fields for `showtime_id`, selected seats (comma-separated string), number of seats, total price.
        *   CSRF token.
        *   Submit to `user/book_ticket.php`.
    *   **`user/book_ticket.php` (Handles form submission from `select_seats.php`):**
        *   Requires user login.
        *   Validate CSRF token.
        *   Validate inputs (showtime_id, selected seats exist, seats are not already booked - re-check against DB to prevent race conditions).
        *   INSERT into `bookings` with `payment_status = 'pending'`. Get the `booking_id`.
        *   Redirect to `user/payment_simulation.php?booking_id=<booking_id>`.
    *   **`user/payment_simulation.php?booking_id=<booking_id>`:**
        *   Display booking summary.
        *   "Simulate Payment Success" and "Simulate Payment Failure" buttons.
        *   These buttons submit a form (POST) to a script (could be same page with `if ($_SERVER['REQUEST_METHOD'] === 'POST')`) that updates `payment_status` in `bookings` table for the given `booking_id`.
        *   If success, redirect to `user/booking_confirmation.php?booking_id=<booking_id>`.
        *   If failure, show an error message.
    *   **`user/booking_confirmation.php?booking_id=<booking_id>`:**
        *   Display "Booking Confirmed!" and booking details.
    *   **`user/my_bookings.php`:**
        *   Requires user login.
        *   SELECT bookings for the logged-in user from `bookings` table JOIN with `showtimes` and `movies`. Display them.

6.  **Security Measures:**
    *   **SQL Injection:**
        *   Use PDO or MySQLi with **prepared statements** for ALL database queries.
        *   **Never** directly embed user input into SQL strings.
    *   **XSS (Cross-Site Scripting):**
        *   Escape ALL user-generated content before displaying it in HTML: `echo escape($user_comment);` (using the `escape()` function defined earlier).
        *   Set `Content-Security-Policy` HTTP header (via `.htaccess` or PHP `header()` function) to restrict sources of scripts, styles, images.
    *   **CSRF (Cross-Site Request Forgery):**
        *   Generate a unique, random token for each user session (`$_SESSION['csrf_token']`).
        *   Include this token as a hidden field in all state-changing forms (POST requests like login, registration, booking, admin actions).
        *   On the server-side, verify that the submitted token matches the one in the session before processing the request.
        *   Use the `generateCsrfToken()` and `validateCsrfToken()` functions.
    *   **Password Security:**
        *   Use `password_hash()` for storing passwords.
        *   Use `password_verify()` for checking passwords.
    *   **Session Security:**
        *   `session_regenerate_id(true)` after login and any privilege level change.
        *   Configure session cookies to be `HttpOnly` and `Secure` (for HTTPS):
            ```php
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', 1);
            }
            ```
            Place this before `session_start()`.
    *   **File Uploads:**
        *   Validate file type (check MIME type server-side, not just extension).
        *   Validate file size.
        *   Rename uploaded files to something random/unique to prevent directory traversal or execution issues.
        *   Store uploads outside the web root if possible. If not, use `.htaccess` in the `uploads` directory to prevent direct execution of PHP or other scripts:
            ```apache
            # In uploads/.htaccess
            <FilesMatch "\.(php|phtml|php3|php4|php5|pl|py|cgi|sh|exe)$">
                Order Allow,Deny
                Deny from all
            </FilesMatch>
            # Or more simply for just denying PHP
            php_flag engine off
            ```
    *   **Input Validation:**
        *   Validate ALL incoming data (GET, POST, COOKIE) on the server-side, even if you have client-side validation. Check for expected types, lengths, formats, ranges.

7.  **Google OAuth:**
    *   This is the most complex part if doing "pure PHP" without Composer and Google's client library.
    *   **Recommendation:** Use the Google API Client Library for PHP (installable via Composer). If "pure PHP" means no Composer, you'll have to manually implement OAuth 2.0 flows, which is error-prone and not recommended.
    *   **If you must do it manually (Simplified Overview):**
        1.  **Google Developer Console:**
            *   Create a project.
            *   Enable the "Google People API" (or relevant API for user info).
            *   Create OAuth 2.0 credentials (Client ID and Client Secret).
            *   Set Authorized redirect URIs (e.g., `http://localhost/movie_ticket_booking/auth/google_oauth.php`).
        2.  **Login with Google Button:**
            *   Link points to Google's OAuth endpoint with your `client_id`, `redirect_uri`, `response_type=code`, and `scope` (e.g., `email profile openid`).
        3.  **`auth/google_oauth.php` (Redirect URI):**
            *   Google redirects here with a `code` parameter.
            *   Exchange this `code` for an access token by making a POST request to Google's token endpoint (passing `code`, `client_id`, `client_secret`, `redirect_uri`, `grant_type=authorization_code`).
            *   Use the access token to fetch user profile information from a Google API endpoint (e.g., `https://www.googleapis.com/oauth2/v3/userinfo`).
            *   Check if `google_id` exists in your `users` table.
                *   If yes, log them in (set session variables).
                *   If no, create a new user (you might need to ask for a username or generate one) and then log them in. Ensure their email is also stored.
    *   **Using the Library (Preferred):**
        1.  Install: `composer require google/apiclient:^2.0`
        2.  Follow Google's PHP Quickstart guides. It simplifies token handling and API calls significantly.

**IV. Development Tips**

*   **Start Simple:** Get basic user registration/login working first. Then movie display. Then admin movie CRUD. Incrementally add features.
*   **PDO & Prepared Statements:** Use them from day one for all DB interactions.
*   **Error Reporting:** `error_reporting(E_ALL); ini_set('display_errors', 1);` during development. Turn off `display_errors` in production.
*   **Version Control:** Use Git.
*   **Test Thoroughly:** Test all features, especially security aspects, form submissions, and edge cases.
*   **Modularity:** Keep functions and includes organized.
*   **Comments:** Write comments to explain complex parts of your code.

This is a substantial project, but breaking it down like this should make it achievable. Good luck!