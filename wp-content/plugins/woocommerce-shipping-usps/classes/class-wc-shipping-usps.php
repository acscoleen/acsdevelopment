<?php
/**
 * WC_Shipping_USPS class.
 *
 * @extends WC_Shipping_Method
 */
class WC_Shipping_USPS extends WC_Shipping_Method {

	private $endpoint = 'http://production.shippingapis.com/shippingapi.dll';

	private $default_user_id = '150WOOTH2143';

	private $domestic = array( "US", "PR", "VI" );

	private $found_rates;

	// Define flat rate box dimensions
	private $flat_rate_boxes = array(
		// Priority Mail Express
		"d13"     => array(
			"name"        => "Priority Mail Express Flat Rate Envelope",
			"length"      => "12.5",
			"width"       => "9.5",
			"height"      => "0.25",
			"weight"      => "70",
			"type"        => "express"
		),
		"d30"     => array(
			"name"        => "Priority Mail Express Legal Flat Rate Envelope",
			"length"      => "9.5",
			"width"       => "15",
			"height"      => "0.25",
			"weight"      => "70",
			"type"        => "express"
		),
		"d55"     => array(
			"name"   => "Priority Mail Express Flat Rate Box",
			"length" => "11",
			"width"  => "8.5",
			"height" => "5.5",
			"weight" => "70",
			"type"   => "express"
		),
		"d63"     => array(
			"name"        => "Priority Mail Express Padded Flat Rate Envelope",
			"length"      => "12.5",
			"width"       => "9.5",
			"height"      => "1",
			"weight"      => "70",
			"type"        => "express"
		),

		// Priority Mail
		"d16"     => array(
			"name"        => "Priority Mail Flat Rate Envelope",
			"length"      => "12.5",
			"width"       => "9.5",
			"height"      => "0.25",
			"weight"      => "70",
			"type"        => "priority"
		),
		"d17"     => array(
			"name"        => "Priority Mail Flat Rate Medium Box",
			"length"      => "11.875",
			"width"       => "13.625",
			"height"      => "3.375",
			"weight"      => "70",
			"type"        => "priority"
		),
		"d17b"     => array(
			"name"        => "Priority Mail Flat Rate Medium Box",
			"length"      => "11",
			"width"       => "8.5",
			"height"      => "5.5",
			"weight"      => "70",
			"type"        => "priority"
		),
		"d22"     => array(
			"name"        => "Priority Mail Flat Rate Large Box",
			"length"      => "12",
			"width"       => "12",
			"height"      => "5.5",
			"weight"      => "70",
			"type"        => "priority"
		),
		"d28"     => array(
			"name"        => "Priority Mail Flat Rate Small Box",
			"length"      => "5.375",
			"width"       => "8.625",
			"height"      => "1.625",
			"weight"      => "70",
			"type"        => "priority"
		),
		"d29"     => array(
			"name"        => "Priority Mail Padded Flat Rate Envelope",
			"length"      => "12.5",
			"width"       => "9.5",
			"height"      => "1",
			"weight"      => "70",
			"type"        => "priority"
		),
		"d38"     => array(
			"name"        => "Priority Mail Gift Card Flat Rate Envelope",
			"length"      => "10",
			"width"       => "7",
			"height"      => "0.25",
			"weight"      => "70",
			"type"        => "priority"
		),
		"d40"     => array(
			"name"        => "Priority Mail Window Flat Rate Envelope",
			"length"      => "5",
			"width"       => "10",
			"height"      => "0.25",
			"weight"      => "70",
			"type"        => "priority"
		),
		"d42"     => array(
			"name"        => "Priority Mail Small Flat Rate Envelope",
			"length"      => "6",
			"width"       => "10",
			"height"      => "0.25",
			"weight"      => "70",
			"type"        => "priority"
		),
		"d44"     => array(
			"name"        => "Priority Mail Legal Flat Rate Envelope",
			"length"      => "9.5",
			"width"       => "15",
			"height"      => "0.5",
			"weight"      => "70",
			"type"        => "priority"
		),

		// International Priority Mail Express
		"i13"     => array(
			"name"    => "Priority Mail Express Flat Rate Envelope",
			"length"  => "12.5",
			"width"   => "9.5",
			"height"  => "0.25",
			"weight"  => "4",
			"type"    => "express"
		),
		"i30"     => array(
			"name"    => "Priority Mail Express Legal Flat Rate Envelope",
			"length"  => "9.5",
			"width"   => "15",
			"height"  => "0.25",
			"weight"  => "4",
			"type"    => "express"
		),
		"i55"     => array(
			"name"   => "Priority Mail Express Flat Rate Box",
			"length" => "11",
			"width"  => "8.5",
			"height" => "5.5",
			"weight" => "20",
			"type"   => "express"
		),
		"i63"     => array(
			"name"    => "Priority Mail Express Padded Flat Rate Envelope",
			"length"  => "12.5",
			"width"   => "9.5",
			"height"  => "1",
			"weight"  => "4",
			"type"    => "express"
		),

		// International Priority Mail
		"i8"      => array(
			"name"   => "Priority Mail Flat Rate Envelope",
			"length" => "12.5",
			"width"  => "9.5",
			"height" => "0.25",
			"weight" => "4",
			"type"   => "priority"
		),
		"i16"     => array(
			"name"   => "Priority Mail Flat Rate Small Box",
			"length" => "5.375",
			"width"  => "8.625",
			"height" => "1.625",
			"weight" => "4",
			"type"   => "priority"
		),
		"i9"      => array(
			"name"   => "Priority Mail Flat Rate Medium Box",
			"length" => "11.875",
			"width"  => "13.625",
			"height" => "3.375",
			"weight" => "20",
			"type"   => "priority"
		),
		"i9b"      => array(
			"name"   => "Priority Mail Flat Rate Medium Box",
			"length" => "11",
			"width"  => "8.5",
			"height" => "5.5",
			"weight" => "70",
			"type"   => "priority"
		),
		"i11"     => array(
			"name"   => "Priority Mail Flat Rate Large Box",
			"length" => "12",
			"width"  => "12",
			"height" => "5.5",
			"weight" => "20",
			"type"   => "priority"
		)
	);

	// Define flat rate box retail and online pricing - 2014 rates
	private $flat_rate_pricing = array(
		
		// Priority Mail Express
		
			// Priority Mail Express Flat Rate Envelope
			"d13"     => array(
				"retail" => "19.99",
				"online" => "18.11",
			),
			// Priority Mail Express Legal Flat Rate Envelope
			"d30"     => array(
				"retail" => "19.99",
				"online" => "18.11",
			),
			// Priority Mail Express Flat Rate Box
			"d55"     => array(
				"retail" => "44.95",
				"online" => "44.95",
			),
			// Priority Mail Express Padded Flat Rate Envelope
			"d63"     => array(
				"retail" => "19.99",
				"online" => "18.11",
			),

		// Priority Mail Boxes

			// Priority Mail Flat Rate Medium Box
			"d17"     => array(
				"retail" => "12.35",
				"online" => "11.30",
			),
			// Priority Mail Flat Rate Medium Box
			"d17b"     => array(
				"retail" => "12.35",
				"online" => "11.30",
			),
			// Priority Mail Flat Rate Large Box
			"d22"     => array(
				"retail" => "17.45",
				"online" => "15.80",
			),
			// Priority Mail Flat Rate Small Box
			"d28"     => array(
				"retail" => "5.80",
				"online" => "5.25",
			),

		// Priority Mail Envelopes
		
			// Priority Mail Flat Rate Envelope
			"d16"     => array(
				"retail" => "5.60",
				"online" => "5.05",
			),
			// Priority Mail Padded Flat Rate Envelope
			"d29"     => array(
				"retail" => "5.95",
				"online" => "5.70",
			),
			// Priority Mail Gift Card Flat Rate Envelope
			"d38"     => array(
				"retail" => "5.60",
				"online" => "5.05",
			),
			// Priority Mail Window Flat Rate Envelope
			"d40"     => array(
				"retail" => "5.60",
				"online" => "5.05",
			),
			// Priority Mail Small Flat Rate Envelope
			"d42"     => array(
				"retail" => "5.60",
				"online" => "5.05",
			),
			// Priority Mail Legal Flat Rate Envelope
			"d44"     => array(
				"retail" => "5.75",
				"online" => "5.25",
			),

		// International Priority Mail Express

			// Priority Mail Express Flat Rate Envelope
			"i13"     => array(
				"retail"    => array(
					'*'  => "46.50",
					'CA' => "35.95"
				)
			),
			// Priority Mail Express Legal Flat Rate Envelope
			"i30"     => array(
				"retail"    => array(
					'*'  => "46.50",
					'CA' => "35.95"
				)
			),
			// Priority Mail Express Flat Rate Box
			"i55"     => array(
				"retail"    => array(
					'*'  => "84.95",
					'CA' => "66.95"
				)
			),
			// Priority Mail Express Padded Flat Rate Envelope
			"i63"     => array(
				"retail"    => array(
					'*'  => "46.50",
					'CA' => "35.95"
				)
			),

		// International Priority Mail

			// Priority Mail Flat Rate Envelope
			"i8"      => array(
				"retail"    => array(
					'*'  => "24.75",
					'CA' => "20.55"
				)
			),
			// Priority Mail Flat Rate Small Box
			"i16"     => array(
				"retail"    => array(
					'*'  => "24.75",
					'CA' => "20.55"
				)
			),
			// Priority Mail Flat Rate Medium Box
			"i9"      => array(
				"retail"    => array(
					'*'  => "61.75",
					'CA' => "42.25"
				)
			),
			// Priority Mail Flat Rate Medium Box
			"i9b"      => array(
				"retail"    => array(
					'*'  => "61.75",
					'CA' => "42.25"
				)
			),
			// Priority Mail Flat Rate Large Box
			"i11"     => array(
				"retail"    => array(
					'*'  => "80.50",
					'CA' => "55.75"
				)
			)
	);

