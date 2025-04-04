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

// Handle activity actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_activity'])) {
        $activityId = sanitizeInput($_POST['activity_id']);

        try {
            // First delete attendance records
            executeQuery("DELETE FROM attendance WHERE activity_id = ?", [$activityId]);
            // Then delete the activity
            executeQuery("DELETE FROM activities WHERE id = ?", [$activityId]);
            $_SESSION['success'] = "Activity deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error deleting activity: " . $e->getMessage();
        }
        header("Location: org-activities.php?id=$orgId");
        exit();
    }
}

// Get organization activities with attendance count
$activities = executeQuery("
    SELECT a.id, a.title, a.description, a.date, a.time, a.location, 
           COUNT(at.student_id) as attendance_count
    FROM activities a
    LEFT JOIN attendance at ON a.id = at.activity_id AND at.status = 'present'
    WHERE a.org_id = ?
    GROUP BY a.id
    ORDER BY a.date DESC, a.time DESC
", [$orgId])->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $org['name']; ?> Activities | DYCI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'admin-dashboard.php'; ?>

    <div class="ml-64 p-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800"><?php echo $org['name']; ?> Activities</h1>
                <p class="text-gray-600">Organization Activity Management</p>
            </div>
            <div class="flex space-x-3">
                <a href="org-details.php?id=<?php echo $orgId; ?>" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Organization
                </a>
                <a href="activity-scheduler.php?org_id=<?php echo $orgId; ?>" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-plus-circle mr-2"></i> Schedule Activity
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attendance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($activities as $activity): ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="font-medium"><?php echo $activity['title']; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo substr($activity['description'], 0, 50) . '...'; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div><?php echo date('M j, Y', strtotime($activity['date'])); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo date('g:i A', strtotime($activity['time'])); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $activity['location']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <?php echo $activity['attendance_count']; ?> attended
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="activity-details.php?id=<?php echo $activity['id']; ?>" 
                                        class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="activity-scheduler.php?edit=<?php echo $activity['id']; ?>" 
                                        class="text-green-600 hover:text-green-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="activity_id" value="<?php echo $activity['id']; ?>">
                                        <button type="submit" name="delete_activity" 
                                            class="text-red-600 hover:text-red-900"
                                            onclick="return confirm('Are you sure you want to delete this activity? All attendance records will also be deleted.')">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($activities)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    No activities found. Schedule your first activity!
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-calendar-alt text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-gray-500">Total Activities</h3>
                        <p class="text-2xl font-bold"><?php echo count($activities); ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-gray-500">Total Attendance</h3>
                        <p class="text-2xl font-bold">
                            <?php 
                            $totalAttendance = array_reduce($activities, function($count, $activity) {
                                return $count + $activity['attendance_count'];
                            }, 0);
                            echo $totalAttendance;
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                        <i class="fas fa-user-check text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-gray-500">Avg. Attendance</h3>
                        <p class="text-2xl font-bold">
                            <?php 
                            $avgAttendance = count($activities) > 0 ? round($totalAttendance / count($activities), 1) : 0;
                            echo $avgAttendance;
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>