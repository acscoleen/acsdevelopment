<?php
/*
Plugin Name: Nextend Image Magnifier
Plugin URI: http://nextendweb.com
Description: Easy to use plugin for high-res images with magnifying glass 
Author: Roland Soos
Author URI: http://nextendweb.com
Version: 1.0.12
License: GPL2
*/

/*  Copyright 2012  Roland Soos - Nextend  (email : roland@nextendweb.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function _new_im_shortcode_load_files() {
  wp_register_style( 'new-im-style', plugins_url('css/style.css?t='.time(), __FILE__) );
  wp_enqueue_style( 'new-im-style' );
  
  wp_enqueue_script("jquery");
  
  wp_register_script( 'jquery.mousewheel', plugins_url('js/jquery.mousewheel.min.js', __FILE__) );
  wp_enqueue_script( 'jquery.mousewheel' );
  
	wp_enqueue_script('jquery-ui-slider');
  
  wp_register_script( 'jquery.nextend.magny', plugins_url('js/jquery.nextend.magny.js?t='.time(), __FILE__) );
  wp_enqueue_script( 'jquery.nextend.magny' );
}

add_action( 'wp_enqueue_scripts', 'new_im_shortcode_load_files' );
function new_im_shortcode_load_files() {
    // Check if shortcode exists in page or post content
    global $post;
    // I removed the end ' ] '... so it can accept args.
    if ( strpos( $post->post_content, '[magny' ) !== false ) {
      _new_im_shortcode_load_files();
    }
}

function new_im_shortcode($atts){
	static $count = 0;
	$count++;
  if($count == 1) _new_im_shortcode_load_files();
  extract( shortcode_atts( array(
		'image' => '',
		'title' => '',
		'description' => '',
		'align' => 'center',
		'click' => 1,
		'link_url' => '',
		'scroll_zoom' => 0,
		'scroll_size' => 0,
		'small_image' => '',
		'maxwidth' => '500px',
		'zoom' => 1,
		'dia' => '200px',
    'canvas_mode' => '0',
    'skin' => 'new-im-frame-photo,new-title-below,new-description-off,new-slider-below,new-im-magnifier-light'
	), $atts ) );
  require_once(ABSPATH . '/wp-admin/includes/image.php');
  if($small_image != ''){
    $thumb = $small_image;
  }else if(strpos($image, site_url())!== false){
  	require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'freshizer.php';
    $thumb = fImg::resize(str_replace(array(site_url(),'//'),array(ABSPATH,'/'),$image), intval($maxwidth));
  }else{
    $thumb = $image;
  }
  ob_start();
  if($click == 0){
  	echo '<a href="'.$link_url.'">';
  }
  echo '<img src="'.$thumb.'" title="'.$title.'" alt="'.$description.'" data-img="'.$image.'" data-align="'.$align.'" data-click="'.$click.'" data-link_url="'.$link_url.'" data-scroll_zoom="'.$scroll_zoom.'" data-scroll_size="'.$scroll_size.'" data-maxwidth="'.$maxwidth.'" data-zoom="'.$zoom.'" data-dia="'.$dia.'" data-canvas_mode="'.$canvas_mode.'" data-skin="'.$skin.'" class="magny magny'.$count.'" />';
  if($click == 0){
  	echo '</a>';
  }
  ?>
  <script type="text/javascript">
    jQuery(document).ready(function () {
      (function($){
        $('.magny<?php echo $count; ?>').magny();
      })(jQuery);
    });
  </script>
  <?php
  return ob_get_clean();
}

add_shortcode( 'magny', 'new_im_shortcode' );

function new_im_generator(){
  add_menu_page(__('Nextend Image Magnifier'), __('Nextend Image Magnifier'), 'manage_options', 'nextend_im', new_im_global_settings_page, plugin_dir_url( __FILE__ ).'images/menuicon.png' );
  add_submenu_page( 
          'nextend_im'  // set the parent to your first page and it wont appear
        , 'Shortcode generator'
        , 'Shortcode generator'  // unused
        , 'administrator'
        , 'nextend_im_shortcode'
        , 'new_im_shortcode_page'
    );
}

add_action('admin_menu', 'new_im_generator', 1);

function new_im_global_settings_page() {
	$status = "normal";
	if(isset($_POST['new_im_update_options'])) {
		if($_POST['new_im_update_options'] == 'Y') {
	    foreach($_POST AS $k => $v){
	      $_POST[$k] = stripslashes($v);
	    }
			update_option("new_im", maybe_serialize($_POST));
			$status = 'update_success';
		}
	}
	$options = maybe_unserialize(get_option('new_im'));
  wp_register_style( 'new-im-shortcode-generator', plugins_url('css/shortcodegenerator.css', __FILE__) );
  wp_enqueue_style( 'new-im-shortcode-generator' );
	wp_enqueue_style( 'media' );
  wp_enqueue_script("jquery");
  
  wp_enqueue_script('jquery-ui-slider');

  wp_register_script( 'new-im-shortcode-generator', plugins_url('js/shortcodegenerator.js', __FILE__) );
  wp_enqueue_script( 'new-im-shortcode-generator' );
?>
<h1>Nextend Image Magnifier</h1>
<?php
	if($status == 'update_success')
		$message =__('Configuration updated', 'new-im') . "<br />";
	else if($status == 'update_failed')
		$message =__('Error while saving options', 'new-im') . "<br />";
	else
		$message = '';

	if($message != "") {
	?>
		<div class="updated"><strong><p><?php
		echo $message;
		?></p></strong></div><?php
	} ?>
<form method="post" action="<?php echo get_bloginfo("wpurl"); ?>/wp-admin/admin.php?page=nextend_im" class="media-upload-form">
	<input type="hidden" name="new_im_update_options" value="Y">
	<div class="new-im-pages current">
        <h3>Global settings</h3>
        <div class="new-im-frame">
          <table>
            <tbody>
              <tr class="align">
                <th>
                  <span class="alignleft"><label>Image alignment</label></span>
                </th>
								<td class="field">
                	<?php
                	if(!isset($options['new_im_align'])) $options['new_im_align'] = 'center';
                	?>
									<input type="radio" value="none"<?php if($options['new_im_align'] == 'none'){?> checked="checked"<?php }?> id="align-none" name="new_im_align">
									<label class="align image-align-none-label" for="align-none">None</label>
									<input type="radio" value="left"<?php if($options['new_im_align'] == 'left'){?> checked="checked"<?php }?> id="align-left" name="new_im_align">
									<label class="align image-align-left-label" for="align-left">Left</label>
									<input type="radio" value="center"<?php if($options['new_im_align'] == 'center'){?> checked="checked"<?php }?> id="align-center" name="new_im_align">
									<label class="align image-align-center-label" for="align-center">Center</label>
									<input type="radio" value="right"<?php if($options['new_im_align'] == 'right'){?> checked="checked"<?php }?> id="align-right" name="new_im_align">
									<label class="align image-align-right-label" for="align-right">Right</label>
								</td>
              </tr>
              <tr>
                <th>
                  <span class="alignleft"><label for="new_im_click">Click function</label></span>
                </th>
                <td>
                	<?php
                	if(!isset($options['new_im_click'])) $options['new_im_click'] = 0;
                	?>
                  <select name="new_im_click" id="new_im_click">
                    <option value="0" <?php if($options['new_im_click'] == 0){?> selected<?php }?>>Open link</option>
                    <option value="1" <?php if($options['new_im_click'] == 1){?> selected<?php }?>>Show/Hide magnifier</option>
                  </select>
                </td>
              </tr>
              <tr>
                <th>
                  <span class="alignleft"><label>Scroll effect</label></span>
                </th>
                <td>
                	<?php
                	if(!isset($options['new_im_scroll_zoom'])) $options['new_im_scroll_zoom'] = 0;
                	?>
                  <input type="checkbox" name="new_im_scroll_zoom" id="new_im_scroll_zoom" value="1"<?php if($options['new_im_scroll_zoom'] == 1){?> checked<?php }?>> <label for="new_im_scroll_zoom">Zoom</label><br>
                  
                	<?php
                	if(!isset($options['new_im_scroll_size'])) $options['new_im_scroll_size'] = 0;
                	?>
                  <input type="checkbox" name="new_im_scroll_size" id="new_im_scroll_size" value="1"<?php if($options['new_im_scroll_size'] == 1){?> checked<?php }?>> <label for="new_im_scroll_size">Size</label><br>
                </td>
              </tr>
              <tr>
                <th>
                  <span class="alignleft"><label for="new_im_canvas_mode">Chrome Canvas Mode</label></span>
                </th>
                <td>
                  <?php
                	if(!isset($options['new_im_canvas_mode'])) $options['new_im_canvas_mode'] = 0;
                	?>
                  <input type="checkbox" name="new_im_canvas_mode" id="new_im_canvas_mode" value="1"<?php if($options['new_im_canvas_mode'] == 1){?> checked<?php }?> />
                  (Only if you experience problem with normal mode)
                </td>
              </tr>
              <tr>
                <th>
                  <span class="alignleft"><label for="new_im_maxwidth">Maximum image width</label></span>
                </th>
                <td>
                	<?php
                	if(!isset($options['new_im_maxwidth'])) $options['new_im_maxwidth'] = '500px';
                	?>
                  <input value="<?php echo $options['new_im_maxwidth']; ?>" id="new_im_maxwidth" name="new_im_maxwidth" type="text" />
                  <div id="new_im_maxwidth_slider"></div>
                </td>
              </tr>
              <tr>
                <th>
                  <span class="alignleft"><label for="new_im_zoom">Zoom</label></span>
                </th>
                <td>
                	<?php
                	if(!isset($options['new_im_zoom'])) $options['new_im_zoom'] = 1;
                	?>
                  <input value="<?php echo $options['new_im_zoom']; ?>" id="new_im_zoom" name="new_im_zoom" type="text" />
                  <div id="new_im_zoom_slider"></div>
                </td>
              </tr>
              <tr>
                <th>
                  <span class="alignleft"><label for="new_im_size">Magnifier diameter</label></span>
                </th>
                <td>
                	<?php
                	if(!isset($options['new_im_size'])) $options['new_im_size'] = '200px';
                	?>
                  <input value="<?php echo $options['new_im_size']; ?>" id="new_im_size" name="new_im_size" type="text" />
                  <div id="new_im_size_slider"></div>
                </td>
              </tr>
              <tr>
                <th>
                  <span class="alignleft"><label for="new_im_skin">Skin</label></span>
                </th>
                <td>
                	<?php
                	if(!isset($options['new_im_skin'])) $options['new_im_skin'] = 'new-im-frame-photo,new-title-below,new-description-off,new-slider-below,new-im-magnifier-light';
                	?>
                  <textarea style="height:85px; width: 230px; vertical-align: middle; margin-top:4px;" id="new_im_skin" name="new_im_skin" type="text"><?php echo $options['new_im_skin']; ?></textarea>
                  <a href="http://www.nextendweb.com/image-magnifier-skin-generator" target="_blank">
                  	<img style="vertical-align: middle;" src="<?php echo plugins_url('images/banner.png', __FILE__); ?>" />
                  </a>
                </td>
              </tr>
            </tbody>
          </table>
          
					<p class="submit">
					<input style="margin-left: 10%;" type="submit" name="Submit" value="<?php _e('Save Changes', 'new-im'); ?>" />
					</p>
        </div>
      </div>
      
</form>
<?php
}

function new_im_shortcode_page(){
  wp_register_style( 'new-im-shortcode-generator', plugins_url('css/shortcodegenerator.css', __FILE__) );
  wp_enqueue_style( 'new-im-shortcode-generator' );
  wp_enqueue_style( 'thickbox' );
	wp_enqueue_style( 'media' );
	
  wp_enqueue_script("jquery");
  wp_enqueue_script('thickbox');
  
  wp_enqueue_script('jquery-ui-slider');
	
  wp_register_script( 'new-im-shortcode-generator', plugins_url('js/shortcodegenerator.js', __FILE__) );
  wp_enqueue_script( 'new-im-shortcode-generator' );
	
	$options = maybe_unserialize(get_option('new_im'));
  ?>
  <div id="new-im-shortcode-generator-header">
  	<ul class="new-im-sidemenu">
  	 <li id="tab-type"><a class="current" href="#">Basic</a></li>
  	 <li id="tab-type_url"><a href="#">Advanced</a></li>
  	 <li id="tab-library"><a href="#">Skin</a></li>
    </ul>
	</div>
  <form id="new-im-shortcode-generator-form" class="media-upload-form">
    <div id="new-im-shortcode-generator-pages">
    	<div class="current new-im-pages">
        <h3>Choose an image to magnify</h3>
        <div class="new-im-frame">
          <table>
            <tbody>
              <tr>
                <th>
                  <span class="alignleft"><label for="new_im_image">Image URL</label></span>
                </th>
                <td>
                  <input value="" id="new_im_image" name="new_im_image" type="text" /><br>
                  <input id="new_im_image_button" value="Add from media library" data-adminurl="<?php echo admin_url(); ?>" class="button" type="button" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    	<div class="new-im-pages">
        <h3>Advaced settings</h3>
        <div class="new-im-frame">
          <table>
            <tbody>
              <tr>
                <th>
                  <span class="alignleft"><label for="new_im_title">Title</label></span>
                </th>
                <td>
                  <input value="" id="new_im_title" name="new_im_title" type="text" />
                </td>
              </tr>
              <tr>
                <th>
                  <span class="alignleft"><label for="new_im_description">Description</label></span>
                </th>
                <td>
                  <textarea id="new_im_description" name="new_im_description" type="text"></textarea>
                </td>
              </tr>
              <tr class="align">
                <th>
                  <span class="alignleft"><label>Image alignment</label></span>
                </th>
								<td class="field">
                	<?php
                	if(!isset($options['new_im_align'])) $options['new_im_align'] = 'center';
                	?>
									<input type="radio" value="none"<?php if($options['new_im_align'] == 'none'){?> checked="checked"<?php }?> id="align-none" name="new_im_align">
									<label class="align image-align-none-label" for="align-none">None</label>
									<input type="radio" value="left"<?php if($options['new_im_align'] == 'left'){?> checked="checked"<?php }?> id="align-left" name="new_im_align">
									<label class="align image-align-left-label" for="align-left">Left</label>
									<input type="radio" value="center"<?php if($options['new_im_align'] == 'center'){?> checked="checked"<?php }?> id="align-center" name="new_im_align">
									<label class="align image-align-center-label" for="align-center">Center</label>
									<input type="radio" value="right"<?php if($options['new_im_align'] == 'right'){?> checked="checked"<?php }?> id="align-right" name="new_im_align">
									<label class="align image-align-right-label" for="align-right">Right</label>
								</td>
              </tr>
              <tr>
                <th>
                  <span class="alignleft"><label for="new_im_click">Click function</label></span>
                </th>
                <td>
                	<?php
                	if(!isset($options['new_im_click'])) $options['new_im_click'] = 0;
                	?>
                  <select name="new_im_click" id="new_im_click">
                    <option value="0" <?php if($options['new_im_click'] == 0){?> selected<?php }?>>Open link</option>
                    <option value="1" <?php if($options['new_im_click'] == 1){?> selected<?php }?>>Show/Hide magnifier</option>
                  </select>
                </td>
              </tr>
              <tr class="new_im_link_url_tr">
                <th>
                  <span class="alignleft"><label for="new_im_link_url">Link URL</label></span>
                </th>
                <td>
                  <input value="" id="new_im_link_url" name="new_im_link_url" type="text" />
                </td>
              </tr>
              <tr>
                <th>
                  <span class="alignleft"><label>Scroll effect</label></span>
                </th>
                <td>
                	<?php
                	if(!isset($options['new_im_scroll_zoom'])) $options['new_im_scroll_zoom'] = 0;
                	?>
                  <input type="checkbox" name="new_im_scroll_zoom" id="new_im_scroll_zoom" value="1"<?php if($options['new_im_scroll_zoom'] == 1){?> checked<?php }?> /> <label for="new_im_scroll_zoom">Zoom</label><br>
                  
                	<?php
                	if(!isset($options['new_im_scroll_size'])) $options['new_im_scroll_size'] = 0;
                	?>
                  <input type="checkbox" name="new_im_scroll_size" id="new_im_scroll_size" value="1"<?php if($options['new_im_scroll_size'] == 1){?> checked<?php }?> /> <label for="new_im_scroll_size">Size</label><br>
                </td>
              </tr>
              <tr>
                <th>&nbsp;</th>
                <td></td>
              </tr>
              <tr>
                <th>
                  <span class="alignleft"><label for="new_im_small_image">Thumbnail image URL (Optional)</label></span>
                </th>
                <td>
                  <input value="" id="new_im_small_image" name="new_im_small_image" type="text" /><br>
                  <input id="new_im_small_image_button" value="Add from media library" data-adminurl="<?php echo admin_url(); ?>" class="button" type="button" />
                  The plugin will generate the thumbnail if you leave it blank.
                </td>
              </tr>
              <tr>
                <th>
                  <span class="alignleft"><label for="new_im_canvas_mode">Chrome Canvas Mode</label></span>
                </th>
                <td>
                  <?php
                	if(!isset($options['new_im_canvas_mode'])) $options['new_im_canvas_mode'] = 0;
                	?>
                  <input type="checkbox" name="new_im_canvas_mode" id="new_im_canvas_mode" value="1"<?php if($options['new_im_canvas_mode'] == 1){?> checked<?php }?> />
                  (Only if you experience problem with normal mode)
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    	<div class="new-im-pages">
        <h3>Skin parameters</h3>
        <div class="new-im-frame">
          <table>
            <tbody>
              <tr>
                <th>
                  <span class="alignleft"><label for="new_im_maxwidth">Maximum image width</label></span>
                </th>
                <td>
                	<?php
                	if(!isset($options['new_im_maxwidth'])) $options['new_im_maxwidth'] = '500px';
                	?>
                  <input value="<?php echo $options['new_im_maxwidth']; ?>" id="new_im_maxwidth" name="new_im_maxwidth" type="text" />
                  <div id="new_im_maxwidth_slider"></div>
                </td>
              </tr>
              <tr>
                <th>
                  <span class="alignleft"><label for="new_im_zoom">Zoom</label></span>
                </th>
                <td>
                	<?php
                	if(!isset($options['new_im_zoom'])) $options['new_im_zoom'] = 1;
                	?>
                  <input value="<?php echo $options['new_im_zoom']; ?>" id="new_im_zoom" name="new_im_zoom" type="text" />
                  <div id="new_im_zoom_slider"></div>
                </td>
              </tr>
              <tr>
                <th>
                  <span class="alignleft"><label for="new_im_size">Magnifier diameter</label></span>
                </th>
                <td>
                	<?php
                	if(!isset($options['new_im_size'])) $options['new_im_size'] = '200px';
                	?>
                  <input value="<?php echo $options['new_im_size']; ?>" id="new_im_size" name="new_im_size" type="text" />
                  <div id="new_im_size_slider"></div>
                </td>
              </tr>
              <tr>
                <th>
                  <span class="alignleft"><label for="new_im_skin">Skin</label></span>
                </th>
                <td>
                	<?php
                	if(!isset($options['new_im_skin'])) $options['new_im_skin'] = 'new-im-frame-photo,new-title-below,new-description-off,new-slider-below,new-im-magnifier-light';
                	?>
                  <textarea style="height:85px;" id="new_im_skin" name="new_im_skin" type="text"><?php echo $options['new_im_skin']; ?></textarea>
                  <a href="http://www.nextendweb.com/image-magnifier-skin-generator" target="_blank">
                  	<img style="vertical-align: middle;margin-top:10px;" src="<?php echo plugins_url('images/banner.png', __FILE__); ?>" />
                  </a>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
  	</div>
    <?php if(defined('NEW_IM_TINYMCE')){ ?>
    <div class="new-im-shortcode">
      <input id="insert_shortcode" value="Insert shortcode" class="button" type="button" />
    </div>
    <?php }else{ ?>
    <div class="new-im-shortcode">
      <input id="generate_shortcode" value="Generate shortcode" class="button" type="button" />
      <h3>Short code</h3>
      <div id="new-im-shortcode-frame">
        
      </div>
    </div>
    <?php } ?>
  </form>
  <?php
}

add_action('wp_ajax_load_generator', 'new_im_load_generator');

function new_im_load_generator(){
  define('NEW_IM_TINYMCE',1);
  include( dirname(__FILE__) . '/admin-header.php' );
  new_im_shortcode_page();
  include( dirname(__FILE__) . '/admin-footer.php' );
  exit;
}

add_action('media_buttons', 'new_im_media_buttons',11);

function new_im_media_buttons($editor_id){
	echo '<a id="nextend_im_magnifier_btn_link" title="Add Nextend Image Magnifier" href="'.admin_url('admin-ajax.php?action=load_generator&editor='.$editor_id.'&tinymce=0&width=740&height=600&TB_iframe=1').'" class="thickbox" onclick="return false;">';
	echo '<img src="'.plugin_dir_url( __FILE__ ).'images/menuicon.png" />';
	echo '</a>';
	?>
	<script type="text/javascript">
		var fireFN = function(){
		  var event;
		  var element = document.getElementById('nextend_im_magnifier_btn_link');
		  if (document.createEvent) {
		    event = document.createEvent("HTMLEvents");
		    event.initEvent("click", true, true);
		  } else {
		    event = document.createEventObject();
		    event.eventType = "click";
		  }
		  event.eventName = 'click';
		  if (document.createEvent) {
		    element.dispatchEvent(event);
		  } else {
		    element.fireEvent("on" + event.eventType, event);
		  }
		};
		jQuery(document).ready(function () {
			if(QTags){
				QTags.addButton( 'nextend_im_magnifier_btn', 'Image magnifier', fireFN);
			}else if(edButton){
				edButton('nextend_im_magnifier_btn', 'Image magnifier', fireFN);
			}
		});
	</script>
	<?php
}

 
/*
  TinyMCE hooks
*/

function new_im_tinymce_addbuttons(){
	if (!current_user_can('edit_posts') && !current_user_can('edit_pages'))
		return;

	if ( get_user_option('rich_editing') == 'true') {
		add_filter("mce_external_plugins", "new_im_tinymce_add_plugin");
		add_filter('mce_buttons', 'new_im_tinymce_register_button');
	}
}

function new_im_tinymce_register_button($buttons){
  array_push($buttons, "|", "nextendimagemagnifier");
	return $buttons;
}

function new_im_tinymce_add_plugin($plugin_array){
  $plugin_array['nextendimagemagnifier'] = plugins_url('/js/new_im_tinymce.js', __FILE__);
	return $plugin_array;
}

add_action('init', 'new_im_tinymce_addbuttons');