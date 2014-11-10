=== CategoryTinymce ===
Contributors: ypraise
Donate link: http://wp.ypraise.com/
Tags: category description, wp_editor
Requires at least: 3.3
Tested up to: 3.8.2
Stable tag: 3.5.1
Version: 3.5.1

Provides the ability to add a fully functional tinymce editor to the category and tag editor to style up the introductory information for category archives.

== Description ==

Support for this plugin has now ended. When you update to Wordpress 3.9 you will lose the editor for the bottom description. I have no time or inclination to continue updated this plugin due to the number of Wordpress updates there are each year.

The latest fully supported version of this plugin - compatible with Wordpress 3.9 - is available from http://wp.ypraise.com/


This plugin needs at least Wordpress 3.3 to work as it uses the new wp_editor call introduced in WP 3.3.

The CategoryTinymce plugin replaces the current category description box with one that has a fully active tinymce editor. 

By adding html formatted text in the category description you can spoil the look of the category admin page so this plugin also removes the description column from the admin page to keep it looking nice and manageable.

The plugin has now been extended to include the tag description and admin screens.

There are no setting to configure just upload and  activate.

You can now add a second category description at the bottom of the category listing. You need to add the following code to the category template file to get the description to display:
`
<div class="botdesc">
<?php
if ( is_category() ) {
 
$cat_id = get_query_var('cat');
$cat_data = get_option("category_$cat_id");
if (isset($cat_data['bottomdescription'])){
echo do_shortcode($cat_data['bottomdescription']);
}
                    } 

?>
</div>
`

you can also set a category image and this is called in your template by:
`
<div class="category_image">
<?php
$cat_id = get_query_var('cat');
$cat_data = get_option("category_$cat_id");
if (isset($cat_data['img'])){
echo '<img src="'.$cat_data['img'].'">';
}
?>
</div>
`
The new options are based on code blogged by Ohad Raz at http://en.bainternet.info/

You can add a bottom description to your tag pages using the following code in your template:

`
<div class="bottagdesc">
<?php
if ( is_tag() ) {
$tag_data = get_option("tag_$tag_id");
if (isset($tag_data['bottomdescription'])){
echo do_shortcode($tag_data['bottomdescription']);
}
                    } 

?>
</div>
`

You can call the tag image in your template by using:

`
<div class="tag_image">
<?php
$tag_data = get_option("tag_$tag_id");
if (isset($tag_data['img'])){
echo '<img src="'.$tag_data['img'].'">';
}
?>
</div>
`




== Installation ==

1. Upload CategoryTinymce folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to your category edit pages and start to style them up.


== Frequently Asked Questions ==

= What exactly does this plugin do to my Wordpress installation =

1. The plugin removes the default category description field.
2. It then adds in a new field that is fully tinymce enabled. I did try and just add an editor to the default field but I could not get it to function correctly. The new field saves to the same database section as the default field so no new database tables or fields are added.
3. The plugin runs a filter on the category edit admin pages to remove the default description field as this broke up the admin table and made it unweildly.


= The plugin does not work =

The plugin in was tested on a clean install of wordpress 3.3 and a child theme of 2010. If the plugin does not work then raise a topic for this plugin and tell me: what version of wordpress you are using, what theme are you using, do you have problems with any other tinymce call in your theme.

= What does the future hold for CategoryTinymce =

There's a bit of tweaking needs doing but the main feature next is to only display the category description on the first page of the archives so it is not repeated when you go to the next page in the archive. In the mean time you can use the following code to deal with the issue.

In category.php of your theme folder add:
`
					if (is_category() && $paged < 2) {
		echo '
		<p>'.category_description().'</p>';
	} 
	`
	just before the get template part.
	
In tag.php of your theme folder add:

`
if (is_tag() && $paged < 2) {
		echo '
		<p>'.tag_description().'</p>';
	} 
`
just before the get template part.

= How to display the description at the bottom of the category listings  =

