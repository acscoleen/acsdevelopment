<?php
/*
 * Plugin Name: WooCommerce TradeGecko Integration
 * Plugin URI: http://woothemes.com/products/woocommerce-tradegecko/
 * Description: This plugin integrates your TradeGecko Account with your WooCommerce store.
 * Version: 1.5.3
 * Author: TradeGecko Pte Ltd
 * Author URI: http://tradegecko.com/
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once('woo-includes/woo-functions.php');

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '21da7811f7fc1f13ee19daa7415f0ff3', 245960 );

/**
 * Main Class to TradeGecko
 *
 * @since 1.0
 */
class WC_TradeGecko_Init {

	/** Plugin prefix for options */
	public static $prefix = 'wc_tradegecko_';

	/** Plugin prefix for options */
	public static $meta_prefix = '_wc_tradegecko_';

	/** Plugin text domain */
	public static $text_domain = 'wc_tradegecko_lang';

	/** Settings page tag name */
	public static $settings_page = 'tradegecko';

	/** Plugin Directory Path */
	public static $plugin_dir;

	/** Plugin Directory URL */
	public static $plugin_url;

	/** Plugin settings holder */
	public static $settings;

	/** @var \WC_TradeGecko_API */
	public static $api;

	/** Debug log */
	public static $log;

	/** The store product count */
	private static $product_count;

	/** The store orders count */
	private static $orders_count;

	/** TG Price Lists */
	private static $tg_price_lists;

	/** TG Currencies */
	private static $tg_currencies;

	/** TG Locations */
	private static $tg_locations;

	/** Is Subscriptions plugin active */
	private static $is_subscriptions_active;

	const VERSION = '1.5.3';

	public function __construct() {

		// Install settings
		if ( is_admin() && ! defined('DOING_AJAX') ) $this->install();

		/** Plugin Directory Path */
		self::$plugin_dir = plugin_dir_path( __FILE__ );

		/** Plugin Directory URL */
		self::$plugin_url = plugin_dir_url( __FILE__ );

		self::$settings = $this->get_settings();

		// Include required files
		$this->includes();

		if ( is_admin() ) {
			add_action( 'woocommerce_init', array( $this, 'admin_includes' ) );
		}

		// Init the TG API
		$this->init_api();

		// Actions
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 15 );
		add_action( 'admin_init', array( $this, 'admin_init_listner' ) );

