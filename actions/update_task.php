<?php
session_start();
require '../includes/db_connect.php'; 
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403); 
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$task_id = (int) ($_POST['task_id'] ?? 0);
$new_description = trim($_POST['new_description'] ?? '');
$is_completed = isset($_POST['is_completed']) ? (int) $_POST['is_completed'] : null; // Can be null if only description is updated

if ($task_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid task ID.']);
    exit;
}

$sql_parts = [];
$execute_params = [];

// Determine which fields to update
if ($is_completed !== null) {
    $sql_parts[] = 'is_completed = ?';
    $execute_params[] = $is_completed;
}

if (!empty($new_description)) {
    $sql_parts[] = 'task_description = ?';
    $execute_params[] = $new_description;
}

if (empty($sql_parts)) {
    echo json_encode(['success' => false, 'message' => 'No fields provided for update.']);
    exit;
}

// Build the SQL query
$sql = 'UPDATE tasks SET ' . implode(', ', $sql_parts) . ' WHERE id = ? AND user_id = ?';
$execute_params[] = $task_id;
$execute_params[] = $user_id;

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($execute_params);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Task updated successfully.',
            'task_id' => $task_id,
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Task not found or no changes made.']);
    }

} catch (PDOException $e) {
    http_response_code(500); 
    echo json_encode(['success' => false, 'message' => 'Database error: Could not update task.']);
}
?>