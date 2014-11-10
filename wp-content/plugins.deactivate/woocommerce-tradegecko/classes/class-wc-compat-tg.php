<?php
/*
 * Class to ensure compatibility in the transition of WC 2.0 to 2.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Compat_TG {

	/**
	 * Is WC 2.1+
	 * @var bool
	 */
	public static $is_wc_2_1;

	/**
	 * Is WC 2.0+
	 * @var type
	 */
	public static $is_wc_2;

	/**
	 * Detect, if we are using WC 2.1+
	 *
	 * @return bool
	 */
	public static function is_wc_2_1() {
		if ( is_bool( self::$is_wc_2_1 ) ) {
			return self::$is_wc_2_1;
		}

		return self::$is_wc_2_1 = ( version_compare( self::get_wc_version_constant(), '2.1', '>=') );
	}

	/**
	 * Detect, if we are using WC 2.0+
	 *
	 * @return bool
	 */
	public static function is_wc_2() {
		if ( is_bool( self::$is_wc_2 ) ) {
			return self::$is_wc_2;
		}

		return self::$is_wc_2 = ( version_compare( self::get_wc_version_constant(), '2.0', '>=') );
	}

	/**
	 * Get the Gateway settings page
	 *
	 * @param string $class_name
	 * @return string Formated URL
	 */
	public static function gateway_settings_page( $class_name ) {
		$page = 'woocommerce_settings';
		$tab = 'payment_gateways';
		$section = $class_name;
		if ( self::is_wc_2_1() ) {
			$page = 'wc-settings';
			$tab = 'checkout';
			$section = strtolower( $class_name );
		}

		return admin_url( 'admin.php?page='. $page .'&tab='. $tab .'&section='. $section );

	}

	/**
	 * Get the WC logger object
	 *
	 * @global object $woocommerce
	 * @return \WC_Logger
	 */
        public static function get_wc_logger() {
                if ( self::is_wc_2_1() ) {
                        return new WC_Logger();
                } else {
                        global $woocommerce;
                        return $woocommerce->logger();
                }
        }

	/**
	 * Get the stacked site notices
	 *
	 * @global object $woocommerce
	 * @param string $notice_type
	 * @return int
	 */
        public static function wc_notice_count( $notice_type = '' ) {
                if ( self::is_wc_2_1() ) {
                        return wc_notice_count( $notice_type );
                } else {
                        global $woocommerce;

                        if ( 'message' == $notice_type ) {
                                return $woocommerce->message_count();
                        } else {
                                return $woocommerce->error_count();
                        }
                }
        }

	/**
	 * Add site notices
	 *
	 * @global object $woocommerce
	 * @param string $message The message to be logged.
	 * @param string $notice_type (Optional) Name of the notice type. Can be success, message, error, notice.
	 * @return void
	 */
	public static function wc_add_notice( $message, $notice_type = 'success' ) {
                if ( self::is_wc_2_1() ) {
                        wc_add_notice( $message, $notice_type );
                } else {
                        global $woocommerce;

                        if ( 'message' == $notice_type || 'success' == $notice_type ) {
                                $woocommerce->add_message( $message );
                        } else {
                                $woocommerce->add_error( $message );
                        }
                }
        }

	/**
	 * Get the global WC object
	 *
	 * @global object $woocommerce
	 * @return object
	 */
	public static function get_wc_global() {
		if ( self::is_wc_2_1() && function_exists( 'WC' ) ) {
                        return WC();
                } else {
			global $woocommerce;
			return $woocommerce;
		}

	}

	/**
	 * Force SSL on a URL
	 *
	 * @global object $woocommerce
	 * @param string $url The URL to format
	 * @return string
	 */
	public static function force_https( $url ) {
		if ( self::is_wc_2_1() ) {
                        return WC_HTTPS::force_https_url( $url );
                } else {
			global $woocommerce;
			return $woocommerce->force_ssl( $url );
		}
	}

	/**
	 * Empty the user cart session
	 *
	 * @global object $woocommerce
	 */
	public static function empty_cart() {
		if ( self::is_wc_2_1() ) {
                        WC()->cart->empty_cart();
                } else {
			global $woocommerce;
			$woocommerce->cart->empty_cart();
		}
	}

	/**
	 * Get other templates (e.g. product attributes) passing attributes and including the file.
	 *
	 * @param mixed $template_name
	 * @param array $args
	 * @param string $template_path
	 * @param string $default_path
	 * @return void
	 */
	public static function wc_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
		if ( self::is_wc_2_1() ) {
                        wc_get_template( $template_name, $args, $template_path, $default_path );
                } else {
			woocommerce_get_template( $template_name, $args, $template_path, $default_path );
		}
	}

	/**
	 * Get Order shipping total
	 *
	 * @param WC_Order $order
	 * @return double
	 */
	public static function get_total_shipping( WC_Order $order ) {
		if ( self::is_wc_2_1() ) {
                        return $order->get_total_shipping();
                } else {
			return $order->get_shipping();
		}
	}

	/**
	 * Get My Account URL
	 *
	 * @return string Formatted URL string
	 */
	public static function get_myaccount_url() {
		if ( self::is_wc_2_1() ) {
                        return get_permalink( wc_get_page_id( 'myaccount' ) );
                } else {
			return get_permalink( woocommerce_get_page_id( 'myaccount' ) );
		}
	}

	/**
	 * Get Order meta object. WC 2.0 compatibility
	 *
	 * @param array $item
	 * @return \order_item_meta|\WC_Order_Item_Meta
	 */
	public static function get_order_item_meta( $item ) {
		if ( self::is_wc_2() ) {
			$item_meta = new WC_Order_Item_Meta( $item['item_meta'] );
		} else {
			$item_meta = new order_item_meta( $item['item_meta'] );
		}

		return $item_meta;
	}

	/**
	 * Get WC version constant.
	 *
	 * @return string|null
	 */
	public static function get_wc_version_constant() {
		if ( defined( 'WC_VERSION' ) && WC_VERSION ) {
			return WC_VERSION;
		}

                if ( defined( 'WOOCOMMERCE_VERSION' ) && WOOCOMMERCE_VERSION ) {
			return WOOCOMMERCE_VERSION;
		}

                return null;
	}

	/**
	 * Include inline JS
	 *
	 * @since 1.0.1
	 * @global object $woocommerce
	 * @param type $script
	 */
	public static function wc_include_js( $script ) {
		if ( self::is_wc_2_1() ) {
			wc_enqueue_js( $script );
		} else {
			global $woocommerce;
			$woocommerce->add_inline_js( $script );
		}
	}

	/**
	 * Get WC page ID
	 *
	 * @since 1.0.1
	 * @param string $page
	 * @return int
	 */
	public static function wc_get_page_id( $page ) {
		if ( self::is_wc_2_1() ) {
			return wc_get_page_id( $page );
		} else {
			return woocommerce_get_page_id( $page );
		}

	}

	/**
	 * Format date
	 *
	 * @return type
	 */
	public static function wc_date_format() {
		if ( self::is_wc_2_1() ) {
			return wc_date_format();
		} else {
			return woocommerce_date_format();
		}
	}

	/**
	 * Format decimal numbers ready for DB storage
	 *
	 * @param type $number
	 * @param type $dp
	 * @param type $trim_zeros
	 * @return type
	 */
	public static function wc_format_decimal( $number, $dp = false, $trim_zeros = false ) {
		if ( self::is_wc_2_1() ) {
			return wc_format_decimal( $number, $dp, $trim_zeros );
		} else {
			return woocommerce_format_decimal( $number, $dp, $trim_zeros );
		}
	}


}