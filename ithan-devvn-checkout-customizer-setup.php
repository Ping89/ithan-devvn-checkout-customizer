<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

define('ITHANDECH_CHECKOUT_SLUG', 'ithan-devvn-checkout-for-woocommerce');
define('ITHANDEVVN_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Kiểm tra nếu WooCommerce đã được kích hoạt
function ithandech_is_woocommerce_active() {
    $active_plugins = (array) get_option('active_plugins', array());
    if (is_multisite()) {
        $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
    }
    return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins) || class_exists('WooCommerce');
}

// Kiểm tra nếu Woo Checkout Field Editor Pro đã được kích hoạt
function ithandech_is_woo_checkout_field_editor_pro_active() {
    $active_plugins = (array) get_option('active_plugins', array());
    if (is_multisite()) {
        $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
    }
    return in_array('woo-checkout-field-editor-pro/checkout-form-designer.php', $active_plugins)
        || array_key_exists('woo-checkout-field-editor-pro/checkout-form-designer.php', $active_plugins)
        || class_exists('Woo_Checkout_Field_Editor_Pro');
}

// Kiểm tra cả hai plugin đã được kích hoạt hay chưa
add_action('admin_init', 'ithandech_check_required_plugins_active');

function ithandech_check_required_plugins_active() {
    if ( !ithandech_is_woocommerce_active() ) {
        // Nếu WooCommerce chưa được kích hoạt, hiển thị thông báo yêu cầu kích hoạt WooCommerce
        add_action( 'admin_notices', 'ithandech_woocommerce_required_notice' );
    }

    if ( !ithandech_is_woo_checkout_field_editor_pro_active() ) {
        // Nếu Woo Checkout Field Editor Pro chưa được kích hoạt, hiển thị thông báo yêu cầu kích hoạt plugin này
        add_action( 'admin_notices', 'checkout_field_editor_required_notice' );
    }
}

// Thông báo yêu cầu kích hoạt WooCommerce
function ithandech_woocommerce_required_notice() {
    echo '<div class="error"><p><strong>' . esc_html(__('Bạn cần cài đặt và kích hoạt plugin "WooCommerce" trước khi sử dụng plugin ithan-devvn-checkout-for-woocommerce-plugin.', ITHANDECH_CHECKOUT_SLUG)) . '</strong></p></div>';
}

// Thông báo yêu cầu kích hoạt Woo Checkout Field Editor Pro
function checkout_field_editor_required_notice() {
    echo '<div class="error"><p><strong>' . esc_html(__('Bạn cần cài đặt và kích hoạt plugin "Woo Checkout Field Editor Pro" trước khi sử dụng plugin ithan-devvn-checkout-for-woocommerce-plugin.', ITHANDECH_CHECKOUT_SLUG)) . '</strong></p></div>';
}

// Bước 1: Nạp file
include_once ITHANDEVVN_PLUGIN_DIR . 'includes/ithan_wc_checkout_helper.php';
include_once ITHANDEVVN_PLUGIN_DIR . 'includes/tinh_thanhpho.php';
include_once ITHANDEVVN_PLUGIN_DIR . 'includes/quan_huyen.php';
include_once ITHANDEVVN_PLUGIN_DIR . 'includes/xa_phuong_thitran.php';

include_once ITHANDEVVN_PLUGIN_DIR . 'includes/ithandevvn_vietnam_shipping.php';

if ( ! function_exists( 'devvn_vietnam_shipping' ) ) {
    /**
     * Hàm trả về đối tượng Devvn_Vietnam_Shipping
     */
    function devvn_vietnam_shipping() {
        return \ithandech\devvncheckout\Vietnam_Shipping::instance();
    }
}

// Đăng ký callback cho AJAX
add_action('wp_ajax_ithan_load_districts', 'ithandech_load_districts_callback');
add_action('wp_ajax_nopriv_ithan_load_districts', 'ithandech_load_districts_callback');

function ithandech_load_districts_callback() {
    // Kiểm tra nonce để bảo vệ khỏi CSRF
    if ( !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ithan_load_address_nonce') ) {
        wp_send_json_error(array('message' => 'Nonce validation failed.'));
        exit;
    }

    // Lấy province_code từ AJAX và loại bỏ các dấu gạch chéo
    $province_code = isset($_POST['province_code']) ? sanitize_text_field(wp_unslash($_POST['province_code'])) : '';

    // Gọi hàm trong class plugin devvn_vietnam_shipping()
    $districts = devvn_vietnam_shipping()->get_list_district_select($province_code);

    // Trả về JSON
    wp_send_json($districts);
}


