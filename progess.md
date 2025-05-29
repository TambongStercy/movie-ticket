# Movie Ticket Booking System - Project Progress

## Phase 1: Project Setup & Database Design

1.  **Project Initialization:**
    *   Defined project scope: A Movie Ticket Booking System using pure PHP and MySQL.
    *   Agreed on initial development steps based on `project.md`.

2.  **Directory Structure Setup:**
    *   Created the main project directory: `movie_ticket_booking`.
    *   Established the following sub-directory structure within `movie_ticket_booking/`:
        *   `admin/`
            *   `includes/`
        *   `auth/`
        *   `css/`
        *   `images/`
        *   `includes/`
        *   `js/`
        *   `user/`
            *   `includes/`
        *   `uploads/`
            *   `posters/`
    *   *(Correction Note: The main `includes/` folder containing `db_connect.php`, `functions.php`, `header.php`, and `footer.php` was initially misplaced in the parent directory and has now been moved to its correct location at `movie_ticket_booking/includes/`.)*

3.  **Database Schema Definition:***
    *   Provided SQL Data Definition Language (DDL) statements for the following tables:
        *   `users`: Stores user information, credentials, and roles.
        *   `movies`: Stores details about movies (title, description, poster, etc.).
        *   `theatres`: Stores information about cinema screens/theatres and their capacity.
        *   `showtimes`: Links movies to theatres at specific times with pricing.
        *   `bookings`: Records user bookings, selected seats, and payment status.
    *   Database name: `movie_booking_db`.
    *   Currency for pricing: FCFA.
    *   Commands: 
        * **First, create the database itself**: 
        CREATE DATABASE movie_booking_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
        * **Use table**:
        USE movie_booking_db;
        * `create users table`:
        CREATE TABLE `users` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `username` VARCHAR(50) UNIQUE NOT NULL,
        `email` VARCHAR(100) UNIQUE NOT NULL,
        `password_hash` VARCHAR(255) NOT NULL,
        `role` ENUM('user', 'admin') DEFAULT 'user',
        `google_id` VARCHAR(255) NULL UNIQUE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        * **create movies table**:
        CREATE TABLE `movies` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT NULL,
        `director` VARCHAR(100) NULL,
        `cast` TEXT NULL,
        `genre` VARCHAR(100) NULL,
        `duration_minutes` INT NULL,
        `release_date` DATE NULL,
        `poster_image_path` VARCHAR(255) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );
        * **create theatres table**:
        CREATE TABLE `theatres` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `name` VARCHAR(100) NOT NULL,
        `capacity` INT NOT NULL,
        `seat_layout` JSON NULL
        );


        * **create showtimes table**:
        CREATE TABLE `showtimes` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `movie_id` INT,
        `theatre_id` INT,
        `show_datetime` DATETIME NOT NULL,
        `price_per_seat` DECIMAL(10, 2) NOT NULL, -- Assuming FCFA, precision 2 for cents/francs
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`movie_id`) REFERENCES `movies`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`theatre_id`) REFERENCES `theatres`(`id`) ON DELETE CASCADE
        );


        * **create bookings table**:
        CREATE TABLE `bookings` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `user_id` INT,
        `showtime_id` INT,
        `num_seats` INT NOT NULL,
        `booked_seats` TEXT NOT NULL,
        `total_amount` DECIMAL(10, 2) NOT NULL, -- Assuming FCFA
        `booking_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `payment_status` ENUM('pending', 'confirmed', 'failed') DEFAULT 'pending',
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL, -- Or CASCADE if preferred
        FOREIGN KEY (`showtime_id`) REFERENCES `showtimes`(`id`) ON DELETE CASCADE
        );
     


## Phase 2: Core PHP Files & Logic

1.  **Database Connection (`movie_ticket_booking/includes/db_connect.php`):**
    *   Created the `db_connect.php` file within the project's `includes` directory.
    *   Uses PDO for database interactions.
    *   Includes constants for DB server, username, password, and database name.
    *   **Action Required by User:** Update placeholder `DB_USERNAME` and `DB_PASSWORD` with actual credentials.

2.  **Common Helper Functions (`movie_ticket_booking/includes/functions.php`):**
    *   Created `functions.php` within the project's `includes` directory to store reusable utility functions.
    *   Includes:
        *   `session_start()`: Initiates session handling.
        *   `escape()`: Sanitizes output to prevent XSS.
        *   `isLoggedIn()`: Checks if a user is currently logged in.
        *   `isAdmin()`: Checks if the logged-in user has admin privileges.
        *   `redirect()`: Handles page redirections.
        *   `generateCsrfToken()`: Creates a CSRF token for form security.
        *   `validateCsrfToken()`: Validates a submitted CSRF token.

3.  **Global Layout Files (`movie_ticket_booking/includes/header.php`, `movie_ticket_booking/includes/footer.php`):**
    *   Created `header.php` within the project's `includes` directory:
        *   Includes `functions.php` (relative path `__DIR__ . '/functions.php'` remains correct).
        *   Includes the Tailwind CSS CDN link in the `<head>`.
        *   Basic Tailwind classes applied to header and navigation.
        *   Sets up HTML document structure (head, body, header).
        *   Contains main navigation links, dynamically changing based on login status and user role (user/admin).
        *   Links to `/movie_ticket_booking/css/style.css`.
    *   Created `footer.php` within the project's `includes` directory:
        *   Closes the main content area and HTML structure.
        *   Includes a copyright notice.
        *   Links to `/movie_ticket_booking/js/script.js`.
        *   Contains a commented-out placeholder for closing PDO connection (better managed per script).

## Phase 3: Key Feature Implementation

1.  **User Authentication - Registration (`auth/register.php`):**
    *   Created `register.php` to handle new user sign-ups.
    *   **Functionality:**
        *   Displays a registration form (username, email, password, confirm password).
        *   Includes CSRF token protection.
        *   Performs server-side validation:
            *   Checks for empty fields.
            *   Validates email format.
            *   Ensures password and confirm password match.
            *   Enforces a minimum password length of 8 characters.
        *   Checks the database to prevent duplicate usernames or emails.
        *   Hashes passwords using `password_hash()`.
        *   Inserts new user data into the `users` table with a default role of 'user'.
        *   Redirects to `login.php` on successful registration, storing a success message in the session.
        *   Displays validation errors or database errors to the user.
    *   **Dependencies:** `includes/db_connect.php`, `includes/functions.php`, `includes/header.php`, `includes/footer.php`.
    *   **Styling:** Uses Tailwind CSS classes for layout and appearance. The inline `<style>` block has been removed.

2.  **User Authentication - Login (`auth/login.php`):**
    *   Created `login.php` to handle user authentication.
    *   **Functionality:**
        *   Redirects logged-in users to their respective dashboards (admin/user).
        *   Displays a login form (username/email, password).
        *   Includes CSRF token protection.
        *   Validates that input fields are not empty.
        *   Retrieves user details from the `users` table by username or email.
        *   Verifies the provided password against the stored hash using `password_verify()`.
        *   On successful login:
            *   Regenerates session ID using `session_regenerate_id(true)`.
            *   Stores `user_id`, `username`, and `user_role` in the session.
            *   Redirects users to either the admin dashboard (`../admin/index.php`) or user dashboard (`../user/index.php`) based on their role.
        *   Displays a success message if redirected from a successful registration.
        *   Shows appropriate error messages for failed login attempts or database issues.
    *   **Dependencies:** `includes/db_connect.php`, `includes/functions.php`, `includes/header.php`, `includes/footer.php`.
    *   **Styling:** Uses Tailwind CSS classes for layout and appearance.

3.  **User Authentication - Logout (`auth/logout.php`):**
    *   Created `logout.php` to handle user session termination.
    *   **Functionality:**
        *   Includes `functions.php` (which starts the session).
        *   Clears all session variables by re-initializing `$_SESSION` as an empty array.
        *   Deletes the session cookie from the browser.
        *   Destroys the session on the server.
        *   Redirects the user to `login.php`.
    *   **Dependencies:** `includes/functions.php`.

4.  **Google OAuth - Setup & Library Installation:**
    *   User opted to implement Google OAuth using Composer and the Google API Client Library.
    *   User provided an existing `vendor` folder and `composer.json` file.
    *   Executed `composer install` in the `movie_ticket_booking` directory.
    *   This installed/updated necessary dependencies, including `google/apiclient`, and generated autoload files.
    *   **Next Steps:** Configure Google Developer Console credentials, set up `.env` file, and implement OAuth flow.

5.  **Google OAuth - Environment Setup:**
    *   Instructed user to create a `.env` file in the project root (`movie_ticket_booking/`) for storing `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI`.
    *   The specified `GOOGLE_REDIRECT_URI` is `http://localhost:3000/movie_ticket_booking/auth/google_oauth_callback.php` based on user input.
    *   Created a `.gitignore` file to exclude `/vendor/` and `.env` from version control.
    *   Modified `includes/functions.php` to:
        *   Include Composer's `vendor/autoload.php`.
        *   Initialize `Dotenv\Dotenv` to load variables from the `.env` file into `$_ENV` and `$_SERVER`.
    *   **Action Required by User:** Manually create `.env` with actual credentials and confirm Redirect URI in Google Console.

