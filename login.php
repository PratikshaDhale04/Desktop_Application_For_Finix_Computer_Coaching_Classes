<?php
session_start();
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php?page=dashboard");
    exit();
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $conn = mysqli_connect('localhost', 'root', '', 'finix_computers');
    if ($conn) {
        mysqli_set_charset($conn, 'utf8mb4');
        $username = mysqli_real_escape_string($conn, $username);
        $result = mysqli_query($conn, "SELECT * FROM admins WHERE username = '$username'");
        $admin = mysqli_fetch_assoc($result);
        
        // Simple password check (plain text)
        if ($admin && $password === $admin['password']) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            header("Location: index.php?page=dashboard");
            exit();
        } else {
            $error = "Invalid username or password";
        }
        mysqli_close($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Finix Computers</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <div class="login-logo">
            <i class="fas fa-laptop-code"></i>
        </div>
        <h1 class="login-title">Finix Computers</h1>
        <p class="login-subtitle">Institute Management System - Ashta</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group" style="text-align:left;">
                <label>Username</label>
                <input type="text" name="username" required placeholder="Enter username">
            </div>
            <div class="form-group" style="text-align:left;">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Enter password">
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>
        <p style="margin-top:20px;color:#666;font-size:14px;">Default: admin / admin123</p>
    </div>
</div>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>