add_action('wp_ajax_ithan_load_wards', 'ithandech_load_wards_callback');
add_action('wp_ajax_nopriv_ithan_load_wards', 'ithandech_load_wards_callback');
function ithandech_load_wards_callback() {
    if ( !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ithan_load_address_nonce') ) {
        wp_send_json_error(array('message' => 'Nonce validation failed.'));
        exit;
    }

    $district_code = isset($_POST['district_code']) ? sanitize_text_field(wp_unslash($_POST['district_code'])) : '';

    $wards = devvn_vietnam_shipping()->get_list_village_select($district_code);

    wp_send_json($wards);
}

// 1) Khai báo states cho VN
add_filter('woocommerce_states', 'add_vietnam_states');
function add_vietnam_states( $states ) {
    global $tinh_thanhpho;
    $states['VN'] = $tinh_thanhpho; // mảng province
    return $states;
}

// 2) Tùy biến trường checkout
add_filter('woocommerce_checkout_fields', 'ithandech_custom_billing_fields');
function ithandech_custom_billing_fields( $fields ) {

    // Lấy province (vd. do người dùng chọn ở billing_state)
    // Ở đây ta chỉ ví dụ cứng:
    $default_province_code = '';
    $default_district_code = '';

    global $tinh_thanhpho;
    // Lấy mảng quận/huyện dựa trên $province_code
    $districts = devvn_vietnam_shipping()->get_list_district_select( $default_province_code );

    // Lấy mảng xã/phường dựa trên $district_code
    $wards = devvn_vietnam_shipping()->get_list_village_select( $default_district_code );

    // Tùy chỉnh trường billing_first_name thành hidden
    if ( isset( $fields['billing']['billing_first_name'] ) ) {
        // Nếu muốn vẫn yêu cầu (và gán một giá trị mặc định) thì giữ required = true
        // Nếu không cần thiết phải bắt buộc nhập, bạn có thể chuyển required = false
        $fields['billing']['billing_first_name']['type']    = 'hidden';
        $fields['billing']['billing_first_name']['default'] = 'Anh'; // hoặc giá trị rỗng nếu cần
        // Bạn có thể bỏ label nếu muốn (vì trường hidden sẽ không hiển thị)
        $fields['billing']['billing_first_name']['label']   = '';
    }

    // Tùy chỉnh trường billing_last_name thành hidden
    if ( isset( $fields['billing']['billing_last_name'] ) ) {
        $fields['billing']['billing_last_name']['default'] = '';
        $fields['billing']['billing_last_name']['label']   = 'Họ và tên';

        $fields['billing']['billing_last_name']['required'] = true;
        // Thêm class hoặc id
        $fields['billing']['billing_last_name']['class'][]  = 'validate-last-name';
        $fields['billing']['billing_last_name']['id']       = 'billing_last_name';

        // Thêm placeholder
        $fields['billing']['billing_last_name']['placeholder'] = 'Nhập họ và tên';
    }

    // Tùy chỉnh trường billing_first_name thành hidden
    if ( isset( $fields['billing']['billing_country'] ) ) {
        $fields['billing']['billing_country']['default'] = 'VN'; // hoặc giá trị rỗng nếu cần
        $fields['billing']['billing_country']['type']    = 'hidden';
    }

    if ( isset( $fields['billing']['billing_province_code'] ) ) {
        $fields['billing']['billing_province_code']['required'] = true;
        // Loại field = state -> WooCommerce sẽ dùng mảng ở trên
        $fields['billing']['billing_province_code']['options']  = $tinh_thanhpho;
        $fields['billing']['billing_province_code']['priority']  = 42;

        // Thêm class hoặc id
        $fields['billing']['billing_province_code']['class'][]  = 'address__billing_state_select';
        $fields['billing']['billing_province_code']['id']       = 'address-billing_state';
    }

    if ( isset( $fields['billing']['billing_district_code'] ) ) {
        $fields['billing']['billing_district_code']['type']      = 'select';
        $fields['billing']['billing_district_code']['options']   = $districts;
        $fields['billing']['billing_district_code']['priority']   = 43;

        $fields['billing']['billing_district_code']['class'][]  = 'address__billing_city_select';
        $fields['billing']['billing_district_code']['id']       = 'address-billing_city';
    }

    if ( isset( $fields['billing']['billing_ward_code'] ) ) {
        $fields['billing']['billing_ward_code']['type']      = 'select';
        $fields['billing']['billing_ward_code']['options']   = $wards;
        $fields['billing']['billing_ward_code']['priority']   = 44;

        $fields['billing']['billing_ward_code']['class'][]  = 'address__billing_ward_select';
        $fields['billing']['billing_ward_code']['id']       = 'address-billing_ward';
    }


    // Bỏ field ra khỏi form
    unset($fields['billing']['billing_city']);
    unset($fields['billing']['billing_address_1']);

    return $fields;
}