Add this to your template
`
<div class="botdesc">
<?php
if ( is_category() ) {
 
$cat_id = get_query_var('cat');
$cat_data = get_option("category_$cat_id");
if (isset($cat_data['bottomdescription'])){
echo do_shortcode($cat_data['bottomdescription']);
}
                    } 

?>
</div>
`
= How to display the bottom desciption of the tag listings =

Add this to your template file:

`
<div class="bottagdesc">
<?php
if ( is_tag() ) {
$tag_data = get_option("tag_$tag_id");
if (isset($tag_data['bottomdescription'])){
echo do_shortcode($tag_data['bottomdescription']);
}
                    } 

?>
</div>
`
= How do I use this in Woocommerce? =

Woocommerce integration:
The top category description is called as normal. To add the bottom description to a product archive add the following to the archive-product.php file. I would strongly recommend you add the file in the child folder in your theme file rather than directly changing the file in the woocommerce plugin file.
Read how to do this in woocommerce if you don't already know. Also remember that if you use a none woocommerce theme and are using the woocommerce.php option in your theme folder then this is the file you need to edit as product-archive.php file does not work.
I add the code after the 	

`do_action( 'woocommerce_after_shop_loop' );
			?>`
The code to call bottom description in woocommerce: 

`
				<div class="bottagdesc">
<?php
if ( is_product_category()) {
global $wp_query;
        $q_obj = $wp_query->get_queried_object();
        $cat_id = $q_obj->term_id;


$tag_data = get_option("tag_$cat_id");
if (isset($tag_data['bottomdescription'])){
echo do_shortcode($tag_data['bottomdescription']);
         } 
}

?>
</div>	
`


= There's no styling for the bottom description  =

Lots of themes will have different style for the category description style at the top. I don't know what your theme uses so the bottom description is enclosed in a div calls called botdesc

Bottom desciption for tags has a div of bottagdesc

You can either write up your own styles for the div class or find out what your theme is using to style the top category description and then simply add botdesc to include the bottom description.

eg if your theme uses #header for the top description you add botdesc to the style.css as:

#header, botdesc h1{blah blah blah}


= How do I add the category image to my template? =

add this to your theme file
`
<div class="category_image">
<?php
$cat_id = get_query_var('cat');
$cat_data = get_option("category_$cat_id");
if (isset($cat_data['img'])){
echo '<img src="'.$cat_data['img'].'">';
}
?>
</div>
`

= How do I add the tag image to my template? =

add this to your template:

`
<div class="tag_image">
<?php

$tag_data = get_option("tag_$tag_id");
if (isset($tag_data['img'])){
echo '<img src="'.$tag_data['img'].'">';
}
?>
</div>
`

= The SEO parts do not work? =
If you have an SEO plugin already installed then that may override the SEO aspects of this plugin. For All in One SEO pack you can disable the overwriting by putting in the name of the category in the exclude pages of the settings. Take care and read the All in One instructions on using this feature so you know what you are disabling.

= I don't want SEO as I use my own plugin? =
Go to the settings page and switch off the SEO. This will not remove the boxes in the admin pages but will stop the hook from firing and adding the meta tags to the head.


== Screenshots ==

1. A new tinymce enabled category description box is added to the category edit screen.
2. The category description box and column are removed form the admin page to keep it looking nice.


== Changelog ==

= 3.5.1 =
* Notification of CategoryTinymce 4.0 at http://wp.ypraise.com/

= 3.5 = 
* Notification of ending of support for this plugin due to latest update of Wordpress to 3.9

= 3.4 =
* Change readme file to up date FAQ for woocommerce - I'd been calling the wrong id.

= 3.3 =
* Change read me to show how to add bottom description to a woocommerce product category.

= 3.2 = 
* Added settings page so people who do not want SEO can switch it off.

= 3.1 =
* Combined tag and category hooks to stop conflict on title re-writes

= 3.0 =
* Tags now have bottom description, taq image and SEO meta abilities.

= 2.4 =
* Fixed issue of losing titles on single pages

= 2.3 =
* added seo meta options for categories

