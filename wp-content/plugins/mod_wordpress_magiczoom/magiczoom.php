<?php
/*

Copyright 2008 MagicToolbox (email : support@magictoolbox.com)
Plugin Name: Magic Zoom
Plugin URI: http://www.magictoolbox.com/magiczoom/
Description: Magic Zoom <sup>&#8482;</sup> lets you display a high-res zoomed image when your visitors hover over an image. Try out some <a target="_blank" href="http://www.magictoolbox.com/magiczoom_integration/">customisation options</a>.
Version: 5.12.3
Author: MagicToolbox
Author URI: http://www.magictoolbox.com/

*/

/*
    WARNING: DO NOT MODIFY THIS FILE!

    NOTE: If you want change Magic Zoom settings
            please go to plugin page
            and click 'Magic Zoom Configuration' link in top navigation sub-menu.
*/

if(!function_exists('magictoolbox_WordPress_MagicZoom_init')) {
    /* Include MagicToolbox plugins core funtions */
    require_once(dirname(__FILE__)."/magiczoom/plugin.php");
}

//MagicToolboxPluginInit_WordPress_MagicZoom ();
register_activation_hook( __FILE__, 'WordPress_MagicZoom_activate');

register_deactivation_hook( __FILE__, 'WordPress_MagicZoom_deactivate');

magictoolbox_WordPress_MagicZoom_init();
?>