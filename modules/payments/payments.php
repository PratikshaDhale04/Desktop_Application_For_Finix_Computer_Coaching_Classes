<?php
$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_payment'])) {
        $student_id = intval($_POST['student_id']);
        $course_id = intval($_POST['course_id']);
        $apply_id = intval($_POST['apply_id']);
        $amount_paid = floatval($_POST['amount_paid']);
        $payment_date = $_POST['payment_date'];
        $payment_mode = sanitize($conn, $_POST['payment_mode']);
        
        $receipt_no = sanitize($conn, $_POST['receipt_no']);
        $transaction_id = '';
        
        // Get current payment count - this is the number of payments already made
        // including any payment from ApplyCourse module
        $countResult = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM payments WHERE apply_id=$apply_id");
        $countRow = mysqli_fetch_assoc($countResult);
        $payment_count = intval($countRow['cnt']);
        
        // Installment number = current payment count + 1
        // So if 0 payments made = Installment 1
        // If 1 payment made (from ApplyCourse) = Installment 2
        // And so on...
        $installment_no = $payment_count + 1;
        
        // Insert payment
        $sql = "INSERT INTO payments (student_id, course_id, apply_id, installment_no, amount_paid, payment_date, payment_mode, receipt_no, transaction_id, payment_status) 
            VALUES ($student_id, $course_id, $apply_id, $installment_no, $amount_paid, '$payment_date', '$payment_mode', '$receipt_no', '$transaction_id', 'completed')";
        
        if (mysqli_query($conn, $sql)) {
            // Get current paid_fee and calculate new values from database
            $applyData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT paid_fee, final_fees FROM apply_course WHERE apply_id=$apply_id"));
            $newPaid = $applyData['paid_fee'] + $amount_paid;
            $remaining = $applyData['final_fees'] - $newPaid;
            $status = $remaining <= 0 ? 'completed' : 'active';
            
            // Update apply_course with new values
            mysqli_query($conn, "UPDATE apply_course SET paid_fee=$newPaid, remaining_fee=$remaining, status='$status' WHERE apply_id=$apply_id");
            
            $msg = "Payment of Rs.$amount_paid saved!"; $msgType = "success";
        } else {
            $msg = "Error: " . mysqli_error($conn); $msgType = "error";
        }
    }
}

$search = isset($_GET['search']) ? sanitize($conn, $_GET['search']) : '';
$where = '';
if ($search) {
    $searchTerm = '%' . $search . '%';
    $where = "AND (s.student_uid LIKE '$searchTerm' OR s.full_name LIKE '$searchTerm' OR p.installment_no LIKE '$searchTerm' OR p.amount_paid LIKE '$searchTerm')";
}
$payments = mysqli_query($conn, "SELECT p.*, s.student_uid, s.full_name, c.course_name FROM payments p JOIN students s ON p.student_id=s.student_id JOIN courses c ON p.course_id=c.course_id WHERE 1=1 $where ORDER BY p.payment_id DESC");

$studentSearch = isset($_GET['student_search']) ? sanitize($conn, $_GET['student_search']) : '';
$studentWhere = $studentSearch ? "WHERE (student_uid LIKE '%$studentSearch%' OR full_name LIKE '%$studentSearch%') AND status='active'" : "WHERE status='active'";
$studentList = mysqli_query($conn, "SELECT student_id, student_uid, full_name FROM students $studentWhere ORDER BY full_name LIMIT 10");
?>

<?php if ($msg): ?><div class="alert alert-<?php echo $msgType; ?>"><?php echo $msg; ?></div><?php endif; ?>

