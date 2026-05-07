<?php
header('Content-Type: text/html');
$conn = mysqli_connect('localhost', 'root', '', 'finix_computers');
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
if ($search) {
    $result = mysqli_query($conn, "SELECT student_id, student_uid, full_name FROM students WHERE (student_uid LIKE '%$search%' OR full_name LIKE '%$search%') AND status='active' LIMIT 10");
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<div style="padding:10px;cursor:pointer;border-bottom:1px solid #eee;" onmouseover="this.style.background=\'#f5f5f5\';" onmouseout="this.style.background=\'white\';" onclick="selectStudent('.$row['student_id'].',\''.$row['full_name'].'\',\''.$row['student_uid'].'\')">'.$row['full_name'].' ('.$row['student_uid'].')</div>';
    }
}
mysqli_close($conn);
?>