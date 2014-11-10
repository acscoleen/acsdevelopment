<?php
/**
 * Integrate cloud image optimization into WordPress.
 * @version 1.9.3
 * @package EWWW_Image_Optimizer_Cloud
 */
/*
Plugin Name: EWWW Image Optimizer Cloud
Plugin URI: http://www.exactlywww.com/cloud/
Description: Reduce file sizes for images within WordPress including NextGEN Gallery and GRAND FlAGallery via paid cloud service.
Author: Shane Bishop
Text Domain: ewww-image-optimizer-cloud
Version: 1.9.3
Author URI: http://www.shanebishop.net/
License: GPLv3
*/

// Constants
define('EWWW_IMAGE_OPTIMIZER_DOMAIN', 'ewww-image-optimizer-cloud');
// this is the full system path to the plugin file itself
define('EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE', __FILE__);
// this is the full system path to the plugin folder
define('EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('EWWW_IMAGE_OPTIMIZER_VERSION', '193');

require_once(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'common.php');

$ewww_debug .= 'EWWW IO version: ' . EWWW_IMAGE_OPTIMIZER_VERSION . '<br>';

/**
 * Plugin initialization function
 */
function ewww_image_optimizer_init() {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_init()</b><br>";
	if (preg_match('/image\/webp/', $_SERVER['HTTP_ACCEPT'])) {
		//echo '<!-- webpsupported -->';
	}
	if (get_option('ewww_image_optimizer_version') < EWWW_IMAGE_OPTIMIZER_VERSION) {
		ewww_image_optimizer_install_table();
		update_option('ewww_image_optimizer_version', EWWW_IMAGE_OPTIMIZER_VERSION);
	}
	ewww_image_optimizer_disable_tools();
	if (!defined('EWWW_IMAGE_OPTIMIZER_CLOUD'))
		define('EWWW_IMAGE_OPTIMIZER_CLOUD', TRUE);
	load_plugin_textdomain(EWWW_IMAGE_OPTIMIZER_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

// Plugin initialization for admin area
function ewww_image_optimizer_admin_init() {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_admin_init()</b><br>";
	ewww_image_optimizer_init();
	/*if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_key' ) && ! get_option( 'ewww_image_optimizer_cloud_verified' ) && ! ewww_image_optimizer_cloud_verify( false ) ) {
		add_action('network_admin_notices', 'ewww_image_optimizer_notice_cloud_failed');
		add_action('admin_notices', 'ewww_image_optimizer_notice_cloud_failed');
	}*/
	if (function_exists('is_plugin_active_for_network') && is_plugin_active_for_network('ewww-image-optimizer-cloud/ewww-image-optimizer-cloud.php')) {
		// network version is simply incremented any time we need to make changes to this section for new defaults
		if (get_site_option('ewww_image_optimizer_network_version') < 1) {
			update_site_option('ewww_image_optimizer_network_version', '1');
		}
		// set network settings if they have been POSTed
		if (!empty($_POST['ewww_image_optimizer_optipng_level'])) {
			if (empty($_POST['ewww_image_optimizer_debug'])) $_POST['ewww_image_optimizer_debug'] = '';
			update_site_option('ewww_image_optimizer_debug', $_POST['ewww_image_optimizer_debug']);
			if (empty($_POST['ewww_image_optimizer_jpegtran_copy'])) $_POST['ewww_image_optimizer_jpegtran_copy'] = '';
			update_site_option('ewww_image_optimizer_jpegtran_copy', $_POST['ewww_image_optimizer_jpegtran_copy']);
			if (empty($_POST['ewww_image_optimizer_png_lossy'])) $_POST['ewww_image_optimizer_png_lossy'] = '';
			update_site_option('ewww_image_optimizer_png_lossy', $_POST['ewww_image_optimizer_png_lossy']);
			if (empty($_POST['ewww_image_optimizer_lossy_skip_full'])) $_POST['ewww_image_optimizer_lossy_skip_full'] = '';
			update_site_option('ewww_image_optimizer_lossy_skip_full', $_POST['ewww_image_optimizer_lossy_skip_full']);
			if (empty($_POST['ewww_image_optimizer_delete_originals'])) $_POST['ewww_image_optimizer_delete_originals'] = '';
			update_site_option('ewww_image_optimizer_delete_originals', $_POST['ewww_image_optimizer_delete_originals']);
			if (empty($_POST['ewww_image_optimizer_jpg_to_png'])) $_POST['ewww_image_optimizer_jpg_to_png'] = '';
			update_site_option('ewww_image_optimizer_jpg_to_png', $_POST['ewww_image_optimizer_jpg_to_png']);
			if (empty($_POST['ewww_image_optimizer_png_to_jpg'])) $_POST['ewww_image_optimizer_png_to_jpg'] = '';
			update_site_option('ewww_image_optimizer_png_to_jpg', $_POST['ewww_image_optimizer_png_to_jpg']);
			if (empty($_POST['ewww_image_optimizer_gif_to_png'])) $_POST['ewww_image_optimizer_gif_to_png'] = '';
			update_site_option('ewww_image_optimizer_gif_to_png', $_POST['ewww_image_optimizer_gif_to_png']);
			if (empty($_POST['ewww_image_optimizer_jpg_background'])) $_POST['ewww_image_optimizer_jpg_background'] = '';
			update_site_option('ewww_image_optimizer_jpg_background', $_POST['ewww_image_optimizer_jpg_background']);
			if (empty($_POST['ewww_image_optimizer_jpg_quality'])) $_POST['ewww_image_optimizer_jpg_quality'] = '';
			update_site_option('ewww_image_optimizer_jpg_quality', $_POST['ewww_image_optimizer_jpg_quality']);
			if (empty($_POST['ewww_image_optimizer_disable_convert_links'])) $_POST['ewww_image_optimizer_disable_convert_links'] = '';
			update_site_option('ewww_image_optimizer_disable_convert_links', $_POST['ewww_image_optimizer_disable_convert_links']);
			if (empty($_POST['ewww_image_optimizer_cloud_key'])) $_POST['ewww_image_optimizer_cloud_key'] = '';
			update_site_option('ewww_image_optimizer_cloud_key', $_POST['ewww_image_optimizer_cloud_key']);
			if (empty($_POST['ewww_image_optimizer_cloud_jpg'])) $_POST['ewww_image_optimizer_cloud_jpg'] = '';
			update_site_option('ewww_image_optimizer_cloud_jpg', $_POST['ewww_image_optimizer_cloud_jpg']);
			if (empty($_POST['ewww_image_optimizer_cloud_png'])) $_POST['ewww_image_optimizer_cloud_png'] = '';
			update_site_option('ewww_image_optimizer_cloud_png', $_POST['ewww_image_optimizer_cloud_png']);
			if (empty($_POST['ewww_image_optimizer_cloud_png_compress'])) $_POST['ewww_image_optimizer_cloud_png_compress'] = '';
			update_site_option('ewww_image_optimizer_cloud_png_compress', $_POST['ewww_image_optimizer_cloud_png_compress']);
			if (empty($_POST['ewww_image_optimizer_cloud_gif'])) $_POST['ewww_image_optimizer_cloud_gif'] = '';
			update_site_option('ewww_image_optimizer_cloud_gif', $_POST['ewww_image_optimizer_cloud_gif']);
			if (empty($_POST['ewww_image_optimizer_auto'])) $_POST['ewww_image_optimizer_auto'] = '';
			update_site_option('ewww_image_optimizer_auto', $_POST['ewww_image_optimizer_auto']);
			if (empty($_POST['ewww_image_optimizer_aux_paths'])) $_POST['ewww_image_optimizer_aux_paths'] = '';
			update_site_option('ewww_image_optimizer_aux_paths', ewww_image_optimizer_aux_paths_sanitize($_POST['ewww_image_optimizer_aux_paths']));
			if (empty($_POST['ewww_image_optimizer_enable_cloudinary'])) $_POST['ewww_image_optimizer_enable_cloudinary'] = '';
			update_site_option('ewww_image_optimizer_enable_cloudinary', $_POST['ewww_image_optimizer_enable_cloudinary']);
			if (empty($_POST['ewww_image_optimizer_delay'])) $_POST['ewww_image_optimizer_delay'] = '';
			update_site_option('ewww_image_optimizer_delay', intval($_POST['ewww_image_optimizer_delay']));
			if (empty($_POST['ewww_image_optimizer_interval'])) $_POST['ewww_image_optimizer_interval'] = '';
			update_site_option('ewww_image_optimizer_interval', intval($_POST['ewww_image_optimizer_interval']));
			if (empty($_POST['ewww_image_optimizer_skip_size'])) $_POST['ewww_image_optimizer_skip_size'] = '';
			update_site_option('ewww_image_optimizer_skip_size', intval($_POST['ewww_image_optimizer_skip_size']));
			add_action('network_admin_notices', 'ewww_image_optimizer_network_settings_saved');
		}
	}
	// register all the EWWW IO settings
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_debug');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_jpegtran_copy');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_png_lossy');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_lossy_skip_full');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_delete_originals');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_jpg_to_png');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_png_to_jpg');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_gif_to_png');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_jpg_background', 'ewww_image_optimizer_jpg_background_sanitize');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_jpg_quality', 'ewww_image_optimizer_jpg_quality_sanitize');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_disable_convert_links');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_bulk_resume');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_bulk_attachments');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_aux_resume');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_aux_attachments');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_aux_type');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_cloud_key', 'ewww_image_optimizer_cloud_key_sanitize');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_cloud_jpg');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_cloud_png');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_cloud_png_compress');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_cloud_gif');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_auto');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_aux_paths', 'ewww_image_optimizer_aux_paths_sanitize');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_enable_cloudinary');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_delay', 'intval');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_interval', 'intval');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_skip_size', 'intval');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_import_status');
	// setup scheduled optimization if the user has enabled it, and it isn't already scheduled
	if (ewww_image_optimizer_get_option('ewww_image_optimizer_auto') == TRUE && !wp_next_scheduled('ewww_image_optimizer_auto')) {
		$ewww_debug .= "scheduling auto-optimization<br>";
		wp_schedule_event(time(), 'hourly', 'ewww_image_optimizer_auto');
	} elseif (ewww_image_optimizer_get_option('ewww_image_optimizer_auto') == TRUE) {
		$ewww_debug .= "auto-optimization already scheduled: " . wp_next_scheduled('ewww_image_optimizer_auto') . "<br>";
	} elseif (wp_next_scheduled('ewww_image_optimizer_auto')) {
		$ewww_debug .= "un-scheduling auto-optimization<br>";
		wp_clear_scheduled_hook('ewww_image_optimizer_auto');
		if (function_exists('is_plugin_active_for_network') && is_plugin_active_for_network('ewww-image-optimizer-cloud/ewww-image-optimizer-cloud.php')) {
			global $wpdb;
			if (function_exists('wp_get_sites')) {
				add_filter('wp_is_large_network', 'ewww_image_optimizer_large_network', 20, 0);
				$blogs = wp_get_sites(array(
					'network_id' => $wpdb->siteid,
					'limit' => 10000
				));
				remove_filter('wp_is_large_network', 'ewww_image_optimizer_large_network', 20, 0);
			} else {
				$query = "SELECT blog_id FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}' ";
				$blogs = $wpdb->get_results($query, ARRAY_A);
			}
			foreach ($blogs as $blog) {
				switch_to_blog($blog['blog_id']);
				wp_clear_scheduled_hook('ewww_image_optimizer_auto');
			}
			restore_current_blog();
		}
	}
	// require the files that do the bulk processing 
	require_once(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'bulk.php'); 
	require_once(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'aux-optimize.php'); 
	// queue the function that contains custom styling for our progressbars, but only in wp 3.8+ 
	global $wp_version; 
	if ( substr($wp_version, 0, 3) >= 3.8 ) {  
		add_action('admin_enqueue_scripts', 'ewww_image_optimizer_progressbar_style'); 
	}
}

