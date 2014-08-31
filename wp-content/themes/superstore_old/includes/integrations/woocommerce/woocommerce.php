<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $woo_options;

/**
 * Declare WooCommerce Support
 */
add_action( 'after_setup_theme', 'woocommerce_support' );
function woocommerce_support() {
	add_theme_support( 'woocommerce' );
}

/**
 * CSS
 * Disable the WooCommerce CSS then enqueue Superstore css.
 */
add_filter( 'woocommerce_enqueue_styles', '__return_false' );

if ( ! is_admin() ) {
	add_action( 'wp_enqueue_scripts', 'woo_load_woocommerce_css', 20 );
}
if ( ! function_exists( 'woo_load_woocommerce_css' ) ) {
	function woo_load_woocommerce_css () {
		wp_register_style( 'woocommerce', esc_url( get_template_directory_uri() . '/includes/integrations/woocommerce/css/woocommerce.css' ) );
		wp_enqueue_style( 'woocommerce' );
	} // End woo_load_woocommerce_css()
}

/**
 * Ratings
 * Remove the rating in the loop and the single product - the theme includes
 * it's own functions for this.
 * Add the Superstore rating functions.
 */
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 32 );

add_action( 'woocommerce_after_shop_loop_item', 'superstore_product_rating_overview', 9 );

if ( ! function_exists( 'superstore_product_rating_overview' ) ) {
	function superstore_product_rating_overview() {
		global $product;
		$review_total = get_comments_number();
		if ( $review_total > 0 && get_option( 'woocommerce_enable_review_rating' ) !== 'no' ) {
			echo '<div class="rating-wrap">';
				echo '<a href="' . get_permalink() . '#reviews">';
					echo $product->get_rating_html();
					echo '<span class="review-count">';
						comments_number( '', __('1 review', 'woothemes'), __('% reviews', 'woothemes') );
					echo '</span>';
				echo '</a>';
			echo '</div>';
		}
	}
}

/**
 * Product Columns
 * Change the number of product columns based on specified settings
 */
add_filter( 'loop_shop_columns', 'wooframework_loop_columns' );
if ( ! function_exists( 'wooframework_loop_columns' ) ) {
	function wooframework_loop_columns() {
		global $woo_options;
		if ( ! isset( $woo_options['woocommerce_product_columns'] ) ) {
			$cols = 3;
		} else {
			$cols = $woo_options['woocommerce_product_columns'] + 2;
		}
		return $cols;
	} // End wooframework_loop_columns()
}

/**
 * Products per page
 * Change the number of products per page based on specified settings
 */
add_filter( 'loop_shop_per_page', 'wooframework_products_per_page' );
if ( ! function_exists( 'wooframework_products_per_page' ) ) {
	function wooframework_products_per_page() {
		global $woo_options;
		if ( isset( $woo_options['woocommerce_products_per_page'] ) ) {
			return $woo_options['woocommerce_products_per_page'];
		}
	} // End wooframework_products_per_page()
}

/**
 * Image Wrap
 * Add a wrapping div around product/category images in the loop
 */
add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_product_thumbnail_wrap_open', 5, 2);
add_action( 'woocommerce_before_subcategory_title', 'woocommerce_product_thumbnail_wrap_open', 5, 2);
if (!function_exists('woocommerce_product_thumbnail_wrap_open')) {
	function woocommerce_product_thumbnail_wrap_open() {
		echo '<div class="img-wrap">';
	}
}

add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_product_thumbnail_wrap_close', 15, 2);
add_action( 'woocommerce_before_subcategory_title', 'woocommerce_product_thumbnail_wrap_close', 15, 2);
if (!function_exists('woocommerce_product_thumbnail_wrap_close')) {
	function woocommerce_product_thumbnail_wrap_close() {
		echo '<span class="details-link"></span>';
		echo '</div> <!--/.wrap-->';
	}
}

/**
 * Move the price
 * Move the price function in the loop inside of the wrapping div
 */
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_price', 12 );

/**
 * Product Categories
 * Display product categories in the loop
 */
