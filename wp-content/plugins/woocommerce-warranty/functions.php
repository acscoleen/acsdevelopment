<?php

/**
 * Loads a warranty request, along with its metadata
 *
 * @param int $request_id
 * @return array
 */
function warranty_load( $request_id ) {

    $warranty   = get_post( $request_id, ARRAY_A );

    if ( $warranty['post_type'] != 'warranty_request' ) return false;

    $defaults   = array(
        'code'                      => '',
        'tracking_code'             => '',
        'tracking_provider'         => '',
        'return_tracking_code'      => '',
        'return_tracking_provider'  => '',
        'request_tracking_code'     => ''
    );

    if ( $warranty ) {
        $custom = get_post_custom( $request_id );

        foreach ( $custom as $key => $value ) {
            $clean = ltrim($key, '_');
            $warranty[$clean] = $value[0];
        }
    }

    return wp_parse_args($warranty, $defaults);
}

function warranty_get_quantity_remaining( $order_id, $product_id, $idx ) {
    $order  = new WC_Order( $order_id );
    $items  = $order->get_items();
    $qty    = 0;

    if ( isset($items[$idx]) ) {
        $qty        = $items[$idx]['qty'];
        $warranties = warranty_search( $order_id, $product_id, $idx );

        if ( $warranties ) {
            $used = 0;
            foreach ( $warranties as $warranty ) {
                $warranty = warranty_load( $warranty->ID );
                $used += $warranty['qty'];
            }

            $qty -= $used;
        }
    }

    return $qty;
}

function warranty_count_quantity_used( $order_id, $product_id, $idx ) {
    $order  = new WC_Order( $order_id );
    $items  = $order->get_items();
    $used   = 0;

    if ( isset($items[$idx]) ) {
        $warranties = warranty_search( $order_id, $product_id, $idx );

        if ( $warranties ) {
            foreach ( $warranties as $warranty ) {
                $warranty = warranty_load( $warranty->ID );
                $used += $warranty['qty'];
            }
        }
    }

    return $used;
}

/**
 * Searches for warranty requests for a particular item in an order
 *
 * @param int $order_id
 * @param int $product_id
 * @param int $idx The index in the WC_Order::get_items results
 */
function warranty_search( $order_id, $product_id, $idx = null ) {
    $tax = array(
        array(
            'key'       => '_order_id',
            'value'     => $order_id,
            'compare'   => '='
        ),
        array(
            'key'       => '_product_id',
            'value'     => $product_id,
            'compare'   => '='
        )
    );

    if ( !is_null($idx) ) {
        $tax[] = array(
            'key'       => '_index',
            'value'     => $idx,
            'compare'   => '='
        );
    }

    $args = array(
        'post_type'     => 'warranty_request',
        'meta_query'    => $tax
    );

    $results = get_posts($args);

    if ( ! $results ) return false;

    return $results;
}

/**
 * Get all warranty statuses and allow other plugins to add their own
 *
 * @return array
 */
function warranty_get_statuses() {
    $defaults   = WC_Warranty::$default_statuses;
    $statuses   = get_terms( 'shop_warranty_status', array('hide_empty' => false) );
    $orders     = get_option( 'wc_warranty_status_order', array() );
    $orig_orders= $orders;
    $terms      = array();

    // make sure all statuses are inside $order
    foreach ( $statuses as $status ) {
        if (! in_array($status->slug, $orders) ) {
            $orders[] = $status->slug;
        }
    }

    if ( $orig_orders != $orders ) {
        update_option( 'wc_warranty_status_order', $orders );
    }

    foreach ( $orders as $slug ) {
        $term = get_term_by( 'slug', $slug, 'shop_warranty_status' );

        if (! $term ) continue;

        $terms[] = $term;
    }

    return apply_filters( 'warranty_statuses', $terms );
}

/**
 * Get the valid date based on the order date and warranty duration
 *
 * @param string $order_date
 * @param int $warranty_duration
 * @param string $warranty_unit
 * @return string
 */