6.  **Google OAuth - Login Initiation (`auth/google_login.php`):**
    *   Created `google_login.php`.
    *   **Functionality:**
        *   Initializes the `Google_Client`.
        *   Sets Client ID, Client Secret, and Redirect URI from environment variables (`$_ENV`).
        *   Defines scopes: `email`, `profile`, `openid`.
        *   Generates the Google Authentication URL.
        *   Redirects the user to Google's OAuth server.
    *   **Dependencies:** `includes/functions.php` (for autoloader, .env, session), `Google_Client` from the installed library.

7.  **Google OAuth - Callback Handling (`auth/google_oauth_callback.php`):**
    *   Created `google_oauth_callback.php` to handle the redirect from Google.
    *   **Functionality:**
        *   Initializes the `Google_Client`.
        *   If an authorization `code` is received:
            *   Exchanges the code for an access token.
            *   Sets the access token for the client.
            *   Fetches user profile information (Google ID, email, name) using `Google_Service_Oauth2`.
            *   Checks if a user exists with the `google_id`.
            *   If not, checks if a user exists with the `email` (to link accounts).
                *   If email exists, updates the existing user record with the `google_id`.
            *   If no existing user is found by `google_id` or `email`, creates a new user account:
                *   Generates a unique username based on the email.
                *   Stores a placeholder password hash (as auth is via Google).
                *   Stores the `google_id`.
            *   If user is found or successfully created, logs the user in:
                *   Regenerates session ID.
                *   Sets session variables (`user_id`, `username`, `user_role`).
                *   Redirects to the appropriate dashboard (admin/user).
        *   Handles errors during token exchange or profile fetching.
        *   Displays errors if the Google login fails.
    *   **Dependencies:** `includes/db_connect.php`, `includes/functions.php`, `Google_Client`, `Google_Service_Oauth2`.

