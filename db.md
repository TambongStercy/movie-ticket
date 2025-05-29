CREATE DATABASE movie_booking_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE movie_booking_db;


CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    google_id VARCHAR(255) NULL UNIQUE,
    profile_picture VARCHAR(255) NULL,
    phone VARCHAR(30) NULL,
    address VARCHAR(255) NULL,
    otp_code VARCHAR(10) NULL,
    otp_expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE movies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    director VARCHAR(100) NULL,
    cast TEXT NULL,
    genre VARCHAR(100) NULL,
    category VARCHAR(50) NULL, -- e.g., Cartoon, Anime, TV Show
    max_age INT NULL,          -- e.g., 18 for 18+
    duration_minutes INT NULL,
    release_date DATE NULL,
    poster_image_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


CREATE TABLE movie_ratings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    movie_id INT NOT NULL,
    rating INT NOT NULL, -- 1 to 5
    comment TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
);

CREATE TABLE showtimes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    movie_id INT,
    theatre_id INT,
    show_datetime DATETIME NOT NULL,
    price_per_seat DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (theatre_id) REFERENCES theatres(id) ON DELETE CASCADE
);


CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    showtime_id INT,
    num_seats INT NOT NULL,
    booked_seats TEXT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    transaction_ref VARCHAR(50) UNIQUE NOT NULL,
    booking_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_status ENUM('pending', 'confirmed', 'failed') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (showtime_id) REFERENCES showtimes(id) ON DELETE CASCADE
);

