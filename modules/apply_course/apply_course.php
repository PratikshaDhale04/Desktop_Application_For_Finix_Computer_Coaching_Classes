<?php
$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['apply_course'])) {
        $student_id = intval($_POST['student_id']);
        $course_id = intval($_POST['course_id']);
        
        if (!$student_id) {
            $msg = "Please select a student!"; $msgType = "error";
        } elseif (!$course_id) {
            $msg = "Please select a course!"; $msgType = "error";
        } else {
            $course_fee = floatval($_POST['course_fee']);
            $discount = floatval($_POST['discount']);
            $final_fees = $course_fee - $discount;
            $course_start_date = $_POST['course_start_date'];
            $paid_fee = floatval($_POST['paid_fee']);
            $remaining_fee = $final_fees - $paid_fee;
            
            $check = mysqli_query($conn, "SELECT * FROM apply_course WHERE student_id=$student_id AND course_id=$course_id AND status='active'");
            if (mysqli_num_rows($check) > 0) {
                $msg = "Already enrolled for this course!"; $msgType = "error";
            } else {
                $sql = "INSERT INTO apply_course (student_id, course_id, course_fee, discount, final_fees, course_start_date, paid_fee, remaining_fee) 
                    VALUES ($student_id, $course_id, $course_fee, $discount, $final_fees, '$course_start_date', $paid_fee, $remaining_fee)";
                
                if (mysqli_query($conn, $sql)) {
                    $receipt_no = '';
                if ($paid_fee > 0) {
                    $apply_id = mysqli_insert_id($conn);
                    $receipt_no = sanitize($conn, $_POST['receipt_no']);
                    
                    $payment_sql = "INSERT INTO payments (student_id, course_id, apply_id, installment_no, amount_paid, payment_date, payment_mode, receipt_no, payment_status) 
                        VALUES ($student_id, $course_id, $apply_id, 1, $paid_fee, '$course_start_date', 'cash', '$receipt_no', 'completed')";
                        mysqli_query($conn, $payment_sql);
                        $msg = "Course applied! First payment of ₹$paid_fee recorded. Receipt: $receipt_no"; 
                    } else {
                        $msg = "Course applied successfully!"; 
                    }
                    $msgType = "success";
                } else {
                    $msg = "Error: " . mysqli_error($conn); $msgType = "error";
                }
            }
        }
    }
    
    if (isset($_POST['update_apply'])) {
        $apply_id = intval($_POST['apply_id']);
        $course_fee = floatval($_POST['course_fee']);
        $discount = floatval($_POST['discount']);
        $final_fees = $course_fee - $discount;
        
        $result = mysqli_query($conn, "SELECT paid_fee FROM apply_course WHERE apply_id=$apply_id");
        $row = mysqli_fetch_assoc($result);
        $remaining_fee = $final_fees - $row['paid_fee'];
        $status = $remaining_fee <= 0 ? 'completed' : 'active';
        
        mysqli_query($conn, "UPDATE apply_course SET course_fee=$course_fee, discount=$discount, final_fees=$final_fees, remaining_fee=$remaining_fee, status='$status' WHERE apply_id=$apply_id");
        $msg = "Updated!"; $msgType = "success";
    }
}

if (isset($_GET['delete'])) {
    mysqli_query($conn, "DELETE FROM apply_course WHERE apply_id=" . intval($_GET['delete']));
    $msg = "Deleted!"; $msgType = "success";
}

$search = isset($_GET['search']) ? sanitize($conn, $_GET['search']) : '';
$where = $search ? "WHERE s.student_uid LIKE '%$search%' OR s.full_name LIKE '%$search%' OR c.course_name LIKE '%$search%'" : "";
$applyCourses = mysqli_query($conn, "SELECT a.*, s.student_uid, s.full_name, c.course_name FROM apply_course a JOIN students s ON a.student_id=s.student_id JOIN courses c ON a.course_id=c.course_id $where ORDER BY a.apply_id DESC");

$searchStudent = isset($_GET['search_student']) ? sanitize($conn, $_GET['search_student']) : '';
$studentWhere = $searchStudent ? "WHERE (student_uid LIKE '%$searchStudent%' OR full_name LIKE '%$searchStudent%') AND status='active'" : "WHERE status='active'";
$students = mysqli_query($conn, "SELECT student_id, student_uid, full_name FROM students $studentWhere ORDER BY full_name LIMIT 10");

$courses = mysqli_query($conn, "SELECT * FROM courses WHERE status='active' ORDER BY course_name");
?>

<?php if ($msg): ?><div class="alert alert-<?php echo $msgType; ?>"><?php echo $msg; ?></div><?php endif; ?>