// check the mimetype of the given file ($path) with various methods
// valid values for $type are 'b' for binary or 'i' for image
function ewww_image_optimizer_mimetype($path, $case) {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_mimetype()</b><br>";
	$ewww_debug .= "testing mimetype: $path <br>";
	if (function_exists('finfo_file') && defined('FILEINFO_MIME')) {
		// create a finfo resource
		$finfo = finfo_open(FILEINFO_MIME);
		// retrieve the mimetype
		$type = explode(';', finfo_file($finfo, $path));
		$type = $type[0];
		finfo_close($finfo);
		$ewww_debug .= "finfo_file: $type <br>";
	}
	// see if we can use the getimagesize function
	if (empty($type) && function_exists('getimagesize') && $case === 'i') {
		// run getimagesize on the file
		$type = getimagesize($path);
		// make sure we have results
		if(false !== $type){
			// store the mime-type
			$type = $type['mime'];
		}
		$ewww_debug .= "getimagesize: $type <br>";
	}
	// see if we can use mime_content_type
	if (empty($type) && function_exists('mime_content_type')) {
		// retrieve and store the mime-type
		$type = mime_content_type($path);
		$ewww_debug .= "mime_content_type: $type <br>";
	}
	// if we are dealing with a binary, and found an executable
	if ($case == 'b' && preg_match('/executable/', $type)) {
		return $type;
	// otherwise, if we are dealing with an image
	} elseif ($case == 'i') {
		return $type;
	// if all else fails, bail
	} else {
		return false;
	}
}

