<?php
require_once __DIR__ . '/includes/auth_check_admin.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$name = $capacity = $seat_layout = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'CSRF token validation failed.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $capacity = (int) ($_POST['capacity'] ?? 0);
        $seat_layout = trim($_POST['seat_layout'] ?? '');
        if ($name === '') {
            $errors[] = 'Name is required.';
        }
        if ($capacity <= 0) {
            $errors[] = 'Capacity must be a positive number.';
        }
        if (empty($errors)) {
            $stmt = $pdo->prepare('INSERT INTO theatres (name, capacity, seat_layout) VALUES (?, ?, ?)');
            $stmt->execute([$name, $capacity, $seat_layout ?: null]);
            $_SESSION['success'] = 'Theatre added successfully!';
            header('Location: manage_theatres.php');
            exit;
        }
    }
}
$csrf_token = generateCsrfToken();
?>
<div class="flex">
    <div class="ml-[240px] pt-20 w-full min-h-screen p-8 bg-[#18181c]">
        <div class="max-w-lg mx-auto card-magic">
            <h1 class="text-2xl font-bold text-white mb-6">Add New Theatre / Screen</h1>
            <?php if ($errors): ?>
                <div class="bg-red-900 text-red-300 px-4 py-2 rounded mb-4">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo escape($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="space-y-6" id="theatreForm">
                <input type="hidden" name="csrf_token" value="<?php echo escape($csrf_token); ?>">
                <div>
                    <label class="block text-sm font-medium text-gray-200 mb-1">Name <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="name" value="<?php echo escape($name); ?>"
                        class="w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded text-white focus:ring-red-500 focus:border-red-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-200 mb-1">Capacity <span
                            class="text-red-500">*</span></label>
                    <input type="number" name="capacity" value="<?php echo escape($capacity); ?>"
                        class="w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded text-white focus:ring-red-500 focus:border-red-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-200 mb-1">Rows</label>
                    <input type="number" id="rows" min="1"
                        class="w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded text-white focus:ring-red-500 focus:border-red-500"
                        value="">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-200 mb-1">Seats per Row</label>
                    <input type="number" id="cols" min="1"
                        class="w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded text-white focus:ring-red-500 focus:border-red-500"
                        value="">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-200 mb-1">Disabled Seats (comma-separated, e.g.
                        S3,S5)</label>
                    <input type="text" id="disabled_seats"
                        class="w-full px-3 py-2 bg-[#23232b] border border-[#333] rounded text-white focus:ring-red-500 focus:border-red-500"
                        value="">
                </div>
                <input type="hidden" name="seat_layout" id="seat_layout">
                <div class="flex flex-col md:flex-row gap-2 pt-2">
                    <button type="submit" class="w-full btn-magic">Add Theatre</button>
                    <a href="manage_theatres.php"
                        class="w-full btn-magic bg-gray-700 hover:bg-gray-600 text-gray-200">Cancel</a>
                </div>
            </form>
            <script>
                function updateJson() {
                    const rows = parseInt(document.getElementById('rows').value) || null;
                    const cols = parseInt(document.getElementById('cols').value) || null;
                    const disabled = document.getElementById('disabled_seats').value.trim();
                    let obj = {};
                    if (rows) obj.rows = rows;
                    if (cols) obj.cols = cols;
                    if (disabled) obj.disabled_seats = disabled.split(',').map(s => s.trim()).filter(Boolean);
                    const json = Object.keys(obj).length ? JSON.stringify(obj) : '';
                    document.getElementById('seat_layout').value = json;
                }
                document.getElementById('rows').addEventListener('input', updateJson);
                document.getElementById('cols').addEventListener('input', updateJson);
                document.getElementById('disabled_seats').addEventListener('input', updateJson);
                document.getElementById('theatreForm').addEventListener('submit', function (e) {
                    updateJson();
                });
            </script>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>