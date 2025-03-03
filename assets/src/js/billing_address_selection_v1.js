jQuery(document).ready(function($) {

    // Biến lưu mã tỉnh đang sở hữu danh sách quận huyện trong selectbox
    var currentProvinceForDistrict = null;
    // Biến lưu mã quận đang sở hữu danh sách xã/phường trong selectbox
    var currentDistrictForWard = null;
    // Biến lưu mã tỉnh đang hiển thị (đã tải) phương thức thanh toán
    var currentProvinceForPayment = null;

    // Cache danh sách quận huyện theo mã tỉnh
    var provinceDistrictData = {};

    // Cache danh sách xã/phường theo cặp (mã tỉnh, mã quận)
    var wardData = {}; // Cấu trúc: wardData[provinceCode][districtCode] = { loaded: true, data: { wardCode: wardName, ... } }

    // --- Các hàm hỗ trợ load dữ liệu vào select box ---

    function populateDistrictSelect(data) {
        var $districtSelect = $('#address-billing_city');
        $districtSelect.empty();
        $.each(data, function(districtCode, districtName) {
            $districtSelect.append(
                $('<option></option>').val(districtCode).text(districtName)
            );
        });
        // Sau khi load quận huyện, reset selectbox xã/phường
        $('#address-billing_ward').empty().append(
            $('<option></option>').val('').text('Chọn xã/phường')
        );
    }

    function populateWardSelect(data) {
        var $wardSelect = $('#address-billing_ward');
        $wardSelect.empty();
        $.each(data, function(wardCode, wardName) {
            $wardSelect.append(
                $('<option></option>').val(wardCode).text(wardName)
            );
        });
    }

    // --- Sự kiện cho selectbox Tỉnh/Thành ---
    $('#address-billing_state').on('change', function() {
        var provinceCode = $(this).val();

        // Nếu không chọn tỉnh nào, reset các select liên quan
        if (!provinceCode) {
            $('#address-billing_city').empty().append(
                $('<option></option>').val('').text('Chọn quận/huyện/t.x')
            );
            $('#address-billing_ward').empty().append(
                $('<option></option>').val('').text('Chọn xã/phường')
            );
            currentProvinceForDistrict = null;
            currentProvinceForPayment = null;
            return;
        }

        // Khi tỉnh thay đổi, reset dữ liệu của Quận/Huyện và Xã/Phường
        currentDistrictForWard = null;
        $('#address-billing_city').empty().append(
            $('<option></option>').val('').text('Chọn quận/huyện/t.x')
        );
        $('#address-billing_ward').empty().append(
            $('<option></option>').val('').text('Chọn xã/phường')
        );

        // Kiểm tra xem mã tỉnh mới có khác với mã đang hiển thị phương thức thanh toán không
        if (currentProvinceForPayment !== provinceCode) {
            if (typeof ithanLoadPaymentMethods === 'function') {
                ithanLoadPaymentMethods(provinceCode);
                currentProvinceForPayment = provinceCode;
            }
        }
    });

    // --- Sự kiện cho selectbox Quận/Huyện ---

    // 1. Sự kiện click vào selectbox Quận/Huyện:
    $('#address-billing_city').on('click', function() {
        var selectedProvince = $('#address-billing_state').val();
        if (!selectedProvince) {
            return; // Nếu chưa chọn tỉnh, không làm gì
        }
        // Kiểm tra phương thức thanh toán: nếu mã tỉnh trong selectbox khác với mã đã tải thì gọi hàm
        if (currentProvinceForPayment !== selectedProvince) {
            if (typeof ithanLoadPaymentMethods === 'function') {
                ithanLoadPaymentMethods(selectedProvince);
                currentProvinceForPayment = selectedProvince;
            }
        }
        // Tiếp theo, kiểm tra dữ liệu Quận/Huyện của tỉnh
        if (currentProvinceForDistrict !== selectedProvince) {
            if (provinceDistrictData.hasOwnProperty(selectedProvince) &&
                provinceDistrictData[selectedProvince].loaded) {
                populateDistrictSelect(provinceDistrictData[selectedProvince].data);
                currentProvinceForDistrict = selectedProvince;
            } else {
                // Nếu chưa có dữ liệu, gửi AJAX để tải danh sách Quận/Huyện theo mã tỉnh
                $.ajax({
                    url: ithan_billing_address_ajax_obj.ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'ithan_load_districts',
                        province_code: selectedProvince
                    },
                    success: function(response) {
                        provinceDistrictData[selectedProvince] = {
                            loaded: true,
                            data: response
                        };
                        populateDistrictSelect(response);
                        currentProvinceForDistrict = selectedProvince;
                    },
                    error: function() {
                        alert('Không thể tải danh sách quận/huyện. Vui lòng thử lại!');
                    }
                });
            }
        }
    });

    // 2. Sự kiện change của selectbox Quận/Huyện (để load danh sách Xã/Phường):
    $('#address-billing_city').on('change', function() {
        var selectedProvince = $('#address-billing_state').val();
        var districtCode = $(this).val();

        // Nếu không có quận nào được chọn, reset selectbox Xã/Phường
        if (!districtCode) {
            $('#address-billing_ward').empty().append(
                $('<option></option>').val('').text('Chọn xã/phường')
            );
            currentDistrictForWard = null;
            return;
        }

        // Kiểm tra cache: nếu dữ liệu Xã/Phường của cặp (tỉnh, quận) đã có thì load luôn
        if (wardData[selectedProvince] &&
            wardData[selectedProvince][districtCode] &&
            wardData[selectedProvince][districtCode].loaded) {
            populateWardSelect(wardData[selectedProvince][districtCode].data);
            currentDistrictForWard = districtCode;
        } else {
            // Nếu chưa có, gửi AJAX để lấy danh sách Xã/Phường cho Quận được chọn
            $.ajax({
                url: ithan_billing_address_ajax_obj.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'ithan_load_wards',
                    district_code: districtCode
                },
                success: function(response) {
                    if (!wardData[selectedProvince]) {
                        wardData[selectedProvince] = {};
                    }
                    wardData[selectedProvince][districtCode] = {
                        loaded: true,
                        data: response
                    };
                    populateWardSelect(response);
                    currentDistrictForWard = districtCode;
                },
                error: function() {
                    alert('Không thể tải danh sách xã/phường. Vui lòng thử lại!');
                }
            });
        }
    });

    // --- Sự kiện cho selectbox Xã/Phường ---
    $('#address-billing_ward').on('click', function() {
        var selectedProvince = $('#address-billing_state').val();
        var selectedDistrict = $('#address-billing_city').val();
        if (!selectedDistrict) {
            return; // Nếu chưa chọn quận, không làm gì
        }
        // Nếu mã quận trong biến lưu khác với mã quận hiện đang chọn, kiểm tra lại dữ liệu Xã/Phường
        if (currentDistrictForWard !== selectedDistrict) {
            if (wardData[selectedProvince] &&
                wardData[selectedProvince][selectedDistrict] &&
                wardData[selectedProvince][selectedDistrict].loaded) {
                populateWardSelect(wardData[selectedProvince][selectedDistrict].data);
                currentDistrictForWard = selectedDistrict;
            } else {
                $.ajax({
                    url: ithan_billing_address_ajax_obj.ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'ithan_load_wards',
                        district_code: selectedDistrict
                    },
                    success: function(response) {
                        if (!wardData[selectedProvince]) {
                            wardData[selectedProvince] = {};
                        }
                        wardData[selectedProvince][selectedDistrict] = {
                            loaded: true,
                            data: response
                        };
                        populateWardSelect(response);
                        currentDistrictForWard = selectedDistrict;
                    },
                    error: function() {
                        alert('Không thể tải danh sách xã/phường. Vui lòng thử lại!');
                    }
                });
            }
        }
    });

});

// Nếu hàm ithanLoadPaymentMethods chưa được định nghĩa (plugin khác sẽ định nghĩa), fake hàm này.
// if (typeof ithanLoadPaymentMethods !== 'function') {
//     function ithanLoadPaymentMethods(provinceCode) {
//         alert('Đang gọi phương thức thanh toán cho tỉnh: ' + provinceCode);
//     }
// }
