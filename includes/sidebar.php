<?php
// Sidebar for both user and admin dashboards
// Usage: include this file after header.php and before main content
?>
<aside class="sidebar-magic hidden md:flex flex-col" id="sidebar">
    <?php if (isLoggedIn()): ?>
        <div class="sidebar-logo">
            <img src="/movie_ticket_booking/images/logo.jpg" alt="Logo" class="h-10 w-10 object-contain" />
            <span class="text-xl font-extrabold tracking-wide text-red-600">Movie Magic</span>
        </div>
    <?php endif; ?>
    <?php if (isLoggedIn() && isAdmin()): ?>
        <?php
        $is_admin_page = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
        $is_user_page = strpos($_SERVER['PHP_SELF'], '/user/') !== false;
        ?>
        <?php if ($is_admin_page): ?>
            <a href="/movie_ticket_booking/admin/index.php" class="sidebar-link<?php if (basename($_SERVER['PHP_SELF']) == 'index.php')
                echo ' active'; ?>">
                <span>🏠</span> Dashboard
            </a>
            <a href="/movie_ticket_booking/admin/manage_movies.php" class="sidebar-link<?php if (strpos($_SERVER['PHP_SELF'], 'manage_movies') !== false)
                echo ' active'; ?>">
                <span>🎬</span> Movies
            </a>
            <a href="/movie_ticket_booking/admin/manage_showtimes.php" class="sidebar-link<?php if (strpos($_SERVER['PHP_SELF'], 'manage_showtimes') !== false)
                echo ' active'; ?>">
                <span>🕒</span> Showtimes
            </a>
            <a href="/movie_ticket_booking/admin/manage_theatres.php" class="sidebar-link<?php if (strpos($_SERVER['PHP_SELF'], 'manage_theatres') !== false)
                echo ' active'; ?>">
                <span>🏢</span> Theatres
            </a>
            <a href="/movie_ticket_booking/admin/manage_users.php" class="sidebar-link<?php if (strpos($_SERVER['PHP_SELF'], 'manage_users') !== false)
                echo ' active'; ?>">
                <span>👤</span> Users
            </a>
            <a href="/movie_ticket_booking/admin/view_bookings.php" class="sidebar-link<?php if (strpos($_SERVER['PHP_SELF'], 'view_bookings') !== false)
                echo ' active'; ?>">
                <span>📖</span> Bookings
            </a>
            <a href="/movie_ticket_booking/user/index.php"
                class="flex items-center gap-2 px-4 py-1 rounded-full bg-gray-800 hover:bg-green-600 text-white font-semibold transition border border-gray-700 mt-4">
                <svg class="w-5 h-5 mr-1 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M15 7l-5 5 5 5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M19 7l-5 5 5 5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Switch to User
            </a>
            <a href="/movie_ticket_booking/auth/logout.php"
                class="flex items-center gap-2 px-4 py-2 rounded-full bg-red-700 hover:bg-red-800 text-white font-semibold transition w-full mt-4 justify-center">
                Logout
            </a>
        <?php elseif ($is_user_page): ?>
            <a href="/movie_ticket_booking/user/index.php" class="sidebar-link<?php if (basename($_SERVER['PHP_SELF']) == 'index.php')
                echo ' active'; ?>">
                <span>🏠</span> Home
            </a>
            <a href="/movie_ticket_booking/user/my_bookings.php" class="sidebar-link<?php if (strpos($_SERVER['PHP_SELF'], 'my_bookings') !== false)
                echo ' active'; ?>">
                <span>🎟️</span> My Bookings
            </a>
            <a href="/movie_ticket_booking/user/profile.php" class="sidebar-link<?php if (strpos($_SERVER['PHP_SELF'], 'profile') !== false)
                echo ' active'; ?>">
                <span>👤</span> Profile
            </a>
            <a href="/movie_ticket_booking/admin/index.php"
                class="flex items-center gap-2 px-4 py-1 rounded-full bg-gray-800 hover:bg-blue-600 text-white font-semibold transition border border-gray-700 mt-4">
                <svg class="w-5 h-5 mr-1 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M9 7l5 5-5 5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M5 7l5 5-5 5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Switch to Admin
            </a>
            <a href="/movie_ticket_booking/auth/logout.php"
                class="flex items-center gap-2 px-4 py-2 rounded-full bg-red-700 hover:bg-red-800 text-white font-semibold transition w-full mt-4 justify-center">
                Logout
            </a>
        <?php endif; ?>
    <?php elseif (isLoggedIn()): ?>
        <a href="/movie_ticket_booking/user/index.php" class="sidebar-link<?php if (basename($_SERVER['PHP_SELF']) == 'index.php')
            echo ' active'; ?>">
            <span>🏠</span> Home
        </a>
        <a href="/movie_ticket_booking/user/my_bookings.php" class="sidebar-link<?php if (strpos($_SERVER['PHP_SELF'], 'my_bookings') !== false)
            echo ' active'; ?>">
            <span>🎟️</span> My Bookings
        </a>
        <a href="/movie_ticket_booking/user/profile.php" class="sidebar-link<?php if (strpos($_SERVER['PHP_SELF'], 'profile') !== false)
            echo ' active'; ?>">
            <span>👤</span> Profile
        </a>
        <a href="/movie_ticket_booking/auth/logout.php"
            class="flex items-center gap-2 px-4 py-2 rounded-full bg-red-700 hover:bg-red-800 text-white font-semibold transition w-full mt-4 justify-center">
            <span>🚪</span> Logout
        </a>
    <?php endif; ?>
</aside>
<!-- Add a left margin to main content to account for sidebar width on md: and up -->