add_action('wp_head', 'ithandech_load_svg_icons_conditionally');
function ithandech_load_svg_icons_conditionally() {
    echo '<svg style="display: none;">
            <symbol id="user" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
            </symbol>
            <symbol id="phone" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 4.5a.75.75 0 01.75-.75h2.457c.72 0 1.362.486 1.543 1.184l.53 2.118a.75.75 0 01-.216.67l-1.305 1.305a11.95 11.95 0 006.198 6.198l1.305-1.305a.75.75 0 01.67-.216l2.118.53c.698.181 1.184.823 1.184 1.543v2.457a.75.75 0 01-.75.75h-2.25C9.163 21 3 14.837 3 7.5V4.5z" />
            </symbol>
            <symbol id="envelope" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.125A2.25 2.25 0 014.5 4.875h15a2.25 2.25 0 012.25 2.25v9a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25v-9z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M22.5 7.125l-9.008 5.337a.75.75 0 01-.684 0L3 7.125" />
            </symbol>
            <symbol id="zipcode" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none">
                <!-- Hộp chữ nhật với các góc bo tròn -->
                <rect x="3" y="4" width="18" height="16" rx="2" ry="2" stroke-linecap="round" stroke-linejoin="round"></rect>
                <!-- Các đường ngang biểu thị nội dung (ví dụ: mã ZIP) -->
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 9h10M7 13h4"></path>
            </symbol>
            <symbol id="location" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" 
                    d="M12 2.25c-3.724 0-6.75 2.993-6.75 6.682 0 4.188 3.338 7.7 6.034 10.577a1.75 1.75 0 0 0 2.432 0c2.696-2.877 6.034-6.389 6.034-10.577 0-3.689-3.026-6.682-6.75-6.682z"/>
                <path stroke-linecap="round" stroke-linejoin="round" 
                    d="M12 11.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5z"/>
            </symbol>
            <!-- Icon Exclamation -->
            <symbol id="exclamation" viewBox="0 0 16 16" fill="currentColor">
                <path d="M8 0a8 8 0 1 0 8 8A8 8 0 0 0 8 0zm0 1a7 7 0 1 1 0 14A7 7 0 0 1 8 1zm-.5 4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5V5a.5.5 0 0 1 .5-.5h1zm0 7a.5.5 0 0 1 .5.5h1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5h1z"/>
            </symbol>
            <!-- Icon Close (X) -->
            <symbol id="close" viewBox="0 0 16 16" fill="currentColor">
                <path d="M11.742 4.258a1 1 0 1 0-1.484-1.328L8 6.716 5.742 3.93a1 1 0 1 0-1.484 1.328L6.716 8l-3.458 4.742a1 1 0 0 0 1.484 1.328L8 9.284l2.258 2.886a1 1 0 1 0 1.484-1.328L9.284 8l3.458-4.742z"/>
            </symbol>
            <symbol id="icon--error" viewBox="0 0 32 32" >
                <circle r="15" cx="16" cy="16" fill="none" stroke="hsl(13,90%,55%)" stroke-width="2" />
                <line x1="10" y1="10" x2="22" y2="22" stroke="hsl(13,90%,55%)" stroke-width="2" stroke-linecap="round" />
                <line x1="22" y1="10" x2="10" y2="22" stroke="hsl(13,90%,55%)" stroke-width="2" stroke-linecap="round" />
            </symbol>
        </svg>';
}