= 2.2 =
* added wp stripslashes_deep function call to deal with some reports of auto escaping slashes causing problems with shortcode use

= 2.1 =
* Removed BOM from php file
* Removed some rogue code I forget to take out after testing
* Set wpautop to false to try and stop paragraphs and linebreaks being removed
* Adapted call to second description code to allow for shortcodes

= 2.0 =
* add ability to add a description to the bottom of the category listing. Evidently this is useful in ecommerce sites but I guess it can also help to add extra category specific information or advertising.
* add ability to set a category image
* to use both of the above you will need to add code to your template to display the output

= 1.8 =
* Better fix for loss of data which also allows for the saving of multiple paragraphs. I'd miss typed a fix provided by BugTracker earlier.

= 1.7 =
* Added fix to stop description from deleting when saving with multiple empty paragraphs. Multiple empty paragraphs will be deleted on saving still but all the data will not be lost. If you want to increase spacing between paragraphs use css not empty paragraphs. 


= 1.6 =
* added shortcode abilities - thanks to nikosnikos for suggested and code.
* fixed issue with quote marks causing problems with rendering saved descriptions in some cases - thanks to BugTracker for fix.

= 1.5 =
* tackled the parent category option bug and cleaned up some css code - thanks to Brightweb for fixes.

= 1.4 =
* support for custom taxonomies - thanks to Jaime Martinez for adapting the taxonomy call line.

= 1.3 =
* forced a button style css width to correct the one button per row bug in html quicktags.

= 1.2 =
* dealt with the issue that prevented setting parent categories..

= 1.1 =
* extened the plugin to include tags as there's been no issues raised with the basic category description plugin.

= 1.0 =
* The first flavour launched.


== Upgrade Notice ==

= 3.5.1 =
* Notification of CategoryTinymce 4.0 at http://wp.ypraise.com/

= 3.5 = 
* Notification of ending of support for this plugin due to latest update of Wordpress to 3.9

= 3.4 =
* Change readme file to up date FAQ for woocommerce - I'd been calling the wrong id.

= 3.3 =
* Change read me to show how to add bottom description to a woocommerce product category.

= 3.2 =
* Added settings page so people who do ot want SEO can switch it off.

= 3.1 =
* Combined tag and category hooks to stop conflict on title re-writes

= 3.0 =
* Tags now have bottom description, taq image and SEO meta abilities.

= 2.4 =
* Fixed issue of losing titles on single pages

= 2.3 =
* added seo meta options for categories

= 2.2 =
* added wp stripslashes_deep function call to deal with some reports of auto escaping slashes causing problems with shortcode use

= 2.1 =
* Removed BOM from php file
* Removed some rogue code I forget to take out after testing
* Set wpautop to false to try and stop paragraphs and linebreaks being removed
* Adapted call to second description code to allow for shortcodes

= 2.0 =
* add ability to add a description to the bottom of the category listing. Evidently this is useful in ecommerce sites but I guess it can also help to add extra category specific information or advertising.
* add ability to set a category image
* to use both of the above you will need to add code to your template to display the output

= 1.8 =
* Better fix for loss of data which also allows for the saving of multiple paragraphs. I'd miss typed a fix provided by BugTracker earlier.

= 1.7 =
* Added fix to stop description from deleting when saving with multiple empty paragraphs. Multiple empty paragraphs will be deleted on saving still but all the data will not be lost. If you want to increase spacing between paragraphs use css not empty paragraphs. 

= 1.6 =
* added shortcode abilities - thanks to nikosnikos for suggested and code.
* fixed issue with quote marks causing problems with rendering saved descriptions in some cases - thanks to BugTracker for fix.

= 1.5 =
tackled the parent category option bug and cleaned up some css code - thanks to Brightweb for fixes.

= 1.4 =
Support for custom taxonomies.

= 1.3 = 
Corrected a missing css stlye for buttons in html quicktags.

= 1.2 =
Upgrade if you need to be able to set parent categories on the category admin pages.

= 1.1 =
Upgrade if you want to use the plugin on tag descriptions and pages.

= 1.0 =
None