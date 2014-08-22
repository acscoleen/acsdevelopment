<div id="search_form" <?php if ($searched || $form_view) echo 'style="display:none;"'; ?>>
    <form action="admin.php" method="get">
        <h4><?php _e('Search for an Order', 'wc_warranty'); ?></h4>

        <input type="hidden" name="page" value="warranty_requests" />
        <input type="hidden" name="tab" value="new" />

        <p>
            <select name="search_key">
                <option value="order_id"><?php _e('Order Number', 'wc_warranty'); ?></option>
                <option value="email"><?php _e('Customer Email', 'wc_warranty'); ?></option>
                <option value="name"><?php _e('Customer Name', 'wc_warranty'); ?></option>
            </select>

            <input type="text" name="search_term" id="search_term" value="<?php if (isset($_GET['search_term'])) echo esc_attr($_GET['search_term']); ?>" class="short" />

            <input type="submit" id="order_search_button" class="button-primary" value="<?php _e('Search', 'wc_warranty'); ?>" />
        </p>
    </form>
</div>
<?php if ($searched || $form_view): ?>
<p><input type="button" class="toggle_search_form button" value="Show Search Form" /></p>
<?php endif; ?>

<?php if ( $searched && empty($orders) ): ?>
<div class="error"><p><?php _e('No orders found', 'wc_warranty'); ?></p></div>
<?php endif; ?>

<?php if ( !empty($orders) ): ?>
<form method="get" action="admin.php">
    <table class="wp-list-table widefat fixed" cellspacing="0">
        <thead>
            <tr>
                <th scope="col" id="order_id" class="manage-column column-order_id" style="width: 100px;"><?php _e('Order ID', 'wc_warranty'); ?></th>
                <th scope="col" id="order_customer" class="manage-column column-order_customer" style="width: 200px;"><?php _e('Customer', 'wc_warranty'); ?></th>
                <th scope="col" id="order_status" class="manage-column column-status" style="width: 100px;"><?php _e('Order Status', 'wc_warranty'); ?></th>
                <th scope="col" id="order_items" class="manage-column column-order_items"><?php _e('Order Items', 'wc_warranty'); ?></th>
                <th scope="col" id="order_items" class="manage-column column-order_items" style="width: 100px;"><?php _e('Date', 'wc_warranty'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ( $orders as $order_row):
                $order = new WC_Order( $order_row->id );
            ?>
            <tr class="alternate">
                <td class="order_id column-order_id"><?php echo $order_row->id; ?></td>
                <td class="order_id column-order_customer"><?php echo $order->billing_first_name .' '. $order->billing_last_name; ?></td>
                <td class="order_status column-status"><?php echo $order->status; ?></td>
                <td class="order_items column-order_items">
                    <ul class="order-items">
                    <?php
                    foreach ( $order->get_items() as $item_idx => $item ):
                        $item_id = (isset($item['product_id'])) ? $item['product_id'] : $item['id'];

                        // variation support
                        if ( isset($item['variation_id']) && $item['variation_id'] > 0 )
                            $item_id = $item['variation_id'];

                        $product = (function_exists('get_product')) ? get_product( $item_id ) : new WC_Product($item_id);

                    ?>
                        <li>
                            <?php echo $product->get_title(); ?>
                            <?php if (isset($item['Warranty'])): ?>
                            <span class="description">(Warranty: <?php echo $item['Warranty']; ?>)</span>
                            <?php endif; ?>
                            &times;
                            <?php echo $item['qty']; ?>
                            &mdash;
                            <a href="admin.php?page=warranty_requests&amp;tab=new&amp;order_id=<?php echo $order->id; ?>&amp;product_id=<?php echo $item_id; ?>&amp;idx=<?php echo $item_idx; ?>" class="button"><?php _e('Create Request', 'wc_warranty'); ?></a>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                </td>
                <td class="order_id column-order_date"><?php echo $order->order_date; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</form>
<?php endif; ?>

<?php
global $wc_warranty;
if ( isset($_GET['order_id'], $_GET['product_id'], $_GET['idx']) ):
    if ( isset($_GET['error']) ) {
        echo '<div class="error"><p>'. $_GET['error'] .'</p></div>';
    }

    $order          = new WC_Order( $_GET['order_id'] );
    $has_warranty   = $this->order_has_warranty($order);
    $items          = $order->get_items();
    $item           = (isset($items[$_GET['idx']])) ? $items[$_GET['idx']] : false;
    $max            = 0;

    if ( $item ) {
        if ( $has_warranty && $item['qty'] > 1 ) {
            $max = warranty_get_quantity_remaining( $_GET['order_id'], $_GET['product_id'], $_GET['idx'] );
        } else {
            $max = $item['qty'] - warranty_count_quantity_used( $_GET['order_id'], $_GET['product_id'], $_GET['idx'] );
        }
    }

    if ( $max < 1 ) {
        echo '<div class="message error"><p><strong>'. __('No available warranties for products in this order.', 'wc_warranty') .'</strong></p></div>';
    } else {
        ?><form method="post" action="admin-post.php" enctype="multipart/form-data"><?php
        if ( $max > 1 ):
            ?>
            <p>
                <?php _e('Quantity', 'wc_warranty'); ?><br/>
                <select name="warranty_qty">
                    <?php for ( $x = 1; $x <= $max; $x++ ): ?>
                        <option value="<?php echo $x; ?>"><?php echo $x; ?></option>
                    <?php endfor; ?>
                </select>
            </p>
        <?php else: ?>
            <input type="hidden" name="warranty_qty" value="1" />
        <?php
        endif;

        WC_Warranty::render_warranty_form();

        ?>
        <input type="hidden" name="order_id" value="<?php echo $_GET['order_id']; ?>" />
        <input type="hidden" name="product_id" value="<?php echo $_GET['product_id']; ?>" />
        <input type="hidden" name="index" value="<?php echo $_GET['idx']; ?>" />
        <input type="hidden" name="action" value="warranty_create" />
        <input type="submit" name="submit" value="Submit" class="button">
        </form>
        <?php
    }
endif;
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    $(".toggle_search_form").click(function() {
        if ( $("#search_form").is(":visible") ) {
            $(this).val("Show Search Form");
            $("#search_form").hide();
        } else {
            $(this).val("Hide Search Form");
            $("#search_form").show();
        }
    });

    jQuery(".help_tip").tipTip();
});
</script>
