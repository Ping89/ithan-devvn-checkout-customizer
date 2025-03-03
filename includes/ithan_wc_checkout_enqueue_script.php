<?php

function ithan_checkout_quick_buy_enqueue_js(){
    // Đảm bảo jQuery đã có
    wp_enqueue_script( 'jquery' );
    
    // Enqueue script custom
    wp_enqueue_script(
        'ithan-billing-address-checkout-js',
        ithan_wc_checkout_get_assets_file("billing_address_selection.js", "js", "", ""),
        array('jquery'), 
        '1.0', 
        true
    );

    // Truyền biến ajaxurl cho JS (nếu cần)
    wp_localize_script(
        'ithan-billing-address-checkout-js',
        'ithan_billing_address_ajax_obj',
        array(
            'nonce' => wp_create_nonce('ithan_load_address_nonce'), // Tạo nonce cho AJAX
            'ajaxurl' => admin_url('admin-ajax.php')
        )
    );
}

function ithan_wc_checkout_enqueue_script_for_popup(){
    // Đảm bảo jQuery đã có
    wp_enqueue_script( 'jquery' );
    
    // Enqueue script custom
    wp_enqueue_script(
        'ithan-billing-address-checkout-js',
        ithan_wc_checkout_get_assets_file("billing_address_selection_for_popup.js", "js", "", ""),
        array('jquery'), 
        '1.0', 
        true
    );

    // Truyền biến ajaxurl cho JS (nếu cần)
    wp_localize_script(
        'ithan-billing-address-checkout-js',
        'ithan_billing_address_ajax_obj',
        array(
            'nonce' => wp_create_nonce('ithan_load_address_nonce'), // Tạo nonce cho AJAX
            'ajaxurl' => admin_url('admin-ajax.php')
        )
    );
}

function ithan_wc_checkout_enqueue_js(){
    // Đảm bảo jQuery đã có
    wp_enqueue_script( 'jquery' );
    
    // Enqueue script custom
    wp_enqueue_script(
        'ithan-billing-address-checkout-js',
        ithan_wc_checkout_get_assets_file("billing_address_selection_for_wc_checkout_page.js", "js", "", ""),
        array('jquery'), 
        '1.0', 
        true
    );

    // Truyền biến ajaxurl cho JS (nếu cần)
    wp_localize_script(
        'ithan-billing-address-checkout-js',
        'ithan_billing_address_ajax_obj',
        array(
            'nonce' => wp_create_nonce('ithan_load_address_nonce'), // Tạo nonce cho AJAX
            'ajaxurl' => admin_url('admin-ajax.php')
        )
    );
}

add_action( 'wp_enqueue_scripts', 'ithan_checkout_quick_buy_enqueue_js_action' );
function ithan_checkout_quick_buy_enqueue_js_action(){
    if ( is_checkout() ) {
        // ithan_checkout_quick_buy_enqueue_js(); // cho ithan quick buy
        ithan_wc_checkout_enqueue_js();
    }
}

add_action('wp_enqueue_scripts', 'ithan_woocommerce_enqueue_css');
function ithan_woocommerce_enqueue_css() {
    if (is_checkout()){
        // Enqueue file CSS
        wp_enqueue_style(
            'ithan-woocommerce-enqueue-css',
            ithan_wc_checkout_get_assets_file("ithan-devvn-woocommerce-checkout.css", "css", "", ""),
            array(), // Các dependency nếu có
            '1.0'
        );

        wp_enqueue_style(
            'ithan-woocommerce-notices-enqueue-css',
            ithan_wc_checkout_get_assets_file("notices.css", "css", "", ""),
            array(), // Các dependency nếu có
            '1.0'
        );
    }
}

