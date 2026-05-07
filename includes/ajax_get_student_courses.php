<?php
header('Content-Type: text/html');
header('Cache-Control: no-cache, no-store, must-revalidate');

$conn = mysqli_connect('localhost', 'root', '', 'finix_computers');
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

if ($student_id) {
    $result = mysqli_query($conn, "
        SELECT 
            a.apply_id, 
            a.course_id, 
            c.course_name, 
            a.final_fees, 
            a.paid_fee, 
            a.remaining_fee, 
            a.status,
            (SELECT COUNT(*) FROM payments p WHERE p.apply_id = a.apply_id) as payment_count
        FROM apply_course a 
        JOIN courses c ON a.course_id = c.course_id 
        WHERE a.student_id = $student_id AND a.status = 'active'
    ");
    
    while ($row = mysqli_fetch_assoc($result)) {
        $payment_count = intval($row['payment_count']);
        
        // NEXT payment number to be made = payment_count + 1
        // payment_count = payments already in database (from ApplyCourse or Payment Module)
        $next_payment = $payment_count + 1;
        
        $total_fee = floatval($row['final_fees']);
        $paid_fee = floatval($row['paid_fee']);
        $remaining = floatval($row['remaining_fee']);
        
        // For form fields: apply_id-course_id-total_fee-paid_fee-remaining-next_installment
        $val = $row['apply_id'].'-'.$row['course_id'].'-'.$total_fee.'-'.$paid_fee.'-'.$remaining.'-'.$next_payment;
        
        $display_text = $row['course_name'].' | Total: Rs.'.$total_fee.' | Paid: Rs.'.$paid_fee.' | Balance: Rs.'.$remaining;
        
        // Append payment count info
        if ($payment_count == 0) {
            $display_text .= ' | No payment yet - First payment';
        } else {
            $display_text .= ' | '.$payment_count.' payment(s) made';
        }
        
        echo '<option value="'.$val.'">'.$display_text.'</option>';
    }
}

mysqli_close($conn);
?>