<div class="form-container">
    <h3><i class="fas fa-credit-card"></i> Add Payment</h3>
    
    <form method="GET" style="margin-bottom:15px;">
        <input type="hidden" name="page" value="payments">
        <div style="display:flex;gap:10px;">
            <input type="text" name="student_search" placeholder="Search student by ID or Name..." value="<?php echo htmlspecialchars($studentSearch); ?>" style="padding:10px;border:2px solid #e2e8f0;border-radius:10px;flex:1;">
            <button type="submit" class="btn"><i class="fas fa-search"></i> Search</button>
            <a href="?page=payments" class="btn"><i class="fas fa-times"></i> Clear</a>
        </div>
    </form>
    
    <?php if ($studentSearch && mysqli_num_rows($studentList) > 0): ?>
    <div style="background:#f0f0f0;padding:15px;border-radius:10px;margin-bottom:20px;">
        <h4 style="margin-bottom:10px;">Select Student:</h4>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;">
            <?php while ($s = mysqli_fetch_assoc($studentList)): ?>
                <label style="display:flex;align-items:center;gap:8px;padding:10px;background:white;border-radius:8px;cursor:pointer;">
                    <input type="radio" name="selected_student" value="<?php echo $s['student_id']; ?>" onchange="selectStudent(this.value, '<?php echo htmlspecialchars($s['full_name']); ?>', '<?php echo htmlspecialchars($s['student_uid']); ?>')">
                    <?php echo htmlspecialchars($s['full_name'] . ' (' . $s['student_uid'] . ')'); ?>
                </label>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <form method="POST" class="form-grid">
        <input type="hidden" name="student_id" id="selectedStudentId">
        <input type="hidden" name="course_id" id="courseId">
        <input type="hidden" name="apply_id" id="applyId">
        
        <div class="form-group">
            <label>Select Course <span style="color:red">*</span></label>
            <select id="courseSelect" required onchange="loadCourseDetails()">
                <option value="">-- Select Course --</option>
            </select>
        </div>
        
        <!-- Dynamic fee fields from database -->
        <div class="form-group"><label>Total Fee (Rs.)</label><input type="text" id="totalFee" readonly style="background:#f0f0f0;"></div>
        <div class="form-group"><label>Already Paid (Rs.)</label><input type="text" id="paidFee" readonly style="background:#f0f0f0;"></div>
        <div class="form-group"><label>Remaining Fee (Rs.)</label><input type="text" id="remainingFee" readonly style="background:#f0f0f0;"></div>
        <div class="form-group"><label>Installment</label><input type="text" id="installmentNo" readonly style="background:#f0f0f0;"></div>
        
        <div class="form-group"><label>Amount (Rs.) <span style="color:red">*</span></label><input type="number" name="amount_paid" required min="1"></div>
        <div class="form-group"><label>Payment Date <span style="color:red">*</span></label><input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required></div>
        
        <div class="form-group">
            <label>Payment Mode <span style="color:red">*</span></label>
            <select name="payment_mode" id="paymentMode" required onchange="togglePaymentFields()">
                <option value="">-- Select Mode --</option>
                <option value="cash">Cash</option>
                <option value="upi">UPI</option>
            </select>
        </div>
        
        <div class="form-group" id="receiptField" style="display:none;">
            <label>Receipt No <span style="color:red">*</span></label>
            <input type="text" name="receipt_no" placeholder="Enter Receipt No" required disabled>
        </div>
        
        <div class="form-group" id="transactionField" style="display:none;">
            <label>Transaction ID <span style="color:red">*</span></label>
            <input type="text" name="transaction_id" placeholder="Enter UPI Transaction ID" required disabled>
        </div>
        
        <div class="form-group"><button type="submit" name="add_payment" class="btn-submit"><i class="fas fa-save"></i> Save Payment</button></div>
    </form>
</div>

