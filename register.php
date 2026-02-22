<?php
session_start();
require 'includes/db_connect.php'; // Include the database connection

// Check if the user is already logged in, redirect to index.php
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error_message = '';
$success_message = '';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic Input Validation
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error_message = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } else {
        try {
            // 1. Check if username already exists
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $error_message = "Username is already taken. Please choose another.";
            } else {
                // 2. Hash the password securely
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // 3. Insert the new user into the database
                $stmt = $pdo->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
                $stmt->execute([$username, $hashed_password]);

                $success_message = "Registration successful! You can now log in.";
                // Optional: Automatically log in the user
                // $_SESSION['user_id'] = $pdo->lastInsertId();
                // $_SESSION['username'] = $username;
                // header('Location: index.php');
                // exit;
            }
        } catch (PDOException $e) {
            $error_message = "A database error occurred during registration. Please try again.";
            // In a real application, you would log the error here: error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ToDo App</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class = "register-page">
    <div class="container">
        <h2>User Registration</h2>
        
        <?php if ($error_message): ?>
            <p class="error"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <p class="success"><?= htmlspecialchars($success_message) ?></p>
            <p><a href="login.php">Go to Login</a></p>
        <?php endif; ?>

        <?php if (!$success_message): // Only show form if registration hasn't succeeded ?>
            <form method="POST" action="register.php">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($username ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password (min 8 chars):</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit">Register</button>
            </form>
            <p>Already have an account? <a href="login.php">Log in here</a>.</p>
        <?php endif; ?>
    </div>
</body>
</html>