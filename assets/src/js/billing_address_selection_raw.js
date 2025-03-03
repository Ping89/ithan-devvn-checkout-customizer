jQuery(document).ready(function($) {

    // Biến lưu mã tỉnh đang sở hữu danh sách quận huyện trong selectbox
    var currentProvinceForDistrict = null;
    // Biến lưu mã quận đang sở hữu danh sách xã/phường trong selectbox
    var currentDistrictForWard = null;

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
            return;
        }

        // Khi tỉnh thay đổi, reset dữ liệu của quận và xã
        currentDistrictForWard = null;
        $('#address-billing_city').empty().append(
            $('<option></option>').val('').text('Chọn quận/huyện/t.x')
        );
        $('#address-billing_ward').empty().append(
            $('<option></option>').val('').text('Chọn xã/phường')
        );
        // (Không cần load danh sách quận ngay, vì sẽ được kiểm tra khi click vào selectbox quận)
    });

    // --- Sự kiện cho selectbox Quận/Huyện ---

    // 1. Sự kiện click vào selectbox Quận/Huyện:
    $('#address-billing_city').on('click', function() {
        var selectedProvince = $('#address-billing_state').val();
        if (!selectedProvince) {
            return; // Nếu chưa chọn tỉnh, không làm gì
        }
        // Nếu mã tỉnh trong biến lưu khác với mã tỉnh hiện đang chọn
        if (currentProvinceForDistrict !== selectedProvince) {
            // Kiểm tra cache: nếu danh sách quận huyện của tỉnh này đã có
            if (provinceDistrictData.hasOwnProperty(selectedProvince) &&
                provinceDistrictData[selectedProvince].loaded) {
                populateDistrictSelect(provinceDistrictData[selectedProvince].data);
                currentProvinceForDistrict = selectedProvince;
            } else {
                // Chưa có dữ liệu, gửi AJAX để lấy danh sách quận huyện theo mã tỉnh
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

    // 2. Sự kiện change của selectbox Quận/Huyện:
    // Khi lựa chọn quận/huyện thay đổi, load danh sách xã/phường tương ứng.
    $('#address-billing_city').on('change', function() {
        var selectedProvince = $('#address-billing_state').val();
        var districtCode = $(this).val();

        // Nếu không có quận nào được chọn, reset selectbox xã/phường
        if (!districtCode) {
            $('#address-billing_ward').empty().append(
                $('<option></option>').val('').text('Chọn xã/phường')
            );
            currentDistrictForWard = null;
            return;
        }

        // Kiểm tra cache: nếu dữ liệu xã/phường của cặp (tỉnh, quận) đã có
        if (wardData[selectedProvince] &&
            wardData[selectedProvince][districtCode] &&
            wardData[selectedProvince][districtCode].loaded) {
            populateWardSelect(wardData[selectedProvince][districtCode].data);
            currentDistrictForWard = districtCode;
        } else {
            // Chưa có dữ liệu, gửi AJAX để lấy danh sách xã/phường cho quận được chọn
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

    // Sự kiện click vào selectbox Xã/Phường tương tự:
    $('#address-billing_ward').on('click', function() {
        var selectedProvince = $('#address-billing_state').val();
        var selectedDistrict = $('#address-billing_city').val();
        if (!selectedDistrict) {
            return; // Nếu chưa chọn quận, không làm gì
        }
        // Nếu mã quận trong biến lưu khác với mã quận hiện đang chọn
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
