# Security Report: Movie Ticket Booking System

**Date:** June 06, 2024

**Our Team:** A dedicated team of three members.

---

## 1. Introduction to Security Measures

In developing the Movie Ticket Booking System, our team has placed a strong emphasis on implementing robust security measures to protect user data and ensure the integrity of the application. This report details the strategies and specific implementations used to mitigate common web vulnerabilities such as SQL Injection, Cross-Site Scripting (XSS), Cross-Site Request Forgery (CSRF), and to ensure secure session management.

---

## 2. SQL Injection Protection

**Strategy:**

Our primary defense against SQL Injection attacks is the consistent use of **PDO (PHP Data Objects) with prepared statements** for all database interactions. Prepared statements separate SQL query logic from user-supplied data, ensuring that user input is treated as data values rather than executable SQL code. This approach automatically escapes malicious characters, effectively preventing injection attempts.

**Implementation Examples:**

Wherever user input is used in a database query, we use placeholders (`:param` or `?`) and bind parameters. This ensures that even if a user attempts to inject malicious SQL, it will be treated as a literal string within the query.

*   **Example from `includes/db_connect.php` (Connection setup):**
    The PDO connection is configured to throw exceptions on errors, which helps in debugging and prevents silent failures.
    ```php
    try {
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Could not connect. " . $e->getMessage());
    }
    ```

*   **Example from `auth/login.php` (User authentication):**
    When querying for user credentials, parameters are bound to prevent injection in the login identifier or password.
    ```php
    // ... (in login.php)
    $sql = "SELECT id, username, email, password_hash, role FROM users WHERE username = :identifier OR email = :identifier";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':identifier', $login_identifier);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    ```

*   **Example from `user/profile.php` (Updating user profile):**
    All user-editable fields are updated using prepared statements to prevent injection during profile modifications.
    ```php
    // ... (in user/profile.php)
    $stmt = $pdo->prepare('UPDATE users SET username = ?, phone = ?, address = ?, profile_picture = ? WHERE id = ?');
    $stmt->execute([$new_username, $new_phone, $new_address, $new_profile_picture, $user_id]);
    ```

---

## 3. Cross-Site Scripting (XSS) Mitigation

**Strategy:**

To prevent XSS attacks, where malicious scripts are injected into web pages and executed in the user's browser, we meticulously **sanitize all user inputs before storing them** and **escape all outputs before displaying them** in the HTML. This ensures that any user-supplied content is treated as plain text rather than executable code.

**Implementation Examples:**

We utilize a custom `escape()` function, defined in `includes/functions.php`, which wraps PHP's built-in `htmlspecialchars()` function. This function is consistently applied to all data retrieved from the database or user input before it is rendered on a web page.

*   **`escape()` function in `includes/functions.php`:**
    ```php
    // Function to sanitize output (prevent XSS)
    function escape($html)
    {
        return htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    ```

*   **Example of usage in `auth/login.php` (displaying error messages):**
    ```php
    // ... (in login.php, when displaying an error)
    <?php foreach ($errors as $error): ?>
        <li><?php echo escape($error); ?></li>
    <?php endforeach; ?>
    ```

*   **Example of usage in `user/movie_details.php` (displaying movie title):**
    ```php
    // ... (in user/movie_details.php)
    <h1 class="text-4xl md:text-5xl font-extrabold mb-4 drop-shadow-lg">
        <?php echo escape($movie['title']); ?>
    </h1>
    ```

---

## 4. Cross-Site Request Forgery (CSRF) Protection

**Strategy:**

To protect against CSRF attacks, where an attacker tricks a victim into submitting a malicious request, we implement a **CSRF token mechanism**. A unique, unpredictable token is generated for each user session, embedded in all forms, and validated upon form submission. If the submitted token does not match the token stored in the user's session, the request is rejected.

**Implementation Examples:**

*   **Token Generation in `includes/functions.php`:**
    The `generateCsrfToken()` function creates a secure, random token and stores it in the session.
    ```php
    // CSRF Token Generation
    function generateCsrfToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    ```

*   **Token Validation in `includes/functions.php`:**
    The `validateCsrfToken()` function compares the submitted token with the session token.
    ```php
    // CSRF Token Validation
    function validateCsrfToken($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    ```

*   **Embedding Token in Forms (Example from `auth/login.php`):**
    All forms include a hidden input field containing the CSRF token.
    ```html
    <!-- ... (in login.php form) -->
    <form action="login.php" method="POST" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?php echo escape($csrf_token); ?>">
        <!-- ... form fields ... -->
    </form>
    ```

*   **Validation on Form Submission (Example from `auth/login.php`):**
    Before processing POST requests, the CSRF token is validated.
    ```php
    // ... (in login.php POST handling)
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = "CSRF token validation failed. Please try again.";
    } else {
        // Process form data
    }
    ```

---

## 5. Session Security

**Strategy:**

Robust session management is crucial for maintaining the integrity of user sessions and preventing unauthorized access. Our approach includes secure session creation, regeneration, and destruction, along with storing minimal sensitive information directly in the session.

**Implementation Details:**

*   **Session Start:** `session_start()` is called at the very beginning of `includes/functions.php` to ensure session variables are available across pages.
*   **Session Regeneration on Login:** Upon successful login, `session_regenerate_id(true)` is used. This generates a new session ID and deletes the old one, preventing session fixation attacks.
    ```php
    // Example from auth/login.php after successful login
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_role'] = $user['role'];
    ```
*   **Minimal Session Data:** Only essential user identification and authorization data (`user_id`, `username`, `user_role`) are stored in the session. Sensitive information like passwords or extensive profile data is retrieved from the database as needed.
*   **Session Destruction on Logout:** The `logout.php` script properly destroys the session, unsetting all session variables and deleting the session cookie, ensuring the user is fully logged out.
    ```php
    // Example from auth/logout.php
    session_start();
    $_SESSION = array(); // Clear all session variables
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy(); // Destroy the session
    redirect('login.php');
    ```
*   **HTTP-Only Cookies:** While not explicitly set in `setcookie` calls within application code, it's assumed that the `php.ini` configuration for `session.cookie_httponly` is set to `true` to prevent client-side scripts from accessing session cookies, further mitigating XSS risks. (This is a server configuration, not directly in application code, but critical for session security).
*   **Secure Cookies (HTTPS):** Similarly, `session.cookie_secure` should be set to `true` in `php.ini` in a production environment to ensure session cookies are only sent over HTTPS connections, protecting against eavesdropping. Our `.env` configuration includes `SMTP_SECURE`, hinting at an awareness of secure connections for email, which extends to general application security practices. 