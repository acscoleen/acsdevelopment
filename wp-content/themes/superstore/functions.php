<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/*-----------------------------------------------------------------------------------*/
/* Start WooThemes Functions - Please refrain from editing this section */
/*-----------------------------------------------------------------------------------*/

// WooFramework init
require_once ( get_template_directory() . '/functions/admin-init.php' );

/*-----------------------------------------------------------------------------------*/
/* Load the theme-specific files, with support for overriding via a child theme.
/*-----------------------------------------------------------------------------------*/

$includes = array(
				'includes/theme-options.php', 			// Options panel settings and custom settings
				'includes/theme-functions.php', 		// Custom theme functions
				'includes/theme-actions.php', 			// Theme actions & user defined hooks
				'includes/theme-comments.php', 			// Custom comments/pingback loop
				'includes/theme-js.php', 				// Load JavaScript via wp_enqueue_script
				'includes/sidebar-init.php', 			// Initialize widgetized areas
				'includes/theme-widgets.php',			// Theme widgets
				'includes/theme-plugin-integrations.php'// Plugin integrations
				);

// Allow child themes/plugins to add widgets to be loaded.
$includes = apply_filters( 'woo_includes', $includes );

foreach ( $includes as $i ) {
	locate_template( $i, true );
}

/*-----------------------------------------------------------------------------------*/
/* You can add custom functions below */
/*-----------------------------------------------------------------------------------*/

function ewp_remove_script_version( $src ){
	return remove_query_arg( 'ver', $src );
}
add_filter( 'script_loader_src', 'ewp_remove_script_version', 15, 1 );
add_filter( 'style_loader_src', 'ewp_remove_script_version', 15, 1 );


/**
 * Alter wordpress heartbeat 
 */

function wptuts_heartbeat_settings( $settings ) {
    $settings['interval'] = 60; //Anything between 15-60
    return $settings;
}
add_filter( 'heartbeat_settings', 'wptuts_heartbeat_settings' );





add_filter( 'jpeg_quality', create_function( '', 'return 100;' ) );


add_action( 'wp_enqueue_scripts', 'child_manage_woocommerce_styles', 99 );
/**
 * Remove WooCommerce Generator tag, styles, and scripts from the homepage.
 * Tested and works with WooCommerce 2.0+
 *
 * @author Greg Rickaby
 * @since 2.0.0
 */
function child_manage_woocommerce_styles() {
	remove_action( 'wp_head', array( $GLOBALS['woocommerce'], 'generator' ) );
	if ( is_front_page() || is_home() ) {
		wp_dequeue_style( 'woocommerce_frontend_styles' );
		wp_dequeue_style( 'woocommerce_fancybox_styles' );
		wp_dequeue_style( 'woocommerce_chosen_styles' );
		wp_dequeue_style( 'woocommerce_prettyPhoto_css' );
		wp_dequeue_script( 'wc_price_slider' );
		wp_dequeue_script( 'wc-single-product' );
		wp_dequeue_script( 'wc-add-to-cart' );
		wp_dequeue_script( 'wc-cart-fragments' );
		wp_dequeue_script( 'wc-checkout' );
		wp_dequeue_script( 'wc-add-to-cart-variation' );
		wp_dequeue_script( 'wc-single-product' );
		wp_dequeue_script( 'wc-cart' );
		wp_dequeue_script( 'wc-chosen' );
		wp_dequeue_script( 'woocommerce' );
		wp_dequeue_script( 'prettyPhoto' );
		wp_dequeue_script( 'prettyPhoto-init' );
		wp_dequeue_script( 'jquery-blockui' );
		wp_dequeue_script( 'jquery-placeholder' );
		wp_dequeue_script( 'fancybox' );
		wp_dequeue_script( 'jqueryui' );
}}




add_filter( 'wc_product_enable_dimensions_display', '__return_false' );


//Displays brief description on each product category pages product listings
add_action('woocommerce_after_shop_loop_item_title','woocommerce_template_single_excerpt', 5);

//fix for cookie error while login.
setcookie(TEST_COOKIE, 'WP Cookie check', 0, COOKIEPATH, COOKIE_DOMAIN);
if ( SITECOOKIEPATH != COOKIEPATH )
	setcookie(TEST_COOKIE, 'WP Cookie check', 0, SITECOOKIEPATH, COOKIE_DOMAIN);


/*-----------------------------------------------------------------------------------*/
/* Don't add any code below here or the sky will fall down */
/*-----------------------------------------------------------------------------------*/
?>