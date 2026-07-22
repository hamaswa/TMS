$("#date").submit(function(e) {
    e.preventDefault();
    var formData = $(this).serialize();
    // console.log(formData);
    submitform(formData);
});

function submitform(formData) {
    $.ajax({
        type: 'POST',
        url: $("#date").attr('action'),
        data: formData,
        dataType: 'json',
        success: function(response) {
            var expensesDetail = response.expenses_detail;
            var totalExpenses = response.total_expenses;
            var salariesDetail = response.salaries_detail;
            var totalSalaries = response.salaries;

            // Populate modal with data
            $('#modalTable tbody').html(`
                <tr>
                    <td>${expensesDetail}</td>
                    <td>${totalExpenses}</td>
                    <td>${salariesDetail}</td>
                    <td>${totalSalaries}</td>
                </tr>
            `);

            // Show the modal
            $('#myModal').modal('show');
        },
        error: function(error) {
            console.error('Error:', error);
        }
    });
}
