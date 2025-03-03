jQuery(document).ready(function($) {

    // Các biến trạng thái cục bộ cho từng tab (không chia sẻ qua localStorage)
    var currentProvinceForDistrict = null;
    var currentDistrictForWard = null;
    var currentProvinceForPayment = null;

    // Cache dữ liệu (chỉ chia sẻ dữ liệu danh sách, không chia sẻ lựa chọn đang được chọn)
    var provinceDistrictData = JSON.parse(localStorage.getItem('provinceDistrictData')) || {};
    var wardData = JSON.parse(localStorage.getItem('wardData')) || {};

    // Hàm lưu cache (chỉ lưu provinceDistrictData và wardData)
    function saveCache() {
        localStorage.setItem('provinceDistrictData', JSON.stringify(provinceDistrictData));
        localStorage.setItem('wardData', JSON.stringify(wardData));
    }

    // Lắng nghe sự kiện storage để cập nhật cache từ các tab khác
    window.addEventListener('storage', function(e) {
        if (e.key === 'provinceDistrictData') {
            provinceDistrictData = JSON.parse(e.newValue) || {};
        }
        if (e.key === 'wardData') {
            wardData = JSON.parse(e.newValue) || {};
        }
    });

    // --- Các hàm hỗ trợ load dữ liệu vào select box ---
    function populateDistrictSelect(data) {
        var $districtSelect = $('#address-billing_city');
        $districtSelect.empty();
        $.each(data, function(districtCode, districtName) {
            $districtSelect.append(
                $('<option></option>').val(districtCode).text(districtName)
            );
        });
        // Sau khi load danh sách Quận/Huyện, reset selectbox Xã/Phường
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

        // Khi tỉnh thay đổi, reset các selectbox Quận/Huyện và Xã/Phường
        currentDistrictForWard = null;
        $('#address-billing_city').empty().append(
            $('<option></option>').val('').text('Chọn quận/huyện/t.x')
        );
        $('#address-billing_ward').empty().append(
            $('<option></option>').val('').text('Chọn xã/phường')
        );

        // Lấy mã quốc gia từ trường hidden
        var billingCountry = $('#billing_country').val();

        // Nếu mã tỉnh mới khác với mã đã hiển thị phương thức thanh toán, gọi hàm ithanLoadPaymentMethods với mã quốc gia và mã tỉnh
        if (currentProvinceForPayment !== provinceCode) {
            if (typeof ithanLoadPaymentMethods === 'function') {
                ithanLoadPaymentMethods(billingCountry, provinceCode);
                currentProvinceForPayment = provinceCode;
            }
        }
    });

    // --- Sự kiện cho selectbox Quận/Huyện ---
    // 1. Sự kiện click vào selectbox Quận/Huyện:
    $('#address-billing_city').on('click', function() {
        
        var selectedProvince = $('#address-billing_state').val();
        if (!selectedProvince) {
            return;
        }
        // Lấy mã quốc gia từ trường hidden
        var billingCountry = $('#billing_country').val();
        // Kiểm tra phương thức thanh toán: nếu mã tỉnh chưa khớp, gọi hàm
        if (currentProvinceForPayment !== selectedProvince) {
            if (typeof ithanLoadPaymentMethods === 'function') {
                ithanLoadPaymentMethods(billingCountry, selectedProvince);
                currentProvinceForPayment = selectedProvince;
            }
        }
        // Kiểm tra cache danh sách Quận/Huyện theo tỉnh
        if (currentProvinceForDistrict !== selectedProvince) {
            if (provinceDistrictData.hasOwnProperty(selectedProvince) &&
                provinceDistrictData[selectedProvince].loaded) {
                populateDistrictSelect(provinceDistrictData[selectedProvince].data);
                currentProvinceForDistrict = selectedProvince;
                saveCache();
            } else {
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
                        saveCache();
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

        if (!districtCode) {
            $('#address-billing_ward').empty().append(
                $('<option></option>').val('').text('Chọn xã/phường')
            );
            currentDistrictForWard = null;
            return;
        }

        // Kiểm tra cache danh sách Xã/Phường theo cặp (tỉnh, quận)
        if (wardData[selectedProvince] &&
            wardData[selectedProvince][districtCode] &&
            wardData[selectedProvince][districtCode].loaded) {
            populateWardSelect(wardData[selectedProvince][districtCode].data);
            currentDistrictForWard = districtCode;
            saveCache();
        } else {
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
                    saveCache();
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
            return;
        }
        if (currentDistrictForWard !== selectedDistrict) {
            if (wardData[selectedProvince] &&
                wardData[selectedProvince][selectedDistrict] &&
                wardData[selectedProvince][selectedDistrict].loaded) {
                populateWardSelect(wardData[selectedProvince][selectedDistrict].data);
                currentDistrictForWard = selectedDistrict;
                saveCache();
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
                        saveCache();
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
//     function ithanLoadPaymentMethods(countryCode, provinceCode) {
//         alert('Đang gọi phương thức thanh toán cho quốc gia: ' + countryCode + ' và tỉnh: ' + provinceCode);
//     }
// }
