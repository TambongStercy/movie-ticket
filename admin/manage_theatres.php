<?php
require_once __DIR__ . '/includes/auth_check_admin.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Fetch all theatres
$stmt = $pdo->query('SELECT * FROM theatres ORDER BY id ASC');
$theatres = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="flex">
    <div class="w-full min-h-screen p-8 bg-[#18181c] pt-20 md:ml-[240px]">
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="bg-green-900 text-green-300 px-4 py-2 rounded mb-4">
                <?php echo escape($_SESSION['success']);
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="bg-red-900 text-red-300 px-4 py-2 rounded mb-4">
                <?php echo escape($_SESSION['error']);
                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        <h1 class="text-2xl font-bold text-white mb-6">Manage Theatres / Screens</h1>
        <div class="mb-4">
            <a href="add_theatre.php" class="btn-magic">Add New Theatre</a>
        </div>
        <div class="card-magic p-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Name</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Capacity</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-[#23232b] divide-y divide-gray-800 text-gray-200">
                    <?php foreach ($theatres as $theatre): ?>
                        <tr class="hover:bg-[#28282f]">
                            <td class="px-4 py-2 font-semibold text-white"><?php echo escape($theatre['name']); ?></td>
                            <td class="px-4 py-2 text-gray-300"><?php echo escape($theatre['capacity']); ?></td>
                            <td class="px-4 py-2">
                                <a href="edit_theatre.php?id=<?php echo $theatre['id']; ?>"
                                    class="text-blue-400 hover:underline mr-4">Edit</a>
                                <a href="delete_theatre.php?id=<?php echo $theatre['id']; ?>"
                                    class="text-red-400 hover:underline"
                                    onclick="return confirm('Are you sure you want to delete this theatre?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($theatres)): ?>
                        <tr>
                            <td colspan="3" class="text-gray-400 italic py-4">No theatres/screens found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>