<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
include 'config/config.php';
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) { die("Connection failed"); }
mysqli_set_charset($conn, 'utf8mb4');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finix Computers - Institute Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="wrapper">
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-laptop-code"></i>
                <span>Finix Computers</span>
            </div>
            <span class="location">Ashta</span>
        </div>
        <ul class="nav-links">
            <li class="<?php echo $page === 'dashboard' ? 'active' : ''; ?>">
                <a href="index.php?page=dashboard"><i class="fas fa-chart-line"></i><span>Dashboard</span></a>
            </li>
            <li class="<?php echo $page === 'students' ? 'active' : ''; ?>">
                <a href="index.php?page=students"><i class="fas fa-user-graduate"></i><span>Students</span></a>
            </li>
            <li class="<?php echo $page === 'courses' ? 'active' : ''; ?>">
                <a href="index.php?page=courses"><i class="fas fa-book"></i><span>Courses</span></a>
            </li>
            <li class="<?php echo $page === 'apply_course' ? 'active' : ''; ?>">
                <a href="index.php?page=apply_course"><i class="fas fa-user-plus"></i><span>Apply Course</span></a>
            </li>
            <li class="<?php echo $page === 'payments' ? 'active' : ''; ?>">
                <a href="index.php?page=payments"><i class="fas fa-credit-card"></i><span>Payments</span></a>
            </li>
            <li class="<?php echo $page === 'reports' ? 'active' : ''; ?>">
                <a href="index.php?page=reports"><i class="fas fa-file-alt"></i><span>Reports</span></a>
            </li>
            <li class="logout">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </li>
        </ul>
    </nav>
    <div class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i></button>
                <h2><?php echo ucfirst(str_replace('_', ' ', $page)); ?></h2>
            </div>
            <div class="header-right">
                <div class="admin-info">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo $_SESSION['admin_name'] ?? 'Admin'; ?></span>
                </div>
            </div>
        </header>
        <main class="content">
<?php
switch($page) {
    case 'dashboard': include 'modules/dashboard/dashboard.php'; break;
    case 'students': include 'modules/students/students.php'; break;
    case 'courses': include 'modules/courses/courses.php'; break;
    case 'apply_course': include 'modules/apply_course/apply_course.php'; break;
    case 'payments': include 'modules/payments/payments.php'; break;
    case 'reports': include 'modules/reports/reports.php'; break;
    default: include 'modules/dashboard/dashboard.php';
}
?>
        </main>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>