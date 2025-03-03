<?php 

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'woocommerce_locate_template', 'ithan_wc_checkout_custom_override_error_template', 10, 3 );
function ithan_wc_checkout_custom_override_error_template( $template, $template_name, $template_path ) {
    if ( 'notices/error.php' === $template_name ) {
        $plugin_template = plugin_dir_path( dirname(__FILE__) ) . 'templates/notices/error.php';
        if ( file_exists( $plugin_template ) ) {
            $template = $plugin_template;
        }
    }
    return $template;
}

add_filter( 'woocommerce_kses_notice_allowed_tags', 'ithan_wc_custom_add_svg_to_allowed_tags', 10, 1 );

function ithan_wc_custom_add_svg_to_allowed_tags( $allowed_tags ) {
    // Thêm các thẻ SVG và các thuộc tính của chúng vào danh sách thẻ được phép
    $allowed_tags['svg'] = array(
        'xmlns' => true,
        'width' => true,
        'height' => true,
        'viewBox' => true,
        'fill' => true,
    );

    $allowed_tags['path'] = array(
        'd' => true,
        'stroke' => true,
        'stroke-width' => true,
        'stroke-linecap' => true,
    );

    $allowed_tags['use'] = array(
        'href' => true,
    );

    return $allowed_tags;
}