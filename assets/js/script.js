$(document).ready(function() {
    $('#toggleBtn').click(function() {
        $('#sidebar').toggleClass('show');
    });
    
    $(document).click(function(e) {
        if (!$(e.target).closest('#sidebar, #toggleBtn').length) {
            $('#sidebar').removeClass('show');
        }
    });
    
    $('select[name="course_id"]').change(function() {
        var fee = $('option:selected', this).data('fee') || 0;
        $('#course_fee').val(fee);
        calculateFinalFee();
    });
    
    $('#course_fee, #discount, #paid_fee').on('input', calculateFinalFee);
    
    function calculateFinalFee() {
        var courseFee = parseFloat($('#course_fee').val()) || 0;
        var discount = parseFloat($('#discount').val()) || 0;
        var paidFee = parseFloat($('#paid_fee').val()) || 0;
        var finalFees = courseFee - discount;
        var remaining = finalFees - paidFee;
        $('#final_fees').val(finalFees);
        $('#remaining_fee').val(remaining);
    }
    
    $('#searchStudent').on('input', function() {
        var search = $(this).val();
        if (search.length >= 2) {
            $.ajax({
                url: 'includes/ajax_search_student.php',
                type: 'GET',
                data: { search: search },
                success: function(response) {
                    $('#studentResults').html(response).show();
                }
            });
        } else {
            $('#studentResults').hide();
        }
    });
    
    $(document).on('click', '.student-result', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var uid = $(this).data('uid');
        $('#searchStudent').val(name + ' (' + uid + ')');
        $('#selectedStudentId').val(id);
        $('#studentResults').hide();
        loadStudentCourses(id);
    });
    
    function loadStudentCourses(studentId) {
        $.ajax({
            url: 'includes/ajax_get_student_courses.php',
            type: 'GET',
            data: { student_id: studentId, t: new Date().getTime() },
            success: function(response) {
                $('#courseSelect').html('<option value="">-- Select Course --</option>' + response);
            }
        });
    }
    
    $('#courseSelect').change(function() {
        var val = $(this).val();
        if (val) {
            var parts = val.split('-');
            // parts: apply_id-course_id-final_fees-paid_fee-remaining_fee-payment_count
            $('#applyId').val(parts[0]);
            $('#courseId').val(parts[1]);
            $('#totalFee').val('Rs. ' + parts[2]);
            $('#paidFee').val('Rs. ' + parts[3]);
            $('#remainingFee').val('Rs. ' + parts[4]);
            $('#installmentNo').val(parts[5]);
        }
    });
    
    $('.delete-btn').click(function(e) {
        if (!confirm('Are you sure you want to delete this record?')) {
            e.preventDefault();
        }
    });
    
    $('.print-btn').click(function() {
        window.print();
    });
    
    $('#searchInput, #studentSearch, #courseSearch, #applySearch').on('keyup', function() {
        var search = $(this).val().toLowerCase();
        var table = $(this).attr('id').replace('Search', 'Table');
        $('#' + table + ' tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(search) > -1);
        });
    });
});