add_action( 'woocommerce_after_shop_loop_item', 'superstore_product_loop_categories', 2 );
if (!function_exists('superstore_product_loop_categories')) {
	function superstore_product_loop_categories() {
		global $post;
		$terms_as_text = get_the_term_list( $post->ID, 'product_cat', '', ', ', '' );
		if ( ! is_product_category() ) {
			echo '<div class="categories">' . $terms_as_text . '</div>';
		}
	}
}

/**
 * Display Stock
 * Display product stock status in the loop when out of stock
 */
add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_stock', 10);
function woocommerce_template_loop_stock() {
	global $product;
 	if ( ! $product->managing_stock() && ! $product->is_in_stock() ) {
 		echo '<p class="stock out-of-stock">' . __( 'Out of stock', 'woothemes' ) . '</p>';
 	}
}

/**
 * Move product tabs
 * Move the product tabs to just after the summary in the markup
 */
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
add_action( 'woocommerce_single_product_summary', 'woocommerce_output_product_data_tabs', 34 );

/**
 * Move Short Description
 * Move the short product description into the main description tab
 */
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );

/**
 * Tweak Product Tabs
 * Remove the reviews tab - it's loaded back further down the page.
 * Re-add the description tab without a check for the content.
 */
add_filter( 'woocommerce_product_tabs', 'woo_overwrite_tabs', 11 );
function woo_overwrite_tabs( $tabs ) {
	unset( $tabs['reviews'] );
	unset( $tabs['description'] );
	$tabs['description'] = array(
		'title'    => __( 'Description', 'woocommerce' ),
		'priority' => 10,
		'callback' => 'woocommerce_product_description_tab'
		);
	return $tabs;
}

/**
 * Move Product Reviews
 * Add the reviews beneath the product overview
 */
add_action( 'woocommerce_after_single_product_summary', 'superstore_product_reviews', 17 );
function superstore_product_reviews() {
	global $post;

	if ( ! comments_open() )
		return;

	$comments = get_comments(array(
		'post_id' => $post->ID,
		'status' => 'approve'
	));

	comments_template();

}

/**
 * Related Products
 * Remove related products if specified in the settings.
 * Tweak the number of products should be displayed, and the number of columns they should
 * be displayed in according to settings.
 */
add_action( 'wp_head','wooframework_related_products' );
if ( ! function_exists( 'wooframework_related_products' ) ) {
	function wooframework_related_products() {
		global $woo_options;
		if ( isset( $woo_options['woocommerce_related_products'] ) &&  'false' == $woo_options['woocommerce_related_products'] ) {
			remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);
		}
	} // End wooframework_related_products()
}

add_filter( 'woocommerce_output_related_products_args', 'superstore_related_products' );
function superstore_related_products() {
	global $woo_options, $post;
	$single_layout = get_post_meta( $post->ID, '_layout', true );
	$products_max = $woo_options['woocommerce_related_products_maximum'] + 2;
	if ( $woo_options[ 'woocommerce_products_fullwidth' ] == 'true' && ( $single_layout != 'layout-left-content' && $single_layout != 'layout-right-content' ) ) {
		$products_cols = 4;
	} else {
		$products_cols = 3;
	}
	$args = array(
		'posts_per_page' => $products_max,
		'columns'        => $products_cols,
	);
	return $args;
}

/**
 * Upsells
 * Tweak the number of columns upsells are displayed in according to settings.
 */
if ( ! function_exists( 'woo_upsell_display' ) ) {
	function woo_upsell_display() {
	    // Display up sells in correct layout.
		global $woo_options, $post;
		$single_layout = get_post_meta( $post->ID, '_layout', true );

		if ( $woo_options[ 'woocommerce_products_fullwidth' ] == 'true' && ( $single_layout != 'layout-left-content' && $single_layout != 'layout-right-content' ) ) {
			$products_cols = 4;
		} else {
			$products_cols = 3;
		}
	    woocommerce_upsell_display( -1, $products_cols );
	}
}
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
add_action( 'woocommerce_after_single_product_summary', 'woo_upsell_display', 15 );

/**
 * Placeholder
 * Display a custom placeholder if one if specified.
 */
add_filter( 'woocommerce_placeholder_img_src', 'wooframework_wc_placeholder_img_src' );

