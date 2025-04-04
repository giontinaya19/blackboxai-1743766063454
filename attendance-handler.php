<?php
require_once 'db-connect.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'org_officer')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get input data
$activityId = isset($_POST['activity_id']) ? (int)$_POST['activity_id'] : 0;
$studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
$status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : '';
$action = isset($_POST['action']) ? sanitizeInput($_POST['action']) : '';

// Validate inputs
if ($activityId <= 0 || $studentId <= 0 || !in_array($action, ['add', 'update', 'remove'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

// Check if the activity belongs to the user's organization
$activityOrg = executeQuery("
    SELECT org_id FROM activities WHERE id = ?
", [$activityId])->fetch();

if (!$activityOrg) {
    echo json_encode(['success' => false, 'message' => 'Activity not found']);
    exit();
}

// For org officers, verify they belong to the same org
if ($_SESSION['role'] === 'org_officer' && $_SESSION['org_id'] != $activityOrg['org_id']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized for this organization']);
    exit();
}

// Check if student belongs to the organization
$studentOrg = executeQuery("
    SELECT org_id FROM users WHERE id = ? AND role = 'student'
", [$studentId])->fetch();

if (!$studentOrg || $studentOrg['org_id'] != $activityOrg['org_id']) {
    echo json_encode(['success' => false, 'message' => 'Student not in organization']);
    exit();
}

try {
    if ($action === 'add' || $action === 'update') {
        if (!in_array($status, ['present', 'absent'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit();
        }

        // Check if attendance record already exists
        $existing = executeQuery("
            SELECT id FROM attendance 
            WHERE activity_id = ? AND student_id = ?
        ", [$activityId, $studentId])->fetch();

        if ($existing) {
            // Update existing record
            executeQuery("
                UPDATE attendance SET 
                    status = ?,
                    attended_at = CASE WHEN ? = 'present' THEN CURRENT_TIMESTAMP ELSE NULL END
                WHERE id = ?
            ", [$status, $status, $existing['id']]);
        } else {
            // Create new record
            executeQuery("
                INSERT INTO attendance (activity_id, student_id, status, attended_at)
                VALUES (?, ?, ?, CASE WHEN ? = 'present' THEN CURRENT_TIMESTAMP ELSE NULL END)
            ", [$activityId, $studentId, $status, $status]);
        }
    } elseif ($action === 'remove') {
        // Remove attendance record
        executeQuery("
            DELETE FROM attendance 
            WHERE activity_id = ? AND student_id = ?
        ", [$activityId, $studentId]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}