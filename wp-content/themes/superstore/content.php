<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * The default template for displaying content
 */

	global $woo_options;

/**
 * The Variables
 *
 * Setup default variables, overriding them if the "Theme Options" have been saved.
 */

?>

	<?php woo_post_before(); ?>

	<article <?php post_class(); ?>>

		<div class="post-content">

			<?php woo_post_inside_before(); ?>

			<section class="entry">

				<header class="post-header">

		            <h1 class="entry-title"><a href="<?php the_permalink(); ?>" title="<?php esc_attr_e( 'Continue Reading &rarr;', 'woothemes' ); ?>"><?php the_title(); ?></a></h1>

		        </header>

				<?php if ( isset( $woo_options['woo_post_content'] ) && $woo_options['woo_post_content'] == 'content' ) { the_content( __( 'Continue Reading &rarr;', 'woothemes' ) ); } else { the_excerpt(); } ?>
				<footer class="post-more">
				<?php if ( isset( $woo_options['woo_post_content'] ) && $woo_options['woo_post_content'] == 'excerpt' ) { ?>
					<span class="read-more"><a href="<?php the_permalink(); ?>" title="<?php esc_attr_e( 'Continue Reading &rarr;', 'woothemes' ); ?>"><?php _e( 'Continue Reading &rarr;', 'woothemes' ); ?></a></span>
				<?php } ?>
				</footer>

			</section>

			<?php woo_post_inside_after(); ?>

		</div><!--/.post-content-->

		<?php woo_post_after(); ?>

		<?php woo_post_meta(); ?>

	</article><!-- /.post -->