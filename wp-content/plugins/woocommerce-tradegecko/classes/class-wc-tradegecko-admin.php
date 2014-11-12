<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class WC_TradeGecko_Sync
 * Class to handle all sync actions.
 *
 * @since 1.0
 */
class WC_TradeGecko_Admin {

	public function __construct() {

		$this->client_id	= WC_TradeGecko_Init::get_setting( 'client_id' );
		$this->client_secret	= WC_TradeGecko_Init::get_setting( 'client_secret' );
		$this->redirect_uri	= WC_TradeGecko_Init::get_setting( 'redirect_uri' );
		$this->auth_code	= WC_TradeGecko_Init::get_setting( 'auth_code' );

		// Add meta box
		add_action( 'add_meta_boxes', array( $this, 'wc_tradegecko_meta_boxes' ) );

		// Save meta box
		add_action('woocommerce_process_shop_order_meta', array( $this, 'wc_tradegecko_save_meta_boxes' ), 10, 2 );

		// Show Shipping and tracking to View Order and Tracking pages
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'show_shipping_tracking_to_view_and_track_order_pages' ) );

		// Add the shipping and tracking info to the "Order Completed" emails
		add_action( 'woocommerce_email_order_meta', array( $this, 'add_shipping_tracking_order_completed_emails' ), 100, 3 );

		// Add the order actions, only if all credentials are filled in
		if ( $this->client_id && $this->client_secret && $this->redirect_uri && $this->auth_code ) {
			// Add AJAX order actions to the orders panel
			add_action( 'woocommerce_admin_order_actions_end', array( $this, 'add_order_actions' ) );

			// Add Sync with TradeGecko order action
			add_filter( 'woocommerce_order_actions', array( $this, 'add_order_meta_box_actions' ) );
		}

		// Add Product Icon in the Product List Table
		add_filter( 'manage_edit-product_columns', array( $this, 'add_synced_product_column_title'), 20 );
		add_action( 'manage_product_posts_custom_column', array( $this, 'add_synced_product_column_content'), 10, 2 );

		// Users List Table Actions
		add_filter( 'manage_users_columns', array( $this, 'add_new_user_column'), 15, 1 );
		add_action( 'manage_users_custom_column', array( $this, 'add_user_column_content'), 15, 3 );

		// Add notification next to the SKU field
		add_action( 'woocommerce_product_options_sku', array( $this, 'add_product_sku_notification' ) );

		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'check_variable_product_sync_status' ), 20, 3 );

		if ( WC_TradeGecko_Init::is_subscriptions_active() ) {
			add_filter( 'woocommerce_subscriptions_renewal_order_meta_query', array( $this, 'remove_renewal_order_meta' ), 10, 4 );
		}

	}

	/**
	 * Remove all meta added to the Original order, when the renewal order is created.
	 * All types of renewal orders are treated as new and should be exported separately to TG.
	 *
	 * @access public
	 * @since 1.4
	 * @param array $order_meta_query MySQL query for pulling the metadata
	 * @param int $original_order_id Post ID of the order being used to purchased the subscription being renewed
	 * @param int $renewal_order_id Post ID of the order created for renewing the subscription
	 * @param string $new_order_role The role the renewal order is taking, one of 'parent' or 'child'
	 * @return void
	 */
	public function remove_renewal_order_meta( $order_meta_query, $original_order_id, $renewal_order_id, $new_order_role ) {

		// Remove the synced id and any fulfillment information
		$order_meta_query .= " AND `meta_key` NOT IN ('_wc_tradegecko_synced_order_id', '_wc_tradegecko_order_fulfillment')";

		return $order_meta_query;
	}

	/**
	 * Add the TG info meta box
	 *
	 * @access public
	 * @since 1.0
	 */
	public function wc_tradegecko_meta_boxes() {
		add_meta_box(
			'tradegecko-fulfillment-details',
			__('TradeGecko Fulfillment Details', WC_TradeGecko_Init::$text_domain ),
			array( $this, 'wc_tradegecko_fulfillment_details_meta_box' ),
			'shop_order',
			'normal',
			'default'
		);
	 }

	/**
	 * Save the metabox info
	 *
	 * @access public
	 * @since 1.0
	 * @param type $post_id
	 * @param type $post
	 */
	public function wc_tradegecko_save_meta_boxes( $post_id, $post ) {

		$ff_data = WC_TradeGecko_Init::get_post( WC_TradeGecko_Init::$meta_prefix .'order_fulfillment' );

		if ( $ff_data ) {
			WC_TradeGecko_Init::update_post_meta(
				$post_id,
				'order_fulfillment',
				$ff_data
			);
		}

	}

	/**
	 * Init the meta box to the Admin Order page
	 *
	 * @access public
	 * @since 1.0
	 * @param mixed $post Current Post data
	 * @return void
	 */
	public function wc_tradegecko_fulfillment_details_meta_box($post) {
		$ff_data = WC_TradeGecko_Init::get_post_meta( $post->ID, 'order_fulfillment' );

		// Move the ff_data one level up
		$ff_data = isset( $ff_data[0] ) ? $ff_data[0] : '';

	?>
		<div class="totals_group">
		<h4><?php _e( 'TradeGecko Fulfillment Details', WC_TradeGecko_Init::$text_domain ); ?></h4>
	<?php
		if ( ! empty( $ff_data ) ) {

			$updated = false;
			foreach ( $ff_data as $key => $data ) {

				if ( ! empty( $data['line_item_ids'] ) ) {

					try {
						// Get the fulfillment line items
						$tg_fulfillment_products = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'order_line_items', null, null, array( 'ids' => $data['line_item_ids'] ) ) );

						// If error occurred end the process and log the error
						if ( isset( $tg_fulfillment_products->error ) ) {
							throw new Exception( sprintf( __( 'There was an error retrieving fulfillments.'
								. ' Error Code: %s.'
								. ' Error Message: %s.', WC_TradeGecko_Init::$text_domain ),
								$tg_fulfillment_products->error,
								$tg_fulfillment_products->error_description ) );
						}

						// Get the correct node
						$tg_line_items = isset( $tg_fulfillment_products->order_line_item ) ? $tg_fulfillment_products->order_line_item : $tg_fulfillment_products->order_line_items;

						// Filter the line items and match them to a WC product
						$product_ids = array();
						foreach ( $tg_line_items as $tg_line_item ) {
							$id = $this->get_product_by_tg_id( $tg_line_item->variant_id );

							if ( $id ) {
								$product_ids[] = $id;
							}
						}

						if ( ! empty( $product_ids ) ) {
							foreach ( $product_ids as $value ) {
								$ff_data[ $key ]['products'][] =  $value;
							}
						}

						array_unique( $ff_data[ $key ]['products'] );

						// Remove the line items, once we have the products populated
						$ff_data[ $key ]['line_item_ids'] = array();

						$updated = true;

					} catch( Exception $e ) {
						// We cant do anything if there was an error, so just log the error and move on
						WC_TradeGecko_Init::add_log( $e->getMessage() );
					}

				}

				if ( ! empty( $ff_data[ $key ]['products'] ) ) {
				?>
					<h4><?php _e('Shipped Products:', WC_TradeGecko_Init::$text_domain); ?></h4>
				<?php
					$i = 0;
					foreach ( $ff_data[ $key ]['products'] as $id ) {

						$product = get_product( $id );
					?>
						<p><?php echo $product->get_title() ?></p>
						<input type="hidden"
						id="wc_tradegecko_products"
						name="<?php echo WC_TradeGecko_Init::$meta_prefix .'order_fulfillment['.$key.'][products][]'; ?>"
						value="<?php if ( isset( $ff_data[ $key ]['products'][ $i ] ) ) echo $ff_data[ $key ]['products'][ $i ]; ?>"
						class="first" />
					<?php
					$i++;
					}
				}
			?>
			<ul class="totals">
				<li class="right">
					<label><?php _e('Shipped At:', WC_TradeGecko_Init::$text_domain); ?><a class="tips" data-tip="<?php _e('The time and date the order was shipped at.', WC_TradeGecko_Init::$text_domain); ?>" href="#">[?]</a></label>
					<input type="text"
					       id="wc_tradegecko_shipped_at"
					       name="<?php echo WC_TradeGecko_Init::$meta_prefix .'order_fulfillment['.$key.'][shipped_at]'; ?>"
					       placeholder="<?php _e('2013-01-09T00:00:00Z', WC_TradeGecko_Init::$text_domain); ?>"
					       value="<?php if ( isset( $data['shipped_at'] ) ) echo $data['shipped_at']; ?>"
					       class="first" />
				</li>

				<li class="left">
					<label><?php _e('Received At:', WC_TradeGecko_Init::$text_domain); ?><a class="tips" data-tip="<?php _e('The time and date the order was received at.', WC_TradeGecko_Init::$text_domain); ?>" href="#">[?]</a></label>
					<input type="text"
					       id="wc_tradegecko_received_at"
					       name="<?php echo WC_TradeGecko_Init::$meta_prefix .'order_fulfillment['.$key.'][received_at]'; ?>"
					       placeholder="<?php _e('2013-01-09T00:00:00Z', WC_TradeGecko_Init::$text_domain); ?>"
					       value="<?php if ( isset( $data['received_at'] ) ) echo $data['received_at']; ?>"
					       class="first" />
				</li>

				<li class="right">
					<label><?php _e('Tracking Number:', WC_TradeGecko_Init::$text_domain); ?><a class="tips" data-tip="<?php _e('The shippment order tracking number, if provided.', WC_TradeGecko_Init::$text_domain); ?>" href="#">[?]</a></label>
					<input type="text"
					       id="wc_tradegecko_tracking_number"
					       name="<?php echo WC_TradeGecko_Init::$meta_prefix .'order_fulfillment['.$key.'][tracking_number]'; ?>"
					       placeholder="<?php _e('Tracking Number', WC_TradeGecko_Init::$text_domain); ?>"
					       value="<?php if ( isset( $data['tracking_number'] ) ) echo $data['tracking_number']; ?>"
					       class="first" />
				</li>
				<li class="left">
					<label><?php _e('Tracking URL:', WC_TradeGecko_Init::$text_domain); ?><a class="tips" data-tip="<?php _e('The shippment order tracking URL, if provided.', WC_TradeGecko_Init::$text_domain); ?>" href="#">[?]</a></label>
					<input type="text"
					       id="wc_tradegecko_tracking_url"
					       name="<?php echo WC_TradeGecko_Init::$meta_prefix .'order_fulfillment['.$key.'][tracking_url]'; ?>"
					       placeholder="<?php _e('Tracking URL', WC_TradeGecko_Init::$text_domain); ?>"
					       value="<?php if ( isset( $data['tracking_url'] ) ) echo $data['tracking_url']; ?>"
					       class="first" />
				</li>
				<li class="right">
					<label><?php _e('Delivery Type:', WC_TradeGecko_Init::$text_domain); ?><a class="tips" data-tip="<?php _e('The type of the delivery service (ie: Courier, Pickup).', WC_TradeGecko_Init::$text_domain); ?>" href="#">[?]</a></label>
					<input type="text"
					       id="wc_tradegecko_delivery_type"
					       name="<?php echo WC_TradeGecko_Init::$meta_prefix .'order_fulfillment['.$key.'][delivery_type]'; ?>"
					       placeholder="<?php _e('Delivery Type', WC_TradeGecko_Init::$text_domain); ?>"
					       value="<?php if ( isset( $data['delivery_type'] ) ) echo $data['delivery_type']; ?>"
					       class="first" />
				</li>
				<li class="left">
					<label><?php _e('Tracking Message:', WC_TradeGecko_Init::$text_domain); ?><a class="tips" data-tip="<?php _e('Informational message about the order tracking.', WC_TradeGecko_Init::$text_domain); ?>" href="#">[?]</a></label>
					<input type="text"
					       id="wc_tradegecko_tracking_message"
					       name="<?php echo WC_TradeGecko_Init::$meta_prefix .'order_fulfillment['.$key.'][tracking_message]'; ?>"
					       placeholder="<?php _e('Tracking Message', WC_TradeGecko_Init::$text_domain); ?>"
					       value="<?php if ( isset( $data['tracking_message'] ) ) echo $data['tracking_message']; ?>"
					       class="first" />
				</li>
				<div class="clear"></div>
			</ul>
	<?php
			}

			if ( true == $updated ) {
				WC_TradeGecko_Init::update_post_meta( $post->ID, 'order_fulfillment', $ff_data );
			}

		} else {
		?>
			<p>There is no Fulfillment data, yet.</p>
		<?php
		}
		?>
		</div>
	<?php
	}

	/**
	 * Show the shipping info to the view order and track your order pages
	 *
	 * @access public
	 * @since 1.0
	 * @param type $order
	 */
	public function show_shipping_tracking_to_view_and_track_order_pages( $order ) {
		$ff_data = WC_TradeGecko_Init::get_post_meta( $order->id, 'order_fulfillment' );

		$ff_data = isset( $ff_data[0] ) ? $ff_data[0] : '';

		if ( ! empty( $ff_data ) ) {
			$k = 1;
			$updated = false;
			foreach ( $ff_data as $key => $data ) {
				if ( 1 == count( $ff_data ) ) {
					$k = '';
				}

				?>
				<header>
					<h2><?php echo apply_filters( 'wc_tradegecko_shipping_details_header', __('Shipping Details', WC_TradeGecko_Init::$text_domain) ) .' '. $k; ?></h2>
				</header>

				<dl class="customer_details">
				<?php
				if ( 1 < count( $ff_data ) ) {
					$k++;
				}

				if ( ! empty( $data['line_item_ids'] ) ) {

					try {
						// Get the fulfillment line items
						$tg_fulfillment_products = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'order_line_items', null, null, array( 'ids' => $data['line_item_ids'] ) ) );

						// If error occurred end the process and log the error
						if ( isset( $tg_fulfillment_products->error ) ) {
							throw new Exception( sprintf( __( 'There was an error retrieving fulfillments in Order Completed email. Error Code: %s. Error Message: %s.', WC_TradeGecko_Init::$text_domain ), $tg_fulfillment_products->error, $tg_fulfillment_products->error_description ) );
						}

						// Get the correct node
						$tg_line_items = isset( $tg_fulfillment_products->order_line_item ) ? $tg_fulfillment_products->order_line_item : $tg_fulfillment_products->order_line_items;

						// Filter the line items and match them to a WC product
						$product_ids = array();
						foreach ( $tg_line_items as $tg_line_item ) {
							$id = $this->get_product_by_tg_id( $tg_line_item->variant_id );

							if ( $id ) {
								$product_ids[] = $id;
							}
						}

						if ( ! empty( $product_ids ) ) {
							foreach ( $product_ids as $value ) {
								$ff_data[ $key ]['products'][] =  $value;
							}
						}

						array_unique( $ff_data[ $key ]['products'] );

						// Remove the line items, once we have the products populated
						$ff_data[ $key ]['line_item_ids'] = array();

						$updated = true;

					} catch( Exception $e ) {
						// We cant do anything if there was an error, so just log the error and move on
						WC_TradeGecko_Init::add_log( $e->getMessage() );
					}

				}

				if ( ! empty( $ff_data[ $key ]['products'] ) ) {

					echo '<dt>'. apply_filters( 'wc_tradegecko_products_shipped_label', __( 'Products Shipped:', WC_TradeGecko_Init::$text_domain ) ) .'</dt><dd>';

					$prod_titles = '';
					$i = 0;
					foreach ( $ff_data[ $key ]['products'] as $id ) {

						$product = get_product( $id );
						$prod_titles .= $product->get_title() .', ';

					$i++;
					}
					$prod_titles = substr($prod_titles, 0, -2 );

					echo $prod_titles.'</dd>';
				}
				?>
				<?php
					if ( $data['shipped_at'] ) {
						echo '<dt>'.apply_filters( 'wc_tradegecko_shipped_at_label', __( 'Order Shipped at:', WC_TradeGecko_Init::$text_domain ) ).'</dt><dd>'. $data['shipped_at'] .'</dd>';
					}
					if ( $data['tracking_number'] ) {
						echo '<dt>'.apply_filters( 'wc_tradegecko_tracking_number_label', __( 'Tracking Number:', WC_TradeGecko_Init::$text_domain ) ).'</dt><dd>'. $data['tracking_number'] .'</dd>';
					}
					if ( $data['tracking_url'] ) {
						// Make sure the URL has a scheme.
						$url = $this->format_url( $data['tracking_url'] );

						echo '<dt>'.apply_filters( 'wc_tradegecko_tracking_message_label', __( 'Tracking URL:', WC_TradeGecko_Init::$text_domain ) ).'</dt><dd><a href="'. $url .'">'. $data['tracking_url'] .'</a></dd>';
					}
					if ( $data['delivery_type'] ) {
						echo '<dt>'.apply_filters( 'wc_tradegecko_delivery_type_label', __( 'Delivery Type:', WC_TradeGecko_Init::$text_domain ) ).'</dt><dd>'. $data['delivery_type'] .'</dd>';
					}
					if ( $data['tracking_message'] ) {
						echo '<dt>'.apply_filters( 'wc_tradegecko_tracking_message_label', __( 'Tracking Message:', WC_TradeGecko_Init::$text_domain ) ).'</dt><dd>'. $data['tracking_message'] .'</dd>';
					}
				?>
				</dl>
				<?php
			}

			if ( true == $updated ) {
				WC_TradeGecko_Init::update_post_meta( $order->id, 'order_fulfillment', $ff_data );
			}
		}
	}

	/**
	 * Add shipping and tracking info to the email templates.
	 *
	 * @access public
	 * @param mixed $order
	 * @param bool $sent_to_admin (default: false)
	 * @param bool $plain_text (default: false)
	 */
	public function add_shipping_tracking_order_completed_emails( $order, $sent_to_admin = false, $plain_text = false ) {
		$ff_data = WC_TradeGecko_Init::get_post_meta( $order->id, 'order_fulfillment' );

		if ( ! empty( $ff_data ) ) {
			$k = '';
			foreach ( $ff_data[0] as $key => $data ) {
				if ( 1 == count( $ff_data[0] ) ) {
					$k = '';
				}

				$head = apply_filters( 'wc_tradegecko_shipping_details_header', __('Shipping Details', WC_TradeGecko_Init::$text_domain) );
				if ( $plain_text ) {
					echo $head .' '. $k .'\n\n';
				} else {
					echo '<h2>'. $head .' '. $k .'</h2>';
				}

				if ( 1 < count( $ff_data[0] ) ) {
					$k++;
				}

				if ( $data['shipped_at'] ) {
					$head = apply_filters( 'wc_tradegecko_shipped_at_label', __( 'Order Shipped at', WC_TradeGecko_Init::$text_domain ) );
					if ( $plain_text ) {
						echo $head .':'. $data['shipped_at'] .'\n';
					} else {
						echo '<p><strong>'. $head .':</strong> '. $data['shipped_at'] .'</p>';
					}
				}

				if ( $data['tracking_number'] ) {
					$head = apply_filters( 'wc_tradegecko_tracking_number_label', __( 'Tracking Number', WC_TradeGecko_Init::$text_domain ) );
					if ( $plain_text ) {
						echo $head .':'. $data['tracking_number'] .'\n';
					} else {
						echo '<p><strong>'. $head .': </strong>'. $data['tracking_number'] .'</p>';
					}
				}

				if ( $data['tracking_url'] ) {
					// Make sure the URL has a scheme.
					$url = $this->format_url( $data['tracking_url'] );

					$head = apply_filters( 'wc_tradegecko_tracking_message_label', __( 'Tracking URL', WC_TradeGecko_Init::$text_domain ) );
					if ( $plain_text ) {
						echo $head .':'. $data['tracking_url'] .'\n';
					} else {
						echo '<p><strong>'. $head .':</strong> <a href="'. $url .'">'. $data['tracking_url'] .'</a></p>';
					}
				}

				if ( $data['delivery_type'] ) {
					$head = apply_filters( 'wc_tradegecko_delivery_type_label', __( 'Delivery Type', WC_TradeGecko_Init::$text_domain ) );
					if ( $plain_text ) {
						echo $head .':'. $data['delivery_type'] .'\n';
					} else {
						echo '<p><strong>'. $head .':</strong> '. $data['delivery_type'] .'</p>';
					}
				}

				if ( $data['tracking_message'] ) {
					$head = apply_filters( 'wc_tradegecko_tracking_message_label', __( 'Tracking Message', WC_TradeGecko_Init::$text_domain ) );
					if ( $plain_text ) {
						echo $head .':'. $data['tracking_message'] .'\n';
					} else {
						echo '<p><strong>'. $head .': </strong>'. $data['tracking_message'] .'</p>';
					}
				}
			}
		}
	}

	/**
	 * Add Sync to TradeGecko action in the orders panel
	 *
	 * @access public
	 * @since 1.0
	 * @param object $order
	 */
	public function add_order_actions( $order ) {

		$is_synced = WC_TradeGecko_Init::get_post_meta( $order->id, 'synced_order_id', true );


		echo sprintf( '<a class="button tips %s" id="wc_tradegecko_sync_action_button" href="%s" data-tip="%s"><img src="%s" alt="%s" class="wc_tradegecko_sync_icon" /></a>',
			($is_synced) ? 'synced' : '',
			wp_nonce_url( admin_url( 'admin-ajax.php?action=wc_tradegecko_update_order&order_id=' . $order->id ), 'wc_tradegecko_sync_order' ),
			( $is_synced ) ? __( 'Update from TradeGecko', WC_TradeGecko_Init::$text_domain ) : __( 'Export to TradeGecko', WC_TradeGecko_Init::$text_domain ),
			WC_TradeGecko_Init::$plugin_url . 'assets/images/wc-tradegecko-sync-icon.png',
			( $is_synced ) ? 'Update' : 'Export'
		);
	}

	/**
	 * Add a Sync with TradeGecko action to the order settings.
	 *
	 * @access public
	 * @since 1.0
	 * @global type $theorder
	 * @param array $actions The actions to be set to the order settings page
	 * @return array
	 */
	public function add_order_meta_box_actions( $actions ) {
		global $theorder;

		$is_synced = WC_TradeGecko_Init::get_post_meta( $theorder->id, 'synced_order_id', true );

		$actions['wc_tradegecko_update_order'] = ( $is_synced ) ? __( 'Update from TradeGecko', WC_TradeGecko_Init::$text_domain ) : __( 'Export to TradeGecko', WC_TradeGecko_Init::$text_domain );

		return $actions;
	}

	/*=======================================================
	 * Product List Table View - Add Synced Product Column
	 ========================================================*/

	/**
	 * Add the Title in the List Table
	 *
	 * @since 1.0.1
	 * @param type $columns
	 * @return string
	 */
	public function add_synced_product_column_title ( $columns ) {

		$new_columns = array();

		foreach( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( 'date' == $key ) {
				$new_columns[ 'wc-tg-synced' ] = '<span class="tips" data-tip="' . __('Products Synced with TradeGecko', WC_TradeGecko_Init::$text_domain) . '">' . __( 'Synced' , WC_TradeGecko_Init::$text_domain ) . '</span>';
			}
		}

		return $new_columns;
	}

	/**
	 * Add Product Synced Icon in the List Table
	 *
	 * @since 1.0.1
	 * @param type $column_name
	 * @param type $post_id
	 */
	public function add_synced_product_column_content( $column_name, $post_id ) {

		switch ( $column_name ) {

			case 'wc-tg-synced' :

				$is_synced = false;
				$attention = false;

				// Get the product object
				$product = get_product( $post_id );

				if ( 'variable' == $product->product_type ) {
					$variations = $product->get_available_variations();

					foreach ( $variations as $variation ) {
						$tg_variant_id = WC_TradeGecko_Init::get_post_meta( $variation['variation_id'], 'variant_id', true );

						// If we have a single variant synced then show the whole product as synced
						if ( ! empty( $tg_variant_id ) ) {
							$is_synced = true;
						} else {
							$attention = true;
						}

						// We have both of our conditions
						if ( $is_synced && $attention ) {
							break;
						}

					}

					echo sprintf( '<span class="tips %s" id="wc_tradegecko_sync_product" data-tip="%s"><img src="%s" alt="%s" class="wc_tradegecko_sync_icon" /></span>',
						( $is_synced ) ? 'synced' : 'not_synced',
						( $is_synced ) ? ( $attention ) ? __( 'Synced but not all variations. You can sync each variation manually from the Edit product page.', WC_TradeGecko_Init::$text_domain ) : __( 'Synced. You can sync each variation manually from the Edit product page.', WC_TradeGecko_Init::$text_domain ) : __( 'Not Synced. You can sync each variation manually from the Edit product page.', WC_TradeGecko_Init::$text_domain ),
						WC_TradeGecko_Init::$plugin_url . 'assets/images/wc-tradegecko-product.png',
						( $is_synced ) ? ( $attention ) ? __( 'Synced but not all variations. You can sync each variation manually from the Edit product page.', WC_TradeGecko_Init::$text_domain ) : __( 'Synced. You can sync each variation manually from the Edit product page.', WC_TradeGecko_Init::$text_domain ) : __( 'Not Synced. You can sync each variation manually from the Edit product page.', WC_TradeGecko_Init::$text_domain )
					);
				} else {
					$tg_variant_id = WC_TradeGecko_Init::get_post_meta( $product->id, 'variant_id', true );

					if ( ! empty( $tg_variant_id ) ) {
						$is_synced = true;
					} else {
						$attention = true;
					}

					echo sprintf( '<a href="%s" class="tips %s" id="wc_tradegecko_sync_product" data-tip="%s"><img src="%s" alt="%s" class="wc_tradegecko_sync_icon" /></a>',
						wp_nonce_url( admin_url( 'admin-ajax.php?action=wc_tradegecko_single_product_sync&product_id=' . $product->id ), 'wc_tradegecko_single_product_sync' ),
						( $is_synced ) ? 'synced' : 'not_synced',
						( $is_synced ) ? __( 'Synced. Click to update the product manually.', WC_TradeGecko_Init::$text_domain ) : __( 'Not Synced. Click to sync the product manually.', WC_TradeGecko_Init::$text_domain ),
						WC_TradeGecko_Init::$plugin_url . 'assets/images/wc-tradegecko-product.png',
						( $is_synced ) ? __( 'Synced', WC_TradeGecko_Init::$text_domain ) : __( 'Not Synced', WC_TradeGecko_Init::$text_domain )
					);
				}
			break;
		}

	}

	/**
	 * Add a row to each variation in the Edit product panel
	 *
	 * @since 1.2
	 * @global object $woocommerce
	 * @global int $thepostid
	 * @param int $loop
	 * @param array $variation_data
	 * @param object $variation
	 */
	public function check_variable_product_sync_status( $loop, $variation_data, $variation ) {
		global $thepostid;

		$variation_data = array_map( array( $this, 'map_user_array' ), $variation_data );
		$sku = isset( $variation_data['_sku'] ) ? $variation_data['_sku'] : '';
		$show_button = false;
		$warning = false;
		$html = '';
		$message = '';

		$html .= '<tr>';
			$html .= '<td>';
			if ( empty( $sku ) ) {
				$message .= __( 'Warning: SKU is Required to sync the variation product with TG.', WC_TradeGecko_Init::$text_domain);
				$warning = true;
			} elseif ( empty( $variation_data[ WC_TradeGecko_Init::$meta_prefix .'variant_id' ] ) ) {

				$message .= sprintf( __( 'The product is not synced with TG system. Click on the button to sync the product manually.', WC_TradeGecko_Init::$text_domain), $sku );
				$show_button = true;
				$warning = true;

			} else {
				$message .= sprintf( __( 'The product is synced with TradeGecko. Click on the button to update the product manually.', WC_TradeGecko_Init::$text_domain) );
				$show_button = true;
			}

			$class = ( $warning ) ? 'sku_warning' : 'sku_message';
			$html .= '<div class="'. $class .'"><p>'. $message .'</p></div>';

			$html .= '</td>';

		if ( $show_button ) {
			$html .= '<td>';
			$html .= sprintf( '<p><a href="%s" class="button">%s</a></p>',
				wp_nonce_url( admin_url( 'admin-ajax.php?action=wc_tradegecko_single_product_sync&product_id=' . $variation->ID ), 'wc_tradegecko_single_product_sync' ),
				__( 'Manually Sync the Product with TradeGecko', WC_TradeGecko_Init::$text_domain )
			);
			$html .= '</td>';
		}

		do_action( 'wc_tradegecko_end_variant_row', $loop, $variation_data, $variation );

		$html .= '</tr>';

		echo $html;


	}

	/**
	 * Check if the SKU is used and used only ones in TG.
	 *
	 * @global type $post
	 * @since 1.0.1
	 */
	public function add_product_sku_notification() {
		global $post;

		$sku = get_post_meta( $post->ID, '_sku', true );
		$product = get_product( $post->ID );

		// For variable products the parent product is not required to be synced
		if ( 'variable' != $product->product_type ) {

			try {

				if ( empty( $sku ) ) {
					echo '<div class="sku_warning"><p>'. __( 'Warning: SKU is Required to sync the product with TG.', WC_TradeGecko_Init::$text_domain) .'</p></div>';
				} else {
					$tg_sku = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'variants', null, null, array( 'sku' => $sku ) ) );

					if ( empty( $tg_sku->variants ) ) {
						echo '<div class="sku_warning"><p>'. sprintf( __( 'Warning: Product with SKU %s does not exist in TradeGecko', WC_TradeGecko_Init::$text_domain), $sku ) .'</p></div>';
					} elseif ( 1 < count( $tg_sku->variants ) ) {
						echo '<div class="sku_warning"><p>'. sprintf( __( 'Warning: There are more than one matches of this SKU ( %s ) in the TradeGecko system, please make sure SKUs are unique.', WC_TradeGecko_Init::$text_domain), $sku ) .'</p></div>';
					}
				}

			} catch( Exception $e ) {

				// Add log
				WC_TradeGecko_Init::add_log( 'Could not connect to TG: Error: '. $e->getCode() .' Message: '. $e->getMessage() );

				WC_TradeGecko_Init::add_sync_log( 'Error', 'Could not connect to TG: Error: '. $e->getCode() .' Message: '. $e->getMessage() );

				echo '<div class="sku_warning"><p>'. sprintf( __( 'We could not obtain connection to TradeGecko System. Please check the %sTradeGecko Sync Log%s for more information.', WC_TradeGecko_Init::$text_domain), '<a href="'. admin_url( 'admin.php?page=tradegecko&tab=sync-log' ) .'" >', '</a>' ) .'</p></div>';

			}

		}

	}

	/**
	 * Add a column to the Users List
	 *
	 * @param array $columns
	 * @return string
	 */
	function add_new_user_column( $columns ) {
		if ( ! current_user_can( 'manage_woocommerce' ) )
			return $columns;

		$columns['tradegecko_sync_customer'] = '<span class="tips" data-tip="' . __('Customer Synced with TradeGecko', WC_TradeGecko_Init::$text_domain) . '">' . __( 'Synced' , WC_TradeGecko_Init::$text_domain ) . '</span>';

		return $columns;
	}

	/**
	 * Show the sync status of the users in the Users List column
	 *
	 * @global object $woocommerce
	 * @global object $wpdb
	 * @param string $content
	 * @param string $column_name
	 * @param int $user_id
	 * @return string
	 */
	function add_user_column_content( $content, $column_name, $user_id ) {
		global $wpdb;

		switch ( $column_name ) {

			case 'tradegecko_sync_customer' :

				$is_synced = false;

				// Get the product object
				$user = get_user_meta( $user_id, 'wc_tradegecko_customer_id', true );

				if ( '' != $user ) {
					$is_synced = true;

					$content = sprintf( '<span class="tips %s" id="wc_tradegecko_sync_product" data-tip="%s"><img src="%s" alt="%s" class="wc_tradegecko_sync_icon" /></span>',
						( $is_synced ) ? 'synced' : 'not_synced',
						( $is_synced ) ? __( 'Synced', WC_TradeGecko_Init::$text_domain ) : __( 'Not Synced', WC_TradeGecko_Init::$text_domain ),
						WC_TradeGecko_Init::$plugin_url . 'assets/images/wc-tradegecko-product.png',
						( $is_synced ) ? __( 'Synced', WC_TradeGecko_Init::$text_domain ) : __( 'Not Synced', WC_TradeGecko_Init::$text_domain )
					);
				} else {

					$content = sprintf( '<a href="%s" class="tips %s" id="wc_tradegecko_sync_product" data-tip="%s"><img src="%s" alt="%s" class="wc_tradegecko_sync_icon" /></a>',
						wp_nonce_url( admin_url( 'admin-ajax.php?action=wc_tradegecko_export_customer&user_id=' . $user_id ), 'wc_tradegecko_export_customer' ),
						( $is_synced ) ? 'synced' : 'not_synced',
						( $is_synced ) ? __( 'Synced', WC_TradeGecko_Init::$text_domain ) : __( 'Not Synced. Click to export the user.', WC_TradeGecko_Init::$text_domain ),
						WC_TradeGecko_Init::$plugin_url . 'assets/images/wc-tradegecko-product.png',
						( $is_synced ) ? __( 'Synced', WC_TradeGecko_Init::$text_domain ) : __( 'Not Synced', WC_TradeGecko_Init::$text_domain )
					);

				}



			break;

		}

		return $content;
	}

	/**
	 * Map User meta array and return the first of each field.
	 *
	 * @since 1.2
	 * @access public
	 * @param array $a
	 * @return mixed
	 */
	public function map_user_array( $a ) {
		return $a[0];
	}

	/**
	 * Get the product id from the TG variant id
	 *
	 * @access private
	 * @since 1.0
	 * @global object $wpdb
	 * @param type $line_item_id
	 * @return boolean
	 */
	private function get_product_by_tg_id( $line_item_id ) {
		global $wpdb;
		$product =
			"SELECT post_id
			FROM   $wpdb->postmeta
			WHERE  meta_key = '%s'
			AND    meta_value = %s";
		$product_id = $wpdb->get_var( $wpdb->prepare( $product, WC_TradeGecko_Init::$meta_prefix .'variant_id', $line_item_id ) );
		if ( $product_id ) {
			return $product_id;
		} else {
			return false;
		}
	}

	/**
	 * Check if the URL has correct scheme.
	 *
	 * @since 1.2.1
	 * @param string $url
	 * @return string
	 */
	private function format_url( $url ) {
		$parse = parse_url( $url );
		if ( ! isset( $parse['scheme'] ) ) {
			$url = 'http://' . $url;
		}
		return $url;
	}


} new WC_TradeGecko_Admin();
