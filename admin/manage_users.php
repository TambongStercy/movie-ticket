<?php
require_once __DIR__ . '/includes/auth_check_admin.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Handle search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$current_user_id = $_SESSION['user_id'];

$sql = 'SELECT * FROM users WHERE id != ?';
$params = [$current_user_id];
if ($search !== '') {
    $sql .= ' AND (username LIKE ? OR email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($role_filter === 'user' || $role_filter === 'admin') {
    $sql .= ' AND role = ?';
    $params[] = $role_filter;
}
$sql .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="flex">
    <div class="w-full min-h-screen p-8 bg-[#18181c] pt-20 md:ml-[240px]">
        <h1 class="text-2xl font-bold text-white mb-6">Manage Users</h1>
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
        <form method="GET" class="mb-4 flex flex-col sm:flex-row gap-2 items-end">
            <input type="text" name="search" value="<?php echo escape($search); ?>"
                placeholder="Search by username or email..."
                class="flex-1 px-3 py-2 bg-[#23232b] border border-[#333] rounded text-white">
            <select name="role" class="px-3 py-2 bg-[#23232b] border border-[#333] rounded text-white">
                <option value="">All Roles</option>
                <option value="user" <?php if ($role_filter === 'user')
                    echo 'selected'; ?>>User</option>
                <option value="admin" <?php if ($role_filter === 'admin')
                    echo 'selected'; ?>>Admin</option>
            </select>
            <button type="submit" class="btn-magic">Filter</button>
            <a href="manage_users.php" class="ml-2 text-gray-400 hover:underline">Reset</a>
        </form>
        <div class="mb-4">
            <a href="add_user.php" class="btn-magic">Add New User</a>
        </div>
        <div class="card-magic p-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Username</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Email</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Role</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Created At</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-[#23232b] divide-y divide-gray-800 text-gray-200">
                    <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-[#28282f]">
                            <td class="px-4 py-2 font-semibold text-white"><?php echo escape($user['username']); ?></td>
                            <td class="px-4 py-2 text-gray-300"><?php echo escape($user['email']); ?></td>
                            <td class="px-4 py-2 text-gray-300"><?php echo escape($user['role']); ?></td>
                            <td class="px-4 py-2 text-gray-300"><?php echo escape($user['created_at']); ?></td>
                            <td class="px-4 py-2">
                                <a href="edit_user.php?id=<?php echo $user['id']; ?>"
                                    class="text-blue-400 hover:underline mr-4">Edit</a>
                                <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="text-red-400 hover:underline"
                                    onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" class="text-gray-400 italic py-4">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>