		// Add a 'Settings' link to the plugin action links
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'settings_link' ), 10, 4 );

		// Add admin notice for the Price List Mapping
		add_action( 'admin_notices', array( $this, 'maybe_show_admin_notifications' ) );
	}

	/**
	 * Admin Init listener. Call all "Admin Init" specific actions.
	 */
	public function admin_init_listner() {

		// Capture the new Auth Code
		$this->save_new_auth_code();

	}

	/**
	 * Capture and save the Authorization Code to the settings
	 *
	 * @since 1.0
	 * @access public
	 * @return void
	 */
	public function save_new_auth_code() {
		$new_code = self::get_get( 'code' );
		if ( ! empty( $new_code ) ) {

			$api = get_option( self::$prefix .'settings_api' );

			$api[ 'auth_code' ] = $new_code;

			update_option( self::$prefix .'settings_api', $api );

			// Update a potential Auth Error option
			update_option( 'wc_tg_auth_error', '' );

			wp_redirect( self::get_admin_url( 'api' ) .'&new-auth-code-obtained=true' );
			exit;
		}
	}

	public static function add_sync_log( $log_type = 'Message', $message = '' ) {

		if ( ! $message ) {
			return;
		}

		// Get the sync log
		$sync_log = get_option( self::$prefix . 'sync_log', array() );

		// Add new message/error to the log
		$sync_log[] = array( 'timestamp' => time(), 'log_type' => $log_type, 'action' => $message );

		// Remove the oldest messeges from the log
		if ( 30 < count( $sync_log ) ) {
			array_shift( $sync_log );
		}

		$error_count = get_option( self::$prefix . 'error_count', 0 );
		if ( 'error' == strtolower( $log_type ) ) {
			$error_count++;

			update_option( self::$prefix . 'error_count', $error_count );
		}

		update_option( self::$prefix . 'sync_log', $sync_log );
	}

	/**
	 * Include plugin files
	 *
	 * @since 1.0
	 * @access public
	 * @return void
	 */
	public function includes() {

		if ( WC_TradeGecko_Init::get_setting( 'enable' ) ) {
			include_once( 'classes/class-wc-tradegecko-sync.php' ); // Sync class
			include_once( 'classes/class-wc-tradegecko-cron.php' ); // Cron schedule class
			include_once( 'classes/class-wc-tradegecko-admin.php' ); // Sync logs class
		}
		include_once( 'classes/class-wc-compat-tg.php' ); // WC Compatability class

	}

	/**
	 * Include admin specific plugin files
	 *
	 * @since 1.0
	 * @access public
	 * @return void
	 */
	function admin_includes() {

		include_once( 'admin/wc-tradegecko-register-settings.php' );
		include_once( 'admin/wc-tradegecko-settings.php' );
		include_once( 'classes/class-wc-tradegecko-list-table.php' ); // Sync logs class

		if ( self::get_setting( 'enable' ) ) {
			include_once( 'classes/class-wc-tradegecko-ajax.php' ); // Ajax manipulations class
		}

	}

	/**
	 * Include and init the API class
	 *
	 * @since 1.0
	 * @access public
	 * @return void
	 */
	public function init_api() {

		include_once( 'classes/class-wc-tradegecko-api.php' );

		self::$api = new WC_TradeGecko_API();

	}

	/**
	 * Run on plugin installation
	 *
	 * @since 1.0
	 * @access public
	 * @return void
	 */
	public function install() {
		register_activation_hook(__FILE__, array($this, 'activate') );
	}

	/**
	 * Init
	 *
	 * @since 1.0
	 */
	public function init() {

		load_plugin_textdomain( self::$text_domain, false, dirname( plugin_basename( __FILE__ ) ).'/languages' );

		// Generate the price list and currency options
		$this->generate_tradegecko_price_lists();
		$this->generate_tradegecko_currencies();
		$this->generate_tradegecko_stock_locations();

	}

	/**
	 * Add the admin scripts and styles
	 *
	 * @since 1.0
	 * @access public
	 */
	public function admin_scripts() {

		// Get the admin page we are on
		$screen = get_current_screen();

		wp_register_style( 'wc-tradegecko-admin-styles', self::$plugin_url . 'assets/css/admin.css' );
		wp_enqueue_style( 'wc-tradegecko-admin-styles' );

		// Set the TG scripts and styles only for the TG pages
		if ( 'woocommerce_page_tradegecko' == $screen->id ) {
			wp_register_script( 'wc-tradegecko-chosen', self::$plugin_url . 'assets/js/chosen/chosen.jquery.min.js', array( 'jquery' ), '0.9.8', false );
			wp_register_script( 'wc-tradegecko-admin', self::$plugin_url . 'assets/js/admin.js', array( 'jquery' ), '1.0', false );
			wp_register_style( 'wc-tradegecko-chosen-styles', self::$plugin_url . 'assets/css/chosen.css' );

			wp_enqueue_script( 'wc-tradegecko-admin' );
			wp_enqueue_script( 'wc-tradegecko-chosen' );
			wp_enqueue_style( 'wc-tradegecko-chosen-styles' );

		}

		// Call the WC tip scripts to the users list page
		if ( 'users' == $screen->id ) {
			wp_enqueue_script( 'woocommerce_admin' );
			wp_enqueue_script( 'jquery-tiptip' );

		}

	}

	/**
	 * Add default settings on activation
	 *
	 * @since 1.0
	 * @access public
	 * @return void
	 */
	public function activate() {

		$general_settings = array(
			'enable'		=> '1',
			'enable_debug'		=> '0',
			'inventory_sync'	=> '1',
			'product_price_sync'	=> '1',
			'product_title_sync'	=> '1',
			'orders_sync'		=> '1',
			'order_number_prefix'	=> 'WooCommerce-',
			'product_allow_backorders'	=> 'notify',
			'order_fulfillment_sync'	=> 'full',
			'available_currency_id'		=> '',
			'regular_price_id'		=> '',
			'allow_sale_price_mapping'	=> '0',
			'sale_price_id'			=> '',
			'stock_location_id'		=> '',
		);

		$get_general = get_option( self::$prefix .'settings_general' );
		if ( empty( $get_general ) ) {
			update_option( self::$prefix .'settings_general', $general_settings );
		}

		$api_settings = array(
			'client_id'	=> '',
			'client_secret' => '',
			'redirect_uri'	=> '',
			'auth_code'	=> '',
			'client_id'	=> '',
		);

		$get_api = get_option( self::$prefix .'settings_api' );
		if ( empty( $get_api ) ) {
			update_option( self::$prefix .'settings_api', $api_settings );
		}

		$sync_settings = array(
			'automatic_sync'		=> '',
			'sync_time_interval'		=> '30',
			'sync_time_period'		=> 'minutes',
			'automatic_inventory_sync'	=> '',
			'sync_inventory_time_interval'	=> '40',
			'sync_inventory_time_period'	=> 'minutes',
			'automatic_order_update_sync'	=> '',
			'order_update_sync_time_interval'	=> '50',
			'order_update_sync_time_period'		=> 'minutes',
		);

		$get_sync = get_option( self::$prefix .'settings_sync' );
		if ( empty( $get_sync ) ) {
			update_option( self::$prefix .'settings_sync', $sync_settings );
		}

	}

	/**
	 * Retrieves all plugin settings and returns them
	 * as a combined array.
	 *
	 * @since 1.0
	 * @access public
	 * @return array
	 */
	public function get_settings() {
		$general_settings = is_array(get_option(self::$prefix .'settings_general')) ? get_option(self::$prefix .'settings_general') : array();
		$api_settings	 = is_array(get_option(self::$prefix .'settings_api')) ? get_option(self::$prefix .'settings_api') : array();
		$sync_settings	 = is_array(get_option(self::$prefix .'settings_sync')) ? get_option(self::$prefix .'settings_sync') : array();

		return array_merge($general_settings, $api_settings, $sync_settings);
	}

	/**
	 * Add Debug Log
	 *
	 * @param string $message
	 */
	public static function add_log( $message ) {
		if ( '1' == self::get_setting( 'enable_debug' ) ) {

			self::get_logger_object();

			self::$log->add( 'tradegecko', $message );
		}
	}

	/**
	 * Get the WC logger object
	 *
	 * @return type
	 */
	public static function get_logger_object() {
		if ( is_object( self::$log ) ) {
			return self::$log;
		} else {
			return self::$log = WC_Compat_TG::get_wc_logger();
		}
	}

	/**
	 * Add 'Settings' link to the plugin actions links
	 *
	 * @since 1.0
	 * @return array associative array of plugin action links
	 */
	public function settings_link( $actions, $plugin_file, $plugin_data, $context ) {
		return array_merge(
			array(
			    'settings' => '<a href="' . self::get_admin_url( 'general' ) .'">' . __( 'Settings', self::$text_domain . '</a>' ),
			    'tg_support' => '<a href="http://support.tradegecko.com">' . __( 'Support', self::$text_domain . '</a>' ),
			    'tg_docs' => '<a href="http://support.tradegecko.com/hc/en-us/sections/200006607-WooCommerce">' . __( 'Documentation', self::$text_domain . '</a>' ),
			),
			$actions
		);
	}

	/**
	 * Helper, Savely retrieve GET variables
	 *
	 * @since 1.0
	 * @param string Get variable name
	 * @return string The variable value
	 **/
	public static function get_get( $name ) {
		if ( isset( $_GET[$name] ) ) {
			return $_GET[$name];
		}
		return null;
	}

	/**
	 * Helper, Savely retrieve POST variables
	 *
	 * @since 1.0
	 * @param string Get variable name
	 * @return string The variable value
	 **/
	public static function get_post( $name ) {
		if ( isset( $_POST[$name] ) ) {
			return $_POST[$name];
		}
		return null;
	}

	/**
	 * Helper, Savely retrieve settings
	 *
	 * @since 1.0
	 * @param string Setting name
	 * @return string The setting value
	 **/
	public static function get_setting( $name ) {
		if ( isset( self::$settings[ $name ] ) ) {
			return self::$settings[ $name ];
		}
		return null;
	}

	/**
	 * Format the date and time from a timestamp string
	 *
	 * @since 1.0
	 * @param int $timestamp
	 * @return string formatted datetime
	 */
	public static function get_formatted_datetime( $timestamp ) {

		if ( ! $timestamp ) {
			return __( 'N/A', self::$text_domain );
		}

		$current_offset = get_option('gmt_offset');
		$tzstring = get_option('timezone_string');

		if ( empty( $tzstring ) ) {
			if ( 0 == $current_offset ) {
				$tzstring = 'UTC';
			} elseif ($current_offset < 0) {
				$tzstring = 'UTC' . $current_offset;
			} else {
				$tzstring = 'UTC+' . $current_offset;
			}
		}

		// Return the date and time
		return date_i18n( WC_Compat_TG::wc_date_format() . ' ' . get_option( 'time_format' ), $timestamp + ( $current_offset * HOUR_IN_SECONDS ) ) .' '. $tzstring;

	}

	/**
	 * Get the count of all products and variations in the store.
	 * Only the published products will be counted.
	 *
	 * @since 1.2.2
	 * @return int The count of all products and variations in the store
	 */
	public static function get_products_count() {
		if ( self::$product_count ) {
			return self::$product_count;
		} else {
			$count1 = wp_count_posts( 'product' )->publish;
			$count2 = wp_count_posts( 'product_variation' )->publish;

			self::$product_count = $count1 + $count2;

			return self::$product_count;
		}
	}

	/**
	 * Get the count of all orders with Processing status
	 *
	 * @since 1.5
	 * @return int The total Processing orders
	 */
	public static function get_processing_orders_count() {
		if ( self::$orders_count ) {
			return self::$orders_count;
		} else {
			self::$orders_count = get_term_by( 'slug', 'processing', 'shop_order_status' )->count;

			return self::$orders_count;
		}
	}

	/**
	 * Get the body of the API response.
	 *
	 * @since 1.2.2
	 * @param array $response The complete API response
	 * @return object The object of the decoded JSON string
	 */
	public static function get_decoded_response_body( $response ) {

		return json_decode( $response['body'] );

	}

	/**
	 * Retieve TradeGecko post meta
	 *
	 * @since 1.3
	 * @param int $post_id
	 * @param string $meta_key
	 * @param bool $single
	 * @return mixed
	 */
	public static function get_post_meta( $post_id, $meta_key, $single = false ) {

		return get_post_meta( $post_id, self::$meta_prefix . $meta_key, $single );

	}

	/**
	 * Update TradeGecko post meta
	 *
	 * @since 1.3
	 * @param int $post_id
	 * @param string $meta_key
	 * @param mized $meta_value
	 * @return bool
	 */
	public static function update_post_meta( $post_id, $meta_key, $meta_value ) {

		// Don't update, if the key was not given
		if ( ! $meta_key ) {
			return;
		}

		return update_post_meta( $post_id, self::$meta_prefix . $meta_key, $meta_value );

	}

	/**
	 * Build TradeGecko Price List associative array to use for price list mapping
	 *
	 * @since 1.3
	 * @return void|bool
	 * @throws Exception
	 */
	private function generate_tradegecko_price_lists() {

		$active_tab = self::get_get( 'tab' ) ? self::get_get( 'tab' ) : 'general';
		$active_page = self::get_get( 'page' ) ? self::get_get( 'page' ) : '';

		// Don't execute, if not TradeGecko page
		if ( 'tradegecko' != $active_page ) {
			return;
		}

		// Don't execute, if not General page
		if ( 'general' != $active_tab ) {
			return;
		}

		if ( ! self::$tg_price_lists ) {

			try {
				$tg_price_lists_data = self::get_decoded_response_body( self::$api->process_api_request( 'GET', 'price_lists' ) );

				// Add log
				self::add_log( 'Price List response data: '. print_r( $tg_price_lists_data, true ) );

				if ( isset( $tg_price_lists_data->error ) ) {
					throw new Exception( sprintf( __( 'Price List could not be retrieved.'
						. ' Error Code: %s. Error Message: %s.', self::$text_domain ),
						$tg_price_lists_data->error,
						$tg_price_lists_data->error_description ) );
				}

				$price_lists = isset( $tg_price_lists_data->price_lists ) ? $tg_price_lists_data->price_lists : $tg_price_lists_data->price_list;

				foreach ( $price_lists as $price_list ) {

					self::$tg_price_lists[ $price_list->id ] = $price_list->name .' ('. $price_list->currency_iso .')';

				}
			} catch( Exception $e ) {
				WC_TradeGecko_Init::add_log( $e->getMessage() );

				self::add_sync_log( 'Error', $e->getMessage() );

				self::$tg_price_lists[0] = __( 'Price Lists could not be retrieved.', self::$text_domain );
			}
		}
	}

	/**
	 * Return the TradeGecko price list
	 *
	 * @since 1.3
	 * @return array
	 */
	public static function get_tradegecko_price_lists() {
		return self::$tg_price_lists;
	}

	/**
	 * Generate an associative array from the TradeGecko currencies
	 *
	 * @return void|bool
	 * @throws Exception
	 */
	private function generate_tradegecko_currencies() {

		$active_tab = self::get_get( 'tab' ) ? self::get_get( 'tab' ) : 'general';
		$active_page = self::get_get( 'page' ) ? self::get_get( 'page' ) : '';

		// Don't execute, if not TradeGecko page
		if ( 'tradegecko' != $active_page ) {
			return;
		}

		// Don't execute, if not General page
		if ( 'general' != $active_tab ) {
			return;
		}

		if ( ! self::$tg_currencies ) {

			try {
				$tg_currencies_data = self::get_decoded_response_body( self::$api->process_api_request( 'GET', 'currencies' ) );

				// Add log
				self::add_log( 'Currecnies response data: '. print_r( $tg_currencies_data, true ) );

				if ( isset( $tg_currencies_data->error ) ) {
					throw new Exception( sprintf( __( 'Currencies could not be retrieved.'
						. ' Error Code: %s.'
						. ' Error Message: %s.', self::$text_domain ),
						$tg_currencies_data->error,
						$tg_currencies_data->error_description ) );
				}

				$currencies = isset( $tg_currencies_data->currencies ) ? $tg_currencies_data->currencies : $tg_currencies_data->currency;

				foreach ( $currencies as $currency ) {

					self::$tg_currencies[ $currency->id ] = $currency->name .' ('. $currency->iso .')';

				}
			} catch( Exception $e ) {
				WC_TradeGecko_Init::add_log( $e->getMessage() );

				self::add_sync_log( 'Error', $e->getMessage() );

				self::$tg_currencies[0] = __( 'Currencies could not be retrieved.', self::$text_domain );
			}
		}
	}

	/**
	 * Return the TradeGecko currencies
	 *
	 * @since 1.3
	 * @return array
	 */
	public static function get_tradegecko_currencies() {
		return self::$tg_currencies;
	}

	/**
	 * Request and generate Stock Locations array
	 *
	 * @since 1.3
	 * @return void|bool
	 * @throws Exception
	 */
	private function generate_tradegecko_stock_locations() {

		$active_tab = self::get_get( 'tab' ) ? self::get_get( 'tab' ) : 'general';
		$active_page = self::get_get( 'page' ) ? self::get_get( 'page' ) : '';

		// Don't execute, if not TradeGecko page
		if ( 'tradegecko' != $active_page ) {
			return;
		}

		// Don't execute, if not General page
		if ( 'general' != $active_tab ) {
			return;
		}

		if ( ! self::$tg_locations ) {

			try {
				$tg_locations_data = self::get_decoded_response_body( self::$api->process_api_request( 'GET', 'locations' ) );

				// Add log
				self::add_log( 'Stock Locations response data: '. print_r( $tg_locations_data, true ) );

				if ( isset( $tg_locations_data->error ) ) {
					throw new Exception( sprintf( __( 'Locations could not be retrieved.'
						. ' Error Code: %s.'
						. ' Error Message: %s.', self::$text_domain ),
						$tg_locations_data->error,
						$tg_locations_data->error_description ) );
				}

				$locations = isset( $tg_locations_data->locations ) ? $tg_locations_data->locations : $tg_locations_data->location;

				foreach ( $locations as $location ) {

					self::$tg_locations[ $location->id ] = $location->label;

				}
			} catch( Exception $e ) {
				WC_TradeGecko_Init::add_log( $e->getMessage() );

				self::add_sync_log( 'Error', $e->getMessage() );

				self::$tg_locations[0] = __( 'Locations could not be retrieved.', self::$text_domain );
			}
		}
	}

	/**
	 * Return the Stock Locations
	 *
	 * @since 1.3
	 * @return array
	 */
	public static function get_tradegecko_stock_locations() {
		return self::$tg_locations;
	}

	/**
	 * Show an admin notice, if we are missing any of the price list parameters
	 *
	 * @since 1.3
	 */
	public function maybe_show_admin_notifications() {
		$product_price_sync		= self::get_setting( 'product_price_sync' );
		$allow_sale_price_mapping	= self::get_setting( 'allow_sale_price_mapping' );
		$regular_price_id		= self::get_setting( 'regular_price_id' );
		$sale_price_id			= self::get_setting( 'sale_price_id' );
		$available_currency_id		= self::get_setting( 'available_currency_id' );
		$client_id			= self::get_setting( 'client_id' );
		$client_secret			= self::get_setting( 'client_secret' );
		$redirect_uri			= self::get_setting( 'redirect_uri' );
		$auth_code			= self::get_setting( 'auth_code' );
		$stock_location_id		= self::get_setting( 'stock_location_id' );

		// Notice for incomplete API setup, if all required credentials are not filled in.
		if ( ! $client_id || ! $client_secret || ! $redirect_uri || ! $auth_code ) {
			?>
			<div id="message" class="error">
				<p><?php echo sprintf( __( 'TradeGecko API setup is not complete.'
					. ' Please visit the %sAPI Settings Page%s to enter your API credentials. ', self::$text_domain ),
					'<a href="'. self::get_admin_url( 'api' ) .'">',
					'</a>' ); ?>
				</p>
			</div>
			<?php
		} elseif (  $product_price_sync && ( '0' == $regular_price_id || '' == $regular_price_id ) ||
			( $allow_sale_price_mapping && ( '0' == $sale_price_id || '' == $sale_price_id ) ) ||
			( '0' == $available_currency_id || '' == $available_currency_id ) ) {

			// Notice for incomplete prices mapping
			?>
			<div id="message" class="error">
				<p><?php echo sprintf( __( 'TradeGecko Product Price Mapping is not complete.'
					. ' Your Prices will not be synced correctly.'
					. ' Please visit the %sGeneral Settings Page%s and setup TradeGecko <strong>Currency,'
					. ' Regular Price, Sale Price mapping</strong>.', self::$text_domain ),
					'<a href="'. self::get_admin_url( 'general' ) .'">',
					'</a>' ); ?>
				</p>
			</div>
			<?php
		} elseif ( '0' == $stock_location_id || '' == $stock_location_id ) {

			// Notice for incomplete Stock Location mapping
			?>
			<div id="message" class="error">
				<p><?php echo sprintf( __( 'TradeGecko Stock Location is not set.'
					. ' Your Stock Levels will not be synced correctly.'
					. ' Please visit the %sGeneral Settings Page%s and set TradeGecko '
					. '<strong>Stock Location</strong> setting.', self::$text_domain ),
					'<a href="'. self::get_admin_url( 'general' ) .'">',
					'</a>' ); ?>
				</p>
			</div>
			<?php
		}

		$auth_error = get_option( 'wc_tg_auth_error' );

		if ( 'error' == $auth_error ) {

			// Add Error notice for the user to Re-Auth his integration
			?>
			<div id="message" class="error">
				<p><?php echo sprintf( __( 'Your TradeGecko credentials need to be refreshed - please %sclick here%s to re-connect now | %sLearn more%s', self::$text_domain ),
					'<a href="'. self::get_admin_url( 'api' ) .'">',
					'</a>',
					'<a target="_blank" href="http://support.tradegecko.com/hc/en-us/sections/200006607-WooCommerce">',
					'</a>' ); ?>
				</p>
			</div>
			<?php

		}



	}

	/**
	 * Get the plugin admin settings page
	 *
	 * @param string $tab The settings page tab
	 * @return string (Optional) The settings URL. Default 'general'
	 */
	public static function get_admin_url( $tab = 'general' ) {
		return admin_url( '/admin.php?page='. self::$settings_page .'&tab='. $tab );
	}

	/**
	 * Detect if WC Subscriptions is active
	 *
	 * @since 1.4
	 * @return bool True if actiove, False if not
	 */
	public static function is_subscriptions_active() {

		if ( is_bool( self::$is_subscriptions_active ) ) {
			return self::$is_subscriptions_active;
		}

		if ( class_exists( 'WC_Subscriptions' ) ) {
			self::$is_subscriptions_active = true;
		} else {
			self::$is_subscriptions_active = false;
		}

		return self::$is_subscriptions_active;

	}

} new WC_TradeGecko_Init;