add_filter( 'woocommerce_form_field', 'ithandech_customize_fields_with_icon', 10, 4 );
function ithandech_customize_fields_with_icon( $field, $key, $args, $value ) {

    // Danh sách các trường cần tùy biến (và biểu tượng SVG tương ứng)
    $icon_map = [
        'billing_last_name' => 'user',
        'billing_phone'     => 'phone',
        'billing_email'     => 'envelope',
        'billing_postcode'  => 'zipcode',
        'billing_address_2' => 'location'
    ];

    // Kiểm tra xem $key có nằm trong danh sách cần tùy biến không
    if ( ! array_key_exists( $key, $icon_map ) ) {
        return $field; // Không tùy biến, trả về HTML gốc
    }

    // Kiểm tra type (text, tel, email...). Nếu khác, có thể return gốc hoặc tùy biến tiếp
    if ( ! in_array( $args['type'], [ 'text', 'tel', 'email' ], true ) ) {
        return $field;
    }

    // Lấy icon ID từ mảng
    $icon_id = $icon_map[ $key ];

    // Xử lý label (có dấu sao nếu required)
    $label_html = '';
    if ( ! empty( $args['label'] ) ) {
        $label_text = $args['label'];
        if ( ! empty( $args['required'] ) ) {
            $label_text .= ' <span class="required">*</span>';
        }
        $label_html = sprintf(
            '<label for="%s" class="checkout-label tooltip__jump display__none">%s</label>',
            esc_attr( $args['id'] ),
            wp_kses_post( $label_text )
        );
    }

    // Tạo input
    $input_html = sprintf(
        '<input type="%s" class="input %s" name="%s" id="%s" placeholder="%s" value="%s" %s />',
        esc_attr( $args['type'] ),
        isset( $args['input_class'] ) ? esc_attr( join( ' ', (array) $args['input_class'] ) ) : '',
        esc_attr( $key ),
        esc_attr( $args['id'] ),
        esc_attr( $args['placeholder'] ),
        esc_attr( $value ),
        ! empty( $args['custom_attributes'] )
            ? implode( ' ', array_map( function( $attr, $val ) {
            return esc_attr( $attr ) . '="' . esc_attr( $val ) . '"';
        }, array_keys( $args['custom_attributes'] ), $args['custom_attributes'] ) )
            : ''
    );

    // Mô tả (nếu có)
    $description_html = '';
    if ( ! empty( $args['description'] ) ) {
        $description_html = '<span class="description">' . wp_kses_post( $args['description'] ) . '</span>';
    }

    // Tạo class wrapper theo tên field (để CSS riêng từng loại nếu muốn)
    // Ví dụ: billing_last_name -> "last_name-wrapper"
    $wrapper_class = str_replace( 'billing_', '', $key ) . '-wrapper';

    /**
     * 1) Kiểm tra trường có required không?
     * 2) Nếu có lỗi từ WooCommerce (trong $args['errors']), thì hiển thị lỗi đó.
     * 3) Nếu không có lỗi nhưng vẫn là trường required, bạn có thể hiển thị
     *    một tooltip mặc định, hoặc để trống tuỳ ý.
     */
    $error_html = '';
    if ( ! empty( $args['required'] ) ) {
        // Nếu WooCommerce phát hiện lỗi, nó sẽ lưu vào $args['errors'] (mảng)
        if ( ! empty( $args['errors'] ) && is_array( $args['errors'] ) ) {
            // Nếu có nhiều lỗi, gộp chúng lại (hoặc bạn có thể hiển thị từng lỗi)
            $error_message = implode( ', ', $args['errors'] );
            $error_html = '<span class="error">' . esc_html( $error_message ) . '</span>';
        } else {
            // Mặc định (chưa có lỗi gì hoặc chưa submit form), vẫn có thể hiển thị tooltip
            // hoặc bạn để trống nếu chỉ muốn hiển thị lỗi khi thật sự có lỗi.
            $error_html = '<span class="error">Bắt buộc</span>';
        }
    }

    // Xây dựng HTML cuối
    $field = sprintf(
        '<div class="wc__form-field has__tooltips">
            %s
            <div class="input-container">
                <div class="input-wrapper %s">
                    <span>
                        <svg class="icon"><use href="#%s"></use></svg>
                    </span>
                    %s
                </div>
                %s <!-- Chèn tooltip lỗi (nếu có) vào đây -->
            </div>
            %s
        </div>',
        $label_html,
        esc_attr( $wrapper_class ),
        esc_attr( $icon_id ),
        $input_html,
        $error_html,
        $description_html
    );

    return $field;
}

