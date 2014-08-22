<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WC_TradeGecko_AJAX class
 *
 * Handles all AJAX actions
 *
 */

class WC_TradeGecko_AJAX {

	/**
	 * Add wp_ajax_* hooks
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct() {

		// Clear logs call
		add_action( 'wp_ajax_wc_tradegecko_clear_sync_logs', array( $this, 'clear_sync_logs' ) );

		// Manual orders sync call
		add_action( 'wp_ajax_wc_tradegecko_manual_sync', array( $this, 'manual_sync' ) );

		// Manual orders sync call
		add_action( 'wp_ajax_wc_tradegecko_manual_order_update_sync', array( $this, 'manual_order_update_sync' ) );

		// Manual inventory sync call
		add_action( 'wp_ajax_wc_tradegecko_inventory_manual_sync', array( $this, 'manual_inventory_sync' ) );

		// Manual update order call
		add_action( 'wp_ajax_wc_tradegecko_update_order', array( $this, 'update_order' ) );

		// Manual export customer call
		add_action( 'wp_ajax_wc_tradegecko_export_customer', array( $this, 'export_customer' ) );

		// Manual single product inventory sync
		add_action( 'wp_ajax_wc_tradegecko_single_product_sync', array( $this, 'single_product_sync' ) );

	}

	/**
	 * Perform Orders Synchronization
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function manual_sync() {

		$this->verify_request( 'wc_tradegecko_manual_sync_nonce' );

		do_action( 'wc_tradegecko_synchronization' );

		wp_safe_redirect( wp_get_referer() );

	}

	/**
	 * Perform Orders Synchronization
	 *
	 * @access public
	 * @since  1.5
	 * @return void
	 */
	public function manual_order_update_sync() {

		$this->verify_request( 'wc_tradegecko_manual_order_update_sync_nonce' );

		do_action( 'wc_tradegecko_order_update_synchronization' );

		wp_safe_redirect( wp_get_referer() );

	}

	/**
	 * Perform Inventory Synchronization
	 *
	 * @access public
	 * @since  1.1
	 * @return void
	 */
	public function manual_inventory_sync() {

		$this->verify_request( 'wc_tradegecko_manual_inventory_sync_nonce' );

		do_action( 'wc_tradegecko_inventory_synchronization' );

		wp_safe_redirect( wp_get_referer() );

	}

	/**
	 * Clear sync logs
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function clear_sync_logs() {

		$this->verify_request( WC_TradeGecko_Init::$prefix .'clear_sync_logs' );

		update_option( WC_TradeGecko_Init::$prefix . 'sync_log', array() );

		wp_safe_redirect( wp_get_referer() );
	}

	/**
	 * Update a single order. If order is not exported, export it.
	 */
	public function update_order() {

		// Verify nonce
		$this->verify_request( 'wc_tradegecko_sync_order' );

		$order_id = WC_TradeGecko_Init::get_get( 'order_id' );
		$tg_order_id = WC_TradeGecko_Init::get_post_meta( $order_id, 'synced_order_id', true );

		if ( $tg_order_id ) {
			do_action( 'wc_tradegecko_update_order', WC_TradeGecko_Init::get_get( 'order_id' ) );
		} else {
			do_action( 'wc_tradegecko_export_new_orders', WC_TradeGecko_Init::get_get( 'order_id' ) );
		}

		wp_safe_redirect( wp_get_referer() );

	}

	/**
	 * Update a single order. If order is not exported, export it.
	 */
	public function export_customer() {

		// Verify nonces
		$this->verify_request( 'wc_tradegecko_export_customer' );

		$user_id = WC_TradeGecko_Init::get_get( 'user_id' );

		try {

			do_action( 'wc_tradegecko_export_customer', $user_id );

		} catch( Exception $e ) {

			WC_TradeGecko_Init::add_log( $e->getMessage() );

			WC_TradeGecko_Init::add_sync_log( 'Error', $e->getMessage() );

		}
		wp_safe_redirect( wp_get_referer() );

	}

	/**
	 * Sync a single product with TG
	 *
	 * @since 1.2
	 */
	function single_product_sync() {

		// Verify nonces
		$this->verify_request( 'wc_tradegecko_single_product_sync' );

		$product_id = (int) WC_TradeGecko_Init::get_get( 'product_id' );

		try {
			// If we have a variant ID, attempt to sync the product information
			do_action( 'wc_tradegecko_single_product_inventory_sync', $product_id );

		} catch( Exception $e ) {

			WC_TradeGecko_Init::add_log( $e->getMessage() );

			WC_TradeGecko_Init::add_sync_log( 'Error', $e->getMessage() );

		}
		wp_safe_redirect( wp_get_referer() );

	}

	/**
	 * Check if the request is from an admin or a user that with suffitient rights. <br/>
	 * Varify the _wpnonce.
	 *
	 * @access private
	 * @since  1.0
	 * @param string $action - Nonce the ajax action is performed with
	 * @return void
	 */
	private function verify_request( $action ) {

		if ( ! is_admin() || ! current_user_can( 'manage_woocommerce' ) )
			wp_die( __( 'You do not have sufficient permissions to access this page.', WC_TradeGecko_Init::$text_domain ) );

		if ( ! wp_verify_nonce( ( WC_TradeGecko_Init::get_get( '_wpnonce' ) ), $action ) )
			wp_die( __( 'Cannot verify the request, please go back and try again.', WC_TradeGecko_Init::$text_domain ) );
	}

} new WC_TradeGecko_Ajax();