if ( ! function_exists( 'wooframework_wc_placeholder_img_src' ) ) {
	function wooframework_wc_placeholder_img_src( $src ) {
		global $woo_options;
		if ( isset( $woo_options['woo_placeholder_url'] ) && '' != $woo_options['woo_placeholder_url'] ) {
			$src = $woo_options['woo_placeholder_url'];
		}
		else {
			$src = get_template_directory_uri() . '/images/wc-placeholder.gif';
		}
		return esc_url( $src );
	} // End wooframework_wc_placeholder_img_src()
}

/**
 * Lightbox
 * If the theme lightbox is enabled, disable the WooCommerce lightbox and make
 * product images prettyPhoto galleries.
 */
add_action( 'wp_footer', 'woocommerce_prettyphoto' );
function woocommerce_prettyphoto() {
	global $woo_options;
	if ( $woo_options[ 'woo_enable_lightbox' ] == "true" ) {
		update_option( 'woocommerce_enable_lightbox', false );
		?>
			<script>
				jQuery(document).ready(function(){
					jQuery('.images a').attr('rel', 'prettyPhoto[product-gallery]');
				});
			</script>
		<?php
	}
}

/**
 * Single Product Thumbnails
 * Display a large amount of product thumbnails to remove unnecessary 'last' class.
 */
add_filter( 'woocommerce_product_thumbnails_columns', 'woocommerce_custom_product_thumbnails_columns' );
if ( ! function_exists( 'woocommerce_custom_product_thumbnails_columns' ) ) {
	function woocommerce_custom_product_thumbnails_columns() {
		return 40;
	}
}



/**
 * Add to cart text
 * Tweak the add to cart text to say 'Add' instead.
 */
add_filter( 'add_to_cart_text', 'superstore_custom_cart_button_text', 10, 2 );
add_filter( 'woocommerce_product_add_to_cart_text', 'superstore_custom_cart_button_text', 10, 2 );
function superstore_custom_cart_button_text( $text, $product ) {

    // by default set the text to Add
    $result = 'Add';

    // if the text is 'Read More' the product is most likely out of stock so 'Add' doesn't make much sense. Let's replace it with Info since space is tight
    if ( 'Read More' == $text ) {
        $result = 'Info';
    }

    // return the result
    return __( $result, 'woothemes' );
}

/**
 * Layout
 * Remove the WooCommerce wrappers and add the Superstore wrappers
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
add_action( 'woocommerce_before_main_content', 'woocommerce_theme_before_content', 10 );
add_action( 'woocommerce_after_main_content', 'woocommerce_theme_after_content', 20 );

if ( ! function_exists( 'woocommerce_theme_before_content' ) ) {
	function woocommerce_theme_before_content() {
		global $woo_options;
		if ( ! isset( $woo_options['woocommerce_product_columns'] ) ) {
			$columns = 'woocommerce-columns-3';
		} else {
			$columns = 'woocommerce-columns-' . ( $woo_options['woocommerce_product_columns'] + 2 );
		}
		?>
		<!-- #content Starts -->
		<?php woo_content_before(); ?>
	    <div id="content" class="col-full <?php echo esc_attr( $columns ); ?>">
	        <!-- #main Starts -->
	        <?php woo_main_before(); ?>
	        <div id="main" class="col-left">
	    <?php
	} // End woocommerce_theme_before_content()
}

if ( ! function_exists( 'woocommerce_theme_after_content' ) ) {
	function woocommerce_theme_after_content() {
		?>
			</div><!-- /#main -->
	        <?php woo_main_after();?>
	        <?php do_action( 'woocommerce_sidebar' ); ?>
	    </div><!-- /#content -->
		<?php woo_content_after(); ?>
	    <?php
	} // End woocommerce_theme_after_content()
}

/**
 * Header Search
 * Add a search form to the site header. Uses the WooCommerce search widget.
 */
add_action( 'woo_nav_before', 'woocommerce_search_widget', 30 );
function woocommerce_search_widget() {
	global $woo_options;
	if ( isset( $woo_options['woocommerce_header_search_form'] ) && 'true' == $woo_options['woocommerce_header_search_form'] ) {
		the_widget( 'WC_Widget_Product_Search', 'title=' );
	}
} // End woocommerce_search_widget()

