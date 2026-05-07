<?php
$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_student'])) {
        $full_name = sanitize($conn, $_POST['full_name']);
        $phone = sanitize($conn, $_POST['phone']);
        $email = sanitize($conn, $_POST['email']);
        $address = sanitize($conn, $_POST['address']);
        $birthdate = $_POST['birthdate'];
        $admission_date = date('Y-m-d');
        
        $result = mysqli_query($conn, "SELECT student_id FROM students ORDER BY student_id DESC LIMIT 1");
        $row = mysqli_fetch_assoc($result);
        $nextNum = $row ? $row['student_id'] + 1 : 1;
        $student_uid = 'STU' . date('Y') . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO students (student_uid, full_name, phone, email, address, birthdate, admission_date) VALUES ('$student_uid', '$full_name', '$phone', '$email', '$address', '$birthdate', '$admission_date')";
        
        if (mysqli_query($conn, $sql)) {
            $msg = "Student added successfully!"; $msgType = "success";
        } else {
            $msg = "Error: " . mysqli_error($conn); $msgType = "error";
        }
    }
    
    if (isset($_POST['update_student'])) {
        $id = intval($_POST['student_id']);
        $full_name = sanitize($conn, $_POST['full_name']);
        $phone = sanitize($conn, $_POST['phone']);
        $email = sanitize($conn, $_POST['email']);
        $address = sanitize($conn, $_POST['address']);
        $birthdate = $_POST['birthdate'];
        
        mysqli_query($conn, "UPDATE students SET full_name='$full_name', phone='$phone', email='$email', address='$address', birthdate='$birthdate' WHERE student_id=$id");
        $msg = "Student updated!"; $msgType = "success";
    }
}

if (isset($_GET['delete'])) {
    mysqli_query($conn, "DELETE FROM students WHERE student_id=" . intval($_GET['delete']));
    $msg = "Student deleted!"; $msgType = "success";
}

$search = isset($_GET['search']) ? sanitize($conn, $_GET['search']) : '';
$where = $search ? "WHERE student_uid LIKE '%$search%' OR full_name LIKE '%$search%'" : "";
$students = mysqli_query($conn, "SELECT * FROM students $where ORDER BY student_id DESC");
?>

<?php if ($msg): ?><div class="alert alert-<?php echo $msgType; ?>"><?php echo $msg; ?></div><?php endif; ?>

<div class="form-container">
    <h3><i class="fas fa-user-plus"></i> Add New Student</h3>
    <form method="POST" class="form-grid">
        <div class="form-group">
            <label>Full Name <span style="color:red">*</span></label>
            <input type="text" name="full_name" required placeholder="Enter full name">
        </div>
        <div class="form-group">
            <label>Phone Number <span style="color:red">*</span></label>
            <input type="tel" name="phone" required placeholder="Enter phone number" maxlength="10">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="Enter email">
        </div>
        <div class="form-group">
            <label>Birthdate</label>
            <input type="date" name="birthdate">
        </div>
        <div class="form-group" style="grid-column: span 2;">
            <label>Address</label>
            <textarea name="address" placeholder="Enter address"></textarea>
        </div>
        <div class="form-group" style="grid-column: span 2;">
            <button type="submit" name="add_student" class="btn-submit"><i class="fas fa-save"></i> Add Student</button>
            <a href="index.php?page=dashboard" class="btn" style="background:#666;margin-left:10px;">Back to Dashboard</a>
        </div>
    </form>
</div>

<div class="data-table">
    <div class="table-header">
        <h3><i class="fas fa-users"></i> All Students</h3>
        <form method="GET" style="display:flex;gap:10px;">
            <input type="hidden" name="page" value="students">
            <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>" style="padding:10px;border:2px solid #e2e8f0;border-radius:10px;width:200px;">
            <button type="submit" class="btn"><i class="fas fa-search"></i> Search</button>
        </form>
    </div>
    <table id="studentTable">
        <thead>
            <tr><th>ID</th><th>Full Name</th><th>Phone</th><th>Email</th><th>Birthdate</th><th>Admission Date</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($students) > 0): while ($row = mysqli_fetch_assoc($students)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['student_uid']); ?></td>
                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo $row['birthdate'] ? date('d M Y', strtotime($row['birthdate'])) : '-'; ?></td>
                    <td><?php echo date('d M Y', strtotime($row['admission_date'])); ?></td>
                    <td><span class="status-badge <?php echo htmlspecialchars($row['status']); ?>"><?php echo ucfirst($row['status']); ?></span></td>
                    <td>
                        <div class="action-btns">
                            <a href="?page=students&edit=<?php echo $row['student_id']; ?>" class="edit"><i class="fas fa-edit"></i></a>
                            <a href="?page=students&delete=<?php echo $row['student_id']; ?>" class="delete delete-btn" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="8" style="text-align:center;color:#999;padding:40px;">No students found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM students WHERE student_id=$edit_id"));
    if ($edit):
?>
<div class="modal show">
    <div class="modal-content">
        <h3><i class="fas fa-edit"></i> Edit Student</h3>
        <form method="POST" class="form-grid">
            <input type="hidden" name="student_id" value="<?php echo $edit['student_id']; ?>">
            <div class="form-group"><label>Student ID</label><input type="text" value="<?php echo htmlspecialchars($edit['student_uid']); ?>" disabled></div>
            <div class="form-group"><label>Full Name</label><input type="text" name="full_name" value="<?php echo htmlspecialchars($edit['full_name']); ?>" required></div>
            <div class="form-group"><label>Phone</label><input type="tel" name="phone" value="<?php echo htmlspecialchars($edit['phone']); ?>" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($edit['email']); ?>"></div>
            <div class="form-group"><label>Birthdate</label><input type="date" name="birthdate" value="<?php echo $edit['birthdate']; ?>"></div>
            <div class="form-group" style="grid-column:span 2"><label>Address</label><textarea name="address"><?php echo htmlspecialchars($edit['address']); ?></textarea></div>
            <div class="form-group" style="grid-column:span 2">
                <button type="submit" name="update_student" class="btn-submit">Update</button>
                <a href="?page=students" class="btn" style="background:#666;margin-left:10px;">Back</a>
            </div>
        </form>
    </div>
</div>
<?php endif; } ?>