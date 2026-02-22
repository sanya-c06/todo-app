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

if ($task_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid task ID.']);
    exit;
}

try {
    // 1. Delete Task (DELETE part of CRUD)
    // IMPORTANT: Check that the task belongs to the current user ($user_id)
    $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
    $stmt->execute([$task_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Task deleted successfully.',
            'task_id' => $task_id
        ]);
    } else {
        http_response_code(404); // Not Found/Forbidden
        echo json_encode(['success' => false, 'message' => 'Task not found or does not belong to you.']);
    }

} catch (PDOException $e) {
    http_response_code(500); 
    echo json_encode(['success' => false, 'message' => 'Database error: Could not delete task.']);
}
?>