<?php
$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_course'])) {
        $course_name = sanitize($conn, $_POST['course_name']);
        $duration = sanitize($conn, $_POST['duration']);
        $fee = floatval($_POST['fee']);
        $description = sanitize($conn, $_POST['description']);
        
        $sql = "INSERT INTO courses (course_name, duration, fee, description) VALUES ('$course_name', '$duration', $fee, '$description')";
        
        if (mysqli_query($conn, $sql)) {
            $msg = "Course added!"; $msgType = "success";
        } else {
            $msg = "Error: " . mysqli_error($conn); $msgType = "error";
        }
    }
    
    if (isset($_POST['update_course'])) {
        $id = $_POST['course_id'];
        mysqli_query($conn, "UPDATE courses SET course_name='".sanitize($conn,$_POST['course_name'])."', duration='".sanitize($conn,$_POST['duration'])."', fee=".floatval($_POST['fee']).", description='".sanitize($conn,$_POST['description'])."' WHERE course_id=$id");
        $msg = "Course updated!"; $msgType = "success";
    }
}

if (isset($_GET['delete'])) {
    mysqli_query($conn, "DELETE FROM courses WHERE course_id=" . $_GET['delete']);
    $msg = "Course deleted!"; $msgType = "success";
}

if (isset($_GET['toggle'])) {
    $c = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM courses WHERE course_id=".$_GET['toggle']));
    $new = $c['status'] === 'active' ? 'inactive' : 'active';
    mysqli_query($conn, "UPDATE courses SET status='$new' WHERE course_id=".$_GET['toggle']);
    $msg = "Status updated!"; $msgType = "success";
}

$courses = mysqli_query($conn, "SELECT * FROM courses ORDER BY course_id DESC");
?>

<?php if ($msg): ?><div class="alert alert-<?php echo $msgType; ?>"><?php echo $msg; ?></div><?php endif; ?>

<div class="form-container">
    <h3><i class="fas fa-book"></i> Add New Course</h3>
    <form method="POST" class="form-grid">
        <div class="form-group">
            <label>Course Name <span style="color:red">*</span></label>
            <input type="text" name="course_name" required placeholder="Course name">
        </div>
        <div class="form-group">
            <label>Duration <span style="color:red">*</span></label>
            <input type="text" name="duration" required placeholder="e.g., 3 Months">
        </div>
        <div class="form-group">
            <label>Course Fee (₹) <span style="color:red">*</span></label>
            <input type="number" name="fee" required placeholder="Fee amount" min="0">
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" placeholder="Course description"></textarea>
        </div>
        <div class="form-group">
            <button type="submit" name="add_course" class="btn-submit"><i class="fas fa-save"></i> Add Course</button>
        </div>
    </form>
</div>

<div class="data-table">
    <div class="table-header">
        <h3><i class="fas fa-book"></i> All Courses</h3>
    </div>
    <table id="courseTable">
        <thead>
            <tr><th>ID</th><th>Course Name</th><th>Duration</th><th>Fee</th><th>Description</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($courses) > 0): while ($row = mysqli_fetch_assoc($courses)): ?>
                <tr>
                    <td><?php echo 'CRS'.str_pad($row['course_id'],3,'0',STR_PAD_LEFT); ?></td>
                    <td><?php echo $row['course_name']; ?></td>
                    <td><?php echo $row['duration']; ?></td>
                    <td>₹ <?php echo number_format($row['fee']); ?></td>
                    <td><?php echo $row['description']; ?></td>
                    <td><a href="?page=courses&toggle=<?php echo $row['course_id']; ?>" class="status-badge <?php echo $row['status']; ?>" style="text-decoration:none;"><?php echo ucfirst($row['status']); ?></a></td>
                    <td>
                        <div class="action-btns">
                            <a href="?page=courses&edit=<?php echo $row['course_id']; ?>" class="edit"><i class="fas fa-edit"></i></a>
                            <a href="?page=courses&delete=<?php echo $row['course_id']; ?>" class="delete delete-btn" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="7" style="text-align:center;color:#999;padding:40px;">No courses yet</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (isset($_GET['edit'])) {
    $edit = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM courses WHERE course_id=".$_GET['edit']));
    if ($edit):
?>
<div class="modal show">
    <div class="modal-content">
        <h3><i class="fas fa-edit"></i> Edit Course</h3>
        <form method="POST" class="form-grid">
            <input type="hidden" name="course_id" value="<?php echo $edit['course_id']; ?>">
            <div class="form-group"><label>Course Name</label><input type="text" name="course_name" value="<?php echo $edit['course_name']; ?>" required></div>
            <div class="form-group"><label>Duration</label><input type="text" name="duration" value="<?php echo $edit['duration']; ?>" required></div>
            <div class="form-group"><label>Fee (₹)</label><input type="number" name="fee" value="<?php echo $edit['fee']; ?>" required></div>
            <div class="form-group"><label>Description</label><textarea name="description"><?php echo $edit['description']; ?></textarea></div>
            <div class="form-group">
                <button type="submit" name="update_course" class="btn-submit">Update</button>
                <a href="?page=courses" class="btn" style="background:#666;margin-left:10px;">Back</a>
            </div>
        </form>
    </div>
</div>
<?php endif; } ?>