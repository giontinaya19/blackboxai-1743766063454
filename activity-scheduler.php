<?php
require_once 'db-connect.php';

// Only admins and org officers can access this page
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'org_officer')) {
    header("Location: unauthorized.php");
    exit();
}

// Get organization ID - for admins from URL, for officers from session
$orgId = $_SESSION['role'] === 'admin' ? 
    (isset($_GET['org_id']) ? (int)$_GET['org_id'] : 0) : 
    $_SESSION['org_id'];

// Check if organization exists
$org = executeQuery("SELECT name FROM organizations WHERE id = ?", [$orgId])->fetch();
if (!$org) {
    $_SESSION['error'] = "Organization not found";
    header("Location: " . ($_SESSION['role'] === 'admin' ? "org-management.php" : "org-officer-dashboard.php"));
    exit();
}

// Check if editing existing activity
$isEdit = isset($_GET['edit']);
$activity = null;
if ($isEdit) {
    $activityId = (int)$_GET['edit'];
    $activity = executeQuery("
        SELECT * FROM activities 
        WHERE id = ? AND org_id = ?
    ", [$activityId, $orgId])->fetch();
    
    if (!$activity) {
        $_SESSION['error'] = "Activity not found or you don't have permission to edit it";
        header("Location: org-activities.php?id=$orgId");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $date = sanitizeInput($_POST['date']);
    $time = sanitizeInput($_POST['time']);
    $location = sanitizeInput($_POST['location']);

    // Validate required fields
    if (empty($title) || empty($date) || empty($time) || empty($location)) {
        $_SESSION['error'] = "Please fill all required fields";
    } else {
        try {
            if ($isEdit) {
                // Update existing activity
                executeQuery("
                    UPDATE activities SET 
                        title = ?, 
                        description = ?, 
                        date = ?, 
                        time = ?, 
                        location = ?
                    WHERE id = ?
                ", [$title, $description, $date, $time, $location, $activityId]);
                
                $_SESSION['success'] = "Activity updated successfully!";
            } else {
                // Create new activity
                executeQuery("
                    INSERT INTO activities (org_id, title, description, date, time, location, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ", [$orgId, $title, $description, $date, $time, $location, $_SESSION['user_id']]);
                
                $_SESSION['success'] = "Activity scheduled successfully!";
            }
            
            header("Location: org-activities.php?id=$orgId");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error saving activity: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit' : 'Schedule'; ?> Activity | DYCI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include ($_SESSION['role'] === 'admin' ? 'admin-dashboard.php' : 'org-officer-dashboard.php'); ?>

    <div class="ml-64 p-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800"><?php echo $isEdit ? 'Edit' : 'Schedule'; ?> Activity</h1>
                <p class="text-gray-600"><?php echo $org['name']; ?></p>
            </div>
            <a href="org-activities.php?id=<?php echo $orgId; ?>" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-1"></i> Back to Activities
            </a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-lg shadow">
            <form method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Activity Title *</label>
                        <input type="text" name="title" required 
                            value="<?php echo $isEdit ? htmlspecialchars($activity['title']) : ''; ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" rows="3" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"><?php 
                            echo $isEdit ? htmlspecialchars($activity['description']) : ''; 
                        ?></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date *</label>
                        <input type="date" name="date" required 
                            value="<?php echo $isEdit ? htmlspecialchars($activity['date']) : ''; ?>"
                            min="<?php echo date('Y-m-d'); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Time *</label>
                        <input type="time" name="time" required 
                            value="<?php echo $isEdit ? htmlspecialchars(substr($activity['time'], 0, 5)) : ''; ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Location *</label>
                        <input type="text" name="location" required 
                            value="<?php echo $isEdit ? htmlspecialchars($activity['location']) : ''; ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="mt-8 flex justify-end">
                    <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <?php echo $isEdit ? 'Update Activity' : 'Schedule Activity'; ?>
                    </button>
                </div>
            </form>
        </div>

        <?php if ($isEdit): ?>
            <!-- Attendance Management Section -->
            <div class="bg-white p-6 rounded-lg shadow mt-8">
                <h2 class="text-xl font-semibold mb-4">Attendance Management</h2>
                
                <div class="mb-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Add Attendees</h3>
                    <div class="flex">
                        <select id="studentSelect" class="block w-full rounded-l-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select a student</option>
                            <?php
                            $students = executeQuery("
                                SELECT id, full_name 
                                FROM users 
                                WHERE org_id = ? AND role = 'student'
                                ORDER BY full_name
                            ", [$orgId])->fetchAll();
                            
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
                            class="px-4 py-2 bg-green-600 text-white rounded-r-md text-sm font-medium hover:bg-green-700">
                            Mark Present
                        </button>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="attendanceList">
                            <?php
                            $attendees = executeQuery("
                                SELECT u.id, u.full_name, a.status, a.attended_at
                                FROM attendance a
                                JOIN users u ON a.student_id = u.id
                                WHERE a.activity_id = ?
                                ORDER BY u.full_name
                            ", [$activityId])->fetchAll();
                            
                            foreach ($attendees as $attendee): ?>
                                <tr data-student-id="<?php echo $attendee['id']; ?>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($attendee['full_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $attendee['status'] === 'present' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo ucfirst($attendee['status']); ?>
                                        </span>
                                        <?php if ($attendee['attended_at']): ?>
                                            <div class="text-xs text-gray-500">
                                                <?php echo date('M j, g:i A', strtotime($attendee['attended_at'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($attendee['status'] === 'present'): ?>
                                            <button onclick="updateAttendance(<?php echo $attendee['id']; ?>, 'absent')" 
                                                class="text-red-600 hover:text-red-900">
                                                Mark Absent
                                            </button>
                                        <?php else: ?>
                                            <button onclick="updateAttendance(<?php echo $attendee['id']; ?>, 'present')" 
                                                class="text-green-600 hover:text-green-900">
                                                Mark Present
                                            </button>
                                        <?php endif; ?>
                                        <button onclick="removeAttendance(<?php echo $attendee['id']; ?>)" 
                                            class="text-gray-600 hover:text-gray-900 ml-4">
                                            Remove
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($attendees)): ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-center text-gray-500">
                                        No attendance records yet
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
        <?php endif; ?>
    </div>
</body>
</html>