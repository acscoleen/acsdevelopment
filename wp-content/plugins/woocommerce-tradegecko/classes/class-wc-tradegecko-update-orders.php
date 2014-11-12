<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class handles the Update orders process of order synchronization
 *
 * @since 1.5
 */
class WC_TradeGecko_Update_Orders {

	public function __construct() {

		$this->tg_order_ids		= array();
		$this->inventory_sync		= WC_TradeGecko_Init::get_setting( 'inventory_sync' );
		$this->orders_sync		= WC_TradeGecko_Init::get_setting( 'orders_sync' );
		$this->enable			= WC_TradeGecko_Init::get_setting( 'enable' );
		$this->product_price_sync	= WC_TradeGecko_Init::get_setting( 'product_price_sync' );
		$this->product_title_sync	= WC_TradeGecko_Init::get_setting( 'product_title_sync' );
		$this->sync_fulfillments	= WC_TradeGecko_Init::get_setting( 'order_fulfillment_sync' );
		$this->allow_sale_price_mapping	= WC_TradeGecko_Init::get_setting( 'allow_sale_price_mapping' );
		$this->regular_price_id		= WC_TradeGecko_Init::get_setting( 'regular_price_id' );
		$this->sale_price_id		= WC_TradeGecko_Init::get_setting( 'sale_price_id' );
		$this->available_currency_id	= WC_TradeGecko_Init::get_setting( 'available_currency_id' );
		$this->stock_location_id	= WC_TradeGecko_Init::get_setting( 'stock_location_id' );

		add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'process_tg_order_update_request' ) );

	}

	/**
	 * Process update order request from TG.
	 *
	 * NOTE: Method is not in use, but it is added to prepare
	 * implementation of TG -> WC request for orders updating.
	 */
	function process_tg_order_update_request() {

	}

	/**
	 * Process and initiate the orders update.
	 *
	 * @since 1.5
	 * @param array $order_ids The TG Order IDs of the orders that need updating.
	 * @param array $tg_id_to_order_id_mapping TG to WC order IDs mapping
	 */
	function process_update_orders( array $order_ids, array $tg_id_to_order_id_mapping ) {
		// Split the order ids to smaller batches
		$update_batches = $this->split_tg_order_ids_into_batches( $order_ids );

		foreach ( $update_batches as $tg_order_ids ) {
			// Update each batch of orders
			$this->update_orders_batch( $tg_order_ids, $tg_id_to_order_id_mapping );
		}
	}

	/**
	 * Update the orders in the order batches
	 *
	 * @since 1.5
	 * @global type $wc_tg_sync The global WC_TradeGecko_Sync class
	 * @param array $order_batch The IDs to update in the batch
	 * @param type $tg_id_to_order_id_mapping TG to WC order IDs mapping
	 */
	function update_orders_batch( array $order_batch, $tg_id_to_order_id_mapping ) {

		// Get the orders info from TG
		$tg_orders = $this->get_tg_orders_update_info( $order_batch );

		foreach ( $tg_orders as $tg_open_order ) {

			// Get the WC order ID from the mapping
			$order_id = $tg_id_to_order_id_mapping[ $tg_open_order->id ];

			if ( empty( $order_id ) ) {
				continue;
			}

			$order = new WC_Order( (int) $order_id );

			// Sync fulfillments, if allowed
			if ( 'no' != $this->sync_fulfillments && ! empty( $tg_open_order->fulfillment_ids ) ) {

				// Update the order fulfillment
				$this->update_order_fulfillments( $order, $tg_open_order );

			} else {
				$this->maybe_update_tg_unpaid_order_status( $order, $tg_open_order );
			}

			$order_status_changed = $this->update_order_status( $order, $tg_open_order );

			// Re-sync order line items stock level
			if ( $order_status_changed && $this->inventory_sync ) {
				$updated_product = array();
				$items = $order->get_items();
				foreach ( $items as $item ) {
					$product = $order->get_product_from_item( $item );

					// Prevent syncing the same product over and over again
					if( isset($updated_product[ (string) $product->get_sku() ]) && $updated_product[ (string) $product->get_sku() ] === true ) {
						continue;
					} else {
						global $wc_tg_sync;
						$wc_tg_sync->sync_product_inventory( $product );
						$wc_tg_sync->update_last_synced_at( $product );

						// Log updated product
						$updated_product[ (string) $product->get_sku() ] = true;
					}
				}
			}
		}
	}

	/**
	 * Update the TG order status
	 *
	 * @since 1.5
	 * @param WC_Order $order
	 * @param type $tg_open_order
	 */
	function maybe_update_tg_unpaid_order_status( WC_Order $order, $tg_open_order ) {
		// This should be skipped all the time, but do it just in case
		if ( 'unpaid' == $tg_open_order->payment_status &&
			( 'completed' == $order->status || 'processing' == $order->status ) ) {
			// Update TG order to paid
			$update_info = array(
				'order' => array(
					'payment_status' => 'paid'
				)
			);

			// Add log
			WC_TradeGecko_Init::add_log( 'Updating order payment status. Request: ' . print_r( $update_info, true ) );

			$tg_update_order = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'PUT', 'orders', $update_info, $tg_open_order->id ) );

		}
	}

	/**
	 * Update the WC orders with the appropriate TG fulfillment information
	 *
	 * @since 1.5
	 * @param WC_Order $order
	 * @param object $tg_open_order TG order data
	 */
	function update_order_fulfillments( WC_Order $order, $tg_open_order ) {
		// Sync fulfillments, if full order is shipped.
		if ( ( 'full' == $this->sync_fulfillments && 'shipped' == $tg_open_order->fulfillment_status ) ||
			'partial' == $this->sync_fulfillments ) {
			// Get the fullfilment info and update with shipping time and tracking info
			$ff_data = array();
			foreach ( $tg_open_order->fulfillment_ids as $fulfillment_id ) {
				$tg_fulfillment = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'fulfillments', null, $fulfillment_id ) );

				// Add log
				WC_TradeGecko_Init::add_log( 'Doing Fulfillment ID: ' . $fulfillment_id .'. Data: '.print_r( $tg_fulfillment, true ) );

				// Make sure we have the correct node
				$fulfillment = isset( $tg_fulfillment->fulfillment ) ? $tg_fulfillment->fulfillment : $tg_fulfillment->fulfillments;

				// Ignore the fulfillment, if it is not completed
				if ( 'packed' == $fulfillment->status ) {
					continue;
				}

				// Get the fulfillment line items node
				$fulfillment_line_items = isset( $tg_fulfillment->fulfillment_line_items ) ? $tg_fulfillment->fulfillment_line_items : $tg_fulfillment->fulfillment_line_item;

				// Run through the line items and get their ids
				$order_line_items = array();
				foreach( $fulfillment_line_items as $fulfillment_line_item ) {
					$order_line_items[] = $fulfillment_line_item->order_line_item_id;
				}

				$ff_data[ $fulfillment->id ] = array(
					'shipped_at'		=> $fulfillment->shipped_at,
					'received_at'		=> $fulfillment->received_at,
					'delivery_type'		=> $fulfillment->delivery_type,
					'tracking_number'	=> $fulfillment->tracking_number,
					'tracking_message'	=> ( ! empty( $fulfillment->notes ) ) ? $fulfillment->notes : '',
					'tracking_url'		=> $fulfillment->tracking_url,
					'line_item_ids'		=> $order_line_items,
				);

				WC_TradeGecko_Init::update_post_meta( $order->id, 'order_fulfillment', $ff_data );

			}
		}
	}

	/**
	 * Update WC Order status according to TG Order status
	 *
	 * @since 1.5
	 * @param WC_Order $order WC Order
	 * @param type $tg_order TG Order
	 * @return boolean TRUE, if status is updated. FALSE, if status is not updated.
	 */
	function update_order_status( WC_Order $order, $tg_order ) {
		// After order info is added and updated
		// Complete the order, if the fulfillment status is shipped
		// This will trigger all emails and notification to the customer

		$order_status_changed = false;

		if ( 'completed' != $order->status && 'shipped' == $tg_order->fulfillment_status ) {
			$order->update_status( 'completed', __( 'Order shipped in TradeGecko.', WC_TradeGecko_Init::$text_domain ) );
			$order_status_changed = true;
		}

		// Cancel voided order
		if ( 'cancelled' != $order->status && 'void' == $tg_order->status ) {
			$order->update_status( 'cancelled', __( 'Order voided in TradeGecko.', WC_TradeGecko_Init::$text_domain ) );
			$order_status_changed = true;
		}

		// Cancel deleted order
		if ( 'cancelled' != $order->status && 'deleted' == $tg_order->status ) {
			$order->update_status( 'cancelled', __( 'Order deleted in TradeGecko.', WC_TradeGecko_Init::$text_domain ) );
			$order_status_changed = true;
		}

		return $order_status_changed;
	}



	/**
	 * Make an API call an get the order info for the orders we want to update
	 *
	 * @since 1.5
	 * @param array $tg_order_ids
	 * @return array
	 * @throws Exception
	 */
	private function get_tg_orders_update_info( $tg_order_ids ) {

		// Now that we filtered all open orders, sync the information with TG
		$tg_open_orders = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'orders', null, null, array( 'ids' => $tg_order_ids ) ) );

		// Add log
		WC_TradeGecko_Init::add_log( 'Update orders data response: ' . print_r( $tg_open_orders, true ) );

		// If error occurred end the process and log the error
		if ( isset( $tg_open_orders->error ) ) {
			throw new Exception( sprintf( __( 'Could not retrieve the open orders from TradeGecko. Error Code: %s. Error Message: %s.', WC_TradeGecko_Init::$text_domain ), $tg_open_orders->error, $tg_open_orders->error_description ) );
		}

		$tg_orders =  isset( $tg_open_orders->order ) ? $tg_open_orders->order : $tg_open_orders->orders;

		return $tg_orders;

	}

	/**
	 * Split the order ids into smaller batches of 100 or less.
	 *
	 * Filter 'update_orders_chunks' can be used to have more or less orders in batches.
	 *
	 * @since 1.4.1
	 * @param array $order_ids An array of each tg order id
	 * @return array Order ids into 300 or less
	 */
	private function split_tg_order_ids_into_batches( array $order_ids ) {
		$batches = array();
		$chunks_size = apply_filters( 'update_orders_chunks', 300 );

		if ( $chunks_size >= count( $order_ids ) ) {
			$batches[] = $order_ids;
		} else {
			$batches = array_chunk( $order_ids, (int) $chunks_size );
		}

		return $batches;
	}

}