<?php

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Warranty_Reports_List_Table extends WP_List_Table {

    public $valid_orders = array();

    function __construct( $args = array() ) {
        parent::__construct($args);
    }

    function get_columns(){
        $columns = array(
            'order_id'  => __('Order ID', 'wc_warranty'),
            'customer'  => __('Customer Name', 'wc_warranty'),
            'product'   => __('Product', 'wc_warranty'),
            'validity'  => __('Validity', 'wc_warranty'),
            'date'      => __('Order Date', 'wc_warranty')
        );
        return $columns;
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'order_id'  => array('order_id',false),
            'date'      => array('date',false)
        );
        return $sortable_columns;
    }

    function prepare_items() {
        global $wpdb;

        $columns    = $this->get_columns();
        $hidden     = array();

        $sortable   = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $per_page       = 10;
        $current_page   = $this->get_pagenum();
        $start          = ($current_page * $per_page) - $per_page;
        $where          = '';

        if ( isset($_GET['s']) && !empty($_GET['s']) ) {
            $where = " AND ID LIKE %{$_GET['s']}%";
        }

        $order_items    = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS im.*, i.order_id FROM {$wpdb->prefix}woocommerce_order_itemmeta im, {$wpdb->prefix}woocommerce_order_items i WHERE im.meta_key = '_item_warranty' AND im.order_item_id = i.order_item_id $where ORDER BY i.order_id DESC LIMIT $start,$per_page");
        $total_rows     = $wpdb->get_var("SELECT FOUND_ROWS()");

        foreach ( $order_items as $i => $item ) {
            $order = new WC_Order($item->order_id);

            if ( $order->status != 'completed' && $order->status != 'processing' ) {
                unset($order_items[$i]);
                $total_rows--;
            } else {
                $warranty       = maybe_unserialize($item->meta_value);
                $addon_index    = woocommerce_get_order_item_meta( $item->order_item_id, '_item_warranty_selected', true );
                $product_id     = woocommerce_get_order_item_meta( $item->order_item_id, '_product_id', true );

                if ( $warranty['type'] == 'addon_warranty' && $addon_index == '' ) {
                    unset($order_items[$i]);
                    $total_rows--;
                }
            }
        }

        $this->items    = $order_items;

        $this->set_pagination_args( array(
            'total_items' => $total_rows,
            'per_page'    => $per_page
        ) );

        wp_reset_postdata();
    }

    function column_order_id($item) {

        $order = new WC_Order($item->order_id);

        if ( class_exists('WC_Seq_Order_Number') ) {
            $order_id = $GLOBALS['wc_seq_order_number']->find_order_by_order_number( $item->order_id );

            if ( $order_id ) {
                return '<a href="post.php?post='. $order_id .'&action=edit">#'. $order_id .'</a>';
            } else {
                return '<a href="post.php?post='. $order->id .'&action=edit">'. $order->get_order_number() .'</a>';
            }
        } else {
            return '<a href="post.php?post='. $order->id .'&action=edit">'. $order->get_order_number() .'</a>';
        }

        return '<a href="post.php?post='. $order->id .'&action=edit">'. $order->get_order_number() .'</a>';
    }

    function column_customer($item) {
        $order = new WC_Order($item->order_id);
        $first_name = $order->billing_first_name;
        $last_name  = $order->billing_last_name;

        return $first_name .' '. $last_name;
    }

    function column_product($item) {
        global $woocommerce, $wpdb;

        $warranty       = maybe_unserialize($item->meta_value);
        $addon_index    = woocommerce_get_order_item_meta( $item->order_item_id, '_item_warranty_selected', true );
        $product_id     = woocommerce_get_order_item_meta( $item->order_item_id, '_product_id', true );

        if ( $warranty ) {
            echo '<a href="post.php?post='. $product_id .'&action=edit">'. get_the_title($product_id) .'</a>';
        }

    }

    function column_validity($item) {
        $warranty       = maybe_unserialize($item->meta_value);
        $addon_index    = woocommerce_get_order_item_meta( $item->order_item_id, '_item_warranty_selected', true );
        $product_id     = woocommerce_get_order_item_meta( $item->order_item_id, '_product_id', true );

        if ( $warranty ) {
            if ( $warranty['type'] == 'addon_warranty' ) {
                $valid_until    = false;

                // order's date of completion must be within the warranty period
                $completed      = get_post_meta( $item->order_id, '_completed_date', true);

                if (! empty($completed) ) {
                    $addon          = $warranty['addons'][$addon_index];
                    $date           = warranty_get_date( $completed, $addon['value'], $addon['duration'] );

                    echo $date;
                }

            } elseif ( $warranty['type'] == 'included_warranty' ) {
                if ( $warranty['length'] == 'lifetime' ) {
                    echo __('Lifetime', 'wc_warranty');
                } else {
                    // order's date of completion must be within the warranty period
                    $valid_until    = false;
                    $completed      = get_post_meta( $order->id, '_completed_date', true);

                    if (! empty($completed) ) {
                        $addon          = $warranty['addons'][$addon_index];
                        $date           = warranty_get_date( $completed, $addon['value'], $addon['duration'] );

                        echo $date;
                    }
                }
            }
        }
    }

    function column_date($item) {
        $order = new WC_Order($item->order_id);
        return date( get_option('date_format') .' '. get_option('time_format'), strtotime($order->modified_date) );
    }

    function no_items() {
        _e( 'No requests found.', 'wc_warranty' );
    }

}

echo '<style type="text/css">
table.woocommerce_page_warranty_requests #status { width: 200px; }
.wc-updated {width: 95%; margin: 5px 0 15px; background-color: #ffffe0; border-color: #e6db55; padding: 0 .6em; -webkit-border-radius: 3px; border-radius: 3px; border-width: 1px; border-style: solid;}
.wc-updated p {margin: .5em 0 !important; padding: 2px;}
</style>';

if ( isset($_GET['updated']) ) {
    echo '<div class="updated"><p>'. $_GET['updated'] .'</p></div>';
}
$warranty_table = new Warranty_Reports_List_Table();
$warranty_table->prepare_items();
?>

<form action="admin.php" method="get" style="margin-top: 20px;">
    <input type="hidden" name="page" value="warranty_requests" />
    <input type="hidden" name="tab" value="list" />

    <p class="search-box">
        <label class="screen-reader-text" for="search"><?php _e('Search', 'wc_warranty') ?>:</label>
        <input type="search" id="search" name="s" value="<?php _admin_search_query(); ?>" placeholder="Order #" />
        <?php submit_button( 'Search', 'button', false, false, array('id' => 'search-submit') ); ?>
    </p>
</form>

<?php $warranty_table->display(); ?>