8.  **Google OAuth - UI Integration:**
    *   Added a "Login with Google" button to `auth/login.php`.
    *   This button links to `auth/google_login.php` to start the OAuth flow.
    *   Styled the button using Tailwind CSS.

## Phase 4: Admin Panel - Core & Movie Management

1.  **Admin Area Authentication Check (`admin/includes/auth_check_admin.php`):**
    *   Created `auth_check_admin.php`.
    *   **Functionality:**
        *   Includes `includes/functions.php` (which handles session start and provides `isAdmin()`).
        *   Checks if the current user is an administrator using the `isAdmin()` function.
        *   If the user is not an admin (or not logged in), redirects them to the login page (`/auth/login.php?error=admin_required`).
        *   This script is intended to be included at the top of all pages within the `admin/` directory to protect them.
    *   **Dependencies:** `includes/functions.php`.

2.  **Custom CSS File (`css/style.css`):**
    *   Created an empty `css/style.css` file.
    *   Linked in `includes/header.php` to allow for custom CSS rules that complement or override Tailwind CSS if needed.

3.  **Placeholder Index Pages:**
    *   Created main `movie_ticket_booking/index.php`:
        *   Redirects logged-in users to their respective dashboards (`admin/index.php` or `user/index.php`).
        *   Redirects non-logged-in users to `auth/login.php`.
    *   Created `movie_ticket_booking/user/includes/auth_check_user.php`:
        *   Ensures only logged-in users can access pages that include it. Redirects to login if not.
    *   Created `movie_ticket_booking/user/index.php`:
        *   Protected by `auth_check_user.php`.
        *   Displays a welcome message and a placeholder for the user dashboard and movie listings.
        *   Styled with Tailwind CSS.
    *   Created `movie_ticket_booking/admin/index.php`:
        *   Protected by `admin/includes/auth_check_admin.php`.
        *   Displays a welcome message and placeholder cards/links for admin functionalities (Manage Movies, Manage Showtimes, View Bookings).
        *   Styled with Tailwind CSS.

