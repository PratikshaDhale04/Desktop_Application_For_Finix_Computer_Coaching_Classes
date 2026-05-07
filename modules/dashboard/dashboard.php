<?php
$totalStudents = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM students WHERE status='active'"));
$totalStudents = $totalStudents ? $totalStudents['count'] : 0;

$totalCourses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM courses WHERE status='active'"));
$totalCourses = $totalCourses ? $totalCourses['count'] : 0;

$totalFees = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount_paid),0) as total FROM payments WHERE payment_status='completed'"));
$totalFees = $totalFees ? $totalFees['total'] : 0;

$pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(remaining_fee),0) as total FROM apply_course WHERE status='active'"));
$pending = $pending ? $pending['total'] : 0;

$recentStudents = mysqli_query($conn, "SELECT * FROM students ORDER BY created_at DESC LIMIT 5");
$recentPayments = mysqli_query($conn, "SELECT p.*, s.full_name, c.course_name FROM payments p JOIN students s ON p.student_id=s.student_id JOIN courses c ON p.course_id=c.course_id ORDER BY p.created_at DESC LIMIT 5");
?>
<div class="card-container">
    <div class="stat-card">
        <div class="icon students"><i class="fas fa-user-graduate"></i></div>
        <div class="info">
            <h3>Total Students</h3>
            <div class="number"><?php echo $totalStudents; ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="icon courses"><i class="fas fa-book"></i></div>
        <div class="info">
            <h3>Total Courses</h3>
            <div class="number"><?php echo $totalCourses; ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="icon fees"><i class="fas fa-rupee-sign"></i></div>
        <div class="info">
            <h3>Fees Collected</h3>
            <div class="number">₹ <?php echo number_format($totalFees, 0); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="icon pending"><i class="fas fa-clock"></i></div>
        <div class="info">
            <h3>Pending Fees</h3>
            <div class="number">₹ <?php echo number_format($pending, 0); ?></div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
    <div class="data-table">
        <div class="table-header">
            <h3><i class="fas fa-user-plus"></i> Recent Students</h3>
            <a href="index.php?page=students" class="btn">View All</a>
        </div>
        <table>
            <thead>
                <tr><th>ID</th><th>Name</th><th>Phone</th><th>Date</th></tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($recentStudents) > 0): while ($row = mysqli_fetch_assoc($recentStudents)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['student_uid']); ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="4" style="text-align:center;color:#999;">No students yet</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="data-table">
        <div class="table-header">
            <h3><i class="fas fa-credit-card"></i> Recent Payments</h3>
            <a href="index.php?page=payments" class="btn">View All</a>
        </div>
        <table>
            <thead>
                <tr><th>Receipt</th><th>Student</th><th>Amount</th><th>Date</th></tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($recentPayments) > 0): while ($row = mysqli_fetch_assoc($recentPayments)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['receipt_no']); ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td>₹ <?php echo number_format($row['amount_paid']); ?></td>
                        <td><?php echo date('d M Y', strtotime($row['payment_date'])); ?></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="4" style="text-align:center;color:#999;">No payments yet</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>