<?php
/*
Plugin Name: WooCommerce Warranty Requests
Plugin URI: https://www.woothemes.com/products/warranty-requests/
Description: Set warranties for your products (free and paid), and allow customers to purchase warranties when buying a product, and to initiate a return request right from their account. Manage RMA numbers, return status, email communications, and track return shipping easily with this extension.
Version: 1.1.7
Author: 75nineteen Media
Author URI: http://www.75nineteen.com

Copyright: © 2014 75nineteen Media.

*/

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
    require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '9b4c41102e6b61ea5f558e16f9b63e25', '228315' );

if ( is_woocommerce_active() ) {

    load_plugin_textdomain( 'wc_warranty', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

    class WC_Warranty {

        public static $base_path;
        public static $default_statuses     = array();
        public static $shipping_tracking    = false;
        public static $providers            = array(); // shipping providers
        public static $tips                 = array();

        function __construct() {
            self::$default_statuses = array( __('New', 'wc_warranty'), __('Processing', 'wc_warranty'), __('Completed', 'wc_warranty'), __('Rejected', 'wc_warranty') );
            self::$base_path = plugin_dir_path( __FILE__ );

            if ( in_array('woocommerce-shipment-tracking/shipment-tracking.php', get_option('active_plugins')) ) {
                self::$shipping_tracking    = true;
                self::$providers            = $GLOBALS['WC_Shipment_Tracking']->providers;
            }

            // tips
            self::$tips   = array(
                'name'      => __('The name of the field that gets displayed on the Warranty Requests Table (Admin Panel)', 'wc_warranty'),
                'label'     => __('The label of the field displayed to the user when requesting for an RMA (Frontend)', 'wc_warranty'),
                'default'   => __('The initial value of the field', 'wc_warranty'),
                'required'  => __('Check this to make this field required', 'wc_warranty'),
                'multiple'  => __('Check this to allow users to select one or more options', 'wc_warranty'),
                'options'   => __('One option per line', 'wc_warranty')
            );

            // functions
            require self::$base_path .'/functions.php';

            add_action( 'init', array( $this, 'init' ) );
            register_activation_hook( __FILE__, array($this, 'install') );

            add_action( 'init', array($this, 'create_warranty_request_page') );

            add_filter( 'body_class', array( $this, 'output_body_class' ) );

            add_action( 'admin_menu', array( $this, 'add_menu' ), 20 );

            add_action( 'wp_enqueue_scripts', array($this, 'frontend_styles') );
            add_action( 'admin_enqueue_scripts', array($this, 'settings_scripts') );

            add_action( 'admin_post_warranty_create', array($this, 'create_warranty') );

            // bulk edit
            add_action( 'admin_post_warranty_bulk_edit', array($this, 'bulk_edit') );

            // order status changed
            add_action( 'woocommerce_order_status_changed', array($this, 'order_status_changed'), 10, 3 );

            add_action( 'woocommerce_order_details_after_order_table', array( $this, 'show_request_button' ), 10, 1 );
            add_filter( 'woocommerce_my_account_my_orders_actions', array($this, 'my_orders_request_button'), 10, 2 );

            // Account Shortcode
            add_shortcode( 'warranty_request', array($this, 'do_shortcode') );

            // Generic Return Form Shortcode
            add_shortcode( 'warranty_return_form', array($this, 'return_form') );

            // catch frontend form submission
            add_action( 'template_redirect', array($this, 'template_redirect') );

            // Update request from the admin
            add_action( 'wp_ajax_warranty_update',  array($this, 'warranty_update') );
            add_action( 'admin_post_warranty_update', array($this, 'warranty_update') );
            add_action( 'admin_post_warranty_delete', array($this, 'warranty_delete') );

            // metaboxes
            add_action( 'woocommerce_product_write_panel_tabs', array($this, 'panel_data_tab') );
            add_action( 'woocommerce_product_write_panels', array($this, 'panel_add_custom_box') );
            add_action( 'save_post', array($this, 'save_postdata') );

            // variable products support
            add_action( 'woocommerce_product_after_variable_attributes', array($this, 'variables_panel'), 10, 3 );
            add_filter( 'woocommerce_available_variation', array($this, 'add_variation_data'), 10, 3 );

            // adding to cart
            add_action( 'woocommerce_before_add_to_cart_button', array($this, 'show_product_warranty') );
            add_filter( 'woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 2 );
            add_filter( 'woocommerce_add_cart_item', array($this, 'add_cart_item'), 10, 1 );

            add_filter( 'woocommerce_add_to_cart_validation', array($this, 'add_cart_validation'), 10, 2 );

            add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 10, 2 );
            add_filter( 'woocommerce_get_item_data', array( $this, 'get_item_data' ), 10, 2 );
            add_action( 'woocommerce_add_to_cart', array($this, 'add_warranty_index'), 10, 6 );

            add_action( 'woocommerce_order_status_completed', array($this, 'order_completed') );

            // Add meta to order
            add_action( 'woocommerce_order_item_meta', array( $this, 'order_item_meta' ), 10, 2 );

            // Add meta to order 2.0
            add_action( 'woocommerce_add_order_item_meta', array( $this, 'order_item_meta_2' ), 10, 2 );

            add_action( 'woocommerce_checkout_update_order_meta', array($this, 'add_order_meta'), 10, 2 );

            // settings
            add_filter( 'woocommerce_settings_tabs_array', array($this, 'settings_tabs') );
            add_action( 'woocommerce_settings_tabs_warranty', array($this, 'settings_page') );
            add_action( 'woocommerce_update_options_warranty', array($this, 'update_warranty_settings') );
            add_action( 'woocommerce_update_option', array($this, 'update_warranty_emails') );
            add_action( 'woocommerce_update_option', array($this, 'update_permissions') );
            add_action( 'woocommerce_update_option', array($this, 'update_multi_status') );
            add_action( 'woocommerce_update_option', array($this, 'update_form_builder') );
            add_action( 'woocommerce_admin_field_warranty_emails', array($this, 'warranty_emails_table') );
            add_action( 'woocommerce_admin_field_multi_status', array($this, 'warranty_multi_status_field') );
            add_action( 'woocommerce_admin_field_warranty_form_builder', array($this, 'form_builder') );
            add_action( 'woocommerce_admin_field_warranty_permissions', array($this, 'permissions_table') );

            // return stock
            add_action( 'admin_post_warranty_return_inventory', array($this, 'return_inventory') );

            // CSV Import
            add_filter( 'woocommerce_csv_product_post_columns', array($this, 'csv_import_fields') );
        }

        function output_body_class( $classes ) {
            if ( is_page( woocommerce_get_page_id( 'warranty' ) ) ) {
                $classes[] = 'woocommerce';
                $classes[] = 'woocommerce-page';
            }

            return $classes;
        }

        /**
         * Fired on plugin activation. Create the Warranty Request page if it doesn't exist
         */
        function install() {
            if (! get_option('wc_warranty_page_title') ) {
                add_option( 'wc_warranty_page_title', __('Request Warranty', 'wc_warranty') );
            }

            if (! get_option('wc_warranty_page_name') ) {
                add_option( 'wc_warranty_page_name', __('Request Warranty', 'wc_warranty') );
            }

            if (! get_option('woocommerce_warranty_page_id') ) {
                // Create post object
                $parent_id = woocommerce_get_page_id( 'myaccount' );

                if ( $parent_id ) {
                    $_p = array();
                    $_p['post_title'] = __('Request Warranty', 'wc_warranty');
                    $_p['post_content'] = "[warranty_request]";
                    $_p['post_status'] = 'publish';
                    $_p['post_type'] = 'page';
                    $_p['comment_status'] = 'closed';
                    $_p['ping_status'] = 'closed';
                    $_p['post_category'] = array(1);
                    $_p['post_parent'] = $parent_id;

                    // Insert the post into the database
                    $page_id = wp_insert_post( $_p );

                    update_option( 'woocommerce_warranty_page_id', $page_id );
                }

            }
        }

        function create_warranty_request_page() {
            $page_id    = woocommerce_get_page_id('warranty');
            $page       = get_post($page_id);

            if ($page_id == -1 || !$page) {
                // Create post object
                $parent_id = woocommerce_get_page_id( 'myaccount' );

                if ( $parent_id ) {
                    $_p = array();
                    $_p['post_title'] = __('Request Warranty', 'wc_warranty');
                    $_p['post_content'] = "[warranty_request]";
                    $_p['post_status'] = 'publish';
                    $_p['post_type'] = 'page';
                    $_p['comment_status'] = 'closed';
                    $_p['ping_status'] = 'closed';
                    $_p['post_category'] = array(1);
                    $_p['post_parent'] = $parent_id;

                    // Insert the post into the database
                    $page_id = wp_insert_post( $_p );

                    update_option( 'woocommerce_warranty_page_id', $page_id );
                }
            }
        }

        /**
         * Register warranty_request post type and shop_warranty_status taxonomy
         */
        function init() {
            global $wpdb;

            $admin_only_query_var = ( is_admin() ) ? true : false;

            register_taxonomy( 'shop_warranty_status',
                array('warranty_request'),
                array(
                    'hierarchical'          => false,
                    'update_count_callback' => '_update_post_term_count',
                    'labels' => array(
                            'name'              => __( 'Warranty Statuses', 'wc_warranty'),
                            'singular_name'     => __( 'Warranty Status', 'wc_warranty'),
                            'search_items'      => __( 'Search Warranty Statuses', 'wc_warranty'),
                            'all_items'         => __( 'All Warranty Statuses', 'wc_warranty'),
                            'parent_item'       => __( 'Parent Warranty Status', 'wc_warranty'),
                            'parent_item_colon' => __( 'Parent Warranty Status:', 'wc_warranty'),
                            'edit_item'         => __( 'Edit Warranty Status', 'wc_warranty'),
                            'update_item'       => __( 'Update Warranty Status', 'wc_warranty'),
                            'add_new_item'      => __( 'Add New Warranty Status', 'wc_warranty'),
                            'new_item_name'     => __( 'New Warranty Status Name', 'wc_warranty')
                     ),
                    'show_ui'               => false,
                    'show_in_nav_menus'     => false,
                    'query_var'             => $admin_only_query_var,
                    'rewrite'               => false,
                )
            );

            register_post_type( 'warranty_request', array(
                    'label'                 => __('Warranty Requests', 'wc_warranty'),
                    'labels'                => array(
                            'name'              => __('Warranty Requests', 'wc_warranty'),
                            'singular_name'     => __('Warranty Request', 'wc_warranty'),
                            'all_items'         => __('All Requests', 'wc_warranty'),
                            'menu_name'         => __( 'Warranty', 'wc_warranty' ),
                            'not_found'         => __('No requests found', 'wc_warranty')
                        ),
                    'public'                => true,
                    'exclude_from_search'   => true,
                    'publicly_queryable'    => false,
                    'show_ui'               => false,
                    'capability_type'       => 'post',
                    'hierarchical'          => false,
                    'show_in_nav_menus'     => false,
                    'rewrite'               => false,
                    'query_var'             => true,
                    'supports'              => array( 'title', 'comments', 'custom-fields' ),
                    'has_archive'           => false,
                    'menu_icon'             => plugins_url( 'assets/images/icon-menu.png', __FILE__ )
                )
            );

            /* Default data */
            $statuses = get_terms( 'shop_warranty_status', array('hide_empty' => false) );

            if ( empty($statuses) ) {
                $warranty_status = apply_filters( 'wc_warranty_statuses', array(
                    __('New', 'wc_warranty'),
                    __('Processing', 'wc_warranty'),
                    __('Completed', 'wc_warranty'),
                    __('Rejected', 'wc_warranty')
                ));

                $default_slugs = array();

                foreach ( $warranty_status as $status ) {
                    if ( ! get_term_by( 'name', $status, 'shop_warranty_status' ) ) {
                        wp_insert_term( $status, 'shop_warranty_status' );

                        $term = get_term_by( 'name', $status, 'shop_warranty_status' );
                        $default_slugs[] = $term->slug;
                    }
                }

                if (! empty($default_slugs) ) {
                    update_option( 'wc_warranty_default_slugs', $default_slugs );
                }
            }

            if (! get_option('wc_warranty_status_order', false) ) {
                $order  = apply_filters( 'wc_warranty_order_statuses', array(
                    __('New', 'wc_warranty'),
                    __('Processing', 'wc_warranty'),
                    __('Completed', 'wc_warranty'),
                    __('Rejected', 'wc_warranty')
                ));
                $option = array();
                foreach ( $order as $id => $status ) {
                    $term = get_term_by( 'name', $status, 'shop_warranty_status' );
                    $option[$id] = $term->slug;
                }

                update_option( 'wc_warranty_status_order', $option );
            }

            if (false == get_option( 'wc_warranty_form_migrated', false ) ) {
                $used_keys  = array();
                $form       = array(
                    'fields'    => array(),
                    'inputs'    => array()
                );

                /* Warranty Reasons */
                do {
                    $key = rand(100, 2147483647);
                } while ( in_array($key, $used_keys) );
                $used_keys[] = $key;

                $input = array(
                    'key'   => $key,
                    'type'  => 'select'
                );
                $field  = array(
                    'name'      => __('Reason', 'wc_warranty'),
                    'label'     => __('Select reason to request for warranty', 'wc_warranty'),
                    'default'   => 'Defective Item',
                    'options'   => get_option('warranty_reason', 'Defective Item')
                );

                $form['inputs'][] = $input;
                $form['fields'][$key] = $field;

                $wpdb->query(
                    "UPDATE {$wpdb->prefix}postmeta
                    SET meta_key = '_field_". $key ."'
                    WHERE meta_key = '_reason'"
                );

                /* Warranty Question */
                do {
                    $key = rand(100, 2147483647);
                } while ( in_array($key, $used_keys) );
                $used_keys[] = $key;

                $question = get_option( 'warranty_question', false );

                if ( $question ) {

                    $input = array(
                        'key'   => $key,
                        'type'  => 'textarea'
                    );
                    $field  = array(
                        'name'      => __('Description', 'wc_warranty'),
                        'label'     => get_option( 'warranty_question', 'Describe the problem' ),
                        'default'   => '',
                        'required'  => 'yes',
                        'rows'      => 5,
                        'cols'      => 50
                    );

                    $form['inputs'][] = $input;
                    $form['fields'][$key] = $field;
                }

                $wpdb->query(
                    "UPDATE {$wpdb->prefix}postmeta
                    SET meta_key = '_field_". $key ."'
                    WHERE meta_key = '_answer'"
                );

                /* Warranty File Upload */
                do {
                    $key = rand(100, 2147483647);
                } while ( in_array($key, $used_keys) );
                $used_keys[] = $key;

                $upload_file    = get_option( 'warranty_upload', 'no' );
                $required       = get_option( 'warranty_require_upload', 'no' );

                if ( $upload_file == 'yes' ) {
                    $input = array(
                        'key'   => $key,
                        'type'  => 'file'
                    );
                    $field  = array(
                        'name'      => __('Attachment', 'wc_warranty'),
                        'label'     => get_option('warranty_upload_title', 'Attach File'),
                        'default'   => '',
                        'required'  => $required
                    );

                    $form['inputs'][] = $input;
                    $form['fields'][$key] = $field;
                }

                $wpdb->query(
                    "UPDATE {$wpdb->prefix}postmeta
                    SET meta_key = '_field_". $key ."'
                    WHERE meta_key = '_attachment'"
                );

                $form['inputs'] = json_encode($form['inputs']);
                update_option('warranty_form', $form);

                update_option('wc_warranty_form_migrated', true);
            }

        }

        function add_menu() {
            add_submenu_page('woocommerce', __('Warranty', 'wc_warranty'), __('Warranty Requests', 'wc_warranty'), 'manage_woocommerce', 'warranty_requests',array( $this,"settings"));
        }

        function settings() {
            global $wpdb, $woocommerce;

            $tab = 'list';

            if ( isset($_GET['tab']) ) {
                $tab = $_GET['tab'];
            }

            include self::$base_path .'templates/header.php';

            if ( $tab == 'list' ) {
                include self::$base_path .'templates/list.php';
            } elseif ( $tab == 'new' ) {
                $orders         = array();
                $searched       = false;
                $form_view      = false;

                if ( isset($_GET['search_key']) && isset($_GET['search_term']) ) {
                    $searched   = true;

                    switch ( $_GET['search_key'] ) {

                        case 'email':
                            $sql = $wpdb->prepare("SELECT DISTINCT post_id AS id FROM {$wpdb->postmeta} pm, {$wpdb->posts} p WHERE pm.meta_key = '_billing_email' AND pm.meta_value LIKE %s AND pm.post_id = p.ID AND p.post_status = 'publish'", $_GET['search_term'].'%');
                            break;

                        case 'name':
                            $sql = $wpdb->prepare("SELECT DISTINCT pm1.post_id AS id FROM {$wpdb->postmeta} pm1, {$wpdb->postmeta} pm2, {$wpdb->posts} p WHERE ( (pm1.meta_key = '_billing_first_name' AND pm1.meta_value LIKE %s) OR (pm2.meta_key = '_billing_last_name' AND pm2.meta_value = %s) ) AND pm1.post_id = pm2.post_id AND pm1.post_id = p.ID AND pm2.post_id = p.ID AND p.post_status = 'publish'", $_GET['search_term'].'%', $_GET['search_term'].'%');
                            break;

                        default:
                            $sql = $wpdb->prepare("SELECT DISTINCT ID AS id FROM {$wpdb->posts} WHERE post_type = 'shop_order' AND ID LIKE %s AND post_status = 'publish'", $_GET['search_term'].'%');
                            break;

                    }

                    $orders = $wpdb->get_results( $sql );
                } elseif ( isset($_GET['product_id']) && isset($_GET['idx']) ) {
                    $form_view  = true;
                }

                include self::$base_path .'templates/new.php';
            } elseif ( $tab == 'manage' ) {
                include self::$base_path .'templates/manage.php';
            } elseif ( $tab == 'report' ) {
                $orders = $wpdb->get_results("SELECT DISTINCT ID AS id FROM {$wpdb->posts} WHERE post_type = 'shop_order' AND post_status = 'publish' ORDER BY post_date DESC");
                include self::$base_path .'templates/report.php';
            }

            include self::$base_path .'templates/footer.php';
        }

        function settings_scripts() {
            global $woocommerce;

            if ( isset($_GET['page']) && $_GET['page'] == 'warranty_requests' ) {
                if (! function_exists('wc_add_notice') ) {
                    woocommerce_admin_scripts();
                } else {
                    $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
                    wp_register_script( 'chosen', WC()->plugin_url() . '/assets/js/chosen/chosen.jquery' . $suffix . '.js', array('jquery'), WC()->version );
                    wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array('jquery'), WC()->version );
                }

                wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css' );

                wp_enqueue_script( 'jquery-tiptip' );
                wp_enqueue_script( 'chosen' );
                wp_enqueue_script( 'jquery-ui-sortable' );
                wp_enqueue_script( 'jquery-ui-core', null, array('jquery') );

                $js = '
                    jQuery(".warranty-delete").click(function(e) {
                        return confirm("'. __('Do you really want to delete this request?', 'wc_warranty') .'");
                    });
                ';

                if ( function_exists('wc_enqueue_js') ) {
                    wc_enqueue_js( $js );
                } else {
                    $woocommerce->add_inline_js( $js );
                }

            }

            // settings css
            if ( isset($_GET['page'], $_GET['tab']) && ($_GET['page'] == 'wc-settings' || $_GET['page'] == 'woocommerce_settings') && $_GET['tab'] == 'warranty' ) {
                wp_enqueue_style( 'warranty_admin_css', plugins_url( 'assets/admin.css', __FILE__) );

                wp_enqueue_script( 'jquery-ui' );
                wp_enqueue_script( 'jquery-ui-sortable' );
                wp_enqueue_script( 'warranty_form_builder', plugins_url( 'assets/form-builder.js', __FILE__ ) );

                $data = array(
                    'help_img_url' => plugins_url() .'/woocommerce/assets/images/help.png',
                    'tips' => self::$tips
                );

                wp_localize_script('warranty_form_builder', 'WFB', $data);
            }

            wp_enqueue_style( 'wc-form-builder', plugins_url('assets/form-builder.css', __FILE__) );

            $js = '
                if (jQuery("select.multi-chosen").length)
                    jQuery("select.multi-chosen").chosen();
            ';

            if ( function_exists('wc_enqueue_js') ) {
                wc_enqueue_js( $js );
            } else {
                $woocommerce->add_inline_js( $js );
            }

        }

        function frontend_styles() {
            wp_enqueue_style( 'wc-form-builder', plugins_url('assets/form-builder.css', __FILE__) );
        }

        /**
         * Display the 'Request Warranty' button on the order view page if
         * an order contains a product with a valid warranty
         * @param $order WC_Order object
         */
        function show_request_button( $order ) {
            global $wpdb;

            //$exclude = apply_filters( 'wc_warranty_exclude_statuses', array('failed', 'on-hold', 'pending', 'cancelled', 'refunded') );
            $include        = get_option( 'warranty_request_statuses', array() );

            if ( in_array($order->status, $include) && $this->order_has_warranty($order) ) {
                // If there is an existing warranty request, show a different text
                $requests = get_post_meta( $order->id, '_warranty_requests', true );

                if (! $requests ) $requests = array();

                if ( count($requests) > 0 ) {
                    $title = get_option('warranty_button_text', __('View Warranty Request', 'wc_warranty'));
                } else {
                    $title = get_option( 'warranty_button_text', __('Request Warranty', 'wc_warranty') );
                }

                $page_id = get_option("woocommerce_warranty_page_id");
                $permalink = add_query_arg( 'order', $order->id, get_permalink( $page_id ) );
                echo '<a class="warranty-button button" href="'.$permalink.'">'.$title.'</a>';
            }
        }

        /**
         * Display the 'Request Warranty' button on the My Account page
         * @param  array $actions
         * @param  WC_Order $order
         * @return array $actions
         */
        function my_orders_request_button( $actions, $order ) {
            global $wpdb;

            $include        = get_option( 'warranty_request_statuses', array() );

            if ( in_array($order->status, $include) && $this->order_has_warranty($order) ) {
                // If there is an existing warranty request, show a different text
                $requests = get_post_meta( $order->id, '_warranty_requests', true );

                if (! $requests ) $requests = array();

                if ( count($requests) > 0 ) {
                    $title = get_option('warranty_button_text', __('View Warranty Request', 'wc_warranty'));
                } else {
                    $title = get_option( 'warranty_button_text', __('Request Warranty', 'wc_warranty') );
                }

                $page_id = get_option("woocommerce_warranty_page_id");
                $permalink = add_query_arg( 'order', $order->id, get_permalink( $page_id ) );

                $actions['request_warranty'] = array('url' => $permalink, 'name' => $title);
            }

            return $actions;
        }

        /**
         * Returns the content for the shortcode [warranty_request]
         * @return string The HTML content
         */
        function do_shortcode() {
            global $current_user, $wpdb, $woocommerce;

            if ( isset($_REQUEST['order']) ) {
                $order_id = trim($_REQUEST['order']);
            } else {
                return __('No order selected.', 'wc_warranty');
            }

            if (! is_numeric($order_id) ) return __('No order selected.', 'wc_warranty');

            $product_id = false;

            if ( isset($_REQUEST['product_id']) ) {
                $product_id = intval($_REQUEST['product_id']);
            }

            ob_start();
            include self::$base_path .'templates/shortcode-content.php';
            return ob_get_clean();
        }

        /**
         * Generates and returns the form for generic return requests
         * @return string
         */
        function return_form() {
            global $current_user, $wpdb;

            ob_start();
            include self::$base_path .'/templates/shortcode-return-form.php';
            return ob_get_clean();
        }

        /**
         * Capture and process frontend form submissions
         * TODO: Process form builder data from POST
         */
        function template_redirect() {
            global $wpdb, $woocommerce;

            if ( isset($_REQUEST['req']) ) {
                $request = $_REQUEST['req'];

                if ( $request == 'new_warranty' ) {
                    $order_id   = isset($_GET['order']) ? intval($_GET['order']) : false;
                    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : false;
                    $idx        = isset($_GET['idx']) ? intval($_GET['idx']) : '';
                    $_POST      = array_map('stripslashes_deep', $_POST);


                    if ( $order_id && $product_id ) {
                        $warranty_data  = array();
                        $order          = new WC_Order( $order_id );
                        $include        = get_option( 'warranty_request_statuses', array() );

                        if ( in_array($order->status, $include) ) {
                            $request_id = warranty_process_request( $order_id, $product_id, $idx );
                        } else {
                            $request_id = new WP_Error( 'wc_warranty', __('Order does not have a valid warranty', 'wc_warranty') );
                        }

                        if ( is_wp_error( $request_id ) ) {
                            $result = $request_id;
                            $error  = $result->get_error_message( 'wc_warranty' );
                            $back   = get_permalink( woocommerce_get_page_id('warranty') );
                            $back   = add_query_arg( 'product_id', $product_id, add_query_arg( 'order', $order_id, $back ) );
                            $back   = add_query_arg( 'error', urlencode($error), $back );

                            if (! empty($idx) ) {
                                $back = add_query_arg( 'idx', $idx, $back );
                            }

                            wp_redirect( $back );
                            exit;
                        }

                        // save the custom forms
                        $result = WC_Warranty::process_warranty_form( $request_id );
                        if ( is_wp_error($result) ) {
                            $errors = $result->get_error_messages();
                            $back   = get_permalink( woocommerce_get_page_id('warranty') );
                            $back   = add_query_arg( 'product_id', $product_id, add_query_arg( 'order', $order_id, $back ) );
                            $back   = add_query_arg( 'errors', urlencode(json_encode($errors)), $back );

                            if (! empty($idx) ) {
                                $back = add_query_arg( 'idx', $idx, $back );
                            }

                            wp_redirect( $back );
                            exit;
                        }

                        // send the emails
                        warranty_send_emails( $request_id, 'new');

                        $back   = get_permalink( get_option('woocommerce_warranty_page_id') );
                        $back   = add_query_arg( 'order', $order_id, $back );
                        $back   = add_query_arg( 'updated', urlencode(__('Warranty request sent', 'wc_warranty')), $back );

                        wp_redirect( $back );
                        exit;

                    }
                } elseif ( $request == 'new_return' ) {
                    $_POST = array_map('stripslashes_deep', $_POST);

                    $return_id      = $_POST['return'];
                    $order_id       = $_POST['order_id'];
                    $product_name   = $_POST['product'];
                    $first_name     = $_POST['first_name'];
                    $last_name      = $_POST['last_name'];
                    $email          = $_POST['email'];
                    $issue          = $_POST['issue'];

                    $warranty = array(
                        'post_content'  => '',
                        'post_name'     => __('Return Request for Order #', 'wc_warranty') . $order_id,
                        'post_status'   => 'publish',
                        'post_author'   => 1,
                        'post_type'     => 'warranty_request'
                    );
                    $request_id = wp_insert_post( $warranty );

                    wp_set_post_terms( $request_id, 'new', 'shop_warranty_status' );

                    $metas = array(
                        'order_id'      => $order_id,
                        'product_id'    => 0,
                        'product_name'  => $product_name,
                        'reason'        => $issue,
                        'answer'        => '',
                        'attachment'    => '',
                        'code'          => warranty_generate_rma_code(),
                        'first_name'    => $first_name,
                        'last_name'     => $last_name,
                        'email'         => $email
                    );

                    foreach ( $metas as $key => $value ) {
                        add_post_meta( $request_id, '_'.$key, $value, true );
                    }

                    warranty_send_emails( $request_id, 'new');

                    if ( function_exists('wc_add_notice') ) {
                        wc_add_notice(__('Return request submitted successfully', 'wc_warranty'));
                    } else {
                        $woocommerce->add_message(__('Return request submitted successfully', 'wc_warranty'));
                    }

                    wp_redirect( get_permalink($return_id) );
                    exit;
                }
            }

            if ( isset($_POST['action']) ) {
                if ( $_POST['action'] == 'set_tracking_code' ) {
                    $request_id = $_POST['request_id'];
                    $code       = $_POST['tracking_code'];
                    $provider   = isset($_POST['tracking_provider']) ? $_POST['tracking_provider'] : '';

                    update_post_meta( $request_id, '_tracking_code', $code );

                    if (! empty($provider) ) {
                        update_post_meta( $request_id, '_tracking_provider', $provider );
                    }

                    $request = warranty_load($request_id);

                    $back   = get_permalink( get_option('woocommerce_warranty_page_id') );
                    $back   = add_query_arg( 'order', $request['order_id'], $back );
                    $back   = add_query_arg( 'updated', urlencode(__('Tracking codes updated', 'wc_warranty')), $back );

                    wp_redirect( $back );
                    exit;
                }
            }
        }

        /**
         * Updates an existing warranty request based on values from $_REQUEST
         */
        function warranty_update() {
            global $wpdb, $woocommerce;

            $post   = array_map( 'stripslashes_deep', $_REQUEST );
            $type   = $post['type'];
            $ajax   = isset($post['ajax']) ? $post['ajax'] : false;

            if ( $type == 'change_status' ) {
                $new_status = $post['status'];
                $request_id = $post['request_id'];
                $now        = current_time( 'mysql' );

                wp_set_post_terms( $request_id, $new_status, 'shop_warranty_status', false );

                $update = array(
                    'ID'            => $request_id,
                    'post_modified' => $now
                );
                wp_update_post( $update );

                do_action( 'wc_warranty_status_updated', $request_id, $new_status );

                warranty_send_emails( $request_id, $new_status );

                if ( $new_status == get_option('warranty_returned_status') ) {
                    // Item has already been returned
                    do_action( 'wc_warranty_item_returned', $request_id, $new_status );
                }

                if ( $ajax ) {
                    die( 'admin.php?page=warranty_requests&tab=list&updated='. urlencode(__('Request status updated', 'wc_warranty')) );
                } else {
                    wp_redirect( 'admin.php?page=warranty_requests&tab=list&updated='. urlencode(__('Request status updated', 'wc_warranty')) );
                    exit;
                }
            } elseif ( $type == 'generate_rma' ) {
                // using GET
                $request_id = $post['request_id'];
                $code       = warranty_generate_rma_code();
                $now        = current_time( 'mysql' );

                update_post_meta( $request_id, '_code', $code );

                wp_redirect( 'admin.php?page=warranty_requests&tab=list&updated='. urlencode(__('RMA Code generated successfully', 'wc_warranty')) );
                exit;
            } elseif ( $type == 'request_code' ) {
                $request_id = $post['request_id'];

                update_post_meta( $request_id, '_request_tracking_code', 'y' );

                warranty_send_emails( $request_id, 'Request Tracking');

                wp_redirect( 'admin.php?page=warranty_requests&tab=list&updated='. urlencode(__('Tracking code requested', 'wc_warranty')) );
                exit;
            } elseif ( $type == 'set_return_tracking' ) {
                $request_id = $post['request_id'];
                $provider   = isset($post['return_tracking_provider']) ? $post['return_tracking_provider'] : false;
                $code       = $post['return_tracking_code'];

                update_post_meta( $request_id, '_return_tracking_code', $code );

                if ( false !== $provider ) {
                    update_post_meta( $request_id, '_return_tracking_provider', $provider );
                }

                wp_redirect( 'admin.php?page=warranty_requests&tab=list&updated='. urlencode(__('Return tracking code updated', 'wc_warranty')) );
                exit;
            }
        }

        public function warranty_delete() {
            global $wpdb;

            check_admin_referer( 'warranty_delete' );

            $id = absint($_GET['id']);

            wp_delete_post( $id, true );

            wp_redirect( 'admin.php?page=warranty_requests&updated='. urlencode(__('Warranty request deleted', 'wc_warranty') ) );
            exit;
        }

        /**
         * Adds a 'Warranty' tab to a product's data tabs
         */
        function panel_data_tab() {
            echo ' <li class="warranty_tab tax_options hide_if_external hide_if_variable"><a href="#warranty_product_data">'. __('Warranty', 'woocommerce') .'</a></li>';
        }

        /**
         * Outputs the form for the Warranty data tab
         */
        function panel_add_custom_box() {
            global $post, $wpdb, $thepostid, $woocommerce;

            $warranty_type_value = get_post_meta($post->ID, '_warranty_type', true);

            if (trim($warranty_type_value) == '') {
                update_post_meta($post->ID, '_warranty_type', 'no_warranty');
                $warranty_type_value = 'no_warranty';
            }

            $warranty_duration_value = get_post_meta($post->ID, '_warranty_duration', true);

            if (trim($warranty_duration_value) == '') {
                update_post_meta($post->ID, '_warranty_duration', 0);
                $warranty_duration_value = 0;
            }

            $warranty_unit_value = get_post_meta($post->ID, '_warranty_unit', true);

            if (trim($warranty_unit_value)=='') {
                update_post_meta($post->ID, '_warranty_unit', 'day');
                $warranty_unit_value = 'day';
            }

            $currency = get_woocommerce_currency_symbol();
            $inline = '
            $("#product_warranty_type").change(function() {
                $(".show_if_included_warranty, .show_if_addon_warranty").hide();

                if ($(this).val() == "included_warranty") {
                    $(".show_if_included_warranty").show();
                } else if ($(this).val() == "addon_warranty") {
                    $(".show_if_addon_warranty").show();
                }
            }).change();

            $("#included_warranty_length").change(function() {
                if ($(this).val() == "limited") {
                    $(".limited_warranty_length_field").show();
                } else {
                    $(".limited_warranty_length_field").hide();
                }
            }).change();

            var tmpl = "<tr>\
                            <td valign=\"middle\">\
                                <span class=\"input\"><b>+</b> '. $currency .'</span>\
                                <input type=\"text\" name=\"addon_warranty_amount[]\" class=\"input-text sized\" size=\"4\" value=\"\" />\
                            </td>\
                            <td valign=\"middle\">\
                                <input type=\"text\" class=\"input-text sized\" size=\"3\" name=\"addon_warranty_length_value[]\" value=\"\" />\
                                <select name=\"addon_warranty_length_duration[]\">\
                                    <option value=\"days\">'. __('Days', 'wc_warranty') .'</option>\
                                    <option value=\"weeks\">'. __('Weeks', 'wc_warranty') .'</option>\
                                    <option value=\"months\">'. __('Months', 'wc_warranty') .'</option>\
                                    <option value=\"years\">'. __('Years', 'wc_warranty') .'</option>\
                                </select>\
                            </td>\
                            <td><a class=\"button warranty_addon_remove\" href=\"#\">&times;</a></td>\
                        </tr>";

            $(".btn-add-warranty").click(function(e) {
                e.preventDefault();

                $("#warranty_addons").append(tmpl);
            });

            $(".warranty_addon_remove").live("click", function(e) {
                e.preventDefault();

                $(this).parents("tr").remove();
            });
            ';

            if ( function_exists('wc_enqueue_js') ) {
                wc_enqueue_js( $inline );
            } else {
                $woocommerce->add_inline_js( $inline );
            }

            $warranty = get_post_meta($post->ID, '_warranty', true);

            if ( empty($warranty) ) {
                $warranty = array('type' => 'no_warranty');
            }

            $warranty_label = get_post_meta($post->ID, '_warranty_label', true);

            if ( empty($warranty_label) ) {
                $warranty_label = __('Warranty', 'wc_warranty');
            }
            ?>
            <style type="text/css">
            span.input {float: left; margin-top: 4px;}
            p.addon-row {margin-left: 25px;}
            </style>
            <div id="warranty_product_data" class="panel woocommerce_options_panel">

                <div class="options_group grouping">
                    <p class="form-field product_warranty_type_field">
                        <label for="product_warranty_type"><?php _e('Product Warranty', 'wc_warranty'); ?></label>

                        <select id="product_warranty_type" name="product_warranty_type" class="select">
                            <option value="no_warranty" <?php if ($warranty['type'] == 'no_warranty') echo 'selected'; ?>><?php _e('No Warranty', 'wc_warranty'); ?></option>
                            <option value="included_warranty" <?php if ($warranty['type'] == 'included_warranty') echo 'selected'; ?>><?php _e('Warranty Included', 'wc_warranty'); ?></option>
                            <option value="addon_warranty" <?php if ($warranty['type'] == 'addon_warranty') echo 'selected'; ?>><?php _e('Warranty as Add-On', 'wc_warranty'); ?></option>
                        </select>
                    </p>

                    <p class="form-field show_if_included_warranty show_if_addon_warranty">
                        <label for="warranty_label"><?php _e('Warranty Label', 'wc_warranty'); ?></label>

                        <input type="text" name="warranty_label" value="<?php echo esc_attr($warranty_label); ?>" class="input-text sized" />
                    </p>
                </div>

                <div class="options_group grouping show_if_included_warranty">
                    <p class="form-field included_warranty_length_field">
                        <label for="included_warranty_length"><?php _e('Warranty Length', 'wc_warranty'); ?></label>

                        <select id="included_warranty_length" name="included_warranty_length" class="select short">
                            <option value="lifetime" <?php if ($warranty['type'] == 'included_warranty' && $warranty['length'] == 'lifetime') echo 'selected'; ?>><?php _e('Lifetime Warranty', 'wc_warranty'); ?></option>
                            <option value="limited" <?php if ($warranty['type'] == 'included_warranty' && $warranty['length'] == 'limited') echo 'selected'; ?>><?php _e('Limited Warranty', 'wc_warranty'); ?></option>
                        </select>
                    </p>

                    <p class="form-field limited_warranty_length_field">
                        <label for="limited_warranty_length_value"><?php _e('Warranty Duration', 'wc_warranty'); ?></label>
                        <input type="text" class="input-text sized" size="3" name="limited_warranty_length_value" value="<?php if ($warranty['type'] == 'included_warranty') echo $warranty['value']; ?>" />
                        <select name="limited_warranty_length_duration">
                            <option value="days" <?php if ($warranty['type'] == 'included_warranty' && $warranty['duration'] == 'days') echo 'selected'; ?>><?php _e('Days', 'wc_warranty'); ?></option>
                            <option value="weeks" <?php if ($warranty['type'] == 'included_warranty' && $warranty['duration'] == 'weeks') echo 'selected'; ?>><?php _e('Weeks', 'wc_warranty'); ?></option>
                            <option value="months" <?php if ($warranty['type'] == 'included_warranty' && $warranty['duration'] == 'months') echo 'selected'; ?>><?php _e('Months', 'wc_warranty'); ?></option>
                            <option value="years" <?php if ($warranty['type'] == 'included_warranty' && $warranty['duration'] == 'years') echo 'selected'; ?>><?php _e('Years', 'wc_warranty'); ?></option>
                        </select>
                    </p>
                </div>

                <div class="options_group grouping show_if_addon_warranty">
                    <p class="form-field">
                        <label for="addon_no_warranty">
                            <?php _e( '"No Warranty" option', 'wc_warranty'); ?>
                        </label>
                        <input type="checkbox" name="addon_no_warranty" id="addon_no_warranty" value="yes" <?php if (isset($warranty['no_warranty_option']) && $warranty['no_warranty_option'] == 'yes') echo 'checked'; ?> class="checkbox" />
                    </p>

                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Cost', 'wc_warranty'); ?></th>
                                <th><?php _e('Duration', 'wc_warranty'); ?></th>
                                <th width="50">&nbsp;</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th colspan="3">
                                    <a href="#" class="button btn-add-warranty"><?php _e('Add Row', 'wc_warranty'); ?></a>
                                </th>
                            </tr>
                        </tfoot>
                        <tbody id="warranty_addons">
                            <?php
                            if ( isset($warranty['addons']) ) foreach ( $warranty['addons'] as $addon ):
                            ?>
                            <tr>
                                <td valign="middle">
                                    <span class="input"><b>+</b> <?php echo $currency; ?></span>
                                    <input type="text" name="addon_warranty_amount[]" class="input-text sized" size="4" value="<?php echo $addon['amount']; ?>" />
                                </td>
                                <td valign="middle">
                                    <input type="text" class="input-text sized" size="3" name="addon_warranty_length_value[]" value="<?php echo $addon['value']; ?>" />
                                    <select name="addon_warranty_length_duration[]">
                                        <option value="days" <?php if ($addon['duration'] == 'days') echo 'selected'; ?>><?php _e('Days', 'wc_warranty'); ?></option>
                                        <option value="weeks" <?php if ($addon['duration'] == 'weeks') echo 'selected'; ?>><?php _e('Weeks', 'wc_warranty'); ?></option>
                                        <option value="months" <?php if ($addon['duration'] == 'months') echo 'selected'; ?>><?php _e('Months', 'wc_warranty'); ?></option>
                                        <option value="years" <?php if ($addon['duration'] == 'years') echo 'selected'; ?>><?php _e('Years', 'wc_warranty'); ?></option>
                                    </select>
                                </td>
                                <td><a class="button warranty_addon_remove" href="#">&times;</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>
            </div>
            <?php
        }

        function variables_panel( $loop, $data, $variation ) {
            global $woocommerce;

            $warranty = (isset($data['_warranty'])) ? maybe_unserialize($data['_warranty'][0]) : '';

            if ( empty($warranty) ) {
                $warranty = array('type' => 'no_warranty');
            }

            $warranty_label = (isset($data['_warranty_label'])) ? $data['_warranty_label'][0] : '';

            if ( empty($warranty_label) ) {
                $warranty_label = __('Warranty', 'wc_warranty');
            }

            $currency = get_woocommerce_currency_symbol();
            $inline = '
            $("#variable_product_warranty_type_'. $loop .'").change(function() {
                $(".variable_show_if_included_warranty_'. $loop .', .variable_show_if_addon_warranty_'. $loop .'").hide();

                if ($(this).val() == "included_warranty") {
                    $(".variable_show_if_included_warranty_'. $loop .'").show();
                } else if ($(this).val() == "addon_warranty") {
                    $(".variable_show_if_addon_warranty_'. $loop .'").show();
                }
            }).change();

            $("#variable_included_warranty_length_'. $loop .'").change(function() {
                if ($(this).val() == "limited") {
                    $(".variable_limited_warranty_length_field_'. $loop .'").show();
                } else {
                    $(".variable_limited_warranty_length_field_'. $loop .'").hide();
                }
            }).change();

            var tmpl_'. $loop .' = "<tr>\
                            <td valign=\"middle\">\
                                <span class=\"input\"><b>+</b> '. $currency .'</span>\
                                <input type=\"text\" name=\"variable_addon_warranty_amount['. $loop .'][]\" value=\"\" style=\"width:50px;\" />\
                            </td>\
                            <td valign=\"middle\">\
                                <input type=\"text\" style=\"width:50px;\" name=\"variable_addon_warranty_length_value['. $loop .'][]\" value=\"\" />\
                                <select name=\"variable_addon_warranty_length_duration['. $loop .'][]\" style=\"width: auto !important;\">\
                                    <option value=\"days\">'. __('Days', 'wc_warranty') .'</option>\
                                    <option value=\"weeks\">'. __('Weeks', 'wc_warranty') .'</option>\
                                    <option value=\"months\">'. __('Months', 'wc_warranty') .'</option>\
                                    <option value=\"years\">'. __('Years', 'wc_warranty') .'</option>\
                                </select>\
                            </td>\
                            <td><a class=\"button warranty_addon_remove_variable_'. $loop .'\" data-loop=\"'. $loop .'\" href=\"#\">&times;</a></td>\
                        </tr>";

            $(".btn-add-warranty-variable").click(function(e) {
                e.preventDefault();
                $("#variable_warranty_addons_'. $loop .'").append(tmpl_'. $loop.');
            });

            $(".warranty_addon_remove_variable_'. $loop .'").live("click", function(e) {
                e.preventDefault();

                $(this).parents("tr").eq(0).remove();
            });
            ';

            if ( function_exists('wc_enqueue_js') ) {
                wc_enqueue_js( $inline );
            } else {
                $woocommerce->add_inline_js( $inline );
            }

            ?>
            <tr>
                <td>
                    <label for="variable_product_warranty_type_<?php echo $loop; ?>"><?php _e('Product Warranty', 'wc_warranty'); ?></label>

                    <select id="variable_product_warranty_type_<?php echo $loop; ?>" name="variable_product_warranty_type[<?php echo $loop; ?>]" class="select">
                        <option value="no_warranty" <?php if ($warranty['type'] == 'no_warranty') echo 'selected'; ?>><?php _e('No Warranty', 'wc_warranty'); ?></option>
                        <option value="included_warranty" <?php if ($warranty['type'] == 'included_warranty') echo 'selected'; ?>><?php _e('Warranty Included', 'wc_warranty'); ?></option>
                        <option value="addon_warranty" <?php if ($warranty['type'] == 'addon_warranty') echo 'selected'; ?>><?php _e('Warranty as Add-On', 'wc_warranty'); ?></option>
                    </select>
                </td>
                <td>
                    <label for="variable_warranty_label_<?php echo $loop; ?>"><?php _e('Warranty Label', 'wc_warranty'); ?></label>
                    <input type="text" id="variable_warranty_label_<?php echo $loop; ?>" name="variable_warranty_label[<?php echo $loop; ?>]" value="<?php echo esc_attr($warranty_label); ?>" class="input-text sized" />
                </td>
            </tr>

            <tr class="variable_show_if_included_warranty_<?php echo $loop; ?>">
                <td>
                    <label for="variable_included_warranty_length_<?php echo $loop; ?>"><?php _e('Warranty Length', 'wc_warranty'); ?></label>

                    <select id="variable_included_warranty_length_<?php echo $loop; ?>" name="variable_included_warranty_length[<?php echo $loop; ?>]" class="select short">
                        <option value="lifetime" <?php if ($warranty['type'] == 'included_warranty' && $warranty['length'] == 'lifetime') echo 'selected'; ?>><?php _e('Lifetime Warranty', 'wc_warranty'); ?></option>
                        <option value="limited" <?php if ($warranty['type'] == 'included_warranty' && $warranty['length'] == 'limited') echo 'selected'; ?>><?php _e('Limited Warranty', 'wc_warranty'); ?></option>
                    </select>
                </td>

                <td>
                    <div class="variable_limited_warranty_length_field_<?php echo $loop; ?>">
                        <label for="variable_limited_warranty_length_value_<?php echo $loop; ?>"><?php _e('Warranty Duration', 'wc_warranty'); ?></label>
                        <input type="text" class="input-text sized" size="3" style="width: 50px;" name="variable_limited_warranty_length_value[<?php echo $loop; ?>]" value="<?php if ($warranty['type'] == 'included_warranty') echo $warranty['value']; ?>" />
                        <select name="variable_limited_warranty_length_duration[<?php echo $loop; ?>]" style="width: auto !important;">
                            <option value="days" <?php if ($warranty['type'] == 'included_warranty' && $warranty['duration'] == 'days') echo 'selected'; ?>><?php _e('Days', 'wc_warranty'); ?></option>
                            <option value="weeks" <?php if ($warranty['type'] == 'included_warranty' && $warranty['duration'] == 'weeks') echo 'selected'; ?>><?php _e('Weeks', 'wc_warranty'); ?></option>
                            <option value="months" <?php if ($warranty['type'] == 'included_warranty' && $warranty['duration'] == 'months') echo 'selected'; ?>><?php _e('Months', 'wc_warranty'); ?></option>
                            <option value="years" <?php if ($warranty['type'] == 'included_warranty' && $warranty['duration'] == 'years') echo 'selected'; ?>><?php _e('Years', 'wc_warranty'); ?></option>
                        </select>
                    </div>
                </td>
            </tr>

            <tr class="variable_show_if_addon_warranty_<?php echo $loop; ?>">
                <td colspan="2">
                    <p>
                        <label for="variable_addon_no_warranty_<?php echo $loop; ?>">
                            <input type="checkbox" name="variable_addon_no_warranty[<?php echo $loop; ?>]" id="variable_addon_no_warranty_<?php echo $loop; ?>" value="yes" <?php if (isset($warranty['no_warranty_option']) && $warranty['no_warranty_option'] == 'yes') echo 'checked'; ?> class="checkbox" />
                            <?php _e( '"No Warranty" option', 'wc_warranty'); ?>
                        </label>
                    </p>

                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Cost', 'wc_warranty'); ?></th>
                                <th><?php _e('Duration', 'wc_warranty'); ?></th>
                                <th width="50">&nbsp;</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th colspan="3">
                                    <a href="#" class="button btn-add-warranty-variable" data-loop="<?php echo $loop; ?>"><?php _e('Add Row', 'wc_warranty'); ?></a>
                                </th>
                            </tr>
                        </tfoot>
                        <tbody id="variable_warranty_addons_<?php echo $loop; ?>">
                            <?php
                            if ( isset($warranty['addons']) ) foreach ( $warranty['addons'] as $addon ):
                            ?>
                            <tr>
                                <td valign="middle">
                                    <span class="input"><b>+</b> <?php echo $currency; ?></span>
                                    <input type="text" name="variable_addon_warranty_amount[<?php echo $loop; ?>][]" class="input-text sized" size="4" value="<?php echo $addon['amount']; ?>" style="width: 50px;" />
                                </td>
                                <td valign="middle">
                                    <input type="text" class="input-text sized" size="3" name="variable_addon_warranty_length_value[<?php echo $loop; ?>][]" value="<?php echo $addon['value']; ?>" />
                                    <select name="variable_addon_warranty_length_duration[<?php echo $loop; ?>][]">
                                        <option value="days" <?php if ($addon['duration'] == 'days') echo 'selected'; ?>><?php _e('Days', 'wc_warranty'); ?></option>
                                        <option value="weeks" <?php if ($addon['duration'] == 'weeks') echo 'selected'; ?>><?php _e('Weeks', 'wc_warranty'); ?></option>
                                        <option value="months" <?php if ($addon['duration'] == 'months') echo 'selected'; ?>><?php _e('Months', 'wc_warranty'); ?></option>
                                        <option value="years" <?php if ($addon['duration'] == 'years') echo 'selected'; ?>><?php _e('Years', 'wc_warranty'); ?></option>
                                    </select>
                                </td>
                                <td><a class="button warranty_addon_remove warranty_addon_remove_variable_<?php echo $loop; ?>" data-loop="<?php echo $loop; ?>" href="#">&times;</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </td>
            </tr>
            <?php
        }

        /**
         * Save warranty data from a product post
         */
        function save_postdata($post_ID) {

            if ( isset($_POST['product-type']) && $_POST['product-type'] == 'variable' ) {
                $types          = $_POST['variable_product_warranty_type'];
                $labels         = $_POST['variable_warranty_label'];
                $inc_lengths    = $_POST['variable_included_warranty_length'];
                $ltd_lengths    = $_POST['variable_limited_warranty_length_value'];
                $ltd_durations  = $_POST['variable_limited_warranty_length_duration'];
                $addon_amounts  = $_POST['variable_addon_warranty_amount'];
                $addon_lengths  = $_POST['variable_addon_warranty_length_value'];
                $addon_durations= $_POST['variable_addon_warranty_length_duration'];
                $no_warranties  = (isset($_POST['variable_addon_no_warranty'])) ? $_POST['variable_addon_no_warranty'] : array();

                $count  = count($types);
                $ids    = $_POST['variable_post_id'];

                for ( $x = 0; $x < $count; $x++ ) {
                    $product_warranty = array();

                    if ( $types[$x] == 'no_warranty' ) {
                        update_post_meta( $ids[$x], '_warranty', $product_warranty );
                    } elseif ( $types[$x] == 'included_warranty' ) {
                        $product_warranty = array(
                            'type'      => 'included_warranty',
                            'length'    => $inc_lengths[$x],
                            'value'     => $ltd_lengths[$x],
                            'duration'  => $ltd_durations[$x]
                        );
                        update_post_meta( $ids[$x], '_warranty', $product_warranty );
                    } elseif ( $types[$x] == 'addon_warranty' ) {
                        $no_warranty= (isset($no_warranties[$x])) ? $no_warranties[$x] : 'no';
                        $amounts    = $addon_amounts[$x];
                        $values     = $addon_lengths[$x];
                        $durations  = $addon_durations[$x];
                        $addons     = array();

                        for ($i = 0; $i < count($amounts); $i++) {
                            if (!isset($amounts[$i]) || !isset($values[$i]) || !isset($durations[$i])) continue;

                            $addons[] = array(
                                'amount'    => $amounts[$i],
                                'value'     => $values[$i],
                                'duration'  => $durations[$i]
                            );
                        }

                        $product_warranty = array(
                            'type'                  => 'addon_warranty',
                            'addons'                => $addons,
                            'no_warranty_option'    => $no_warranty
                        );
                        update_post_meta( $ids[$x], '_warranty', $product_warranty );
                    }

                    if ( $labels[$x] ) {
                        update_post_meta( $ids[$x], '_warranty_label', stripslashes($labels[$x]) );
                    }
                }
            } else {
                if ( isset($_POST['product_warranty_type']) ) {
                    $product_warranty = array();

                    if ( $_POST['product_warranty_type'] == 'no_warranty' ) {
                        update_post_meta( $post_ID, '_warranty', $product_warranty );
                    } elseif ( $_POST['product_warranty_type'] == 'included_warranty' ) {
                        $product_warranty = array(
                            'type'      => 'included_warranty',
                            'length'    => $_POST['included_warranty_length'],
                            'value'     => $_POST['limited_warranty_length_value'],
                            'duration'  => $_POST['limited_warranty_length_duration']
                        );
                        update_post_meta( $post_ID, '_warranty', $product_warranty );
                    } elseif ( $_POST['product_warranty_type'] == 'addon_warranty' ) {
                        $no_warranty= (isset($_POST['addon_no_warranty'])) ? $_POST['addon_no_warranty'] : 'no';
                        $amounts    = $_POST['addon_warranty_amount'];
                        $values     = $_POST['addon_warranty_length_value'];
                        $durations  = $_POST['addon_warranty_length_duration'];
                        $addons     = array();

                        for ($x = 0; $x < count($amounts); $x++) {
                            if (!isset($amounts[$x]) || !isset($values[$x]) || !isset($durations[$x])) continue;

                            $addons[] = array(
                                'amount'    => $amounts[$x],
                                'value'     => $values[$x],
                                'duration'  => $durations[$x]
                            );
                        }

                        $product_warranty = array(
                            'type'                  => 'addon_warranty',
                            'addons'                => $addons,
                            'no_warranty_option'    => $no_warranty
                        );
                        update_post_meta( $post_ID, '_warranty', $product_warranty );
                    }

                    if ( isset($_POST['warranty_label']) ) {
                        update_post_meta( $post_ID, '_warranty_label', stripslashes($_POST['warranty_label']) );
                    }
                }
            }

        }

        public function bulk_edit() {
            $post   = array_map('stripslashes_deep', $_POST);
            $ids    = array_keys($post['warranty_type']);
            $type   = $post['warranty_type'];
            $label  = $post['warranty_label'];

            foreach ( $ids as $id ) {
                 $warranty = array();

                if ( $type[$id] == 'no_warranty' ) {
                    update_post_meta( $id, '_warranty', $warranty );
                } elseif ( $type[$id] == 'included_warranty' ) {
                    $warranty = array(
                        'type'      => 'included_warranty',
                        'length'    => $post['included_warranty_length'][$id],
                        'value'     => $post['limited_warranty_length_value'][$id],
                        'duration'  => $post['limited_warranty_length_duration'][$id]
                    );
                    update_post_meta( $id, '_warranty', $warranty );
                } elseif ( $type[$id] == 'addon_warranty' ) {
                    $no_warranty= (isset($post['addon_no_warranty'][$id])) ? $post['addon_no_warranty'][$id] : 'no';
                    $amounts    = $post['addon_warranty_amount'][$id];
                    $values     = $post['addon_warranty_length_value'][$id];
                    $durations  = $post['addon_warranty_length_duration'][$id];
                    $addons     = array();

                    for ($x = 0; $x < count($amounts); $x++) {
                        if (!isset($amounts[$x]) || !isset($values[$x]) || !isset($durations[$x])) continue;

                        $addons[] = array(
                            'amount'    => $amounts[$x],
                            'value'     => $values[$x],
                            'duration'  => $durations[$x]
                        );
                    }

                    $warranty = array(
                        'type'                  => 'addon_warranty',
                        'addons'                => $addons,
                        'no_warranty_option'    => $no_warranty
                    );
                    update_post_meta( $id, '_warranty', $warranty );
                }

                if ( isset($post['warranty_label'][$id]) ) {
                    update_post_meta( $id, '_warranty_label', $post['warranty_label'][$id] );
                }
            }

            wp_redirect("admin.php?page=warranty_requests&tab=manage&updated=1");
            exit;
        }

        function add_variation_data( $data, $product, $variation ) {
            //var_dump($variation);

            $data['_warranty']          = get_post_meta( $variation->variation_id, '_warranty', true );
            $data['_warranty_label']    = get_post_meta( $variation->variation_id, '_warranty_label', true );

            return $data;
        }

        /**
         * Show a product's warranty information
         */
        function show_product_warranty() {
            global $post, $product, $woocommerce;

            if ( $product->product_type == 'external' ) return;

            $product_id     = $product->id;
            $warranty       = get_post_meta( $product_id, '_warranty', true );
            $warranty_label = get_post_meta( $product_id, '_warranty_label', true );

            if (empty($warranty)) {
                $warranty = array('type' => 'no_warranty');
            }

            if ( empty($warranty_label) ) {
                $warranty_label = __('Warranty', 'wc_warranty');
            }

            if ( $warranty['type'] == 'included_warranty' ) {
                if ( $warranty['length'] == 'limited' ) {
                    $value      = $warranty['value'];
                    $duration   = $warranty['duration'];

                    if ( $value == 1 ) {
                        $duration = rtrim($duration, 's');
                    }

                    echo '<p class="warranty_info"><b>'. $warranty_label .':</b> '. $value .' '. $duration .'</p>';
                } else {
                    echo '<p class="warranty_info"><b>'. $warranty_label .':</b> '. __('Lifetime Warranty', 'wc_warranty') .'</p>';
                }
            } elseif ( $warranty['type'] == 'addon_warranty' ) {
                $addons = $warranty['addons'];

                if ( is_array($addons) && !empty($addons) ) {
                    echo '<p class="warranty_info"><b>'. $warranty_label .'</b> <select name="warranty">';

                    if ( isset($warranty['no_warranty_option']) && $warranty['no_warranty_option'] == 'yes' ) {
                        echo '<option value="-1">'. __('No warranty', 'wc_warranty') .'</option>';
                    }

                    foreach ( $addons as $x => $addon ) {
                        $amount     = $addon['amount'];
                        $value      = $addon['value'];
                        $duration   = $addon['duration'];

                        if ( $value == 0 && $amount == 0 ) {
                            // no warranty option
                            echo '<option value="-1">'. __('No warranty', 'wc_warranty') .'</option>';
                        } else {
                            if ( $value == 1 ) {
                                $duration = rtrim($duration, 's');
                            }

                            if ( $amount == 0 ) {
                                $amount = __('Free', 'wc_warranty');
                            } else {
                                $amount = woocommerce_price( $amount );
                            }
                            echo '<option value="'. $x .'">'. $value .' '. $duration . ' &mdash; '. $amount .'</option>';
                        }
                    }

                    echo '</select></p>';
                }
            } else {
                echo '<p class="warranty_info"></p>';
            }

            if ( $product->product_type == 'variable' ) {
                $js = '
                wc_currency = "'. get_woocommerce_currency_symbol() .'";
                $("input[name=variation_id]").change(function() {
                    var variations      = $(".variations_form").data("product_variations");
                    var variation_id    = $("input[name=variation_id]").val();

                    $("p.warranty_info").html("");

                    for ( var x = 0; x < variations.length; x++ ) {

                        if ( variations[x].variation_id == variation_id ) {
                            var variation = variations[x];

                            if ( variation._warranty ) {
                                if ( variation._warranty.type == "included_warranty" ) {
                                    if ( variation._warranty.length == "limited" ) {
                                        var value      = variation._warranty.value;
                                        var duration   = variation._warranty.duration;

                                        if ( value == 1 ) {
                                            duration = duration.replace(/s+$/,"");
                                        }

                                        $(".warranty_info").html("<b>"+ variation._warranty_label +":</b> "+ value +" "+ duration);
                                    } else {
                                        $(".warranty_info").html("<b>"+ variation._warranty_label +":</b> Lifetime Warranty");
                                    }
                                } else if ( variation._warranty.type == "addon_warranty" ) {
                                    var addons = variation._warranty.addons;

                                    if ( addons.length ) {
                                        var src = "<b>"+ variation._warranty_label +"</b> <select name=\"warranty\">";

                                        if ( variation._warranty.no_warranty_option && variation._warranty.no_warranty_option == "yes" ) {
                                            src += "<option value=\"-1\">No warranty</option>";
                                        }

                                        for ( var i = 0; i < addons.length; i++ ) {
                                            var amount  = addons[i].amount;
                                            var value   = addons[i].value;
                                            var duration= addons[i].duration;

                                            if ( value == 0 && amount == 0 ) {
                                                src += "<option value=\"-1\">No warranty</option>";
                                            } else {
                                                if ( value == 1 ) {
                                                    duration = duration.replace(/s+$/,"");
                                                }

                                                if ( amount == 0 ) {
                                                    amount = "Free";
                                                } else {
                                                    amount = wc_currency +""+ amount;
                                                }
                                                src += "<option value=\""+ i +"\">"+ value +" "+ duration +" &mdash; "+ amount +"</option>";
                                            }
                                        }

                                        src += "</select>";
                                        $(".warranty_info").html(src);
                                    }
                                }
                            }
                        }

                    }
                }).change();
                ';
                if ( function_exists('wc_enqueue_js') ) {
                    wc_enqueue_js( $js );
                } else {
                    $woocommerce->add_inline_script( $js );
                }
            }

        }

        /**
         * Record the date and time that an order has been marked as completed
         *
         * @param int $order_id
         */
        function order_completed( $order_id ) {
            update_post_meta( $order_id, '_order_date_completed', current_time('timestamp') );
        }

        /**
         * Adds a warranty_index to a cart item. Used in tracking the selected warranty options
         *
         * @see WC_Warranty::add_cart_item()
         * @param array $item_data
         * @param int $product_id
         * @return array $item_data
         */
        function add_cart_item_data( $item_data, $product_id ) {
            global $woocommerce;

            if ( isset($_POST['warranty']) && $_POST['warranty'] !== '' ) {
                $item_data['warranty_index'] = $_POST['warranty'];
            }

            return $item_data;
        }

        /**
         * Add custom data to a cart item based on the selected warranty type
         *
         * @see WC_Warranty::add_cart_item_data()
         * @param array $item_data
         * @return array $item_data
         */
        function add_cart_item( $item_data ) {
            global $woocommerce;

            $_product       = $item_data['data'];
            $warranty_index = false;

            if ( isset($item_data['warranty_index']) ) {
                $warranty_index = $item_data['warranty_index'];
            }

            $product_id = (isset($_product->variation_id)) ? $_product->variation_id : $_product->id;

            $warranty = get_post_meta( $product_id, '_warranty', true );

            if ( $warranty ) {
                if ( $warranty['type'] == 'addon_warranty' && $warranty_index !== false ) {
                    $addons                         = $warranty['addons'];
                    $item_data['warranty_index']    = $warranty_index;
                    $add_cost                       = 0;

                    if ( isset($addons[$warranty_index]) && !empty($addons[$warranty_index]) ) {
                        $addon = $addons[$warranty_index];
                        if ( $addon['amount'] > 0 ) {
                            $add_cost += $addon['amount'];

                            $item_data['data']->adjust_price( $add_cost );
                        }
                    }
                }
            }

            return $item_data;
        }

        /**
         * Make sure an add-to-cart request is valid
         *
         * @param bool $valid
         * @param int $product_id
         * @return bool $valid
         */
        function add_cart_validation( $valid = '', $product_id = '' ) {
            global $woocommerce;

            $warranty       = get_post_meta( $product_id, '_warranty', true );
            $warranty_label = get_post_meta( $product_id, '_warranty_label', true );

            if (empty($warranty)) {
                $warranty = array('type' => 'no_warranty');
            }

            if ( empty($warranty_label) ) {
                $warranty_label = __('Warranty', 'wc_warranty');
            }

            if ( $warranty['type'] == 'addon_warranty' && !isset($_REQUEST['warranty']) ) {
                $woocommerce->add_error( sprintf(__('Please select a %s', 'wc_warranty'), $warranty_label) );
                return false;
            }

            return $valid;
        }

        /**
         * Returns warranty data about a cart item
         *
         * @param array $other_data
         * @param array $cart_item
         * @return array $other_data
         */
        function get_item_data( $other_data, $cart_item ) {
            $_product   = $cart_item['data'];
            $product_id = (isset($_product->variation_id)) ? $_product->variation_id : $_product->id;
            $warranty   = get_post_meta( $product_id, '_warranty', true );

            if ( $warranty ) {
                if ( $warranty['type'] == 'addon_warranty' && isset($cart_item['warranty_index']) ) {
                    $addons         = $warranty['addons'];
                    $warranty_index = $cart_item['warranty_index'];

                    if ( isset($addons[$warranty_index]) && !empty($addons[$warranty_index]) ) {
                        $addon  = $addons[$warranty_index];
                        $name   = 'Warranty';
                        $value  = $this->get_warranty_string( $addon['value'], $addon['duration'] );

                        if ( $addon['amount'] > 0 ) {
                            $value .= ' (' . woocommerce_price( $addon['amount'] ) . ')';
                        }

                        $other_data[] = array(
                            'name'      => $name,
                            'value'     => $value,
                            'display'   => ''
                        );
                    }
                } elseif ( $warranty['type'] == 'included_warranty' ) {
                    if ( $warranty['length'] == 'lifetime' ) {
                        $other_data[] = array(
                            'name'      => 'Warranty',
                            'value'     => __('Lifetime Warranty', 'wc_warranty'),
                            'display'   => ''
                        );
                    } elseif ( $warranty['length'] == 'limited' ) {
                        $string = $this->get_warranty_string( $warranty['value'], $warranty['duration'] );
                        $other_data[] = array(
                            'name'      => 'Warranty',
                            'value'     => $string,
                            'display'   => ''
                        );
                    }
                }
            }

            return $other_data;
        }

        /**
         * Get warranty index and add it to the cart item
         *
         * @param array $cart_item
         * @param array $values
         * @return array $cart_item
         */
        function get_cart_item_from_session( $cart_item, $values ) {

            if ( isset( $values['warranty_index'] ) ) {
                $cart_item['warranty_index'] = $values['warranty_index'];
                $cart_item = $this->add_cart_item( $cart_item );
            }

            return $cart_item;
        }

        /**
         * Adds the warranty to the item as item meta
         *
         * @param object $item_meta
         * @param array $cart_item
         */
        function order_item_meta( $item_meta, $cart_item ) {
            $_product       = $cart_item['data'];
            $warranty       = get_post_meta( $_product->id, '_warranty', true );
            $warranty_index = null;

            if ( $warranty ) {
                if ( $warranty['type'] == 'addon_warranty' && isset($cart_item['warranty_index']) ) {
                    $addons         = $warranty['addons'];
                    $warranty_index = $cart_item['warranty_index'];

                    if ( isset($addons[$warranty_index]) && !empty($addons[$warranty_index]) ) {
                        $addon  = $addons[$warranty_index];
                        $name   = 'Warranty';
                        $value  = $this->get_warranty_string( $addon['value'], $addon['duration'] );

                        if ( $addon['amount'] > 0 ) {
                            $value .= ' (' . strip_tags(woocommerce_price( $addon['amount'] )). ')';
                        }

                        $item_meta->add( $name, $value );
                    }

                } elseif ( $warranty['type'] == 'included_warranty' ) {
                    if ( $warranty['length'] == 'lifetime' ) {
                        $item_meta->add( __('Warranty', 'wc_warranty'), __('Lifetime Warranty', 'wc_warranty') );
                    } elseif ( $warranty['length'] == 'limited' ) {
                        $string = $this->get_warranty_string( $warranty['value'], $warranty['duration'] );

                        $item_meta->add( __('Warranty', 'wc_warranty'), $string );
                    }
                }

                $item_meta->add( '_item_warranty', $warranty );

                if ( $warranty_index ) {
                    $item_meta->add( '_item_warranty_selected', $warranty_index );
                }
            }
        }

        /**
         * order_item_meta_2 function.
         *
         * @access public
         * @param mixed $item_id
         * @param mixed $values
         * @return void
         */
        function order_item_meta_2( $item_id, $values ) {
            if ( function_exists('woocommerce_add_order_item_meta') ) {
                $_product   = $values['data'];
                $_prod_id   = ($_product->variation_id) ? $_product->variation_id : $_product->id;
                $warranty   = get_post_meta( $_prod_id, '_warranty', true );

                if ( $warranty ) {

                    if ( $warranty['type'] == 'addon_warranty' ) {
                        $addons = $warranty['addons'];

                        $warranty_index = isset($values['warranty_index']) ? $values['warranty_index'] : false;

                        if ( $warranty_index !== false && isset($addons[$warranty_index]) && !empty($addons[$warranty_index]) ) {
                            $addon  = $addons[$warranty_index];
                            $name   = 'Warranty';
                            $value  = $this->get_warranty_string( $addon['value'], $addon['duration'] );

                            if ( $addon['amount'] > 0 ) {
                                $value .= ' (' . strip_tags(woocommerce_price( $addon['amount'] )) . ')';
                            }

                            woocommerce_add_order_item_meta( $item_id, $name, $value );

                            woocommerce_add_order_item_meta( $item_id, '_item_warranty_selected', $warranty_index );
                        }
                    } elseif ( $warranty['type'] == 'included_warranty' ) {
                        if ( $warranty['length'] == 'lifetime' ) {
                            $name   = __('Warranty', 'wc_warranty');
                            $value  = __('Lifetime Warranty', 'wc_warranty');
                        } elseif ( $warranty['length'] == 'limited' ) {
                            $name   = __('Warranty', 'wc_warranty');
                            $value  = $this->get_warranty_string( $warranty['value'], $warranty['duration'] );
                        }
                        woocommerce_add_order_item_meta( $item_id, $name, $value );
                    }

                    woocommerce_add_order_item_meta( $item_id, '_item_warranty', $warranty );
                }

            }
        }

        /**
         * Add item meta for WC < 2.0
         *
         * @see WC_Warranty::add_order_meta_2
         * @param int $order_id
         * @param array $posted
         */
        function add_order_meta( $order_id, $posted ) {
            global $woocommerce;

            $item_addons = array();
            foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {

            }

            if (! empty($item_addons) ) {
                update_post_meta( $order_id, '_warranties', $item_addons );
            }
        }

        /**
         * Add warranty index to the cart items from POST
         *
         * @param string $cart_key
         * @param int $product_Id
         * @param int $quantity
         * @param int $variation_id
         * @param object $variation
         * @param array $cart_item_data
         */
        function add_warranty_index( $cart_key, $product_id, $quantity, $variation_id = null, $variation = null, $cart_item_data = null ) {
            global $woocommerce;

            if ( isset($_POST['warranty']) && $_POST['warranty'] !== '' ) {
                $woocommerce->cart->cart_contents[$cart_key]['warranty_index'] = $_POST['warranty'];
            }
        }

        /**
         * Helper method to properly format a warranty string (e.g. 5 months)
         *
         * @param int $duration
         * @param string $unit
         * @return string Formatted warranty string
         */
        function get_warranty_string( $duration, $unit = 'days' ) {
            if ( $duration == 1 ) {
                $unit = rtrim($unit, 's');
            }

            return $duration .' '. $unit;
        }

        /**
         * Check if an order contain items that have valid warranties
         *
         * @param object $order WC_Order object
         * @return bool
         */
        function order_has_warranty( $order ) {
            global $woocommerce;

            $items          = $order->get_items();
            $has_warranty   = false;
            $now            = current_time('timestamp');
            $warranty       = false;
            $addon_index    = null;

            foreach ( $items as $item ) {
                $metas = (isset($item['item_meta'])) ? $item['item_meta'] : array();

                if ( version_compare($woocommerce->version, '2.0', '>=') ) {
                    foreach ( $metas as $key => $value ) {
                        if ( $key == '_item_warranty' ) {
                            $warranty = maybe_unserialize( $value[0] );
                        } elseif ( $key == '_item_warranty_selected' ) {
                            $addon_index = $value[0];
                        }
                    }
                } else {
                    foreach ( $metas as $meta ) {
                        if ( $meta['meta_name'] == '_item_warranty' ) {
                            $warranty = maybe_unserialize( $meta['meta_value'] );
                        } elseif ( $meta['meta_name'] == '_item_warranty_selected' ) {
                            $addon_index = $meta['meta_value'];
                        }
                    }
                }

                if ( $warranty !== false ) {
                    $has_warranty = true;
                    break;
                }
            }

            if ( $has_warranty ) {
                if ( $warranty['type'] == 'addon_warranty' ) {
                    $valid_until    = false;
                    $addon          = (isset($warranty['addons'][$addon_index])) ? $warranty['addons'][$addon_index] : null;

                    if (! $addon ) {
                        $has_warranty = false;
                    }

                    // order's date of completion must be within the warranty period
                    $completed      = get_post_meta( $order->id, '_completed_date', true);

                    if (! empty($completed) ) {
                        $valid_until = strtotime( $completed .' +'. $addon['value'] .' '. $addon['duration'] );
                    }

                    if ( $valid_until && current_time('timestamp') > $valid_until ) {
                        $has_warranty = false;
                    }
                } elseif ( $warranty['type'] == 'included_warranty' ) {
                    if ( $warranty['length'] == 'lifetime' ) {
                        $has_warranty = true;
                    } else {
                        // order's date of completion must be within the warranty period
                        $valid_until    = false;
                        $completed      = get_post_meta( $order->id, '_completed_date', true);

                        if (! empty($completed) ) {
                            $valid_until = strtotime( $completed .' +'. $warranty['value'] .' '. $warranty['duration'] );
                        }


                        if ( $valid_until && current_time('timestamp') > $valid_until ) {
                            $has_warranty = false;
                        }
                    }
                } else {
                    $has_warranty = false;
                }
            }

            if (! $has_warranty ) {
                $query_args     = array(
                    'post_type'         => 'warranty_request',
                    'orderby'           => 'date',
                    'order'             => 'DESC'
                );

                $query_args['meta_query'][] = array(
                    'key'       => '_order_id',
                    'value'     => $order->id,
                    'compare'   => '='
                );

                $wp_query = new WP_Query();
                $wp_query->query($query_args);

                $total_items = $wp_query->found_posts;
                wp_reset_postdata();

                if ( $total_items > 0 ) $has_warranty = true;
            }

            return $has_warranty;
        }

        public function create_warranty() {
            global $wpdb;

            $order_id   = $_POST['order_id'];
            $product_id = $_POST['product_id'];
            $idx        = $_POST['index'];

            $request_id = warranty_process_request( $order_id, $product_id, $idx );

            if ( is_wp_error( $request_id ) ) {
                $result = $request_id;
                $error  = $result->get_error_message( 'wc_warranty' );
                $back   = 'admin.php?page=warranty_requests&tab=new&order_id='. $order_id .'&product_id='. $product_id .'&idx='. $idx;
                $back   = add_query_arg( 'error', urlencode($error), $back );

                wp_redirect( $back );
                exit;
            }

            // save the custom forms
            $result = WC_Warranty::process_warranty_form( $request_id );

            if ( is_wp_error($result) ) {
                $errors = $result->get_error_messages();
                $back   = 'admin.php?page=warranty_requests&tab=new&order_id='. $order_id .'&product_id='. $product_id .'&idx='. $idx;
                $back   = add_query_arg( 'errors', json_encode($errors), $back );

                wp_redirect( $back );
                exit;
            }

            // send the emails
            warranty_send_emails( $request_id, 'new');

            $back   = 'admin.php?page=warranty_requests';
            $back   = add_query_arg( 'updated', urlencode(__('Warranty request created', 'wc_warranty')), $back );

            wp_redirect( $back );
            exit;
        }

        public function order_status_changed( $order_id, $old_status, $new_status ) {
            global $woocommerce;

            $start_status   = get_option( 'warranty_start_status', 'completed' );
            $order          = new WC_Order( $order_id );

            if ( $new_status != $start_status ) return;

            $items          = $order->get_items();
            $has_warranty   = false;
            $now            = current_time('mysql');

            foreach ( $items as $item ) {
                $warranty       = false;
                $addon_index    = false;
                $metas          = (isset($item['item_meta'])) ? $item['item_meta'] : array();

                if ( version_compare($woocommerce->version, '2.0', '>=') ) {
                    foreach ( $metas as $key => $value ) {
                        if ( $key == '_item_warranty' ) {
                            $warranty = maybe_unserialize( $value[0] );
                        } elseif ( $key == '_item_warranty_selected' ) {
                            $addon_index = $value[0];
                        }
                    }
                } else {
                    foreach ( $metas as $meta ) {
                        if ( $meta['meta_name'] == '_item_warranty' ) {
                            $warranty = maybe_unserialize( $meta['meta_value'] );
                        } elseif ( $meta['meta_name'] == '_item_warranty_selected' ) {
                            $addon_index = $meta['meta_value'];
                        }
                    }
                }

                if ( $warranty ) {

                    // update order's date of completion
                    update_post_meta( $order->id, '_completed_date', $now );
                    break; // only need to update once per order

                }
            }

        }

        public function settings_tabs( $tabs ) {
            $tabs['warranty'] = 'Warranty';
            return $tabs;
        }

        public function settings_page() {
            woocommerce_admin_fields( $this->get_settings_fields() );
        }

        public function update_warranty_settings() {
            woocommerce_update_options( $this->get_settings_fields() );
        }

        public function update_warranty_emails( $value ) {
            if ( $value['type'] == 'warranty_emails' ) {
                $_POST      = array_map('stripslashes_deep', $_POST);
                $statuses   = (isset($_POST['status'])) ? $_POST['status'] : array();
                $recipients = (isset($_POST['send_to'])) ? $_POST['send_to'] : array();
                $subjects   = (isset($_POST['subject'])) ? $_POST['subject'] : array();
                $messages   = (isset($_POST['message'])) ? $_POST['message'] : array();
                $emails     = array();

                if (! empty($statuses) ) {
                    foreach ( $statuses as $idx => $status ) {
                        if ( isset($subjects[$idx]) && !empty($subjects[$idx]) && isset($messages[$idx]) && !empty($messages[$idx]) ) {
                            $emails[$status][] = array(
                                'recipient' => $recipients[$idx],
                                'subject'   => $subjects[$idx],
                                'message'   => $messages[$idx]
                            );
                        }
                    }
                }

                update_option( 'warranty_emails', $emails );
            }
        }

        public function update_permissions( $value ) {
            if ( $value['type'] == 'warranty_permissions' ) {
                $statuses   = warranty_get_statuses();
                $users      = isset($_POST['permission']) ? $_POST['permission'] : array();
                $permissions= array();

                foreach ($statuses as $status) {
                    $slug = $status->slug;
                    if (isset($users[$slug])) {
                        foreach ($users[$slug] as $user_id) {
                            $permissions[$slug][] = $user_id;
                        }
                    } else {
                        $permissions[$slug] = array();
                    }
                }

                update_option('warranty_permissions', $permissions);
            }
        }

        public function update_multi_status( $value ) {
            if ( $value['type'] == 'multi_status' ) {
                $_POST      = array_map('stripslashes_deep', $_POST);
                $statuses   = $_POST[$value['id']];

                update_option( $value['id'], $statuses );
            }
        }

        public function update_form_builder( $value ) {
            if ( $value['type'] == 'warranty_form_builder' ) {
                $_POST  = array_map('stripslashes_deep', $_POST);
                $fields = $_POST['fb_field'];
                $inputs = $_POST['form_fields'];

                update_option( 'warranty_form', array('fields' => $fields, 'inputs' => $inputs) );
            }
        }

        public function get_settings_fields() {
            $warranty_page_id       = woocommerce_get_page_id('warranty');
            $order_statuses         = get_terms( 'shop_order_status', array('hide_empty' => false) );
            $order_status_options   = array();
            $warranty_statuses      = warranty_get_statuses();
            $warranty_status_options= array();

            $saved_ram  = get_option( 'warranty_saved_rma', 0 );
            $last_rma   = get_option( 'warranty_last_rma', 0 );

            foreach ( $order_statuses as $order_status ) {
                $order_status_options[ $order_status->name ] = $order_status->name;
            }

            foreach ( $warranty_statuses as $warranty_status ) {
                $warranty_status_options[ $warranty_status->slug ] = $warranty_status->name;
            }

            return apply_filters( 'woocommerce_warranty_settings', array(

                array(
                    'name'     => __( 'Warranty Settings', 'wc_warranty' ),
                    'type'     => 'title',
                    'desc'     => '',
                    'id'       => 'warranty_settings_title'
                ),

                array(
                    'name'      => __( 'Warranty Request Page', 'wc_warranty' ),
                    'type'      => 'single_select_page',
                    'id'        => 'woocommerce_warranty_page_id',
                    'desc'      => __( 'Must contain the <code>[warranty_request]</code> shortcode', 'wc_warranty' ),
                    'tip'       => '',
                    'desc_tip'  => true
                ),

                array(
                    'name'     => __( 'Order Status', 'wc_warranty' ),
                    'desc'     => __( 'The order status that starts the warranty duration', 'wc_warranty' ),
                    'tip'      => '',
                    'id'       => 'warranty_start_status',
                    'css'      => 'min-width:150px;',
                    'default'  => 'processing',
                    'type'     => 'select',
                    'options'  => $order_status_options,
                    'desc_tip' => true,
                ),

                array(
                    'name'      => __( 'Order Status to allow Warranty Request', 'wc_warranty' ),
                    'desc'      => __( 'What statuses do you want to enable the warranty request button to your users?', 'wc_warranty' ),
                    'tip'       => '',
                    'id'        => 'warranty_request_statuses',
                    'css'       => 'width:400px;',
                    'default'   => array('processing', 'completed'),
                    'type'      => 'multi_status',
                    'options'   => $order_status_options,
                    'desc_tip'  => true
                ),

                array(
                    'name'      => __('Returned Status', 'wc_warranty'),
                    'desc'      => __('The warranty status that marks an item as "returned"', 'wc_warranty'),
                    'tip'       => '',
                    'id'        => 'warranty_returned_status',
                    'css'       => 'min-width: 150px;',
                    'default'   => 'Processing',
                    'type'      => 'select',
                    'options'   => $warranty_status_options,
                    'desc_tip'  => true
                ),

                /*array(
                    'name'      => __('Restocking Fee', 'wc_warranty'),
                    'desc'      => __('Ask the customer to pay a restocking fee for returning a merchandise (e.g. 10 or 5%)', 'wc_warranty'),
                    'tip'       => __('Fixed amount or a percentage of the product\'s amount', 'wc_warranty'),
                    'id'        => 'warranty_restocking_fee',
                    'default'   => '0',
                    'type'      => 'text',
                    'css'       => 'width: 50px;',
                    'desc_tip'  => false
                ),*/

                array(
                    'name'     => __( 'Warranty Button Text', 'wc_warranty' ),
                    'desc'     => __( 'Default: Request Warranty', 'wc_warranty' ),
                    'tip'      => '',
                    'id'       => 'warranty_button_text',
                    'css'      => 'min-width:150px;',
                    'default'  => __( 'Request Warranty', 'wc_warranty' ),
                    'type'     => 'text',
                    'desc_tip' => true,
                ),

                array( 'type' => 'sectionend', 'id' => 'warranty_settings_title' ),

                array(
                    'name'     => __( 'Warranty Form Builder', 'wc_warranty' ),
                    'type'     => 'title',
                    'desc'     => '',
                    'id'       => 'warranty_form_title'
                ),

                array(
                    'name'      => '',
                    'id'        => 'warranty_form_builder',
                    'type'      => 'warranty_form_builder'
                ),

                array( 'type' => 'sectionend', 'id' => 'warranty_form_title' ),

                array(
                    'name'     => __( 'RMA Code Format', 'wc_warranty' ),
                    'type'     => 'title',
                    'desc'     => '',
                    'id'       => 'warranty_rma_title'
                ),

                array(
                    'name'     => __( 'RMA Code Start', 'wc_warranty' ),
                    'desc'     => __('The starting number for the incrementing portion of the code', 'wc_warranty'),
                    'tip'      => '',
                    'id'       => 'warranty_rma_start',
                    'css'      => 'min-width:150px;',
                    'default'  => $last_rma,
                    'type'     => 'text',
                    'desc_tip' => true,
                ),

                array(
                    'name'     => __( 'RMA Code Length', 'wc_warranty' ),
                    'desc'     => __('The desired minimum length of the incrementing portion of the code', 'wc_warranty'),
                    'tip'      => '',
                    'id'       => 'warranty_rma_length',
                    'css'      => 'min-width:150px;',
                    'default'  => 3,
                    'type'     => 'text',
                    'desc_tip' => true,
                ),

                array(
                    'name'     => __( 'RMA Code Prefix', 'wc_warranty' ),
                    'desc'     => __('You may use {DD}, {MM} and {YYYY} for the current day, month and year respectively', 'wc_warranty'),
                    'tip'      => '',
                    'id'       => 'warranty_rma_prefix',
                    'css'      => 'min-width:150px;',
                    'default'  => '',
                    'type'     => 'text',
                    'desc_tip' => true,
                ),

                array(
                    'name'     => __( 'RMA Code Suffix', 'wc_warranty' ),
                    'desc'     => __('You may use {DD}, {MM} and {YYYY} for the current day, month and year respectively', 'wc_warranty'),
                    'tip'      => '',
                    'id'       => 'warranty_rma_suffix',
                    'css'      => 'min-width:150px;',
                    'default'  => '',
                    'type'     => 'text',
                    'desc_tip' => true,
                ),

                array( 'type' => 'sectionend', 'id' => 'warranty_rma_title' ),

                array(
                    'name'     => __( 'Emails', 'wc_warranty' ),
                    'type'     => 'title',
                    'desc'     => '',
                    'id'       => 'warranty_emails_title'
                ),

                array(
                    'name'     => __( 'Emails', 'wc_warranty' ),
                    'desc'     => '',
                    'tip'      => '',
                    'id'       => 'warranty_emails',
                    'default'  => '',
                    'type'     => 'warranty_emails',
                    'desc_tip' => true,
                ),

                array( 'type' => 'sectionend', 'id' => 'warranty_emails_title' ),

                array(
                    'name'      => __('Permissions', 'wc_warranty'),
                    'type'      => 'title',
                    'desc'      => '',
                    'id'        => 'warranty_permissions_title'
                ),

                array(
                    'name'      => __('Permissions', 'wc_warranty'),
                    'desc'      => '',
                    'tip'       => '',
                    'id'        => 'warranty_permissions',
                    'default'   => '',
                    'type'      => 'warranty_permissions',
                    'desc_tip'  => true
                ),

                array( 'type' => 'sectionend', 'id' => 'warranty_permissions_title' )

            ) );
        }

        public function warranty_multi_status_field( $value ) {
            global $woocommerce;

            if ( ! isset( $value['id'] ) ) $value['id'] = '';
            if ( ! isset( $value['title'] ) ) $value['title'] = isset( $value['name'] ) ? $value['name'] : '';
            if ( ! isset( $value['class'] ) ) $value['class'] = '';
            if ( ! isset( $value['css'] ) ) $value['css'] = '';
            if ( ! isset( $value['default'] ) ) $value['default'] = '';
            if ( ! isset( $value['desc'] ) ) $value['desc'] = '';
            if ( ! isset( $value['desc_tip'] ) ) $value['desc_tip'] = false;

            // Custom attribute handling
            $custom_attributes = array();

            if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) )
                foreach ( $value['custom_attributes'] as $attribute => $attribute_value )
                    $custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';

            // Description handling
            if ( $value['desc_tip'] === true ) {
                $description = '';
                $tip = $value['desc'];
            } elseif ( ! empty( $value['desc_tip'] ) ) {
                $description = $value['desc'];
                $tip = $value['desc_tip'];
            } elseif ( ! empty( $value['desc'] ) ) {
                $description = $value['desc'];
                $tip = '';
            } else {
                $description = $tip = '';
            }

            if ( $description && in_array( $value['type'], array( 'textarea', 'radio' ) ) ) {
                $description = '<p style="margin-top:0">' . wp_kses_post( $description ) . '</p>';
            } elseif ( $description ) {
                $description = '<span class="description">' . wp_kses_post( $description ) . '</span>';
            }

            if ( $tip && in_array( $value['type'], array( 'checkbox' ) ) ) {

                $tip = '<p class="description">' . $tip . '</p>';

            } elseif ( $tip ) {

                $tip = '<img class="help_tip" data-tip="' . esc_attr( $tip ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" height="16" width="16" />';

            }
            $option_value   = woocommerce_settings_get_option( $value['id'], $value['default'] );
            ?><tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
                        <?php echo $tip; ?>
                    </th>
                    <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
                        <select
                            name="<?php echo esc_attr( $value['id'] ); ?>[]"
                            id="<?php echo esc_attr( $value['id'] ); ?>"
                            style="<?php echo esc_attr( $value['css'] ); ?>"
                            class="multi-chosen <?php echo esc_attr( $value['class'] ); ?>"
                            multiple
                            <?php echo implode( ' ', $custom_attributes ); ?>
                            >
                            <?php
                                foreach ( $value['options'] as $key => $val ) {
                                    ?>
                                    <option value="<?php echo esc_attr( $key ); ?>" <?php

                                        if ( is_array( $option_value ) )
                                            selected( in_array( $key, $option_value ), true );
                                        else
                                            selected( $option_value, $key );

                                    ?>><?php echo $val ?></option>
                                    <?php
                                }
                            ?>
                       </select> <?php echo $description; ?>
                    </td>
                </tr><?php
        }

        public function warranty_emails_table( $value ) {
            $all_statuses   = warranty_get_statuses();
            $default_slugs  = get_option( 'wc_warranty_default_slugs' );

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
            foreach ( $inputs as $input ) {
                $key = $input->key;
                $custom_vars[] = $form['fields'][$key]['name'];
            }
            ?>
            <p class="description">
                <?php _e('Available variables:', 'wc_warranty'); ?>
                <code>{order_id}</code>, <code>{rma_code}</code>, <code>{shipping_code}</code>,
                <code>{product_id}</code>, <code>{product_name}</code>, <code>{warranty_status}</code>,
                <?php
                foreach ($custom_vars as $custom_var) {
                    $custom_var = str_replace( '-', '_', sanitize_title( strtolower($custom_var) ) );
                    echo '<code>{'. $custom_var .'}</code>, ';
                }
                ?>
                <code>{customer_name}</code>, <code>{customer_email}</code>, <code>{customer_shipping_code}</code>,
                <code>{store_shipping_code}</code>, <code>{warranty_request_url}</code>, <code>{store_url}</code>
            </p>

            <?php
            $emails = get_option( 'warranty_emails', array() );
            ?>
            <table class="wp-list-table widefat fixed posts generic-table">
                <thead>
                    <tr>
                        <th scope="col" id="status" class="manage-column column-status"><?php _e('Status', 'wc_warranty'); ?></th>
                        <th scope="col" id="send_to" class="manage-column column-send_to" style=""><?php _e('Send To', 'wc_warranty'); ?></th>
                        <th scope="col" id="subject" class="manage-column column-subject" width="27%"><?php _e('Subject', 'wc_warranty'); ?></th>
                        <th scope="col" id="message" class="manage-column column-message" width="35%"><?php _e('Message', 'wc_warranty'); ?></th>
                        <th scope="col" id="delete" class="manage-column column-delete" width="30"></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <td colspan="4">
                            <a class="button add-email" href="#">+ Add Email</a>
                        </td>
                    </tr>
                </tfoot>
                <tbody id="emails_tbody">
                    <?php
                    if (! empty($emails) ):
                        $idx = 0;

                        foreach ( $emails as $email_status => $status_email ):
                            foreach ( $status_email as $email ):
                    ?>
                    <tr id="email_<?php echo $idx; ?>">
                        <td>
                            <select name="status[<?php echo $idx; ?>]">
                                <?php foreach ( $all_statuses as $status ): ?>
                                <option value="<?php echo $status->slug; ?>" <?php echo ($status->slug == $email_status) ? 'selected' : ''; ?>><?php echo $status->name; ?></option>
                                <?php endforeach; ?>
                                <option value="Request Tracking" <?php echo ('Request Tracking' == $email_status) ? 'selected' : ''; ?>>Request Tracking</option>
                            </select>
                        </td>
                        <td>
                            <select name="send_to[<?php echo $idx; ?>]">
                                <option value="customer" <?php echo ($email['recipient'] == 'customer') ? 'selected' : ''; ?>>Customer</option>
                                <option value="admin" <?php echo ($email['recipient'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                <option value="both" <?php echo ($email['recipient'] == 'both') ? 'selected' : ''; ?>>Customer &amp; Admin</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="subject[<?php echo $idx; ?>]" value="<?php echo esc_attr($email['subject']); ?>" class="regular-text" />
                        </td>
                        <td>
                            <textarea name="message[<?php echo $idx; ?>]" rows="3" style="width: 99%;"><?php echo esc_attr($email['message']); ?></textarea>
                        </td>
                        <td><a class="button delete-row" href="#">&times;</a></td>
                    </tr>
                    <?php       $idx++;
                            endforeach;
                        endforeach;
                    else:
                    ?>
                    <tr id="email_0">
                        <td>
                            <select name="status[0]">
                                <?php foreach ( $all_statuses as $status ): ?>
                                <option value="<?php echo $status->slug; ?>"><?php echo $status->name; ?></option>
                                <?php endforeach; ?>
                                <option value="Request Tracking">Request Tracking</option>
                            </select>
                        </td>
                        <td>
                            <select name="send_to[0]">
                                <option value="customer">Customer</option>
                                <option value="admin">Admin</option>
                                <option value="both">Customer &amp; Admin</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="subject[0]" value="" class="regular-text" />
                        </td>
                        <td>
                            <textarea name="message[0]" rows="3" style="width: 99%;"></textarea>
                        </td>
                        <td></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <script type="text/javascript">
            <?php
            $js_statuses = array();
            foreach ( $all_statuses as $status ) {
                if ( !isset($status->slug) || empty($status->slug) ) $status->slug = $status->name;
                $js_statuses[] = array('slug' => $status->slug, 'name' => $status->name);
            }
            ?>
            var statuses = <?php echo json_encode($js_statuses); ?>;
            jQuery(document).ready(function($) {
                $(".add-email").click(function(e) {
                    e.preventDefault();

                    var idx     = 1;

                    while ( $("#email_"+ idx).length > 0 ) {
                        idx++;
                    }

                    var html    = '<tr id="email_'+ idx +'"><td><select name="status['+ idx + ']">';

                    for (var x = 0; x < statuses.length; x++) {
                        html += '<option value="'+ statuses[x].slug +'">'+ statuses[x].name +'</option>';
                    }
                    html        += '<option value="Request Tracking">Request Tracking</option>';
                    html        += '</select></td>';
                    html        += '<td><select name="send_to['+ idx +']"><option value="customer">Customer</option><option value="admin">Admin</option><option value="both">Customer &amp; Admin</option></select></td>';
                    html        += '<td><input type="text" name="subject['+ idx +']" value="" class="regular-text" /></td>';
                    html        += '<td><textarea name="message['+ idx +']" rows="3" style="width: 99%;"></textarea></td>';
                    html        += '<td><a class="button delete-row" href="#">&times;</a></td></tr>';

                    $("#emails_tbody").append(html);
                });

                $(".delete-row").live("click", function(e) {
                    e.preventDefault();

                    $(this).parents("tr").remove();
                });
            });
            </script>
            <?php
        }

        public function permissions_table($value) {
            $all_statuses   = warranty_get_statuses();
            $managers       = get_users(array('role' => 'shop_manager'));
            $permissions    = get_option('warranty_permissions', array());
            ?>
            <table class="wp-list-table widefat fixed posts permissions-table">
                <thead>
                    <tr>
                        <th scope="col" id="status" class="manage-column column-status" width="200"><?php _e('Status', 'wc_warranty'); ?></th>
                        <th scope="col" id="users" class="manage-column column-users" style=""><?php _e('Users with Access', 'wc_warranty'); ?></th>
                    </tr>
                </thead>
                <tbody id="permissions_tbody">
                <?php
                foreach ($all_statuses as $status):
                    $slug = $status->slug;
                ?>
                    <tr>
                        <td><?php echo $status->name; ?></td>
                        <td>
                            <select name="permission[<?php echo $slug; ?>][]" class="chzn-select" multiple data-placeholder="All Managers and Administrators" style="width: 500px;">
                                <?php
                                foreach ($managers as $manager):
                                    $selected = (isset($permissions[$slug]) && in_array($manager->ID, $permissions[$slug])) ? true : false;
                                ?>
                                <option value="<?php echo $manager->ID; ?>" <?php selected(true, $selected, true); ?>><?php echo $manager->display_name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <style type="text/css">
            table.permissions-table.widefat th, table.permissions-table.widefat td {overflow: visible;}
            </style>
            <script type="text/javascript">
                jQuery("select.chzn-select").chosen();
            </script>
            <?php
        }

        public function form_builder( $value ) {
            global $woocommerce;

            $types = array(
                'paragraph' => array(
                                    'label'     => __('Paragraph', 'wc_warranty'),
                                    'options'   => 'text'
                                ),
                'text'      => array(
                                    'label'     => __('Text Field', 'wc_warranty'),
                                    'options'   => 'name|label|style|default|required'
                                ),
                'textarea'  => array(
                                    'label'     => __('Multi-line Text Field', 'wc_warranty'),
                                    'options'   => 'name|label|style|default|rowscols|required'
                                ),
                'select'    => array(
                                    'label'     => __('Drop Down', 'wc_warranty'),
                                    'options'   => 'name|label|style|default|options|multiple'
                                ),
                'file'      => array(
                                    'label'     => __('File Upload Field', 'wc_warranty'),
                                    'options'   => 'name|label|required'
                                )
            );

            $defaults = array(
                'fields'    => array(),
                'inputs'    => ''
            );
            $form   = get_option( 'warranty_form', $defaults );

            $inputs = array();
            if (! empty($form['inputs']) ) {
                $inputs = json_decode($form['inputs']);
            }
            ?>
            <tr valign="top">
                <td colspan="2">
                    <div class="warranty_form">
                        <h4><?php _e('Warranty Form', 'wc_warranty'); ?></h4>
                        <p class="description"><?php _e('Drag the fields below to reorder', 'wc_warranty'); ?></p>
                        <ul id="warranty_form">
                        <?php
                        foreach($inputs as $input):
                            $src    = '';
                            $key    = $input->key;
                            $type   = $input->type;

                            if ( array_key_exists($key, $form['fields']) ) {
                                $field = $form['fields'][$key];

                                $src = '<div class="wfb-field wfb-field-'. $type .'" data-key="'. $key .'" id="wfb-field-'. $key .'">
                                            <div class="wfb-field-title">
                                                <h3>'. $types[$type]['label'] .'</h3>

                                                <div class="wfb-field-controls">
                                                    <a class="toggle-field wfb-toggle" data-key="'. $key .'" href="#">&#9652;</a>
                                                    <a class="remove-field wfb-remove" href="#">&times;</a>
                                                </div>
                                            </div>

                                            <div class="wfb-content" id="wfb-content-'. $key .'">
                                                <div class="wfb-field-content">
                                                    <table class="form-table">
                                ';

                                $options = explode('|', $types[$type]['options']);

                                foreach ( $options as $option ) {
                                    $src .= '<tr>';

                                    $value = (isset($field[$option])) ? $field[$option] : '';

                                    if ($option == "name") {
                                        $src .= '<th>Name <img class="help_tip" data-tip="'. self::$tips[$option] .'" src="'. plugins_url() .'/woocommerce/assets/images/help.png" height="16" width="16" /></th><td><input type="text" name="fb_field['. $key .'][name]" value="'. $value .'" /></td>';
                                    } elseif ($option == "label") {
                                        $src .= '<th>Label <img class="help_tip" data-tip="'. self::$tips[$option] .'" src="'. plugins_url() .'/woocommerce/assets/images/help.png" height="16" width="16" /></th><td><input type="text" name="fb_field['. $key .'][label]" value="'. $value .'" /></td>';
                                    } elseif ($option == "text") {
                                        $src .= '<th>Text</th><td><textarea name="fb_field['. $key .'][text]" rows="5" cols="35">'. $value .'</textarea></td>';
                                    } elseif ($option == "default") {
                                        $src .= '<th>Default Value <img class="help_tip" data-tip="'. self::$tips[$option] .'" src="'. plugins_url() .'/woocommerce/assets/images/help.png" height="16" width="16" /></th><td><input type="text" name="fb_field['. $key .'][default]" value="'. $value .'" /></td>';
                                    } elseif ($option == "rowscols") {
                                        $rows = (isset($field['rows'])) ? $field['rows'] : '';
                                        $cols = (isset($field['cols'])) ? $field['cols'] : '';
                                        $src .= '<th>Size</th><td><input type="text" size="2" name="fb_field['. $key .'][rows]" value="'. $rows .'" /><span class="description">Rows</span> <input type="text" size="2" name="fb_field['. $key .'][cols]" value="'. $cols .'" /><span class="description">Columns</span>';
                                    } elseif ($option == "options") {
                                        $src .= '<th>Options <img class="help_tip" data-tip="'. self::$tips[$option] .'" src="'. plugins_url() .'/woocommerce/assets/images/help.png" height="16" width="16" /></th><td><textarea name="fb_field['. $key .'][options]" rows="3" cols="35">'. $value .'</textarea></td>';
                                    } elseif ($option == "multiple") {
                                        $checked = ($value == 'yes') ? 'checked' : '';
                                        $src .= '<th>Allow Multiple <img class="help_tip" data-tip="'. self::$tips[$option] .'" src="'. plugins_url() .'/woocommerce/assets/images/help.png" height="16" width="16" /></th><td><input type="checkbox" name="fb_field['. $key .'][multiple]" value="yes" '. $checked .' /></td>';
                                    } elseif ($option == "required") {
                                        $checked = ($value == 'yes') ? 'checked' : '';
                                        $src .= '<th>Required <img class="help_tip" data-tip="'. self::$tips[$option] .'" src="'. plugins_url() .'/woocommerce/assets/images/help.png" height="16" width="16" /></th><td><input type="checkbox" name="fb_field['. $key .'][required]" value="yes" '. $checked .' /></td>';
                                    }

                                    $src .= '</tr>';
                                }

                                $src .= '       </table>
                                            </div>
                                        </div>';
                                echo '<li class="wfb-field" data-key="'. $key .'" data-type="'. $type .'">'. $src .'</li>';

                            }
                        ?>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="warranty_fields">
                        <h4><?php _e('Available Fields', 'wc_warranty'); ?></h4>

                        <ul id="warranty_form_fields">
                            <?php foreach ( $types as $key => $type ): ?>
                            <li><a class="control button" href="#" data-type="<?php echo $key; ?>" data-options="<?php echo $type['options']; ?>"><?php echo $type['label']; ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <input type="hidden" name="form_fields" id="form_fields" value="<?php echo esc_attr($form['inputs']); ?>" />
                </td>
            </tr>
            <?php
        }

        public static function render_warranty_form() {
            $defaults = array(
                'fields'    => array(),
                'inputs'    => ''
            );
            $form   = get_option( 'warranty_form', $defaults );
            $inputs = array();

            if (! empty($form['inputs']) ) {
                $inputs = json_decode($form['inputs']);
            }

            foreach ( $inputs as $input ) {
                $key    = $input->key;
                $type   = $input->type;

                self::render_warranty_form_field($type, $key, $form['fields'][$key]);
            }
        }

        public static function render_warranty_form_field($type, $key, $field) {
            echo '<div class="wfb-field-div wfb-field-div-'. $type .'" id="wfb-field-'. $key .'-div">';

            switch ( $type ) {
                case 'paragraph':
                    echo '<p class="wfb-field-para" id="wfb-field-'. $key .'">'. $field['text'] .'</p>';
                    break;

                case 'text':
                    echo '<label for="wfb-field-label" id="wfb-field-label-'. $key .'">'. $field['label'] .'</label>';
                    echo '<input type="text" name="wfb-field['. $key .']" id="wfb-field-'. $key .'" value="'. esc_attr($field['default']) .'" />';
                    break;

                case 'textarea':
                    echo '<label for="wfb-field-label" id="wfb-field-label-'. $key .'">'. $field['label'] .'</label>';
                    echo '<textarea name="wfb-field['. $key .']" id="wfb-field-'. $key .'" rows="'. $field['rows'] .'" cols="'. $field['cols'] .'">'. $field['default'] .'</textarea>';
                    break;

                case 'select':
                    $name       = (isset($field['multiple']) && $field['multiple'] == 'yes') ? 'wfb-field['. $key .'][]' : 'wfb-field['. $key .']';
                    $multiple   = (isset($field['multiple']) && $field['multiple'] == 'yes') ? 'multiple' : '';
                    $options    = preg_split("/(\r\n|\n|\r)/", $field['options']);
                    echo '<label for="wfb-field-label" id="wfb-field-label-'. $key .'">'. $field['label'] .'</label>';
                    echo '<select name="'. $name .'" id="wfb-field-'. $key .'" '. $multiple .'>';

                    foreach ( $options as $option ) {
                        echo '<option value="'. $option .'">'. $option .'</option>';
                    }

                    echo '</select>';
                    break;

                case 'file':
                    echo '<label for="wfb-field-label" id="wfb-field-label-'. $key .'">'. $field['label'] .'</label>';
                    echo '<input type="file" name="wfb-field['. $key .']" id="wfb-field-'. $key .'" />';
                    break;
            }

            echo '</div>';
        }

        public static function render_old_warranty_form() {
            $reasons = get_option('warranty_reason', '');
            $reason_array = preg_split("/(\r\n|\n|\r)/", $reasons);

            if ( !empty($reasons) && !empty($reason_array) ):
            ?>
                <p><?php _e('Select reason to request for warranty', 'wc_warranty'); ?><br/>
                    <select name="warranty_reason">
                        <?php
                        foreach ($reason_array as $reason) {
                            if ( empty($reason) ) continue;
                            echo '<option value="'.trim($reason).'">'. trim($reason).'</option>';
                        }
                        ?>
                    </select>
                </p>
            <?php
            else:
                echo '<input type="hidden" name="warranty_reason" value="" />';
            endif;

            $question   = get_option( 'warranty_question', '' );
            $required   = get_option( 'warranty_require_question', 'no' );

            if ( $question ):
                ?>
                <p><?php echo $question; ?> <?php if ($required == 'yes') echo '<b>(*)</b>'; ?><br/>
                    <textarea style="width:250px;" rows="4" name="warranty_answer" id="warranty_answer"></textarea>
                </p>
            <?php endif; ?>

            <?php
            $upload = get_option( 'warranty_upload', 'no' );

            if ( $upload == 'yes' ):
                $title      = get_option( 'warranty_upload_title', 'Upload Attachment' );
                $required   = get_option( 'warranty_require_upload', 'no' );
                ?>
                <p>
                    <?php echo $title; ?> <?php if ($required == 'yes') echo '<b>(*)</b>'; ?><br/>
                    <input type="file" name="warranty_upload" />
                </p>
            <?php endif;
        }

        public static function process_warranty_form( $request_id ) {
            $defaults = array(
                'fields'    => array(),
                'inputs'    => ''
            );
            $form   = get_option( 'warranty_form', $defaults );
            $inputs = array();
            $errors = array();
            $data   = array();

            if (! empty($form['inputs']) ) {
                $inputs = json_decode($form['inputs']);
            }

            foreach ( $inputs as $input ) {
                $key    = $input->key;
                $type   = $input->type;
                $field  = $form['fields'][$key];
                $posted = $_POST['wfb-field'];

                if ( $type == 'paragraph' ) continue;

                if ( $type == 'file' ) {
                    $required   = isset($field['required']) && $field['required'] == 'yes';
                    $files      = (isset($_FILES['wfb-field'])) ? $_FILES['wfb-field'] : false;

                    if ( $required && !is_uploaded_file($files['tmp_name'][$key]) ) {
                        $errors[] = sprintf(__('The field "%s" is required', 'wc_warranty'), $field['label']);
                        continue;
                    }

                    if ( is_uploaded_file($files['tmp_name'][$key]) ) {
                        $upload_dir = wp_upload_dir();
                        $new_path   = $upload_dir['path'] .'/'. $files['name'][$key];

                        if ( move_uploaded_file($files['tmp_name'][$key], $new_path) ) {
                            $data[$key] = $upload_dir['subdir'] .'/'. $files['name'][$key];
                        }
                    }

                } else {
                    $value      = (isset($posted[$key])) ? $posted[$key] : false;
                    $required   = isset($field['required']) && $field['required'] == 'yes';

                    if ( $required && !$value ) {
                        $errors[] = sprintf(__('The field "%s" is required', 'wc_warranty'), $field['label']);
                    } elseif ( $value ) {
                        $data[$key] = $value;
                    }
                }
            }

            if (! empty($errors) ) {
                $wp_error = new WP_Error();
                foreach ( $errors as $idx => $error ) {
                    $wp_error->add( 'wfb-error-'. $idx, $error );
                }

                return $wp_error;
            } else {
                // store data
                if (! empty($data) ) {
                    foreach ( $data as $key => $value ) {
                        update_post_meta( $request_id, '_field_'. $key, $value );
                    }
                }
                return true;
            }
        }

        public function return_inventory() {
            $warranty_id    = absint( $_GET['id'] );
            $product_id     = get_post_meta( $warranty_id, '_product_id', true );
            $qty            = get_post_meta( $warranty_id, '_qty', true );
            $product        = function_exists('get_product') ? get_product($product_id) : new WC_Product($product_id);

            $manage_stock   = get_post_meta( $product_id, '_manage_stock', true );

            if ( $product->is_type('variation') ) {
                $stock = get_post_meta( $product_id, '_stock', true );

                if ( $stock > 0 )
                    $manage_stock = 'yes';
            }

            if ( $manage_stock == 'yes' ) {
                $product->increase_stock($qty);

                update_post_meta( $warranty_id, '_returned', 'yes' );
            }

            wp_redirect( 'admin.php?page=warranty_requests&tab=list&updated='. urlencode('Product stock returned') );
            exit;
        }

        public function csv_import_fields( $fields ) {
            $fields['_warranty_type']       = 'meta:_warranty_type';
            $fields['_warranty_duration']   = 'meta:_warranty_duration';
            $fields['_warranty_unit']       = 'meta:_warranty_unit';
            $fields['_warranty']            = 'meta:_warranty';
            $fields['_warranty_label']      = 'meta:_warranty_label';

            return $fields;
        }

    }

    $GLOBALS['wc_warranty'] = new WC_Warranty();
}
