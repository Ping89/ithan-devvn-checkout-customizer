<?php 


function convert_to_minified($filename) {
    // Lấy thông tin path (tên file, extension, v.v.)
    $info = pathinfo($filename);
    
    // Lấy phần tên file (không gồm phần mở rộng)
    $basename = $info['filename'];
    
    // Nếu có phần mở rộng, ta lấy để ghép lại
    $extension = isset($info['extension']) ? $info['extension'] : '';
    
    // Trả về tên file dạng style.min.css hoặc script.min.js
    return $basename . '.min.' . $extension;
}

/**
 * Tìm và tải file CSS/JS từ child theme hoặc plugin, tùy vào chế độ phát triển hoặc build
 *
 * @param string $file_name Tên file CSS/JS (VD: 'style.css', 'script.js')
 * @param string $file_type Loại file cần tìm ('css' hoặc 'js')
 * @param string $mode Chế độ hiện tại ('src', 'build' hoặc '')
 * @param string $plugin_path Đường dẫn thư mục chứa file trong plugin
 * @return string|bool Đường dẫn đến file nếu tìm thấy, hoặc false nếu không tìm thấy
 */
function ithan_wc_checkout_get_assets_file($file_name, $file_type = 'css', $mode = '', $plugin_path = ''): bool|string
{
    // Nếu không có plugin path, sử dụng thư mục mặc định của plugin
    if (!$plugin_path) {
        $plugin_path = plugin_dir_path(dirname(__FILE__)) . 'assets/'; // Thư mục assets trong plugin
    }

    // Tạo 2 đường dẫn file cho child theme: một cho build và một cho dev
    $build_file_path = get_stylesheet_directory() . '/ithan-devvn-checkout-customizer/assets/build/' . $file_type . '/' . convert_to_minified($file_name);
    $dev_file_path = get_stylesheet_directory() . '/ithan-devvn-checkout-customizer/assets/src/' . $file_type . '/' . $file_name;

    // Kiểm tra mode, nếu rỗng thì ưu tiên build trước, dev sau
    if ($mode === '') {
        // Ưu tiên build trước
        if (file_exists($build_file_path)) {
            $file_url = get_stylesheet_directory_uri() . '/ithan-devvn-checkout-customizer/assets/build/' . $file_type . '/' . convert_to_minified($file_name);
        } else {
            // Nếu không có trong build, kiểm tra dev
            if (file_exists($dev_file_path)) {
                $file_url = get_stylesheet_directory_uri() . '/ithan-devvn-checkout-customizer/assets/src/' . $file_type . '/' . $file_name;
            } else {
                // Nếu không tìm thấy trong child theme, tìm trong plugin
                $plugin_file_path = $plugin_path . 'build/' . $file_type . '/' . convert_to_minified($file_name);
                if (file_exists($plugin_file_path)) {
                    $file_url = plugins_url('assets/build/' . $file_type . '/' . convert_to_minified($file_name), dirname(__FILE__));
                } else {
                    $plugin_file_path = $plugin_path . 'src/' . $file_type . '/' . $file_name;
                    if (file_exists($plugin_file_path)) {
                        $file_url = plugins_url('assets/src/' . $file_type . '/' . $file_name, dirname(__FILE__));
                    } else {
                        return false; // Nếu không tìm thấy ở đâu cả, trả về false
                    }
                }
            }
        }
    } else {
        // Nếu có mode ('src' hoặc 'build'), tìm theo mode
        if ($mode === 'build') {
            $mode_file_path = get_stylesheet_directory() . '/ithan-devvn-checkout-customizer/assets/' . $mode . '/' . $file_type . '/' . convert_to_minified($file_name);
        }
        else{
            $mode_file_path = get_stylesheet_directory() . '/ithan-devvn-checkout-customizer/assets/' . $mode . '/' . $file_type . '/' . $file_name;
        }
        if (file_exists($mode_file_path)) {
            if ($mode === 'build'){
                $file_url = get_stylesheet_directory_uri() . '/ithan-devvn-checkout-customizer/assets/' . $mode . '/' . $file_type . '/' . convert_to_minified($file_name);
            }
            else{
                $file_url = get_stylesheet_directory_uri() . '/ithan-devvn-checkout-customizer/assets/' . $mode . '/' . $file_type . '/' . $file_name;
            }
        } else {
            // Nếu không tìm thấy trong child theme, tìm trong plugin
            if ($mode === 'build'){
                $plugin_mode_file_path = $plugin_path . $mode . '/' . $file_type . '/' . convert_to_minified($file_name);
            }
            else{
                $plugin_mode_file_path = $plugin_path . $mode . '/' . $file_type . '/' . $file_name;
            }
            if (file_exists($plugin_mode_file_path)) {
                if ($mode === 'build'){
                    $file_url = plugins_url('assets/' . $mode . '/' . $file_type . '/' . convert_to_minified($file_name), dirname(__FILE__));
                }
                else{
                    $file_url = plugins_url('assets/' . $mode . '/' . $file_type . '/' . $file_name, dirname(__FILE__));
                }
            } else {
                return false; // Nếu không tìm thấy ở đâu cả, trả về false
            }
        }
    }

    // Nếu file là CSS
    if ($file_type === 'css') {
        return $file_url;
    }

    // Nếu file là JS
    if ($file_type === 'js') {
        return $file_url;
    }

    return false;
}


