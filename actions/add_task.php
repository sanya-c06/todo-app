<?php
session_start();
require '../includes/db_connect.php'; // Note the use of ../ to go up one directory

// Set content type header to JSON
header('Content-Type: application/json');

// 1. Basic Security Check
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$task_description = trim($_POST['task_description'] ?? '');

if (empty($task_description)) {
    echo json_encode(['success' => false, 'message' => 'Task description cannot be empty.']);
    exit;
}

try {
    // 2. Insert Task (CREATE part of CRUD)
    $stmt = $pdo->prepare('INSERT INTO tasks (user_id, task_description) VALUES (?, ?)');
    $stmt->execute([$user_id, $task_description]);
    
    // Get the ID of the newly inserted task
    $new_task_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Task added successfully.',
        'task' => [
            'id' => $new_task_id,
            'description' => htmlspecialchars($task_description),
            'is_completed' => 0
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500); // Server error
    echo json_encode(['success' => false, 'message' => 'Database error: Could not add task.']);
}
?>