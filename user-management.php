<?php
require_once 'db-connect.php';
redirectIfNotAdmin();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_user'])) {
        // Create new user
        $username = sanitizeInput($_POST['username']);
        $password = hashPassword(sanitizeInput($_POST['password']));
        $fullName = sanitizeInput($_POST['full_name']);
        $email = sanitizeInput($_POST['email']);
        $role = sanitizeInput($_POST['role']);
        $orgId = ($role === 'student' || $role === 'org_officer') ? sanitizeInput($_POST['org_id']) : null;

        try {
            executeQuery(
                "INSERT INTO users (username, password, full_name, email, role, org_id) 
                VALUES (?, ?, ?, ?, ?, ?)",
                [$username, $password, $fullName, $email, $role, $orgId]
            );
            $_SESSION['success'] = "User created successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error creating user: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_user'])) {
        // Update existing user
        $userId = sanitizeInput($_POST['user_id']);
        $fullName = sanitizeInput($_POST['full_name']);
        $email = sanitizeInput($_POST['email']);
        $role = sanitizeInput($_POST['role']);
        $orgId = ($role === 'student' || $role === 'org_officer') ? sanitizeInput($_POST['org_id']) : null;

        try {
            executeQuery(
                "UPDATE users SET full_name = ?, email = ?, role = ?, org_id = ? WHERE id = ?",
                [$fullName, $email, $role, $orgId, $userId]
            );
            $_SESSION['success'] = "User updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating user: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_user'])) {
        // Delete user
        $userId = sanitizeInput($_POST['user_id']);

        try {
            executeQuery("DELETE FROM users WHERE id = ?", [$userId]);
            $_SESSION['success'] = "User deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
        }
    }
    header("Location: user-management.php");
    exit();
}

// Get all users
$users = executeQuery("SELECT u.*, o.name as org_name FROM users u LEFT JOIN organizations o ON u.org_id = o.id")->fetchAll();

// Get organizations for dropdown
$organizations = executeQuery("SELECT id, name FROM organizations")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | DYCI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'admin-dashboard.php'; ?>

    <div class="ml-64 p-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">User Management</h1>
            <button onclick="document.getElementById('createUserModal').classList.remove('hidden')" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-user-plus mr-2"></i> Add New User
            </button>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Organization</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $user['username']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $user['full_name']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $user['email']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 
                                              ($user['role'] === 'org_officer' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $user['org_name'] ?? 'N/A'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button onclick="openEditModal(
                                        '<?php echo $user['id']; ?>',
                                        '<?php echo $user['username']; ?>',
                                        '<?php echo $user['full_name']; ?>',
                                        '<?php echo $user['email']; ?>',
                                        '<?php echo $user['role']; ?>',
                                        '<?php echo $user['org_id']; ?>'
                                    )" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="confirmDelete('<?php echo $user['id']; ?>')" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div id="createUserModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Create New User</h3>
                <button onclick="document.getElementById('createUserModal').classList.add('hidden')" class="text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" name="username" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" name="full_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Role</label>
                        <select name="role" id="roleSelect" onchange="toggleOrgField()" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="admin">Admin</option>
                            <option value="org_officer">Organization Officer</option>
                            <option value="student">Student</option>
                        </select>
                    </div>
                    <div id="orgField" class="hidden">
                        <label class="block text-sm font-medium text-gray-700">Organization</label>
                        <select name="org_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <?php foreach ($organizations as $org): ?>
                                <option value="<?php echo $org['id']; ?>"><?php echo $org['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('createUserModal').classList.add('hidden')" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" name="create_user" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">
                        Create User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Edit User</h3>
                <button onclick="document.getElementById('editUserModal').classList.add('hidden')" class="text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" id="editUserId">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" id="editUsername" readonly class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" name="full_name" id="editFullName" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" id="editEmail" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Role</label>
                        <select name="role" id="editRole" onchange="toggleEditOrgField()" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="admin">Admin</option>
                            <option value="org_officer">Organization Officer</option>
                            <option value="student">Student</option>
                        </select>
                    </div>
                    <div id="editOrgField" class="hidden">
                        <label class="block text-sm font-medium text-gray-700">Organization</label>
                        <select name="org_id" id="editOrgId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <?php foreach ($organizations as $org): ?>
                                <option value="<?php echo $org['id']; ?>"><?php echo $org['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('editUserModal').classList.add('hidden')" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" name="update_user" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">
                        Update User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="text-center">
                <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                <h3 class="text-lg font-semibold">Confirm Deletion</h3>
                <p class="text-gray-600 mt-2">Are you sure you want to delete this user? This action cannot be undone.</p>
            </div>
            <form method="POST" class="mt-6 flex justify-center space-x-3">
                <input type="hidden" name="user_id" id="deleteUserId">
                <button type="button" onclick="document.getElementById('deleteModal').classList.add('hidden')" 
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" name="delete_user" 
                    class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700">
                    Delete
                </button>
            </form>
        </div>
    </div>

    <script>
        function toggleOrgField() {
            const role = document.getElementById('roleSelect').value;
            const orgField = document.getElementById('orgField');
            orgField.classList.toggle('hidden', role === 'admin');
        }

        function toggleEditOrgField() {
            const role = document.getElementById('editRole').value;
            const orgField = document.getElementById('editOrgField');
            orgField.classList.toggle('hidden', role === 'admin');
        }

        function openEditModal(id, username, fullName, email, role, orgId) {
            document.getElementById('editUserId').value = id;
            document.getElementById('editUsername').value = username;
            document.getElementById('editFullName').value = fullName;
            document.getElementById('editEmail').value = email;
            document.getElementById('editRole').value = role;
            if (orgId) document.getElementById('editOrgId').value = orgId;
            
            // Show/hide org field based on role
            const orgField = document.getElementById('editOrgField');
            orgField.classList.toggle('hidden', role === 'admin');
            
            document.getElementById('editUserModal').classList.remove('hidden');
        }

        function confirmDelete(userId) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteModal').classList.remove('hidden');
        }
    </script>
</body>
</html>