/**
 * Process an image.
 *
 * Returns an array of the $file, $results, $converted to tell us if an image changes formats, and the $original file if it did.
 *
 * @param   string $file		Full absolute path to the image file
 * @param   int $gallery_type		1=wordpress, 2=nextgen, 3=flagallery, 4=aux_images, 5=image editor, 6=imagestore, 7=retina
 * @param   boolean $converted		tells us if this is a resize and the full image was converted to a new format
 * @returns array
 */
function ewww_image_optimizer($file, $gallery_type, $converted, $new, $fullsize = false) {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer()</b><br>";
	// if the plugin gets here without initializing, we need to run through some things first
	if ( ! defined('EWWW_IMAGE_OPTIMIZER_CLOUD' ) ) {
		ewww_image_optimizer_init();
	}
	$bypass_optimization = apply_filters( 'ewww_image_optimizer_bypass', false, $file ); 
	if (true === $bypass_optimization) { 
		// tell the user optimization was skipped 
		$msg = __( "Optimization skipped", EWWW_IMAGE_OPTIMIZER_DOMAIN ); 
		$ewww_debug .= "optimization bypassed: $file <br>"; 
		// send back the above message 
		return array(false, $msg, $converted, $file); 
	}
	// initialize the original filename 
	$original = $file;
	$result = '';
	// check that the file exists
	if (FALSE === file_exists($file)) {
		// tell the user we couldn't find the file
		$msg = sprintf(__("Could not find <span class='code'>%s</span>", EWWW_IMAGE_OPTIMIZER_DOMAIN), $file);
		$ewww_debug .= "file doesn't appear to exist: $file <br>";
		// send back the above message
		return array(false, $msg, $converted, $original);
	}
	// check that the file is writable
	if ( FALSE === is_writable($file) ) {
		// tell the user we can't write to the file
		$msg = sprintf(__("<span class='code'>%s</span> is not writable", EWWW_IMAGE_OPTIMIZER_DOMAIN), $file);
		$ewww_debug .= "couldn't write to the file<br>";
		// send back the above message
		return array(false, $msg, $converted, $original);
	}
	if (function_exists('fileperms'))
		$file_perms = substr(sprintf('%o', fileperms($file)), -4);
	$file_owner = 'unknown';
	$file_group = 'unknown';
	if (function_exists('posix_getpwuid')) {
		$file_owner = posix_getpwuid(fileowner($file));
		$file_owner = $file_owner['name'];
	}
	if (function_exists('posix_getgrgid')) {
		$file_group = posix_getgrgid(filegroup($file));
		$file_group = $file_group['name'];
	}
	$ewww_debug .= "permissions: $file_perms, owner: $file_owner, group: $file_group <br>";
	$type = ewww_image_optimizer_mimetype($file, 'i');
	if (!$type) {
		//otherwise we store an error message since we couldn't get the mime-type
		$msg = __('Missing finfo_file(), getimagesize() and mime_content_type() PHP functions', EWWW_IMAGE_OPTIMIZER_DOMAIN);
		$ewww_debug .= "couldn't find any functions for mimetype detection<br>";
		return array(false, $msg, $converted, $original);
	}
	// if the full-size image was converted
	if ($converted) {
		$ewww_debug .= "full-size image was converted, need to rebuild filename for meta<br>";
		$filenum = $converted;
		// grab the file extension
		preg_match('/\.\w+$/', $file, $fileext);
		// strip the file extension
		$filename = str_replace($fileext[0], '', $file);
		// grab the dimensions
		preg_match('/-\d+x\d+(-\d+)*$/', $filename, $fileresize);
		// strip the dimensions
		$filename = str_replace($fileresize[0], '', $filename);
		// reconstruct the filename with the same increment (stored in $converted) as the full version
		$refile = $filename . '-' . $filenum . $fileresize[0] . $fileext[0];
		// rename the file
		rename($file, $refile);
		$ewww_debug .= "moved $file to $refile<br>";
		// and set $file to the new filename
		$file = $refile;
		$original = $file;
	}
	// get the original image size
	$orig_size = filesize($file);
	$ewww_debug .= "original filesize: $orig_size<br>";
	// initialize $new_size with the original size
//	$new_size = $orig_size;
	$new_size = 0;
	// set the optimization process to OFF
	$optimize = false;
	// toggle the convert process to ON
	$convert = true;
	// run the appropriate optimization/conversion for the mime-type
	switch($type) {
		case 'image/jpeg':
			// if jpg2png conversion is enabled, and this image is in the wordpress media library
			if ((ewww_image_optimizer_get_option('ewww_image_optimizer_jpg_to_png') && $gallery_type == 1) || !empty($_GET['convert'])) {
				// generate the filename for a PNG
				// if this is a resize version
				if ($converted) {
					// just change the file extension
					$pngfile = preg_replace('/\.\w+$/', '.png', $file);
				// if this is a full size image
				} else {
					// get a unique filename for the png image
					list($pngfile, $filenum) = ewww_image_optimizer_unique_filename($file, '.png');
				}
			} else {
				// otherwise, set it to OFF
				$convert = false;
				$pngfile = '';
			}
			// check for previous optimization, so long as the force flag is on and this isn't a new image that needs converting
			if ( empty( $_REQUEST['force'] ) && ! ( $new && $convert ) ) {
				if ( $results_msg = ewww_image_optimizer_check_table( $file, $orig_size ) ) {
					return array( $file, $results_msg, $converted, $original );
				}
			}
			if (ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_jpg')) {
				list($file, $converted, $result, $new_size) = ewww_image_optimizer_cloud_optimizer($file, $type, $convert, $pngfile, 'image/png', $fullsize);
				if ($converted) $converted = $filenum;
			}
			break;
		case 'image/png':
			// png2jpg conversion is turned on, and the image is in the wordpress media library
			if ( ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_to_jpg' ) || ! empty( $_GET['convert'] ) ) && $gallery_type == 1 && ! $fullsize && ( ! ewww_image_optimizer_png_alpha( $file ) || ewww_image_optimizer_jpg_background() ) ) {
				$ewww_debug .= "PNG to JPG conversion turned on<br>";
				// if the user set a fill background for transparency
				$background = '';
				if ($background = ewww_image_optimizer_jpg_background()) {
					// set background color for GD
					$r = hexdec('0x' . strtoupper(substr($background, 0, 2)));
                                        $g = hexdec('0x' . strtoupper(substr($background, 2, 2)));
					$b = hexdec('0x' . strtoupper(substr($background, 4, 2)));
					// set the background flag for 'convert'
					$background = "-background " . '"' . "#$background" . '"';
				} else {
					$r = '255';
					$g = '255';
					$b = '255';
				}
				// if the user manually set the JPG quality
				if ($quality = ewww_image_optimizer_jpg_quality()) {
					// set the quality for GD
					$gquality = $quality;
					// set the quality flag for 'convert'
					$cquality = "-quality $quality";
				} else {
					$cquality = '';
					$gquality = '92';
				}
				// if this is a resize version
				if ($converted) {
					// just replace the file extension with a .jpg
					$jpgfile = preg_replace('/\.\w+$/', '.jpg', $file);
				// if this is a full version
				} else {
					// construct the filename for the new JPG
					list($jpgfile, $filenum) = ewww_image_optimizer_unique_filename($file, '.jpg');
				}
			} else {
				$ewww_debug .= "PNG to JPG conversion turned off<br>";
				// turn the conversion process OFF
				$convert = false;
				$jpgfile = '';
				$r = null;
				$g = null;
				$b = null;
				$gquality = null;
			}
			// check for previous optimization, so long as the force flag is on and this isn't a new image that needs converting
			if ( empty( $_REQUEST['force'] ) && ! ( $new && $convert ) ) {
				if ( $results_msg = ewww_image_optimizer_check_table( $file, $orig_size ) ) {
					return array( $file, $results_msg, $converted, $original );
				}
			}
			// retrieve the filesize of the original image
			//$orig_size = filesize($file);
			if (ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png')) {
				list($file, $converted, $result, $new_size) = ewww_image_optimizer_cloud_optimizer($file, $type, $convert, $jpgfile, 'image/jpeg', $fullsize, array('r' => $r, 'g' => $g, 'b' => $b, 'quality' => $gquality));
				if ($converted) $converted = $filenum;
			}
			break;
		case 'image/gif':
			// if gif2png is turned on, and the image is in the wordpress media library
			if (((ewww_image_optimizer_get_option('ewww_image_optimizer_gif_to_png') && $gallery_type == 1) || !empty($_GET['convert'])) && !ewww_image_optimizer_is_animated($file)) {
				// generate the filename for a PNG
				// if this is a resize version
				if ($converted) {
					// just change the file extension
					$pngfile = preg_replace('/\.\w+$/', '.png', $file);
				// if this is the full version
				} else {
					// construct the filename for the new PNG
					list($pngfile, $filenum) = ewww_image_optimizer_unique_filename($file, '.png');
				}
			} else {
				// turn conversion OFF
				$convert = false;
				$pngfile = '';
			}
			// check for previous optimization, so long as the force flag is on and this isn't a new image that needs converting
			if ( empty( $_REQUEST['force'] ) && ! ( $new && $convert ) ) {
				if ( $results_msg = ewww_image_optimizer_check_table( $file, $orig_size ) ) {
					return array( $file, $results_msg, $converted, $original );
				}
			}
			if (ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_gif')) {
				list($file, $converted, $result, $new_size) = ewww_image_optimizer_cloud_optimizer($file, $type, $convert, $pngfile, 'image/png', $fullsize);
				if ($converted) $converted = $filenum;
			}
			break;
		default:
			// if not a JPG, PNG, or GIF, tell the user we don't work with strangers
			return array($file, __('Unknown type: ' . $type, EWWW_IMAGE_OPTIMIZER_DOMAIN), $converted, $original);
	}
	// if their cloud api license limit has been exceeded
	if ($result == 'exceeded') {
		return array($file, __('License exceeded', EWWW_IMAGE_OPTIMIZER_DOMAIN), $converted, $original);
	}
	if (!empty($new_size)) {
		$results_msg = ewww_image_optimizer_update_table ($file, $new_size, $orig_size, $new);
		return array($file, $results_msg, $converted, $original);
	}
	// otherwise, send back the filename, the results (some sort of error message), the $converted flag, and the name of the original image
	return array($file, $result, $converted, $original);
}

