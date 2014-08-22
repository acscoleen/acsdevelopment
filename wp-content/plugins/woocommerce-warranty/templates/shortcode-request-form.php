<form name="warranty_form" id="warranty_form" method="POST" action="<?php echo add_query_arg( array('product_id' => $product_id, 'req' => 'new_warranty', 'idx' => $idx) ); ?>" enctype="multipart/form-data" >

    <?php if ( isset($_REQUEST['error']) ): ?>
    <ul class="woocommerce_error">
        <li><?php echo $_REQUEST['error']; ?></li>
    </ul>
    <?php endif; ?>
    <?php if ( isset($_REQUEST['errors']) ): ?>
        <div class="woocommerce-error">
            <?php _e('The following errors were found while processing your request:', 'wc_warranty'); ?>
            <ul>
            <?php
            $errors = json_decode(stripslashes($_REQUEST['errors']));
            foreach ($errors as $error):
            ?>
                <li><?php echo esc_html($error); ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php
    $item = (isset($items[$idx])) ? $items[$idx] : false;

    if ( $item && $item['qty'] > 1 ):
        $max = warranty_get_quantity_remaining( $order_id, $product_id, $idx );
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
    <p>
      <input type="submit" name="submit" value="Submit" class="button">
    </p>

</form>