/**
 * Header Account Section
 * Add a section to the header that gives users quick access to their account.
 */
add_action( 'woo_nav_before', 'superstore_user', 40 );
function superstore_user() {
	global $current_user;

	// WooCommerce 2.1 or above is active
	$url_changepass 	= wc_customer_edit_account_url();
	$url_myaccount 		= get_permalink( wc_get_page_id( 'myaccount' ) );
	$url_editaddress 	= get_permalink( wc_get_page_id( 'myaccount' ) );
	$url_vieworder 		= get_permalink( wc_get_page_id( 'view_order' ) );


	?>
	<div class="account <?php if ( is_user_logged_in() ) { echo 'logged-in'; } else { echo 'logged-out'; } ?>">
	<a href="<?php echo $url_myaccount; ?>" title="<?php if ( is_user_logged_in() ) {  _e('Account', 'woothemes' ); } else { _e( 'Log In', 'woothemes' ); } ?>">
		<?php
			echo get_avatar( get_current_user_id() );
		?>
	</a>
		<nav class="account-links">
			<ul>
				<?php if ( wc_get_page_id( 'myaccount' ) !== -1 ) { ?>
					<li class="my-account"><a href="<?php echo $url_myaccount; ?>" class="tiptip" title="<?php if ( is_user_logged_in() ) {  _e('My Account', 'woothemes' ); } else { _e( 'Log In', 'woothemes' ); } ?>"><span><?php if ( is_user_logged_in() ) { _e('My Account', 'woothemes' ); } else { _e( 'Log In', 'woothemes' ); } ?></span></a></li>
				<?php } ?>

				<?php if ( ! is_user_logged_in() && wc_get_page_id( 'myaccount' ) !== -1 && get_option('woocommerce_enable_myaccount_registration')=='yes' ) { ?>
					<li class="register"><a href="<?php echo $url_myaccount; ?>" class="tiptip" title="<?php _e( 'Register', 'woothemes' ); ?>"><span><?php _e( 'Register', 'woothemes' ); ?></span></a></li>
				<?php } ?>

				<?php if ( is_user_logged_in() ) { ?>

					<?php if ( wc_get_page_id( 'myaccount' ) !== -1 ) { ?>
						<li class="edit-address"><a href="<?php echo $url_editaddress; ?>" class="tiptip" title="<?php _e( 'Edit Address', 'woothemes' ); ?>"><span><?php _e( 'Edit Address', 'woothemes' ); ?></span></a></li>
					<?php } ?>

					<li class="edit-password"><a href="<?php echo $url_changepass; ?>" class="tiptip" title="<?php _e( 'Change Password', 'woothemes' ); ?>"><span><?php _e( 'Change Password', 'woothemes' ); ?></span></a></li>

					<li class="logout"><a href="<?php echo wp_logout_url( $_SERVER['REQUEST_URI'] ); ?>" class="tiptip" title="<?php _e( 'Logout', 'woothemes' ); ?>"><span><?php _e( 'Logout', 'woothemes' ); ?></span></a></li>

				<?php } ?>
			</ul>
		</nav>
	<?php

	echo '</div>';
}

