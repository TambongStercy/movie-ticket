# Project Report: Movie Ticket Booking System

**Date:** June 06, 2024

**Our Team:** A dedicated team of three members.

---

## 1. Introduction

This report details our efforts in developing the **Movie Ticket Booking System**, a comprehensive and modern web application designed for online movie ticket reservations. Built with PHP and MySQL, our system caters to both end-users and administrators, offering a seamless and secure experience for booking movie tickets. This project allowed our team to apply and deepen our understanding of full-stack web development principles, database management, and UI/UX design.

---

## 2. Project Overview

The Movie Ticket Booking System aims to provide a robust platform for managing movie listings, showtimes, and user bookings. It features a user-friendly interface inspired by popular streaming services, alongside a powerful admin panel for comprehensive system management. Our focus was on creating a responsive, secure, and feature-rich application.

### Key Features Implemented:

#### User-Facing Functionalities:
*   **Authentication & Access Control:**
    *   Secure user registration and login using email/password, with an added layer of **Two-Factor Authentication (2FA)** via OTP (One-Time Password) sent to the user's email for enhanced security.
    *   Seamless integration with **Google OAuth** for quick and easy social logins.
    *   A robust **password reset mechanism** for users who forget their credentials, involving email verification and a secure token.
*   **Movie Browsing & Information:**
    *   A Netflix-style dashboard featuring carousels, top-rated movies, and categorized listings (genres, popular).
    *   Efficient search and filtering capabilities by title, genre, and rating.
    *   Detailed movie information pages displaying descriptions, cast, director, genre, duration, release date, content category, maximum age ratings, and average user ratings. These pages dynamically display both **poster and cover images** for an immersive experience.
*   **Interactive Rating System:**
    *   Users can rate movies from 1 to 5 stars and add comments using an intuitive **interactive star rating UI**. The system also displays average ratings and user-specific ratings.
*   **Ticket Booking Process:**
    *   Showtime selection, with only future showtimes being displayed for relevance.
    *   An advanced **dynamic seat selection UI** that visually replicates a cinema hall layout. This layout is configurable by administrators via JSON, allowing for custom rows, columns, and the designation of **disabled seats**. The UI clearly indicates available, booked, and disabled seats.
    *   Simulated payment processing with options for success or failure, leading to booking confirmation.
    *   Automated **PDF ticket generation** and delivery via email upon successful booking. Users can also download their tickets from their booking history.
*   **User Profile Management:**
    *   Users can edit their profile details, including phone number and address.
    *   Ability to upload and update **profile pictures**, featuring a live preview and robust validation for file type and size.
    *   Emphasis on data integrity: Email addresses cannot be changed from the profile page, and username uniqueness is strictly enforced.

#### Admin Panel Functionalities:
*   **Comprehensive Dashboard:** Quick links to various management sections.
*   **Movie Management (CRUD):**
    *   Add, edit, and delete movie entries.
    *   Upload and manage both **poster and cover images** for movies, with integrated validation for file types and sizes.
*   **Theatre/Screen Management (CRUD):**
    *   Add, edit, and delete theatre information.
    *   Define and configure **custom seat layouts via JSON**, including the ability to mark specific seats as disabled, which directly influences the user-facing seat selection interface.
*   **Showtime Management (CRUD):
    *   Add, edit, and delete showtimes, including setting the price per seat in FCFA.
*   **User Management (CRUD):**
    *   Search, filter, edit, and delete user accounts.
*   **Booking Overview:**
    *   View all system bookings, including details about the user, movie, selected seats, payment status, and the option to download tickets.

---

## 3. Technical Deep Dive

Our project's architecture is based on a LAMP-like stack, utilizing a blend of robust server-side scripting and modern frontend frameworks.

*   **Backend:**
    *   **PHP 7+:** The core server-side language, leveraging **PDO (PHP Data Objects)** for secure and efficient database interactions, and PHP sessions for managing user state.
    *   **Dotenv Library:** Utilized for loading environment variables from the `.env` file, ensuring sensitive credentials (like database and SMTP settings) are kept separate from the codebase, enhancing security and configurability.
