# Movie Ticket Booking System

A modern, responsive web application for booking movie tickets online, built with **PHP** and **MySQL**. It supports both user and admin roles, Google OAuth, seat selection, movie ratings, PDF ticket generation, and more. All prices are in **FCFA**.

---

## Features

### User Features
- Register, login (with Google OAuth or email/password), and logout
- View movies in a Netflix-style dashboard (carousel, top rated, genres, popular)
- Search and filter movies by title, genre, and rating
- View detailed movie info (poster, cover image, description, cast, director, genre, duration, release date, category, max age, ratings)
- Rate movies (1-5 stars + comment) with an interactive star rating UI, see average/user rating
- Book tickets for showtimes (with a dynamic seat selection UI that matches cinema hall layout, displaying available, booked, and disabled seats based on admin configuration)
- Simulate payment (success/failure)
- Receive PDF ticket by email after booking confirmation
- Download ticket PDF from booking history
- View booking history
- Edit user profile (profile picture with live preview and validation for type/size, phone, address). Note: Email cannot be changed from the profile page, and username uniqueness is enforced.

### Admin Features
- Admin dashboard with quick links
- Manage movies (add, edit, delete, upload poster and cover images with validation for file type and size)
- Manage theatres/screens (add, edit, delete, set custom seat layout via JSON, including marking seats as disabled)
- Manage showtimes (add, edit, delete, set price in FCFA)
- Manage users (search, filter, edit, delete)
- View all bookings (with user, movie, seats, status, ticket download)

### Technical/UX
- Responsive design (works on mobile, tablet, desktop)
- Sidebar and navbar adapt to screen size
- All forms and tables are mobile-friendly
- Uses Tailwind CSS for modern UI
- Secure: CSRF protection (via `generateCsrfToken` and `validateCsrfToken` functions), XSS prevention (via `escape` function), password hashing
- All emails and PDFs use PHPMailer and FPDF (via Composer)

---

## Tech Stack
- **Backend:** PHP 7+ (PDO, sessions, Dotenv library for environment variables)
- **Frontend:** Tailwind CSS, custom CSS
- **Database:** MySQL (see schema below)
- **PDF/Email:** FPDF, PHPMailer (installed via Composer)
- **OAuth:** Google API Client (installed via Composer)

---

## Directory Structure

```
movie_ticket_booking/
├── admin/              # Admin panel (CRUD for movies, users, showtimes, theatres)
├── auth/               # Login, register, Google OAuth
├── css/                # Tailwind and custom styles
├── images/             # App images (logo, etc.)
├── includes/           # Shared PHP includes (db, functions, header, footer, sidebar)
├── js/                 # JS scripts (carousel, seat selection, etc.)
├── uploads/
│   └── posters/        # Movie posters
│   └── profile_pics/   # User profile pictures
├── user/               # User dashboard, booking, profile, etc.
├── vendor/             # Composer dependencies
├── .env                # Environment variables (DB, SMTP, Google OAuth)
├── composer.json       # Composer config
├── README.md           # This file
└── ...
```

---

## Database Schema (MySQL)

- **users**: id, username, email, password_hash, role, google_id, profile_picture, phone, address, otp_code, otp_expires_at, password_reset_token, password_reset_expires, created_at
- **movies**: id, title, description, director, cast, genre, duration_minutes, release_date, poster_image_path, cover_image_path, category, max_age, created_at, updated_at
- **theatres**: id, name, capacity, seat_layout (JSON)
- **showtimes**: id, movie_id, theatre_id, show_datetime, price_per_seat (FCFA), created_at
- **bookings**: id, user_id, showtime_id, num_seats, booked_seats, total_amount (FCFA), booking_timestamp, payment_status, transaction_ref (VARCHAR 32, can be NULL)
- **movie_ratings**: id, user_id, movie_id, rating (1-5), comment, created_at

See `db.md` for full SQL DDL.

---

## Setup & Installation

1. **Clone the repo:**
   ```
   git clone <your-repo-url>
   ```
2. **Install Composer dependencies:**
   ```
   composer install
   ```
3. **Create `.env` file:** (copy `.env.example` if available)
   - Set DB credentials, SMTP (for email), Google OAuth keys
   - **Note:** The application uses `Dotenv` to load these variables. Ensure your `.env` file is in the project root.
   - Example:
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
4. **Import the database:**
   - Use the SQL in `db.md` to create all tables.
5. **Set permissions:**
   - Ensure `uploads/` is writable by the web server.
6. **Run locally:**
   - Use XAMPP, WAMP, or PHP's built-in server:
     ```
     php -S localhost:3000
     ```
7. **Access the app:**
   - Go to `http://localhost:3000/movie_ticket_booking/`

---

## User Flow

1. **Register/Login:**
   - Users can register with email/password or Google OAuth.
   - **For email/password logins, an OTP (One-Time Password) is sent to the registered email for verification as a second factor of authentication.**
2. **Password Reset:**
   - Users can initiate a password reset if they forget their password, which involves email verification and a secure token.
3. **Browse Movies:**
   - See featured carousel, top rated, genres, and all movies.
4. **Search/Filter:**
   - Use the search page to filter by title, genre, or rating.
5. **View Details:**
   - Click a movie to see details, ratings, and available showtimes (only future showtimes are displayed).
6. **Book Tickets:**
   - Select a showtime, pick seats (cinema-style UI), and book.
7. **Simulate Payment:**
   - Choose payment success/failure. On success, booking is confirmed.
8. **Receive Ticket:**
   - PDF ticket is generated (using `generateMovieTicketPDF` function) and emailed. Can also download from booking history.
9. **Profile:**
   - Edit profile info and picture.

---

## Admin Flow

1. **Login as Admin:**
   - Use an admin account (set role in DB if needed).
2. **Dashboard:**
   - Quick links to manage movies, showtimes, theatres, users, bookings.
3. **CRUD Operations:**
   - Add/edit/delete movies, upload posters
   - Add/edit/delete theatres and set seat layouts (JSON)
   - Add/edit/delete showtimes (set price in FCFA)
   - Add/edit/delete users, search/filter users
   - View all bookings, download tickets

---

## Customization & Tips

- **Currency:** All prices are in FCFA by default. Change labels in PHP if needed.
- **Seat Layouts:** Admin can define detailed custom seat layouts per theatre using JSON, specifying rows, seats per row, and individual disabled seats. This dynamic layout is rendered in the user-facing seat selection interface.
- **Email:** Uses Gmail SMTP by default. You can use any SMTP server by updating `.env`.
- **Security:**
  - All forms have CSRF protection.
  - Passwords are hashed.
  - User input is escaped to prevent XSS.
- **PDF Tickets:** Generated with FPDF, attached to emails with PHPMailer.
- **Responsive:** All pages, navbars, and sidebars are mobile-friendly.

---

## Credits
- Built with ❤️ using PHP, MySQL, Tailwind CSS, FPDF, PHPMailer, and Google API Client.
- UI inspired by Netflix.

---

## License
This project is for educational/demo purposes. You can use, modify, and extend it as you wish. 