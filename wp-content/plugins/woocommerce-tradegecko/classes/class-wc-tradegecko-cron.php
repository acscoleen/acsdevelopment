<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * WC TradeGecko Cron Class
 *
 * Handles scheduling for WooCommerce to TradeGecko Synchronization.
 */

class WC_TradeGecko_Cron {

	/**
	 * Adds hooks and filters
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct() {

		$this->automatic_sync			= WC_TradeGecko_Init::get_setting( 'automatic_sync' );
		$this->sync_time_interval		= (int) WC_TradeGecko_Init::get_setting( 'sync_time_interval' );
		$this->sync_time_period			= WC_TradeGecko_Init::get_setting( 'sync_time_period' );

		$this->automatic_order_update_sync	= WC_TradeGecko_Init::get_setting( 'automatic_order_update_sync' );
		$this->order_update_sync_time_interval	= (int) WC_TradeGecko_Init::get_setting( 'order_update_sync_time_interval' );
		$this->order_update_sync_time_period	= WC_TradeGecko_Init::get_setting( 'order_update_sync_time_period' );

		$this->automatic_inventory_sync		= WC_TradeGecko_Init::get_setting( 'automatic_inventory_sync' );
		$this->sync_inventory_time_interval	= (int) WC_TradeGecko_Init::get_setting( 'sync_inventory_time_interval' );
		$this->sync_inventory_time_period	= WC_TradeGecko_Init::get_setting( 'sync_inventory_time_period' );

		// Add sync custom schedule
		add_filter( 'cron_schedules', array( $this, 'add_sync_schedules' ) );

		// Schedule
		add_action( 'init', array( $this, 'add_scheduled_syncs' ) );

	}

	/**
	 * Adds custom schedule from admin setting
	 *
	 * @access public
	 * @since  1.0
	 * @param array $schedules - existing WP recurring schedules
	 * @return array
	 */
	public function add_sync_schedules( $schedules ) {

		if ( '1' == $this->automatic_sync ) {

			if ( $this->sync_time_interval ) {
				$schedules[ WC_TradeGecko_Init::$prefix . 'automatic_sync' ] = array(
					'interval' => $this->sync_time_interval * $this->get_time_in_seconds( $this->sync_time_period ),
					'display'  => sprintf( __( 'Every %d %s', WC_TradeGecko_Init::$text_domain ), $this->sync_time_interval, $this->get_time_to_display( $this->sync_time_period ) )
				);
			}

		}

		if ( '1' == $this->automatic_order_update_sync ) {

			if ( $this->order_update_sync_time_interval ) {
				$schedules[ WC_TradeGecko_Init::$prefix . 'order_update_automatic_sync' ] = array(
					'interval' => $this->order_update_sync_time_interval * $this->get_time_in_seconds( $this->order_update_sync_time_period ),
					'display'  => sprintf( __( 'Every %d %s', WC_TradeGecko_Init::$text_domain ), $this->order_update_sync_time_interval, $this->get_time_to_display( $this->order_update_sync_time_period ) )
				);
			}

		}

		if ( '1' == $this->automatic_inventory_sync ) {

			if ( $this->sync_inventory_time_interval ) {
				$schedules[ WC_TradeGecko_Init::$prefix . 'automatic_inventory_sync' ] = array(
					'interval' => $this->sync_inventory_time_interval * $this->get_time_in_seconds( $this->sync_inventory_time_period ),
					'display'  => sprintf( __( 'Every %d %s', WC_TradeGecko_Init::$text_domain ), $this->sync_inventory_time_interval, $this->get_time_to_display( $this->sync_inventory_time_period ) )
				);
			}

		}

		return $schedules;
	}

	/**
	 * Add scheduled events to wp-cron if not already added
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function add_scheduled_syncs() {

		if ( '1' == $this->automatic_sync ) {

			// Schedule inventory update
			if ( ! wp_next_scheduled( 'wc_tradegecko_synchronization' ) ) {
				wp_schedule_event(
					strtotime( $this->sync_time_interval .' '. $this->get_time_to_display( $this->sync_time_period ) ),
					WC_TradeGecko_Init::$prefix . 'automatic_sync',
					'wc_tradegecko_synchronization'
				);
			}

		} else {
			// If sync is disabled then clear the cron schedule
			wp_clear_scheduled_hook( 'wc_tradegecko_synchronization' );
		}

		if ( '1' == $this->automatic_order_update_sync ) {

			// Schedule inventory update
			if ( ! wp_next_scheduled( 'wc_tradegecko_order_update_synchronization' ) ) {
				wp_schedule_event(
					strtotime( $this->order_update_sync_time_interval .' '. $this->get_time_to_display( $this->order_update_sync_time_period ) ),
					WC_TradeGecko_Init::$prefix . 'order_update_automatic_sync',
					'wc_tradegecko_order_update_synchronization'
				);
			}

		} else {
			// If sync is disabled then clear the cron schedule
			wp_clear_scheduled_hook( 'wc_tradegecko_order_update_synchronization' );
		}

		if ( '1' == $this->automatic_inventory_sync ) {

			// Schedule inventory update
			if ( ! wp_next_scheduled( 'wc_tradegecko_inventory_synchronization' ) ) {
				wp_schedule_event(
					strtotime( $this->sync_inventory_time_interval .' '. $this->get_time_to_display( $this->sync_inventory_time_period ) ),
					WC_TradeGecko_Init::$prefix . 'automatic_inventory_sync',
					'wc_tradegecko_inventory_synchronization'
				);
			}

		} else {
			// If sync is disabled then clear the cron schedule
			wp_clear_scheduled_hook( 'wc_tradegecko_inventory_synchronization' );
		}

	}

	/**
	 * Return the time to display. Minutes, Hours, Days
	 *
	 * @param type $period
	 * @return string
	 */
	private function get_time_to_display( $period ) {

		if ( 'HOUR_IN_SECONDS' == $period ) {
			$time_to_display = 'hours';
		} elseif ( 'DAY_IN_SECONDS' == $period ) {
			$time_to_display = 'days';
		} else {
			$time_to_display = 'minutes';
		}

		return $time_to_display;

	}

	private function get_time_in_seconds( $period ) {

		if ( 'HOUR_IN_SECONDS' == $period ) {
			$time_in_seconds = HOUR_IN_SECONDS;
		} elseif ( 'DAY_IN_SECONDS' == $period ) {
			$time_in_seconds = DAY_IN_SECONDS;
		} else {
			$time_in_seconds = MINUTE_IN_SECONDS;
		}

		return $time_in_seconds;

	}

} new WC_TradeGecko_Cron();