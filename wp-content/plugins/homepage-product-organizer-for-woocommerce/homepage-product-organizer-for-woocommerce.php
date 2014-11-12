<?php
/* 
Plugin Name: Homepage Product Organizer
Plugin URI: http://www.wooplugins.co 
Version: 1.0
Author: WooPlugins.co
Description: The Homepage Product Organizer for WooCommerce is a simple "drag and drop" solution that gives you full control of product placement and works wherever you paste the shortcode.
*/ 
function wcs_admin_actions() {
	add_menu_page('Page title', 'Product Organizer', 'manage_options', 'acs-view-all', 'acs_cat_prod_view');
	add_submenu_page( 'acs-view-all', 'Page title', 'Add New', 'manage_options', 'acs-add-new', 'acs_cat_prod_sort');
	add_submenu_page( 'acs-view-all', 'Page title', 'Settings', 'manage_options', 'acs-settings', 'acs_cat_prod_set');
}
function acs_cat_prod_set () {
	include "assets/wcs_settings.php";
}
function acs_cat_prod_sort () {	global $wpdb;	$db_table_name 		= $wpdb->prefix . 'wcs_selection_data';	$selections			= $wpdb->get_results("SELECT * FROM $db_table_name", ARRAY_A);		foreach ($selections as $selection) {		$limit = $selection['limit'];			}	if($limit == '1'){		echo "<h2>Homepage Product Organizer</h2>";		echo "<br/>";		echo "You can add maximum 1 selection in light version. Please upgrade to <a href='http://wooplugins.co'>premium</a> in order to create multiple selections. VERY IMPORTANT: The Lite version MUST be deactivated and uninstalled prior to uploading the Pro version.";	}else{
		acs_enqueue_script_and_styles();
		include "assets/wcs_prod_sort.php";	}
}

function wcs_save_selection_callback() {
	global $wpdb; // this is how you get access to the database

	$fdata 				= ( $_POST['fdata'] );
	$name 				= ( $_POST['name'] );
	$db_table_name 		= $wpdb->prefix . 'wcs_selection_data';
	
	$params = array();
	parse_str($fdata, $params);
	$params = serialize($params);	$limit = 1;
	$query = "INSERT INTO $db_table_name (`id`, `name`, `data`, `limit`) VALUES (NULL, '$name', '$params', '$limit');";
	if ($wpdb->query($query)) {
		echo "Selection have been saved";
	} else {
		echo "There was an error, please try again.";
	}
	die(); // this is required to return a proper result
}

function wcs_update_selection_callback () {
	global $wpdb; // this is how you get access to the database

	$fdata 				= ( $_POST['fdata'] );
	$name 				= ( $_POST['name'] );
	$id					= ( $_POST['id'] );
	$db_table_name 		= $wpdb->prefix . 'wcs_selection_data';
	
	$params = array();
	parse_str($fdata, $params);
	$params = serialize($params);
	$query = "UPDATE  $db_table_name SET `name`= '$name', data='$params' where id=$id;";
	if ($wpdb->query($query)) {
		echo "Selection have been Updated";
	} else {
		echo "There was an error, please try again.";
	}
	die(); // this is required to return a proper result
}
function wcs_delete_selection_callback () {
	global $wpdb; // this is how you get access to the database

	$id					= ( $_POST['id'] );
	$db_table_name 		= $wpdb->prefix . 'wcs_selection_data';
	
	$query = "DELETE FROM  $db_table_name where id=$id;";
	if ($wpdb->query($query)) {
		echo "1";
	} else {
		echo "0";
	}
	die(); // this is required to return a proper result
	
}
function acs_enqueue_script_and_styles () {
	
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-sortable');
	wp_enqueue_script('jquery-ui-draggable');
	wp_enqueue_script('jquery-ui-droppable');
	
	wp_enqueue_script(
		'jQueryDataTables',
		plugins_url() . '/homepage-product-organizer-for-woocommerce/assets/js/jquery.dataTables.js'
	);
	wp_enqueue_script(
		'jQueryshapeShift',
		plugins_url() . '/homepage-product-organizer-for-woocommerce/assets/js/jquery.shapeshift.min.js'
	);
	wp_enqueue_style( 'jQueryDatabTablesCss',
					plugins_url() . '/homepage-product-organizer-for-woocommerce/assets/style/jquery.dataTables.css'
	);
	wp_enqueue_style( 'acs_core_style',
					plugins_url() . '/homepage-product-organizer-for-woocommerce/assets/style/css.css'
	);
	
}
function acs_cat_prod_view() {
	acs_enqueue_script_and_styles();
	include "assets/wcs_prod_view.php";
}
function wcs_shortcode_func( $atts ) {
	extract( shortcode_atts( array(
		'selection' => 'selection'
	), $atts ) );
	
	
	global $wpdb;
	$db_table_name 		= $wpdb->prefix . 'wcs_selection_data';
	$selectionData		= $wpdb->get_results("SELECT * FROM $db_table_name WHERE id={$selection}", ARRAY_A);
	
	$selectionUnData	= unserialize ( $selectionData[0]['data'] );
	
	$productsIds 		= $selectionUnData['products'];
	?>
    <style>
	<?php echo get_option('wcs_style_options'); ?>
	</style>
    <?php
	foreach($productsIds as $key=>$value){
		$term = get_term_by('slug', $key, 'product_cat');
		$name = $term->name;
		?><div class="woocommerce"><h1 style="float:none; clear:both"><?php echo  ucfirst($name);?></h1><div style="clear:both"><br /></div><ul class = "products"><?php 
		$prod_ids = array();
		foreach($value as $val) {
			$prod_ids[] = $val;
		}
			$args = array( 'post_type' => 'product','post__in' => ($prod_ids),'orderby' => 'post__in'  );

			$loop = new WP_Query( $args );

			while ( $loop->have_posts() ) : $loop->the_post(); 

				global $product; 

				 woocommerce_get_template_part('content', 'product');


			endwhile; 

			

    	wp_reset_query(); 
		}
		?> </ul><?php
	
	?> </div><?php

}
function plugin_name_activation() {
	require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
	global $wpdb;
	$db_table_name = $wpdb->prefix . 'wcs_selection_data';
	if( $wpdb->get_var( "SHOW TABLES LIKE '$db_table_name'" ) != $db_table_name ) {
		if ( ! empty( $wpdb->charset ) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty( $wpdb->collate ) )
			$charset_collate .= " COLLATE $wpdb->collate";
 
		$sql = "CREATE TABLE " . $db_table_name . " (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`name` varchar(255) NOT NULL,
				`data` text NOT NULL,								`limit` int(11) NOT NULL,				
				PRIMARY KEY (`id`)
		) $charset_collate;";
		dbDelta( $sql );
	}
}
register_activation_hook(__FILE__, 'plugin_name_activation');
add_action('admin_menu', 'wcs_admin_actions'); 
add_action( 'wp_ajax_wcs_save_selection', 'wcs_save_selection_callback' );
add_action( 'wp_ajax_wcs_update_selection', 'wcs_update_selection_callback' );
add_action( 'wp_ajax_wcs_delete_selection', 'wcs_delete_selection_callback' );

add_shortcode( 'wcs', 'wcs_shortcode_func' );
?>