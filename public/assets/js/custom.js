if (document.documentElement.lang === 'ur' && jQuery.fn.dataTable) {
    jQuery.extend(true, jQuery.fn.dataTable.defaults, {
        language: {
            search: 'تلاش:',
            emptyTable: 'کوئی ریکارڈ موجود نہیں۔',
            zeroRecords: 'کوئی ملتا جلتا ریکارڈ نہیں ملا۔',
            info: 'کل _TOTAL_ ریکارڈز میں سے _START_ تا _END_ دکھائے جا رہے ہیں',
            infoEmpty: 'کوئی ریکارڈ موجود نہیں۔',
            infoFiltered: '(_MAX_ ریکارڈز میں سے فلٹر شدہ)',
            lengthMenu: '_MENU_ ریکارڈز دکھائیں',
            loadingRecords: 'لوڈ ہو رہا ہے۔۔۔',
            processing: 'کارروائی جاری ہے۔۔۔',
            paginate: { first: 'پہلا', last: 'آخری', next: 'اگلا', previous: 'پچھلا' },
            aria: { sortAscending: ': صعودی ترتیب', sortDescending: ': نزولی ترتیب' }
        }
    });
}

jQuery(document).ready(function ($) {

    $('#cc-table-data-customer-list').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'csv', 'excel', 'pdf'
        ]
    });

    $('#v-pills-tab a').on('click', function () {
        var OptionTypeId = $(this).attr('id');
        $('#OptionType').val(OptionTypeId);

    });
    // balance calculate
    $('#recivedPayment').keyup(function () {
        var recivedPayment = $('#recivedPayment').val();
        var totalPayment = $('#totalPayment').val();
        var balance = totalPayment - recivedPayment;
        $('#balance').val(balance);
    });
    // customer order display
    $(document).on('click', '.getCustomer', function () {
        var customer_id = $(this).data('id');
        var name = $(this).data('name');
        $('#cus_name').text(name);

        $.ajax({
            type: 'GET',
            url: '/admin/getCustomer',
            data: {
                id: customer_id,
            },
            dataType: 'json',
            success: function (data) {
                $('#orderDetail').css('display', 'block');
                if ($.fn.DataTable.isDataTable('#cc-table-data-order-history')) {
                    $('#cc-table-data-order-history').DataTable().destroy();
                }
                $('.tbody').empty();
                $.each(data, function (index, order) {
                    var row = '<tr>' +
                        '<td>' + order.number + '</td>' +
                        '<td>' + order.totalPayment + '</td>' +
                        '<td>' + (order.paidAmount === null ? '—' : '<span class="order-money is-paid">Rs. ' + Number(order.paidAmount).toFixed(2) + '</span>') + '</td>' +
                        '<td>' + (order.remainingAmount === null ? '—' : '<span class="order-money ' + (Number(order.remainingAmount) > 0 ? 'is-due' : 'is-paid') + '">Rs. ' + Number(order.remainingAmount).toFixed(2) + '</span>') + '</td>';

                    if (order.paymentStatus) {
                        row += '<td><div class="order-payment-cell"><span class="order-payment-status is-' + order.paymentStatus.key + '">' + order.paymentStatus.label + '</span>';
                        if (order.canReceivePayment) {
                            row += '<button type="button" class="order-payment-button" data-toggle="modal" data-target="#myModalpayment" data-customerid="' + order.customerId + '" data-orderid="' + order.orderId + '" data-remaining="' + order.remainingAmount + '"><i class="fas fa-wallet"></i> رقم وصول کریں</button>';
                        }
                        row += '</div></td>';
                    } else {
                        row += '<td>اجازت درکار ہے</td>';
                    }

                    row +=
                        '<td>' + order.created_at + '</td>' +
                        '<td>' + order.returnDate + '</td>' +
                        '<td>' + order.suitQuantity + '</td>' +
                        '<td>' + order.tailorName + '</td>';

                    var buttonClass = 'customer-order-status ' + order.btnClass;
                    var buttonStatus = order.button.toLowerCase();
                    var buttonText = order.button;
                    var nextStatuses = Array.isArray(order.nextStatuses) ? order.nextStatuses : [];

                    if (nextStatuses.length === 0) {
                        buttonClass += ' disabled';
                        row += '<td><button type="button" class="btn btn-sm ' + buttonClass + '" disabled>' + buttonText + '</button></td>';
                    } else {
                        row += '<td><button type="button" class="btn btn-sm ' + buttonClass + '" data-toggle="modal" data-target="#myModal" data-orderid="' + order.orderId + '" data-nextstatuses="' + encodeURIComponent(JSON.stringify(nextStatuses)) + '">' + buttonText + '</button></td>';
                    }

                    // Adding the select dropdown for rack number
                    row += '<td><select class="form-control px-1" id="rack-no" data-orderid="' + order.orderId + '" style="height:40px;width:100%;padding:6px 10px;margin:0;font-size:12px;">' +
                        '<option value="">ریک نمبر منتخب کریں</option>';

                    // Iterating over racks
                    $.each(order.racks, function (i, rack) {
                        row += '<option value="' + rack.rack_no + '"';
                        if (order.rack_no == rack.rack_no) {
                            row += ' selected';
                        }
                        row += '>' + rack.rack_no + '</option>';
                    });

                    row += '</select></td>' +
                        '<td><a class="btn btn-outline-primary btn-sm admin-order-status" href="/admin/order/edit/' + order.orderId + '">تبدیلی</a></td>' +
                        '<td><a class="btn btn-light btn-sm" href="/admin/order/prints/' + order.orderId + '" target="_blank" aria-label="آرڈر پرنٹ کریں"><i class="fa fa-print"></i></a></td>' +
                        '</tr>';

                    $('.tbody').append(row);
                });
                $('#cc-table-data-order-history').DataTable();
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
                alert('Error: ' + xhr.responseText);
            }
        });

    });

    // update rack no
    $(document).on('change', '#rack-no', function () {
        // Fetch CSRF token from the meta tag in your Blade template
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var orderId = $(this).data('orderid');
        var rackNo = $(this).val();

        // AJAX request to update rack_no
        $.ajax({
            url: '/admin/order/update-rack-no/' + orderId,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken // Include CSRF token
            },
            data: {
                rack_no: rackNo
            },
            success: function (response) {
                // Handle success if needed
                console.log('Rack number updated successfully');
            },
            error: function (xhr, status, error) {
                // Handle error if needed
                console.error('Error updating rack number:', error);
            }
        });
    });

    //customer sale display
    $(".sale").on('click', function () {
        var customer_id = $(this).data('id');
        // console.log(customer_id);
        var name = $(this).data('name');
        $('#cus_name').text(name);
        // console.log(name);

        $.ajax({
            type: 'GET',
            url: '/admin/getSale',
            data: {
                id: customer_id,
            },
            dataType: 'json',
            success: function (data) {
                $('#orderDetail').css('display', 'block');
                if ($.fn.DataTable.isDataTable('#cc-table-data-order-history')) {
                    $('#cc-table-data-order-history').DataTable().destroy();
                }
                $('.tbody').empty();
                $.each(data, function (index, sale) {
                    var row = '<tr>' +
                        '<td></td>' +
                        '<td>' + sale.number + '</td>' +
                        '<td>' + sale.totalPayment + '</td>' +
                        '<td>' + sale.created_at + '</td>' +
                        '<td>' + sale.brand + '</td>' +
                        '<td>' + sale.type + '</td>' +
                        '<td>' + sale.color + '</td>' +
                        '<td>' + sale.length + '</td>' +
                        '<td>' + sale.rate + '</td>' +
                        '<td><a href="/admin/prints/' + sale.salesId + '/' + customer_id + '" target="_blank"><i class="fa fa-print"></i></a></td>' +
                        '</tr>';
                    $('.tbody').append(row);
                });
                $('#cc-table-data-order-history').DataTable();
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
                alert('Error: ' + xhr.responseText);
            }
        });
    });





    $(".search").keyup(function () {

        var url = $(this).data('url');
        var sub_search = $("#search").val();
        $.ajax({
            type: 'GET',
            url: url,
            data: {
                sub_search: sub_search
            },
            success: function (data) {
                // console.log(data);
                $('#select').empty();
                $('#select').append(data);
            }
        });
    });

    $('.status').change(function () {
        var suit_status = $(this).val();
        var url = $(this).data('url');
        alert(suit_status);
    });
    // richtext
    // CKEDITOR.replace('address');

    // flatpickr("#myflatpickr", {

    //     dateFormat: "Y-m-d",
    //     maxDate: new Date(),
    //     mode: "range"
    // });

    // order status change
    $(document).on('click', '.customer-order-status', function () {
        var order_id = $(this).data('orderid');
        var encodedStatuses = $(this).attr('data-nextstatuses') || '';
        var nextStatuses = [];
        try {
            nextStatuses = JSON.parse(decodeURIComponent(encodedStatuses));
        } catch (error) {
            nextStatuses = [];
        }
        $('#order_id').val(order_id);
        var select = $('#myModal .order-status');
        select.empty();
        $.each(nextStatuses, function (_, status) {
            select.append($('<option>', { value: status.value, text: status.label }));
        });
        $('#submit-button').prop('disabled', nextStatuses.length === 0);
    });

    // now send notification for order complete to user
    $(document).on('click', '#submit-button', function () {
        var order_id = $('#order_id').val();
        var selectedStatus = $('.order-status').val();
        // console.log(order_id,selectedStatus);

        // check if selected status is complete
        if(selectedStatus === 'complete'){
            $.ajax({
                url : '/admin/order/order-complete',
                type: 'POST',
                data:{
                    order_id: order_id,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success:function(response){
                    console.log(response);
                },
                error: function (error) {
                    console.error("Notification error:", error);
                }
            })
        }
    });;

    //for tailoring
    $(document).on('click', '.customer_payment_paid', function () {
        var cus_id = $(this).data('customerid');
        $('#customer_id').val(cus_id);
        $('#payment_order_id').val('');
        $('#directPaymentAmount').removeAttr('max').val('');
        $('#paymentModalTitle').html('<i class="fas fa-wallet text-success ml-2"></i> گاہک کی ادائیگی درج کریں');
        $('#orderPaymentContext').hide().text('');
        // alert('tst');
    });

    $(document).on('click', '.order-payment-button', function () {
        var customerId = $(this).data('customerid');
        var orderId = $(this).data('orderid');
        var remaining = Number($(this).data('remaining') || 0).toFixed(2);

        $('#customer_id').val(customerId);
        $('#payment_order_id').val(orderId);
        $('#directPaymentAmount').attr('max', remaining).val('');
        $('#paymentModalTitle').html('<i class="fas fa-wallet text-success ml-2"></i> آرڈر #' + orderId + ' کی ادائیگی');
        $('#orderPaymentContext').text('اس آرڈر کا موجودہ بقایا: Rs. ' + remaining).show();
    });

    // for tailoring and sale both
    $(document).on('click', '.customer_payment', function () {
        var cus_id = $(this).data('customerid');
        $('#customer_id').val(cus_id);
        // alert('tst');
    });

    // var div = jQuery(".record").clone();
    var _div = $(".record").clone();
    $(".add_new").on("click", function () {
        var newdiv = $(_div).clone();
        $(".addmore").append(newdiv);
    });

    $(".remove-div").on("click", function () {
        $(".record:last").remove();
    });

    setTimeout(function () {
        $("#message").hide()
    }, 5000);

    $(document).on("change", "#tailor-selected", function (e) {
        var tailor_id = $(this).val();

        $.ajax({
            type: 'GET',
            url: '/admin/salary/' + tailor_id,
            success: function (data) {
                $("#tailor-rates").html(data);
                // console.log(data);
            }
        });

    });

    //design price
    $(document).on("change", "#design-selected", function (e) {
        var design_value = $(this).val();
        console.log(design_value);

        // Split the design_value based on the separator '-'
        var design_parts = design_value.split('-');

        // design_parts[0] will contain the design name and design_parts[1] will contain the design id
        var design_name = design_parts[0].trim();
        var design_id = design_parts[1].trim();


        $.ajax({
            type: 'GET',
            url: '/admin/design/price/' + design_id,
            success: function (data) {
                $("#designPrice").val(data);
                // console.log(data);
            }
        });

    });
});
