<?php
require_once 'db-connect.php';
redirectIfNotAdmin();

// Get admin details and stats
$admin = executeQuery("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']])->fetch();
$totalUsers = executeQuery("SELECT COUNT(*) as count FROM users")->fetch()['count'];
$totalOrgs = executeQuery("SELECT COUNT(*) as count FROM organizations")->fetch()['count'];
$recentActivities = executeQuery(
    "SELECT a.title, a.date, o.name as org_name 
     FROM activities a 
     JOIN organizations o ON a.org_id = o.id 
     ORDER BY a.date DESC LIMIT 5"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | DYCI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-blue-800 text-white p-4">
            <div class="text-center py-6">
                <h1 class="text-2xl font-bold">DYCI</h1>
                <p class="text-blue-200">Admin Dashboard</p>
            </div>
            <nav class="mt-8">
                <div class="px-2 py-3 bg-blue-700 rounded-lg mb-1">
                    <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                </div>
                <a href="user-management.php" class="block px-2 py-3 hover:bg-blue-700 rounded-lg mb-1">
                    <i class="fas fa-users mr-2"></i> User Management
                </a>
                <a href="org-management.php" class="block px-2 py-3 hover:bg-blue-700 rounded-lg mb-1">
                    <i class="fas fa-sitemap mr-2"></i> Organization Management
                </a>
                <a href="reports.php" class="block px-2 py-3 hover:bg-blue-700 rounded-lg mb-1">
                    <i class="fas fa-chart-bar mr-2"></i> Reports & Analytics
                </a>
                <a href="admin-announcements.php" class="block px-2 py-3 hover:bg-blue-700 rounded-lg mb-1">
                    <i class="fas fa-bullhorn mr-2"></i> Announcements
                </a>
                <a href="security-logs.php" class="block px-2 py-3 hover:bg-blue-700 rounded-lg mb-1">
                    <i class="fas fa-shield-alt mr-2"></i> Security Logs
                </a>
                <a href="logout.php" class="block px-2 py-3 hover:bg-red-600 rounded-lg mt-8">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Welcome, <?php echo $admin['full_name']; ?></h1>
                <div class="text-sm text-gray-500">
                    Last login: <?php 
                    $lastLogin = executeQuery(
                        "SELECT created_at FROM security_logs 
                         WHERE user_id = ? AND action = 'login' 
                         ORDER BY created_at DESC LIMIT 1,1",
                        [$admin['id']]
                    )->fetch()['created_at'];
                    echo $lastLogin ? date('M j, Y g:i A', strtotime($lastLogin)) : 'First login';
                    ?>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500">Total Users</h3>
                            <p class="text-2xl font-bold"><?php echo $totalUsers; ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-sitemap text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500">Organizations</h3>
                            <p class="text-2xl font-bold"><?php echo $totalOrgs; ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-calendar-alt text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500">Upcoming Events</h3>
                            <p class="text-2xl font-bold"><?php echo count($recentActivities); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="bg-white p-6 rounded-lg shadow mb-8">
                <h2 class="text-xl font-semibold mb-4">Recent Activities</h2>
                <div class="space-y-4">
                    <?php foreach ($recentActivities as $activity): ?>
                        <div class="border-b pb-3 last:border-b-0">
                            <h3 class="font-medium"><?php echo $activity['title']; ?></h3>
                            <p class="text-sm text-gray-600">
                                <span class="font-medium"><?php echo $activity['org_name']; ?></span> • 
                                <?php echo date('M j, Y', strtotime($activity['date'])); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($recentActivities)): ?>
                        <p class="text-gray-500">No recent activities found</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-semibold mb-4">Quick Actions</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="user-management.php?action=create" class="p-4 border rounded-lg hover:bg-gray-50 text-center">
                        <i class="fas fa-user-plus text-blue-600 text-2xl mb-2"></i>
                        <p>Add New User</p>
                    </a>
                    <a href="org-management.php?action=create" class="p-4 border rounded-lg hover:bg-gray-50 text-center">
                        <i class="fas fa-plus-circle text-green-600 text-2xl mb-2"></i>
                        <p>Create Organization</p>
                    </a>
                    <a href="admin-announcements.php" class="p-4 border rounded-lg hover:bg-gray-50 text-center">
                        <i class="fas fa-bullhorn text-purple-600 text-2xl mb-2"></i>
                        <p>Send Announcement</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>