/**
 * Breadcrumb
 * Replace the WooCommerce breadcrumb with the WooFramework breadcrumb.
 * Tweak the WooFramework breadcrumb to include product categories.
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0 );

// Customise the breadcrumb
add_filter( 'woo_breadcrumbs_args', 'woo_custom_breadcrumbs_args', 10 );

if (!function_exists('woo_custom_breadcrumbs_args')) {
	function woo_custom_breadcrumbs_args ( $args ) {
		$textdomain = 'woothemes';
		$title = get_bloginfo( 'name' );
		$args = array('separator' => ' ', 'before' => '', 'show_home' => __( $title, $textdomain ),);
		return $args;
	} // End woo_custom_breadcrumbs_args()
}

add_filter( 'woo_breadcrumbs_trail', 'woo_custom_breadcrumbs_trail_add_product_categories', 20 );

function woo_custom_breadcrumbs_trail_add_product_categories ( $trail ) {
  if ( ( get_post_type() == 'product' ) && is_singular() ) {
		global $post;

		$taxonomy = 'product_cat';

		$terms = get_the_terms( $post->ID, $taxonomy );
		$links = array();

		if ( $terms && ! is_wp_error( $terms ) ) {
		$count = 0;
			foreach ( $terms as $c ) {
				$count++;
				if ( $count > 1 ) { continue; }
				$parents = woo_get_term_parents( $c->term_id, $taxonomy, true, ', ', $c->name, array() );

				if ( $parents != '' && ! is_wp_error( $parents ) ) {
					$parents_arr = explode( ', ', $parents );

					foreach ( $parents_arr as $p ) {
						if ( $p != '' ) { $links[] = $p; }
					}
				}
			}

			// Add the trail back on to the end.
			// $links[] = $trail['trail_end'];
			$trail_end = get_the_title($post->ID);

			// Add the new links, and the original trail's end, back into the trail.
			array_splice( $trail, 2, count( $trail ) - 1, $links );

			$trail['trail_end'] = $trail_end;
		}
	}

	return $trail;
} // End woo_custom_breadcrumbs_trail_add_product_categories()

/**
 * Retrieve term parents with separator.
 *
 * @param int $id Term ID.
 * @param string $taxonomy.
 * @param bool $link Optional, default is false. Whether to format with link.
 * @param string $separator Optional, default is '/'. How to separate terms.
 * @param bool $nicename Optional, default is false. Whether to use nice name for display.
 * @param array $visited Optional. Already linked to terms to prevent duplicates.
 * @return string
 */

if ( ! function_exists( 'woo_get_term_parents' ) ) {
function woo_get_term_parents( $id, $taxonomy, $link = false, $separator = '/', $nicename = false, $visited = array() ) {
	$chain = '';
	$parent = &get_term( $id, $taxonomy );
	if ( is_wp_error( $parent ) )
		return $parent;

	if ( $nicename ) {
		$name = $parent->slug;
	} else {
		$name = $parent->name;
	}

	if ( $parent->parent && ( $parent->parent != $parent->term_id ) && !in_array( $parent->parent, $visited ) ) {
		$visited[] = $parent->parent;
		$chain .= woo_get_term_parents( $parent->parent, $taxonomy, $link, $separator, $nicename, $visited );
	}

	if ( $link ) {
		$chain .= '<a href="' . get_term_link( $parent, $taxonomy ) . '" title="' . esc_attr( sprintf( __( "View all posts in %s" ), $parent->name ) ) . '">'.$parent->name.'</a>' . $separator;
	} else {
		$chain .= $name.$separator;
	}
	return $chain;
} // End woo_get_term_parents()
}


/**
 * Sidebar
 * Remove the WooCommerce sidebar.
 * Replace it with a new function which checks whether to display a sidebar or not based
 * on settings.
 */
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
add_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

if ( ! function_exists( 'woocommerce_get_sidebar' ) ) {
	function woocommerce_get_sidebar() {
		global $woo_options, $post;

		// Display the sidebar if full width option is disabled on archives
		if ( ! is_product() ) {
			if ( isset( $woo_options['woocommerce_archives_fullwidth'] ) && 'false' == $woo_options['woocommerce_archives_fullwidth'] ) {
				get_sidebar('shop');
			}
		}

		// Display the sidebar on product details page if the full width option is not enabled.
		$single_layout = get_post_meta( $post->ID, '_layout', true );
		if ( is_product() ) {
			if ( $woo_options[ 'woocommerce_products_fullwidth' ] == 'false' || ( $woo_options[ 'woocommerce_products_fullwidth' ] == 'true' && $single_layout != "" && $single_layout != "layout-full" && $single_layout != "layout-default" ) ) {
				get_sidebar('shop');
			}
		}

	} // End woocommerce_get_sidebar()
}

/**
 * Pagination
 * Replace the WooCommerce pagination with the WooFramework Pagination
 */
remove_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination', 10 );
add_action( 'woocommerce_after_shop_loop', 'woocommerceframework_pagination', 10 );