4.  **Admin - Movie Listing (`admin/manage_movies.php`):**
    *   Created `manage_movies.php` as the main page for movie administration.
    *   **Functionality:**
        *   Protected by `admin/includes/auth_check_admin.php`.
        *   Fetches all movies from the `movies` table, ordered by release date and title.
        *   Displays movies in a responsive HTML table using Tailwind CSS.
        *   Table columns include: Poster, Title, Director, Release Date, Duration.
        *   Movie posters are displayed if available (path: `/movie_ticket_booking/uploads/posters/`).
        *   Includes an "Add New Movie" button linking to `add_movie.php`.
        *   For each movie, provides "Edit" and "Delete" icon buttons:
            *   Edit links to `edit_movie.php?id=<movie_id>`.
            *   Delete links to `delete_movie.php?id=<movie_id>` and includes a JavaScript `confirm()` dialog.
        *   Displays session-based success or error messages (e.g., after a CRUD operation).
        *   Includes Font Awesome CDN for icons (e.g., add, edit, delete).
    *   **Dependencies:** `admin/includes/auth_check_admin.php`, `includes/db_connect.php`, `includes/header.php`, `includes/footer.php`.

5.  **Admin - Edit Movie (`admin/edit_movie.php`):**
    *   Created `edit_movie.php` for modifying existing movie details.
    *   **Functionality:**
        *   Protected by `admin/includes/auth_check_admin.php`.
        *   Requires a movie `id` as a GET parameter.
        *   Fetches the specified movie's data from the `movies` table.
        *   Displays a form pre-filled with the movie's current details (title, description, director, cast, genre, duration, release date, poster).
        *   Allows updating all fields, including uploading a new poster image.
        *   If a new poster is uploaded, the old one (if it exists) is deleted from the server.
        *   Uses CSRF token for form submission security.
        *   Performs server-side validation for required fields (title, duration, release date) and file uploads (type, size).
        *   Updates the movie record in the `movies` table using prepared statements.
        *   Redirects to `manage_movies.php` with a success message upon successful update.
        *   Displays error messages if validation fails or database issues occur.
    *   **Dependencies:** `admin/includes/auth_check_admin.php`, `includes/db_connect.php`, `includes/functions.php`, `includes/header.php`, `includes/footer.php`.

6.  **Admin Panel - Movie Management (CRUD):** *(DONE)*
    *   All CRUD operations for movies (add, edit, delete, list) are implemented and functional in the admin panel.

7.  **Admin Panel - Theatre/Screen Management (Simplified - if multiple screens are needed):** *(DONE)*
    *   All CRUD operations for theatres/screens (add, edit, delete, list) are implemented and functional in the admin panel.

## Phase 5: Future TODO / Remaining Tasks

**(Based on `project.md` and current progress)**

1.  **Admin Panel - Movie Management (CRUD):**
    *   `admin/add_movie.php`: Form and logic for adding new movies, including poster upload.
    *   `admin/delete_movie.php` (or logic within `manage_movies.php`): Script to handle movie deletion (including poster file) with CSRF protection.

