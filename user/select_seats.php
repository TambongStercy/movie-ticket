<?php
require_once __DIR__ . '/includes/auth_check_user.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$showtime_id = isset($_GET['showtime_id']) ? (int) $_GET['showtime_id'] : 0;
$showtime = null;
$theatre = null;
$movie = null;
$booked_seats = [];
$disabled_seats = [];

if ($showtime_id > 0) {
    $stmt = $pdo->prepare('SELECT s.*, m.title, t.name AS theatre_name, t.capacity FROM showtimes s JOIN movies m ON s.movie_id = m.id JOIN theatres t ON s.theatre_id = t.id WHERE s.id = ?');
    $stmt->execute([$showtime_id]);
    $showtime = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($showtime) {
        $movie = ['title' => $showtime['title']];
        $theatre = ['name' => $showtime['theatre_name'], 'capacity' => $showtime['capacity']];
        // Get already booked seats
        $stmt = $pdo->prepare('SELECT booked_seats FROM bookings WHERE showtime_id = ? AND payment_status = "confirmed"');
        $stmt->execute([$showtime_id]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $booked_seats = array_merge($booked_seats, explode(',', $row['booked_seats']));
        }
        // Fetch seat_layout JSON for this theatre
        $stmt = $pdo->prepare('SELECT seat_layout FROM theatres WHERE id = ?');
        $stmt->execute([$showtime['theatre_id']]);
        $theatre_row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($theatre_row && !empty($theatre_row['seat_layout'])) {
            $layout = json_decode($theatre_row['seat_layout'], true);
            if (isset($layout['disabled_seats']) && is_array($layout['disabled_seats'])) {
                $disabled_seats = $layout['disabled_seats'];
            }
        }
    }
}