	private $services = array(
		// Domestic
		'D_FIRST_CLASS' => array(
			// Name of the service shown to the user
			'name'  => 'First-Class Mail&#0174;',

			// Services which costs are merged if returned (cheapest is used). This gives us the best possible rate.
			'services' => array(
				"0"  => "First-Class Mail&#0174; Parcel",
				'12' => "First-Class&#8482; Postcard Stamped",
				'15' => "First-Class&#8482; Large Postcards",
				'19' => "First-Class&#8482; Keys and IDs",
				'61' => "First-Class&#8482; Package Service",
				'53' => "First-Class&#8482; Package Service Hold For Pickup",
				'78' => "First-Class Mail&#0174; Metered Letter"
			)
		),
		'D_EXPRESS_MAIL' => array(
			// Name of the service shown to the user
			'name'  => 'Priority Mail Express&#8482;',

			// Services which costs are merged if returned (cheapest is used). This gives us the best possible rate.
			'services' => array(
				'2'  => "Priority Mail Express&#8482; Hold for Pickup",
				'3'  => "Priority Mail Express&#8482; PO to Address",
				'23' => "Priority Mail Express&#8482; Sunday/Holiday",
			)
		),
		'D_STANDARD_POST' => array(
			// Name of the service shown to the user
			'name'  => 'Standard Post&#8482;',

			// Services which costs are merged if returned (cheapest is used). This gives us the best possible rate.
			'services' => array(
				'4'  => "Standard Post&#8482;"
			)
		),
		'D_BPM' => array(
			// Name of the service shown to the user
			'name'  => 'Bound Printed Matter',

			// Services which costs are merged if returned (cheapest is used). This gives us the best possible rate.
			'services' => array(
				'5'  => "Bound Printed Matter"
			)
		),
		'D_MEDIA_MAIL' => array(
			// Name of the service shown to the user
			'name'  => 'Media Mail&#0174;',

			// Services which costs are merged if returned (cheapest is used). This gives us the best possible rate.
			'services' => array(
				'6'  => "Media Mail&#0174;"
			)
		),
		'D_LIBRARY_MAIL' => array(
			// Name of the service shown to the user
			'name'  => "Library Mail",

			// Services which costs are merged if returned (cheapest is used). This gives us the best possible rate.
			'services' => array(
				'7'  => "Library Mail"
			)
		),
		'D_PRIORITY_MAIL' => array(
			// Name of the service shown to the user
			'name'  => "Priority Mail&#0174;",

			// Services which costs are merged if returned (cheapest is used). This gives us the best possible rate.
			'services' => array(
				"1"  => "Priority Mail&#0174;",
				"18" => "Priority Mail&#0174; Keys and IDs",
				"47" => "Priority Mail&#0174; Regional Rate Box A",
				"49" => "Priority Mail&#0174; Regional Rate Box B",
				"58" => "Priority Mail&#0174; Regional Rate Box C",
			)
		),

		// International
		'I_EXPRESS_MAIL' => array(
			// Name of the service shown to the user
			'name'  => "Priority Mail Express International&#8482;",

			// Services which costs are merged if returned (cheapest is used). This gives us the best possible rate.
			'services' => array(
				"1"  => "Priority Mail Express International&#8482;",
			)
		),
		'I_PRIORITY_MAIL' => array(
			// Name of the service shown to the user
			'name'  => "Priority Mail International&#0174;",

			// Services which costs are merged if returned (cheapest is used). This gives us the best possible rate.
			'services' => array(
				"2"  => "Priority Mail International&#0174;",
			)
		),
		'I_GLOBAL_EXPRESS' => array(
			// Name of the service shown to the user
			'name'  => "Global Express Guaranteed&#0174;",

			// Services which costs are merged if returned (cheapest is used). This gives us the best possible rate.
			'services' => array(
				"4"  => "Global Express Guaranteed&#0174;",
				"5"  => "Global Express Guaranteed&#0174; Document used",
				"6"  => "Global Express Guaranteed&#0174; Non-Document Rectangular",
				"7"  => "Global Express Guaranteed&#0174; Non-Document Non-Rectangular",
				"12"  => "Global Express Guaranteed&#0174; Envelope",
			)
		),
		'I_FIRST_CLASS' => array(
			// Name of the service shown to the user
			'name'  => "First Class Package Service&#8482; International",

			// Services which costs are merged if returned (cheapest is used). This gives us the best possible rate.
			'services' => array(
				"13"  => "First Class Package Service&#8482; International Letters",
				"14"  => "First Class Package Service&#8482; International Large Envelope",
				"15"  => "First Class Package Service&#8482; International Parcel"
			)
		),
		'I_POSTCARDS' => array(
			// Name of the service shown to the user
			'name'  => "International Postcards",

			// Services which costs are merged if returned (cheapest is used). This gives us the best possible rate.
			'services' => array(
				"21"  => "International Postcards"
			)
		)
	);

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->id                 = 'usps';
		$this->method_title       = __( 'USPS', 'wc_usps' );
		$this->method_description = __( 'The <strong>USPS</strong> extension obtains rates dynamically from the USPS API during cart/checkout.', 'wc_usps' );
		$this->init();
	}

    /**
     * init function.
     *
     * @access public
     * @return void
     */
    private function init() {
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->enabled               = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : $this->enabled;
		$this->title                 = isset( $this->settings['title'] ) ? $this->settings['title'] : $this->method_title;
		$this->availability          = isset( $this->settings['availability'] ) ? $this->settings['availability'] : 'all';
		$this->countries             = isset( $this->settings['countries'] ) ? $this->settings['countries'] : array();
		$this->origin                = isset( $this->settings['origin'] ) ? $this->settings['origin'] : '';
		$this->user_id               = ! empty( $this->settings['user_id'] ) ? $this->settings['user_id'] : $this->default_user_id;
		$this->packing_method        = isset( $this->settings['packing_method'] ) ? $this->settings['packing_method'] : 'per_item';
		$this->boxes                 = isset( $this->settings['boxes'] ) ? $this->settings['boxes'] : array();
		$this->custom_services       = isset( $this->settings['services'] ) ? $this->settings['services'] : array();
		$this->offer_rates           = isset( $this->settings['offer_rates'] ) ? $this->settings['offer_rates'] : 'all';
		$this->fallback              = ! empty( $this->settings['fallback'] ) ? $this->settings['fallback'] : '';
		$this->flat_rate_fee         = ! empty( $this->settings['flat_rate_fee'] ) ? $this->settings['flat_rate_fee'] : '';
		$this->mediamail_restriction = isset( $this->settings['mediamail_restriction'] ) ? $this->settings['mediamail_restriction'] : array();
		$this->mediamail_restriction = array_filter( (array) $this->mediamail_restriction );

		$this->enable_standard_services = isset( $this->settings['enable_standard_services'] ) && $this->settings['enable_standard_services'] == 'yes' ? true : false;

		$this->enable_flat_rate_boxes = isset( $this->settings['enable_flat_rate_boxes'] ) ? $this->settings['enable_flat_rate_boxes'] : 'yes';
		$this->debug           = isset( $this->settings['debug_mode'] ) && $this->settings['debug_mode'] == 'yes' ? true : false;

		$this->flat_rate_boxes = apply_filters( 'usps_flat_rate_boxes', $this->flat_rate_boxes );

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'clear_transients' ) );
	}

	/**
	 * environment_check function.
	 *
	 * @access public
	 * @return void
	 */
	private function environment_check() {
		global $woocommerce;

		$admin_page = version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) ? 'wc-settings' : 'woocommerce_settings';

		if ( get_woocommerce_currency() != "USD" ) {
			echo '<div class="error">
				<p>' . sprintf( __( 'USPS requires that the <a href="%s">currency</a> is set to US Dollars.', 'wc_usps' ), admin_url( 'admin.php?page=' . $admin_page . '&tab=general' ) ) . '</p>
			</div>';
		}

		elseif ( ! in_array( $woocommerce->countries->get_base_country(), $this->domestic ) ) {
			echo '<div class="error">
				<p>' . sprintf( __( 'USPS requires that the <a href="%s">base country/region</a> is the United States.', 'wc_usps' ), admin_url( 'admin.php?page=' . $admin_page . '&tab=general' ) ) . '</p>
			</div>';
		}

		elseif ( ! $this->origin && $this->enabled == 'yes' ) {
			echo '<div class="error">
				<p>' . __( 'USPS is enabled, but the origin postcode has not been set.', 'wc_usps' ) . '</p>
			</div>';
		}
	}

	/**
	 * admin_options function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_options() {
		// Check users environment supports this method
		$this->environment_check();

		// Show settings
		parent::admin_options();
	}

	/**
	 * generate_services_html function.
	 *
	 * @access public
	 * @return void
	 */
	function generate_services_html() {
		ob_start();
		?>
		<tr valign="top" id="service_options">
			<th scope="row" class="titledesc"><?php _e( 'Services', 'wc_usps' ); ?></th>
			<td class="forminp">
				<table class="usps_services widefat">
					<thead>
						<th class="sort">&nbsp;</th>
						<th><?php _e( 'Name', 'wc_usps' ); ?></th>
						<th><?php _e( 'Service(s)', 'wc_usps' ); ?></th>
						<th><?php echo sprintf( __( 'Price Adjustment (%s)', 'wc_usps' ), get_woocommerce_currency_symbol() ); ?></th>
						<th><?php _e( 'Price Adjustment (%)', 'wc_usps' ); ?></th>
					</thead>
					<tbody>
						<?php
							$sort = 0;
							$this->ordered_services = array();

							foreach ( $this->services as $code => $values ) {

								if ( isset( $this->custom_services[ $code ]['order'] ) ) {
									$sort = $this->custom_services[ $code ]['order'];
								}

								while ( isset( $this->ordered_services[ $sort ] ) )
									$sort++;

								$this->ordered_services[ $sort ] = array( $code, $values );

								$sort++;
							}

							ksort( $this->ordered_services );

							foreach ( $this->ordered_services as $value ) {
								$code   = $value[0];
								$values = $value[1];
								if ( ! isset( $this->custom_services[ $code ] ) )
									$this->custom_services[ $code ] = array();
								?>
								<tr>
									<td class="sort">
										<input type="hidden" class="order" name="usps_service[<?php echo $code; ?>][order]" value="<?php echo isset( $this->custom_services[ $code ]['order'] ) ? $this->custom_services[ $code ]['order'] : ''; ?>" />
									</td>
									<td>
										<input type="text" name="usps_service[<?php echo $code; ?>][name]" placeholder="<?php echo $values['name']; ?> (<?php echo $this->title; ?>)" value="<?php echo isset( $this->custom_services[ $code ]['name'] ) ? $this->custom_services[ $code ]['name'] : ''; ?>" size="35" />
									</td>
									<td>
										<ul class="sub_services" style="font-size: 0.92em; color: #555">
											<?php foreach ( $values['services'] as $key => $name ) : ?>
											<li style="line-height: 23px;">
												<label>
													<input type="checkbox" name="usps_service[<?php echo $code; ?>][<?php echo $key; ?>][enabled]" <?php checked( ( ! isset( $this->custom_services[ $code ][ $key ]['enabled'] ) || ! empty( $this->custom_services[ $code ][ $key ]['enabled'] ) ), true ); ?> />
													<?php echo $name; ?>
												</label>
											</li>
											<?php endforeach; ?>
										</ul>
									</td>
									<td>
										<ul class="sub_services" style="font-size: 0.92em; color: #555">
											<?php foreach ( $values['services'] as $key => $name ) : ?>
											<li>
												<?php echo get_woocommerce_currency_symbol(); ?><input type="text" name="usps_service[<?php echo $code; ?>][<?php echo $key; ?>][adjustment]" placeholder="N/A" value="<?php echo isset( $this->custom_services[ $code ][ $key ]['adjustment'] ) ? $this->custom_services[ $code ][ $key ]['adjustment'] : ''; ?>" size="4" />
											</li>
											<?php endforeach; ?>
										</ul>
									</td>
									<td>
										<ul class="sub_services" style="font-size: 0.92em; color: #555">
											<?php foreach ( $values['services'] as $key => $name ) : ?>
											<li>
												<input type="text" name="usps_service[<?php echo $code; ?>][<?php echo $key; ?>][adjustment_percent]" placeholder="N/A" value="<?php echo isset( $this->custom_services[ $code ][ $key ]['adjustment_percent'] ) ? $this->custom_services[ $code ][ $key ]['adjustment_percent'] : ''; ?>" size="4" />%
											</li>
											<?php endforeach; ?>
										</ul>
									</td>
								</tr>
								<?php
							}
						?>
					</tbody>
				</table>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * generate_box_packing_html function.
	 *
	 * @access public
	 * @return void
	 */
	public function generate_box_packing_html() {
		ob_start();
		?>
		<tr valign="top" id="packing_options">
			<th scope="row" class="titledesc"><?php _e( 'Box Sizes', 'wc_usps' ); ?></th>
			<td class="forminp">
				<style type="text/css">
					.usps_boxes td, .usps_services td, .usps_boxes th, .usps_services th {
						vertical-align: middle;
						padding: 4px 7px;
					}
					.usps_boxes td input {
						margin-right: 4px;
					}
					.usps_boxes .check-column {
						vertical-align: middle;
						text-align: left;
						padding: 0 7px;
					}
					.usps_services th.sort {
						width: 16px;
					}
					.usps_services td.sort {
						cursor: move;
						width: 16px;
						padding: 0;
						cursor: move;
						background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAAHUlEQVQYV2O8f//+fwY8gJGgAny6QXKETRgEVgAAXxAVsa5Xr3QAAAAASUVORK5CYII=) no-repeat center;					}
				</style>
				<table class="usps_boxes widefat">
					<thead>
						<tr>
							<th class="check-column"><input type="checkbox" /></th>
							<th><?php _e( 'Name', 'wc_usps' ); ?></th>
							<th><?php _e( 'L', 'wc_usps' ); ?> (in)</th>
							<th><?php _e( 'W', 'wc_usps' ); ?> (in)</th>
							<th><?php _e( 'H', 'wc_usps' ); ?> (in)</th>
							<th><?php _e( 'Inner L', 'wc_usps' ); ?> (in)</th>
							<th><?php _e( 'Inner W', 'wc_usps' ); ?> (in)</th>
							<th><?php _e( 'Inner H', 'wc_usps' ); ?> (in)</th>
							<th><?php _e( 'Box Weight', 'wc_usps' ); ?> (lbs)</th>
							<th><?php _e( 'Max Weight', 'wc_usps' ); ?> (lbs)</th>
							<th><?php _e( 'Letter', 'wc_usps' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th colspan="3">
								<a href="#" class="button plus insert"><?php _e( 'Add Box', 'wc_usps' ); ?></a>
								<a href="#" class="button minus remove"><?php _e( 'Remove selected box(es)', 'wc_usps' ); ?></a>
							</th>
							<th colspan="8">
								<small class="description"><?php _e( 'Items will be packed into these boxes based on item dimensions and volume. Outer dimensions will be passed to USPS, whereas inner dimensions will be used for packing. Items not fitting into boxes will be packed individually.', 'wc_usps' ); ?></small>
							</th>
						</tr>
					</tfoot>
					<tbody id="rates">
						<?php
							if ( $this->boxes ) {
								foreach ( $this->boxes as $key => $box ) {
									?>
									<tr>
										<td class="check-column"><input type="checkbox" /></td>
										<td><input type="text" size="10" name="boxes_name[<?php echo $key; ?>]" value="<?php echo isset( $box['name'] ) ? esc_attr( $box['name'] ) : ''; ?>" /></td>
										<td><input type="text" size="5" name="boxes_outer_length[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['outer_length'] ); ?>" /></td>
										<td><input type="text" size="5" name="boxes_outer_width[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['outer_width'] ); ?>" /></td>
										<td><input type="text" size="5" name="boxes_outer_height[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['outer_height'] ); ?>" /></td>
										<td><input type="text" size="5" name="boxes_inner_length[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['inner_length'] ); ?>" /></td>
										<td><input type="text" size="5" name="boxes_inner_width[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['inner_width'] ); ?>" /></td>
										<td><input type="text" size="5" name="boxes_inner_height[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['inner_height'] ); ?>" /></td>
										<td><input type="text" size="5" name="boxes_box_weight[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['box_weight'] ); ?>" /></td>
										<td><input type="text" size="5" name="boxes_max_weight[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['max_weight'] ); ?>" /></td>
										<td><input type="checkbox" name="boxes_is_letter[<?php echo $key; ?>]" <?php checked( isset( $box['is_letter'] ) && $box['is_letter'] == true, true ); ?> /></td>
									</tr>
									<?php
								}
							}
						?>
					</tbody>
				</table>
				<script type="text/javascript">

					jQuery(window).load(function(){

						jQuery('#woocommerce_usps_enable_standard_services').change(function(){
							if ( jQuery(this).is(':checked') ) {
								jQuery('#woocommerce_usps_mediamail_restriction').closest('tr').show();
								jQuery('#service_options, #packing_options').show();
								jQuery('#woocommerce_usps_packing_method, #woocommerce_usps_offer_rates').closest('tr').show();
								jQuery('#woocommerce_usps_packing_method').change();
							} else {
								jQuery('#woocommerce_usps_mediamail_restriction').closest('tr').hide();
								jQuery('#service_options, #packing_options').hide();
								jQuery('#woocommerce_usps_packing_method, #woocommerce_usps_offer_rates').closest('tr').hide();
							}
						}).change();

						jQuery('#woocommerce_usps_packing_method').change(function(){

							if ( jQuery('#woocommerce_usps_enable_standard_services').is(':checked') ) {

								if ( jQuery(this).val() == 'box_packing' )
									jQuery('#packing_options').show();
								else
									jQuery('#packing_options').hide();

								if ( jQuery(this).val() == 'weight' )
									jQuery('#woocommerce_usps_max_weight').closest('tr').show();
								else
									jQuery('#woocommerce_usps_max_weight').closest('tr').hide();

							}

						}).change();

						jQuery('#woocommerce_usps_enable_flat_rate_boxes').change(function(){

							if ( jQuery(this).val() == 'yes' ) {
								jQuery('#woocommerce_usps_flat_rate_express_title').closest('tr').show();
								jQuery('#woocommerce_usps_flat_rate_priority_title').closest('tr').show();
								jQuery('#woocommerce_usps_flat_rate_fee').closest('tr').show();
							} else if ( jQuery(this).val() == 'no' ) {
								jQuery('#woocommerce_usps_flat_rate_express_title').closest('tr').hide();
								jQuery('#woocommerce_usps_flat_rate_priority_title').closest('tr').hide();
								jQuery('#woocommerce_usps_flat_rate_fee').closest('tr').hide();
							} else if ( jQuery(this).val() == 'priority' ) {
								jQuery('#woocommerce_usps_flat_rate_express_title').closest('tr').hide();
								jQuery('#woocommerce_usps_flat_rate_priority_title').closest('tr').show();
								jQuery('#woocommerce_usps_flat_rate_fee').closest('tr').show();
							} else if ( jQuery(this).val() == 'express' ) {
								jQuery('#woocommerce_usps_flat_rate_express_title').closest('tr').show();
								jQuery('#woocommerce_usps_flat_rate_priority_title').closest('tr').hide();
								jQuery('#woocommerce_usps_flat_rate_fee').closest('tr').show();
							}

						}).change();

						jQuery('.usps_boxes .insert').click( function() {
							var $tbody = jQuery('.usps_boxes').find('tbody');
							var size = $tbody.find('tr').size();
							var code = '<tr class="new">\
									<td class="check-column"><input type="checkbox" /></td>\
									<td><input type="text" size="10" name="boxes_name[' + size + ']" /></td>\
									<td><input type="text" size="5" name="boxes_outer_length[' + size + ']" /></td>\
									<td><input type="text" size="5" name="boxes_outer_width[' + size + ']" /></td>\
									<td><input type="text" size="5" name="boxes_outer_height[' + size + ']" /></td>\
									<td><input type="text" size="5" name="boxes_inner_length[' + size + ']" /></td>\
									<td><input type="text" size="5" name="boxes_inner_width[' + size + ']" /></td>\
									<td><input type="text" size="5" name="boxes_inner_height[' + size + ']" /></td>\
									<td><input type="text" size="5" name="boxes_box_weight[' + size + ']" />lbs</td>\
									<td><input type="text" size="5" name="boxes_max_weight[' + size + ']" />lbs</td>\
									<td><input type="checkbox" name="boxes_is_letter[' + size + ']" /></td>\
								</tr>';

							$tbody.append( code );

							return false;
						} );

						jQuery('.usps_boxes .remove').click(function() {
							var $tbody = jQuery('.usps_boxes').find('tbody');

							$tbody.find('.check-column input:checked').each(function() {
								jQuery(this).closest('tr').hide().find('input').val('');
							});

							return false;
						});

						// Ordering
						jQuery('.usps_services tbody').sortable({
							items:'tr',
							cursor:'move',
							axis:'y',
							handle: '.sort',
							scrollSensitivity:40,
							forcePlaceholderSize: true,
							helper: 'clone',
							opacity: 0.65,
							placeholder: 'wc-metabox-sortable-placeholder',
							start:function(event,ui){
								ui.item.css('baclbsround-color','#f6f6f6');
							},
							stop:function(event,ui){
								ui.item.removeAttr('style');
								usps_services_row_indexes();
							}
						});

						function usps_services_row_indexes() {
							jQuery('.usps_services tbody tr').each(function(index, el){
								jQuery('input.order', el).val( parseInt( jQuery(el).index('.usps_services tr') ) );
							});
						};

					});

				</script>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * validate_box_packing_field function.
	 *
	 * @access public
	 * @param mixed $key
	 * @return void
	 */
	public function validate_box_packing_field( $key ) {

		$boxes = array();

		if ( isset( $_POST['boxes_outer_length'] ) ) {
			$boxes_name         = isset( $_POST['boxes_name'] ) ? $_POST['boxes_name'] : array();
			$boxes_outer_length = $_POST['boxes_outer_length'];
			$boxes_outer_width  = $_POST['boxes_outer_width'];
			$boxes_outer_height = $_POST['boxes_outer_height'];
			$boxes_inner_length = $_POST['boxes_inner_length'];
			$boxes_inner_width  = $_POST['boxes_inner_width'];
			$boxes_inner_height = $_POST['boxes_inner_height'];
			$boxes_box_weight   = $_POST['boxes_box_weight'];
			$boxes_max_weight   = $_POST['boxes_max_weight'];
			$boxes_is_letter    = isset( $_POST['boxes_is_letter'] ) ? $_POST['boxes_is_letter'] : array();

			for ( $i = 0; $i < sizeof( $boxes_outer_length ); $i ++ ) {

				if ( $boxes_outer_length[ $i ] && $boxes_outer_width[ $i ] && $boxes_outer_height[ $i ] && $boxes_inner_length[ $i ] && $boxes_inner_width[ $i ] && $boxes_inner_height[ $i ] ) {

					$boxes[] = array(
						'name'         => woocommerce_clean( $boxes_name[ $i ] ),
						'outer_length' => floatval( $boxes_outer_length[ $i ] ),
						'outer_width'  => floatval( $boxes_outer_width[ $i ] ),
						'outer_height' => floatval( $boxes_outer_height[ $i ] ),
						'inner_length' => floatval( $boxes_inner_length[ $i ] ),
						'inner_width'  => floatval( $boxes_inner_width[ $i ] ),
						'inner_height' => floatval( $boxes_inner_height[ $i ] ),
						'box_weight'   => floatval( $boxes_box_weight[ $i ] ),
						'max_weight'   => floatval( $boxes_max_weight[ $i ] ),
						'is_letter'    => isset( $boxes_is_letter[ $i ] ) ? true : false
					);

				}

			}
		}

		return $boxes;
	}

	/**
	 * validate_services_field function.
	 *
	 * @access public
	 * @param mixed $key
	 * @return void
	 */
	public function validate_services_field( $key ) {
		$services         = array();
		$posted_services  = $_POST['usps_service'];

		foreach ( $posted_services as $code => $settings ) {

			$services[ $code ] = array(
				'name'               => woocommerce_clean( $settings['name'] ),
				'order'              => woocommerce_clean( $settings['order'] )
			);

			foreach ( $this->services[$code]['services'] as $key => $name ) {
				$services[ $code ][ $key ]['enabled'] = isset( $settings[ $key ]['enabled'] ) ? true : false;
				$services[ $code ][ $key ]['adjustment'] = woocommerce_clean( $settings[ $key ]['adjustment'] );
				$services[ $code ][ $key ]['adjustment_percent'] = woocommerce_clean( $settings[ $key ]['adjustment_percent'] );
			}

		}

		return $services;
	}

	/**
	 * clear_transients function.
	 *
	 * @access public
	 * @return void
	 */
	public function clear_transients() {
		global $wpdb;

		$wpdb->query( "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_usps_quote_%') OR `option_name` LIKE ('_transient_timeout_usps_quote_%')" );
	}

    /**
     * init_form_fields function.
     *
     * @access public
     * @return void
     */
    public function init_form_fields() {
	    global $woocommerce;

	    $shipping_classes = array();
	    $classes = ( $classes = get_terms( 'product_shipping_class', array( 'hide_empty' => '0' ) ) ) ? $classes : array();

	    foreach ( $classes as $class )
	    	$shipping_classes[ $class->term_id ] = $class->name;

    	$this->form_fields  = array(
			'enabled'          => array(
				'title'           => __( 'Enable/Disable', 'wc_usps' ),
				'type'            => 'checkbox',
				'label'           => __( 'Enable this shipping method', 'wc_usps' ),
				'default'         => 'no'
			),
			'title'            => array(
				'title'           => __( 'Method Title', 'wc_usps' ),
				'type'            => 'text',
				'description'     => __( 'This controls the title which the user sees during checkout.', 'wc_usps' ),
				'default'         => __( 'USPS', 'wc_usps' )
			),
			'origin'           => array(
				'title'           => __( 'Origin Postcode', 'wc_usps' ),
				'type'            => 'text',
				'description'     => __( 'Enter the postcode for the <strong>sender</strong>.', 'wc_usps' ),
				'default'         => ''
		    ),
		    'availability'  => array(
				'title'           => __( 'Method Availability', 'wc_usps' ),
				'type'            => 'select',
				'default'         => 'all',
				'class'           => 'availability',
				'options'         => array(
					'all'            => __( 'All Countries', 'wc_usps' ),
					'specific'       => __( 'Specific Countries', 'wc_usps' ),
				),
			),
			'countries'        => array(
				'title'           => __( 'Specific Countries', 'wc_usps' ),
				'type'            => 'multiselect',
				'class'           => 'chosen_select',
				'css'             => 'width: 450px;',
				'default'         => '',
				'options'         => $woocommerce->countries->get_allowed_countries(),
			),
		    'api'           => array(
				'title'           => __( 'API Settings', 'wc_usps' ),
				'type'            => 'title',
				'description'     => __( 'You can obtaining a USPS user ID by signing up via their website, or just use ours. This is optional.', 'wc_usps' ),
		    ),
		    'user_id'           => array(
				'title'           => __( 'USPS User ID', 'wc_usps' ),
				'type'            => 'text',
				'description'     => __( 'Obtained from USPS after getting an account.', 'wc_usps' ),
				'default'         => '',
				'placeholder'     => $this->default_user_id
		    ),
		    'debug_mode'  => array(
				'title'           => __( 'Debug Mode', 'wc_usps' ),
				'label'           => __( 'Enable debug mode', 'wc_usps' ),
				'type'            => 'checkbox',
				'default'         => 'no',
				'description'     => __( 'Enable debug mode to show debugging information on your cart/checkout.', 'wc_usps' )
			),
		    'rates'           => array(
				'title'           => __( 'Rates Options', 'wc_usps' ),
				'type'            => 'title',
				'description'     => __( 'The following settings determine the rates you offer your customers.', 'wc_usps' ),
		    ),
			'shippingrates'  => array(
				'title'           => __( 'Shipping Rates', 'wc_usps' ),
				'type'            => 'select',
				'default'         => 'ONLINE',
				'options'         => array(
					'ONLINE'      => __( 'Use ONLINE Rates', 'wc_usps' ),
					'ALL'         => __( 'Use OFFLINE rates', 'wc_usps' ),
				),
				'description'     => __( 'Choose which rates to show your customers, ONLINE rates are normally cheaper than OFFLINE', 'wc_usps' ),
			),
			 'fallback' => array(
				'title'       => __( 'Fallback', 'wc_usps' ),
				'type'        => 'text',
				'description' => __( 'If USPS returns no matching rates, offer this amount for shipping so that the user can still checkout. Leave blank to disable.', 'wc_usps' ),
				'default'     => ''
			),
			'flat_rates'           => array(
				'title'           => __( 'Flat Rates', 'wc_usps' ),
				'type'            => 'title',
		    ),
		    'enable_flat_rate_boxes'  => array(
				'title'           => __( 'Flat Rate Boxes &amp; envelopes', 'wc_usps' ),
				'type'            => 'select',
				'default'         => 'yes',
				'options'         => array(
					'yes'         => __( 'Yes - Enable flat rate boxes', 'wc_usps' ),
					'no'          => __( 'No - Disable flat rate boxes', 'wc_usps' ),
					'priority'    => __( 'Enable Priority flat rate boxes only', 'wc_usps' ),
					'express'     => __( 'Enable Express flat rate boxes only', 'wc_usps' ),
				),
				'description'     => __( 'Enable this option to offer shipping using USPS Flat Rate boxes. Items will be packed into the boxes and the customer will be offered a single rate from these.', 'wc_usps' )
			),
			'flat_rate_express_title'           => array(
				'title'           => __( 'Express Flat Rate Box Name', 'wc_usps' ),
				'type'            => 'text',
				'description'     => '',
				'default'         => '',
				'placeholder'     => 'Priority Mail Express Flat Rate&#0174;'
		    ),
		    'flat_rate_priority_title'           => array(
				'title'           => __( 'Priority Flat Rate Box Name', 'wc_usps' ),
				'type'            => 'text',
				'description'     => '',
				'default'         => '',
				'placeholder'     => 'Priority Mail Flat Rate&#0174;'
		    ),
		    'flat_rate_fee' => array(
				'title' 		=> __( 'Flat Rate Fee', 'woocommerce' ),
				'type' 			=> 'text',
				'description'	=> __( 'Fee per-box excluding tax. Enter an amount, e.g. 2.50, or a percentage, e.g. 5%. Leave blank to disable.', 'woocommerce' ),
				'default'		=> '',
			),
		    'standard_rates'           => array(
				'title'           => __( 'API Rates', 'wc_usps' ),
				'type'            => 'title',
		    ),
			'enable_standard_services'  => array(
				'title'           => __( 'Standard Services', 'wc_usps' ),
				'label'           => __( 'Enable Standard Services from the API', 'wc_usps' ),
				'type'            => 'checkbox',
				'default'         => 'no',
				'description'     => __( 'Enable non-flat rate services.', 'wc_usps' )
			),
			'packing_method'  => array(
				'title'           => __( 'Parcel Packing Method', 'wc_usps' ),
				'type'            => 'select',
				'default'         => '',
				'class'           => 'packing_method',
				'options'         => array(
					'per_item'       => __( 'Default: Pack items individually', 'wc_usps' ),
					'box_packing'    => __( 'Recommended: Pack into boxes with weights and dimensions', 'wc_usps' ),
					'weight_based'    => __( 'Weight based: Regular sized items (< 12 inches) are grouped and quoted for weights only. Large items are quoted individually.', 'wc_usps' ),
				),
			),
			'boxes'  => array(
				'type'            => 'box_packing'
			),
			'offer_rates'   => array(
				'title'           => __( 'Offer Rates', 'wc_usps' ),
				'type'            => 'select',
				'description'     => '',
				'default'         => 'all',
				'options'         => array(
				    'all'         => __( 'Offer the customer all returned rates', 'wc_usps' ),
				    'cheapest'    => __( 'Offer the customer the cheapest rate only', 'wc_usps' ),
				),
		    ),
			'services'  => array(
				'type'            => 'services'
			),
			'mediamail_restriction'        => array(
				'title'           => __( 'Restrict Media Mail to...', 'wc_usps' ),
				'type'            => 'multiselect',
				'class'           => 'chosen_select',
				'css'             => 'width: 450px;',
				'default'         => '',
				'options'         => $shipping_classes,
				'custom_attributes'      => array(
					'data-placeholder' => __( 'No restrictions', 'wc_usps' ),
				)
			),
		);
    }

    /**
     * calculate_shipping function.
     *
     * @access public
     * @param mixed $package
     * @return void
     */
    public function calculate_shipping( $package ) {
    	global $woocommerce;

    	$this->rates      = array();
    	$domestic         = in_array( $package['destination']['country'], $this->domestic ) ? true : false;

    	$this->debug( __( 'USPS debug mode is on - to hide these messages, turn debug mode off in the settings.', 'wc_usps' ) );

    	if ( $this->enable_standard_services ) {

	    	$package_requests = $this->get_package_requests( $package );
	    	$api              = $domestic ? 'RateV4' : 'IntlRateV2';

	    	libxml_use_internal_errors( true );

	    	if ( $package_requests ) {

	    		$request  = '<' . $api . 'Request USERID="' . $this->user_id . '">' . "\n";
	    		$request .= '<Revision>2</Revision>' . "\n";

	    		foreach ( $package_requests as $key => $package_request ) {
	    			$request .= $package_request;
	    		}

	    		$request .= '</' . $api . 'Request>' . "\n";
	    		$request = 'API=' . $api . '&XML=' . str_replace( array( "\n", "\r" ), '', $request );

	    		$transient       = 'usps_quote_' . md5( $request );
				$cached_response = get_transient( $transient );

				$this->debug( 'USPS REQUEST: <pre>' . print_r( htmlspecialchars( $request ), true ) . '</pre>' );

				if ( $cached_response !== false ) {
					$response = $cached_response;

			    	$this->debug( 'USPS CACHED RESPONSE: <pre style="height: 200px; overflow:auto;">' . print_r( htmlspecialchars( $response ), true ) . '</pre>' );
				} else {
					$response = wp_remote_post( $this->endpoint,
			    		array(
							'timeout'   => 70,
							'sslverify' => 0,
							'body'      => $request
					    )
					);

					if ( is_wp_error( $response ) ) {
		    			$this->debug( 'USPS REQUEST FAILED' );

		    			$response = false;
		    		} else {
			    		$response = $response['body'];
			    		
			    		$this->debug( 'USPS RESPONSE: <pre style="height: 200px; overflow:auto;">' . print_r( htmlspecialchars( $response ), true ) . '</pre>' );

						set_transient( $transient, $response, YEAR_IN_SECONDS );
					}
				}

	    		if ( $response ) {

					$xml = simplexml_load_string( '<root>' . preg_replace('/<\?xml.*\?>/', '', $response ) . '</root>' );

					if ( ! $xml ) {
						$this->debug( 'Failed loading XML', 'error' );
					}

					if ( ! empty( $xml->{ $api . 'Response' } ) ) {

						$usps_packages = $xml->{ $api . 'Response' }->children();

						if ( $usps_packages ) {

							$index = 0;

							foreach ( $usps_packages as $usps_package ) {

								// Get package data
								list( $package_item_id, $cart_item_qty, $package_length, $package_width, $package_height, $package_weight ) = explode( ':', $usps_package->attributes()->ID );
								$quotes              = $usps_package->children();

								if ( $this->debug ) {
									$found_quotes = array();

									foreach ( $quotes as $quote ) {
										if ( $domestic ) {
											$code = strval( $quote->attributes()->CLASSID );
											$name = strip_tags( htmlspecialchars_decode( str_replace( '*', '', (string) $quote->{'MailService'} ) ) );
										} else {
											$code = strval( $quote->attributes()->ID );
											$name = strip_tags( htmlspecialchars_decode( str_replace( '*', '', (string) $quote->{'SvcDescription'} ) ) );
										}

										if ( $name && $code ) {
											$found_quotes[ $code ] = $name;
										} elseif ( $name ) {
											$found_quotes[ $code . '-' . sanitize_title( $name ) ] = $name;
										}
									}

									if ( $found_quotes ) {
										ksort( $found_quotes );
										$found_quotes_html = '';
										foreach ( $found_quotes as $code => $name ) {
											if ( ! strstr( $name, "Flat Rate" ) ) {
												$found_quotes_html .= '<li>' . $code . ' - ' . $name . '</li>';
											}
										}
										$this->debug( 'The following quotes were returned by USPS: <ul>' . $found_quotes_html . '</ul> If any of these do not display, they may not be enabled in USPS settings.', 'success' );
									}
								}

								// Loop our known services
								foreach ( $this->services as $service => $values ) {

									if ( $domestic && strpos( $service, 'D_' ) !== 0 ) {
										continue;
									}

									if ( ! $domestic && strpos( $service, 'I_' ) !== 0 ) {
										continue;
									}

									$rate_code = (string) $service;
									$rate_id   = $this->id . ':' . $rate_code;
									$rate_name = (string) $values['name'] . ' (' . $this->title . ')';
									$rate_cost = null;

									foreach ( $quotes as $quote ) {

										if ( $domestic ) {
											$code = strval( $quote->attributes()->CLASSID );
										} else {
											$code = strval( $quote->attributes()->ID );
										}

										if ( $code !== "" && in_array( $code, array_keys( $values['services'] ) ) ) {

											if ( $domestic ) {

												if ( ! empty( $quote->{'CommercialRate'} ) ) {
													$cost = (float) $quote->{'CommercialRate'} * $cart_item_qty;
												} else {
													$cost = (float) $quote->{'Rate'} * $cart_item_qty;
												}

											} else {

												if ( ! empty( $quote->{'CommercialPostage'} ) ) {
													$cost = (float) $quote->{'CommercialPostage'} * $cart_item_qty;
												} else {
													$cost = (float) $quote->{'Postage'} * $cart_item_qty;
												}

											}

											// Cost adjustment %
											if ( ! empty( $this->custom_services[ $rate_code ][ $code ]['adjustment_percent'] ) )
												$cost = $cost + ( $cost * ( floatval( $this->custom_services[ $rate_code ][ $code ]['adjustment_percent'] ) / 100 ) );

											// Cost adjustment
											if ( ! empty( $this->custom_services[ $rate_code ][ $code ]['adjustment'] ) )
												$cost = $cost + floatval( $this->custom_services[ $rate_code ][ $code ]['adjustment'] );

											// Enabled check
											if ( isset( $this->custom_services[ $rate_code ][ $code ] ) && empty( $this->custom_services[ $rate_code ][ $code ]['enabled'] ) )
												continue;

											if ( $domestic ) {
												switch ( $code ) {
													// Handle first class - there are multiple d0 rates and we need to handle size retrictions because the API is lame
													case "0" :
														$service_name = strip_tags( htmlspecialchars_decode( str_replace( '*', '', (string) $quote->{'MailService'} ) ) );

														if ( apply_filters( 'usps_disable_first_class_rate_' . sanitize_title( $service_name ), false) ) {
															continue 2;
														}
													break;
												}
											}

											if ( $domestic && $package_length && $package_width && $package_height ) {
												switch ( $code ) {
													// Media mail has restrictions - check here
													case "6" :
														if ( sizeof( $this->mediamail_restriction ) > 0 ) {
															$invalid = false;

															foreach ( $package['contents'] as $package_item ) {
																if ( ! in_array( $package_item['data']->get_shipping_class_id(), $this->mediamail_restriction ) )
																	$invalid = true;
															}

															if ( $invalid ) {
																$this->debug( 'Skipping media mail' );
															}

															if ( $invalid ) {
																continue 2;
															}
														}
													break;
													// Regional rate boxes need additonal checks to deal with USPS's crap API
													case "47" :
														if ( $package_length > 10.125 || $package_width > 7.125 || $package_height > 5 ) {
															continue 2;
														} else {
															// Valid
															break;
														}
														if ( $package_length > 13.0625 || $package_width > 11.0625 || $package_height > 2.5 ) {
															continue 2;
														} else {
															// Valid
															break;
														}
													break;
													case "49" :
														if ( $package_length > 12.25 || $package_width > 10.5 || $package_height > 5.5 ) {
															continue 2;
														} else {
															// Valid
															break;
														}
														if ( $package_length > 16.25 || $package_width > 14.5 || $package_height > 3 ) {
															continue 2;
														} else {
															// Valid
															break;
														}
													break;
													case "58" :
														if ( $package_length > 15 || $package_width > 12 || $package_height > 12 ) {
															continue 2;
														} else {
															// Valid
															break;
														}
													break;
													// Handle first class - there are multiple d0 rates and we need to handle size retrictions because the API is lame
													case "0" :
														$service_name = strip_tags( htmlspecialchars_decode( str_replace( '*', '', (string) $quote->{'MailService'} ) ) );

														if ( strstr( $service_name, 'Postcards' ) ) {

															if ( $package_length > 6 || $package_length < 5 ) {
																continue 2;
															}
															if ( $package_width > 4.25 || $package_width < 3.5 ) {
																continue 2;
															}
															if ( $package_height > 0.016 || $package_height < 0.007 ) {
																continue 2;
															}

														} elseif ( strstr( $service_name, 'Large Envelope' ) ) {

															if ( $package_length > 15 || $package_length < 11.5 ) {
																continue 2;
															}
															if ( $package_width > 12 || $package_width < 6 ) {
																continue 2;
															}
															if ( $package_height > 0.75 || $package_height < 0.25 ) {
																continue 2;
															}

														} elseif ( strstr( $service_name, 'Letter' ) ) {

															if ( $package_length > 11.5 || $package_length < 5 ) {
																continue 2;
															}
															if ( $package_width > 6.125 || $package_width < 3.5 ) {
																continue 2;
															}
															if ( $package_height > 0.25 || $package_height < 0.007 ) {
																continue 2;
															}

														} elseif ( strstr( $service_name, 'Parcel' ) ) {

															$girth = ( $package_width + $package_height ) * 2;

															if ( $girth + $package_length > 108 ) {
																continue 2;
															}

														} else {
															continue 2;
														}
													break;
												}
											}

											if ( is_null( $rate_cost ) ) {
												$rate_cost = $cost;
											} elseif ( $cost < $rate_cost ) {
												$rate_cost = $cost;
											}
										}
									}

									if ( $rate_cost ) {
										$this->prepare_rate( $rate_code, $rate_id, $rate_name, $rate_cost );
									}
								}

								$index++;
							}
						}

					} else {
						// No rates
						$this->debug( 'Invalid request; no rates returned', 'error' );
					}
				}
			}

			// Ensure rates were found for all packages
			if ( $this->found_rates ) {
				foreach ( $this->found_rates as $key => $value ) {
					if ( $value['packages'] < sizeof( $package_requests ) )
						unset( $this->found_rates[ $key ] );
				}
			}
		}

		// Flat Rate boxes quote
		if ( $this->enable_flat_rate_boxes == 'yes' || $this->enable_flat_rate_boxes == 'priority' ) {
			// Priority
			$flat_rate = $this->calculate_flat_rate_box_rate( $package, 'priority' );
			if ( $flat_rate )
				$this->found_rates[ $flat_rate['id'] ] = $flat_rate;
		}
		if ( $this->enable_flat_rate_boxes == 'yes' || $this->enable_flat_rate_boxes == 'express' ) {
			// Express
			$flat_rate = $this->calculate_flat_rate_box_rate( $package, 'express' );
			if ( $flat_rate )
				$this->found_rates[ $flat_rate['id'] ] = $flat_rate;
		}

		// Add rates
		if ( $this->found_rates ) {

			if ( $this->offer_rates == 'all' ) {

				uasort( $this->found_rates, array( $this, 'sort_rates' ) );

				foreach ( $this->found_rates as $key => $rate ) {
					$this->add_rate( $rate );
				}

			} else {

				$cheapest_rate = '';

				foreach ( $this->found_rates as $key => $rate ) {
					if ( ! $cheapest_rate || $cheapest_rate['cost'] > $rate['cost'] )
						$cheapest_rate = $rate;
				}

				$cheapest_rate['label'] = $this->title;

				$this->add_rate( $cheapest_rate );

			}

		// Fallback
		} elseif ( $this->fallback ) {
			$this->add_rate( array(
				'id' 	=> $this->id . '_fallback',
				'label' => $this->title,
				'cost' 	=> $this->fallback,
				'sort'  => 0
			) );
		}

    }

    /**
     * prepare_rate function.
     *
     * @access private
     * @param mixed $rate_code
     * @param mixed $rate_id
     * @param mixed $rate_name
     * @param mixed $rate_cost
     * @return void
     */
    private function prepare_rate( $rate_code, $rate_id, $rate_name, $rate_cost ) {

	    // Name adjustment
		if ( ! empty( $this->custom_services[ $rate_code ]['name'] ) )
			$rate_name = $this->custom_services[ $rate_code ]['name'];

		// Merging
		if ( isset( $this->found_rates[ $rate_id ] ) ) {
			$rate_cost = $rate_cost + $this->found_rates[ $rate_id ]['cost'];
			$packages  = 1 + $this->found_rates[ $rate_id ]['packages'];
		} else {
			$packages = 1;
		}

		// Sort
		if ( isset( $this->custom_services[ $rate_code ]['order'] ) ) {
			$sort = $this->custom_services[ $rate_code ]['order'];
		} else {
			$sort = 999;
		}

		$this->found_rates[ $rate_id ] = array(
			'id'       => $rate_id,
			'label'    => $rate_name,
			'cost'     => $rate_cost,
			'sort'     => $sort,
			'packages' => $packages
		);
    }

    /**
     * sort_rates function.
     *
     * @access public
     * @param mixed $a
     * @param mixed $b
     * @return void
     */
    public function sort_rates( $a, $b ) {
		if ( $a['sort'] == $b['sort'] ) return 0;
		return ( $a['sort'] < $b['sort'] ) ? -1 : 1;
    }

    /**
     * get_request function.
     *
     * @access private
     * @return void
     */
    private function get_package_requests( $package ) {

	    // Choose selected packing
    	switch ( $this->packing_method ) {
	    	case 'box_packing' :
	    		$requests = $this->box_shipping( $package );
	    	break;
	    	case 'weight_based' :
	    		$requests = $this->weight_based_shipping( $package );
	    	break;
	    	case 'per_item' :
	    	default :
	    		$requests = $this->per_item_shipping( $package );
	    	break;
    	}

    	return $requests;
    }

    /**
     * per_item_shipping function.
     *
     * @access private
     * @param mixed $package
     * @return void
     */
    private function per_item_shipping( $package ) {
	    global $woocommerce;

	    $requests = array();
	    $domestic = in_array( $package['destination']['country'], $this->domestic ) ? true : false;

    	// Get weight of order
    	foreach ( $package['contents'] as $item_id => $values ) {

    		if ( ! $values['data']->needs_shipping() ) {
    			$this->debug( sprintf( __( 'Product # is virtual. Skipping.', 'wc_usps' ), $item_id ) );
    			continue;
    		}

    		if ( ! $values['data']->get_weight() ) {
	    		$this->debug( sprintf( __( 'Product # is missing weight. Using 1lb.', 'wc_usps' ), $item_id ) );

	    		$weight = 1;
    		} else {
    			$weight = woocommerce_get_weight( $values['data']->get_weight(), 'lbs' );
    		}

    		$size   = 'REGULAR';

    		if ( $values['data']->length && $values['data']->height && $values['data']->width ) {

				$dimensions = array( woocommerce_get_dimension( $values['data']->length, 'in' ), woocommerce_get_dimension( $values['data']->height, 'in' ), woocommerce_get_dimension( $values['data']->width, 'in' ) );

				sort( $dimensions );

				if ( max( $dimensions ) > 12 ) {
					$size   = 'LARGE';
				}

				$girth = $dimensions[0] + $dimensions[0] + $dimensions[1] + $dimensions[1];
			} else {
				$dimensions = array( 0, 0, 0 );
				$girth      = 0;
			}

			if ( $domestic ) {

				$request  = '<Package ID="' . $this->generate_package_id( $item_id, $values['quantity'], $dimensions[2], $dimensions[1], $dimensions[0], $weight ) . '">' . "\n";
				$request .= '	<Service>' . ( ! $this->settings['shippingrates'] ? 'ONLINE' : $this->settings['shippingrates'] ) . '</Service>' . "\n";
				$request .= '	<ZipOrigination>' . str_replace( ' ', '', strtoupper( $this->origin ) ) . '</ZipOrigination>' . "\n";
				$request .= '	<ZipDestination>' . strtoupper( substr( $package['destination']['postcode'], 0, 5 ) ) . '</ZipDestination>' . "\n";
				$request .= '	<Pounds>' . floor( $weight ) . '</Pounds>' . "\n";
				$request .= '	<Ounces>' . number_format( ( $weight - floor( $weight ) ) * 16, 2 ) . '</Ounces>' . "\n";
				$request .= '	<Container />' . "\n";
				$request .= '	<Size>' . $size . '</Size>' . "\n";
				$request .= '	<Width>' . $dimensions[1] . '</Width>' . "\n";
				$request .= '	<Length>' . $dimensions[2] . '</Length>' . "\n";
				$request .= '	<Height>' . $dimensions[0] . '</Height>' . "\n";
				$request .= '	<Girth>' . round( $girth ) . '</Girth>' . "\n";
				$request .= '	<Machinable>true</Machinable> ' . "\n";
				$request .= '	<ShipDate>' . date( "d-M-Y", ( current_time('timestamp') + (60 * 60 * 24) ) ) . '</ShipDate>' . "\n";
				$request .= '</Package>' . "\n";

			} else {

				$request  = '<Package ID="' . $this->generate_package_id( $item_id, $values['quantity'], $dimensions[2], $dimensions[1], $dimensions[0], $weight ) . '">' . "\n";
				$request .= '	<Pounds>' . floor( $weight ) . '</Pounds>' . "\n";
				$request .= '	<Ounces>' . number_format( ( $weight - floor( $weight ) ) * 16, 2 ) . '</Ounces>' . "\n";
				$request .= '	<Machinable>true</Machinable> ' . "\n";
				$request .= '	<MailType>Package</MailType>' . "\n";
				$request .= '	<ValueOfContents>' . $values['data']->get_price() . '</ValueOfContents>' . "\n";
				$request .= '	<Country>' . $this->get_country_name( $package['destination']['country'] ) . '</Country>' . "\n";
				$request .= '	<Container>RECTANGULAR</Container>' . "\n";
				$request .= '	<Size>' . $size . '</Size>' . "\n";
				$request .= '	<Width>' . $dimensions[1] . '</Width>' . "\n";
				$request .= '	<Length>' . $dimensions[2] . '</Length>' . "\n";
				$request .= '	<Height>' . $dimensions[0] . '</Height>' . "\n";
				$request .= '	<Girth>' . round( $girth ) . '</Girth>' . "\n";
				$request .= '	<OriginZip>' . str_replace( ' ', '', strtoupper( $this->origin ) ) . '</OriginZip>' . "\n";
				$request .= '	<CommercialFlag>' . ( $this->settings['shippingrates'] == "ONLINE" ? 'Y' : 'N' ) . '</CommercialFlag>' . "\n";
				$request .= '</Package>' . "\n";

			}

			$requests[] = $request;
    	}

		return $requests;
    }

    /**
     * Generate shipping request for weights only
     * @param  array $package
     * @return array
     */
    private function weight_based_shipping( $package ) {
    	global $woocommerce;

		$requests                  = array();
		$domestic                  = in_array( $package['destination']['country'], $this->domestic ) ? true : false;
		$total_regular_item_weight = 0;

    	// Add requests for larger items
    	foreach ( $package['contents'] as $item_id => $values ) {

    		if ( ! $values['data']->needs_shipping() ) {
    			$this->debug( sprintf( __( 'Product #%d is virtual. Skipping.', 'wc_usps' ), $item_id ) );
    			continue;
    		}

    		if ( ! $values['data']->get_weight() ) {
	    		$this->debug( sprintf( __( 'Product #%d is missing weight. Using 1lb.', 'wc_usps' ), $item_id ), 'error' );

	    		$weight = 1;
    		} else {
    			$weight = woocommerce_get_weight( $values['data']->get_weight(), 'lbs' );
    		}

			$dimensions = array( woocommerce_get_dimension( $values['data']->length, 'in' ), woocommerce_get_dimension( $values['data']->height, 'in' ), woocommerce_get_dimension( $values['data']->width, 'in' ) );

			sort( $dimensions );

			if ( max( $dimensions ) <= 12 ) {
				$total_regular_item_weight += ( $weight * $values['quantity'] );
    			continue;
			}

			$girth = $dimensions[0] + $dimensions[0] + $dimensions[1] + $dimensions[1];

			if ( $domestic ) {
				$request  = '<Package ID="' . $this->generate_package_id( $item_id, $values['quantity'], $dimensions[2], $dimensions[1], $dimensions[0], $weight ) . '">' . "\n";
				$request .= '	<Service>' . ( !$this->settings['shippingrates'] ? 'ONLINE' : $this->settings['shippingrates'] ) . '</Service>' . "\n";
				$request .= '	<ZipOrigination>' . str_replace( ' ', '', strtoupper( $this->origin ) ) . '</ZipOrigination>' . "\n";
				$request .= '	<ZipDestination>' . strtoupper( substr( $package['destination']['postcode'], 0, 5 ) ) . '</ZipDestination>' . "\n";
				$request .= '	<Pounds>' . floor( $weight ) . '</Pounds>' . "\n";
				$request .= '	<Ounces>' . number_format( ( $weight - floor( $weight ) ) * 16, 2 ) . '</Ounces>' . "\n";
				$request .= '	<Container />' . "\n";
				$request .= '	<Size>LARGE</Size>' . "\n";
				$request .= '	<Width>' . $dimensions[1] . '</Width>' . "\n";
				$request .= '	<Length>' . $dimensions[2] . '</Length>' . "\n";
				$request .= '	<Height>' . $dimensions[0] . '</Height>' . "\n";
				$request .= '	<Girth>' . round( $girth ) . '</Girth>' . "\n";
				$request .= '	<Machinable>true</Machinable> ' . "\n";
				$request .= '	<ShipDate>' . date( "d-M-Y", ( current_time('timestamp') + (60 * 60 * 24) ) ) . '</ShipDate>' . "\n";
				$request .= '</Package>' . "\n";
			} else {
				$request  = '<Package ID="' . $this->generate_package_id( $item_id, $values['quantity'], $dimensions[2], $dimensions[1], $dimensions[0], $weight ) . '">' . "\n";
				$request .= '	<Pounds>' . floor( $weight ) . '</Pounds>' . "\n";
				$request .= '	<Ounces>' . number_format( ( $weight - floor( $weight ) ) * 16, 2 ) . '</Ounces>' . "\n";
				$request .= '	<Machinable>true</Machinable> ' . "\n";
				$request .= '	<MailType>Package</MailType>' . "\n";
				$request .= '	<ValueOfContents>' . $values['data']->get_price() . '</ValueOfContents>' . "\n";
				$request .= '	<Country>' . $this->get_country_name( $package['destination']['country'] ) . '</Country>' . "\n";
				$request .= '	<Container>RECTANGULAR</Container>' . "\n";
				$request .= '	<Size>LARGE</Size>' . "\n";
				$request .= '	<Width>' . $dimensions[1] . '</Width>' . "\n";
				$request .= '	<Length>' . $dimensions[2] . '</Length>' . "\n";
				$request .= '	<Height>' . $dimensions[0] . '</Height>' . "\n";
				$request .= '	<Girth>' . round( $girth ) . '</Girth>' . "\n";
				$request .= '	<OriginZip>' . str_replace( ' ', '', strtoupper( $this->origin ) ) . '</OriginZip>' . "\n";
				$request .= '	<CommercialFlag>' . ( $this->settings['shippingrates'] == "ONLINE" ? 'Y' : 'N' ) . '</CommercialFlag>' . "\n";
				$request .= '</Package>' . "\n";
			}

			$requests[] = $request;
    	}

    	// Regular package
    	if ( $total_regular_item_weight > 0 ) {
    		$max_package_weight = ( $domestic || $package['destination']['country'] == 'MX' ) ? 70 : 44;
    		$package_weights    = array();

    		$full_packages      = floor( $total_regular_item_weight / $max_package_weight );
    		for ( $i = 0; $i < $full_packages; $i ++ )
    			$package_weights[] = $max_package_weight;

    		if ( $remainder = fmod( $total_regular_item_weight, $max_package_weight ) )
    			$package_weights[] = $remainder;

    		foreach ( $package_weights as $key => $weight ) {

				if ( $domestic ) {
					$request  = '<Package ID="' . $this->generate_package_id( 'regular_' . $key, 1, 0, 0, 0, 0 ) . '">' . "\n";
					$request .= '	<Service>' . ( !$this->settings['shippingrates'] ? 'ONLINE' : $this->settings['shippingrates'] ) . '</Service>' . "\n";
					$request .= '	<ZipOrigination>' . str_replace( ' ', '', strtoupper( $this->origin ) ) . '</ZipOrigination>' . "\n";
					$request .= '	<ZipDestination>' . strtoupper( substr( $package['destination']['postcode'], 0, 5 ) ) . '</ZipDestination>' . "\n";
					$request .= '	<Pounds>' . floor( $weight ) . '</Pounds>' . "\n";
					$request .= '	<Ounces>' . number_format( ( $weight - floor( $weight ) ) * 16, 2 ) . '</Ounces>' . "\n";
					$request .= '	<Container />' . "\n";
					$request .= '	<Size>REGULAR</Size>' . "\n";
					$request .= '	<Machinable>true</Machinable> ' . "\n";
					$request .= '	<ShipDate>' . date( "d-M-Y", ( current_time('timestamp') + (60 * 60 * 24) ) ) . '</ShipDate>' . "\n";
					$request .= '</Package>' . "\n";
				} else {
					$request  = '<Package ID="' . $this->generate_package_id( 'regular_' . $key, 1, 0, 0, 0, 0 ) . '">' . "\n";
					$request .= '	<Pounds>' . floor( $weight ) . '</Pounds>' . "\n";
					$request .= '	<Ounces>' . number_format( ( $weight - floor( $weight ) ) * 16, 2 ) . '</Ounces>' . "\n";
					$request .= '	<Machinable>true</Machinable> ' . "\n";
					$request .= '	<MailType>Package</MailType>' . "\n";
					$request .= '	<ValueOfContents>' . $values['data']->get_price() . '</ValueOfContents>' . "\n";
					$request .= '	<Country>' . $this->get_country_name( $package['destination']['country'] ) . '</Country>' . "\n";
					$request .= '	<Container />' . "\n";
					$request .= '	<Size>REGULAR</Size>' . "\n";
					$request .= '	<Width />' . "\n";
					$request .= '	<Length />' . "\n";
					$request .= '	<Height />' . "\n";
					$request .= '	<Girth />' . "\n";
					$request .= '	<OriginZip>' . str_replace( ' ', '', strtoupper( $this->origin ) ) . '</OriginZip>' . "\n";
					$request .= '	<CommercialFlag>' . ( $this->settings['shippingrates'] == "ONLINE" ? 'Y' : 'N' ) . '</CommercialFlag>' . "\n";
					$request .= '</Package>' . "\n";
				}

			}

			$requests[] = $request;
    	}

		return $requests;
    }

    /**
     * Generate a package ID for the request
     *
     * Contains qty and dimension info so we can look at it again later when it comes back from USPS if needed
     * 
     * @return string
     */
    public function generate_package_id( $id, $qty, $l, $w, $h, $w ) {
    	return implode( ':', array( $id, $qty, $l, $w, $h, $w ) );
    }

    /**
     * box_shipping function.
     *
     * @access private
     * @param mixed $package
     * @return void
     */
    private function box_shipping( $package ) {
	    global $woocommerce;

	    $requests = array();
	    $domestic = in_array( $package['destination']['country'], $this->domestic ) ? true : false;

	  	if ( ! class_exists( 'WC_Boxpack' ) ) {
	  		include_once 'box-packer/class-wc-boxpack.php';
	  	}

	    $boxpack = new WC_Boxpack();

	    // Define boxes
		foreach ( $this->boxes as $key => $box ) {

			$newbox = $boxpack->add_box( $box['outer_length'], $box['outer_width'], $box['outer_height'], $box['box_weight'] );

			$newbox->set_id( isset( $box['name'] ) ? $box['name'] : $key );
			$newbox->set_inner_dimensions( $box['inner_length'], $box['inner_width'], $box['inner_height'] );

			if ( $box['max_weight'] ) {
				$newbox->set_max_weight( $box['max_weight'] );
			}
		}

		// Add items
		foreach ( $package['contents'] as $item_id => $values ) {

			if ( ! $values['data']->needs_shipping() ) {
				continue;
			}

			if ( $values['data']->length && $values['data']->height && $values['data']->width && $values['data']->weight ) {

				$dimensions = array( $values['data']->length, $values['data']->height, $values['data']->width );

			} else {
				$this->debug( sprintf( __( 'Product #%d is missing dimensions. Using 1x1x1.', 'wc_usps' ), $item_id ), 'error' );

				$dimensions = array( 1, 1, 1 );
			}

			for ( $i = 0; $i < $values['quantity']; $i ++ ) {
				$boxpack->add_item(
					woocommerce_get_dimension( $dimensions[2], 'in' ),
					woocommerce_get_dimension( $dimensions[1], 'in' ),
					woocommerce_get_dimension( $dimensions[0], 'in' ),
					woocommerce_get_weight( $values['data']->get_weight(), 'lbs' ),
					$values['data']->get_price()
				);
			}
		}

		// Pack it
		$boxpack->pack();

		// Get packages
		$box_packages = $boxpack->get_packages();

		foreach ( $box_packages as $key => $box_package ) {

			$this->debug( 'Packed ' . $box_package->id );

			$weight     = $box_package->weight;
    		$size       = 'REGULAR';
    		$dimensions = array( $box_package->length, $box_package->width, $box_package->height );

			sort( $dimensions );

			if ( max( $dimensions ) > 12 ) {
				$size   = 'LARGE';
			}

			$girth = $dimensions[0] + $dimensions[0] + $dimensions[1] + $dimensions[1];

			if ( $domestic ) {

				$request  = '<Package ID="' . $this->generate_package_id( $key, 1, $dimensions[2], $dimensions[1], $dimensions[0], $weight ) . '">' . "\n";
				$request .= '	<Service>' . ( ! $this->settings['shippingrates'] ? 'ONLINE' : $this->settings['shippingrates'] ) . '</Service>' . "\n";
				$request .= '	<ZipOrigination>' . str_replace( ' ', '', strtoupper( $this->origin ) ) . '</ZipOrigination>' . "\n";
				$request .= '	<ZipDestination>' . strtoupper( substr( $package['destination']['postcode'], 0, 5 ) ) . '</ZipDestination>' . "\n";
				$request .= '	<Pounds>' . floor( $weight ) . '</Pounds>' . "\n";
				$request .= '	<Ounces>' . number_format( ( $weight - floor( $weight ) ) * 16, 2 ) . '</Ounces>' . "\n";
				$request .= '	<Container />' . "\n";
				$request .= '	<Size>' . $size . '</Size>' . "\n";
				$request .= '	<Width>' . $dimensions[1] . '</Width>' . "\n";
				$request .= '	<Length>' . $dimensions[2] . '</Length>' . "\n";
				$request .= '	<Height>' . $dimensions[0] . '</Height>' . "\n";
				$request .= '	<Girth>' . round( $girth ) . '</Girth>' . "\n";
				$request .= '	<Machinable>true</Machinable> ' . "\n";
				$request .= '	<ShipDate>' . date( "d-M-Y", ( current_time('timestamp') + (60 * 60 * 24) ) ) . '</ShipDate>' . "\n";
				$request .= '</Package>' . "\n";

			} else {

				$request  = '<Package ID="' . $this->generate_package_id( $key, 1, $dimensions[2], $dimensions[1], $dimensions[0], $weight ) . '">' . "\n";
				$request .= '	<Pounds>' . floor( $weight ) . '</Pounds>' . "\n";
				$request .= '	<Ounces>' . number_format( ( $weight - floor( $weight ) ) * 16, 2 ) . '</Ounces>' . "\n";
				$request .= '	<Machinable>true</Machinable> ' . "\n";
				$request .= '	<MailType>' . ( empty( $this->boxes[ $box_package->id ]['is_letter'] ) ? 'PACKAGE' : 'ENVELOPE' ) . '</MailType>' . "\n";
				$request .= '	<ValueOfContents>' . $values['data']->get_price() . '</ValueOfContents>' . "\n";
				$request .= '	<Country>' . $this->get_country_name( $package['destination']['country'] ) . '</Country>' . "\n";
				$request .= '	<Container>RECTANGULAR</Container>' . "\n";
				$request .= '	<Size>' . $size . '</Size>' . "\n";
				$request .= '	<Width>' . $dimensions[1] . '</Width>' . "\n";
				$request .= '	<Length>' . $dimensions[2] . '</Length>' . "\n";
				$request .= '	<Height>' . $dimensions[0] . '</Height>' . "\n";
				$request .= '	<Girth>' . round( $girth ) . '</Girth>' . "\n";
				$request .= '	<OriginZip>' . str_replace( ' ', '', strtoupper( $this->origin ) ) . '</OriginZip>' . "\n";
				$request .= '	<CommercialFlag>' . ( $this->settings['shippingrates'] == "ONLINE" ? 'Y' : 'N' ) . '</CommercialFlag>' . "\n";
				$request .= '</Package>' . "\n";

			}

    		$requests[] = $request;
		}

		return $requests;
    }

    /**
     * get_country_name function.
     *
     * @access private
     * @return void
     */
    private function get_country_name( $code ) {
		$countries = apply_filters( 'usps_countries', array(
			'AF' => __( 'Afghanistan', 'wc_usps' ),
			'AX' => __( '&#197;land Islands', 'wc_usps' ),
			'AL' => __( 'Albania', 'wc_usps' ),
			'DZ' => __( 'Algeria', 'wc_usps' ),
			'AD' => __( 'Andorra', 'wc_usps' ),
			'AO' => __( 'Angola', 'wc_usps' ),
			'AI' => __( 'Anguilla', 'wc_usps' ),
			'AQ' => __( 'Antarctica', 'wc_usps' ),
			'AG' => __( 'Antigua and Barbuda', 'wc_usps' ),
			'AR' => __( 'Argentina', 'wc_usps' ),
			'AM' => __( 'Armenia', 'wc_usps' ),
			'AW' => __( 'Aruba', 'wc_usps' ),
			'AU' => __( 'Australia', 'wc_usps' ),
			'AT' => __( 'Austria', 'wc_usps' ),
			'AZ' => __( 'Azerbaijan', 'wc_usps' ),
			'BS' => __( 'Bahamas', 'wc_usps' ),
			'BH' => __( 'Bahrain', 'wc_usps' ),
			'BD' => __( 'Bangladesh', 'wc_usps' ),
			'BB' => __( 'Barbados', 'wc_usps' ),
			'BY' => __( 'Belarus', 'wc_usps' ),
			'BE' => __( 'Belgium', 'wc_usps' ),
			'PW' => __( 'Belau', 'wc_usps' ),
			'BZ' => __( 'Belize', 'wc_usps' ),
			'BJ' => __( 'Benin', 'wc_usps' ),
			'BM' => __( 'Bermuda', 'wc_usps' ),
			'BT' => __( 'Bhutan', 'wc_usps' ),
			'BO' => __( 'Bolivia', 'wc_usps' ),
			'BQ' => __( 'Bonaire, Saint Eustatius and Saba', 'wc_usps' ),
			'BA' => __( 'Bosnia and Herzegovina', 'wc_usps' ),
			'BW' => __( 'Botswana', 'wc_usps' ),
			'BV' => __( 'Bouvet Island', 'wc_usps' ),
			'BR' => __( 'Brazil', 'wc_usps' ),
			'IO' => __( 'British Indian Ocean Territory', 'wc_usps' ),
			'VG' => __( 'British Virgin Islands', 'wc_usps' ),
			'BN' => __( 'Brunei', 'wc_usps' ),
			'BG' => __( 'Bulgaria', 'wc_usps' ),
			'BF' => __( 'Burkina Faso', 'wc_usps' ),
			'BI' => __( 'Burundi', 'wc_usps' ),
			'KH' => __( 'Cambodia', 'wc_usps' ),
			'CM' => __( 'Cameroon', 'wc_usps' ),
			'CA' => __( 'Canada', 'wc_usps' ),
			'CV' => __( 'Cape Verde', 'wc_usps' ),
			'KY' => __( 'Cayman Islands', 'wc_usps' ),
			'CF' => __( 'Central African Republic', 'wc_usps' ),
			'TD' => __( 'Chad', 'wc_usps' ),
			'CL' => __( 'Chile', 'wc_usps' ),
			'CN' => __( 'China', 'wc_usps' ),
			'CX' => __( 'Christmas Island', 'wc_usps' ),
			'CC' => __( 'Cocos (Keeling) Islands', 'wc_usps' ),
			'CO' => __( 'Colombia', 'wc_usps' ),
			'KM' => __( 'Comoros', 'wc_usps' ),
			'CG' => __( 'Congo (Brazzaville)', 'wc_usps' ),
			'CD' => __( 'Congo (Kinshasa)', 'wc_usps' ),
			'CK' => __( 'Cook Islands', 'wc_usps' ),
			'CR' => __( 'Costa Rica', 'wc_usps' ),
			'HR' => __( 'Croatia', 'wc_usps' ),
			'CU' => __( 'Cuba', 'wc_usps' ),
			'CW' => __( 'Cura&Ccedil;ao', 'wc_usps' ),
			'CY' => __( 'Cyprus', 'wc_usps' ),
			'CZ' => __( 'Czech Republic', 'wc_usps' ),
			'DK' => __( 'Denmark', 'wc_usps' ),
			'DJ' => __( 'Djibouti', 'wc_usps' ),
			'DM' => __( 'Dominica', 'wc_usps' ),
			'DO' => __( 'Dominican Republic', 'wc_usps' ),
			'EC' => __( 'Ecuador', 'wc_usps' ),
			'EG' => __( 'Egypt', 'wc_usps' ),
			'SV' => __( 'El Salvador', 'wc_usps' ),
			'GQ' => __( 'Equatorial Guinea', 'wc_usps' ),
			'ER' => __( 'Eritrea', 'wc_usps' ),
			'EE' => __( 'Estonia', 'wc_usps' ),
			'ET' => __( 'Ethiopia', 'wc_usps' ),
			'FK' => __( 'Falkland Islands', 'wc_usps' ),
			'FO' => __( 'Faroe Islands', 'wc_usps' ),
			'FJ' => __( 'Fiji', 'wc_usps' ),
			'FI' => __( 'Finland', 'wc_usps' ),
			'FR' => __( 'France', 'wc_usps' ),
			'GF' => __( 'French Guiana', 'wc_usps' ),
			'PF' => __( 'French Polynesia', 'wc_usps' ),
			'TF' => __( 'French Southern Territories', 'wc_usps' ),
			'GA' => __( 'Gabon', 'wc_usps' ),
			'GM' => __( 'Gambia', 'wc_usps' ),
			'GE' => __( 'Georgia', 'wc_usps' ),
			'DE' => __( 'Germany', 'wc_usps' ),
			'GH' => __( 'Ghana', 'wc_usps' ),
			'GI' => __( 'Gibraltar', 'wc_usps' ),
			'GR' => __( 'Greece', 'wc_usps' ),
			'GL' => __( 'Greenland', 'wc_usps' ),
			'GD' => __( 'Grenada', 'wc_usps' ),
			'GP' => __( 'Guadeloupe', 'wc_usps' ),
			'GT' => __( 'Guatemala', 'wc_usps' ),
			'GG' => __( 'Guernsey', 'wc_usps' ),
			'GN' => __( 'Guinea', 'wc_usps' ),
			'GW' => __( 'Guinea-Bissau', 'wc_usps' ),
			'GY' => __( 'Guyana', 'wc_usps' ),
			'HT' => __( 'Haiti', 'wc_usps' ),
			'HM' => __( 'Heard Island and McDonald Islands', 'wc_usps' ),
			'HN' => __( 'Honduras', 'wc_usps' ),
			'HK' => __( 'Hong Kong', 'wc_usps' ),
			'HU' => __( 'Hungary', 'wc_usps' ),
			'IS' => __( 'Iceland', 'wc_usps' ),
			'IN' => __( 'India', 'wc_usps' ),
			'ID' => __( 'Indonesia', 'wc_usps' ),
			'IR' => __( 'Iran', 'wc_usps' ),
			'IQ' => __( 'Iraq', 'wc_usps' ),
			'IE' => __( 'Ireland', 'wc_usps' ),
			'IM' => __( 'Isle of Man', 'wc_usps' ),
			'IL' => __( 'Israel', 'wc_usps' ),
			'IT' => __( 'Italy', 'wc_usps' ),
			'CI' => __( 'Ivory Coast', 'wc_usps' ),
			'JM' => __( 'Jamaica', 'wc_usps' ),
			'JP' => __( 'Japan', 'wc_usps' ),
			'JE' => __( 'Jersey', 'wc_usps' ),
			'JO' => __( 'Jordan', 'wc_usps' ),
			'KZ' => __( 'Kazakhstan', 'wc_usps' ),
			'KE' => __( 'Kenya', 'wc_usps' ),
			'KI' => __( 'Kiribati', 'wc_usps' ),
			'KW' => __( 'Kuwait', 'wc_usps' ),
			'KG' => __( 'Kyrgyzstan', 'wc_usps' ),
			'LA' => __( 'Laos', 'wc_usps' ),
			'LV' => __( 'Latvia', 'wc_usps' ),
			'LB' => __( 'Lebanon', 'wc_usps' ),
			'LS' => __( 'Lesotho', 'wc_usps' ),
			'LR' => __( 'Liberia', 'wc_usps' ),
			'LY' => __( 'Libya', 'wc_usps' ),
			'LI' => __( 'Liechtenstein', 'wc_usps' ),
			'LT' => __( 'Lithuania', 'wc_usps' ),
			'LU' => __( 'Luxembourg', 'wc_usps' ),
			'MO' => __( 'Macao S.A.R., China', 'wc_usps' ),
			'MK' => __( 'Macedonia', 'wc_usps' ),
			'MG' => __( 'Madagascar', 'wc_usps' ),
			'MW' => __( 'Malawi', 'wc_usps' ),
			'MY' => __( 'Malaysia', 'wc_usps' ),
			'MV' => __( 'Maldives', 'wc_usps' ),
			'ML' => __( 'Mali', 'wc_usps' ),
			'MT' => __( 'Malta', 'wc_usps' ),
			'MH' => __( 'Marshall Islands', 'wc_usps' ),
			'MQ' => __( 'Martinique', 'wc_usps' ),
			'MR' => __( 'Mauritania', 'wc_usps' ),
			'MU' => __( 'Mauritius', 'wc_usps' ),
			'YT' => __( 'Mayotte', 'wc_usps' ),
			'MX' => __( 'Mexico', 'wc_usps' ),
			'FM' => __( 'Micronesia', 'wc_usps' ),
			'MD' => __( 'Moldova', 'wc_usps' ),
			'MC' => __( 'Monaco', 'wc_usps' ),
			'MN' => __( 'Mongolia', 'wc_usps' ),
			'ME' => __( 'Montenegro', 'wc_usps' ),
			'MS' => __( 'Montserrat', 'wc_usps' ),
			'MA' => __( 'Morocco', 'wc_usps' ),
			'MZ' => __( 'Mozambique', 'wc_usps' ),
			'MM' => __( 'Myanmar', 'wc_usps' ),
			'NA' => __( 'Namibia', 'wc_usps' ),
			'NR' => __( 'Nauru', 'wc_usps' ),
			'NP' => __( 'Nepal', 'wc_usps' ),
			'NL' => __( 'Netherlands', 'wc_usps' ),
			'AN' => __( 'Netherlands Antilles', 'wc_usps' ),
			'NC' => __( 'New Caledonia', 'wc_usps' ),
			'NZ' => __( 'New Zealand', 'wc_usps' ),
			'NI' => __( 'Nicaragua', 'wc_usps' ),
			'NE' => __( 'Niger', 'wc_usps' ),
			'NG' => __( 'Nigeria', 'wc_usps' ),
			'NU' => __( 'Niue', 'wc_usps' ),
			'NF' => __( 'Norfolk Island', 'wc_usps' ),
			'KP' => __( 'North Korea', 'wc_usps' ),
			'NO' => __( 'Norway', 'wc_usps' ),
			'OM' => __( 'Oman', 'wc_usps' ),
			'PK' => __( 'Pakistan', 'wc_usps' ),
			'PS' => __( 'Palestinian Territory', 'wc_usps' ),
			'PA' => __( 'Panama', 'wc_usps' ),
			'PG' => __( 'Papua New Guinea', 'wc_usps' ),
			'PY' => __( 'Paraguay', 'wc_usps' ),
			'PE' => __( 'Peru', 'wc_usps' ),
			'PH' => __( 'Philippines', 'wc_usps' ),
			'PN' => __( 'Pitcairn', 'wc_usps' ),
			'PL' => __( 'Poland', 'wc_usps' ),
			'PT' => __( 'Portugal', 'wc_usps' ),
			'QA' => __( 'Qatar', 'wc_usps' ),
			'RE' => __( 'Reunion', 'wc_usps' ),
			'RO' => __( 'Romania', 'wc_usps' ),
			'RU' => __( 'Russia', 'wc_usps' ),
			'RW' => __( 'Rwanda', 'wc_usps' ),
			'BL' => __( 'Saint Barth&eacute;lemy', 'wc_usps' ),
			'SH' => __( 'Saint Helena', 'wc_usps' ),
			'KN' => __( 'Saint Kitts and Nevis', 'wc_usps' ),
			'LC' => __( 'Saint Lucia', 'wc_usps' ),
			'MF' => __( 'Saint Martin (French part)', 'wc_usps' ),
			'SX' => __( 'Saint Martin (Dutch part)', 'wc_usps' ),
			'PM' => __( 'Saint Pierre and Miquelon', 'wc_usps' ),
			'VC' => __( 'Saint Vincent and the Grenadines', 'wc_usps' ),
			'SM' => __( 'San Marino', 'wc_usps' ),
			'ST' => __( 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe', 'wc_usps' ),
			'SA' => __( 'Saudi Arabia', 'wc_usps' ),
			'SN' => __( 'Senegal', 'wc_usps' ),
			'RS' => __( 'Serbia', 'wc_usps' ),
			'SC' => __( 'Seychelles', 'wc_usps' ),
			'SL' => __( 'Sierra Leone', 'wc_usps' ),
			'SG' => __( 'Singapore', 'wc_usps' ),
			'SK' => __( 'Slovakia', 'wc_usps' ),
			'SI' => __( 'Slovenia', 'wc_usps' ),
			'SB' => __( 'Solomon Islands', 'wc_usps' ),
			'SO' => __( 'Somalia', 'wc_usps' ),
			'ZA' => __( 'South Africa', 'wc_usps' ),
			'GS' => __( 'South Georgia/Sandwich Islands', 'wc_usps' ),
			'KR' => __( 'South Korea', 'wc_usps' ),
			'SS' => __( 'South Sudan', 'wc_usps' ),
			'ES' => __( 'Spain', 'wc_usps' ),
			'LK' => __( 'Sri Lanka', 'wc_usps' ),
			'SD' => __( 'Sudan', 'wc_usps' ),
			'SR' => __( 'Suriname', 'wc_usps' ),
			'SJ' => __( 'Svalbard and Jan Mayen', 'wc_usps' ),
			'SZ' => __( 'Swaziland', 'wc_usps' ),
			'SE' => __( 'Sweden', 'wc_usps' ),
			'CH' => __( 'Switzerland', 'wc_usps' ),
			'SY' => __( 'Syria', 'wc_usps' ),
			'TW' => __( 'Taiwan', 'wc_usps' ),
			'TJ' => __( 'Tajikistan', 'wc_usps' ),
			'TZ' => __( 'Tanzania', 'wc_usps' ),
			'TH' => __( 'Thailand', 'wc_usps' ),
			'TL' => __( 'Timor-Leste', 'wc_usps' ),
			'TG' => __( 'Togo', 'wc_usps' ),
			'TK' => __( 'Tokelau', 'wc_usps' ),
			'TO' => __( 'Tonga', 'wc_usps' ),
			'TT' => __( 'Trinidad and Tobago', 'wc_usps' ),
			'TN' => __( 'Tunisia', 'wc_usps' ),
			'TR' => __( 'Turkey', 'wc_usps' ),
			'TM' => __( 'Turkmenistan', 'wc_usps' ),
			'TC' => __( 'Turks and Caicos Islands', 'wc_usps' ),
			'TV' => __( 'Tuvalu', 'wc_usps' ),
			'UG' => __( 'Uganda', 'wc_usps' ),
			'UA' => __( 'Ukraine', 'wc_usps' ),
			'AE' => __( 'United Arab Emirates', 'wc_usps' ),
			'GB' => __( 'United Kingdom', 'wc_usps' ),
			'US' => __( 'United States', 'wc_usps' ),
			'UY' => __( 'Uruguay', 'wc_usps' ),
			'UZ' => __( 'Uzbekistan', 'wc_usps' ),
			'VU' => __( 'Vanuatu', 'wc_usps' ),
			'VA' => __( 'Vatican', 'wc_usps' ),
			'VE' => __( 'Venezuela', 'wc_usps' ),
			'VN' => __( 'Vietnam', 'wc_usps' ),
			'WF' => __( 'Wallis and Futuna', 'wc_usps' ),
			'EH' => __( 'Western Sahara', 'wc_usps' ),
			'WS' => __( 'Western Samoa', 'wc_usps' ),
			'YE' => __( 'Yemen', 'wc_usps' ),
			'ZM' => __( 'Zambia', 'wc_usps' ),
			'ZW' => __( 'Zimbabwe', 'woocommerce' )
		));

	    if ( isset( $countries[ $code ] ) ) {
		    return strtoupper( $countries[ $code ] );
	    } else {
		    return false;
	    }
    }

    /**
     * calculate_flat_rate_box_rate function.
     *
     * @access private
     * @param mixed $package
     * @return void
     */
    private function calculate_flat_rate_box_rate( $package, $box_type = 'priority' ) {
	    global $woocommerce;

	    $this->debug( 'Calculating USPS Flat Rate Boxes' );

	    $cost = 0;

	  	if ( ! class_exists( 'WC_Boxpack' ) )
	  		include_once 'box-packer/class-wc-boxpack.php';

	    $boxpack  = new WC_Boxpack();
	    $domestic = in_array( $package['destination']['country'], $this->domestic ) ? true : false;

	    // Define boxes
		foreach ( $this->flat_rate_boxes as $service_code => $box ) {

			if ( $box['type'] != $box_type )
				continue;

			$domestic_service = substr( $service_code, 0, 1 ) == 'd' ? true : false;

			if ( $domestic && $domestic_service || ! $domestic && ! $domestic_service ) {
				$newbox = $boxpack->add_box( $box['length'], $box['width'], $box['height'] );
				$newbox->set_max_weight( $box['weight'] );
				$newbox->set_id( $service_code );

				$this->debug( 'Adding box: ' . $service_code . ' ' . $box['name'] . ' - ' . $box['length'] . 'x' . $box['width'] . 'x' . $box['height'] );
			}
		}

		// Add items
		foreach ( $package['contents'] as $item_id => $values ) {

			if ( ! $values['data']->needs_shipping() )
				continue;

			if ( $values['data']->length && $values['data']->height && $values['data']->width && $values['data']->weight ) {

				$dimensions = array( $values['data']->length, $values['data']->height, $values['data']->width );

			} else {
				$this->debug( sprintf( __( 'Product #%d is missing dimensions! Using 1x1x1.', 'wc_usps' ), $item_id ), 'error' );

				$dimensions = array( 1, 1, 1 );
			}

			for ( $i = 0; $i < $values['quantity']; $i ++ ) {
				$boxpack->add_item(
					woocommerce_get_dimension( $dimensions[2], 'in' ),
					woocommerce_get_dimension( $dimensions[1], 'in' ),
					woocommerce_get_dimension( $dimensions[0], 'in' ),
					woocommerce_get_weight( $values['data']->get_weight(), 'lbs' ),
					$values['data']->get_price()
				);
			}
		}

		// Pack it
		$boxpack->pack();

		// Get packages
		$flat_packages = $boxpack->get_packages();

		if ( $flat_packages ) {
			foreach ( $flat_packages as $flat_package ) {

				if ( isset( $this->flat_rate_boxes[ $flat_package->id ] ) ) {

					$this->debug( 'Packed ' . $flat_package->id );

					// Get pricing
					$box_pricing  = $this->settings['shippingrates'] == 'ONLINE' && isset( $this->flat_rate_pricing[ $flat_package->id ]['online'] ) ? $this->flat_rate_pricing[ $flat_package->id ]['online'] : $this->flat_rate_pricing[ $flat_package->id ]['retail'];

					if ( is_array( $box_pricing ) ) {
						if ( isset( $box_pricing[ $package['destination']['country'] ] ) ) {
							$box_cost = $box_pricing[ $package['destination']['country'] ];
						} else {
							$box_cost = $box_pricing['*'];
						}
					} else {
						$box_cost = $box_pricing;
					}

					// Fees
					if ( ! empty( $this->flat_rate_fee ) ) {
						$sym = substr( $this->flat_rate_fee, 0, 1 );
						$fee = $sym == '-' ? substr( $this->flat_rate_fee, 1 ) : $this->flat_rate_fee;

						if ( strstr( $fee, '%' ) ) {
							$fee = str_replace( '%', '', $fee );

							if ( $sym == '-' )
								$box_cost = $box_cost - ( $box_cost * ( floatval( $fee ) / 100 ) );
							else
								$box_cost = $box_cost + ( $box_cost * ( floatval( $fee ) / 100 ) );
						} else {
							if ( $sym == '-' )
								$box_cost = $box_cost - $fee;
							else
								$box_cost += $fee;
						}

						if ( $box_cost < 0 )
							$box_cost = 0;
					}

					$cost += $box_cost;

				} else {
					return; // no match
				}

			}

			if ( $box_type == 'express' ) {
				$label = ! empty( $this->settings['flat_rate_express_title'] ) ? $this->settings['flat_rate_express_title'] : 'Priority Mail Express Flat Rate&#0174;';
			} else {
				$label = ! empty( $this->settings['flat_rate_priority_title'] ) ? $this->settings['flat_rate_priority_title'] : 'Priority Mail Flat Rate&#0174;';
			}

			return array(
				'id' 	=> $this->id . ':flat_rate_box_' . $box_type,
				'label' => $label,
				'cost' 	=> $cost,
				'sort'  => ( $box_type == 'express' ? -1 : -2 )
			);
		}
    }

    public function debug( $message, $type = 'notice' ) {
    	if ( $this->debug ) {
    		if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) ) {
    			wc_add_notice( $message, $type );
    		} else {
    			global $woocommerce;

    			$woocommerce->add_message( $message );
    		}
		}
    }
}
