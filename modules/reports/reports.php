<?php
$search = isset($_GET['search']) ? sanitize($conn, $_GET['search']) : '';
$where = $search ? "WHERE s.student_uid LIKE '%$search%' OR s.full_name LIKE '%$search%'" : "";
$students = mysqli_query($conn, "SELECT s.* FROM students s $where ORDER BY s.student_id DESC");

$report = null;
if (isset($_GET['report'])) {
    $id = intval($_GET['report']);
    $report = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.* FROM students s WHERE s.student_id=$id"));
    
    if ($report) {
        $report['courses'] = mysqli_query($conn, "SELECT a.*, c.course_name, c.duration, c.fee as original_fee FROM apply_course a JOIN courses c ON a.course_id=c.course_id WHERE a.student_id=$id AND a.status='active'");
        $report['payments'] = mysqli_query($conn, "SELECT p.*, c.course_name FROM payments p JOIN courses c ON p.course_id=c.course_id WHERE p.student_id=$id ORDER BY p.payment_id");
        
        $totalPaid = 0;
        while ($p = mysqli_fetch_assoc($report['payments'])) { $totalPaid += $p['amount_paid']; }
        mysqli_data_seek($report['payments'], 0);
        
        $feeResult = mysqli_query($conn, "SELECT COALESCE(SUM(final_fees),0) as total FROM apply_course WHERE student_id=$id AND status='active'");
        $feeRow = mysqli_fetch_assoc($feeResult);
        $report['total_fee'] = $feeRow['total'];
        $report['total_paid'] = $totalPaid;
        $report['total_remaining'] = $feeRow['total'] - $totalPaid;
    }
}
?>

<div class="data-table">
    <div class="table-header">
        <h3><i class="fas fa-file-alt"></i> Student Reports</h3>
        <form method="GET" style="display:flex;gap:10px;">
            <input type="hidden" name="page" value="reports">
            <input type="text" name="search" placeholder="Search by ID or Name..." value="<?php echo $search; ?>" style="padding:10px;border:2px solid #e2e8f0;border-radius:10px;width:200px;">
            <button type="submit" class="btn"><i class="fas fa-search"></i> Search</button>
        </form>
    </div>
    <table id="reportTable">
        <thead>
            <tr><th>ID</th><th>Name</th><th>Phone</th><th>Email</th><th> Courses</th><th>Total Fee</th><th>Paid</th><th>Balance</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($students) > 0): while ($row = mysqli_fetch_assoc($students)): 
                $totalFee = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(final_fees),0) as t FROM apply_course WHERE student_id=".$row['student_id']." AND status='active'"))['t'];
                $totalPaid = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(paid_fee),0) as t FROM apply_course WHERE student_id=".$row['student_id']." AND status='active'"))['t'];
                $courses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM apply_course WHERE student_id=".$row['student_id']." AND status='active'"))['c'];
            ?>
                <tr>
                    <td><?php echo $row['student_uid']; ?></td>
                    <td><?php echo $row['full_name']; ?></td>
                    <td><?php echo $row['phone']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $courses; ?></td>
                    <td>₹ <?php echo number_format($totalFee); ?></td>
                    <td style="color:green;">₹ <?php echo number_format($totalPaid); ?></td>
                    <td style="color:<?php echo ($totalFee-$totalPaid) > 0 ? 'red' : 'green'; ?>;">₹ <?php echo number_format($totalFee-$totalPaid); ?></td>
                    <td><a href="?page=reports&report=<?php echo $row['student_id']; ?>" class="btn"><i class="fas fa-eye"></i> View Report</a></td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="9" style="text-align:center;color:#999;padding:40px;">No students found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($report): ?>