*   **Frontend:**
    *   **Tailwind CSS:** Employed for rapid UI development, providing a highly customizable and responsive design framework.
    *   **Custom CSS:** Used for specific stylistic enhancements and complex UI components complementing Tailwind's utility-first approach.
    *   **JavaScript:** Implemented for interactive elements such as the live preview for profile picture uploads, the interactive star rating system, and the dynamic seat selection logic.
*   **Database:**
    *   **MySQL:** The relational database management system, storing all application data. Key tables include:
        *   `users`: Contains user authentication details, profile information, and fields for OTP (`otp_code`, `otp_expires_at`) and password reset (`password_reset_token`, `password_reset_expires`).
        *   `movies`: Stores movie details, including `poster_image_path` and `cover_image_path`.
        *   `theatres`: Defines theatre capacity and the flexible `seat_layout` (JSON).
        *   `showtimes`: Links movies and theatres to specific show dates/times and pricing.
        *   `bookings`: Records booking details, including a nullable `transaction_ref` (VARCHAR 32) and payment status.
        *   `movie_ratings`: Stores user-submitted movie ratings and comments.
*   **Key Libraries & Tools (Managed via Composer):**
    *   **FPDF:** A PHP class for generating PDF documents, used for creating movie tickets.
    *   **PHPMailer:** A powerful and flexible library for sending emails, integrated for delivering OTPs and PDF tickets.
    *   **Google API Client:** Enables secure Google OAuth authentication.

---

## 4. Setup & Installation

To set up and run the project locally, follow these steps:

1.  **Clone the Repository:**
    ```bash
    git clone <your-repo-url>
    ```
2.  **Install Composer Dependencies:** Navigate to the project root and run:
    ```bash
    composer install
    ```
3.  **Create `.env` file:** Copy the `.env.example` file (if available) to `.env` in the project root. Configure your database credentials, SMTP settings (for email), and Google OAuth keys. The application uses the `vlucas/phpdotenv` library to load these variables.
    ```
    DB_HOST=localhost
    DB_NAME=movie_booking_db
    DB_USER=your_db_user
    DB_PASS=your_db_pass
    SMTP_HOST=smtp.gmail.com
    SMTP_PORT=25
    SMTP_USER=your_gmail@gmail.com
    SMTP_PASS=your_gmail_password
    SMTP_FROM=your_gmail@gmail.com
    SMTP_FROM_NAME=Movie Magic
    SMTP_SECURE=false
    GOOGLE_CLIENT_ID=...
    GOOGLE_CLIENT_SECRET=...
    GOOGLE_REDIRECT_URI=http://localhost:3000/movie_ticket_booking/auth/google_oauth_callback.php
    ```
4.  **Import the Database:** Use the SQL DDL provided in `db.md` to create the `movie_booking_db` database and all necessary tables.
5.  **Set Permissions:** Ensure the `uploads/` directory (and its subdirectories `posters/` and `profile_pics/`) is writable by your web server.
6.  **Run Locally:** Use a web server like XAMPP, WAMP, or PHP's built-in server (e.g., `php -S localhost:3000`).
7.  **Access the Application:** Navigate to `http://localhost:3000/movie_ticket_booking/` in your web browser.

---

## 5. Development Process & Learnings

Our development process involved iterative cycles of design, implementation, and testing. A significant part of this project involved deep analysis of the existing codebase to ensure comprehensive documentation. We encountered and resolved several inconsistencies between initial documentation and actual code implementation, particularly regarding environment variable usage, advanced authentication flows (OTP 2FA, password reset), and the dynamic nature of UI components like seat selection and image handling. This rigorous analysis helped us build a more accurate and reliable `README.md` and understand the project's intricacies at a deeper level.

We gained valuable experience in:
*   Integrating third-party libraries (PHPMailer, FPDF, Google API Client) using Composer.
*   Implementing robust security practices (CSRF protection, XSS prevention, password hashing, 2FA).
*   Designing and implementing a flexible database schema for complex relationships.
*   Developing a responsive user interface with Tailwind CSS and custom JavaScript.
*   The importance of thorough code analysis and documentation to ensure project clarity and maintainability.

---

## 6. Conclusion

The Movie Ticket Booking System represents a successful application of various web development technologies and principles. Our team is proud of the comprehensive features implemented, the robust security measures, and the user-friendly interface. This project has significantly enhanced our practical skills and collaborative development experience. 