add_filter( 'woocommerce_form_field', 'ithandech_customize_location_fields', 10, 4 );
function ithandech_customize_location_fields( $field, $key, $args, $value ) {
    // Danh sách các trường select muốn tùy biến
    $location_fields = array( 'billing_province_code', 'billing_district_code', 'billing_ward_code' );

    // Nếu $key không nằm trong mảng, hoặc type != select, trả về HTML gốc
    if ( ! in_array( $key, $location_fields, true ) || 'select' !== $args['type'] ) {
        return $field;
    }

    // === 1) Tạo label (nếu có) ===
    $label_html = '';
    if ( ! empty( $args['label'] ) ) {
        // Nếu field required, thêm dấu *
        $label_text = $args['label'];
        if ( ! empty( $args['required'] ) ) {
            $label_text .= ' <span class="required">*</span>';
        }
        $label_html = sprintf(
            '<label for="%s" class="checkout-label location__tooltip-jump">%s</label>',
            esc_attr( $args['id'] ),
            wp_kses_post( $label_text )
        );
    }

    // === 2) Tạo HTML cho <select> ===
    $options_html = '';
    if ( ! empty( $args['options'] ) && is_array( $args['options'] ) ) {
        foreach ( $args['options'] as $option_value => $option_label ) {
            // So sánh $value (giá trị hiện tại) để chọn selected
            $selected = selected( $value, $option_value, false );
            $options_html .= sprintf(
                '<option value="%s" %s>%s</option>',
                esc_attr( $option_value ),
                $selected,
                esc_html( $option_label )
            );
        }
    }

    // Kiểm tra nếu trường required để thêm aria-required="true"
    $required_attr = ! empty( $args['required'] ) ? 'aria-required="true"' : '';

    $select_html = sprintf(
        '<select name="%s" id="%s" class="%s" %s>%s</select>',
        esc_attr( $key ),
        esc_attr( $args['id'] ),
        // Gom các class cũ + class mới (nếu cần)
        esc_attr( trim( implode( ' ', (array) $args['input_class'] ) ) ),
        $required_attr,
        $options_html
    );

    // === 3) Mô tả (nếu có) ===
    $description_html = '';
    if ( ! empty( $args['description'] ) ) {
        $description_html = '<span class="description">' . wp_kses_post( $args['description'] ) . '</span>';
    }

    // === 4) Xử lý tooltip lỗi cho trường required ===
    $error_html = '';
    if ( ! empty( $args['required'] ) ) {
        if ( ! empty( $args['errors'] ) && is_array( $args['errors'] ) ) {
            // Nếu có lỗi, gộp lại các thông báo lỗi (hoặc bạn có thể hiển thị từng lỗi riêng biệt)
            $error_message = implode( ', ', $args['errors'] );
            $error_html = '<span class="error">' . esc_html( $error_message ) . '</span>';
        } else {
            // Nếu chưa có lỗi (hoặc chưa submit form), bạn có thể để tooltip mặc định hoặc bỏ qua
            $error_html = '<span class="error">Bắt buộc</span>';
        }
    }

    // === 5) Gói <select> trong .input-wrapper, kèm icon location và tooltip lỗi ===
    $field = sprintf(
        '<div class="wc__form-field has__tooltips">
            %s
            <div class="input-container">
                <div class="input-wrapper location-wrapper">
                    <span>
                        <svg class="icon"><use href="#location"></use></svg>
                    </span>
                    %s
                </div>
                %s
            </div>
            %s
        </div>
        ',
        $label_html,
        $select_html,
        $error_html,
        $description_html
    );

    return $field;
}

add_action('woocommerce_checkout_process', 'ithandech_buy_now_custom_validate_checkout_fields');
function ithandech_buy_now_custom_validate_checkout_fields() {
    if ( empty( $_POST['billing_province_code'] ) ) {
        wc_add_notice( __( 'Cung cấp địa chỉ tỉnh/thành phố.', ITHANDECH_CHECKOUT_SLUG ), 'error' );
    }
    if ( empty( $_POST['billing_district_code'] ) ) {
        wc_add_notice( __( 'Cung cấp địa chỉ quận/huyện/thị xã...', ITHANDECH_CHECKOUT_SLUG ), 'error' );
    }
}

