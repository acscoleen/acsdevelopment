<?php

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_init', 'wc_tradegecko_register_settings' );

/**
 * Register the settings
 *
 * Settings options are as follows: <br />
 *
 * text, textarea, select, multiselect, password, radio, hook, upload, rich_editor <br />
 *
 * Options for the settings types are: <br />
 *
 * id		- option unique ID. It will be used as id attribute and as name
 * class	- additional class for the option
 * label	- the label
 * size		- size type - regular, small, large
 * css		- any css styles to add to the option
 * desc		- option description
 * desc_style	- tip, text - How the description should be visualized
 * options	- for Select and multicheck. The options to be vizualized.
 * before_option - Output to visualize before the option
 * after_option	- Output to visualize after the option
 *
 * @return void
 */
function wc_tradegecko_register_settings() {

	$wc_tradegecko_settings = array(
		'general' => apply_filters( 'wc_tradegecko_settings_general',
			array(
				array(
					'id'		=> 'enable',
					'name'		=> '',
					'label'		=> __('Activate TradeGecko Integration', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __('Enable this to take advantage of the TradeGecko integration features.', WC_TradeGecko_Init::$text_domain),
					'desc_style'	=> 'text',
					'std'		=> '1',
					'type'		=> 'checkbox',
				),

				array(
					'id'		=> 'enable_debug',
					'name'		=> '',
					'label'		=> __('Enable Debug Logging', WC_TradeGecko_Init::$text_domain),
					'desc'		=> sprintf( __('This option will provide you with a step by step log of all manipulations done during a synchronization. Please enable, if needed ONLY.<br />The debug log will be inside <code>woocommerce/logs/tradegecko-%s.txt</code>.', WC_TradeGecko_Init::$text_domain), sanitize_file_name( wp_hash( 'tradegecko' ) ) ),
					'desc_style'	=> 'text',
					'std'		=> '0',
					'type'		=> 'checkbox',
				),

				array(
					'id'		=> 'inventory_section',
					'name'		=> '<strong>' . __('Product Inventory Settings', WC_TradeGecko_Init::$text_domain) . '</strong>',
					'desc'		=> __('This section will help you setup the inventory and product synchronization settings.', WC_TradeGecko_Init::$text_domain),
					'type'		=> 'header',
				),
				array(
					'id'		=> 'inventory_sync',
					'name'		=> '',
					'label'		=> __('Stock Synchronization', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __('Enable, to sync your WooCommerce products inventory with TradeGecko. The WooCommerce inventory for each product will be synchronized with the TradeGecko inventory.', WC_TradeGecko_Init::$text_domain),
					'desc_style'	=> 'text',
					'std'		=> '1',
					'type'		=> 'checkbox',
				),
				array(
					'id'		=> 'product_price_sync',
					'name'		=> '',
					'label'		=> __('Price Synchronization', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __("Enable, to sync your WooCommerce products prices with TradeGecko. The WooCommerce products prices will be synchronized with the products prices in TradeGecko.", WC_TradeGecko_Init::$text_domain),
					'desc_style'	=> 'text',
					'std'		=> '1',
					'type'		=> 'checkbox',
				),
				array(
					'id'		=> 'product_title_sync',
					'name'		=> '',
					'label'		=> __('Title Synchronization', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __("Enable, to sync your WooCommerce products title with TradeGecko products title. Sync the product titles only, if they are going to be changing often. Otherwise, good practice would be to sync them once and then disable it.", WC_TradeGecko_Init::$text_domain),
					'desc_style'	=> 'text',
					'std'		=> '1',
					'type'		=> 'checkbox',
				),

				array(
					'id'		=> 'product_allow_backorders',
					'name'		=> __('Set Allow Backorders', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __('Choose the way backorders are allowed for your products.', WC_TradeGecko_Init::$text_domain),
					'class'		=> WC_TradeGecko_Init::$prefix .'product_allow_backorders',
					'css'		=> 'width: 300px;',
					'type'		=> 'select',
					'options'	=> array( 'allow' => 'Allow', 'notify' => 'Allow, but notify the customer' ),
				),

				array(
					'id'		=> 'available_currency_id',
					'name'		=> __('TradeGecko Currency', WC_TradeGecko_Init::$text_domain),
					'desc'		=> sprintf( __( 'Choose the TradeGecko currency you will use for your store. Your store currency is: %s', WC_TradeGecko_Init::$text_domain ), get_woocommerce_currency() ),
					'class'		=> WC_TradeGecko_Init::$prefix .'available_currency_id',
					'css'		=> 'width: 300px;',
					'type'		=> 'select',
					'options'	=> WC_TradeGecko_Init::get_tradegecko_currencies(),
				),

				array(
					'id'		=> 'regular_price_id',
					'name'		=> __('Map Product Regular Price', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __('Choose the TradeGecko Price List you want to use, to update the products Regular Price.', WC_TradeGecko_Init::$text_domain),
					'class'		=> WC_TradeGecko_Init::$prefix .'regular_price_id',
					'css'		=> 'width: 300px;',
					'type'		=> 'select',
					'options'	=> WC_TradeGecko_Init::get_tradegecko_price_lists(),
				),

				array(
					'id'		=> 'allow_sale_price_mapping',
					'name'		=> '',
					'label'		=> __('Allow Sale Price Mapping', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __("Enable to allow TradeGecko to modify your products Sale Price. Sale prices will be controlled by the TradeGecko Price List you choose for <strong>'Map Product Sale Price'<strong>", WC_TradeGecko_Init::$text_domain),
					'desc_style'	=> 'text',
					'type'		=> 'checkbox',
				),

				array(
					'id'		=> 'sale_price_id',
					'name'		=> __('Map Product Sale Price', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __("Choose the TradeGecko Price List you want to use, to update the products Sale Price.<br/>If the price in the Price List is 0, then the Sale Price will also be removed leaving only the Regular Price.", WC_TradeGecko_Init::$text_domain),
					'class'		=> WC_TradeGecko_Init::$prefix .'sale_price_id',
					'css'		=> 'width: 300px;',
					'type'		=> 'select',
					'options'	=> WC_TradeGecko_Init::get_tradegecko_price_lists(),
				),

				array(
					'id'		=> 'stock_location_id',
					'name'		=> __('Stock Location', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __("Choose the Stock Location your store stock will be synced to.", WC_TradeGecko_Init::$text_domain),
					'class'		=> WC_TradeGecko_Init::$prefix .'stock_location_id',
					'css'		=> 'width: 300px;',
					'type'		=> 'select',
					'options'	=> WC_TradeGecko_Init::get_tradegecko_stock_locations(),
				),

				array(
					'id'		=> 'orders_section',
					'name'		=> '<strong>' . __('Orders Settings', WC_TradeGecko_Init::$text_domain) . '</strong>',
					'desc'		=> __('This section will help you setup the orders synchronization settings.', WC_TradeGecko_Init::$text_domain),
					'type'		=> 'header',
				),

				array(
					'id'		=> 'orders_sync',
					'name'		=> '',
					'label'		=> __('Orders Synchronization', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __('Enable, to sync your WooCommerce orders with TradeGecko. The WooCommerce orders will be created send to TradeGecko and orders status will be updated as it is updated in TradeGecko. Your Customers info will be synced together with the orders.', WC_TradeGecko_Init::$text_domain),
					'desc_style'	=> 'text',
					'std'		=> '1',
					'type'		=> 'checkbox',
				),

				array(
					'id'		=> 'order_number_prefix',
					'name'		=> __('Order Number Prefix', WC_TradeGecko_Init::$text_domain),
					'label'		=> '',
					'desc'		=> __('Enter an prefix word that will identify the store orders from your other channel orders.', WC_TradeGecko_Init::$text_domain),
					'desc_style'	=> 'text',
					'std'		=> 'WooCommerce-',
					'type'		=> 'text',
				),

				array(
					'id'		=> 'order_fulfillment_sync',
					'name'		=> __('Sync Order Fulfillmets', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __('Do you want to pull the shipping information from TG and display it to the customers? The fulfillment information will be displayed at the View Order page and Order Completed emails. You will also be able to edit it in WooCommerce from an order panel meta box.', WC_TradeGecko_Init::$text_domain),
					'class'		=> WC_TradeGecko_Init::$prefix .'order_fulfillment_sync',
					'css'		=> 'width: 300px;',
					'type'		=> 'select',
					'options'	=> array( 'full' => 'Sync, but only when full order is shipped', 'partial' => 'Sync, even partial shipments', 'no' => 'Do not sync' ),
				),
			)
		),
		'api' => apply_filters( 'wc_tradegecko_settings_api',
			array(
				array(
					'id'		=> 'api_section',
					'name'		=> '<strong>' . __('TradeGecko API Settings', WC_TradeGecko_Init::$text_domain) . '</strong>',
					'desc'		=> __('This section will help you setup the TradeGecko API settings.', WC_TradeGecko_Init::$text_domain),
					'type'		=> 'header',
				),
				array(
					'id'		=> 'get_api_credentials',
					'name'		=> '',
					'desc'		=> '',
					'type'		=> 'hook',
				),
				array(
					'id'		=> 'client_id',
					'name'		=> __('API Application Id', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __('Enter here the your API Application Id', WC_TradeGecko_Init::$text_domain),
					'class'		=> WC_TradeGecko_Init::$prefix .'client_id',
					'type'		=> 'text',
				),
				array(
					'id'		=> 'client_secret',
					'name'		=> __('API Secret', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __('Enter here the your API Secret. You will obtain that after you register your API Application.', WC_TradeGecko_Init::$text_domain),
					'class'		=> WC_TradeGecko_Init::$prefix .'client_secret',
					'type'		=> 'password',
				),
				array(
					'id'		=> 'redirect_uri',
					'name'		=> __('Redirect URI', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __('Enter here your API Redirect URI. This is the redirect uri you entered when you registered your API Application.<br/> The Redirect URI should be: '.admin_url('/admin.php'), WC_TradeGecko_Init::$text_domain),
					'class'		=> WC_TradeGecko_Init::$prefix .'redirect_uri',
					'type'		=> 'text',
				),
				array(
					'id'		=> 'auth_code',
					'name'		=> __('Authorization Code', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __('Here you will see the Authorization Code given to you when you Authorize the TradeGecko Application.', WC_TradeGecko_Init::$text_domain) . '<br/>' . __( '<strong>IMPORTANT:</strong> If you change any of the above credentials, you need to obtain a new Authorization Code as well.', WC_TradeGecko_Init::$text_domain ),
					'class'		=> WC_TradeGecko_Init::$prefix .'auth_code',
					'type'		=> 'text',
				),
				array(
					'id'		=> 'get_authorization_code',
					'name'		=> '',
					'desc'		=> __('Pressing the button will lead you to a TradeGecko page, where you will be asked to grant access and give Authorization to the application.', WC_TradeGecko_Init::$text_domain),
					'type'		=> 'hook',
				),
			)
		),

		'sync' => apply_filters( 'wc_tradegecko_settings_sync',
			array(
				array(
					'id'		=> 'auto_sync_section',
					'name'		=> '<strong>' . __('Automatic Orders Sync', WC_TradeGecko_Init::$text_domain) . '</strong>',
					'desc'		=> __('This section will help you schedule automatic synchronizations.', WC_TradeGecko_Init::$text_domain),
					'type'		=> 'header',
				),
				array(
					'id'		=> 'automatic_sync',
					'name'		=> __('Order Export', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __('Enable, to be able to setup automatic order export sync schedule.<br />Next scheduled order export sync: '. WC_TradeGecko_Init::get_formatted_datetime( wp_next_scheduled( 'wc_tradegecko_synchronization' ) ), WC_TradeGecko_Init::$text_domain),
					'class'		=> WC_TradeGecko_Init::$prefix .'automatic_sync',
					'label'		=> __('Turn on Automatic Orders Export Synchronization', WC_TradeGecko_Init::$text_domain),
					'type'		=> 'checkbox',
				),
				array(
					'id'		=> 'sync_time_interval',
					'name'		=> __('Sync Time Interval', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __('Select the time interval you want the automatic order synchronization to be in.<br/><strong>IMPORTANT:</strong> Please make sure the three sync schedules are set at least <strong>10 minutes</strong> apart from each other.', WC_TradeGecko_Init::$text_domain),
					'class'		=> WC_TradeGecko_Init::$prefix .'sync_time_interval',
					'css'		=> 'width: 100px;',
					'type'		=> 'select',
					'options'	=> wc_tradegecko_get_intervals(),
				),
				array(
					'id'		=> 'sync_time_period',
					'name'		=> __('Sync Time Period', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __('Select the period you want the above interval to be in. For example: if you selected "5" as Interval and "Days" as the Period, then your automatic sync will be every 5 Days', WC_TradeGecko_Init::$text_domain),
					'class'		=> WC_TradeGecko_Init::$prefix .'sync_time_period',
					'css'		=> 'width: 100px;',
					'type'		=> 'select',
					'options'	=> array( 'MINUTE_IN_SECONDS' => 'Minutes', 'HOUR_IN_SECONDS' => 'Hours', 'DAY_IN_SECONDS' => 'Days' ),
				),

				array(
					'id'		=> 'automatic_order_update_sync',
					'name'		=> __('Order Update', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __('Enable, to be able to setup automatic order update sync schedule.<br />Next scheduled order update sync: '. WC_TradeGecko_Init::get_formatted_datetime( wp_next_scheduled( 'wc_tradegecko_order_update_synchronization' ) ), WC_TradeGecko_Init::$text_domain),
					'class'		=> WC_TradeGecko_Init::$prefix .'automatic_sync',
					'label'		=> __('Turn on Automatic Orders Update Synchronization', WC_TradeGecko_Init::$text_domain),
					'type'		=> 'checkbox',
				),
				array(
					'id'		=> 'order_update_sync_time_interval',
					'name'		=> __('Sync Time Interval', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __('Select the time interval you want the automatic order synchronization to be in.<br/><strong>IMPORTANT:</strong> Please make sure the three sync schedules are set at least <strong>10 minutes</strong> apart from each other.', WC_TradeGecko_Init::$text_domain),
					'class'		=> WC_TradeGecko_Init::$prefix .'order_update_sync_time_interval',
					'css'		=> 'width: 100px;',
					'type'		=> 'select',
					'options'	=> wc_tradegecko_get_intervals(),
				),
				array(
					'id'		=> 'order_update_sync_time_period',
					'name'		=> __('Sync Time Period', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __('Select the period you want the above interval to be in. For example: if you selected "5" as Interval and "Days" as the Period, then your automatic sync will be every 5 Days', WC_TradeGecko_Init::$text_domain),
					'class'		=> WC_TradeGecko_Init::$prefix .'order_update_sync_time_period',
					'css'		=> 'width: 100px;',
					'type'		=> 'select',
					'options'	=> array( 'MINUTE_IN_SECONDS' => 'Minutes', 'HOUR_IN_SECONDS' => 'Hours', 'DAY_IN_SECONDS' => 'Days' ),
				),

				array(
					'id'		=> 'auto_inventory_sync_section',
					'name'		=> '<strong>' . __('Automatic Inventory Sync', WC_TradeGecko_Init::$text_domain) . '</strong>',
					'desc'		=> __('This section will help you schedule automatic inventory synchronizations.', WC_TradeGecko_Init::$text_domain),
					'type'		=> 'header',
				),
				array(
					'id'		=> 'automatic_inventory_sync',
					'name'		=> __('Inventory Synchronization', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __('Enable, to be able to setup automatic inventory sync schedule.<br />Next scheduled inventory sync: '. WC_TradeGecko_Init::get_formatted_datetime( wp_next_scheduled( 'wc_tradegecko_inventory_synchronization' ) ), WC_TradeGecko_Init::$text_domain),
					'class'		=> WC_TradeGecko_Init::$prefix .'automatic_inventory_sync',
					'label'		=> __('Turn on Automatic Inventory Synchronization', WC_TradeGecko_Init::$text_domain),
					'type'		=> 'checkbox',
				),
				array(
					'id'		=> 'sync_inventory_time_interval',
					'name'		=> __('Sync Time Interval', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __('Select the time interval you want the automatic inventory synchronization to be in.<br/><strong>IMPORTANT:</strong> Please make sure the three sync schedules are set at least <strong>10 minutes</strong> apart from each other.', WC_TradeGecko_Init::$text_domain),
					'class'		=> WC_TradeGecko_Init::$prefix .'sync_inventory_time_interval',
					'css'		=> 'width: 100px;',
					'type'		=> 'select',
					'options'	=> wc_tradegecko_get_intervals(),
				),
				array(
					'id'		=> 'sync_inventory_time_period',
					'name'		=> __('Sync Time Period', WC_TradeGecko_Init::$text_domain),
					'desc'		=> __('Select the period you want the above interval to be in. For example: if you selected "5" as Interval and "Days" as the Period, then your automatic sync will be every 5 Days', WC_TradeGecko_Init::$text_domain),
					'class'		=> WC_TradeGecko_Init::$prefix .'sync_inventory_time_period',
					'css'		=> 'width: 100px;',
					'type'		=> 'select',
					'options'	=> array( 'MINUTE_IN_SECONDS' => 'Minutes', 'HOUR_IN_SECONDS' => 'Hours', 'DAY_IN_SECONDS' => 'Days' ),
				),

				array(
					'id'		=> 'manual_sync_section',
					'name'		=> '<strong>' . __('Manual Sync', WC_TradeGecko_Init::$text_domain) . '</strong>',
					'desc'		=> __('This section will help you perform manual synchronizations.', WC_TradeGecko_Init::$text_domain),
					'type'		=> 'header',
				),
				array(
					'id'		=> 'manual_sync',
					'name'		=> '',
					'desc'		=> __('Pressing the button will perform a manual orders export synchronization.<br/><strong>IMPORTANT: </strong>Do not click the button, if you have more than 100 order to update and export. Please schedule an automatic synchronization event and wait for it to run instead.', WC_TradeGecko_Init::$text_domain),
					'type'		=> 'hook',
				),
				array(
					'id'		=> 'manual_order_update_sync',
					'name'		=> '',
					'desc'		=> __('Pressing the button will perform a manual orders update synchronization.<br/><strong>IMPORTANT: </strong>Do not click the button, if you have more than 100 order to update and export. Please schedule an automatic synchronization event and wait for it to run instead.', WC_TradeGecko_Init::$text_domain),
					'type'		=> 'hook',
				),
				array(
					'id'		=> 'manual_inventory_sync',
					'name'		=> '',
					'desc'		=> __('Pressing the button will perform a manual inventory synchronization.<br/><strong>IMPORTANT: </strong>Do not click the button, if you have more than 100 products to update and sync. Please schedule an automatic synchronization event and wait for it to run instead.', WC_TradeGecko_Init::$text_domain),
					'type'		=> 'hook',
				),
			)
		),
		'sync-log' => apply_filters( 'wc_tradegecko_settings_sync_log',
			array(
				array(
					'id'		=> 'clear_sync',
					'name'		=> '',
					'desc'		=> __('Press the button to clear the synchronization logs.', WC_TradeGecko_Init::$text_domain),
					'type'		=> 'hook',
				),

				array(
					'id'		=> 'sync_log_table',
					'name'		=> '',
					'desc'		=> __('Table to dispay the synchronization logs.', WC_TradeGecko_Init::$text_domain),
					'type'		=> 'hook',
				),
			)
		),
	);

	if( false == get_option( WC_TradeGecko_Init::$prefix .'settings_general' ) ) {
		add_option( WC_TradeGecko_Init::$prefix .'settings_general' );
	}
	if( false == get_option( WC_TradeGecko_Init::$prefix .'settings_api' ) ) {
		add_option( WC_TradeGecko_Init::$prefix .'settings_api' );
	}
	if( false == get_option( WC_TradeGecko_Init::$prefix .'settings_sync' ) ) {
		add_option( WC_TradeGecko_Init::$prefix .'settings_sync' );
	}
	if( false == get_option( WC_TradeGecko_Init::$prefix .'settings_sync_log' ) ) {
		add_option( WC_TradeGecko_Init::$prefix .'settings_sync_log' );
	}

	add_settings_section(
		WC_TradeGecko_Init::$prefix .'settings_general',
		__('General Settings', WC_TradeGecko_Init::$text_domain),
		'__return_false',
		WC_TradeGecko_Init::$prefix .'settings_general'
	);

	foreach( $wc_tradegecko_settings['general'] as $option ) {
		add_settings_field(
			WC_TradeGecko_Init::$prefix .'settings_general[' . $option['id'] . ']',
			$option['name'],
			function_exists( WC_TradeGecko_Init::$prefix .'' . $option['type'] . '_callback' ) ? WC_TradeGecko_Init::$prefix .'' . $option['type'] . '_callback' : 'wc_tradegecko_missing_callback',
			WC_TradeGecko_Init::$prefix .'settings_general',
			WC_TradeGecko_Init::$prefix .'settings_general',
			array(
				'id' => $option['id'],
				'desc' => $option['desc'],
				'desc_style' => isset($option['desc_style']) ? $option['desc_style'] : '',
				'css' => isset($option['css']) ? $option['css'] : '',
				'class' => isset($option['class']) ? $option['class'] : '',
				'name' => isset($option['name']) ? $option['name'] : '',
				'label'	=> isset($option['label']) ? $option['label'] : '',
				'section' => 'general',
				'size' => isset($option['size']) ? $option['size'] : null,
				'options' => isset($option['options']) ? $option['options'] : '',
				'std' => isset($option['std']) ? $option['std'] : '',
				'before_option' => isset($option['before_option']) ? $option['before_option'] : '',
				'after_option'	=> isset($option['after_option']) ? $option['after_option'] : '',
			)
		);
	}

	add_settings_section(
		WC_TradeGecko_Init::$prefix .'settings_api',
		__('API Settings', WC_TradeGecko_Init::$text_domain),
		'__return_false',
		WC_TradeGecko_Init::$prefix .'settings_api'
	);

	foreach( $wc_tradegecko_settings['api'] as $option ) {
		add_settings_field(
			WC_TradeGecko_Init::$prefix .'settings_api[' . $option['id'] . ']',
			$option['name'],
			function_exists( WC_TradeGecko_Init::$prefix .'' . $option['type'] . '_callback' ) ? WC_TradeGecko_Init::$prefix .'' . $option['type'] . '_callback' : 'wc_tradegecko_missing_callback',
			WC_TradeGecko_Init::$prefix .'settings_api',
			WC_TradeGecko_Init::$prefix .'settings_api',
			array(
				'id' => $option['id'],
				'desc' => $option['desc'],
				'desc_style' => isset($option['desc_style']) ? $option['desc_style'] : '',
				'css' => isset($option['css']) ? $option['css'] : '',
				'class' => isset($option['class']) ? $option['class'] : '',
				'name' => isset($option['name']) ? $option['name'] : '',
				'label'	=> isset($option['label']) ? $option['label'] : '',
				'attr'	=> isset($option['attr']) ? $option['attr'] : '',
				'section' => 'api',
				'size' => isset($option['size']) ? $option['size'] : null,
				'options' => isset($option['options']) ? $option['options'] : '',
				'std' => isset($option['std']) ? $option['std'] : '',
				'before_option' => isset($option['before_option']) ? $option['before_option'] : '',
				'after_option'	=> isset($option['after_option']) ? $option['after_option'] : '',
			)
		);
	}

	add_settings_section(
		WC_TradeGecko_Init::$prefix .'settings_sync',
		__('Sync Settings', WC_TradeGecko_Init::$text_domain),
		'__return_false',
		WC_TradeGecko_Init::$prefix .'settings_sync'
	);

	foreach( $wc_tradegecko_settings['sync'] as $option ) {
		add_settings_field(
			WC_TradeGecko_Init::$prefix .'settings_sync[' . $option['id'] . ']',
			$option['name'],
			function_exists( WC_TradeGecko_Init::$prefix .'' . $option['type'] . '_callback' ) ? WC_TradeGecko_Init::$prefix .'' . $option['type'] . '_callback' : 'wc_tradegecko_missing_callback',
			WC_TradeGecko_Init::$prefix .'settings_sync',
			WC_TradeGecko_Init::$prefix .'settings_sync',
			array(
				'id' => $option['id'],
				'desc' => $option['desc'],
				'desc_style' => isset($option['desc_style']) ? $option['desc_style'] : '',
				'css' => isset($option['css']) ? $option['css'] : '',
				'class' => isset($option['class']) ? $option['class'] : '',
				'name' => isset($option['name']) ? $option['name'] : '',
				'label'	=> isset($option['label']) ? $option['label'] : '',
				'attr'	=> isset($option['attr']) ? $option['attr'] : '',
				'section' => 'sync',
				'size' => isset($option['size']) ? $option['size'] : null,
				'options' => isset($option['options']) ? $option['options'] : '',
				'std' => isset($option['std']) ? $option['std'] : '',
				'before_option' => isset($option['before_option']) ? $option['before_option'] : '',
				'after_option'	=> isset($option['after_option']) ? $option['after_option'] : '',
			)
		);
	}

	add_settings_section(
		WC_TradeGecko_Init::$prefix .'settings_sync_log',
		__('Synchronization Logs', WC_TradeGecko_Init::$text_domain),
		'__return_false',
		WC_TradeGecko_Init::$prefix .'settings_sync_log'
	);

	foreach( $wc_tradegecko_settings['sync-log'] as $option ) {
		add_settings_field(
			WC_TradeGecko_Init::$prefix .'settings_sync_log[' . $option['id'] . ']',
			$option['name'],
			function_exists( WC_TradeGecko_Init::$prefix .'' . $option['type'] . '_callback' ) ? WC_TradeGecko_Init::$prefix .'' . $option['type'] . '_callback' : 'wc_tradegecko_missing_callback',
			WC_TradeGecko_Init::$prefix .'settings_sync_log',
			WC_TradeGecko_Init::$prefix .'settings_sync_log',
			array(
				'id' => $option['id'],
				'desc' => $option['desc'],
				'desc_style' => isset($option['desc_style']) ? $option['desc_style'] : '',
				'css' => isset($option['css']) ? $option['css'] : '',
				'class' => isset($option['class']) ? $option['class'] : '',
				'name' => isset($option['name']) ? $option['name'] : '',
				'label'	=> isset($option['label']) ? $option['label'] : '',
				'attr'	=> isset($option['attr']) ? $option['attr'] : '',
				'section' => 'sync-log',
				'size' => isset($option['size']) ? $option['size'] : null,
				'options' => isset($option['options']) ? $option['options'] : '',
				'std' => isset($option['std']) ? $option['std'] : '',
				'before_option' => isset($option['before_option']) ? $option['before_option'] : '',
				'after_option'	=> isset($option['after_option']) ? $option['after_option'] : '',
			)
		);
	}

	register_setting( WC_TradeGecko_Init::$prefix .'settings_general',	WC_TradeGecko_Init::$prefix .'settings_general',	WC_TradeGecko_Init::$prefix .'settings_sanitize' );
	register_setting( WC_TradeGecko_Init::$prefix .'settings_api',		WC_TradeGecko_Init::$prefix .'settings_api',		WC_TradeGecko_Init::$prefix .'settings_sanitize' );
	register_setting( WC_TradeGecko_Init::$prefix .'settings_sync',		WC_TradeGecko_Init::$prefix .'settings_sync',		WC_TradeGecko_Init::$prefix .'settings_sanitize' );
	register_setting( WC_TradeGecko_Init::$prefix .'settings_sync_log',	WC_TradeGecko_Init::$prefix .'settings_sync_log',	WC_TradeGecko_Init::$prefix .'settings_sanitize' );
}

/**
 * Show settings description
 *
 * @since 1.0
 * @param type $args
 * @return string
 */
function wc_tradegecko_get_setting_description( $args ) {
	if ( $args['desc_style'] == 'tip' ) {
    		$description = '<img class="help_tip" width="16" height="16" data-tip="' . esc_attr( $args['desc'] ) . '" src="' . WC_TradeGecko_Init::$plugin_url . 'assets/images/help.png" />';
    	} elseif ( $args['desc_style'] == 'text' ) {
    		$description = '<br /><span class="description">' . $args['desc'] . '</span>';
    	} else {
		$description = '<br /><span class="description">' . $args['desc'] . '</span>';
	}

	return $description;
}

/**
 * Header Callback
 *
 * Renders the header. <br />
 * Header is currently not shown. May not be needed.
 * REVIEW
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_header_callback( $args ) {
	echo '';
}

/**
 * Checkbox Callback
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_checkbox_callback($args) {

	$checked = isset( WC_TradeGecko_Init::$settings[ $args['id'] ] ) ? checked(1, WC_TradeGecko_Init::$settings[ $args['id'] ], false) : '';

	$html = '<fieldset>';
	$html .= $args['before_option'];
	$html .= '<legend class="screen-reader-text"><span>'. $args['label'] .'</span></legend>';
	$html .= '<label for="'. $args['id'] .'" > ';
	$html .= '<input name="'. WC_TradeGecko_Init::$prefix .'settings_' . $args['section'] . '[' . $args['id'] . ']" class="'. $args['class'] .' " id="'. $args['id'] .'" type="checkbox" value="1" style="'. $args['css'] .'" '. $checked .' /> ';
	$html .= $args['label']. '</label>';
        $html .= wc_tradegecko_get_setting_description($args) .'<br />';
	$html .= $args['after_option'];
	$html .= '</fieldset>';

	echo $html;
}

/**
 * Multicheck Callback
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_multicheck_callback($args) {

	$html = '<fieldset>';
	$html .= $args['before_option'];
	foreach( $args['options'] as $key => $option ):
		if( isset( WC_TradeGecko_Init::$settings[$args['id']][$key] ) ) { $enabled = $option; } else { $enabled = NULL; }
		$html .= '<input name="'. WC_TradeGecko_Init::$prefix .'settings_' . $args['section'] . '[' . $args['id'] . '][' . $key . ']"" class="'. $args['class'] .' " id="'. WC_TradeGecko_Init::$prefix .'settings_' . $args['section'] . '[' . $args['id'] . '][' . $key . ']" type="checkbox" style="'. $args['css'] .'" value="' . $option . '" ' . checked($option, $enabled, false) . '/>&nbsp;';
		$html .= '<label for="'. WC_TradeGecko_Init::$prefix .'settings_' . $args['section'] . '[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br/>';
	endforeach;
	$html .= wc_tradegecko_get_setting_description($args).'<br />';
	$html .= $args['after_option'];
	$html .= '</fieldset>';

	echo $html;
}

/**
 * Radio Callback
 *
 * @since 1.0
 * @return void
*/
function wc_tradegecko_radio_callback($args) {


	$html = '<fieldset>';
	$html .= $args['before_option'];
	foreach($args['options'] as $key => $option) :
		$checked = false;
		if( isset( WC_TradeGecko_Init::$settings[ $args['id'] ] ) && WC_TradeGecko_Init::$settings[ $args['id'] ] == $key ) $checked = true;
		echo '<input name="'. WC_TradeGecko_Init::$prefix .'settings_' . $args['section'] . '[' . $args['id'] . ']" style="'. $args['css'] .'" class="'. $args['class'] .' " id="'. $args['id'] .'" type="radio" value="' . $key . '" ' . checked(true, $checked, false) . '/>&nbsp;';
		echo '<label for="'. $args['id'] .'">' . $option . '</label><br/>';
	endforeach;
	$html .= wc_tradegecko_get_setting_description($args).'<br />';
	$html .= $args['after_option'];
	$html .= '</fieldset>';

}

/**
 * Text Callback
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_text_callback($args) {

	if( isset( WC_TradeGecko_Init::$settings[ $args['id'] ] ) ) { $value = WC_TradeGecko_Init::$settings[ $args['id'] ]; } else { $value = isset( $args['std'] ) ? $args['std'] : ''; }
	$size = isset( $args['size'] ) && ! is_null($args['size']) ? $args['size'] : 'regular';
	$attr = isset( $args['attr'] ) ? $args['attr'] : '';
	$html = '<fieldset>';
	$html .= $args['before_option'];
	$html = '<input type="text" class="' . $size . '-text '. $args['class'] .'" style="'. $args['css'] .'" id="'. $args['id'] .'" name="'. WC_TradeGecko_Init::$prefix .'settings_' . $args['section'] . '[' . $args['id'] . ']" '. $attr .' value="' . esc_attr( $value ) . '"/>';
	$html .= '<label for="'. $args['id'] .'"></label>';
	$html .= wc_tradegecko_get_setting_description($args).'<br />';
	$html .= $args['after_option'];
	$html .= '</fieldset>';

	echo $html;
}

/**
 * Textarea Callback
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_textarea_callback($args) {

	if( isset( WC_TradeGecko_Init::$settings[ $args['id'] ] ) ) { $value = WC_TradeGecko_Init::$settings[ $args['id'] ]; } else { $value = isset( $args['std'] ) ? $args['std'] : ''; }
	$size = isset( $args['size'] ) && ! is_null($args['size']) ? $args['size'] : 'regular';
	$html = '<fieldset>';
	$html .= $args['before_option'];
	$html = '<textarea class="' . $size . '-text '. $args['class'] .' " style="'. $args['css'] .'" cols="50" rows="5" id="'. $args['id'] .'" name="'. WC_TradeGecko_Init::$prefix .'settings_' . $args['section'] . '[' . $args['id'] . ']">' . esc_textarea( $value ) . '</textarea>';
	$html .= '<label for="'. $args['id'] .'"></label>';
	$html .= wc_tradegecko_get_setting_description($args).'<br />';
	$html .= $args['after_option'];
	$html .= '</fieldset>';

	echo $html;
}

/**
 * Password Callback
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_password_callback($args) {

	if( isset( WC_TradeGecko_Init::$settings[ $args['id'] ] ) ) { $value = WC_TradeGecko_Init::$settings[ $args['id'] ]; } else { $value = isset( $args['std'] ) ? $args['std'] : ''; }
	$size = isset( $args['size'] ) && ! is_null($args['size']) ? $args['size'] : 'regular';
	$html = '<fieldset>';
	$html .= $args['before_option'];
	$html = '<input type="password" class="' . $size . '-text '. $args['class'] .' " style="'. $args['css'] .'" id="'. $args['id'] .'" name="'. WC_TradeGecko_Init::$prefix .'settings_' . $args['section'] . '[' . $args['id'] . ']" value="' . esc_attr( $value ) . '"/>';
	$html .= '<label for="'. $args['id'] .'"></label>';
	$html .= wc_tradegecko_get_setting_description($args).'<br />';
	$html .= $args['after_option'];
	$html .= '</fieldset>';

	echo $html;
}

/**
 * Missing Callback
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_missing_callback($args) {
	printf( __( 'The callback function used for the <strong>%s</strong> setting is missing.', WC_TradeGecko_Init::$text_domain ), $args['id'] );
}

/**
 * Select Callback
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_select_callback($args) {

	$html = '<fieldset>';
	$html .= $args['before_option'];
	$html = '<select id="'. $args['id'] .'" style="'. $args['css'] .'" class="chosen_select '. $args['class'] .' " name="'. WC_TradeGecko_Init::$prefix .'settings_' . $args['section'] . '[' . $args['id'] . ']"/>';
	foreach( $args['options'] as $option => $name ) {
		$selected = isset( WC_TradeGecko_Init::$settings[ $args['id'] ] ) ? selected( $option, WC_TradeGecko_Init::$settings[$args['id']], false ) : '';
		$html .= '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>';
	}
	$html .= '</select>';
	$html .= '<label for="'. $args['id'] .'"></label>';
	$html .= wc_tradegecko_get_setting_description($args).'<br />';
	$html .= $args['after_option'];
	$html .= '</fieldset>';

	echo $html;
}

/**
 * Rich Editor Callback
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_rich_editor_callback($args) {
	global $wp_version;

	if( isset( WC_TradeGecko_Init::$settings[ $args['id'] ] ) ) { $value = WC_TradeGecko_Init::$settings[ $args['id'] ]; } else { $value = isset( $args['std'] ) ? $args['std'] : ''; }
	$html = '<fieldset>';
	$html .= $args['before_option'];
	if( $wp_version >= 3.3 && function_exists('wp_editor')) {
		$html = wp_editor( $value, WC_TradeGecko_Init::$prefix .'settings_' . $args['section'] . '[' . $args['id'] . ']', array( 'textarea_name' => WC_TradeGecko_Init::$prefix .'settings_' . $args['section'] . '[' . $args['id'] . ']', 'editor_class' => $args['class'], 'textarea_rows' => 5) );
	} else {
		$html = '<textarea class="large-text  '. $args['class'] .'" rows="5" cols="10" id="'. WC_TradeGecko_Init::$prefix .'settings_' . $args['section'] . '[' . $args['id'] . ']" name="'. WC_TradeGecko_Init::$prefix .'settings_' . $args['section'] . '[' . $args['id'] . ']">' . esc_textarea( $value ) . '</textarea>';
		$html .= '<br/><label for="'. WC_TradeGecko_Init::$prefix .'settings_' . $args['section'] . '[' . $args['id'] . ']"></label>';
	}
	$html .= wc_tradegecko_get_setting_description($args).'<br />';
	$html .= $args['after_option'];
	$html .= '</fieldset>';

	echo $html;
}

/**
 * Hook Callback
 *
 * Adds a do_action() hook in place of the field
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_hook_callback( $args ) {
	do_action( 'callback_hook_' . $args['id'], $args );
}

/**
 * Settings Sanitization
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_settings_sanitize( $input ) {
	return $input;
}

add_action( 'callback_hook_get_authorization_code', 'wc_tradegecko_get_authorization_code' );
/**
 * Get Auth Code
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_get_authorization_code( $args ) {

	$html = '<fieldset>';
	$html .= '<input type="button" id="wc_tradegecko_get_authorization_code" class="button" value="Get Authorization Code" />';
	$html .= wc_tradegecko_get_setting_description($args).'<br />';
	$html .= '</fieldset>';
	$html .= do_action('wc_tradegecko_get_authorization_code_script');

	echo $html;
}

add_action( 'wc_tradegecko_get_authorization_code_script', 'wc_tradegecko_get_authorization_code_script');
/**
 * Output the Get Auth Code Script
 *
 * @since 1.0
 */
function wc_tradegecko_get_authorization_code_script() {
	$url = http_build_query(
		array(
			'response_type'=>'code',
			'redirect_uri'=>WC_TradeGecko_Init::$settings[ 'redirect_uri' ],
			'client_id'=>WC_TradeGecko_Init::$settings[ 'client_id' ]
		)
	);

	?>
	<script type="text/javascript">
		jQuery(document).ready(function() {
			jQuery('#wc_tradegecko_get_authorization_code').click( function(){
				var url = "https://api.tradegecko.com/oauth/authorize?" + "<?php echo $url; ?>";
				var client_id;
				var redirect_url;
				client_id = jQuery( 'input.<?php echo WC_TradeGecko_Init::$prefix .'client_id' ?>' ).val();
				redirect_url = jQuery( 'input.<?php echo WC_TradeGecko_Init::$prefix .'redirect_uri' ?>' ).val()

				if( 0 >= client_id.length ) {

					alert( 'Please enter "API Application Id" first.' );
					jQuery('input.<?php echo WC_TradeGecko_Init::$prefix .'client_id' ?>').focus();

				} else if( 0 >= redirect_url.length ) {

					alert( 'Please enter "Redirect URI" first.' );
					jQuery( 'input.<?php echo WC_TradeGecko_Init::$prefix .'redirect_uri' ?>' ).focus();

				} else {
					window.location.replace( url );
				}

				return false;

			});
		});
	</script>
	<?php

}

add_action( 'callback_hook_manual_sync', 'wc_tradegecko_manual_sync' );
/**
 * Manual sync
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_manual_sync( $args ) {

        $nonce = wp_create_nonce('wc_tradegecko_manual_sync_nonce');
	$url_query = http_build_query(
		array(
                        'action'	=> 'wc_tradegecko_manual_sync',
			'_wpnonce'	=> $nonce,
		)
	);

	$url = admin_url('admin-ajax.php?' ) . $url_query;
	$count = WC_TradeGecko_Init::get_processing_orders_count();

	$html = '<fieldset>';
	$html .= '<a href="'. $url .'" class="button">'. __( 'Manual Orders Export Sync', WC_TradeGecko_Init::$text_domain );
	if ( 0 < $count ) {
		$html .= ' - ' . $count;
		$html .= ( 1 == $count ) ? ' Order' : ' Orders';
	}

	$html .= '</a>';
	$html .= wc_tradegecko_get_setting_description($args).'<br />';
	$html .= '</fieldset>';

	echo $html;
}

add_action( 'callback_hook_manual_order_update_sync', 'wc_tradegecko_manual_order_update_sync' );
/**
 * Manual sync
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_manual_order_update_sync( $args ) {

        $nonce = wp_create_nonce('wc_tradegecko_manual_order_update_sync_nonce');
	$url_query = http_build_query(
		array(
                        'action'	=> 'wc_tradegecko_manual_order_update_sync',
			'_wpnonce'	=> $nonce,
		)
	);

	$url = admin_url('admin-ajax.php?' ) . $url_query;
	$count = WC_TradeGecko_Init::get_processing_orders_count();

	$html = '<fieldset>';
	$html .= '<a href="'. $url .'" class="button">'. __( 'Manual Orders Update Sync', WC_TradeGecko_Init::$text_domain );
	if ( 0 < $count ) {
		$html .= ' - ' . $count;
		$html .= ( 1 == $count ) ? ' Order' : ' Orders';
	}

	$html .= '</a>';
	$html .= wc_tradegecko_get_setting_description($args).'<br />';
	$html .= '</fieldset>';

	echo $html;
}

add_action( 'callback_hook_manual_inventory_sync', 'wc_tradegecko_manual_inventory_sync' );
/**
 * Manual sync
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_manual_inventory_sync( $args ) {

        $nonce = wp_create_nonce('wc_tradegecko_manual_inventory_sync_nonce');
	$url_query = http_build_query(
		array(
			'action'	=> 'wc_tradegecko_inventory_manual_sync',
			'_wpnonce'	=> $nonce,
		)
	);

	$url = admin_url('admin-ajax.php?' ) . $url_query;

	$count = WC_TradeGecko_Init::get_products_count();

	$html = '<fieldset>';
	$html .= '<a href="'. $url .'" class="button">'. __( 'Manual Inventory Sync', WC_TradeGecko_Init::$text_domain );
	if ( 0 < $count ) {
		$html .= ' - ' . $count . ' ' . _n( 'Product', 'Products', intval( $count ), WC_TradeGecko_Init::$text_domain );
	}

	$html .= '</a>';
	$html .= wc_tradegecko_get_setting_description($args).'<br />';
	$html .= '</fieldset>';

	echo $html;
}

add_action( 'callback_hook_clear_sync', 'wc_tradegecko_clear_sync' );
/**
 * Clear Sync Logs
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_clear_sync( $args ) {

	$nonce = wp_create_nonce( 'wc_tradegecko_clear_sync_logs' );
	$url_query = http_build_query(
		array(
			'action'	=> 'wc_tradegecko_clear_sync_logs',
			'_wpnonce'	=> $nonce,
		)
	);

	$url = admin_url('admin-ajax.php?' ) . $url_query;

	$html = '<fieldset>';
	$html .= '<a href="'. $url .'" class="button">'. __( 'Clear Sync Logs', WC_TradeGecko_Init::$text_domain ) .'</a>';
	$html .= wc_tradegecko_get_setting_description($args).'<br />';
	$html .= '</fieldset>';

	echo $html;
}

add_action( 'callback_hook_sync_log_table', 'wc_tradegecko_sync_log_table' );
/**
 * Sync Logs Table
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_sync_log_table( $args ) {

	$html = do_action('wc_tradegecko_get_sync_log_table');

	echo $html;
}

add_action( 'wc_tradegecko_get_sync_log_table', 'wc_tradegecko_get_sync_log_table');
/**
 * Output the Sync Log Table
 *
 * @since 1.0
 */
function wc_tradegecko_get_sync_log_table() {
	// Get the log
	$log = get_option( WC_TradeGecko_Init::$prefix . 'sync_log', array() );
	$log_data = array();

	$i = 1;
	foreach ( array_reverse( $log ) as $log_key => $log_value ) {

		$log_data[] = array(
			'ID'		=> $log_key,
			'order_num'	=> $i,
			'datetime'	=> WC_TradeGecko_Init::get_formatted_datetime( $log_value['timestamp'] ),
			'log_type'	=> sprintf( '<mark class="%s">%s</mark>', strtolower( $log_value['log_type']), $log_value['log_type'] ),
			'action'	=> $log_value['action']
		);

		$i++;
	}

	$log_table = new WC_TradeGecko_List_Table( $log_data );

	$log_table->prepare_items();
	$log_table->display();

}

/**
 * Return the time interval array, used for the sync schedule
 * Intervals are from 1 to 120
 *
 * @return int
 */
function wc_tradegecko_get_intervals() {
	$intervals = array();

	for( $i = 1; $i <= 120; $i++ ) {
		$intervals[ $i ] = $i;
	}

	return $intervals;
}

add_action( 'callback_hook_get_api_credentials', 'wc_tradegecko_get_api_credentials' );
/**
 * Get API Credentials Button
 *
 * @since 1.3.2
 * @return void
 */
function wc_tradegecko_get_api_credentials( $args ) {

	$html = '<fieldset>';
	$html .= '<a href="https://go.tradegecko.com/oauth/applications/woocommerce?redirect_uri='. urlencode( admin_url( '/admin.php' ) ) . '" target="_blank" class="button">'. __( 'Retrieve TradeGecko API credentials', WC_TradeGecko_Init::$text_domain ) .'</a>';
	$html .= wc_tradegecko_get_setting_description($args).'<br />';
	$html .= '</fieldset>';

	echo $html;
}