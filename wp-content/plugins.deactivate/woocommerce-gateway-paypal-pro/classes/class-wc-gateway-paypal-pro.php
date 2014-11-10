<?php

/**
 * WC_Gateway_PayPal_Pro class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_PayPal_Pro extends WC_Payment_Gateway {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		global $woocommerce;

		$this->id					= 'paypal_pro';
		$this->method_title 		= __( 'PayPal Pro', 'woocommerce-gateway-paypal-pro' );
		$this->method_description 	= __( 'PayPal Pro works by adding credit card fields on the checkout and then sending the details to PayPal for verification.', 'woocommerce-gateway-paypal-pro' );
		$this->icon 				= apply_filters('woocommerce_paypal_pro_icon', WP_PLUGIN_URL . "/" . plugin_basename( dirname( dirname( __FILE__ ) ) ) . '/assets/images/cards.png' );
		$this->has_fields 			= true;
		$this->liveurl				= 'https://api-3t.paypal.com/nvp';
		$this->testurl				= 'https://api-3t.sandbox.paypal.com/nvp';
		$this->liveurl_3ds			= 'https://paypal.cardinalcommerce.com/maps/txns.asp';
		$this->testurl_3ds			= 'https://centineltest.cardinalcommerce.com/maps/txns.asp';
		$this->avaiable_card_types 	= apply_filters( 'woocommerce_paypal_pro_avaiable_card_types', array(
			'GB' => array(
				'Visa' 			=> 'Visa',
				'MasterCard' 	=> 'MasterCard',
				'Maestro'		=> 'Maestro/Switch',
				'Solo'			=> 'Solo'
			),
			'US' => array(
				'Visa' 			=> 'Visa',
				'MasterCard' 	=> 'MasterCard',
				'Discover'		=> 'Discover',
				'AmEx'			=> 'American Express'
			),
			'CA' => array(
				'Visa' 			=> 'Visa',
				'MasterCard' 	=> 'MasterCard'
			),
			'AU' => array(
				'Visa' 			=> 'Visa',
				'MasterCard' 	=> 'MasterCard'
			),
			'JP' => array(
				'Visa' 			=> 'Visa',
				'MasterCard' 	=> 'MasterCard',
				'JCB' 			=> 'JCB'
			)
		) );
		$this->iso4217 = apply_filters( 'woocommerce_paypal_pro_iso_currencies', array(
			'AUD' => '036',
			'CAD' => '124',
			'CZK' => '203',
			'DKK' => '208',
			'EUR' => '978',
			'HUF' => '348',
			'JPY' => '392',
			'NOK' => '578',
			'NZD' => '554',
			'PLN' => '985',
			'GBP' => '826',
			'SGD' => '702',
			'SEK' => '752',
			'CHF' => '756',
			'USD' => '840'
		) );

		// Load the form fields
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Get setting values
		$this->title 			= $this->settings['title'];
		$this->description 		= $this->settings['description'];
		$this->enabled 			= $this->settings['enabled'];
		$this->api_username 	= $this->settings['api_username'];
		$this->api_password 	= $this->settings['api_password'];
		$this->api_signature 	= $this->settings['api_signature'];
		$this->testmode 		= $this->settings['testmode'];
		$this->enable_3dsecure 	= isset( $this->settings['enable_3dsecure'] ) && $this->settings['enable_3dsecure'] == 'yes' ? true : false;
		$this->liability_shift 	= isset( $this->settings['liability_shift'] ) && $this->settings['liability_shift'] == 'yes' ? true : false;
		$this->debug			= isset( $this->settings['debug'] ) && $this->settings['debug'] == 'yes' ? true : false;
		$this->send_items		= isset( $this->settings['send_items'] ) && $this->settings['send_items'] == 'yes' ? true : false;

		// 3DS
		if ( $this->enable_3dsecure ) {
			$this->centinel_pid		= $this->settings['centinel_pid'];
			$this->centinel_mid		= $this->settings['centinel_mid'];
			$this->centinel_pwd		= $this->settings['centinel_pwd'];

			if ( empty( $this->centinel_pid ) || empty( $this->centinel_mid ) || empty( $this->centinel_pwd ) )
				$this->enable_3dsecure = false;

			$this->centinel_url = $this->testmode == "no" ? $this->liveurl_3ds : $this->testurl_3ds;
		}

		// Maestro
		if ( ! $this->enable_3dsecure ) {
			unset( $this->avaiable_card_types['GB']['Maestro'] );
		}

		// Hooks
		add_action( 'woocommerce_api_wc_gateway_paypal_pro', array( $this, 'authorise_3dsecure') );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields() {

    	$this->form_fields = array(
			'enabled' => array(
							'title' => __( 'Enable/Disable', 'woocommerce-gateway-paypal-pro' ),
							'label' => __( 'Enable PayPal Pro', 'woocommerce-gateway-paypal-pro' ),
							'type' => 'checkbox',
							'description' => '',
							'default' => 'no'
						),
			'title' => array(
							'title' => __( 'Title', 'woocommerce-gateway-paypal-pro' ),
							'type' => 'text',
							'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-paypal-pro' ),
							'default' => __( 'Credit card (PayPal)', 'woocommerce-gateway-paypal-pro' ),
							'desc_tip'    => true
						),
			'description' => array(
							'title' => __( 'Description', 'woocommerce-gateway-paypal-pro' ),
							'type' => 'textarea',
							'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-gateway-paypal-pro' ),
							'default' => __( 'Pay with your credit card via PayPal Website Payments Pro.', 'woocommerce-gateway-paypal-pro' ),
							'desc_tip'    => true
						),
			'testmode' => array(
							'title' => __( 'Test Mode', 'woocommerce-gateway-paypal-pro' ),
							'label' => __( 'Enable PayPal Sandbox/Test Mode', 'woocommerce-gateway-paypal-pro' ),
							'type' => 'checkbox',
							'description' => __( 'Place the payment gateway in development mode.', 'woocommerce-gateway-paypal-pro' ),
							'default' => 'no',
							'desc_tip'    => true
						),
			'api_username' => array(
							'title' => __( 'API Username', 'woocommerce-gateway-paypal-pro' ),
							'type' => 'text',
							'description' => __( 'Get your API credentials from PayPal.', 'woocommerce-gateway-paypal-pro' ),
							'default' => '',
							'desc_tip'    => true
						),
			'api_password' => array(
							'title' => __( 'API Password', 'woocommerce-gateway-paypal-pro' ),
							'type' => 'text',
							'description' => __( 'Get your API credentials from PayPal.', 'woocommerce-gateway-paypal-pro' ),
							'default' => '',
							'desc_tip'    => true
						),
			'api_signature' => array(
							'title' => __( 'API Signature', 'woocommerce-gateway-paypal-pro' ),
							'type' => 'text',
							'description' => __( 'Get your API credentials from PayPal.', 'woocommerce-gateway-paypal-pro' ),
							'default' => '',
							'desc_tip'    => true
						),
			'enable_3dsecure' => array(
							'title' => __( '3DSecure', 'woocommerce-gateway-paypal-pro' ),
							'label' => __( 'Enable 3DSecure', 'woocommerce-gateway-paypal-pro' ),
							'type' => 'checkbox',
							'description' => __( 'Allows UK merchants to pass 3-D Secure authentication data to PayPal for debit and credit cards. Updating your site with 3-D Secure enables your participation in the Verified by Visa and MasterCard SecureCode programs. (Required to accept Maestro)', 'woocommerce-gateway-paypal-pro' ),
							'default' => 'no',
							'desc_tip'    => true
						),
			'centinel_pid' => array(
							'title' => __( 'Centinel PID', 'woocommerce-gateway-paypal-pro' ),
							'type' => 'text',
							'description' => __( 'If enabling 3D Secure, enter your Cardinal Centinel Processor ID.', 'woocommerce-gateway-paypal-pro' ),
							'default' => '',
							'desc_tip'    => true
						),
			'centinel_mid' => array(
							'title' => __( 'Centinel MID', 'woocommerce-gateway-paypal-pro' ),
							'type' => 'text',
							'description' => __( 'If enabling 3D Secure, enter your Cardinal Centinel Merchant ID.', 'woocommerce-gateway-paypal-pro' ),
							'default' => '',
							'desc_tip'    => true
						),
			'centinel_pwd' => array(
							'title' => __( 'Transaction Password', 'woocommerce-gateway-paypal-pro' ),
							'type' => 'password',
							'description' => __( 'If enabling 3D Secure, enter your Cardinal Centinel Transaction Password.', 'woocommerce-gateway-paypal-pro' ),
							'default' => '',
							'desc_tip'    => true
						),
			'liability_shift' => array(
							'title' => __( 'Liability Shift', 'woocommerce-gateway-paypal-pro' ),
							'label' => __( 'Require liability shift', 'woocommerce-gateway-paypal-pro' ),
							'type' => 'checkbox',
							'description' => __( 'Only accept payments when liability shift has occurred.', 'woocommerce-gateway-paypal-pro' ),
							'default' => 'no',
							'desc_tip'    => true
						),
			'send_items' => array(
							'title' => __( 'Send Item Details', 'woocommerce-gateway-paypal-pro' ),
							'label' => __( 'Send Line Items to PayPal', 'woocommerce-gateway-paypal-pro' ),
							'type' => 'checkbox',
							'description' => __( 'Sends line items to PayPal. If you experience rounding errors this can be disabled.', 'woocommerce-gateway-paypal-pro' ),
							'default' => 'no',
							'desc_tip'    => true
						),
			'debug' => array(
							'title' => __( 'Debug Log', 'woocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Enable logging', 'woocommerce' ),
							'default' => 'no',
							'desc_tip'    => true,
							'description' => __( 'Log PayPal events inside <code>woocommerce/logs/paypal-pro.txt</code>' ),
						)
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

			if ( ! is_ssl() && $this->testmode == "no" ) {
				return false;
			}

			// Currency check
			if ( ! in_array( get_option( 'woocommerce_currency' ), apply_filters( 'woocommerce_paypal_pro_allowed_currencies', array( 'AUD', 'CAD', 'CZK', 'DKK', 'EUR', 'HUF', 'JPY', 'NOK', 'NZD', 'PLN', 'GBP', 'SGD', 'SEK', 'CHF', 'USD' ) ) ) ) {
				return false;
			}

			// Required fields check
			if ( ! $this->api_username || ! $this->api_password || ! $this->api_signature ) {
				return false;
			}

			return isset( $this->avaiable_card_types[ $woocommerce->countries->get_base_country() ] );

		}

		return false;
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
     * Validate the payment form
     */
	function validate_fields() {
		global $woocommerce;

		try {

			$card_number    = isset( $_POST['paypal_pro-card-number'] ) ? woocommerce_clean( $_POST['paypal_pro-card-number'] ) : '';
			$card_cvc       = isset( $_POST['paypal_pro-card-cvc'] ) ? woocommerce_clean( $_POST['paypal_pro-card-cvc'] ) : '';
			$card_expiry    = isset( $_POST['paypal_pro-card-expiry'] ) ? woocommerce_clean( $_POST['paypal_pro-card-expiry'] ) : '';

			// Format values
			$card_number    = str_replace( array( ' ', '-' ), '', $card_number );
			$card_expiry    = array_map( 'trim', explode( '/', $card_expiry ) );
			$card_exp_month = str_pad( $card_expiry[0], 2, "0", STR_PAD_LEFT );
			$card_exp_year  = $card_expiry[1];

			if ( strlen( $card_exp_year ) == 2 )
				$card_exp_year += 2000;

			// Validate values
			if ( ! ctype_digit( $card_cvc ) ) {
				throw new Exception( __( 'Card security code is invalid (only digits are allowed)', 'woocommerce-gateway-paypal-pro' ) );
			}

			if (
				! ctype_digit( $card_exp_month ) ||
				! ctype_digit( $card_exp_year ) ||
				$card_exp_month > 12 ||
				$card_exp_month < 1 ||
				$card_exp_year < date('y')
			) {
				throw new Exception( __( 'Card expiration date is invalid', 'woocommerce-gateway-paypal-pro' ) );
			}

			if ( empty( $card_number ) || ! ctype_digit( $card_number ) ) {
				throw new Exception( __( 'Card number is invalid', 'woocommerce-gateway-paypal-pro' ) );
			}

			return true;

		} catch( Exception $e ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( $e->getMessage(), 'error' );
			} else {
				$woocommerce->add_error( $e->getMessage() );
			}
			return false;
		}
	}

	/**
     * Process the payment
     */
	function process_payment( $order_id ) {
		global $woocommerce;

		$order = new WC_Order( $order_id );

		$this->log( 'Processing order #' . $order_id );

		$card_number    = isset( $_POST['paypal_pro-card-number'] ) ? woocommerce_clean( $_POST['paypal_pro-card-number'] ) : '';
		$card_cvc       = isset( $_POST['paypal_pro-card-cvc'] ) ? woocommerce_clean( $_POST['paypal_pro-card-cvc'] ) : '';
		$card_expiry    = isset( $_POST['paypal_pro-card-expiry'] ) ? woocommerce_clean( $_POST['paypal_pro-card-expiry'] ) : '';

		// Format values
		$card_number    = str_replace( array( ' ', '-' ), '', $card_number );
		$card_expiry    = array_map( 'trim', explode( '/', $card_expiry ) );
		$card_exp_month = str_pad( $card_expiry[0], 2, "0", STR_PAD_LEFT );
		$card_exp_year  = $card_expiry[1];

		if ( strlen( $card_exp_year ) == 2 ) {
			$card_exp_year += 2000;
		}

		/**
	     * 3D Secure Handling
	     */
		if ( $this->enable_3dsecure ) {

			if ( ! class_exists( 'CentinelClient' ) ) {
				include_once( 'lib/CentinelClient.php' );
			}

			$this->clear_centinel_session();

			$centinelClient = new CentinelClient;

			$centinelClient->add( "MsgType", "cmpi_lookup" );
			$centinelClient->add( "Version", "1.7" );
			$centinelClient->add( "ProcessorId", $this->centinel_pid );
			$centinelClient->add( "MerchantId", $this->centinel_mid );
			$centinelClient->add( "TransactionPwd", $this->centinel_pwd );
			$centinelClient->add( "UserAgent", $_SERVER["HTTP_USER_AGENT"] );
			$centinelClient->add( "BrowserHeader", $_SERVER["HTTP_ACCEPT"] );
			$centinelClient->add( "TransactionType", 'C' );

		    // Standard cmpi_lookup fields
		    $centinelClient->add( 'OrderNumber', $order_id );
		    $centinelClient->add( 'Amount', $order->order_total * 100 );
		    $centinelClient->add( 'CurrencyCode', $this->iso4217[ get_option('woocommerce_currency') ] );
		    $centinelClient->add( 'TransactionMode', 'S' );

			// Items
			$item_loop = 0;

			if ( sizeof( $order->get_items() ) > 0 ) {
				foreach ( $order->get_items() as $item ) {
					$item_loop++;
					$centinelClient->add( 'Item_Name_' . $item_loop, $item['name'] );
					$centinelClient->add( 'Item_Price_' . $item_loop, number_format( $order->get_item_total( $item, true, true ) * 100 ) );
					$centinelClient->add( 'Item_Quantity_' . $item_loop, $item['qty'] );
					$centinelClient->add( 'Item_Desc_' . $item_loop, $item['name'] );
				}
			}

		    // Payer Authentication specific fields
		    $centinelClient->add( 'CardNumber', $card_number );
		    $centinelClient->add( 'CardExpMonth', $card_exp_month );
		    $centinelClient->add( 'CardExpYear', $card_exp_year );

		    // Send request
		    $centinelClient->sendHttp( $this->centinel_url, "5000", "15000" );

			$this->log( 'Centinal client request: ' . print_r( $centinelClient->request, true ) );
			$this->log( 'Centinal client response: ' . print_r( $centinelClient->response, true ) );

		    // Save response in session
		    $woocommerce->session->set( "paypal_pro_orderid", $order_id ); // Save lookup response in session
		    $woocommerce->session->set( "Centinel_cmpiMessageResp", $centinelClient->response );
			$woocommerce->session->set( "Centinel_Enrolled", $centinelClient->getValue("Enrolled") );
		    $woocommerce->session->set( "Centinel_TransactionId", $centinelClient->getValue("TransactionId") );
		    $woocommerce->session->set( "Centinel_ACSUrl", $centinelClient->getValue("ACSUrl") );
		    $woocommerce->session->set( "Centinel_Payload", $centinelClient->getValue("Payload") );
		    $woocommerce->session->set( "Centinel_ErrorNo", $centinelClient->getValue("ErrorNo") );
		    $woocommerce->session->set( "Centinel_ErrorDesc", $centinelClient->getValue("ErrorDesc") );
			$woocommerce->session->set( "Centinel_EciFlag", $centinelClient->getValue("EciFlag") );
		    $woocommerce->session->set( "Centinel_TransactionType", "C" );
		    $woocommerce->session->set( 'Centinel_TermUrl', $woocommerce->api_request_url( 'WC_Gateway_PayPal_Pro' ) );
		    $woocommerce->session->set( 'Centinel_OrderId', $centinelClient->getValue("OrderId") );

		    $this->log( '3dsecure Centinel_Enrolled: ' . $woocommerce->session->get( 'Centinel_Enrolled' ) );

		    /******************************************************************************/
		    /*                                                                            */
		    /*                          Result Processing Logic                           */
		    /*                                                                            */
		    /******************************************************************************/

    		if ( $woocommerce->session->get( 'Centinel_ErrorNo' ) == 0 ) {

    			if ( $woocommerce->session->get( 'Centinel_Enrolled' ) == 'Y' ) {

    				$this->log( 'Doing 3dsecure payment authorization' );
    				$this->log( 'ASCUrl: ' . $woocommerce->session->get("Centinel_ACSUrl") );
    				$this->log( 'PaReq: ' . $woocommerce->session->get("Centinel_Payload") );
    				$this->log( 'TermUrl: ' . $woocommerce->session->get("Centinel_TermUrl") );

			        @ob_clean();
					?>
					<html>
						<head>
							<title>3DSecure Payment Authorisation</title>
						</head>
						<body>
							<form name="frmLaunchACS" id="3ds_submit_form" method="POST" action="<?php echo $woocommerce->session->get("Centinel_ACSUrl"); ?>">
						        <input type="hidden" name="PaReq" value="<?php echo $woocommerce->session->get("Centinel_Payload"); ?>">
						        <input type="hidden" name="TermUrl" value="<?php echo $woocommerce->session->get('Centinel_TermUrl'); ?>">
						        <input type="hidden" name="MD" value="<?php echo urlencode( json_encode( array(
						        	'card' 				=> $card_number,
						        	'csc'				=> $card_cvc,
						        	'card_exp_month' 	=> $card_exp_month,
						        	'card_exp_year' 	=> $card_exp_year
						        ) ) ); ?>">
						        <noscript>
						        	<div class="woocommerce_message"><?php _e('Processing your Payer Authentication Transaction', 'woocommerce-gateway-paypal-pro'); ?> - <?php _e('Please click Submit to continue the processing of your transaction.', 'woocommerce-gateway-paypal-pro'); ?>  <input type="submit" class="button" id="3ds_submit" value="Submit" /></div>
						        </noscript>
						    </form>
						    <script>
						    	document.frmLaunchACS.submit();
						    </script>
						</body>
					</html>
					<?php
					exit;

    			} elseif ( $this->liability_shift && $woocommerce->session->get('Centinel_Enrolled') != 'N' ) {

    				if ( function_exists( 'wc_add_notice' ) ) {
						wc_add_notice( __('Authentication unavailable. Please try a different payment method or card.','woocommerce-gateway-paypal-pro'), 'error' );
					} else {
						$woocommerce->add_error( __('Authentication unavailable. Please try a different payment method or card.','woocommerce-gateway-paypal-pro') );
					}
					return;

				} else {

    				// Customer not-enrolled, so just carry on with PayPal process
    				return $this->do_payment( $order, $card_number, '', $card_exp_month, $card_exp_year, $card_cvc, '', $woocommerce->session->get('Centinel_Enrolled'), '', $woocommerce->session->get("Centinel_EciFlag"), '' );

    			}

    		} else {
    			if ( function_exists( 'wc_add_notice' ) ) {
					wc_add_notice( __('Error in 3D secure authentication: ', 'woocommerce-gateway-paypal-pro') . $woocommerce->session->get('Centinel_ErrorNo'), 'error' );
				} else {
					$woocommerce->add_error( __('Error in 3D secure authentication: ', 'woocommerce-gateway-paypal-pro') . $woocommerce->session->get('Centinel_ErrorNo') );
				}
    			return;
			}

		}

		// Do payment with paypal
		return $this->do_payment( $order, $card_number, '', $card_exp_month, $card_exp_year, $card_cvc );
	}

	function authorise_3dsecure() {
		global $woocommerce;

		if ( ! class_exists( 'CentinelClient' ) ) {
			include_once( 'lib/CentinelClient.php' );
		}

		$pares         = ! empty( $_POST['PaRes'] ) ? $_POST['PaRes'] : '';
		$merchant_data = ! empty( $_POST['MD'] ) ? (array) json_decode( urldecode( $_POST['MD'] ) ) : '';
		$order_id      = $woocommerce->session->get( "paypal_pro_orderid" );
		$order         = new WC_Order( $order_id );

		$this->log( 'authorise_3dsecure() for order ' . absint( $order_id ) );

	    /******************************************************************************/
	    /*                                                                            */
	    /*    If the PaRes is Not Empty then process the cmpi_authenticate message    */
	    /*                                                                            */
	    /******************************************************************************/

	    if (strcasecmp('', $pares )!= 0 && $pares != null) {

	        $centinelClient = new CentinelClient;

	        $centinelClient->add( 'MsgType', 'cmpi_authenticate' );
	        $centinelClient->add( "Version", "1.7" );
			$centinelClient->add( "ProcessorId", $this->centinel_pid );
			$centinelClient->add( "MerchantId", $this->centinel_mid );
			$centinelClient->add( "TransactionPwd", $this->centinel_pwd );
			$centinelClient->add( "TransactionType", 'C' );
	        $centinelClient->add( 'OrderId', $woocommerce->session->get( 'Centinel_OrderId' ) );
	        $centinelClient->add( 'TransactionId', $woocommerce->session->get( 'Centinel_TransactionId' ) );
	        $centinelClient->add( 'PAResPayload', $pares );
			$centinelClient->sendHttp( $this->centinel_url, "5000", "15000" );

			$this->log( 'Centinal transaction ID ' . $woocommerce->session->get('Centinel_TransactionId') );
			$this->log( 'Centinal client request: ' . print_r( $centinelClient->request, true ) );
			$this->log( 'Centinal client response: ' . print_r( $centinelClient->response, true ) );

			$woocommerce->session->set( "Centinel_cmpiMessageResp", $centinelClient->response ); // Save authenticate response in session
			$woocommerce->session->set( "Centinel_PAResStatus", $centinelClient->getValue("PAResStatus") );
			$woocommerce->session->set( "Centinel_SignatureVerification", $centinelClient->getValue("SignatureVerification") );
			$woocommerce->session->set( "Centinel_ErrorNo", $centinelClient->getValue("ErrorNo") );
			$woocommerce->session->set( "Centinel_ErrorDesc", $centinelClient->getValue("ErrorDesc") );
			$woocommerce->session->set( "Centinel_EciFlag", $centinelClient->getValue("EciFlag") );
			$woocommerce->session->set( "Centinel_Cavv", $centinelClient->getValue("Cavv") );
			$woocommerce->session->set( "Centinel_Xid", $centinelClient->getValue("Xid") );
	    } else {
	    	$woocommerce->session->set( "Centinel_ErrorNo", "0" );
			$woocommerce->session->set( "Centinel_ErrorDesc", "NO PARES RETURNED" );
	    }

	    /******************************************************************************/
	    /*                                                                            */
	    /*                  Determine if the transaction resulted in                  */
	    /*                  an error.                                                 */
	    /*                                                                            */
	    /******************************************************************************/

		$redirect_url = $this->get_return_url( $order );

		try {

			$pa_res_status    = $woocommerce->session->get( "Centinel_PAResStatus" );
			$eci_flag         = $woocommerce->session->get( "Centinel_EciFlag" );
			$error_no         = $woocommerce->session->get( 'Centinel_ErrorNo' );
			$error_desc       = $woocommerce->session->get( "Centinel_ErrorDesc" );
			$cavv             = $woocommerce->session->get( "Centinel_Cavv" );
			$xid              = $woocommerce->session->get( "Centinel_Xid" );
			$sig_verification = $woocommerce->session->get( "Centinel_SignatureVerification" );

			$this->log( '3dsecure pa_res_status: ' . $pa_res_status );

			if ( $this->liability_shift ) {
				if ( $eci_flag == '07' || $eci_flag == '01' ) {
					$order->update_status( 'failed', __('3D Secure error: No liability shift', 'woocommerce-gateway-paypal-pro' ) );
					throw new Exception( __( 'Authentication unavailable.  Please try a different payment method or card.', 'woocommerce-gateway-paypal-pro' ) );
				}
			}

			if ( $error_no == "0" ) {

				if ( ( $pa_res_status == "Y" || $pa_res_status == "A" || $pa_res_status == "U") && $sig_verification == "Y" ) {

					// If we are here we can process the card
					$this->do_payment( $order, $merchant_data['card'], $merchant_data['type'], $merchant_data['card_exp_month'], $merchant_data['card_exp_year'], $merchant_data['csc'], $pa_res_status, "Y", $cavv, $eci_flag, $xid );

					$this->clear_centinel_session();

				} else {
					$order->update_status( 'failed', sprintf(__('3D Secure error: %s', 'woocommerce-gateway-paypal-pro' ), $error_desc ) );
					throw new Exception( __( 'Payer Authentication failed.  Please try a different payment method.','woocommerce-gateway-paypal-pro' ) );
				}

			} else {
				$order->update_status( 'failed', sprintf(__('3D Secure error: %s', 'woocommerce-gateway-paypal-pro' ), $error_desc ) );
				throw new Exception( __( 'Error in 3D secure authentication: ', 'woocommerce-gateway-paypal-pro' ) . $error_desc );
			}

		} catch( Exception $e ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( $e->getMessage(), 'error' );
			} else {
				$woocommerce->add_error( $e->getMessage() );
			}
		}

		wp_redirect( $redirect_url );
		exit;
	}

	/**
	 * do_payment function.
	 *
	 * @access public
	 * @param mixed $order
	 * @param mixed $card_number
	 * @param mixed $card_type
	 * @param mixed $card_exp_month
	 * @param mixed $card_exp_year
	 * @param mixed $card_cvc
	 * @param string $centinelPAResStatus (default: '')
	 * @param string $centinelEnrolled (default: '')
	 * @param string $centinelCavv (default: '')
	 * @param string $centinelEciFlag (default: '')
	 * @param string $centinelXid (default: '')
	 * @return void
	 */
	function do_payment( $order, $card_number, $card_type, $card_exp_month, $card_exp_year, $card_cvc, $centinelPAResStatus = '', $centinelEnrolled = '', $centinelCavv = '', $centinelEciFlag = '', $centinelXid = '' ) {

		global $woocommerce;

		$card_exp = $card_exp_month . $card_exp_year;

		// Send request to paypal
		try {
			$url = ($this->testmode == 'yes') ? $this->testurl : $this->liveurl;

			$post_data = array(
				'VERSION'           => '59.0',
				'SIGNATURE'         => $this->api_signature,
				'USER'              => $this->api_username,
				'PWD'               => $this->api_password,
				'METHOD'            => 'DoDirectPayment',
				'PAYMENTACTION'     => 'Sale',
				'IPADDRESS'         => $this->get_user_ip(),
				'AMT'               => $order->get_total(),
				'CURRENCYCODE'      => get_option('woocommerce_currency'),
				'CREDITCARDTYPE'    => $card_type,
				'ACCT'              => $card_number,
				'EXPDATE'           => $card_exp,
				'CVV2'              => $card_cvc,
				'EMAIL'             => $order->billing_email,
				'FIRSTNAME'         => $order->billing_first_name,
				'LASTNAME'          => $order->billing_last_name,
				'STREET'            => $order->billing_address_1 . ' ' . $order->billing_address_2,
				'CITY'              => $order->billing_city,
				'STATE'             => $order->billing_state,
				'ZIP'               => $order->billing_postcode,
				'COUNTRYCODE'       => $order->billing_country,
				'SHIPTONAME'        => $order->shipping_first_name . ' ' . $order->shipping_last_name,
				'SHIPTOSTREET'      => $order->shipping_address_1,
				'SHIPTOSTREET2'     => $order->shipping_address_2,
				'SHIPTOCITY'        => $order->shipping_city,
				'SHIPTOSTATE'       => $order->shipping_state,
				'SHIPTOCOUNTRYCODE' => $order->shipping_country,
				'SHIPTOZIP'         => $order->shipping_postcode,
				'BUTTONSOURCE'      => 'WooThemes_Cart'
			);

			/* Send Item details - thanks Harold Coronado */
			if ( $this->send_items ) {

				/* Send Item details */
				$item_loop = 0;

				if ( sizeof( $order->get_items() ) > 0 ) {

					$ITEMAMT = 0;

					foreach ( $order->get_items() as $item ) {
						$_product = $order->get_product_from_item( $item );
						if ( $item['qty'] ) {
							$post_data[ 'L_NUMBER' . $item_loop ] = $item_loop;
							$post_data[ 'L_NAME' . $item_loop ]   = $item['name'];
							$post_data[ 'L_AMT' . $item_loop ]    = $order->get_item_total( $item, true );
							$post_data[ 'L_QTY' . $item_loop ]    = $item['qty'];

							$ITEMAMT += $order->get_item_total( $item, true ) * $item['qty'];

							$item_loop++;
						}
					}

					// Shipping
					if ( ( $order->get_shipping() + $order->get_shipping_tax() ) > 0 ) {
						$post_data[ 'L_NUMBER' . $item_loop ] = $item_loop;
						$post_data[ 'L_NAME' . $item_loop ]   = 'Shipping';
						$post_data[ 'L_AMT' . $item_loop ]    = round( $order->get_shipping() + $order->get_shipping_tax(), 2 );
						$post_data[ 'L_QTY' . $item_loop ]    = 1;

						$ITEMAMT += round( $order->get_shipping() + $order->get_shipping_tax(), 2 );

						$item_loop++;
					}

					// Discount
					if ( $order->get_order_discount() > 0 ) {
						$post_data[ 'L_NUMBER' . $item_loop ] = $item_loop;
						$post_data[ 'L_NAME' . $item_loop ]   = 'Order Discount';
						$post_data[ 'L_AMT' . $item_loop ]    = '-' . $order->get_order_discount();
						$post_data[ 'L_QTY' . $item_loop ]    = 1;

						$item_loop++;
					}

					$ITEMAMT = round( $ITEMAMT, 2 );

					// Fix rounding
					if ( absint( $order->get_total() * 100 ) !== absint( $ITEMAMT * 100 ) ) {
						$post_data[ 'L_NUMBER' . $item_loop ] = $item_loop;
						$post_data[ 'L_NAME' . $item_loop ]   = 'Rounding amendment';
						$post_data[ 'L_AMT' . $item_loop ]    = ( absint( $order->get_total() * 100 ) - absint( $ITEMAMT * 100 ) ) / 100;
						$post_data[ 'L_QTY' . $item_loop ]    = 1;
					}

					$post_data[ 'ITEMAMT' ] = $order->get_total();
				}
			}

			if ( $this->debug ) {
				$log = $post_data;
				$log['ACCT'] = '****';
				$log['CVV2'] = '****';
				$this->log( 'Do payment request ' . print_r( $log, true ) );
			}

			/* 3D Secure */
			if ( $this->enable_3dsecure ) {
				$post_data['AUTHSTATUS3DS'] = $centinelPAResStatus;
				$post_data['MPIVENDOR3DS'] 	= $centinelEnrolled;
				$post_data['CAVV'] 			= $centinelCavv;
				$post_data['ECI3DS'] 		= $centinelEciFlag;
				$post_data['XID'] 			= $centinelXid;
			}

			$response = wp_remote_post( $url, array(
   				'method'		=> 'POST',
    			'body' 			=> apply_filters( 'woocommerce-gateway-paypal-pro_request', $post_data, $order ),
    			'timeout' 		=> 70,
    			'sslverify' 	=> false,
    			'user-agent' 	=> 'WooCommerce',
    			'httpversion'   => '1.1'
			));

			if ( is_wp_error( $response ) ) {
				$this->log( 'Error ' . print_r( $response->get_error_message(), true ) );

				throw new Exception( __( 'There was a problem connecting to the payment gateway.', 'woocommerce-gateway-paypal-pro' ) );
			}

			$this->log( 'Response ' . print_r( $response['body'], true ) );

			if ( empty($response['body']) )
				throw new Exception(__('Empty Paypal response.', 'woocommerce-gateway-paypal-pro'));

			parse_str( $response['body'], $parsed_response );

			$this->log( 'Parsed Response ' . print_r( $parsed_response, true ) );

			switch ( strtolower( $parsed_response['ACK'] ) ) :
				case 'success':
				case 'successwithwarning':

					// Add order note
					$order->add_order_note( sprintf(__('PayPal Pro payment completed (Transaction ID: %s, Correlation ID: %s)', 'woocommerce-gateway-paypal-pro'), $parsed_response['TRANSACTIONID'], $parsed_response['CORRELATIONID'] ) );

					// Payment complete
					$order->payment_complete();

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

				break;
				case 'failure':
				default:

					// Get error message
					if ( ! empty( $parsed_response['L_LONGMESSAGE0'] ) )
						$error_message = $parsed_response['L_LONGMESSAGE0'];
					elseif ( ! empty( $parsed_response['L_SHORTMESSAGE0'] ) )
						$error_message = $parsed_response['L_SHORTMESSAGE0'];
					elseif ( ! empty( $parsed_response['L_SEVERITYCODE0'] ) )
						$error_message = $parsed_response['L_SEVERITYCODE0'];
					elseif ( $this->testmode == "yes" )
						$error_message = print_r( $parsed_response, true );

					// Payment failed :(
					$order->update_status( 'failed', sprintf(__('PayPal Pro payment failed (Correlation ID: %s). Payment was rejected due to an error: ', 'woocommerce-gateway-paypal-pro'), $parsed_response['CORRELATIONID'] ) . '(' . $parsed_response['L_ERRORCODE0'] . ') ' . '"' . $error_message . '"' );

					throw new Exception( $error_message );

				break;
			endswitch;

		} catch(Exception $e) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( '<strong>' . __('Payment error', 'woocommerce-gateway-paypal-pro') . '</strong>: ' . $e->getMessage(), 'error' );
			} else {
				$woocommerce->add_error( '<strong>' . __('Payment error', 'woocommerce-gateway-paypal-pro') . '</strong>: ' . $e->getMessage() );
			}
			return;
		}
	}


	/**
     * Get user's IP address
     */
	function get_user_ip() {
		return ! empty( $_SERVER['HTTP_X_FORWARD_FOR'] ) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * clear_centinel_session function.
	 */
	function clear_centinel_session() {
		global $woocommerce;

        $woocommerce->session->set( "paypal_pro_orderid", null );
	    $woocommerce->session->set( "Centinel_cmpiMessageResp", null );
		$woocommerce->session->set( "Centinel_Enrolled", null );
	    $woocommerce->session->set( "Centinel_TransactionId", null );
	    $woocommerce->session->set( "Centinel_ACSUrl", null );
	    $woocommerce->session->set( "Centinel_Payload", null );
	    $woocommerce->session->set( "Centinel_ErrorNo", null );
	    $woocommerce->session->set( "Centinel_ErrorDesc", null );
		$woocommerce->session->set( "Centinel_EciFlag", null );
	    $woocommerce->session->set( "Centinel_TransactionType", null );
	    $woocommerce->session->set( 'Centinel_TermUrl', null );
	    $woocommerce->session->set( 'Centinel_OrderId', null );
    }

    /**
     * Add a log entry
     */
    public function log( $message ) {
    	if ( $this->debug ) {
    		if ( ! isset( $this->log ) ) {
    			$this->log = new WC_Logger();
    		}
			$this->log->add( 'paypal-pro', $message );
    	}
    }
}