<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Header Template
 *
 * Here we setup all logic and XHTML that is required for the header section of all screens.
 *
 * @package WooFramework
 * @subpackage Template
 */

 global $woo_options, $woocommerce;

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<title><?php woo_title( '' ); ?></title>
<?php woo_meta(); ?>
<link rel="pingback" href="<?php echo esc_url( get_bloginfo( 'pingback_url' ) ); ?>" />
<?php
wp_head();
woo_head();
?>

<!--Google Webmasters Code-->
<meta name="google-site-verification" content="qEyU6W3JzExR81XDsosrtGq2n4g6MgZTXaRWQUJy1Hg" />
<!--End Google Webmasters Code-->
<!--Bing Webmasters-->
<meta name="msvalidate.01" content="3CECE4F3BEC1C3F3458A77A39393746D" />
<!--End of Bing Webmasters-->
<!--Google Analytics Tracking Code-->
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-40995302-1', 'acshomeandwork.com');
  ga('send', 'pageview');

</script>
<!--End Google Analytics Tracking Code-->

</head>

<body <?php body_class(); ?>>

<?php woo_top(); ?>

<div id="wrapper">

    <?php woo_header_before(); ?>

	<header id="header" class="col-full">

		<div class="header-top <?php if ( 'true' == $woo_options['woo_ad_top']  ) { echo 'banner'; } ?>">

			<div class="row">

				<?php woo_header_inside(); ?>

			    <div class="heading-group">
					<span class="nav-toggle"><a href="#navigation"><span><?php _e( 'Navigation', 'woothemes' ); ?></span></a></span>
					<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a></h1>
					<h2 class="site-description"><?php bloginfo( 'description' ); ?></h2>
				</div>

				<?php woo_nav_before(); ?>


	    	</div><!--/.row-->

	    </div><!--/.header-top-->

		<nav id="navigation" class="col-full" role="navigation">

			<?php if ( is_woocommerce_activated() && isset( $woo_options['woocommerce_header_cart_link'] ) && 'true' == $woo_options['woocommerce_header_cart_link'] ) {
	       		superstore_mini_cart();
	        } ?>

			<?php
			if ( function_exists( 'has_nav_menu' ) && has_nav_menu( 'primary-menu' ) ) {
				wp_nav_menu( array( 'depth' => 6, 'sort_column' => 'menu_order', 'container' => 'ul', 'menu_id' => 'main-nav', 'menu_class' => 'nav fl', 'theme_location' => 'primary-menu' ) );
			} else {
			?>
	        <ul id="main-nav" class="nav fl">
				<?php if ( is_page() ) $highlight = 'page_item'; else $highlight = 'page_item current_page_item'; ?>
				<li class="<?php echo $highlight; ?>"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php _e( 'Home', 'woothemes' ); ?></a></li>
				<?php wp_list_pages( 'sort_column=menu_order&depth=6&title_li=&exclude=' ); ?>
			</ul><!-- /#nav -->
	        <?php } ?>


		</nav><!-- /#navigation -->

		<?php woo_nav_after(); ?>



	</header><!-- /#header -->

	<?php woo_content_before(); ?>