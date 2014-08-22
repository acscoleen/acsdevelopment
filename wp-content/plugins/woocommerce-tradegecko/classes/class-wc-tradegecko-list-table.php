<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * WC_TradeGecko_List_Table class to generate the update and error logs
 *
 * Extends WP_List_Table Class
 *
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WC_TradeGecko_List_Table extends WP_List_Table {

	public function __construct( $data ) {

		$this->data = $data;

		parent::__construct( array(
			'singular' => __( 'Synchronization Log', WC_TradeGecko_Init::$text_domain ),
			'plural'   => __( 'Synchronization Logs', WC_TradeGecko_Init::$text_domain ),
			'ajax'     => false
		) );
	}

	/**
	 * Places the text inside the table column
	 * 
	 * @access public
	 * @since 1.0
	 * @param array $item
	 * @param array $column_name
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'order_num':
			case 'datetime':
			case 'log_type':
			case 'action':	
				return $item[$column_name];
		}
	}

	/**
	 * Builds the table column order and titles 
	 * 
	 * @access public
	 * @since 1.0
	 * @return type
	 */
	public function get_columns() {
		$columns = array(
			'order_num'	=> __( '#', WC_TradeGecko_Init::$text_domain ),
			'datetime'	=> __( 'Date', WC_TradeGecko_Init::$text_domain ),
			'log_type'	=> __( 'Log Type', WC_TradeGecko_Init::$text_domain ),
			'action'	=> __( 'Log Message', WC_TradeGecko_Init::$text_domain )
		);

		return $columns;
	}

	/**
	 * Prepare the column items to display <br />
	 * Puts everything together before we display the list table
	 * 
	 * @access public
	 * @since 1.0
	 */
	public function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		usort( $this->data, array( $this, 'usort_reorder' ) );

		$this->items = $this->data;
	}

	/**
	 * Add the sortable columns
	 * 
	 * @access public
	 * @since 1.0
	 * @return array
	 */
	function get_sortable_columns() {
		$sortable_columns = array(
		    'order_num'         => array('order_num',false),
		    'datetime'          => array('datetime',false),
		    'log_type'          => array('log_type',false),
		);
		return $sortable_columns;
	}

	/**
	 * Display message if there are no items
	 * 
	 * @access public
	 * @since 1.0
	 */
	function no_items() {
		?>
		<p><?php _e( 'No Synchronization logs yet.', WC_TradeGecko_Init::$text_domain ); ?></p>
		<?php
	}

	/**
	 * Filter and sort the values
	 * 
	 * @access public
	 * @since 1.0
	 * @param type $a
	 * @param type $b
	 * @return type
	 */
	function usort_reorder( $a, $b ) {
		$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'order_num';
		$order = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc';
		$result = strnatcmp( $a[ $orderby ], $b[ $orderby ] );

		return ( $order === 'asc' ) ? $result : -$result;
	}
}
