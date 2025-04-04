<?php
require_once 'db-connect.php';
redirectIfNotAdmin();

// Get organization ID from URL
$orgId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get organization details
$org = executeQuery("SELECT name FROM organizations WHERE id = ?", [$orgId])->fetch();

if (!$org) {
    $_SESSION['error'] = "Organization not found";
    header("Location: org-management.php");
    exit();
}

// Handle member actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_member'])) {
        $userId = sanitizeInput($_POST['user_id']);
        $position = sanitizeInput($_POST['position']);

        try {
            executeQuery(
                "UPDATE users SET position = ? WHERE id = ?",
                [$position, $userId]
            );
            $_SESSION['success'] = "Member updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating member: " . $e->getMessage();
        }
    } elseif (isset($_POST['remove_member'])) {
        $userId = sanitizeInput($_POST['user_id']);

        try {
            executeQuery(
                "UPDATE users SET org_id = NULL, position = NULL WHERE id = ?",
                [$userId]
            );
            $_SESSION['success'] = "Member removed from organization!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error removing member: " . $e->getMessage();
        }
    }
    header("Location: org-members.php?id=$orgId");
    exit();
}

// Get organization members
$members = executeQuery("
    SELECT u.id, u.username, u.full_name, u.email, u.role, u.position
    FROM users u
    WHERE u.org_id = ?
    ORDER BY 
        CASE u.role 
            WHEN 'org_officer' THEN 1
            WHEN 'student' THEN 2
            ELSE 3
        END,
        u.full_name ASC
", [$orgId])->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $org['name']; ?> Members | DYCI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'admin-dashboard.php'; ?>

    <div class="ml-64 p-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800"><?php echo $org['name']; ?> Members</h1>
                <p class="text-gray-600">Organization Member Management</p>
            </div>
            <div class="flex space-x-3">
                <a href="org-details.php?id=<?php echo $orgId; ?>" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Organization
                </a>
                <a href="user-management.php?org_id=<?php echo $orgId; ?>&action=create" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-user-plus mr-2"></i> Add Member
                </a>
            </div>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium"><?php echo $member['full_name']; ?></div>
                                    <div class="text-sm text-gray-500">@<?php echo $member['username']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $member['role'] === 'org_officer' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $member['role'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($member['role'] === 'org_officer'): ?>
                                        <form method="POST" class="flex items-center">
                                            <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                                            <input type="text" name="position" value="<?php echo htmlspecialchars($member['position'] ?? ''); ?>" 
                                                class="border-gray-300 rounded-md shadow-sm w-32">
                                            <button type="submit" name="update_member" 
                                                class="ml-2 text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-gray-500">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $member['email']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="user-management.php?edit=<?php echo $member['id']; ?>" 
                                        class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                                        <button type="submit" name="remove_member" 
                                            class="text-red-600 hover:text-red-900"
                                            onclick="return confirm('Are you sure you want to remove this member from the organization?')">
                                            <i class="fas fa-user-minus"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-gray-500">Total Members</h3>
                        <p class="text-2xl font-bold"><?php echo count($members); ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <i class="fas fa-user-tie text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-gray-500">Officers</h3>
                        <p class="text-2xl font-bold">
                            <?php 
                            $officersCount = array_reduce($members, function($count, $member) {
                                return $count + ($member['role'] === 'org_officer' ? 1 : 0);
                            }, 0);
                            echo $officersCount;
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                        <i class="fas fa-user-graduate text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-gray-500">Students</h3>
                        <p class="text-2xl font-bold">
                            <?php 
                            $studentsCount = array_reduce($members, function($count, $member) {
                                return $count + ($member['role'] === 'student' ? 1 : 0);
                            }, 0);
                            echo $studentsCount;
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>