<?php
require_once 'db-connect.php';
redirectIfNotOrgOfficer();

// Get organization details
$org = executeQuery("SELECT * FROM organizations WHERE id = ?", [$_SESSION['org_id']])->fetch();

// Get organization stats
$memberCount = executeQuery("
    SELECT COUNT(*) as count 
    FROM users 
    WHERE org_id = ? AND role = 'student'
", [$_SESSION['org_id']])->fetch()['count'];

$officerCount = executeQuery("
    SELECT COUNT(*) as count 
    FROM users 
    WHERE org_id = ? AND role = 'org_officer'
", [$_SESSION['org_id']])->fetch()['count'];

$upcomingActivities = executeQuery("
    SELECT a.id, a.title, a.date, a.time, a.location
    FROM activities a
    WHERE a.org_id = ? AND a.date >= CURDATE()
    ORDER BY a.date ASC, a.time ASC
    LIMIT 5
", [$_SESSION['org_id']])->fetchAll();

$recentActivities = executeQuery("
    SELECT a.id, a.title, a.date, a.time, 
           COUNT(at.student_id) as attendance_count
    FROM activities a
    LEFT JOIN attendance at ON a.id = at.activity_id AND at.status = 'present'
    WHERE a.org_id = ?
    GROUP BY a.id
    ORDER BY a.date DESC
    LIMIT 5
", [$_SESSION['org_id']])->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Officer Dashboard | DYCI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-blue-800 text-white p-4">
            <div class="text-center py-6">
                <h1 class="text-2xl font-bold">DYCI</h1>
                <p class="text-blue-200">Organization Officer</p>
            </div>
            <div class="px-4 py-3 mb-4 border-t border-blue-700">
                <div class="font-medium"><?php echo $org['name']; ?></div>
                <div class="text-sm text-blue-200"><?php echo $_SESSION['full_name']; ?></div>
            </div>
            <nav class="mt-4">
                <div class="px-2 py-3 bg-blue-700 rounded-lg mb-1">
                    <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                </div>
                <a href="org-members.php?id=<?php echo $_SESSION['org_id']; ?>" class="block px-2 py-3 hover:bg-blue-700 rounded-lg mb-1">
                    <i class="fas fa-users mr-2"></i> Members
                </a>
                <a href="org-activities.php?id=<?php echo $_SESSION['org_id']; ?>" class="block px-2 py-3 hover:bg-blue-700 rounded-lg mb-1">
                    <i class="fas fa-calendar-alt mr-2"></i> Activities
                </a>
                <a href="activity-scheduler.php" class="block px-2 py-3 hover:bg-blue-700 rounded-lg mb-1">
                    <i class="fas fa-plus-circle mr-2"></i> Schedule Activity
                </a>
                <a href="org-reports.php?id=<?php echo $_SESSION['org_id']; ?>" class="block px-2 py-3 hover:bg-blue-700 rounded-lg mb-1">
                    <i class="fas fa-chart-bar mr-2"></i> Reports
                </a>
                <a href="org-settings.php?id=<?php echo $_SESSION['org_id']; ?>" class="block px-2 py-3 hover:bg-blue-700 rounded-lg mb-1">
                    <i class="fas fa-cog mr-2"></i> Settings
                </a>
                <a href="logout.php" class="block px-2 py-3 hover:bg-red-600 rounded-lg mt-8">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Welcome, <?php echo $_SESSION['full_name']; ?></h1>
                <div class="text-sm text-gray-500">
                    Last login: <?php 
                    $lastLogin = executeQuery(
                        "SELECT created_at FROM security_logs 
                         WHERE user_id = ? AND action = 'login' 
                         ORDER BY created_at DESC LIMIT 1,1",
                        [$_SESSION['user_id']]
                    )->fetch()['created_at'];
                    echo $lastLogin ? date('M j, Y g:i A', strtotime($lastLogin)) : 'First login';
                    ?>
                </div>
            </div>

            <!-- Organization Info -->
            <div class="bg-white p-6 rounded-lg shadow mb-8">
                <h2 class="text-xl font-semibold mb-4">Organization Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Name</h3>
                        <p><?php echo $org['name']; ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Adviser</h3>
                        <p><?php echo $org['adviser']; ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Contact Email</h3>
                        <p><?php echo $org['contact_email']; ?></p>
                    </div>
                    <div class="col-span-3">
                        <h3 class="text-sm font-medium text-gray-500">Mission</h3>
                        <p class="whitespace-pre-line"><?php echo $org['mission']; ?></p>
                    </div>
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
                            <h3 class="text-gray-500">Total Members</h3>
                            <p class="text-2xl font-bold"><?php echo $memberCount; ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-user-tie text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500">Officers</h3>
                            <p class="text-2xl font-bold"><?php echo $officerCount; ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-calendar-check text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500">Upcoming Activities</h3>
                            <p class="text-2xl font-bold"><?php echo count($upcomingActivities); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Activities -->
            <div class="bg-white p-6 rounded-lg shadow mb-8">
                <h2 class="text-xl font-semibold mb-4">Upcoming Activities</h2>
                <div class="space-y-4">
                    <?php foreach ($upcomingActivities as $activity): ?>
                        <div class="border-b pb-4 last:border-b-0">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-medium"><?php echo $activity['title']; ?></h3>
                                    <p class="text-sm text-gray-600">
                                        <?php echo date('M j, Y', strtotime($activity['date'])); ?> at <?php echo date('g:i A', strtotime($activity['time'])); ?>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-map-marker-alt mr-1"></i> <?php echo $activity['location']; ?>
                                    </p>
                                </div>
                                <div>
                                    <a href="activity-details.php?id=<?php echo $activity['id']; ?>" 
                                        class="text-blue-600 hover:text-blue-800 text-sm">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($upcomingActivities)): ?>
                        <p class="text-gray-500">No upcoming activities scheduled</p>
                    <?php endif; ?>
                </div>
                <div class="mt-4">
                    <a href="activity-scheduler.php" 
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <i class="fas fa-plus-circle mr-2"></i> Schedule New Activity
                    </a>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-semibold mb-4">Recent Activities</h2>
                <div class="space-y-4">
                    <?php foreach ($recentActivities as $activity): ?>
                        <div class="border-b pb-4 last:border-b-0">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-medium"><?php echo $activity['title']; ?></h3>
                                    <p class="text-sm text-gray-600">
                                        <?php echo date('M j, Y', strtotime($activity['date'])); ?>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-users mr-1"></i> <?php echo $activity['attendance_count']; ?> attended
                                    </p>
                                </div>
                                <div>
                                    <a href="activity-details.php?id=<?php echo $activity['id']; ?>" 
                                        class="text-blue-600 hover:text-blue-800 text-sm">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($recentActivities)): ?>
                        <p class="text-gray-500">No recent activities found</p>
                    <?php endif; ?>
                </div>
                <div class="mt-4">
                    <a href="org-activities.php?id=<?php echo $_SESSION['org_id']; ?>" 
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <i class="fas fa-list mr-2"></i> View All Activities
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>