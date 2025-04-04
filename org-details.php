<?php
require_once 'db-connect.php';
redirectIfNotAdmin();

// Get organization ID from URL
$orgId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get organization details
$org = executeQuery("
    SELECT o.*, u.full_name as created_by_name 
    FROM organizations o 
    LEFT JOIN users u ON o.created_by = u.id
    WHERE o.id = ?
", [$orgId])->fetch();

if (!$org) {
    $_SESSION['error'] = "Organization not found";
    header("Location: org-management.php");
    exit();
}

// Get organization members
$members = executeQuery("
    SELECT u.id, u.username, u.full_name, u.email, u.role, u.position
    FROM users u
    WHERE u.org_id = ?
    ORDER BY u.role DESC, u.full_name ASC
", [$orgId])->fetchAll();

// Get organization activities
$activities = executeQuery("
    SELECT a.id, a.title, a.date, a.time, a.location, 
           COUNT(at.student_id) as attendance_count
    FROM activities a
    LEFT JOIN attendance at ON a.id = at.activity_id AND at.status = 'present'
    WHERE a.org_id = ?
    GROUP BY a.id
    ORDER BY a.date DESC
    LIMIT 5
", [$orgId])->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $org['name']; ?> | DYCI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'admin-dashboard.php'; ?>

    <div class="ml-64 p-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800"><?php echo $org['name']; ?></h1>
                <p class="text-gray-600">Organization Details</p>
            </div>
            <a href="org-management.php" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-1"></i> Back to Organizations
            </a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Organization Info Card -->
            <div class="bg-white p-6 rounded-lg shadow col-span-1">
                <div class="flex items-center mb-4">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-sitemap text-2xl"></i>
                    </div>
                    <h2 class="text-xl font-semibold">Organization Information</h2>
                </div>
                <div class="space-y-3">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Description</h3>
                        <p class="mt-1 text-gray-800"><?php echo $org['description']; ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Mission</h3>
                        <p class="mt-1 text-gray-800"><?php echo $org['mission']; ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Adviser</h3>
                        <p class="mt-1 text-gray-800"><?php echo $org['adviser']; ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Contact Email</h3>
                        <p class="mt-1 text-gray-800"><?php echo $org['contact_email']; ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Created By</h3>
                        <p class="mt-1 text-gray-800"><?php echo $org['created_by_name']; ?></p>
                    </div>
                </div>
                <div class="mt-6 flex space-x-3">
                    <button onclick="document.getElementById('editOrgModal').classList.remove('hidden')" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">
                        <i class="fas fa-edit mr-1"></i> Edit
                    </button>
                    <button onclick="confirmDelete('<?php echo $org['id']; ?>')" 
                        class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700">
                        <i class="fas fa-trash-alt mr-1"></i> Delete
                    </button>
                </div>
            </div>

            <!-- Members Card -->
            <div class="bg-white p-6 rounded-lg shadow col-span-1">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                        <h2 class="text-xl font-semibold">Members (<?php echo count($members); ?>)</h2>
                    </div>
                    <a href="org-members.php?id=<?php echo $org['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm">
                        View All
                    </a>
                </div>
                <div class="space-y-3">
                    <?php foreach (array_slice($members, 0, 5) as $member): ?>
                        <div class="flex items-center justify-between py-2 border-b last:border-b-0">
                            <div>
                                <h3 class="font-medium"><?php echo $member['full_name']; ?></h3>
                                <p class="text-sm text-gray-600">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $member['role'] === 'org_officer' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $member['role'])); ?>
                                    </span>
                                    <?php if ($member['position']): ?>
                                        <span class="ml-2 text-gray-500"><?php echo $member['position']; ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <a href="user-management.php?edit=<?php echo $member['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($members)): ?>
                        <p class="text-gray-500">No members found</p>
                    <?php endif; ?>
                </div>
                <div class="mt-4">
                    <a href="user-management.php?org_id=<?php echo $org['id']; ?>&action=create" 
                        class="block text-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <i class="fas fa-user-plus mr-1"></i> Add Member
                    </a>
                </div>
            </div>

            <!-- Recent Activities Card -->
            <div class="bg-white p-6 rounded-lg shadow col-span-1">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                            <i class="fas fa-calendar-alt text-2xl"></i>
                        </div>
                        <h2 class="text-xl font-semibold">Recent Activities</h2>
                    </div>
                    <a href="org-activities.php?id=<?php echo $org['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm">
                        View All
                    </a>
                </div>
                <div class="space-y-3">
                    <?php foreach ($activities as $activity): ?>
                        <div class="py-2 border-b last:border-b-0">
                            <h3 class="font-medium"><?php echo $activity['title']; ?></h3>
                            <p class="text-sm text-gray-600">
                                <?php echo date('M j, Y', strtotime($activity['date'])); ?> at <?php echo date('g:i A', strtotime($activity['time'])); ?>
                            </p>
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-map-marker-alt mr-1"></i> <?php echo $activity['location']; ?>
                            </p>
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-users mr-1"></i> <?php echo $activity['attendance_count']; ?> attendees
                            </p>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($activities)): ?>
                        <p class="text-gray-500">No recent activities found</p>
                    <?php endif; ?>
                </div>
                <div class="mt-4">
                    <a href="activity-scheduler.php?org_id=<?php echo $org['id']; ?>" 
                        class="block text-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <i class="fas fa-plus-circle mr-1"></i> Schedule Activity
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Section -->
        <div class="bg-white p-6 rounded-lg shadow mb-8">
            <h2 class="text-xl font-semibold mb-4">Organization Statistics</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center p-4 border rounded-lg">
                    <div class="text-3xl font-bold text-blue-600 mb-2">
                        <?php echo count($members); ?>
                    </div>
                    <div class="text-gray-600">Total Members</div>
                </div>
                <div class="text-center p-4 border rounded-lg">
                    <div class="text-3xl font-bold text-green-600 mb-2">
                        <?php 
                        $officersCount = executeQuery("
                            SELECT COUNT(*) as count 
                            FROM users 
                            WHERE org_id = ? AND role = 'org_officer'
                        ", [$orgId])->fetch()['count'];
                        echo $officersCount;
                        ?>
                    </div>
                    <div class="text-gray-600">Officers</div>
                </div>
                <div class="text-center p-4 border rounded-lg">
                    <div class="text-3xl font-bold text-purple-600 mb-2">
                        <?php 
                        $activitiesCount = executeQuery("
                            SELECT COUNT(*) as count 
                            FROM activities 
                            WHERE org_id = ?
                        ", [$orgId])->fetch()['count'];
                        echo $activitiesCount;
                        ?>
                    </div>
                    <div class="text-gray-600">Activities</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Organization Modal (same as in org-management.php) -->
    <div id="editOrgModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Edit Organization</h3>
                <button onclick="document.getElementById('editOrgModal').classList.add('hidden')" class="text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="org-management.php">
                <input type="hidden" name="org_id" value="<?php echo $org['id']; ?>">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($org['name']); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"><?php echo htmlspecialchars($org['description']); ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Mission</label>
                        <textarea name="mission" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"><?php echo htmlspecialchars($org['mission']); ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Adviser</label>
                        <input type="text" name="adviser" value="<?php echo htmlspecialchars($org['adviser']); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Contact Email</label>
                        <input type="email" name="contact_email" value="<?php echo htmlspecialchars($org['contact_email']); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('editOrgModal').classList.add('hidden')" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" name="update_org" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">
                        Update Organization
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal (same as in org-management.php) -->
    <div id="deleteOrgModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="text-center">
                <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                <h3 class="text-lg font-semibold">Confirm Deletion</h3>
                <p class="text-gray-600 mt-2">Are you sure you want to delete this organization? This action cannot be undone.</p>
            </div>
            <form method="POST" action="org-management.php" class="mt-6 flex justify-center space-x-3">
                <input type="hidden" name="org_id" value="<?php echo $org['id']; ?>">
                <button type="button" onclick="document.getElementById('deleteOrgModal').classList.add('hidden')" 
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" name="delete_org" 
                    class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700">
                    Delete
                </button>
            </form>
        </div>
    </div>

    <script>
        function confirmDelete(orgId) {
            document.getElementById('deleteOrgModal').classList.remove('hidden');
        }
    </script>
</body>
</html>