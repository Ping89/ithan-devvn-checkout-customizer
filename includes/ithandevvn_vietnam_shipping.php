<?php 
namespace ithandech\devvncheckout;

if ( ! class_exists( 'Vietnam_Shipping' ) ) {

    class Vietnam_Shipping  {

        /**
         * Chứa thể hiện (instance) duy nhất của class (Singleton).
         */
        private static $instance = null;

        /**
         * Thuộc tính lưu dữ liệu quận/huyện
         */
        private $province_data = array();
        
        /**
         * Thuộc tính lưu dữ liệu quận/huyện
         */
        private $district_data = array();

        /**
         * Thuộc tính lưu dữ liệu xã/phường
         */
        private $ward_data = array();

        /**
         * Hàm dựng (constructor).
         * Ở đây ta gán các biến global vào property của class.
         */
        public function __construct() {
            // Lấy biến global
            global $tinh_thanhpho, $quan_huyen, $xa_phuong_thitran;

            // Gán vào property
            $this->province_data = $tinh_thanhpho;

            $this->district_data = $quan_huyen;
            $this->ward_data = $xa_phuong_thitran;
        }

        /**
         * Trả về 1 đối tượng duy nhất của class
         */
        public static function instance() {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Lấy danh sách Quận/Huyện theo mã Tỉnh/Thành (province_code).
         * Ví dụ province_code = 'HANOI' hoặc 'HOCHIMINH'...
         */
        public function get_list_district_select( $province_code ) {
            $result = array();

            // Chỉ thực hiện nếu $province_code không rỗng
            if ( ! empty( $province_code ) ) {
                // Duyệt mảng $this->district_data ( = $quan_huyen )
                foreach ( $this->district_data as $qh ) {
                    // Mỗi $qh là một item, ví dụ:
                    // ["maqh" => "001", "name" => "Quận Ba Đình", "matp" => "HANOI"]
                    if ( isset($qh['matp']) && $qh['matp'] === $province_code ) {
                        // Gán vào kết quả, key = maqh, value = tên Quận/Huyện
                        $result[ $qh['maqh'] ] = $qh['name'];
                    }
                }
            }

            // Thêm option mặc định ở đầu
            $result = array( '' => __( 'Chọn quận/huyện', ITHAN_CHECKOUT_SLUG ) ) + $result;

            return $result;
        }

        /**
         * Lấy danh sách Xã/Phường theo mã Quận/Huyện (district_code).
         */
        public function get_list_village_select( $district_code ) {
            $result = array();

            // Chỉ thực hiện nếu district_code không rỗng
            if ( ! empty( $district_code ) ) {
                // Duyệt mảng $this->ward_data ( = $xa_phuong_thitran )
                foreach ( $this->ward_data as $ward ) {
                    // Mỗi $ward là:
                    // ["xaid"=>"00001","name"=>"Phường Phúc Xá","maqh"=>"001"]
                    if ( isset( $ward['maqh'] ) && $ward['maqh'] === $district_code ) {
                        // Gán vào kết quả, key = xaid, value = tên Xã/Phường
                        $result[ $ward['xaid'] ] = $ward['name'];
                    }
                }
            }

            // Thêm option mặc định ở đầu, nếu muốn
            $result = array( '' => __( 'Chọn xã/phường', ITHAN_CHECKOUT_SLUG ) ) + $result;

            return $result;
        }

        /**
         * Hàm chuyển mã tỉnh/thành -> tên hiển thị
         */
        public function get_province_name_by_code( $province_code ) {
            // Kiểm tra xem mã code có tồn tại trong mảng $province_data hay không
            if ( isset( $this->province_data[ $province_code ] ) ) {
                return $this->province_data[ $province_code ];
            }

            // Nếu không tìm thấy, trả về rỗng hoặc chính mã code (tuỳ bạn)
            return '';
        }

        public function get_district_name_by_code( $district_code ) {
            // Duyệt qua mảng $this->district_data
            foreach ( $this->district_data as $dist ) {
                // Mỗi $dist có cấu trúc: ["maqh"=>"001","name"=>"Quận Ba Đình","matp"=>"HANOI"]
                if ( isset($dist['maqh']) && $dist['maqh'] === $district_code ) {
                    // Tìm thấy mã quận/huyện trùng khớp
                    return $dist['name']; // Trả về tên
                }
            }
        
            // Nếu không tìm thấy, trả về chuỗi rỗng (hoặc chính $district_code)
            return '';
        }
        
        public function get_ward_name_by_code( $ward_code ) {
            // Duyệt tất cả các phần tử trong mảng ward_data
            foreach ( $this->ward_data as $ward ) {
                // Mỗi $ward có cấu trúc:
                // [
                //   "xaid" => "00001",
                //   "name" => "Phường Phúc Xá",
                //   "maqh" => "001"
                // ]
                if ( isset($ward['xaid']) && $ward['xaid'] === $ward_code ) {
                    // Nếu tìm thấy mã trùng khớp, trả về tên
                    return $ward['name']; 
                }
            }
        
            // Nếu không thấy, có thể trả về rỗng hoặc chính mã ward
            return '';
        }
        

    }
}