if ( ! function_exists( 'woocommerceframework_pagination' ) ) {
function woocommerceframework_pagination() {
	if ( is_search() && is_post_type_archive() ) {
		add_filter( 'woo_pagination_args', 'woocommerceframework_add_search_fragment', 10 );
		add_filter( 'woo_pagination_args_defaults', 'woocommerceframework_woo_pagination_defaults', 10 );
	}
	woo_pagination();
} // End woocommerceframework_pagination()
}

if ( ! function_exists( 'woocommerceframework_add_search_fragment' ) ) {
function woocommerceframework_add_search_fragment ( $settings ) {
	$settings['add_fragment'] = '&post_type=product';

	return $settings;
} // End woocommerceframework_add_search_fragment()
}

if ( ! function_exists( 'woocommerceframework_woo_pagination_defaults' ) ) {
function woocommerceframework_woo_pagination_defaults ( $settings ) {
	$settings['use_search_permastruct'] = false;

	return $settings;
} // End woocommerceframework_woo_pagination_defaults()
}

/**
 * Body Class Filter
 * Add a class to the body if full width shop archives are specified or
 * if the nav should be hidden
 */
add_filter( 'body_class','wooframework_layout_body_class', 10 );		// Add layout to body_class output
if ( ! function_exists( 'wooframework_layout_body_class' ) ) {
	function wooframework_layout_body_class( $wc_classes ) {
		global $woo_options, $post;

		$layout = '';
		$nav_visibility = '';
		if ( ! is_404() ) {
			$single_layout = get_post_meta( $post->ID, '_layout', true );
		}

		// Add layout-full class if full width option is enabled
		if ( isset( $woo_options['woocommerce_archives_fullwidth'] ) && 'true' == $woo_options['woocommerce_archives_fullwidth'] && ( is_shop() || is_post_type_archive( 'product' ) || is_tax( get_object_taxonomies( 'product' ) ) ) ) {
			$layout = 'layout-full';
		}
		if ( ( $woo_options[ 'woocommerce_products_fullwidth' ] == "true" && is_product() ) && ( $single_layout != 'layout-left-content' && $single_layout != 'layout-right-content' ) ) {
			$layout = 'layout-full';
		}

		// Add nav-hidden class if specified in theme options
		if ( isset( $woo_options['woocommerce_hide_nav'] ) && 'true' == $woo_options['woocommerce_hide_nav'] && ( is_checkout() ) ) {
			$nav_visibility = 'nav-hidden';
		}

		// Add classes to body_class() output
		$wc_classes[] = $layout;
		$wc_classes[] = $nav_visibility;

		return $wc_classes;
	} // End woocommerce_layout_body_class()
}

/**
 * Cart Fragments
 * Segments of code which should not be protected from caching and able to update
 * without reloading the page.
 */
add_filter( 'add_to_cart_fragments', 'header_add_to_cart_fragment' );

function header_add_to_cart_fragment( $fragments ) {
	global $woocommerce;
	ob_start();
	superstore_cart_button();
	$fragments['a.cart-contents'] = ob_get_clean();
	return $fragments;
}

function superstore_cart_button() {
	global $woocommerce;
	?>
	<a class="cart-contents" href="<?php echo esc_url( $woocommerce->cart->get_cart_url() ); ?>" title="<?php _e( 'View your shopping cart', 'woothemes' ); ?>"><?php echo $woocommerce->cart->get_cart_total(); ?> <span class="contents"><?php echo $woocommerce->cart->cart_contents_count;?></span></a>
	<?php
}

function superstore_mini_cart() {
	global $woocommerce;
	?>

	<ul class="cart">
		<li class="container <?php if ( is_cart() ) echo 'active'; ?>">
       		<?php
       		superstore_cart_button();
			the_widget( 'WC_Widget_Cart', 'title=' );
       		?>
		</li>
	</ul>

	<script>
    jQuery(function(){
		jQuery('ul.cart a.cart-contents, .added_to_cart').tipTip({
			defaultPosition: "top",
			delay: 0
		});
	});
	</script>

	<?php
}
