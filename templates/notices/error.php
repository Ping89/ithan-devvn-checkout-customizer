<?php
/**
 * Show error messages
 *
 * This template can be overridden by copying it to yourtheme/ithan-devvn-checkout-customizer/notices/error.php.
 *
 */

 if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

 if ( ! $notices ) {
 	return;
 }

?>

<div class="toast__panel-wrapper">
    <div class="toast__panel-container" data-has-run-message="false">
        <?php foreach ( $notices as $notice ) : ?>
            <div class="notification danger" 
                <?php
                /**
                 * wc_get_notice_data_attr() already escapes all data attributes internally.
                 * We can safely echo here.
                 *
                 * phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                 */
                echo wc_get_notice_data_attr( $notice );
                ?>>

                <!-- Biểu tượng lỗi -->
                <i>
                    <svg class="icon"><use href="#icon--error"></use></svg>
                </i>

                <span><?php echo wc_kses_notice( $notice['notice'] ); ?></span>
                <i class="icon-close">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
                        <path d="M11.742 4.258a1 1 0 1 0-1.484-1.328L8 6.716 5.742 3.93a1 1 0 1 0-1.484 1.328L6.716 8l-3.458 4.742a1 1 0 0 0 1.484 1.328L8 9.284l2.258 2.886a1 1 0 1 0 1.484-1.328L9.284 8l3.458-4.742z"/>
                    </svg>
                </i>

            </div>
        <?php endforeach; ?>
    </div>
</div>