<div class="form-container">
    <h3><i class="fas fa-user-plus"></i> Apply Course for Student</h3>
    <form method="GET" style="margin-bottom:15px;">
        <input type="hidden" name="page" value="apply_course">
        <div style="display:flex;gap:10px;">
            <input type="text" name="search_student" placeholder="Search student by ID or Name..." value="<?php echo htmlspecialchars($searchStudent); ?>" style="padding:10px;border:2px solid #e2e8f0;border-radius:10px;flex:1;">
            <button type="submit" class="btn"><i class="fas fa-search"></i> Search</button>
            <a href="?page=apply_course" class="btn"><i class="fas fa-times"></i> Clear</a>
        </div>
    </form>
    
    <?php if ($searchStudent && mysqli_num_rows($students) > 0): ?>
    <div style="background:#f0f0f0;padding:15px;border-radius:10px;margin-bottom:20px;">
        <h4 style="margin-bottom:10px;">Select Student:</h4>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;">
            <?php while ($s = mysqli_fetch_assoc($students)): ?>
                <label style="display:flex;align-items:center;gap:8px;padding:10px;background:white;border-radius:8px;cursor:pointer;">
                    <input type="radio" name="selected_student" value="<?php echo $s['student_id']; ?>" onchange="setStudentId(this.value)" required>
                    <?php echo htmlspecialchars($s['full_name'] . ' (' . $s['student_uid'] . ')'); ?>
                </label>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <form method="POST" class="form-grid">
        <input type="hidden" name="student_id" id="selectedStudentId" value="">
        
        <div class="form-group">
            <label>Select Course <span style="color:red">*</span></label>
            <select name="course_id" required>
                <option value="">-- Select Course --</option>
                <?php while ($c = mysqli_fetch_assoc($courses)): ?>
                    <option value="<?php echo $c['course_id']; ?>" data-fee="<?php echo $c['fee']; ?>"><?php echo htmlspecialchars($c['course_name'] . ' - ₹' . number_format($c['fee'])); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group"><label>Course Fee (₹)</label><input type="number" name="course_fee" id="course_fee" required></div>
        <div class="form-group"><label>Discount (₹)</label><input type="number" name="discount" id="discount" value="0"></div>
        <div class="form-group"><label>Final Fees (₹)</label><input type="number" id="final_fees" readonly style="background:#f0f0f0;"></div>
        <div class="form-group"><label>Course Start Date</label><input type="date" name="course_start_date" value="<?php echo date('Y-m-d'); ?>"></div>
        <div class="form-group"><label>Paid Fee (₹)</label><input type="number" name="paid_fee" id="paid_fee" value="0"></div>
        <div class="form-group"><label>Receipt No</label><input type="text" name="receipt_no" placeholder="Enter Receipt No"></div>
        <div class="form-group"><label>Remaining Fee (₹)</label><input type="number" id="remaining_fee" readonly style="background:#f0f0f0;"></div>
        <div class="form-group"><button type="submit" name="apply_course" class="btn-submit"><i class="fas fa-save"></i> Apply Course</button></div>
        <script>function setStudentId(id) { document.getElementById('selectedStudentId').value = id; }</script>
    </form>
</div>

<div class="data-table">
    <div class="table-header">
        <h3><i class="fas fa-clipboard-list"></i> Course Enrollments</h3>
        <form method="GET" style="display:flex;gap:10px;">
            <input type="hidden" name="page" value="apply_course">
            <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>" style="padding:10px;border:2px solid #e2e8f0;border-radius:10px;width:200px;">
            <button type="submit" class="btn">Search</button>
        </form>
    </div>
    <table id="applyTable">
        <thead>
            <tr><th>Student</th><th>Course</th><th>Fee</th><th>Discount</th><th>Final Fee</th><th>Paid</th><th>Remaining</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($applyCourses) > 0): while ($row = mysqli_fetch_assoc($applyCourses)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['full_name']); ?><br><small><?php echo htmlspecialchars($row['student_uid']); ?></small></td>
                    <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                    <td>₹ <?php echo number_format($row['course_fee']); ?></td>
                    <td>₹ <?php echo number_format($row['discount']); ?></td>
                    <td>₹ <?php echo number_format($row['final_fees']); ?></td>
                    <td>₹ <?php echo number_format($row['paid_fee']); ?></td>
                    <td style="color:<?php echo $row['remaining_fee'] > 0 ? 'red' : 'green'; ?>;">₹ <?php echo number_format($row['remaining_fee']); ?></td>
                    <td><span class="status-badge <?php echo htmlspecialchars($row['status']); ?>"><?php echo ucfirst($row['status']); ?></span></td>
                    <td>
                        <div class="action-btns">
                            <a href="?page=apply_course&edit=<?php echo $row['apply_id']; ?>" class="edit"><i class="fas fa-edit"></i></a>
                            <a href="?page=apply_course&delete=<?php echo $row['apply_id']; ?>" class="delete delete-btn" onclick="return confirm('Delete?')"><i class="fas fa-times"></i></a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="9" style="text-align:center;color:#999;padding:40px;">No enrollments yet</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (isset($_GET['edit'])) {
    $edit = mysqli_fetch_assoc(mysqli_query($conn, "SELECT a.*, s.full_name, c.course_name FROM apply_course a JOIN students s ON a.student_id=s.student_id JOIN courses c ON a.course_id=c.course_id WHERE a.apply_id=".intval($_GET['edit'])));
    if ($edit):
?>
<div class="modal show">
    <div class="modal-content">
        <h3><i class="fas fa-edit"></i> Edit Enrollment</h3>
        <form method="POST" class="form-grid">
            <input type="hidden" name="apply_id" value="<?php echo $edit['apply_id']; ?>">
            <div class="form-group"><label>Student</label><input type="text" value="<?php echo htmlspecialchars($edit['full_name']); ?>" disabled></div>
            <div class="form-group"><label>Course</label><input type="text" value="<?php echo htmlspecialchars($edit['course_name']); ?>" disabled></div>
            <div class="form-group"><label>Course Fee (₹)</label><input type="number" name="course_fee" value="<?php echo $edit['course_fee']; ?>"></div>
            <div class="form-group"><label>Discount (₹)</label><input type="number" name="discount" value="<?php echo $edit['discount']; ?>"></div>
            <div class="form-group">
                <button type="submit" name="update_apply" class="btn-submit">Update</button>
                <a href="?page=apply_course" class="btn" style="background:#666;margin-left:10px;">Back</a>
            </div>
        </form>
    </div>
</div>
<?php endif; } ?>