function warranty_get_date($order_date, $warranty_duration, $warranty_unit) {
    $order_time = strtotime($order_date);

    $order_date = array(
        'month'     => date('n', $order_time),
        'day'       => date('j', $order_time),
        'year'      => date('Y', $order_time)
    );

    if ($warranty_unit == 'days') {
        $expired_time = $order_time + $warranty_duration*86400;
        $expired_date = date( 'Y-m-d', $expired_time )." 23:59:59";
        $expired_time = strtotime($expired_date);
    } elseif ( $warranty_unit == 'weeks' ) {
        $add = (86400 * 7) * $warranty_duration;
        $expired_time = $order_time + $add;
        $expired_date = date( 'Y-m-d', $expired_time )." 23:59:59";
        $expired_time = strtotime($expired_date);
    } elseif ( $warranty_unit == 'months' ) {
        $warranty_day   = $order_date['day'];
        $warranty_month = $order_date['month'] + $warranty_duration;
        $warranty_year  = $order_date['year'] + ($warranty_month / 12);
        $warranty_month = $warranty_month % 12;

        if (($warranty_month == 2) && ($warranty_day > 28)) $warranty_day = 29;

        if (checkdate($warranty_month, $warranty_day, $warranty_year) ) {
            $expired_time = mktime(23, 59, 59, $warranty_month, $warranty_day, $warranty_year);
        } else {
            $expired_time = mktime(23, 59, 59, $warranty_month, ($warranty_day - 1) , $warranty_year);
        }
    } elseif ( $warranty_unit == 'years' ) {
        $warranty_year = $order_date['year'] + $warranty_duration;

        if (checkdate($order_date['month'], $order_date['day'], $warranty_year) ) {
            $expired_time = mktime(23, 59, 59, $order_date['month'], $order_date['day'], $warranty_year);
        } else {
            $expired_time = mktime(23, 59, 59, $order_date['month'], ($order_date['day'] - 1) , $warranty_year);
        }
    }

    if ( $expired_time)
        return date('F d, Y', $expired_time);
}

/**
 * Process a request for warranty
 *
 * @param int $order_id
 * @param int $product_id
 * @return int $request_id
 */
function warranty_process_request( $order_id, $product_id, $idx = '' ) {
    global $wpdb;

    $qty = $_POST['warranty_qty'];

    $warranty = array(
        'post_content'  => '',
        'post_name'     => __('Warranty Request for Order #', 'wc_warranty') . $order_id,
        'post_status'   => 'publish',
        'post_author'   => 1,
        'post_type'     => 'warranty_request'
    );
    $request_id = wp_insert_post( $warranty );

    wp_set_post_terms( $request_id, 'new', 'shop_warranty_status' );

    $metas = array(
        'order_id'      => $order_id,
        'product_id'    => $product_id,
        'index'         => $idx,
        'qty'           => $qty,
        'code'          => warranty_generate_rma_code()
    );

    foreach ( $metas as $key => $value ) {
        add_post_meta( $request_id, '_'.$key, $value, true );
    }

    return $request_id;
}

/**
 * Send emails based on status change
 *
 * @uses warranty_variable_replacements()
 * @param int $request_id
 * @param string $status Status Slug
 */
function warranty_send_emails( $request_id, $status ) {
    global $wpdb, $woocommerce;

    $emails     = get_option( 'warranty_emails', array() );
    $request    = get_post($request_id);

    if ( empty($emails) || !$request ) return;

    if (! isset($emails[$status]) ) return;

    $mailer         = $woocommerce->mailer();
    $order          = new WC_Order( get_post_meta( $request_id, '_order_id', true ) );
    $admin_email    = get_option('admin_email');

    foreach ( $emails[$status] as $email ) {
        $subject    = $email['subject'];
        $message    = $email['message'];


        // Variable replacements
        $subject    = warranty_variable_replacements( $subject, $request_id );
        $message    = warranty_variable_replacements( $message, $request_id );

        // wrap message into the template
        $message    = $mailer->wrap_message( $subject, $message );

        if ( $email['recipient'] == 'customer' ) {
            $mailer->send( $order->billing_email, $subject, $message);
        } elseif ( $email['recipient'] == 'admin' ) {
            $mailer->send( $admin_email, $subject, $message);
        } else {
            // both
            $mailer->send( $order->billing_email, $subject, $message);
            $mailer->send( $admin_email, $subject, $message);
        }
    }
}

