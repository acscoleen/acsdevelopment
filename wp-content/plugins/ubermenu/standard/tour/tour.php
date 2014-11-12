<?php

function ubermenu_tour_load_assets( $hook ){
	global $uberMenu;
	if( $hook == 'nav-menus.php' || $hook == 'appearance_page_uber-menu' || $hook == 'plugins.php' ){
		wp_enqueue_script( 'joyride' , $uberMenu->getEditionURL() . 'tour/joyride/jquery.joyride-2.1.js', array( 'jquery' ) , UBERMENU_VERSION , true  );
		wp_enqueue_style(  'joyride' , $uberMenu->getEditionURL() . 'tour/joyride/joyride-2.1.css' );
	}
}
add_action( 'admin_enqueue_scripts' , 'ubermenu_tour_load_assets' );

function ubermenu_tour_print_steps(){

	$restart = false;
//$restart = true;
//umssd( $_REQUEST );
	if( isset( $_POST['restart-tour-uberMenu'] ) ) {
		update_option( 'tiptour_on_uberMenu' , 1 );
		$restart = true;
	}

	if( get_option( 'tiptour_on_uberMenu' , 1 ) == 0 ) return;

	$screen = get_current_screen();
	$screen_id = $screen->id;
	//echo "[[[$screen_id]]]";

	$steps = array();
	$pause = array();

	$k = 0;	//So that we can dynamically set pauses

	if( $screen_id == 'nav-menus' ){
		
		$steps[$k++] = array(
			'idclass'	=> 'welcome',
			'title'		=>	__( 'Welcome to UberMenu!', 'ubermenu' ),
			'desc'		=> 	__( 'Thank you for installing UberMenu - WordPress Mega Menu Plugin by SevenSpark!  Click "Start Tour" to view a quick introduction', 'ubermenu'),
			'button'	=>	__( 'Start Tour' , 'ubermenu' ),
		);
	}
	else{
		$steps[$k++] = array(
			'idclass'	=> 'welcome-go',
			'title'		=>	__( 'Welcome to UberMenu!', 'ubermenu' ),
			'desc'		=> 	__( 'Thank you for installing UberMenu - WordPress Mega Menu Plugin by SevenSpark!  Click "Start Tour" to view a quick introduction', 'ubermenu').
							'<a href="'.admin_url('nav-menus.php').'" class="joyride-next-tip-link">Start Tour</a>',
			//'button'	=>	__( 'Start Tour' , 'ubermenu' ),
		);
	}

	$steps[$k++] = array(
		'idclass'	=> 'create-menu',
		'title'		=> 	__( 'Create a Menu', 'ubermenu' ),
		'desc'		=>	__( 'Start off by creating a menu using the WordPress 3 Menu System.  Each menu item has new options based on its level.  To create a mega menu drop down, be sure to check "Activate Mega Menu" in the UberMenu Options', 'ubermenu'),
		'screen' 	=>	'nav-menus',
		'id'		=>	'nav-menu-header',
		'ops'		=>	'tipAdjustmentY:-40',	//tipLocation:right;
	);

	$steps[$k++] = array(
		'idclass'	=> 'activate',
		'title'		=>	__( 'Activate UberMenu Theme Locations', 'ubermenu' ),
		'desc'		=>	__( 'Now, activate UberMenu on the appropriate theme location.  This tells UberMenu which menus it should affect, so you can have 1 UberMenu and multiple non-UberMenus.  If your theme does not support theme locations, you can use <a href="http://sevenspark.com/docs/ubermenu-easy-integration" target="_blank">UberMenu Easy Integration</a> instead.', 'ubermenu'),
		'screen'	=>	'nav-menus',
		'id'		=>	'nav-menu-theme-megamenus',
		'ops'		=>	'tipLocation:right;',	
	);

	$pause[] = $k;
	$steps[$k++] = array(
		'idclass'	=> 'manage_locations',
		'title'		=>	__( 'Go to Manage Locations', 'ubermenu' ),
		'desc'		=>	__( 'Click the Manage Locations tab to assign your menu to a theme location.  (The tab appears after you have created a menu).', 'ubermenu').
						'<a href="'.admin_url('nav-menus.php?action=locations').'" class="joyride-next-tip-link">Assign theme locations</a>',
		'screen'	=>	'nav-menus',
		'class'		=>	'nav-tab-wrapper',
		'ops'		=>	'tipAdjustmentY:-40;tipAdjustmentX:140;nextButton:false',
	);


	$steps[$k++] = array(
		'idclass'	=> 'assign_menu',
		'title' 	=>	__( 'Assign Menu to Theme Location', 'ubermenu' ),
		'desc'		=>	__( 'Next, set your menu in the appropriate theme location.  ', 'ubermenu'),
		'screen'	=>	'nav-menus',
		'id'		=>	'menu-locations-wrap',	 
	);

	$pause[] = $k;
	$steps[$k++] = array(
		'idclass'	=> 'control_panel',
		'title'		=>	__( 'Next, configure UberMenu' , 'ubermenu' ),
		'desc'		=>	__( 'Navigate to <a href="themes.php?page=uber-menu">Appearance > UberMenu</a> to configure UberMenu\'s settings' , 'ubermenu' ).
						'<a href="'.admin_url('themes.php?page=uber-menu').'" class="joyride-next-tip-link">UberMenu Control Panel</a>',
		'screen'	=>	'themes',
		'id'		=>	'menu-appearance',
		'ops'		=>	'tipLocation:right;',
	);


	$steps[$k++] = array(
		'idclass'	=> 'orientation',
		'title'		=>	__( 'Select your menu orientation', 'ubermenu' ),
		'desc'		=>	__( 'Decide whether your menu should be vertically or horizontally oriented' , 'ubermenu' ),
		'screen'	=>	'themes',
		'id'		=>	'container-wpmega-orientation',
		'ops'		=>	'tipAdjustmentY:-40',
	);

	$steps[$k++] = array(
		'idclass'	=> 'resources',
		'title'		=>	__( 'Resources: Knowledgebase, Video Tutorials, Support Forum', 'ubermenu' ),
		'desc'		=>	__( 'Access the Knowledgebase, Video Tutorials, Troubleshooter, Customization Assistant, and Support Forum right from the Control Panel.' , 'ubermenu' ),
		'screen'	=>	'themes',
		'class'		=>	'spark-nav-footer',
		'ops'		=>	'tipLocation:right;',
	);

	$steps[$k++] = array(
		'idclass'	=> 'extensions',
		'title'		=>	__( 'Get more out of UberMenu', 'ubermenu' ),
		'desc'		=>	__( 'Extend the functionality of UberMenu with free and premium Extensions like Sticky Menus, Icons, Skin Packs, and more.' , 'ubermenu' ),
		'screen'	=>	'themes',
		'id'		=>	'spark-panel-extensions',
		'ops'		=>	'tipLocation:right;tipAdjustmentY:-50;',
	);

	$steps[$k++] = array(
		'idclass'	=> 'goodbye',
		'title'		=>	__( 'Thanks for using UberMenu!', 'ubermenu' ),
		'desc'		=>	__( 'Have a question?  Don\'t forget to check out all the resources in the <a target="_blank" href="http://sevenspark.com/docs/ubermenu">Knowledgebase</a> for instant answers!  If you get stuck, visit us in the <a target="_blank" href="http://sevenspark.com/support">Support Forum</a>' , 'ubermenu' ),
		'screen'	=>	'themes',
		'button'	=>  __( 'End Tour' , 'ubermenu' ),
	);

	$screen_start_index = 0;
	if( $screen_id == 'nav-menus' ){
		if( isset( $_GET['action'] ) && $_GET['action'] == 'locations' ){
			$screen_start_index = 4;
		}
		else{
			$screen_start_index = 0;
		}
	}
	else if( $screen_id == 'appearance_page_uber-menu' ){
		$screen_start_index = 6;
	}


	?>

	<style>/* Tour */
	#ubermenu-joyride{
		display:none;
	}
	.joyride-tip-guide{
		width:400px;
		padding:15px;
	}
	.joyride-tip-guide h3{
		font-weight:normal;
		margin-bottom:20px;
	}
	.joyride-tip-guide p{
		line-height:24px;
	}
	.joyride-tip-guide a.joyride-next-tip{
		float:right;
		margin-bottom:10px;
	}
	.joyride-tip-welcome-go a.joyride-next-tip,
	.joyride-tip-manage_locations a.joyride-next-tip,
	.joyride-tip-control_panel a.joyride-next-tip{
		display:none;
	}

	.joyride-tip-guide a.joyride-next-tip-link{
		display:block;
		float:right;
		margin-top:30px;
		width: auto;
		padding: 6px 18px 4px;
		font-size: 13px;
		text-decoration: none;
		color: #FFF;
		border: solid 1px #003CB4;
		background: #0063FF;
		background: -moz-linear-gradient(top, rgb(0,99,255) 0%, rgb(0,85,214) 100%);
		background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#0063FF), color-stop(100%,#0055D6));
		background: -webkit-linear-gradient(top, #0063FF 0%,#0055D6 100%);
		background: -o-linear-gradient(top, rgb(0,99,255) 0%,rgb(0,85,214) 100%);
		background: -ms-linear-gradient(top, rgb(0,99,255) 0%,rgb(0,85,214) 100%);
		filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#0063ff', endColorstr='#0055d6',GradientType=0 );
		background: linear-gradient(top, rgb(0,99,255) 0%,rgb(0,85,214) 100%);
		text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.5);
		-webkit-border-radius: 2px;
		-moz-border-radius: 2px;
		border-radius: 2px;
		-webkit-box-shadow: 0px 1px 0px rgba(255, 255, 255, 0.3) inset;
		-moz-box-shadow: 0px 1px 0px rgba(255,255,255,0.3) inset;
		box-shadow: 0px 1px 0px rgba(255, 255, 255, 0.3) inset;
	}
	</style>


	<ol id="ubermenu-joyride">
		<?php foreach( $steps as $step ): ?>
			<?php //if( !isset( $step['screen'] ) || $step['screen'] == $screen_id ): ?>
			<li<?php
					if( isset( $step['idclass'] ) ) echo ' class="joyride-tip-'.$step['idclass'].'"';
					if( isset( $step['id'] ) ) 		echo ' data-id="'.$step['id'].'"'; 
					if( isset( $step['class'] ) ) 	echo ' data-class="'.$step['class'].'"'; 
					if( isset( $step['button'] ) ) 	echo ' data-button="'.$step['button'].'"';
					if( isset( $step['ops'] ) ) 	echo ' data-options="'.$step['ops'].'"';
				?> >
				<h3><?php echo $step['title']; ?></h3>
				<p><?php echo $step['desc']; ?></p>
			</li>
			<?php //endif; ?>
		<?php endforeach; ?>
	</ol>

	<script type="text/javascript">
	jQuery(document).ready(function($) {
		var pauses = [<?php echo implode( ',' , $pause ); ?>];
		var _startOffset = 0;
		_startOffset = <?php echo $screen_start_index; ?>;

		<?php if( !$restart ): ?>
		if( localStorage && localStorage.getItem( 'ubermenu-tour-step' ) ){
			_last_viewed = parseInt( localStorage.getItem( 'ubermenu-tour-step' ) );
			if( _last_viewed > _startOffset ) _startOffset = _last_viewed;
		}
		<?php else: ?>
		if( localStorage ) localStorage.setItem( 'ubermenu-tour-step' , 0 );
		<?php endif; ?>

		//console.log( '_startOffset: ' + _startOffset );
		$("#ubermenu-joyride").joyride({
			autoStart : true,
			pauseAfter: pauses,
			startOffset: _startOffset,
			postStepCallback : function (index, tip) {
				if( localStorage ) localStorage.setItem( 'ubermenu-tour-step' , index + 1 );
			},
			postRideCallback : function( index, tip ){
				var data = {
					action: 'ubermenu_tour_handler',
					tour_action: 'end',
					security: '<?php echo wp_create_nonce( 'ubermenu-tour' ); ?>',
				};
				
				$.post(ajaxurl, data, function(response) {
					//console.log(response);
				});
			}

			/* Options will go here */
		});
	});
	</script>
	<?php
}
//add_action( 'admin_footer' , 'ubermenu_tour_print_steps' );
add_action( 'admin_footer-nav-menus.php' , 'ubermenu_tour_print_steps' );
add_action( 'admin_footer-appearance_page_uber-menu' , 'ubermenu_tour_print_steps' );
add_action( 'admin_footer-plugins.php' , 'ubermenu_tour_print_steps' );




function ubermenu_tour_handler(){
	if( isset( $_POST['tour_action'] ) ){
		if( $_POST['tour_action'] == 'end' ){
			update_option( 'tiptour_on_uberMenu' , 0 );
		}
		else if( $_POST['tour_action'] == 'restart' ){
			update_option( 'tiptour_on_uberMenu' , 1 );
		}
	}
}
add_action( 'wp_ajax_ubermenu_tour_handler', 'ubermenu_tour_handler' , 10 , 0 );


function ubermenu_tour_reset_button(){
	?>
	<form action="nav-menus.php" method="post" class="reset-tour" >
		<input type="submit" value="<?php _e( 'Restart Tour' , 'ubermenu' ); ?>" name="restart-tour-uberMenu" />
	</form>
	<?php
}
