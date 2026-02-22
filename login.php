<?php
session_start();
require 'includes/db_connect.php'; // Include the database connection

// Check if the user is already logged in, redirect to index.php
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error_message = '';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        try {
            // Use prepared statement to prevent SQL injection
            $stmt = $pdo->prepare('SELECT id, password FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // Check if user exists and password is correct
            if ($user && password_verify($password, $user['password'])) {
                // Login successful: set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $username;
                
                // Redirect to the main to-do list page
                header('Location: index.php');
                exit;
            } else {
                $error_message = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $error_message = "A database error occurred. Please try again.";
            // Log the actual error: error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ToDo App</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class = "login-page">
    <div class="container">
        <h2>User Login</h2>
        <?php if ($error_message): ?>
            <p class="error"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>
        
        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Log In</button>
        </form>
        
        <p>Don't have an account? <a href="register.php">Register here</a>.</p>
    </div>
</body>
</html>