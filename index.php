<?php
session_start();
require 'includes/db_connect.php'; 

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$tasks = [];

// 2. Fetch Tasks (READ part of CRUD)
try {
    $stmt = $pdo->prepare('SELECT id, task_description, is_completed FROM tasks WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$current_user_id]);
    $tasks = $stmt->fetchAll();
} catch (PDOException $e) {
    // Handle database read error
    // error_log("Task fetching failed: " . $e->getMessage());
    $tasks = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My To-Do List</title>
    <link rel="stylesheet" href="css/style.css"> 
</head>
<body>
    <div class="header">
        <h1>Welcome, <?= htmlspecialchars($username) ?>!</h1>
        <a href="actions/logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="container">
        <form id="addTaskForm" method="POST">
            <input type="text" id="taskInput" name="task_description" placeholder="What needs to be done?" required>
            <button type="submit">Add Task</button>
        </form>

        <hr>

        <ul id="taskList">
            <?php if (empty($tasks)): ?>
                <li class="empty-list">You have no tasks yet!</li>
            <?php endif; ?>

            <?php foreach ($tasks as $task): 
                $status_class = $task['is_completed'] ? 'completed' : '';
            ?>
                <li class="task-item <?= $status_class ?>" data-task-id="<?= $task['id'] ?>">
                    <input type="checkbox" 
                        class="task-complete-toggle" 
                        <?= $task['is_completed'] ? 'checked' : '' ?>
                        data-task-id="<?= $task['id'] ?>">
                    
                    <span class="task-text"><?= htmlspecialchars($task['task_description']) ?></span>
                    
                    <button class="delete-btn" data-task-id="<?= $task['id'] ?>">Delete</button>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <script src="js/app.js"></script> 
</body>
</html>