add_action('woocommerce_checkout_create_order', 'ithandech_add_checkout_meta_to_order', 10, 2);
function ithandech_add_checkout_meta_to_order($order, $data) {
    // ------------ XỬ LÝ BILLING ------------
    $billing_address_1 = '';

    // 1) Tỉnh/Thành (billing_province_code)
    $province_code = ! empty($data['billing_province_code'])
        ? sanitize_text_field($data['billing_province_code'])
        : ( ! empty($_POST['billing_province_code'])
            ? sanitize_text_field(wp_unslash($_POST['billing_province_code']))
            : ''
        );
    if ( $province_code ) {
        $province_name = devvn_vietnam_shipping()->get_province_name_by_code($province_code);
        // Lưu vào meta city (hoặc _billing_state, tuỳ ý)
        $order->update_meta_data('_billing_city', $province_name);
        $billing_address_1 .= $province_name;
    }

    // 2) Quận/Huyện (billing_district_code)
    $district_code = ! empty($data['billing_district_code'])
        ? sanitize_text_field($data['billing_district_code'])
        : ( ! empty($_POST['billing_district_code'])
            ? sanitize_text_field(wp_unslash($_POST['billing_district_code']))
            : ''
        );
    if ( $district_code ) {
        $district_name = devvn_vietnam_shipping()->get_district_name_by_code($district_code);
        $billing_address_1 .= ' - ' . $district_name;
    }

    // 3) Xã/Phường (billing_ward_code)
    $ward_code = ! empty($data['billing_ward_code'])
        ? sanitize_text_field($data['billing_ward_code'])
        : ( ! empty($_POST['billing_ward_code'])
            ? sanitize_text_field(wp_unslash($_POST['billing_ward_code']))
            : ''
        );
    if ( $ward_code ) {
        $ward_name = devvn_vietnam_shipping()->get_ward_name_by_code($ward_code);
        $billing_address_1 .= ' - ' . $ward_name;
    }

    // Cập nhật meta _billing_address_1
    $order->update_meta_data('_billing_address_1', $billing_address_1);

    // 4) Địa chỉ cụ thể (billing_address_2)
    $billing_address_2 = ! empty($data['billing_address_2'])
        ? sanitize_text_field($data['billing_address_2'])
        : ( ! empty($_POST['billing_address_2'])
            ? sanitize_text_field(wp_unslash($_POST['billing_address_2']))
            : ''
        );
    $order->update_meta_data('_billing_address_2', $billing_address_2);

    // 5) SĐT (billing_phone)
    $billing_phone = ! empty($data['billing_phone'])
        ? sanitize_text_field($data['billing_phone'])
        : ( ! empty($_POST['billing_phone'])
            ? sanitize_text_field(wp_unslash($_POST['billing_phone']))
            : ''
        );
    $order->update_meta_data('_billing_phone', $billing_phone);

    // ------------ XỬ LÝ SHIPPING ------------
    $shipping_address_1 = '';

    // 1) Tỉnh/Thành phố (shipping_province_code)
    // - Ưu tiên $data (đơn ảo), tiếp đó _POST (checkout form),
    // - Nếu vẫn trống, copy từ billing_province_code ở trên (biến $province_code).
    $shipping_province_code = ! empty($data['shipping_province_code'])
        ? sanitize_text_field($data['shipping_province_code'])
        : ( ! empty($_POST['shipping_province_code'])
            ? sanitize_text_field(wp_unslash($_POST['shipping_province_code']))
            : ( ! empty($province_code) ? $province_code : '' )
        );
    if ( $shipping_province_code ) {
        $shipping_province_name = devvn_vietnam_shipping()->get_province_name_by_code($shipping_province_code);
        $order->update_meta_data('_shipping_city', $shipping_province_name);
        $shipping_address_1 .= $shipping_province_name;
    }

    // 2) Quận/Huyện (shipping_district_code)
    $shipping_district_code = ! empty($data['shipping_district_code'])
        ? sanitize_text_field($data['shipping_district_code'])
        : ( ! empty($_POST['shipping_district_code'])
            ? sanitize_text_field(wp_unslash($_POST['shipping_district_code']))
            : ( ! empty($district_code) ? $district_code : '' )
        );
    if ( $shipping_district_code ) {
        $shipping_district_name = devvn_vietnam_shipping()->get_district_name_by_code($shipping_district_code);
        $shipping_address_1 .= ' - ' . $shipping_district_name;
    }

    // 3) Xã/Phường (shipping_ward_code)
    $shipping_ward_code = ! empty($data['shipping_ward_code'])
        ? sanitize_text_field($data['shipping_ward_code'])
        : ( ! empty($_POST['shipping_ward_code'])
            ? sanitize_text_field(wp_unslash($_POST['shipping_ward_code']))
            : ( ! empty($ward_code) ? $ward_code : '' )
        );
    if ( $shipping_ward_code ) {
        $shipping_ward_name = devvn_vietnam_shipping()->get_ward_name_by_code($shipping_ward_code);
        $shipping_address_1 .= ' - ' . $shipping_ward_name;
    }

    // Cập nhật meta _shipping_address_1
    $order->update_meta_data('_shipping_address_1', $shipping_address_1);

    // 4) Địa chỉ cụ thể (shipping_address_2)
    $shipping_address_2 = ! empty($data['shipping_address_2'])
        ? sanitize_text_field($data['shipping_address_2'])
        : ( ! empty($_POST['shipping_address_2'])
            ? sanitize_text_field(wp_unslash($_POST['shipping_address_2']))
            : ( ! empty($billing_address_2) ? $billing_address_2 : '' )
        );
    $order->update_meta_data('_shipping_address_2', $shipping_address_2);

    // 5) SĐT giao hàng (shipping_phone)
    $shipping_phone = ! empty($data['shipping_phone'])
        ? sanitize_text_field($data['shipping_phone'])
        : ( ! empty($_POST['shipping_phone'])
            ? sanitize_text_field(wp_unslash($_POST['shipping_phone']))
            : ( ! empty($billing_phone) ? $billing_phone : '' )
        );
    $order->update_meta_data('_shipping_phone', $shipping_phone);

    // 6) shipping_first_name
    $shipping_first_name = ! empty($data['shipping_first_name'])
        ? sanitize_text_field($data['shipping_first_name'])
        : ( ! empty($_POST['shipping_first_name'])
            ? sanitize_text_field(wp_unslash($_POST['shipping_first_name']))
            : ( ! empty($data['billing_first_name'])
                ? sanitize_text_field($data['billing_first_name'])
                : ( ! empty($_POST['billing_first_name'])
                    ? sanitize_text_field(wp_unslash($_POST['billing_first_name']))
                    : ''
                )
            )
        );
    $order->update_meta_data('_shipping_first_name', $shipping_first_name);

    // 7) shipping_last_name
    $shipping_last_name = ! empty($data['shipping_last_name'])
        ? sanitize_text_field($data['shipping_last_name'])
        : ( ! empty($_POST['shipping_last_name'])
            ? sanitize_text_field(wp_unslash($_POST['shipping_last_name']))
            : ( ! empty($data['billing_last_name'])
                ? sanitize_text_field($data['billing_last_name'])
                : ( ! empty($_POST['billing_last_name'])
                    ? sanitize_text_field(wp_unslash($_POST['billing_last_name']))
                    : ''
                )
            )
        );
    $order->update_meta_data('_shipping_last_name', $shipping_last_name);

    // 8) shipping_company
    $shipping_company = ! empty($data['shipping_company'])
        ? sanitize_text_field($data['shipping_company'])
        : ( ! empty($_POST['shipping_company'])
            ? sanitize_text_field(wp_unslash($_POST['shipping_company']))
            : ( ! empty($data['billing_company'])
                ? sanitize_text_field($data['billing_company'])
                : ( ! empty($_POST['billing_company'])
                    ? sanitize_text_field(wp_unslash($_POST['billing_company']))
                    : ''
                )
            )
        );
    $order->update_meta_data('_shipping_company', $shipping_company);

    // 9) shipping_postcode
    $shipping_postcode = ! empty($data['shipping_postcode'])
        ? sanitize_text_field($data['shipping_postcode'])
        : ( ! empty($_POST['shipping_postcode'])
            ? sanitize_text_field(wp_unslash($_POST['shipping_postcode']))
            : ( ! empty($data['billing_postcode'])
                ? sanitize_text_field($data['billing_postcode'])
                : ( ! empty($_POST['billing_postcode'])
                    ? sanitize_text_field(wp_unslash($_POST['billing_postcode']))
                    : ''
                )
            )
        );
    $order->update_meta_data('_shipping_postcode', $shipping_postcode);

    // Cuối cùng, WooCommerce thường tự lưu order sau hook,
    // nếu bạn tạo đơn hàng thủ công, có thể gọi:
    // $order->save();
}

include_once ITHANDEVVN_PLUGIN_DIR . 'includes/ithan_wc_checkout_enqueue_script.php';
include_once ITHANDEVVN_PLUGIN_DIR . 'includes/ithan_wc_checkout_notices.php';