<?php require_once __DIR__ . '/functions.php'; // Ensure session_start() is called ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Magic</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/movie_ticket_booking/css/style.css"> <?php // Adjust path ?>
    <style>
        .category-active {
            color: #ef4444;
            font-weight: bold;
            border-bottom: 3px solid #ef4444;
        }

        .profile-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 60px;
            background: #23232b;
            color: #fff;
            min-width: 200px;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            z-index: 50;
        }

        .profile-group:hover .profile-dropdown {
            display: block;
        }
    </style>
</head>

<body class="bg-[#18181c] text-white min-h-screen">
    <header
        class="bg-[#18181c] border-b border-[#23232b] shadow flex items-center px-4 md:px-6 h-16 fixed top-0 left-0 w-full z-40">
        <div class="flex items-center w-full">
            <a href="/movie_ticket_booking/index.php" class="flex items-center gap-2 md:ml-[240px]">
                <img src="/movie_ticket_booking/images/logo.jpg" alt="Logo" class="h-10 w-10 object-contain" />
                <span class="text-2xl font-extrabold tracking-wide text-red-600">Movie Magic</span>
            </a>
            <!-- Hamburger for mobile -->
            <button id="mobile-menu-btn"
                class="ml-auto md:hidden text-gray-300 focus:outline-none focus:ring-2 focus:ring-red-500 p-2">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <!-- Desktop nav -->
            <nav class="hidden md:flex gap-8 items-center ml-10">
                <a href="/movie_ticket_booking/user/index.php"
                    class="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'category-active' : 'text-white hover:text-red-500'; ?> text-lg transition">Movies</a>
                <a href="#" class="text-white hover:text-red-500 text-lg transition">Anime</a>
            </nav>
            <form method="GET" action="/movie_ticket_booking/user/index.php"
                class="hidden md:flex items-center bg-[#23232b] rounded-lg px-3 py-1 w-[320px] max-w-xs ml-8">
                <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                <input type="text" name="search" placeholder="Search"
                    class="bg-transparent outline-none text-white flex-1" />
                <button type="submit" class="ml-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path d="M3 6h18M3 12h18M3 18h18" />
                    </svg>
                </button>
            </form>
            <div class="ml-auto flex items-center gap-4 w-full justify-end">
                <?php if (isLoggedIn()): ?>
                    <?php
                    // Profile picture logic
                    $profile_pic = null;
                    if (isset($_SESSION['user_id'])) {
                        $user_id = $_SESSION['user_id'];
                        $pdo = $pdo ?? null;
                        if ($pdo) {
                            $stmt = $pdo->prepare('SELECT profile_picture, email FROM users WHERE id = ?');
                            $stmt->execute([$user_id]);
                            $user = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($user && $user['profile_picture']) {
                                $profile_pic = '/movie_ticket_booking/uploads/profile_pics/' . $user['profile_picture'];
                            }
                            $user_email = $user['email'] ?? '';
                        }
                    }
                    ?>
                    <?php if (isAdmin()): ?>
                        <?php
                        $is_admin_page = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
                        $is_user_page = strpos($_SERVER['PHP_SELF'], '/user/') !== false;
                        ?>
                        <?php if ($is_admin_page): ?>
                            <a href="/movie_ticket_booking/user/index.php"
                                class="flex items-center gap-2 px-4 py-1 rounded-full bg-gray-800 hover:bg-green-600 text-white font-semibold transition border border-gray-700">
                                <svg class="w-5 h-5 mr-1 text-red-500" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path d="M15 7l-5 5 5 5" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M19 7l-5 5 5 5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                Switch to User
                            </a>
                        <?php elseif ($is_user_page): ?>
                            <a href="/movie_ticket_booking/admin/index.php"
                                class="flex items-center gap-2 px-4 py-1 rounded-full bg-gray-800 hover:bg-blue-600 text-white font-semibold transition border border-gray-700">
                                <svg class="w-5 h-5 mr-1 text-red-500" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path d="M9 7l5 5-5 5" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M5 7l5 5-5 5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                Switch to Admin
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="relative profile-group cursor-pointer ml-2" tabindex="0">
                        <a href="/movie_ticket_booking/user/profile.php" class="flex items-center gap-2"
                            id="profileTrigger">
                            <?php if ($profile_pic): ?>
                                <img src="<?php echo escape($profile_pic); ?>" alt="Profile"
                                    class="w-10 h-10 rounded-full object-cover border-2 border-gray-700" />
                            <?php else: ?>
                                <div
                                    class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center text-red-400 text-xl font-bold select-none">
                                    <?php echo isset($user_email) && $user_email ? strtoupper($user_email[0]) : '?'; ?>
                                </div>
                            <?php endif; ?>
                            <span
                                class="font-semibold text-white hidden sm:inline"><?php echo escape($_SESSION['username']); ?></span>
                        </a>
                        <div class="profile-dropdown absolute right-0 mt-2 py-2 px-4 bg-[#23232b] rounded-lg shadow-lg min-w-[200px] pointer-events-auto"
                            style="display:none;">
                            <div class="text-sm text-gray-400 mb-1">Signed in as</div>
                            <div class="font-bold text-white mb-1"><?php echo escape($_SESSION['username']); ?></div>
                            <div class="text-xs text-gray-300 mb-2"><?php echo escape($user_email ?? ''); ?></div>
                            <a href="/movie_ticket_booking/user/profile.php"
                                class="block text-red-500 hover:underline text-sm">View Profile</a>
                            <a href="/movie_ticket_booking/auth/logout.php"
                                class="block mt-2 px-4 py-2 rounded bg-red-700 hover:bg-red-800 text-white text-center font-semibold transition">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/movie_ticket_booking/auth/login.php"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded transition">Login</a>
                    <a href="/movie_ticket_booking/auth/register.php"
                        class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded transition">Register</a>
                <?php endif; ?>
            </div>
        </div>
        <!-- Mobile menu -->
        <div id="mobile-menu"
            class="md:hidden fixed top-16 left-0 w-full bg-[#18181c] border-b border-[#23232b] z-40 hidden flex-col gap-2 px-4 pb-4">
            <nav class="flex flex-col gap-2">
                <a href="/movie_ticket_booking/user/index.php"
                    class="text-lg py-2 px-2 rounded hover:bg-[#23232b] <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'category-active' : 'text-white'; ?>">Movies</a>
                <a href="#" class="text-lg py-2 px-2 rounded hover:bg-[#23232b] text-white">Anime</a>
            </nav>
            <form method="GET" action="/movie_ticket_booking/user/index.php"
                class="flex items-center bg-[#23232b] rounded-lg px-3 py-1 mt-2">
                <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                <input type="text" name="search" placeholder="Search"
                    class="bg-transparent outline-none text-white flex-1" />
                <button type="submit" class="ml-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path d="M3 6h18M3 12h18M3 18h18" />
                    </svg>
                </button>
            </form>
        </div>
    </header>
    <!-- Sidebar and main content will be handled in each page for flexibility -->
    <script>
        // Robust dropdown: toggle on click, close on outside click or Escape
        (function () {
            const profileGroup = document.querySelector('.profile-group');
            const profileDropdown = profileGroup?.querySelector('.profile-dropdown');
            const profileTrigger = document.getElementById('profileTrigger');
            let open = false;
            function showDropdown() {
                profileDropdown.style.display = 'block';
                open = true;
            }
            function hideDropdown() {
                profileDropdown.style.display = 'none';
                open = false;
            }
            if (profileTrigger && profileDropdown) {
                profileTrigger.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (open) {
                        hideDropdown();
                    } else {
                        showDropdown();
                    }
                });
                document.addEventListener('mousedown', function (e) {
                    if (!profileGroup.contains(e.target)) {
                        hideDropdown();
                    }
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        hideDropdown();
                    }
                });
            }
        })();
        // Mobile menu toggle
        const btn = document.getElementById('mobile-menu-btn');
        const menu = document.getElementById('mobile-menu');
        if (btn && menu) {
            btn.addEventListener('click', () => {
                menu.classList.toggle('hidden');
            });
            document.addEventListener('click', (e) => {
                if (!menu.contains(e.target) && !btn.contains(e.target)) {
                    menu.classList.add('hidden');
                }
            });
        }
    </script>
</body>

</html>