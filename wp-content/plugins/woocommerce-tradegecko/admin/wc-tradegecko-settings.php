<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'wc_tradegecko_add_admin_settings_page' );
/**
 * Add admin setting page
 *
 * @since 1.0
 */
function wc_tradegecko_add_admin_settings_page() {
	add_submenu_page('woocommerce', __('TradeGecko', WC_TradeGecko_Init::$text_domain ), __('TradeGecko', WC_TradeGecko_Init::$text_domain ), 'manage_woocommerce', WC_TradeGecko_Init::$settings_page, 'wc_tradegecko_options_page');
}

/**
 * Output the settings page content
 *
 * @since 1.0
 */
function  wc_tradegecko_options_page() {

	$active_tab = WC_TradeGecko_Init::get_get( 'tab' ) ? WC_TradeGecko_Init::get_get( 'tab' ) : 'general';

	$remove_args = array( 'settings-updated', 'new-auth-code-obtained' );

	$error_count = get_option( WC_TradeGecko_Init::$prefix . 'error_count', 0 );

	ob_start(); ?>

	<div class="wrap">

                <div id="tradegecko-header">
                        <img class="icon-tradegecko" width="32" height="32" src="<?php echo WC_TradeGecko_Init::$plugin_url .  "/assets/images/woo-tg-32x32.png" ?>" ></img>
                        <h3 class="tradegecko-header"><?php echo sprintf( __( 'TradeGecko - Woocommerce add-on. %sGo To Tradegecko%s', WC_TradeGecko_Init::$text_domain ), '<a href="http://go.tradegecko.com" target="_blank">', '</a>' ) ?></h3>
                </div>

		<h2 class="nav-tab-wrapper">
			<a href="<?php echo add_query_arg('tab', 'general', remove_query_arg( $remove_args ) ); ?>"
			   class="nav-tab tab_general <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">
				<?php _e('General', WC_TradeGecko_Init::$text_domain); ?>
			</a><a href="<?php echo add_query_arg('tab', 'api', remove_query_arg( $remove_args ) ); ?>"
			   class="nav-tab <?php echo $active_tab == 'api' ? 'nav-tab-active' : ''; ?>">
				<?php _e('API', WC_TradeGecko_Init::$text_domain); ?>
			</a><a href="<?php echo add_query_arg('tab', 'sync', remove_query_arg( $remove_args ) ); ?>"
			   class="nav-tab <?php echo $active_tab == 'sync' ? 'nav-tab-active' : ''; ?>">
				<?php _e('Sync', WC_TradeGecko_Init::$text_domain); ?>
			</a><a href="<?php echo add_query_arg('tab', 'sync-log', remove_query_arg( $remove_args ) ); ?>"
			   class="nav-tab tab_errors <?php echo $active_tab == 'sync-log' ? 'nav-tab-active' : ''; ?>">
				<?php _e('Sync Logs', WC_TradeGecko_Init::$text_domain); if ( 0 < $error_count ) { ?>
				<mark class="error_mark" style=""><?php echo $error_count; ?></mark><?php } ?>
			</a>
		</h2>

		<div id="tab_container">

			<?php if ( 'true' == WC_TradeGecko_Init::get_get( 'settings-updated') ) { ?>
				<div class="updated settings-error">
					<p><strong><?php _e('Settings Updated', WC_TradeGecko_Init::$text_domain); ?></strong></p>
				</div>
			<?php } elseif ( 'true' == WC_TradeGecko_Init::get_get( 'new-auth-code-obtained' ) ) { ?>
				<div class="updated settings-error">
					<p><strong><?php _e('New Authorization Code Obtained', WC_TradeGecko_Init::$text_domain); ?></strong></p>
				</div>
			<?php } ?>

                        <?php
				if ( 'true' == WC_TradeGecko_Init::get_get( 'settings-updated' ) && 'sync' == $active_tab ) {
                                        // Remove the scheduled hook in case new time and date were set
                                        wp_clear_scheduled_hook( 'wc_tradegecko_synchronization' );
				}
                         ?>

			<form method="post" action="options.php">
				<?php
				if( 'api' == $active_tab  ) {
					settings_fields(WC_TradeGecko_Init::$prefix .'settings_api');
					do_settings_sections(WC_TradeGecko_Init::$prefix .'settings_api');
				} elseif ( 'sync' == $active_tab ) {
					settings_fields(WC_TradeGecko_Init::$prefix .'settings_sync');
					do_settings_sections(WC_TradeGecko_Init::$prefix .'settings_sync');
				} elseif ( 'sync-log' == $active_tab ) {
					settings_fields(WC_TradeGecko_Init::$prefix .'settings_sync_log');
					do_settings_sections(WC_TradeGecko_Init::$prefix .'settings_sync_log');
				} else {
					settings_fields(WC_TradeGecko_Init::$prefix .'settings_general');
					do_settings_sections(WC_TradeGecko_Init::$prefix .'settings_general');
				}

				submit_button();
				?>

			</form>
		</div><!--end #tab_container-->
		<script type="text/javascript">
			jQuery(document).ready(function(){
				// Chosen selects
				jQuery("select.chosen_select").chosen();
			});
		</script>
	</div>
	<?php
	if ( 'sync-log' == $active_tab ) {
		update_option( WC_TradeGecko_Init::$prefix . 'error_count', 0 );
	}

	if ( 'true' == WC_TradeGecko_Init::get_get( 'new-auth-code-obtained' ) && 'api' == $active_tab ) {
		$old_auth_code = get_option( 'wc_tradegecko_old_auth_code', '' );
		$new_auth_code = WC_TradeGecko_Init::get_setting('auth_code');

		// We have a new Authorization Code
		if ( $old_auth_code !== $new_auth_code ) {
			update_option( 'wc_tradegecko_old_auth_code', $new_auth_code );

			// Remove the Refresh token
			update_option( 'wc_tradegecko_api_refresh_token', '' );

			// Remove the stored transient
			delete_transient( 'wc_tradegecko_api_access_token' );

			// Update the Auth Error option
			update_option( 'wc_tg_auth_error', '' );

			try {
				// Obtain a new access token right away
				$token = WC_TradeGecko_Init::$api->check_valid_access_token();

				WC_TradeGecko_Init::add_log( 'Successfully obtained first access token with the new Auth Code.' );
			} catch ( Exception $e ) {
				WC_TradeGecko_Init::add_sync_log( 'error', 'Obtaining first access token with the new Auth Code failed. '. $e->getMessage() );

				WC_TradeGecko_Init::add_log( 'Obtaining first access token with the new Auth Code failed. '. $e->getMessage() );
			}
		}
	}

	// If we saved the API setting
	if ( 'true' == WC_TradeGecko_Init::get_get( 'settings-updated' ) && 'api' == $active_tab ) {
		// Remove the stored transient to force the request for new access token
		delete_transient( 'wc_tradegecko_api_access_token' );
	}

	echo ob_get_clean();
}