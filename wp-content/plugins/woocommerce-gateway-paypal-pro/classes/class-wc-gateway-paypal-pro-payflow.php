<?php

/**
 * WC_Gateway_PayPal_Pro_PayFlow class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_PayPal_Pro_PayFlow extends WC_Payment_Gateway {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {

		$this->id					= 'paypal_pro_payflow';
		$this->method_title 		= __( 'PayPal Pro PayFlow', 'woocommerce-gateway-paypal-pro' );
		$this->method_description 	= __( 'PayPal Pro PayFlow Edition works by adding credit card fields on the checkout and then sending the details to PayPal for verification.', 'woocommerce-gateway-paypal-pro' );
		$this->icon 				= WP_PLUGIN_URL . "/" . plugin_basename( dirname( dirname( __FILE__ ) ) ) . '/assets/images/cards.png';
		$this->has_fields 			= true;
		$this->liveurl				= 'https://payflowpro.paypal.com';
		$this->testurl				= 'https://pilot-payflowpro.paypal.com';
		$this->allowed_currencies   = apply_filters( 'woocommerce_paypal_pro_allowed_currencies', array( 'USD', 'EUR', 'GBP', 'CAD', 'JPY', 'AUD' ) );

		// Load the form fields
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Get setting values
		$this->title           = $this->settings['title'];
		$this->description     = $this->settings['description'];
		$this->enabled         = $this->settings['enabled'];

		$this->paypal_vendor   = $this->settings['paypal_vendor'];
		$this->paypal_partner  = ! empty( $this->settings['paypal_partner'] ) ? $this->settings['paypal_partner'] : 'PayPal';
		$this->paypal_password = $this->settings['paypal_password'];
		$this->paypal_user     = ! empty( $this->settings['paypal_user'] ) ? $this->settings['paypal_user'] : $this->paypal_vendor;

		$this->testmode        = $this->settings['testmode'];

		/* 1.6.6 */
		add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );

		/* 2.0.0 */
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields() {

    	$this->form_fields = array(
			'enabled'         => array(
							'title'       => __( 'Enable/Disable', 'woocommerce-gateway-paypal-pro' ),
							'label'       => __( 'Enable PayPal Pro Payflow Edition', 'woocommerce-gateway-paypal-pro' ),
							'type'        => 'checkbox',
							'description' => '',
							'default'     => 'no'
						),
			'title'           => array(
							'title'       => __( 'Title', 'woocommerce-gateway-paypal-pro' ),
							'type'        => 'text',
							'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-paypal-pro' ),
							'default'     => __( 'Credit card (PayPal)', 'woocommerce-gateway-paypal-pro' ),
							'desc_tip'    => true
						),
			'description'     => array(
							'title'       => __( 'Description', 'woocommerce-gateway-paypal-pro' ),
							'type'        => 'textarea',
							'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-gateway-paypal-pro' ),
							'default'     => __( 'Pay with your credit card.', 'woocommerce-gateway-paypal-pro' ),
							'desc_tip'    => true
						),
			'testmode'        => array(
							'title'       => __( 'Test Mode', 'woocommerce-gateway-paypal-pro' ),
							'label'       => __( 'Enable PayPal Sandbox/Test Mode', 'woocommerce-gateway-paypal-pro' ),
							'type'        => 'checkbox',
							'description' => __( 'Place the payment gateway in development mode.', 'woocommerce-gateway-paypal-pro' ),
							'default'     => 'no',
							'desc_tip'    => true
						),
			'paypal_vendor'   => array(
							'title'       => __( 'PayPal Vendor', 'woocommerce-gateway-paypal-pro' ),
							'type'        => 'text',
							'description' => __( 'Your merchant login ID that you created when you registered for the account.', 'woocommerce-gateway-paypal-pro' ),
							'default'     => '',
							'desc_tip'    => true
						),
			'paypal_password' => array(
							'title'       => __( 'PayPal Password', 'woocommerce-gateway-paypal-pro' ),
							'type'        => 'password',
							'description' => __( 'The password that you defined while registering for the account.', 'woocommerce-gateway-paypal-pro' ),
							'default'     => '',
							'desc_tip'    => true
						),
			'paypal_user'     => array(
							'title'       => __( 'PayPal User', 'woocommerce-gateway-paypal-pro' ),
							'type'        => 'text',
							'description' => __( 'If you set up one or more additional users on the account, this value is the ID
of the user authorized to process transactions. Otherwise, leave this field blank.', 'woocommerce-gateway-paypal-pro' ),
							'default'     => '',
							'desc_tip'    => true
						),
			'paypal_partner'  => array(
							'title'       => __( 'PayPal Partner', 'woocommerce-gateway-paypal-pro' ),
							'type'        => 'text',
							'description' => __( 'The ID provided to you by the authorized PayPal Reseller who registered you
for the Payflow SDK. If you purchased your account directly from PayPal, use PayPal or leave blank.', 'woocommerce-gateway-paypal-pro' ),
							'default'     => 'PayPal',
							'desc_tip'    => true
						),
			);
    }

	/**
     * Check if this gateway is enabled and available in the user's country
     *
     * This method no is used anywhere??? put above but need a fix below
     */
	function is_available() {
		global $woocommerce;

		if ( $this->enabled == "yes" ) {

			if ( ! is_ssl() && $this->testmode == "no" )
				return false;

			// Currency check
			if ( ! in_array( get_option('woocommerce_currency'), $this->allowed_currencies ) )
				return false;

			// Required fields check
			if ( ! $this->paypal_vendor || ! $this->paypal_password )
				return false;

			return true;
		}

		return false;
	}

	/**
     * Process the payment
     */
	function process_payment( $order_id ) {
		global $woocommerce;

		$order = new WC_Order( $order_id );

		$card_number    = isset( $_POST['paypal_pro_payflow-card-number'] ) ? woocommerce_clean( $_POST['paypal_pro_payflow-card-number'] ) : '';
		$card_cvc       = isset( $_POST['paypal_pro_payflow-card-cvc'] ) ? woocommerce_clean( $_POST['paypal_pro_payflow-card-cvc'] ) : '';
		$card_expiry    = isset( $_POST['paypal_pro_payflow-card-expiry'] ) ? woocommerce_clean( $_POST['paypal_pro_payflow-card-expiry'] ) : '';

		// Format values
		$card_number    = str_replace( array( ' ', '-' ), '', $card_number );
		$card_expiry    = array_map( 'trim', explode( '/', $card_expiry ) );
		$card_exp_month = str_pad( $card_expiry[0], 2, "0", STR_PAD_LEFT );
		$card_exp_year  = $card_expiry[1];

		if ( strlen( $card_exp_year ) == 4 )
			$card_exp_year = $card_exp_year - 2000;

		// Do payment with paypal
		return $this->do_payment( $order, $card_number, $card_exp_month . $card_exp_year, $card_cvc );
	}

	/**
	 * do_payment function.
	 *
	 * @access public
	 * @param mixed $order
	 * @param mixed $card_number
	 * @param mixed $card_exp
	 * @param mixed $card_cvc
	 * @param string $centinelPAResStatus (default: '')
	 * @param string $centinelEnrolled (default: '')
	 * @param string $centinelCavv (default: '')
	 * @param string $centinelEciFlag (default: '')
	 * @param string $centinelXid (default: '')
	 * @return void
	 */
	function do_payment( $order, $card_number, $card_exp, $card_cvc, $centinelPAResStatus = '', $centinelEnrolled = '', $centinelCavv = '', $centinelEciFlag = '', $centinelXid = '' ) {

		global $woocommerce;

		// Send request to paypal
		try {
			$url = $this->testmode == 'yes' ? $this->testurl : $this->liveurl;

			$post_data = array();

			$post_data['USER']     = $this->paypal_user;
			$post_data['VENDOR']   = $this->paypal_vendor;
			$post_data['PARTNER']  = $this->paypal_partner;
			$post_data['PWD']      = trim( $this->paypal_password );
			$post_data['TENDER']   = 'C'; // Credit card
			$post_data['TRXTYPE']  = 'S'; // Sale
			$post_data['ACCT']     = $card_number; // Credit Card
			$post_data['EXPDATE']  = $card_exp; //MMYY
			$post_data['AMT']      = $order->get_total(); // Order total
			$post_data['CURRENCY'] = get_option('woocommerce_currency'); // Currency code
			$post_data['CUSTIP']   = $this->get_user_ip(); // User IP Address
			$post_data['CVV2']     = $card_cvc; // CVV code
			$post_data['EMAIL']    = $order->billing_email;
			$post_data['INVNUM']   = $order->get_order_number();

			/* Send Item details */
			$item_loop = 0;

			if ( sizeof( $order->get_items() ) > 0 ) {

				$ITEMAMT = 0;

				foreach ( $order->get_items() as $item ) {
					$_product = $order->get_product_from_item( $item );
					if ( $item['qty'] ) {
						$post_data[ 'L_NAME' . $item_loop ] = $item['name'];
						$post_data[ 'L_COST' . $item_loop ] = $order->get_item_total( $item, true );
						$post_data[ 'L_QTY' . $item_loop ]  = $item['qty'];

						if ( $_product->get_sku() )
							$post_data[ 'L_SKU' . $item_loop ] = $_product->get_sku();

						$ITEMAMT += $order->get_item_total( $item, true ) * $item['qty'];

						$item_loop++;
					}
				}

				// Shipping
				if ( ( $order->get_shipping() + $order->get_shipping_tax() ) > 0 ) {
					$post_data[ 'L_NAME' . $item_loop ] = 'Shipping';
					$post_data[ 'L_DESC' . $item_loop ] = 'Shipping and shipping taxes';
					$post_data[ 'L_COST' . $item_loop ] = $order->get_shipping() + $order->get_shipping_tax();
					$post_data[ 'L_QTY' . $item_loop ]  = 1;

					$ITEMAMT += $order->get_shipping() + $order->get_shipping_tax();

					$item_loop++;
				}

				// Discount
				if ( $order->get_order_discount() > 0 ) {
					$post_data[ 'L_NAME' . $item_loop ] = 'Order Discount';
					$post_data[ 'L_DESC' . $item_loop ] = 'Discounts after tax';
					$post_data[ 'L_COST' . $item_loop ] = '-' . $order->get_order_discount();
					$post_data[ 'L_QTY' . $item_loop ]  = 1;

					$item_loop++;
				}

				$ITEMAMT = round( $ITEMAMT, 2 );

				// Fix rounding
				if ( absint( $order->get_total() * 100 ) !== absint( $ITEMAMT * 100 ) ) {
					$post_data[ 'L_NAME' . $item_loop ] = 'Rounding amendment';
					$post_data[ 'L_DESC' . $item_loop ] = 'Correction if rounding is off (this can happen with tax inclusive prices)';
					$post_data[ 'L_COST' . $item_loop ] = ( absint( $order->get_total() * 100 ) - absint( $ITEMAMT * 100 ) ) / 100;
					$post_data[ 'L_QTY' . $item_loop ]  = 1;
				}

				$post_data[ 'ITEMAMT' ] = $order->get_total();
			}

			$post_data['ORDERDESC']      = 'Order ' . $order->get_order_number() . ' on ' . get_bloginfo( 'name' );
			$post_data['FIRSTNAME']      = $order->billing_first_name;
			$post_data['LASTNAME']       = $order->billing_last_name;
			$post_data['STREET']         = $order->billing_address_1 . ' ' . $order->billing_address_2;
			$post_data['CITY']           = $order->billing_city;
			$post_data['STATE']          = $order->billing_state;
			$post_data['COUNTRY']        = $order->billing_country;
			$post_data['ZIP']            = $order->billing_postcode;

			if ( $order->shipping_address_1 ) {
				$post_data['SHIPTOSTREET']  = $order->shipping_address_1 . ' ' . $order->shipping_address_2;
				$post_data['SHIPTOCITY']    = $order->shipping_city;
				$post_data['SHIPTOSTATE']   = $order->shipping_state;
				$post_data['SHIPTOCOUNTRY'] = $order->shipping_country;
				$post_data['SHIPTOZIP']     = $order->shipping_postcode;
			}

			$post_data['BUTTONSOURCE'] = 'WooThemes_Cart';

			$response = wp_remote_post( $url, array(
   				'method'		=> 'POST',
    			'body' 			=> urldecode( http_build_query( apply_filters( 'woocommerce-gateway-paypal-pro_payflow_request', $post_data, $order ), null, '&' ) ),
    			'timeout' 		=> 70,
    			'sslverify' 	=> false,
    			'user-agent' 	=> 'WooCommerce',
    			'httpversion'   => '1.1'
			));

			if ( is_wp_error( $response ) ) {
				throw new Exception( __( 'There was a problem connecting to the payment gateway.', 'woocommerce-gateway-paypal-pro' ) );
			}

			if ( empty( $response['body'] ) ) {
				throw new Exception( __( 'Empty Paypal response.', 'woocommerce-gateway-paypal-pro' ) );
			}

			parse_str( $response['body'], $parsed_response );

			if ( isset( $parsed_response['RESULT'] ) && in_array( $parsed_response['RESULT'], array( 0, 126, 127 ) ) ) {

				switch ( $parsed_response['RESULT'] ) {
					// Approved or screening service was down
					case 0 :
					case 127 :
						$order->add_order_note( sprintf( __( 'PayPal Pro payment completed (PNREF: %s)', 'woocommerce-gateway-paypal-pro' ), $parsed_response['PNREF'] ) );

						// Payment complete
						$order->payment_complete();
					break;
					// Under Review by Fraud Service
					case 126 :
						$order->add_order_note( $parsed_response['RESPMSG'] );
						$order->add_order_note( $parsed_response['PREFPSMSG'] );
						$order->update_status( 'on-hold', __( 'The payment was flagged by a fraud filter. Please check your PayPal Manager account to review and accept or deny the payment and then mark this order "processing" or "cancelled".', 'woocommerce-gateway-paypal-pro' ) );
					break;
				}

				// Remove cart
				$woocommerce->cart->empty_cart();

				if ( method_exists( $order, 'get_checkout_order_received_url' ) ) {
                	$redirect = $order->get_checkout_order_received_url();
                } else {
                	$redirect = add_query_arg( 'key', $order->order_key, add_query_arg( 'order', $order->id, get_permalink( get_option( 'woocommerce_thanks_page_id' ) ) ) );
                }

				// Return thank you page redirect
				return array(
					'result' 	=> 'success',
					'redirect'	=> $redirect
				);

			} else {

				// Payment failed :(
				$order->update_status( 'failed', __( 'PayPal Pro payment failed. Payment was rejected due to an error: ', 'woocommerce-gateway-paypal-pro' ) . '(' . $parsed_response['RESULT'] . ') ' . '"' . $parsed_response['RESPMSG'] . '"' );

				if ( function_exists( 'wc_add_notice' ) ) {
					wc_add_notice( __( 'Payment error:', 'woocommerce-gateway-paypal-pro' ) . ' ' . $parsed_response['RESPMSG'], 'error' );
				} else {
					$woocommerce->add_error( __( 'Payment error:', 'woocommerce-gateway-paypal-pro' ) . ' ' . $parsed_response['RESPMSG'] );
				}
				return;
			}

		} catch( Exception $e ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __('Connection error:', 'woocommerce-gateway-paypal-pro' ) . ': "' . $e->getMessage() . '"', 'error' );
			} else {
				$woocommerce->add_error( __('Connection error:', 'woocommerce-gateway-paypal-pro' ) . ': "' . $e->getMessage() . '"' );
			}
			return;
		}
	}

	/**
	 * Core credit card form which gateways can used if needed.
	 */
	public function paypal_credit_card_form() {
		wp_enqueue_script( 'wc-credit-card-form' );

		$fields = array(
			'card-number-field' => '<p class="form-row form-row-wide">
				<label for="' . $this->id . '-card-number">' . __( "Card Number", 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . $this->id . '-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="•••• •••• •••• ••••" name="' . $this->id . '-card-number" />
			</p>',
			'card-expiry-field' => '<p class="form-row form-row-first">
				<label for="' . $this->id . '-card-expiry">' . __( "Expiry (MM/YY)", 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . $this->id . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="MM / YY" name="' . $this->id . '-card-expiry" />
			</p>',
			'card-cvc-field' => '<p class="form-row form-row-last">
				<label for="' . $this->id . '-card-cvc">' . __( "Card Code", 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . $this->id . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="CVC" name="' . $this->id . '-card-cvc" />
			</p>'
		);
		?>
		<fieldset id="<?php echo $this->id; ?>-cc-form">
			<?php do_action( 'woocommerce_credit_card_form_before', $this->id ); ?>
			<?php echo $fields['card-number-field']; ?>
			<?php echo $fields['card-expiry-field']; ?>
			<?php echo $fields['card-cvc-field']; ?>
			<?php do_action( 'woocommerce_credit_card_form_before', $this->id ); ?>
			<div class="clear"></div>
		</fieldset>
		<?php
	}

	/**
     * Payment form on checkout page
     */
	public function payment_fields() {
		global $woocommerce;

		if ( $this->description ) 
			echo '<p>' . $this->description . ( $this->testmode == 'yes' ? ' ' . __('TEST MODE/SANDBOX ENABLED', 'woocommerce-gateway-paypal-pro') : '' ) . '</p>';

		if ( method_exists( $this, 'credit_card_form' ) )
			$this->credit_card_form();
		else
			$this->paypal_credit_card_form();
	}

	/**
     * Get user's IP address
     */
	function get_user_ip() {
		return ! empty( $_SERVER['HTTP_X_FORWARD_FOR'] ) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'];
	}
}