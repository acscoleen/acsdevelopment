<div id="primary">
    <div id="content" role="main">
        <?php
        if ( isset($_GET['updated']) ) {
            echo '<div class="woocommerce-message">'. $_GET['updated'] .'</div>';
        }

        $order      = new WC_Order( $order_id );
        $include    = get_option( 'warranty_request_statuses', array() );

        if ( in_array($order->status, $include) && $this->order_has_warranty($order) ) {
            if (! $product_id ) {
                // show products in an order
                $completed  = get_post_meta( $order->id, '_completed_date', true);
                $items      = $order->get_items();

                if ( empty($completed) ) {
                    $completed = false;
                }

                include WC_Warranty::$base_path .'templates/shortcode-order-items.php';
            } else {
                // Request warranty on selected product
                $items  = $order->get_items();
                $idx    = (int)$_GET['idx'];

                include WC_Warranty::$base_path .'templates/shortcode-request-form.php';
            }
        } else {
            echo '<div class="woocommerce-error">'. __('There are no valid warranties for this order', 'wc_warranty') .'</div>';
            echo '<p><a href="'. get_permalink(woocommerce_get_page_id('myaccount')) .'" class="button">'. __('Back to My Account', 'wc_warranty') .'</a></p>';
        }

        ?>
    </div>
</div>
