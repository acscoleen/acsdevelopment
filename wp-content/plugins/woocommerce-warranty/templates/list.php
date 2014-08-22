<?php

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Warranty_List_Table extends WP_List_Table {

    private $form;
    private $inputs;

    function __construct( $args = array() ) {
        parent::__construct($args);

        $this->form   = get_option( 'warranty_form' );
        $this->inputs = json_decode($this->form['inputs']);

        $this->statuses = warranty_get_statuses();
    }

    function get_columns(){
        $columns = array(
            'status'    => __('Status', 'wc_warranty'),
            'order_id'  => __('Order ID', 'wc_warranty'),
            'customer'  => __('Customer Name', 'wc_warranty'),
            'product'   => __('Product', 'wc_warranty'),
            'rma'       => __('RMA #', 'wc_warranty')
        );

        foreach ( $this->inputs as $input ) {
            if ( $input->type == 'paragraph') continue;

            $field = $this->form['fields'][$input->key];
            $columns[$input->key] = $field['name'];
        }

        $columns['tracking']    = __('Tracking #', 'wc_warranty');
        $columns['date']        = __('Date', 'wc_warranty');

        return $columns;
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'order_id'  => array('order_id',false),
            'status'    => array('warranty_request_status',false),
            'date'      => array('date',false)
        );
        return $sortable_columns;
    }

    function extra_tablenav( $which ) {
        if ( $which == 'top' ) {

            echo '<form action="admin.php" method="get" style="margin-top: 20px;">';
            echo '  <div class="alignleft actions">';
            echo '      <select name="status" id="status" class="postform">';
            echo '          <option value="">'. __('All Statuses', 'wc_warranty') .'</option>';

            foreach ($this->statuses as $status ) {
                $selected = ( isset($_GET['status']) && $status->slug == $_GET['status'] ) ? 'selected' : '';
                echo '          <option value="'. $status->slug .'" '. $selected .'>'. $status->name .'</option>';
            }

            echo '      </select>';
            echo '      <input type="hidden" name="page" value="warranty_requests" />';
            submit_button( __( 'Filter' ), 'secondary', false, false, array( 'id' => 'post-query-submit' ) );
            echo '  </div>';
            echo '</form>';
        }
    }

    function prepare_items() {
        $columns    = $this->get_columns();
        $hidden     = array();
        //$sortable   = $this->get_sortable_columns();
        $sortable   = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $per_page       = 10;
        $current_page   = $this->get_pagenum();
        $query_args     = array(
            'post_type'         => 'warranty_request',
            'orderby'           => 'date',
            'order'             => 'DESC',
            'posts_per_page'    => $per_page,
            'paged'             => $current_page
        );

        // filter by status
        if ( isset($_GET['status']) && !empty($_GET['status']) ) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy'  => 'shop_warranty_status',
                    'field'     => 'slug',
                    'terms'     => $_GET['status']
                )
            );
        }

        if ( isset($_GET['s']) && !empty($_GET['s']) ) {
            $query_args['meta_query'][] = array(
                'key'       => '_code',
                'value'     => $_GET['s'],
                'compare'   => 'LIKE'
            );
        }

        $wp_query = new WP_Query();
        $wp_query->query($query_args);

        $total_items = $wp_query->found_posts;

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page
        ) );

        $this->items = array();

        while ( $wp_query->have_posts() ) :
            $wp_query->the_post();
            $id = get_the_ID();
            $this->items[] = warranty_load($id);
        endwhile;

        wp_reset_postdata();
    }

    function column_default( $item, $column_name ) {
        foreach ( $this->inputs as $input ) {
            $key    = $input->key;
            $type   = $input->type;

            if ( $type == 'paragraph' ) continue;

            if ( $column_name == $key ) {
                $value = get_post_meta( $item['ID'], '_field_'.$key, true );

                if ( is_array($value) )
                    $value = implode( ',<br/>', $value );

                if ($type == 'file' && !empty($value)) {
                    $wp_uploads = wp_upload_dir();
                    $value = '<a href="'. $wp_uploads['baseurl'] . $value .'">'. basename($value) .'</a>';
                }

                if (! $value )
                    $value = '-';

                return $value;
            }
        }
        switch( $column_name ) {
            case 'order_id':
            case 'customer':
            case 'product':
            case 'rma':
            case 'tracking':
            case 'date':
                return $item[ $column_name ];
                break;
            default:
                //return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
                break;
        }
    }

    function column_order_id($item) {
        $wc_order = new WC_Order();

        if (! $wc_order->get_order( $item['order_id'] ) ) {

            if ( class_exists('WC_Seq_Order_Number') ) {
                $order_id = $GLOBALS['wc_seq_order_number']->find_order_by_order_number( $item['order_id'] );

                if ( $order_id ) {
                    return '<a href="post.php?post='. $order_id .'&action=edit">#'. $item['order_id'] .'</a>';
                } else {
                    return '#'. $item['order_id'];
                }
            } else {
                return '#'. $item['order_id'];
            }

        }

        return '<a href="post.php?post='. $item['order_id'] .'&action=edit">'. $wc_order->get_order_number() .'</a>';
    }

    function column_status($item) {
        $permissions= get_option('warranty_permissions', array());
        $returned   = get_option('warranty_returned_status', 'completed');
        $term       = wp_get_post_terms( $item['ID'], 'shop_warranty_status' );
        $status     = $term[0];
        $me         = wp_get_current_user();
        $readonly   = true;

        if (in_array('administrator', $me->roles)) {
            $readonly = false;
        } elseif (!isset($permissions[$status->slug]) || empty($permissions[$status->slug])) {
            $readonly = false;
        } elseif (in_array($me->ID, $permissions[$status->slug])) {
            $readonly = false;
        }

        if ( $readonly ) {
            $content = ucfirst($status->name);
        } else {
            $content    = '<select name="status" id="status_'. $item['ID'] .'">';

            foreach ($this->statuses as $_status ):
                $sel        = ($status->slug == $_status->slug) ? 'selected' : '';
                $content   .= '<option value="'. $_status->slug .'" '. $sel .'>'. ucfirst($_status->name) .'</option>';
            endforeach;

            $content .= '</select>
            <button class="button wc-reload update-status" type="button" title="Apply" data-request_id="'. $item['ID'] .'"><span>Apply</span></button>
        ';

            $actions = array(
                'edit-tracking' => '<a href="#" class="edit-codes" data-request_id="'. $item['ID'] .'">'. __('Shipping Tracking', 'wc_warranty') .'</a>',
                'trash'        => '<a href="'. wp_nonce_url( 'admin-post.php?action=warranty_delete&id='. $item['ID'], 'warranty_delete') .'" class="submitdelete warranty-delete">'. __('Delete', 'wc_warranty') .'</a>'
            );

            $product_id     = get_post_meta( $item['ID'], '_product_id', true );

            $product        = (function_exists('get_product')) ? get_product($product_id) : new WC_Product($product_id);
            $manage_stock   = '';

            if ( $product->is_type('variation') ) {
                $stock = get_post_meta( $product->variation_id, '_stock', true );

                if ($stock > 0)
                    $manage_stock = 'yes';
            } else {
                $manage_stock = get_post_meta( $product_id, '_manage_stock', true );
            }

            if ( $status->slug == $returned && $manage_stock == 'yes' ) {
                if ( get_post_meta( $item['ID'], '_returned', true ) == 'yes' ) {
                    $actions['inventory-return'] = 'Stock Returned';
                } else {
                    $actions['inventory-return'] = '<a href="admin-post.php?action=warranty_return_inventory&id='. $item['ID'] .'">Return Stock</a>';
                }
            }

            $content = sprintf('%1$s %2$s', $content, $this->row_actions($actions) );
        }

        return $content;
    }

    function column_customer($item) {
        $first_name = get_post_meta( $item['ID'], '_first_name', true );
        $last_name  = get_post_meta( $item['ID'], '_last_name', true );

        if ( !$first_name || !$last_name ) {
            $order = new WC_Order($item['order_id']);
            $first_name = $order->billing_first_name;
            $last_name  = $order->billing_last_name;
        }

        return $first_name .' '. $last_name;
    }

    function column_product($item) {
        $product_name = get_post_meta( $item['ID'], '_product_name', true );

        if (! $product_name ) {
            $product_name = '<a href="post.php?post='. $item['product_id'] .'&action=edit">'. get_the_title($item['product_id']) .' &mdash; #'. $item['product_id'] .'</a>';
        }

        if ( isset($item['qty']) ) {
            return $product_name .' &times; '. $item['qty'];
        } else {
            return $product_name;
        }


    }

    function column_note($item) {
        return (isset($item['answer'])) ? $item['answer'] : '-';
    }

    function column_rma($item) {
        return $item['code'];
    }

    function column_tracking($item) {
        $output = '';
        if ( WC_Warranty::$shipping_tracking ) {
            if (! empty($item['tracking_code']) && ! empty($item['tracking_provider']) ) {
                $all_providers = array();

                foreach ( WC_Warranty::$providers as $providers ) {
                    foreach ( $providers as $provider => $format ) {
                        $all_providers[sanitize_title( $provider )] = $format;
                    }
                }

                $provider   = $item['tracking_provider'];
                $link       = $all_providers[$provider];
                $link       = str_replace('%1$s', $item['tracking_code'], $link);
                $link       = str_replace('%2$s', '', $link);
                $output .= '<p><b>Customer:</b> <a href="'. $link .'" target="_blank">'. __('Track Shipment', 'wc_warranty') .'</a></p>';
            }

            if (! empty($item['return_tracking_code']) && ! empty($item['return_tracking_provider']) ) {
                $all_providers = array();

                foreach ( WC_Warranty::$providers as $providers ) {
                    foreach ( $providers as $provider => $format ) {
                        $all_providers[sanitize_title( $provider )] = $format;
                    }
                }

                $provider   = $item['return_tracking_provider'];
                $link       = $all_providers[$provider];
                $link       = str_replace('%1$s', $item['return_tracking_code'], $link);
                $link       = str_replace('%2$s', '', $link);
                $output .= '<p><b>Store:</b> <a href="'. $link .'" target="_blank">'. __('Track Shipment', 'wc_warranty') .'</a></p>';
            }
        } else {
            if (! empty($item['tracking_code']) ) {
                $output .= '<p><b>Customer:</b> '. $item['tracking_code'] .'</p>';
            }

            if (! empty($item['return_tracking_code']) ) {
                $output .= '<p><b>Store:</b> '. $item['return_tracking_code'] .'</p>';
            }
        }

        if ( empty($output) ) {
            $output = '-';
        }

        echo $output;
    }

    function column_date($item) {
        return $item['post_modified'];
    }

    function column_attachment($item) {
        if (! empty($item['attachment']) ) {
            $wp_uploads = wp_upload_dir();
            echo '<a href="'. $wp_uploads['baseurl'] . $item['attachment'] .'">'. basename($item['attachment']) .'</a>';
        } else {
            echo '-';
        }

    }

    function no_items() {
        _e( 'No requests found.', 'wc_warranty' );
    }

    function single_row( $post, $level = 0 ) {
        parent::single_row( $post, $level );
        $request = warranty_load($post['ID']);
        //var_dump($request);
        ?>
        <tr id="edit-<?php echo $post['ID']; ?>" class="hidden form_tr forms_<?php echo $post['ID']; ?>">
            <td colspan="10">
                <p style="float: right; margin-top: 5px;">
                    <input type="button" class="button close_tr" value="&times;" />
                </p>

                <?php
                // if tracking code is being requested, notify the admin
                if ( $request['request_tracking_code'] == 'y' && empty($request['tracking_code']) ):
                    echo '<div class="wc-updated"><p>'. __('Tracking already requested', 'wc_warranty') .'</p></div>';
                endif;
                ?>

                <div class="codes_form closeable" style="display:none;">
                    <?php
                    // Tracking code hasnt been requested yet
                    if ($request['request_tracking_code'] != 'y'):
                    ?>

                    <form action="admin-post.php" method="post" style="margin-bottom: 20px;">
                        <strong><?php _e('Request tracking code from the Customer', 'wc_warranty'); ?></strong>
                        <br/>
                        <input type="hidden" name="action" value="warranty_update" />
                        <input type="hidden" name="type" value="request_code" />
                        <input type="hidden" name="request_id" value="<?php echo $request['ID']; ?>" />
                        <input type="submit" name="request_code" value="<?php _e('Request Tracking Code', 'wc_warranty'); ?>" class="button-secondary" />
                    </form>
                    <?php
                    else: // tracking code requested
                        // if tracking code is not empty, it has already been provided
                        if (! empty($request['tracking_code']) ) {
                            echo '<strong>'. __('Customer Provided Tracking', 'wc_warranty') .':</strong>&nbsp;';

                            if ( WC_Warranty::$shipping_tracking ) {
                                $all_providers = array();

                                foreach ( WC_Warranty::$providers as $providers ) {
                                    foreach ( $providers as $provider => $format ) {
                                        $all_providers[sanitize_title( $provider )] = $format;
                                    }
                                }

                                $provider   = $request['tracking_provider'];
                                $link       = $all_providers[$provider];
                                $link       = str_replace('%1$s', $request['tracking_code'], $link);
                                $link       = str_replace('%2$s', '', $link);
                                echo '<a href="'. $link .'" target="_blank">'. __('Track Shipment', 'wc_warranty') .'</a>';
                            } else {
                                echo $request['tracking_code'];
                            }
                        }
                    endif;
                    ?>
                </div>

                <div class="codes_form closeable" style="display:none;">
                    <?php if ( WC_Warranty::$shipping_tracking ): ?>
                    <form action="admin-post.php" method="post">
                        <p>
                            <label for="return_tracking_provider_<?php echo $request['ID']; ?>"><strong><?php _e('Shipping Provider', 'wc_warranty'); ?></strong></label>
                            <select class="return_tracking_provider" name="return_tracking_provider" id="return_tracking_provider_<?php echo $request['ID']; ?>">
                                <?php
                                foreach ( WC_Warranty::$providers as $provider_group => $providers ) {
                                    echo '<optgroup label="' . $provider_group . '">';
                                    foreach ( $providers as $provider => $url ) {
                                        $selected = (sanitize_title($provider) == $request['return_tracking_provider']) ? 'selected' : '';
                                        echo '<option value="' . sanitize_title( $provider ) . '" '. $selected .'>' . $provider . '</option>';
                                    }
                                    echo '</optgroup>';
                                }
                                ?>
                            </select>
                        </p>
                        <p>
                            <label for="return_tracking_code_<?php echo $request['ID']; ?>"><strong><?php _e('Shipping Tracking Code', 'wc_warranty'); ?></strong></label>
                            <input type="text" class="tracking_code regular-text" name="return_tracking_code" id="return_tracking_code_<?php echo $request['ID']; ?>" value="<?php echo $request['return_tracking_code']; ?>" placeholder="1234567890" />
                            <span class="description"><?php _e('Shipping Details/Tracking', 'wc_warranty'); ?></span>
                            <br/>
                            <input type="hidden" name="action" value="warranty_update" />
                            <input type="hidden" name="type" value="set_return_tracking" />
                            <input type="hidden" name="request_id" value="<?php echo $request['ID']; ?>" />
                            <input type="submit" name="set_tracking" value="<?php _e('Set Return Tracking Code', 'wc_warranty'); ?>" class="button-secondary" />
                        </p>
                    </form>
                    <?php else: ?>
                    <form action="admin-post.php" method="post">
                        <p>
                            <label for="return_tracking_code_<?php echo $request['ID']; ?>"><strong><?php _e('Shipping Tracking Code', 'wc_warranty'); ?></strong></label>
                            <input type="text" class="tracking_code regular-text" name="return_tracking_code" id="return_tracking_code_<?php echo $request['ID']; ?>" value="<?php echo $request['return_tracking_code']; ?>" placeholder="1234567890" />
                            <span class="description"><?php _e('Shipping Details/Tracking', 'wc_warranty'); ?></span>
                            <br/>
                            <input type="hidden" name="action" value="warranty_update" />
                            <input type="hidden" name="type" value="set_return_tracking" />
                            <input type="hidden" name="request_id" value="<?php echo $request['ID']; ?>" />
                            <input type="submit" name="set_tracking" value="<?php _e('Set Return Tracking Code', 'wc_warranty'); ?>" class="button-secondary" />
                        </p>
                    </form>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
    }

    function display() {
        parent::display();

        // javascript code
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($){
            jQuery(".update-status").click(function() {
                var id      = $(this).data('request_id');
                var value   = $("#status_"+ id).val();

                if ( value ) {
                    var data = {"action": "warranty_update", "type": "change_status", "status": value, "request_id": id, "ajax": true};
                    jQuery.ajax({
                        type:"POST",
                        url: ajaxurl,
                        data : data,
                        success : function(response){
                            //button.parents('tr').hide();
                            //window.location.reload();
                            if ( response ) {
                                window.location.href = response;
                            }
                        }
                    });
                }
            });

            jQuery("a.edit-codes").click(function(e) {
                e.preventDefault();

                var req_id = $(this).data("request_id");

                $("tr.form_tr").hide();

                $("tr.forms_"+req_id).show();
                $("tr.forms_"+req_id+" .codes_form").show();
            });

            jQuery(".close-form").live("click", function(e) {
                e.preventDefault();

                $(this).parents("div.closeable").hide();
            });

            jQuery(".close_tr").click(function() {
                $(this).parents("tr").find("div.closeable").hide();
                $(this).parents("tr").hide();
            });

        });
        </script>
        <?php
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
$warranty_table = new Warranty_List_Table();
$warranty_table->prepare_items();
?>

<form action="admin.php" method="get" style="margin-top: 20px;">
    <input type="hidden" name="page" value="warranty_requests" />
    <input type="hidden" name="tab" value="list" />

    <p class="search-box">
        <label class="screen-reader-text" for="search"><?php _e('Search', 'wc_warranty') ?>:</label>
        <input type="search" id="search" name="s" value="<?php _admin_search_query(); ?>" placeholder="RMA #" />
        <?php submit_button( 'Search', 'button', false, false, array('id' => 'search-submit') ); ?>
    </p>
</form>

<?php $warranty_table->display(); ?>
