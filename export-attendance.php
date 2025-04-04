<?php
require_once 'db-connect.php';

// Only admins and org officers can access this page
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'org_officer')) {
    header("Location: unauthorized.php");
    exit();
}

// Get activity ID from URL
$activityId = isset($_GET['activity_id']) ? (int)$_GET['activity_id'] : 0;

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
    SELECT u.full_name, u.email, u.position,
           a.status, a.attended_at
    FROM attendance a
    JOIN users u ON a.student_id = u.id
    WHERE a.activity_id = ?
    ORDER BY a.status DESC, u.full_name ASC
", [$activityId])->fetchAll();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=attendance_' . preg_replace('/[^a-z0-9]+/i', '_', $activity['title']) . '_' . date('Y-m-d') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Write CSV header
fputcsv($output, [
    'Organization: ' . $activity['org_name'],
    'Activity: ' . $activity['title'],
    'Date: ' . $activity['date'],
    'Location: ' . $activity['location']
]);
fputcsv($output, []); // Empty row
fputcsv($output, ['Student Name', 'Email', 'Position', 'Status', 'Time Recorded']);

// Write attendance data
foreach ($attendance as $record) {
    fputcsv($output, [
        $record['full_name'],
        $record['email'],
        $record['position'],
        ucfirst($record['status']),
        $record['attended_at'] ? date('M j, Y g:i A', strtotime($record['attended_at'])) : 'N/A'
    ]);
}

// Write summary
fputcsv($output, []);
fputcsv($output, ['Total Present:', array_reduce($attendance, function($count, $record) {
    return $count + ($record['status'] === 'present' ? 1 : 0);
}, 0)]);
fputcsv($output, ['Total Absent:', array_reduce($attendance, function($count, $record) {
    return $count + ($record['status'] === 'absent' ? 1 : 0);
}, 0)]);
fputcsv($output, ['Total Recorded:', count($attendance)]);

fclose($output);
exit();