<div class="modal show">
    <div class="modal-content" style="max-width:950px;">
        <div class="report-container">
            <!-- Print Header - Only visible in print -->
            <div class="print-header">
                <div class="print-logo">
                    <h1><i class="fas fa-laptop-code"></i> Finix Computers</h1>
                    <p class="print-tagline">Institute Management System - Ashta</p>
                </div>
                <div class="print-meta">
                    <h2>Student Fee Report</h2>
                    <p class="print-date">Generated: <?php echo date('d-m-Y / h:i A'); ?></p>
                </div>
            </div>

            <!-- Student Information Section -->
            <div class="report-section">
                <h4 class="section-title"><i class="fas fa-user-graduate"></i> Student Information</h4>
                <table class="info-table">
                    <tr>
                        <td class="label">Student ID</td>
                        <td class="value"><?php echo $report['student_uid']; ?></td>
                        <td class="label">Full Name</td>
                        <td class="value"><?php echo $report['full_name']; ?></td>
                    </tr>
                    <tr>
                        <td class="label">Phone</td>
                        <td class="value"><?php echo $report['phone']; ?></td>
                        <td class="label">Email</td>
                        <td class="value"><?php echo $report['email'] ?: 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <td class="label">Address</td>
                        <td class="value"><?php echo $report['address'] ?: 'N/A'; ?></td>
                        <td class="label">Date of Birth</td>
                        <td class="value"><?php echo $report['birthdate'] ? date('d-m-Y',strtotime($report['birthdate'])) : 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <td class="label">Admission Date</td>
                        <td class="value"><?php echo date('d-m-Y',strtotime($report['admission_date'])); ?></td>
                        <td class="label">Status</td>
                        <td class="value"><span class="status-badge <?php echo $report['status']; ?>"><?php echo ucfirst($report['status']); ?></span></td>
                    </tr>
                </table>
            </div>

            <!-- Course Information Section -->
            <div class="report-section">
                <h4 class="section-title"><i class="fas fa-book"></i> Course Information</h4>
                <table class="data-table-report">
                    <thead>
                        <tr>
                            <th>Sl No</th>
                            <th>Course ID</th>
                            <th>Course Name</th>
                            <th>Duration</th>
                            <th>Total Fee</th>
                            <th>Discount</th>
                            <th>Final Fee</th>
                            <th>Paid</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i=1; while ($c = mysqli_fetch_assoc($report['courses'])): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo 'CRS'.str_pad($c['course_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo $c['course_name']; ?></td>
                                <td><?php echo $c['duration']; ?></td>
                                <td>₹ <?php echo number_format($c['original_fee']); ?></td>
                                <td>₹ <?php echo number_format($c['discount']); ?></td>
                                <td class="amount">₹ <?php echo number_format($c['final_fees']); ?></td>
                                <td class="amount paid">₹ <?php echo number_format($c['paid_fee']); ?></td>
                                <td class="amount <?php echo $c['remaining_fee']>0?'balance':'paid';?>">₹ <?php echo number_format($c['remaining_fee']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                        <tr class="total-row">
                            <td colspan="6" class="text-right"><strong>TOTAL</strong></td>
                            <td class="amount"><strong>₹ <?php echo number_format($report['total_fee']); ?></strong></td>
                            <td class="amount paid"><strong>₹ <?php echo number_format($report['total_paid']); ?></strong></td>
                            <td class="amount <?php echo $report['total_remaining']>0?'balance':'paid';?>"><strong>₹ <?php echo number_format($report['total_remaining']); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Payment Summary Section -->
            <div class="report-section">
                <h4 class="section-title"><i class="fas fa-credit-card"></i> Payment Summary</h4>
                <div class="summary-cards">
                    <div class="summary-card">
                        <div class="summary-label">Total Fee</div>
                        <div class="summary-value">₹ <?php echo number_format($report['total_fee']); ?></div>
                    </div>
                    <div class="summary-card paid">
                        <div class="summary-label">Total Paid</div>
                        <div class="summary-value">₹ <?php echo number_format($report['total_paid']); ?></div>
                    </div>
                    <div class="summary-card <?php echo $report['total_remaining']>0?'balance':'paid';?>">
                        <div class="summary-label">Remaining</div>
                        <div class="summary-value">₹ <?php echo number_format($report['total_remaining']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Payment History Section -->
            <div class="report-section">
                <h4 class="section-title"><i class="fas fa-history"></i> Payment Installment Details</h4>
                <table class="data-table-report">
                    <thead>
                        <tr>
                            <th>Receipt No</th>
                            <th>Course</th>
                            <th>Installment No</th>
                            <th>Amount Paid</th>
                            <th>Payment Date</th>
                            <th>Payment Mode</th>
                            <th>Transaction ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $installmentCount = mysqli_num_rows($report['payments']);
                        if ($installmentCount > 0): while ($p = mysqli_fetch_assoc($report['payments'])): ?>
                            <tr>
                                <td><?php echo $p['receipt_no']; ?></td>
                                <td><?php echo $p['course_name']; ?></td>
                                <td><?php echo $p['installment_no']; ?></td>
                                <td class="amount">₹ <?php echo number_format($p['amount_paid']); ?></td>
                                <td><?php echo date('d-m-Y',strtotime($p['payment_date'])); ?></td>
                                <td><?php echo ucfirst($p['payment_mode']); ?></td>
                                <td><?php echo $p['transaction_id'] ?: 'N/A'; ?></td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="7" style="text-align:center;color:#999;padding:20px;">No payment records found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php if ($installmentCount > 0): ?>
                <div class="installment-info">
                    <strong>Total Installments: </strong><?php echo $installmentCount; ?> 
                    <span class="separator">|</span>
                    <strong>Last Payment: </strong><?php 
                        mysqli_data_seek($report['payments'], $installmentCount - 1);
                        $lastPayment = mysqli_fetch_assoc($report['payments']);
                        echo date('d-m-Y', strtotime($lastPayment['payment_date']));
                    ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Print Footer -->
            <div class="print-footer">
                <div class="footer-line"></div>
                <p class="footer-text">This is a computer-generated document. Generated on <?php echo date('d-m-Y'); ?> at <?php echo date('h:i A'); ?></p>
                <p class="footer-address">Finix Computers, Ashta</p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="report-actions no-print">
            <button class="btn btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print Report</button>
            <a href="?page=reports" class="btn btn-close"><i class="fas fa-times"></i> Close</a>
        </div>
    </div>
</div>
<?php endif; mysqli_close($conn); ?>