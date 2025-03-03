=== ithan devvn checkout customizer ===
Contributors: laptrinhvienso0
Donate link: 
Tags: checkout, fields, woocommerce, custom, payment
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 2.1
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Short Description: Cho phép chỉnh sửa các trường địa chỉ phù hợp với Việt Nam. Bao gồm tỉnh, huyện, xã...

== Description ==

This is a custom checkout plugin for WooCommerce. Custom billing address fields for Vietname address.

Thay đổi địa chỉ cho phù hợp với địa chỉ ở Việt Nam.

== Frequently Asked Questions ==

= Where can I find the un-minified source code? =

All of our original, human-readable JavaScript and CSS is included under `assets/src/`. 
We use a build process to generate the files found in `assets/build/`.

= How do I rebuild these files myself? =

1. Make sure you have Node.js and npm installed.
2. From the plugin directory, run `npm install` to fetch all dependencies.
3. Run `npm run minify-js` and `npm run minify-css` to compile/minify the source code into `assets/build/`.
4. The final production-ready files will appear in the `assets/build/` directory.

We also maintain a GitHub repository (link below), where you can view the complete plugin source, including branches, commit history, and build scripts.

== Development & Source Code ==
You can view, fork, or contribute to our plugin’s source code on GitHub:
[https://github.com/Ping89/ithan-devvn-checkout-customizer]

= Làm sao để cài đặt =

Upload the plugin files to the `/wp-content/plugins/ithan-devvn-checkout-customizer` directory, or install the plugin through the WordPress plugins screen directly.

= Bắt đầu sử dụng như thế nào? =

Cài đặt woo-checkout-field-editor và chỉnh sửa các trường billing_fiels theo ý muốn.
Nhưng để sử dụng plugin này cần phải thêm 3 trường sau đâu:
billing_province_code - select - Tỉnh/Thành - Tỉnh/Thành Phố
billing_district_code - select - Quận/Huyện - Quận Huyện
billing_ward_code - select - Xã/Phường - Xã Phường

== Screenshots ==

1. Start screen: /assets/overview.png
2. Label Tooltips: /assets/labelview.png
3. Select VN Address: /assets/select_vn_address_view.png
4. Error Message view: /assets/error_messages.png

== Changelog ==

= 1.2 =
* Change a new skin.
* Make another css and javascript to view error
* View label tooltip.

= 1.0 =
* Create plugin.

== Upgrade Notice ==

= 1.2 =
Nothing to do

== A brief Markdown ==

Markdown is what the parser uses to process much of the readme file.

Ordered list:

1. Chỉnh sửa địa chỉ thanh toán và giao hàng cho phù hợp với địa chỉ ở Việt Nam
2. Cho phép chọn lựa tỉnh thành, quận huyện, xã phường trong các combobox phù hợp
3. Hiển thị thông báo lỗi một cách linh động hơn

Unordered list:

* something
* something else
* third thing

Links require brackets and parenthesis:

Here's a link to [WordPress](https://wordpress.org/ "Your favorite software") and one to [Markdown's Syntax Documentation][markdown syntax]. Link titles are optional, naturally.

Blockquotes are email style:

> Asterisks for *emphasis*. Double it up  for **strong**.

And Backticks for code:

`<?php code(); ?>`
