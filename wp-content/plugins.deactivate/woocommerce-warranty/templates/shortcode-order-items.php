<p class="order-info">
    Order <mark class="order-number"><?php echo $order->get_order_number(); ?></mark>
    made on <mark class="order-date"><?php echo date('F d, y h:i A', strtotime($order->order_date)); ?></mark>.
</p>

<table width="100%" cellpadding="5" cellspacing="0" border="1px" class="shop_table">
    <tr>
        <th><?php _e('Item', 'wc_warranty'); ?></th>
        <th><?php _e('Action', 'wc_warranty'); ?></th>
    </tr>

<?php

    foreach ( $items as $item_idx => $item ) {
        $item_id        = (isset($item['id'])) ? $item['id'] : $item['product_id'];
        $metas          = $item['item_meta'];
        $warranty       = false;
        $warranty_idx   = false;
        $item_qty       = $item['qty'];
        $used_qty       = 0;

        if ( isset($metas['_bundled_by']) && !empty($metas['_bundled_by']) ) continue;

        if ( version_compare($woocommerce->version, '2.0', '>=') ) {
            foreach ( $metas as $key => $value ) {
                if ( $key == '_item_warranty' ) {
                    $warranty = maybe_unserialize( stripslashes($value[0]) );
                } elseif ( $key == '_item_warranty_selected' ) {
                    $warranty_idx = $value[0];
                }
            }
        } else {
            foreach ( $metas as $meta ) {
                if ( $meta['meta_name'] == '_item_warranty' ) {
                    $warranty = maybe_unserialize( $meta['meta_value'] );
                } elseif ( $meta['meta_name'] == '_item_warranty_selected' ) {
                    $warranty_idx = $meta['meta_value'];
                }
            }
        }
    ?>
    <tr>
    <td>
        <ul>
            <li><?php printf(__('Product Name: %s', 'wc_warranty'), get_the_title($item_id)); ?></li>
            <li><?php printf(__('Quantity: %s', 'wc_warranty'), $item['qty']); ?></li>
            <li><?php printf(__('Price: %s', 'wc_warranty'), woocommerce_price($item['line_total'])); ?></li>
        </ul>
    </td>
    <td>
        <?php

        if ( $warranty === false ) {
            $warranty = array('type' => 'no_warranty');
        }

        if ( $warranty['type'] == 'no_warranty' ) {
            $warranty_string = __('Product has no warranty', 'wc_warranty');
        } elseif ( $warranty['type'] == 'included_warranty' ) {
            if ( $warranty['length'] == 'lifetime' ) {
                $warranty_string = __('Lifetime Warranty', 'wc_warranty');
            } else {
                $edate              = ($completed) ? $completed : $order->order_date;
                $warranty_string    = __('Expiry Date: ', 'wc_warranty') . warranty_get_date($edate, $warranty['value'], $warranty['duration']);
            }
        } elseif ( $warranty['type'] == 'addon_warranty' ) {
            $addon              = $warranty['addons'][$warranty_idx];
            $edate              = ($completed) ? $completed : $order->order_date;
            $warranty_string    = __('Expiry Date: ', 'wc_warranty') . warranty_get_date($edate, $addon['value'], $addon['duration']);
        }

        // load warranty request for this order, if any
        $results = warranty_search( $order_id, $item_id, $item_idx );

        $warranty_actions       = '';
        $show_warranty_button   = false;

        if ($results === false) {
            // No request found, but product has warranty
            if ($warranty['type'] == 'included_warranty') {
                if ( $warranty['length'] == 'lifetime' ) {
                    $show_warranty_button = true;
                    //$warranty_actions = '<a class="warranty-button button" title="'. __('Click to request for warranty', 'wc_warranty') .'" href="'. add_query_arg( array('product_id' => $item_id, 'idx' => $item_idx) ) .'">'. __('Request Warranty', 'wc_warranty') .'</a>';
                } else {
                    $now    = current_time( 'timestamp' );
                    $expiry = false;

                    if ( $completed ) {
                        $expiry = strtotime( $completed . ' +'. $warranty['value'] .' '. $warranty['duration'] );
                    }

                    if ( $expiry && $now > $expiry ) {
                        // expired
                        $warranty_string .= '<strong>'. __('Expired Warranty', 'wc_warranty') .'</strong>';
                    } else {
                        $show_warranty_button = true;
                        //$warranty_actions = '<a class="warranty-button button" title="'. __('Click to request for warranty', 'wc_warranty') .'" href="'. add_query_arg( array('product_id' => $item_id, 'idx' => $item_idx) ) .'">'. __('Request Warranty', 'wc_warranty') .'</a>';
                    }
                }
            } elseif ( $warranty['type'] == 'addon_warranty' ) {
                $addon  = $warranty['addons'][$warranty_idx];
                $now    = current_time( 'timestamp' );
                $expiry = false;

                if ( $completed ) {
                    $expiry = strtotime( $completed . ' +'. $addon['value'] .' '. $addon['duration'] );
                }

                if ( $expiry && $now > $expiry ) {
                    // expired
                    $warranty_string .= '<br/><strong>'. __('Expired Warranty', 'wc_warranty') .'</strong>';
                } else {
                    $show_warranty_button = true;
                    //$warranty_actions = '<a class="warranty-button button" title="'. __('Click to request for warranty', 'wc_warranty') .'" href="'. add_query_arg( array('product_id' => $item_id, 'idx' => $item_idx) ) .'">'. __('Request Warranty', 'wc_warranty') .'</a>';
                }
            } else {
                // No warranty. Do nothing
            }
        } else {
            foreach ( $results as $result ) {
                $result = warranty_load( $result->ID );
                $warranty_string    = '';
                $warranty_details   = array();
                $used_qty           = $used_qty + $result['qty'];
                $status_term        = wp_get_post_terms( $result['ID'], 'shop_warranty_status' );
                $status             = $status_term[0]->name;

                $warranty_details[] = __('Warranty Request Status', 'wc_warranty') .': <strong>'.$status.'</strong>';
                $warranty_details[] = __('Updated: ', 'wc_warranty'). date('F d, Y h:i A', strtotime($result['post_modified']));

                if ($result['code']) {
                    $warranty_details[] = __('RMA Code', 'wc_warranty') .': <strong>'.$result['code'].'</strong>';
                }

                if ( $result['request_tracking_code'] == 'y' && empty($result['tracking_code']) ) {
                    // Ask for the shipping provider and tracking code
                    ob_start();

                    if ( WC_Warranty::$shipping_tracking ):
                    ?>
                    <div class="warranty-tracking-code-container">
                        <h4><?php _e('Shipping Tracking Code', 'wc_warranty'); ?></h4>

                        <form action="" method="post">

                            <p class="form-field">
                                <label class="form-label" for="shipping_provider_<?php echo $result['ID']; ?>"><?php _e('Shipping Provider', 'wc_warranty'); ?></label>
                                <select class="tracking_provider" name="tracking_provider" id="shipping_provider_<?php echo $result['ID']; ?>" data-request_id="<?php echo $result['ID']; ?>">
                                    <?php
                                    foreach ( WC_Warranty::$providers as $provider_group => $providers ) {
                                        echo '<optgroup label="' . $provider_group . '">';
                                        foreach ( $providers as $provider => $url ) {
                                            $selected = (sanitize_title($provider) == $result['tracking_provider']) ? 'selected' : '';
                                            echo '<option value="' . sanitize_title( $provider ) . '" '. $selected .'>' . $provider . '</option>';
                                        }
                                        echo '</optgroup>';
                                    }
                                    ?>
                                </select>
                            </p>

                            <p class="form-field">
                                <label class="form-label" for="tracking_code_<?php echo $result['ID']; ?>"><?php _e('Tracking Code', 'wc_warranty'); ?></label>
                                <input type="text" class="tracking_code" name="tracking_code" id="tracking_code_<?php echo $result['ID']; ?>" value="<?php echo $result['tracking_code']; ?>" data-request_id="<?php echo $result['ID']; ?>" />
                            </p>

                            <input type="hidden" name="action" value="set_tracking_code" />
                            <input type="hidden" name="request_id" value="<?php echo $result['ID']; ?>" />
                            <input type="submit" name="send" value="Add Tracking Code" class="button" />
                        </form>
                    </div>
                    <?php
                    else:
                    ?>
                    <div class="warranty-tracking-code-container">
                        <h4><?php _e('Shipping Tracking Code', 'wc_warranty'); ?></h4>

                        <form action="" method="post">

                            <p class="form-field">
                                <label class="form-label" for="tracking_code_<?php echo $result['ID']; ?>"><?php _e('Tracking Code', 'wc_warranty'); ?></label>
                                <input type="text" class="tracking_code" name="tracking_code" id="tracking_code_<?php echo $result['ID']; ?>" value="<?php echo $result['tracking_code']; ?>" data-request_id="<?php echo $result['ID']; ?>" />
                            </p>

                            <input type="hidden" name="action" value="set_tracking_code" />
                            <input type="hidden" name="request_id" value="<?php echo $result['ID']; ?>" />
                            <input type="submit" name="send" value="Add Tracking Code" class="button" />
                        </form>
                    </div>
                    <?php
                    endif;

                    $warranty_actions .= ob_get_clean();
                }

                if ( !empty($result['return_tracking_code']) || !empty($result['tracking_code']) ) {
                    ob_start();
                    ?>
                    <div class="warranty-tracking-container">
                        <h4><?php _e('Track Shipping', 'wc_warranty'); ?></h4>

                    <?php
                    if ( $result['request_tracking_code'] == 'y' ) {
                        if (! empty($result['tracking_code']) ) {
                            // Tracking code provided - show tracking link
                            ?>
                            <div class="customer-tracking-code-container">
                                <b><?php _e('Track Your Package', 'wc_warranty'); ?></b>
                            <?php
                            if (WC_Warranty::$shipping_tracking && !empty($result['tracking_provider']) ) {
                                $all_providers  = array();
                                $providers_name = array();

                                foreach ( WC_Warranty::$providers as $providers ) {
                                    foreach ( $providers as $provider => $format ) {
                                        $all_providers[sanitize_title( $provider )] = $format;
                                        $providers_name[sanitize_title( $provider )] = $provider;
                                    }
                                }

                                $provider   = $result['tracking_provider'];
                                $link       = $all_providers[$provider];
                                $link       = str_replace('%1$s', $result['tracking_code'], $link);
                                $link       = str_replace('%2$s', '', $link);

                                echo '<ul>';
                                echo '<li>Shipped via '. $providers_name[$provider] .'</li>';
                                echo '<li>Tracking Code: '. $result['tracking_code'] .' &mdash; <a href="'. $link .'" target="_blank">'. __('Track Shipment', 'wc_warranty') .'</a></li>';
                                echo '</ul>';
                            } else {
                                echo '<p>'. __('Tracking Number:', 'wc_warranty') .' '. $result['tracking_code'] .'</p>';
                            }
                            ?>
                            </div>
                            <?php
                        }
                    }

                    if (! empty($result['return_tracking_code']) ) {
                        if ( WC_Warranty::$shipping_tracking && !empty($result['return_tracking_provider']) ) {
                            ?>
                                <div class="store-tracking-code-container">
                                    <b><?php _e('Store\'s Shipment Tracking', 'wc_warranty'); ?></b>
                            <?php
                            $all_providers  = array();
                            $providers_name = array();

                            foreach ( WC_Warranty::$providers as $providers ) {
                                foreach ( $providers as $provider => $format ) {
                                    $all_providers[sanitize_title( $provider )] = $format;
                                    $providers_name[sanitize_title( $provider )] = $provider;
                                }
                            }

                            $provider   = $result['return_tracking_provider'];
                            $link       = $all_providers[$provider];
                            $link       = str_replace('%1$s', $result['return_tracking_code'], $link);
                            $link       = str_replace('%2$s', '', $link);

                            echo '<ul>';
                            echo '<li>'. __('Shipped via', 'wc_warranty') .' '. $providers_name[$provider] .'</li>';
                            echo '<li>'. __('Tracking Code:', 'wc_warranty') .' '. $result['return_tracking_code'] .' &mdash; <a href="'. $link .'" target="_blank">'. __('Track Return Shipment', 'wc_warranty') .'</a></li>';
                            echo '</ul>';
                            echo '</div>';
                        } else {
                            echo '<div class="store-tracking-code-container">
                                    <b>'. __('Store\'s Shipment Tracking', 'wc_warranty') .'</b>';
                            echo '<p>'. __('Tracking Number:', 'wc_warranty') .' '. $result['return_tracking_code'] .'</p>';
                            echo '</div>';
                        }
                    }

                    ?>
                    </div>
                    <?php
                    $warranty_actions .= ob_get_clean();
                }

                echo '<ul class="warranty-data">';

                foreach ( (array)$warranty_details as $detail ) {
                    echo '  <li>'. $detail .'</li>';
                }

                if ( isset($result['qty']) ) {
                    echo '  <li>Quantity: '. $result['qty'] .'</li>';
                }

                if (! empty($warranty_string) ) {
                    echo '  <li>'. $warranty_string .'</li>';
                }

                echo '</ul>';

                echo $warranty_actions;
            }

        }

        $remaining = warranty_get_quantity_remaining( $order_id, $item_id, $item_idx );

        echo '<p>'.$warranty_string.'</p>';

        if ( $warranty['type'] == 'no_warranty' ) {
            //echo $warranty_string;
        } elseif ( $show_warranty_button || $remaining > 0 ) {
            echo '<a class="warranty-button button" title="'. __('Click to request for warranty', 'wc_warranty') .'" href="'. add_query_arg( array( 'product_id' => $item_id, 'idx' => $item_idx) ) .'">'. get_option( 'warranty_button_text', __('Request Warranty', 'wc_warranty') ) .'</a>';
        }

        ?>
    </td>
    </tr>
    <?php
    }
?>
</table>