// displays the EWWW IO options and provides one-click install for the optimizer utilities
function ewww_image_optimizer_options () {
	global $ewww_debug;
	$ewww_debug .= "<b>ewww_image_optimizer_options()</b><br>";
	?>
	<script type='text/javascript'>
		jQuery(document).ready(function($) {$('.fade').fadeTo(5000,1).fadeOut(3000);});
	</script>
	<div class="wrap" style="clear: both"><div id="ewww-container-left" style="float: left; margin-right: 200px;">
		<div id="icon-options-general" class="icon32"><br /></div>
		<h2>EWWW <?php _e('Image Optimizer Settings', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></h2>
		<p><a href="http://wordpress.org/extend/plugins/ewww-image-optimizer-cloud/"><?php _e('Plugin Home Page', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></a> |
		<a href="http://wordpress.org/extend/plugins/ewww-image-optimizer-cloud/installation/"><?php _e('Installation Instructions', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></a> | 
		<a href="http://wordpress.org/support/plugin/ewww-image-optimizer-cloud"><?php _e('Plugin Support', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></a> | 
		<a href="http://stats.pingdom.com/w89y81bhecp4"><?php _e('Cloud Status', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></a></p>
<?php		if (function_exists('is_plugin_active_for_network') && is_plugin_active_for_network('ewww-image-optimizer-cloud/ewww-image-optimizer-cloud.php')) {
			$bulk_link = __('Media Library') . ' -> ' . __('Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN);
		} else {
			$bulk_link = '<a href="upload.php?page=ewww-image-optimizer-bulk">' . __('Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</a>'; 
		} ?> 
		<p><?php printf( __( 'New images uploaded to the Media Library will be optimized automatically. If you have existing images you would like to optimize, you can use the %s tool.', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $bulk_link ); ?></p>
		<div id="status" style="border: 1px solid #ccc; padding: 0 8px; border-radius: 12px;">
			<h3>Plugin Status</h3>
			<?php
			echo "<b>" . __('Total Savings:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</b> <span id='total_savings'>" . __('Calculating...', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</span><br>";
			if (ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_key')) {
				echo '<p><b>Cloud API Key:</b> ';
				$verify_cloud = ewww_image_optimizer_cloud_verify(false); 
				if (preg_match('/great/', $verify_cloud)) {
					echo '<span style="color: green">' . __('Verified,', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ' </span>';
					echo ewww_image_optimizer_cloud_quota();
				} elseif (preg_match('/exceeded/', $verify_cloud)) { 
					echo '<span style="color: orange">' . __('Verified,', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ' </span>'; 
					echo ewww_image_optimizer_cloud_quota();
				} else { 
					echo '<span style="color: red">' . __('Not Verified', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>'; 
				}
				echo '</p>';
			}
			echo "\n";
			echo '<b>' . __('Only need one of these:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ' </b>';
			// initialize this variable to check for the 'file' command if we don't have any php libraries we can use
			if (function_exists('finfo_file')) {
				echo 'finfo: <span style="color: green; font-weight: bolder">OK</span>&emsp;&emsp;';
				$file_command_check = false;
			} else {
				echo 'finfo: <span style="color: red; font-weight: bolder">MISSING</span>&emsp;&emsp;';
			}
			if (function_exists('getimagesize')) {
				echo 'getimagesize(): <span style="color: green; font-weight: bolder">OK</span>&emsp;&emsp;';
			} else {
				echo 'getimagesize(): <span style="color: red; font-weight: bolder">MISSING</span>&emsp;&emsp;';
			}
			if (function_exists('mime_content_type')) {
				echo 'mime_content_type(): <span style="color: green; font-weight: bolder">OK</span><br>';
				$file_command_check = false;
			} else {
				echo 'mime_content_type(): <span style="color: red; font-weight: bolder">MISSING</span><br>';
			}
			?></p>
		</div>
<?php		if (function_exists('is_plugin_active_for_network') && is_plugin_active_for_network('ewww-image-optimizer-cloud/ewww-image-optimizer-cloud.php')) { ?>
		<form method="post" action="">
<?php		} else { ?>
		<form method="post" action="options.php">
			<?php settings_fields('ewww_image_optimizer_options'); 
		} ?>
			<h3><?php _e('Cloud Settings', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></h3>
			<p><?php _e('If exec() is disabled for security reasons (and enabling it is not an option), or you would like to offload image optimization to a third-party server, you may purchase an API key for our cloud optimization service. The API key should be entered below, and cloud optimization must be enabled for each image format individually.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?> <a href="http://www.exactlywww.com/cloud/"><?php _e('Purchase an API key.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></a></p>
			<table class="form-table">
				<tr><th><label for="ewww_image_optimizer_cloud_key"><?php _e('Cloud optimization API Key', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></label></th><td><input type="text" id="ewww_image_optimizer_cloud_key" name="ewww_image_optimizer_cloud_key" value="<?php echo ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_key'); ?>" size="32" /> <?php _e('API Key will be validated when you save your settings.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?> <a href="http://www.exactlywww.com/cloud/"><?php _e('Purchase a key.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></a></td></tr>
				<tr><th><label for="ewww_image_optimizer_cloud_jpg">JPG <?php _e('cloud optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></label></th><td><input type="checkbox" id="ewww_image_optimizer_cloud_jpg" name="ewww_image_optimizer_cloud_jpg" value="true" <?php if (ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_jpg') == TRUE) { ?>checked="true"<?php } ?> /></td></tr>
				<tr><th><label for="ewww_image_optimizer_cloud_png">PNG <?php _e('cloud optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></label></th><td><input type="checkbox" id="ewww_image_optimizer_cloud_png" name="ewww_image_optimizer_cloud_png" value="true" <?php if (ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png') == TRUE) { ?>checked="true"<?php } ?> />&emsp;&emsp;
					<label for="ewww_image_optimizer_cloud_png_compress"><?php _e('extra compression (slower)', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></label> <input type="checkbox" id="ewww_image_optimizer_cloud_png_compress" name="ewww_image_optimizer_cloud_png_compress" value="true" <?php if (ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png_compress') == TRUE) { ?>checked="true"<?php } ?> /></td></tr>
				<tr><th><label for="ewww_image_optimizer_cloud_gif">GIF <?php _e('cloud optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></label></th><td><input type="checkbox" id="ewww_image_optimizer_cloud_gif" name="ewww_image_optimizer_cloud_gif" value="true" <?php if (ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_gif') == TRUE) { ?>checked="true"<?php } ?> /></td></tr>
			</table>
			<h3><?php _e('General Settings', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></h3>
			<table class="form-table">
				<tr><th><label for="ewww_image_optimizer_debug"><?php _e('Debugging', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></label></th><td><input type="checkbox" id="ewww_image_optimizer_debug" name="ewww_image_optimizer_debug" value="true" <?php if (ewww_image_optimizer_get_option('ewww_image_optimizer_debug') == TRUE) { ?>checked="true"<?php } ?> /> <?php _e('Use this to provide information for support purposes, or if you feel comfortable digging around in the code to fix a problem you are experiencing.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></td></tr>
				<tr><th><label for="ewww_image_optimizer_auto"><?php _e('Scheduled optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></label></th><td><input type="checkbox" id="ewww_image_optimizer_auto" name="ewww_image_optimizer_auto" value="true" <?php if (ewww_image_optimizer_get_option('ewww_image_optimizer_auto') == TRUE) { ?>checked="true"<?php } ?> /> <?php _e('This will enable scheduled optimization of unoptimized images for your theme, buddypress, and any additional folders you have configured below. Runs hourly: wp_cron only runs when your site is visited, so it may be even longer between optimizations.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></td></tr>
				<tr><th><label for="ewww_image_optimizer_aux_paths"><?php _e('Folders to optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></label></th><td><?php printf(__('One path per line, must be within %s. Use full paths, not relative paths.', EWWW_IMAGE_OPTIMIZER_DOMAIN), ABSPATH); ?><br />
					<textarea id="ewww_image_optimizer_aux_paths" name="ewww_image_optimizer_aux_paths" rows="3" cols="60"><?php if ($aux_paths = ewww_image_optimizer_get_option('ewww_image_optimizer_aux_paths')) { foreach ($aux_paths as $path) echo "$path\n"; } ?></textarea>
					<p class="description">Provide paths containing images to be optimized using scheduled optimization or 'Optimize More' in the Tools menu.<br>
					<b><a href="http://wordpress.org/support/plugin/ewww-image-optimizer-cloud"><?php _e('Please submit a support request in the forums to have folders created by a particular plugin auto-included in the future.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></a></b></p></td></tr>
				<tr><th><label for="ewww_image_optimizer_delay"><?php _e('Bulk Delay', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></label></th><td><input type="text" id="ewww_image_optimizer_delay" name="ewww_image_optimizer_delay" size="5" value="<?php echo ewww_image_optimizer_get_option('ewww_image_optimizer_delay'); ?>"> <?php _e('Choose how long to pause between images (in seconds, 0 = disabled)', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></td></tr>
				<!--                <tr><th><label for="ewww_image_optimizer_interval"><?php _e('Image Batch Size', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></label></th><td><input type="text" id="ewww_image_optimizer_interval" name="ewww_image_optimizer_interval" size="5" value="<?php echo ewww_image_optimizer_get_option('ewww_image_optimizer_interval'); ?>"> <?php _e('Choose how many images should be processed before each delay', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></td></tr>-->
				<tr><th><label for="ewww_image_optimizer_skip_size"><?php _e('Skip Images', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></label></th><td><input type="text" id="ewww_image_optimizer_skip_size" name="ewww_image_optimizer_skip_size" size="8" value="<?php echo ewww_image_optimizer_get_option('ewww_image_optimizer_skip_size'); ?>"> <?php _e('Do not optimize images smaller than this (in bytes)', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></td></tr>
				<tr><th><label for="ewww_image_optimizer_lossy_skip_full"><?php _e('Exclude full-size images from lossy optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></label></th><td><input type="checkbox" id="ewww_image_optimizer_lossy_skip_full" name="ewww_image_optimizer_lossy_skip_full" value="true" <?php if (ewww_image_optimizer_get_option('ewww_image_optimizer_lossy_skip_full') == TRUE) { ?>checked="true"<?php } ?> /></td></tr> 
<?php	if (class_exists('Cloudinary') && Cloudinary::config_get("api_secret")) { ?>
				<tr><th><label for="ewww_image_optimizer_enable_cloudinary"><?php _e('Automatic Cloudinary upload', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></label></th><td><input type="checkbox" id="ewww_image_optimizer_enable_cloudinary" name="ewww_image_optimizer_enable_cloudinary" value="true" <?php if (ewww_image_optimizer_get_option('ewww_image_optimizer_enable_cloudinary') == TRUE) { ?>checked="true"<?php } ?> /> <?php _e('When enabled, uploads to the Media Library will be transferred to Cloudinary after optimization. Cloudinary generates resizes, so only the full-size image is uploaded.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></td></tr>
<?php	} ?>
			</table>
			<h3><?php _e('Optimization Settings', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></h3>
			<table class="form-table">
				<tr><th><label for="ewww_image_optimizer_jpegtran_copy"><?php _e('Remove metadata', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></label></th>
				<td><input type="checkbox" id="ewww_image_optimizer_jpegtran_copy" name="ewww_image_optimizer_jpegtran_copy" value="true" <?php if (ewww_image_optimizer_get_option('ewww_image_optimizer_jpegtran_copy') == TRUE) { ?>checked="true"<?php } ?> /> <?php _e('This will remove ALL metadata: EXIF and comments.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></td></tr>
				<tr><th><label for="ewww_image_optimizer_png_lossy"><?php _e('Lossy PNG optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></label></th><td><input type="checkbox" id="ewww_image_optimizer_png_lossy" name="ewww_image_optimizer_png_lossy" value="true" <?php if (ewww_image_optimizer_get_option('ewww_image_optimizer_png_lossy') == TRUE) { ?>checked="true"<?php } ?> /> <b><?php _e('WARNING:', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></b> <?php _e('While most users will not notice a difference in image quality, lossy means there IS a loss in image quality.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></td></tr>
			</table>
			<h3><?php _e('Conversion Settings', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></h3>
			<p><?php _e('Conversion is only available for images in the Media Library. By default, all images have a link available in the Media Library for one-time conversion. Turning on individual conversion operations below will enable conversion filters any time an image is uploaded or modified.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?><br />
				<b><?php _e('NOTE:', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></b> <?php _e('The plugin will attempt to update image locations for any posts that contain the images. You may still need to manually update locations/urls for converted images.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?> 
			</p>
			<table class="form-table">
				<tr><th><label for="ewww_image_optimizer_disable_convert_links"><?php _e('Hide Conversion Links', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></label</th><td><input type="checkbox" id="ewww_image_optimizer_disable_convert_links" name="ewww_image_optimizer_disable_convert_links" <?php if (ewww_image_optimizer_get_option('ewww_image_optimizer_disable_convert_links') == TRUE) { ?>checked="true"<?php } ?> /> <?php _e('Site or Network admins can use this to prevent other users from using the conversion links in the Media Library which bypass the settings below.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></td></tr>
				<tr><th><label for="ewww_image_optimizer_delete_originals"><?php _e('Delete originals', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></label></th><td><input type="checkbox" id="ewww_image_optimizer_delete_originals" name="ewww_image_optimizer_delete_originals" <?php if (ewww_image_optimizer_get_option('ewww_image_optimizer_delete_originals') == TRUE) { ?>checked="true"<?php } ?> /> <?php _e('This will remove the original image from the server after a successful conversion.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></td></tr>
				<tr><th><label for="ewww_image_optimizer_jpg_to_png"><?php printf(__('enable %s to %s conversion', EWWW_IMAGE_OPTIMIZER_DOMAIN), 'JPG', 'PNG'); ?></label></th><td><span><input type="checkbox" id="ewww_image_optimizer_jpg_to_png" name="ewww_image_optimizer_jpg_to_png" <?php if (ewww_image_optimizer_get_option('ewww_image_optimizer_jpg_to_png') == TRUE) { ?>checked="true"<?php } ?> /> <b><?php _e('WARNING:', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></b> <?php _e('Removes metadata and increases cpu usage dramatically.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></span>
				<p class="description"><?php _e('PNG is generally much better than JPG for logos and other images with a limited range of colors. Checking this option will slow down JPG processing significantly, and you may want to enable it only temporarily.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></p></td></tr>
				<tr><th><label for="ewww_image_optimizer_png_to_jpg"><?php printf(__('enable %s to %s conversion', EWWW_IMAGE_OPTIMIZER_DOMAIN), 'PNG', 'JPG'); ?></label></th><td><span><input type="checkbox" id="ewww_image_optimizer_png_to_jpg" name="ewww_image_optimizer_png_to_jpg" <?php if (ewww_image_optimizer_get_option('ewww_image_optimizer_png_to_jpg') == TRUE) { ?>checked="true"<?php } ?> /> <b><?php _e('WARNING:', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></b> <?php _e('This is not a lossless conversion.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></span>
				<p class="description"><?php _e('JPG is generally much better than PNG for photographic use because it compresses the image and discards data. PNGs with transparency are not converted by default.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></p>
				<span><label for="ewww_image_optimizer_jpg_background"> <?php _e('JPG background color:', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></label> #<input type="text" id="ewww_image_optimizer_jpg_background" name="ewww_image_optimizer_jpg_background" size="6" value="<?php echo ewww_image_optimizer_jpg_background(); ?>" /> <span style="padding-left: 12px; font-size: 12px; border: solid 1px #555555; background-color: #<? echo ewww_image_optimizer_jpg_background(); ?>">&nbsp;</span> <?php _e('HEX format (#123def)', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?>.</span>
				<p class="description"><?php _e('Background color is used only if the PNG has transparency. Leave this value blank to skip PNGs with transparency.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></p>
				<span><label for="ewww_image_optimizer_jpg_quality"><?php _e('JPG quality level:', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></label> <input type="text" id="ewww_image_optimizer_jpg_quality" name="ewww_image_optimizer_jpg_quality" class="small-text" value="<?php echo ewww_image_optimizer_jpg_quality(); ?>" /> <?php _e('Valid values are 1-100.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></span>
				<p class="description"><?php _e('If JPG quality is blank, the plugin will attempt to set the optimal quality level or default to 92. Remember, this is a lossy conversion, so you are losing pixels, and it is not recommended to actually set the level here unless you want noticable loss of image quality.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></p></td></tr>
				<tr><th><label for="ewww_image_optimizer_gif_to_png"><?php printf(__('enable %s to %s conversion', EWWW_IMAGE_OPTIMIZER_DOMAIN), 'GIF', 'PNG'); ?></label></th><td><span><input type="checkbox" id="ewww_image_optimizer_gif_to_png" name="ewww_image_optimizer_gif_to_png" <?php if (ewww_image_optimizer_get_option('ewww_image_optimizer_gif_to_png') == TRUE) { ?>checked="true"<?php } ?> /> <?php _e('No warnings here, just do it.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></span>
				<p class="description"> <?php _e('PNG is generally better than GIF, but animated images cannot be converted.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></p></td></tr>
			</table>
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?>" /></p>
		</form></div><!-- end container left -->
		<div id="ewww-container-right" style="border: 1px solid #ccc; padding: 0 8px; border-radius: 12px; float: right; margin-left: -200px; display: inline-block; width: 174px;"> 
			<h3>Support EWWW I.O.</h3> 
			<p>Would you like to help support development of this plugin?<br />
			<p>Contribute directly by <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=MKMQKCBFFG3WW">donating with Paypal</a>.<br />
			<b>OR</b><br />
			Use any of these referral links to show your appreciation:</p> 
			<p><b>Web Hosting:</b><br> 
				<a href="http://www.dreamhost.com/r.cgi?132143">Dreamhost</a><br> 
				<a href="http://www.bluehost.com/track/nosilver4u">Bluehost</a><br> 
				<a href="http://www.liquidweb.com/?RID=nosilver4u">liquidweb</a><br> 
				<a href="http://www.stormondemand.com/?RID=nosilver4u">Storm on Demand</a> 
			</p> 
			<p><b>VPS:</b><br>
				<a href="http://www.bluehost.com/track/nosilver4u?page=/vps">Bluehost</a><br> 
				<a href="https://www.digitalocean.com/?refcode=89ef0197ec7e">DigitalOcean</a><br> 
				<a href="https://clientarea.ramnode.com/aff.php?aff=1469">RamNode</a> 
			</p> 
			<p><b>CDN Networks:</b><br>Add the MaxCDN content delivery network to increase website speeds dramatically! <a target="_blank" href="http://tracking.maxcdn.com/c/91625/36539/378">Sign Up Now and Save 25%</a> (100% Money Back Guarantee for 30 days). Integrate it within Wordpress using the W3 Total Cache plugin.</p> 
		</div>
	</div>
	<?php
}