2.  **Admin Panel - Theatre/Screen Management (Simplified - if multiple screens are needed):**
    *   `admin/manage_theatres.php` (Optional, if `theatres` table implies more than just a list of screens for one complex)
    *   `admin/add_theatre.php` (Optional)
    *   `admin/edit_theatre.php` (Optional)
    *   *(Note: The current `theatres` table design is simple. If it's just a few screens in one complex, full CRUD might be overkill and could be managed directly in the database or a simpler interface if showtimes always specify one of these.)*

3.  **Admin Panel - Showtime Management (CRUD):**
    *   `admin/manage_showtimes.php`: List showtimes, link to add/edit/delete.
    *   `admin/add_showtime.php`: Form with movie dropdown, theatre/screen dropdown, datetime picker, price (FCFA).
    *   `admin/edit_showtime.php`: Form to edit existing showtime details.
    *   `admin/delete_showtime.php` (or logic within `manage_showtimes.php`): Script for deleting showtimes.

4.  **Admin Panel - View Bookings:**
    *   `admin/view_bookings.php`: Display a list of all bookings, possibly with filtering options.

5.  **User - Movie Listing & Details:**
    *   Update `user/index.php`: Display a grid/list of current movies fetched from the `movies` table. Implement search/filter functionality (by title, genre).
    *   `user/movie_details.php`: **DONE**. Displays detailed movie information (poster, description, cast, etc.), category, max age, and lists available showtimes for that movie (fetched from `showtimes` table, ensuring `show_datetime` > NOW()). Each showtime links to seat selection. **Users can now rate movies (1-5 stars + comment), see average rating, and view their own rating.**

6.  **User - Booking System:**
    *   `user/select_seats.php`: DONE. Requires login. Fetches showtime details, theatre layout (if `seat_layout` in `theatres` table is used, otherwise a generic grid). Displays available/booked seats. Handles seat selection (checkboxes). Submits selected seats, showtime ID, etc., to `book_ticket.php`.
    *   `user/book_ticket.php`: DONE. Requires login. Validates CSRF. Validates inputs (showtime exists, seats are available - re-check DB to prevent race conditions). Inserts into `bookings` with `payment_status = 'pending'` and `total_amount` in FCFA. Redirects to payment simulation.
    *   `user/payment_simulation.php`: DONE. Displays booking summary. Buttons for "Simulate Payment Success" and "Simulate Payment Failure". Updates `payment_status` in `bookings` table. Redirects to confirmation on success.
    *   `user/booking_confirmation.php`: DONE. Displays "Booking Confirmed!" and booking details (movie, showtime, seats, total amount in FCFA).
    *   `user/my_bookings.php`: DONE. Requires login. Lists all bookings for the logged-in user (join `bookings`, `showtimes`, `movies`).

7.  **Security Enhancements & Refinements:**
    *   **File Uploads:** Ensure robust validation (MIME type, size) and secure storage (e.g., `.htaccess` in `uploads/` to prevent script execution) for movie posters.
    *   **Session Security:** Ensure `HttpOnly` and `Secure` (for HTTPS) flags are set for session cookies (can be done in `functions.php` before `session_start()`).
    *   **Input Validation:** Rigorously validate ALL incoming data (GET, POST, COOKIEs) on the server-side for all forms and actions.
    *   **Error Handling:** Implement more user-friendly error pages or messages for critical errors (e.g., database connection failure after initial setup).
    *   **CSRF Protection:** Double-check all state-changing forms (POST requests) have and validate CSRF tokens.
    *   **XSS Prevention:** Ensure `escape()` function is used consistently for all user-generated content displayed in HTML.
    *   **Content Security Policy (CSP):** Consider adding CSP headers for enhanced XSS protection (via `.htaccess` or PHP `header()` calls).

8.  **JavaScript Enhancements (`js/script.js`):**
    *   Client-side validation for forms (complementing server-side validation).
    *   Dynamic seat selection map updates.
    *   AJAX for parts of the booking process or admin panel for smoother UX (optional).

9.  **CSS Styling (`css/style.css` & Tailwind):**
    *   Further refine UI/UX using Tailwind CSS and custom styles as needed for a polished look and feel.
    *   Ensure responsiveness across different screen sizes.

10. **`.htaccess` for URL Rewriting & Security:**
    *   Implement URL rewriting for cleaner URLs (e.g., `movie/1` instead of `movie_details.php?id=1`) - Optional, adds complexity.
    *   Add security headers (e.g., X-Content-Type-Options, X-Frame-Options, X-XSS-Protection).

11. **Testing:**
    *   Thoroughly test all functionalities: user flows, admin actions, form submissions, edge cases, security vulnerabilities.

## Progress Update (User Pages)

- Implemented all core user pages for movie details, seat selection, booking, payment simulation, booking confirmation, and booking history.
- All user pages use Tailwind CSS for a modern look, include proper header/footer, and are protected for logged-in users.
- Booking flow is fully functional: users can view movies, select showtimes, pick seats, book, simulate payment, and view their bookings.

## Progress Update (Image Path Fix)

- Fixed all movie poster image paths in user and admin pages to use /movie_ticket_booking/uploads/posters/{filename}.
- This resolves broken image issues and ensures posters display correctly everywhere in the app.

## Progress Update (Movie Ratings & Details Page)

- Implemented movie ratings: users can rate movies (1-5 stars) and leave a comment. Each user can rate a movie only once.
- The movie details page now displays the average rating, number of ratings, and the user's own rating (if any).
- The UI for rating is simple, user-friendly, and uses Tailwind CSS.
- All currency is displayed in FCFA as required.
- The movie details page also now displays the movie's category and maximum age (if set).

## Upcoming Features (In Progress)

- After booking confirmation, a PDF ticket with a unique transaction reference will be generated and emailed to the user.
- SMTP/email settings will be read from the .env file for sending the PDF ticket and other notifications.

10. **PDF Ticket Generation & Email Delivery:**
    *   After a successful booking (payment confirmed), generate a PDF ticket containing booking details and a unique transaction reference.
    *   Email the PDF ticket to the user using SMTP settings from the .env file.
    *   Add transaction reference to the bookings table if not already present.