<?php 
$hasPayments = mysqli_num_rows($payments) > 0;
$showNoResults = $search && !$hasPayments;
?>
<div class="data-table">
    <div class="table-header">
        <h3><i class="fas fa-list"></i> Payment History</h3>
        <form method="GET" id="searchForm" style="display:flex;gap:10px;">
            <input type="hidden" name="page" value="payments">
            <input type="text" id="paymentSearch" name="search" placeholder="Search by name, ID, installment or amount..." value="<?php echo htmlspecialchars($search); ?>" style="padding:10px;border:2px solid #e2e8f0;border-radius:10px;width:280px;">
            <button type="submit" class="btn">Search</button>
            <?php if ($search): ?><a href="?page=payments" class="btn"><i class="fas fa-times"></i> Clear</a><?php endif; ?>
        </form>
    </div>
    <?php if ($showNoResults): ?>
    <div style="text-align:center;color:#999;padding:40px;font-size:16px;">
        <span style="font-size:24px;margin-bottom:10px;">&#128269;</span><br>
        No records found for "<?php echo htmlspecialchars($search); ?>"
    </div>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>Receipt</th><th>Student</th><th>Course</th><th>Inst.</th><th>Amount</th><th>Date</th><th>Mode</th></tr>
        </thead>
        <tbody>
            <?php if ($hasPayments): while ($row = mysqli_fetch_assoc($payments)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['receipt_no'] ?: '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['full_name']); ?><br><small><?php echo htmlspecialchars($row['student_uid']); ?></small></td>
                    <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                    <td><?php echo $row['installment_no']; ?></td>
                    <td>Rs. <?php echo number_format($row['amount_paid']); ?></td>
                    <td><?php echo date('d M Y', strtotime($row['payment_date'])); ?></td>
                    <td><?php echo ucfirst($row['payment_mode']); ?></td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="7" style="text-align:center;color:#999;padding:40px;">No payments yet</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<script>
var selectedStudentName = '';
var selectedStudentUid = '';
var searchTimeout = null;

document.getElementById('paymentSearch').addEventListener('keyup', function(e) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        var val = document.getElementById('paymentSearch').value.trim();
        if (val.length > 0) {
            document.getElementById('searchForm').submit();
        }
    }, 500);
});

document.getElementById('paymentSearch').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        clearTimeout(searchTimeout);
        document.getElementById('searchForm').submit();
    }
});

function selectStudent(id, name, uid) {
    document.getElementById('selectedStudentId').value = id;
    selectedStudentName = name;
    selectedStudentUid = uid;
    loadStudentCourses(id);
}

function loadStudentCourses(studentId) {
    // Add timestamp to prevent caching and get fresh data
    var timestamp = new Date().getTime();
    fetch('includes/ajax_get_student_courses.php?student_id=' + studentId + '&t=' + timestamp)
        .then(r => r.text())
        .then(html => {
            document.getElementById('courseSelect').innerHTML = '<option value="">-- Select Course --</option>' + html;
            // Reset form fields
            document.getElementById('totalFee').value = '';
            document.getElementById('paidFee').value = '';
            document.getElementById('remainingFee').value = '';
            document.getElementById('installmentNo').value = '';
        });
}

function loadCourseDetails() {
    var val = document.getElementById('courseSelect').value;
    if (val) {
        var parts = val.split('-');
        // parts: apply_id, course_id, total_fee, paid_fee, remaining, payment_count
        document.getElementById('applyId').value = parts[0];
        document.getElementById('courseId').value = parts[1];
        document.getElementById('totalFee').value = parts[2];
        document.getElementById('paidFee').value = parts[3];
        document.getElementById('remainingFee').value = parts[4];
        document.getElementById('installmentNo').value = parts[5];
    }
}

function togglePaymentFields() {
    var mode = document.getElementById('paymentMode').value;
    var receiptInput = document.querySelector('input[name="receipt_no"]');
    
    if (mode === 'cash' || mode === 'upi') {
        document.getElementById('receiptField').style.display = 'block';
        document.getElementById('transactionField').style.display = 'none';
        receiptInput.setAttribute('required', 'required');
        receiptInput.removeAttribute('disabled');
    } else {
        document.getElementById('receiptField').style.display = 'none';
        document.getElementById('transactionField').style.display = 'none';
        receiptInput.removeAttribute('required');
        receiptInput.setAttribute('disabled', 'disabled');
    }
}
</script>