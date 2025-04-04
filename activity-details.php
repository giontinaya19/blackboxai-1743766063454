<?php
require_once 'db-connect.php';

// Only admins and org officers can access this page
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'org_officer')) {
    header("Location: unauthorized.php");
    exit();
}

// Get activity ID from URL
$activityId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get activity details
$activity = executeQuery("
    SELECT a.*, o.name as org_name 
    FROM activities a
    JOIN organizations o ON a.org_id = o.id
    WHERE a.id = ?
", [$activityId])->fetch();

if (!$activity) {
    $_SESSION['error'] = "Activity not found";
    header("Location: " . ($_SESSION['role'] === 'admin' ? "org-management.php" : "org-officer-dashboard.php"));
    exit();
}

// For org officers, verify they belong to the same org
if ($_SESSION['role'] === 'org_officer' && $_SESSION['org_id'] != $activity['org_id']) {
    header("Location: unauthorized.php");
    exit();
}

// Get attendance records
$attendance = executeQuery("
    SELECT u.id, u.full_name, u.email, 
           a.status, a.attended_at
    FROM attendance a
    JOIN users u ON a.student_id = u.id
    WHERE a.activity_id = ?
    ORDER BY u.full_name
", [$activityId])->fetchAll();

// Get total members count for attendance percentage
$totalMembers = executeQuery("
    SELECT COUNT(*) as count 
    FROM users 
    WHERE org_id = ? AND role = 'student'
", [$activity['org_id']])->fetch()['count'];

$presentCount = array_reduce($attendance, function($count, $record) {
    return $count + ($record['status'] === 'present' ? 1 : 0);
}, 0);

$attendancePercentage = $totalMembers > 0 ? round(($presentCount / $totalMembers) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $activity['title']; ?> | DYCI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include ($_SESSION['role'] === 'admin' ? 'admin-dashboard.php' : 'org-officer-dashboard.php'); ?>

    <div class="ml-64 p-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800"><?php echo $activity['title']; ?></h1>
                <p class="text-gray-600"><?php echo $activity['org_name']; ?></p>
            </div>
            <a href="org-activities.php?id=<?php echo $activity['org_id']; ?>" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-1"></i> Back to Activities
            </a>
        </div>

        <!-- Activity Details Card -->
        <div class="bg-white p-6 rounded-lg shadow mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h2 class="text-lg font-semibold mb-2">Activity Information</h2>
                    <div class="space-y-3">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Date & Time</h3>
                            <p>
                                <?php echo date('F j, Y', strtotime($activity['date'])); ?> 
                                at <?php echo date('g:i A', strtotime($activity['time'])); ?>
                            </p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Location</h3>
                            <p><?php echo $activity['location']; ?></p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Description</h3>
                            <p class="whitespace-pre-line"><?php echo $activity['description']; ?></p>
                        </div>
                    </div>
                </div>
                <div>
                    <h2 class="text-lg font-semibold mb-2">Attendance Summary</h2>
                    <div class="space-y-4">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700">Attendance Rate</span>
                                <span class="text-sm font-medium"><?php echo $attendancePercentage; ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full" 
                                    style="width: <?php echo $attendancePercentage; ?>%"></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-blue-50 p-3 rounded-lg">
                                <div class="text-2xl font-bold text-blue-600"><?php echo $presentCount; ?></div>
                                <div class="text-sm text-gray-600">Present</div>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <div class="text-2xl font-bold text-gray-600"><?php echo count($attendance) - $presentCount; ?></div>
                                <div class="text-sm text-gray-600">Absent</div>
                            </div>
                            <div class="bg-green-50 p-3 rounded-lg">
                                <div class="text-2xl font-bold text-green-600"><?php echo count($attendance); ?></div>
                                <div class="text-sm text-gray-600">Recorded</div>
                            </div>
                            <div class="bg-purple-50 p-3 rounded-lg">
                                <div class="text-2xl font-bold text-purple-600"><?php echo $totalMembers; ?></div>
                                <div class="text-sm text-gray-600">Total Members</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Records -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-semibold">Attendance Records</h2>
                <a href="export-attendance.php?activity_id=<?php echo $activityId; ?>" 
                    class="text-blue-600 hover:text-blue-800 text-sm">
                    <i class="fas fa-download mr-1"></i> Export
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Recorded</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($attendance as $record): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium"><?php echo $record['full_name']; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo $record['email']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $record['status'] === 'present' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo ucfirst($record['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($record['attended_at']): ?>
                                        <?php echo date('M j, g:i A', strtotime($record['attended_at'])); ?>
                                    <?php else: ?>
                                        <span class="text-gray-400">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($record['status'] === 'present'): ?>
                                        <button onclick="updateAttendance(<?php echo $record['id']; ?>, 'absent')" 
                                            class="text-red-600 hover:text-red-900 mr-3">
                                            Mark Absent
                                        </button>
                                    <?php else: ?>
                                        <button onclick="updateAttendance(<?php echo $record['id']; ?>, 'present')" 
                                            class="text-green-600 hover:text-green-900 mr-3">
                                            Mark Present
                                        </button>
                                    <?php endif; ?>
                                    <button onclick="removeAttendance(<?php echo $record['id']; ?>)" 
                                        class="text-gray-600 hover:text-gray-900">
                                        Remove
                                        </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($attendance)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                    No attendance records yet
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Students Section -->
        <div class="bg-white p-6 rounded-lg shadow mt-8">
            <h2 class="text-lg font-semibold mb-4">Add Students to Attendance</h2>
            <div class="mb-4">
                <div class="flex">
                    <select id="studentSelect" class="block w-full rounded-l-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select a student</option>
                        <?php
                        $students = executeQuery("
                            SELECT id, full_name 
                            FROM users 
                            WHERE org_id = ? AND role = 'student'
                            ORDER BY full_name
                        ", [$activity['org_id']])->fetchAll();
                        
                        foreach ($students as $student): 
                            $isAttended = executeQuery("
                                SELECT COUNT(*) as count 
                                FROM attendance 
                                WHERE activity_id = ? AND student_id = ?
                            ", [$activityId, $student['id']])->fetch()['count'] > 0;
                            
                            if (!$isAttended): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['full_name']); ?>
                                </option>
                            <?php endif;
                        endforeach; ?>
                    </select>
                    <button onclick="markAttendance('present')" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-r-md text-sm font-medium hover:bg-blue-700">
                        Add
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function markAttendance(status) {
            const studentId = document.getElementById('studentSelect').value;
            if (!studentId) return;

            fetch('attendance-handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `activity_id=<?php echo $activityId; ?>&student_id=${studentId}&status=${status}&action=add`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error updating attendance');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        }

        function updateAttendance(studentId, status) {
            fetch('attendance-handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `activity_id=<?php echo $activityId; ?>&student_id=${studentId}&status=${status}&action=update`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error updating attendance');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        }

        function removeAttendance(studentId) {
            if (!confirm('Are you sure you want to remove this attendance record?')) return;

            fetch('attendance-handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `activity_id=<?php echo $activityId; ?>&student_id=${studentId}&action=remove`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error removing attendance');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        }
    </script>
</body>
</html>