/**
 * Find and replace variable holders. Used by warranty_send_emails()
 *
 * @param string $input
 * @param int $request_id
 * @return string Parsed $input
 */
function warranty_variable_replacements( $input, $request_id ) {
    global $wpdb, $woocommerce;

    $request    = warranty_load( $request_id );
    $status_term= get_the_terms( $request_id, 'shop_warranty_status' );
    $status     = $status_term[0]->name;
    $order      = new WC_Order( $request['order_id'] );
    $product    = function_exists('get_product') ? get_product( $request['product_id'] ) : new WC_Product( $request['product_id'] );

    $store_url      = home_url();
    $request_url    = add_query_arg( 'order', $order->id, get_permalink( get_option("woocommerce_warranty_page_id") ) );

    $vars = array(
        '{order_id}', '{rma_code}', '{shipping_code}', '{product_id}', '{product_name}',
        '{warranty_status}',
        '{customer_email}', '{customer_name}', '{customer_shipping_code}', '{store_shipping_code}',
        '{warranty_request_url}', '{store_url}'
    );
    $reps = array(
        $request['order_id'], $request['code'], $request['tracking_code'], $request['product_id'], get_the_title($request['product_id']),
        $status, $order->billing_email, $order->billing_first_name .' '. $order->billing_last_name,
        $request['tracking_code'], $request['return_tracking_code'], $request_url, $store_url
    );

    $defaults = array(
        'fields'    => array(),
        'inputs'    => ''
    );
    $form = get_option( 'warranty_form', $defaults );

    $inputs = array();
    if (! empty($form['inputs']) ) {
        $inputs = json_decode($form['inputs']);
    }

    $custom_vars = array();
    foreach ( $inputs as $input_field ) {
        $key = $input_field->key;
        $var = $form['fields'][$key]['name'];
        $sanitized_key  = str_replace( '-', '_', sanitize_title( strtolower($var) ) );
        $value          = get_post_meta( $request_id, '_field_'. $key, true );

        if (! $value )
            $value = '';

        if ( is_array($value) )
            $value = implode( ', ', $value );

        if ( $input_field->type == 'file' && !empty($value) ) {
            $uploads    = wp_upload_dir();
            $value      = $uploads['baseurl'] . $value;
        }


        $vars[] = '{'. $sanitized_key .'}';
        $reps[] = $value;

    }

    return str_replace($vars, $reps, $input);
}

/**
 * Generate a unique RMA code
 *
 * @return string $code
 */
function warranty_generate_rma_code() {
    // RMA Code Format
    $last   = get_option( 'warranty_last_rma', 1 );
    $length = get_option( 'warranty_rma_length', 1 );
    $prefix = get_option( 'warranty_rma_prefix', '' );
    $suffix = get_option( 'warranty_rma_suffix', '' );

    $vars   = array( '{DD}', '{MM}', '{YYYY}' );
    $reps   = array( date('d'), date('m'), date('Y') );

    if (! empty($prefix) ) {
        $prefix = str_replace( $vars, $reps, $prefix );
    }

    if (! empty($suffix) ) {
        $suffix = str_replace( $vars, $reps, $suffix );
    }

    $code = (int)$last + 1;

    update_option( 'warranty_last_rma', $code );

    if ( $length > strlen($code) ) {
        $pad    = $length - strlen($code);
        $code   = str_repeat('0', $pad) .''. $code;
    }

    $code = $prefix . $code . $suffix;

    return $code;
}