$csrf_token = generateCsrfToken();
?>
<div class="flex min-h-screen pt-20">
    <div class="w-full min-h-screen p-8 bg-[#18181c] pt-20 md:ml-[240px]">
        <?php if ($showtime && $theatre): ?>
            <div class="w-full flex justify-center">
                <div class="w-full max-w-4xl card-magic mb-8 p-6">
                    <h1 class="text-xl font-bold mb-2 text-white text-center">Select Seats for
                        <?php echo escape($movie['title']); ?>
                    </h1>
                    <div class="mb-2 text-gray-300 text-center">Theatre: <?php echo escape($theatre['name']); ?> | Date &
                        Time: <?php echo escape($showtime['show_datetime']); ?></div>
                    <div class="mb-2 text-gray-300 text-center">Price per seat:
                        <?php echo number_format($showtime['price_per_seat'], 0, '.', ' '); ?> FCFA
                    </div>
                    <!-- Cinema Screen (outside card, prominent) -->
                    <div class="w-full flex justify-center mb-2">
                        <div
                            class="w-[60vw] max-w-3xl h-10 bg-gradient-to-b from-gray-200 to-gray-400 rounded-b-full shadow-lg flex items-end justify-center relative">
                            <span
                                class="absolute left-1/2 -translate-x-1/2 pb-2 text-gray-700 font-bold tracking-widest text-lg">SCREEN</span>
                        </div>
                    </div>
                    <form action="book_ticket.php" method="POST" class="space-y-4" id="seatForm">
                        <input type="hidden" name="csrf_token" value="<?php echo escape($csrf_token); ?>">
                        <input type="hidden" name="showtime_id" value="<?php echo $showtime_id; ?>">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-400 mb-2 text-center">Select your
                                seats:</label>
                            <div class="overflow-x-auto w-full flex flex-col items-center">
                                <?php
                                $rows = $cols = 0;
                                if (isset($layout['rows']) && isset($layout['cols'])) {
                                    $rows = (int) $layout['rows'];
                                    $cols = (int) $layout['cols'];
                                }
                                if ($rows > 0 && $cols > 0) {
                                    $row_letters = range('A', chr(ord('A') + $rows - 1));
                                    for ($r = 0; $r < $rows; $r++) {
                                        $curve_margin = max(0, abs($r - ($rows - 1) / 2) * 4);
                                        echo '<div class="flex items-center justify-center mb-3" style="margin-left:' . $curve_margin . 'px; margin-right:' . $curve_margin . 'px;">';
                                        $row_letter = $row_letters[$r];
                                        for ($c = 1; $c <= $cols; $c++) {
                                            $seat = $row_letter . $c;
                                            $is_booked = in_array($seat, $booked_seats);
                                            $is_disabled = in_array($seat, $disabled_seats);
                                            $seat_class = '';
                                            if ($is_booked) {
                                                $seat_class = 'bg-[#55524a] border-[#55524a]'; // brown
                                            } elseif ($is_disabled) {
                                                $seat_class = 'bg-red-600 border-red-600';
                                            } else {
                                                $seat_class = 'bg-green-600 border-green-600';
                                            }
                                            echo '<label class="inline-flex flex-col items-center mx-1 cursor-pointer">';
                                            echo '<input type="checkbox" name="selected_seats[]" value="' . escape($seat) . '"' . ($is_booked || $is_disabled ? ' disabled' : '') . ' class="seat-checkbox sr-only" />';
                                            echo '<span class="custom-seat w-5 h-5 rounded-full border-2 flex items-center justify-center ' . $seat_class . ' transition"></span>';
                                            echo '<span class="text-xs mt-1 ' . ($is_booked ? 'line-through text-gray-400' : '') . ($is_disabled ? ' line-through text-red-400 font-bold' : '') . '">' . escape($seat) . '</span>';
                                            echo '</label>';
                                        }
                                        echo '</div>';
                                    }
                                } else {
                                    // Fallback: flat grid if no layout
                                    echo '<div class="grid grid-cols-8 gap-2">';
                                    for ($i = 1; $i <= $theatre['capacity']; $i++) {
                                        $seat = 'S' . $i;
                                        $is_booked = in_array($seat, $booked_seats);
                                        $is_disabled = in_array($seat, $disabled_seats);
                                        $seat_class = '';
                                        if ($is_booked) {
                                            $seat_class = 'bg-[#55524a] border-[#55524a]';
                                        } elseif ($is_disabled) {
                                            $seat_class = 'bg-red-600 border-red-600';
                                        } else {
                                            $seat_class = 'bg-green-600 border-green-600';
                                        }
                                        echo '<label class="inline-flex flex-col items-center cursor-pointer">';
                                        echo '<input type="checkbox" name="selected_seats[]" value="' . escape($seat) . '"' . ($is_booked || $is_disabled ? ' disabled' : '') . ' class="seat-checkbox sr-only" />';
                                        echo '<span class="custom-seat w-5 h-5 rounded-full border-2 flex items-center justify-center ' . $seat_class . ' transition"></span>';
                                        echo '<span class="text-xs mt-1 ' . ($is_booked ? 'line-through text-gray-400' : '') . ($is_disabled ? ' line-through text-red-400 font-bold' : '') . '">' . escape($seat) . '</span>';
                                        echo '</label>';
                                    }
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-4 justify-center items-center mt-4 mb-2 text-xs">
                            <span class="flex items-center"><span
                                    class="inline-block w-5 h-5 rounded-full bg-green-600 border-2 border-green-600 mr-1"></span>Available</span>
                            <span class="flex items-center"><span
                                    class="inline-block w-5 h-5 rounded-full bg-[#55524a] border-2 border-[#55524a] mr-1"></span>Booked</span>
                            <span class="flex items-center"><span
                                    class="inline-block w-5 h-5 rounded-full bg-red-600 border-2 border-red-600 mr-1"></span>Disabled</span>
                            <span class="flex items-center"><span
                                    class="inline-block w-5 h-5 rounded-full border-2 border-yellow-400 mr-1"></span>Selected</span>
                        </div>
                        <div class="flex justify-center">
                            <button type="submit" class="btn-magic px-4 py-2 text-sm">Book Selected Seats</button>
                        </div>
                    </form>
                    <script>
                        // Custom seat selection UI
                        document.addEventListener('DOMContentLoaded', function () {
                            const checkboxes = document.querySelectorAll('.seat-checkbox');
                            checkboxes.forEach(cb => {
                                cb.addEventListener('change', function () {
                                    updateCustomSeats();
                                });
                            });
                            function updateCustomSeats() {
                                document.querySelectorAll('label').forEach(label => {
                                    const cb = label.querySelector('.seat-checkbox');
                                    const seat = label.querySelector('.custom-seat');
                                    if (!cb || !seat) return;
                                    if (cb.checked && !cb.disabled) {
                                        seat.classList.remove('bg-green-600', 'border-green-600');
                                        seat.classList.add('border-yellow-400');
                                        seat.style.backgroundColor = 'transparent';
                                    } else if (!cb.disabled) {
                                        seat.classList.remove('border-yellow-400');
                                        seat.classList.add('bg-green-600', 'border-green-600');
                                        seat.style.backgroundColor = '';
                                    }
                                });
                            }
                            updateCustomSeats();
                        });
                    </script>
                </div>
            </div>
        <?php else: ?>
            <div class="max-w-2xl mx-auto card-magic text-center">
                <div class="text-red-400 font-semibold">Showtime not found.</div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>