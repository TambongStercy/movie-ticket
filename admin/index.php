<?php
require_once __DIR__ . '/includes/auth_check_admin.php'; // Protect page
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="flex">
    <div class="w-full min-h-screen p-8 bg-[#18181c] pt-20 md:ml-[240px]">
        <h1 class="text-3xl font-bold text-white mb-8">Admin Dashboard</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Movie Management Card -->
            <div class="card-magic">
                <h3 class="text-xl font-semibold text-red-500 mb-2">Movie Management</h3>
                <p class="text-gray-300 text-sm mb-4">Add new movies, edit existing ones, or remove movies from the
                    system.</p>
                <a href="manage_movies.php" class="btn-magic">Manage Movies</a>
            </div>
            <!-- Showtime Management Card -->
            <div class="card-magic">
                <h3 class="text-xl font-semibold text-yellow-400 mb-2">Showtime Management</h3>
                <p class="text-gray-300 text-sm mb-4">Create, update, or delete movie showtimes and set ticket prices.
                </p>
                <a href="manage_showtimes.php" class="btn-magic">Manage Showtimes</a>
            </div>
            <!-- Theatre Management Card -->
            <div class="card-magic">
                <h3 class="text-xl font-semibold text-green-400 mb-2">Theatre Management</h3>
                <p class="text-gray-300 text-sm mb-4">Add, edit, or remove theatres/screens used for showtimes.</p>
                <a href="manage_theatres.php" class="btn-magic">Manage Theatres</a>
            </div>
            <!-- Booking Management Card -->
            <div class="card-magic">
                <h3 class="text-xl font-semibold text-purple-400 mb-2">View Bookings</h3>
                <p class="text-gray-300 text-sm mb-4">Oversee all user bookings, check payment statuses, and view
                    booking details.</p>
                <a href="view_bookings.php" class="btn-magic">View Bookings</a>
            </div>
            <!-- User Management Card -->
            <div class="card-magic">
                <h3 class="text-xl font-semibold text-blue-400 mb-2">User Management</h3>
                <p class="text-gray-300 text-sm mb-4">Manage user accounts, roles, and permissions.</p>
                <a href="manage_users.php" class="btn-magic">Manage Users</a>
            </div>
        </div>
    </div>
</div>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>