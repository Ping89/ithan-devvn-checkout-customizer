jQuery(document).ready(function($) {

    // Khi người dùng thay đổi tỉnh/thành:
    $('#address-billing_state').on('change', function() {
        var provinceCode = $(this).val(); // giá trị, vd: 'HANOI'
        
        // Gọi AJAX đến server
        $.ajax({
            url: ithan_billing_address_ajax_obj.ajaxurl,  // hoặc custom endpoint
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ithan_load_districts', // Tên action AJAX
                province_code: provinceCode
            },
            success: function(response) {
                // response chứa mảng Quận/Huyện
                // Xóa option cũ của #billing_city
                $('#address-billing_city').empty();
                
                // Thêm option mới
                $.each(response, function(value, label) {
                    // value: '001', label: 'Quận Ba Đình' v.v.
                    $('#address-billing_city').append(
                        $('<option></option>').val(value).text(label)
                    );
                });

                // Sau khi city thay đổi, ward cũng reset
                $('#address-billing_ward').empty().append(
                    $('<option></option>').val('').text('Chọn xã/phường')
                );
            },
            error: function() {
                alert('Không thể load quận/huyện. Vui lòng thử lại!');
            }
        });
    });

    // Tương tự, khi người dùng thay đổi quận/huyện:
    $('#address-billing_city').on('change', function(){
        var districtCode = $(this).val(); // ví dụ: '001'
        
        $.ajax({
            url: ithan_billing_address_ajax_obj.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ithan_load_wards',
                district_code: districtCode
            },
            success: function(response) {
                $('#address-billing_ward').empty();
                $.each(response, function(value, label) {
                    $('#address-billing_ward').append(
                        $('<option></option>').val(value).text(label)
                    );
                });
            },
            error: function(){
                alert('Không thể load xã/phường. Vui lòng thử lại!');
            }
        });
    });
    
});
