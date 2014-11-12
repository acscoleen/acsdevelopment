
<div class="wrap">

<?php
if (! isset ($_POST['submit_categories']) && !isset($_POST['submit_products']) && !isset($_POST['wcs_sorting'])) {
	$args = array('hide_empty'=>0, orderby=>'ASC');
	$product_cats = get_terms('product_cat',$args);
	if (is_wp_error ($product_cats)) {
		die ("Seems woo-commerce is not isntalled, either you have an error.");
	} else if (count ($product_cats) <1) {
		die ("Kindly add some categories.");
	}else { ?>
    	<div id="icon-link-manager" class="icon32"></div>
        <h2>1/3 Select Categories </h2>
        <form method="post" action="" onsubmit="return validate_cats();">
            <?php	
                if($product_cats){
                    $i=1;
                  foreach($product_cats as $catTerms){
            ?>
            <input type="checkbox" name="categories[]" class="cats_selection" id="<?php echo $i; ?>" value="<?php echo $catTerms->name."+-+".$catTerms->slug; ?>"/> <?php echo $catTerms->name; ?><br/><br/>
            <?php		
              $i++;
              } //foreach ends
              ?>
              <input type="submit" name="submit_categories" class="button-primary" value="Next>> Add Products" />
            <?php
            }//if $product_cats ends
            ?>
        </form>

<?php
	}}
///////////////////////////////////////////////////////////////////////
else if(isset($_POST['submit_categories'])){
$categories = $_POST['categories'];
?>
<h2>2/3 Select Products</h2>
<form method="post" action="" onsubmit="return validate_product();">
<?php
$sendCats = array();
foreach($categories as $cat){
$divide = explode("+-+",$cat);
$catName = $divide[0];
$catSlug = $divide[1];
$product_args = array('post_type'=>'product', 'posts_per_page'=>1000,'product_cat'=>$catSlug);
$loop = new WP_Query( $product_args );
echo "<br/>";
?>
<div><span style="font-size:16px; font-weight:bold"><?php echo $catName; ?></span>
</div>
<?php
while ( $loop->have_posts() ) : $loop->the_post(); 

		global $product;
		$postid = get_the_ID();
		$post_name = get_the_title();
?>
<input type="checkbox" class="<?php echo $catSlug; ?> product_selection" name="products[<?php echo $catSlug; ?>][]" value="<?php echo $post_name."+-+".$postid; ?>" /><?php echo $post_name; ?><br/>
<?php
endwhile; 

?>
<div style="margin:7px; width: 95px;padding:5px; background: #ccc;"><input type="checkbox" name="select_all" class="select_all" id="<?php echo $catSlug; ?>" />
<label for="<?php echo $catSlug; ?>" id="lbl_<?php echo $catSlug; ?>"> Check All</label></div>
<input type="hidden" name="cats[]" value="<?php echo $cat; ?>" />
<?php
}//foreach ends fore cats
wp_reset_query();
?><br/><br/>
<input type="submit" name="submit_products" class="button-primary" value="Next>> Do the Sorting" />

</form>
<?php
}//if isset $_POST['submit_categories'] ends

//////////////////////////////////////////////////////////////////////////////////////////
//if isset $_POST['submit_products'] starts

else if(isset($_POST['submit_products'])){
	
	$productsIds 	= $_POST['products'];
	$cats 			= $_POST['cats'];
	?> <h2>3/3 Sort and Save selection </h2>
    <form name="selection_form" id="selection_form">
    <div style="width:80%; margin:0 auto; margin-bottom:10px;"><input type="text" placeholder="Selection name" name="selection_name" value="" id="selection_name" /><input class='button-primary' type='button' id="save_selection" name='Save' value='<?php _e('Save Selection'); ?>'/>
    </div>
    <ul id="sortable"> <?php
	foreach($productsIds as $key=>$value){
		
		?> <li> <h1><?php echo ucfirst($key); ?></h1><div class="container"> <?php
		
		foreach($value as $val) {
			$seperate_products_posts_id = explode("+-+",$val);
			
			$product_name = $seperate_products_posts_id[0];
			$post_id = $seperate_products_posts_id[1];
		
			$args = array( 'post_type' => 'product', 'posts_per_page' => 1000, 'product_cat' => $catSlug,'post__in' => array($post_id) );

			$loop = new WP_Query( $args );

			while ( $loop->have_posts() ) : $loop->the_post(); 

				global $product; 

				echo '<div id="prod_'.get_the_ID().'">
			
				<div class="wcs_prod_cont">' . woocommerce_get_product_thumbnail('thumbnail',75,75).'</div>

				<p>'.substr(get_the_title(),0,14).'... <a href="javaScript:void(0);" style="color:red" dataid="'.get_the_ID().'" class="delete_prod">x</a></p><input type="hidden" name="products['.$key.'][]" value="'.get_the_ID().'" >

				</div>';

			endwhile; 

			

    	wp_reset_query(); 

?>

	 
<?php } ?>
</div>
</li>



<?php
	} ?> </ul></form><?php }
//if isset $_POST['submit_products'] ends
?>


</div>

<script>



jQuery( document ).ready(function() {
	
	jQuery('.select_all').change(function(){
		var cat_id = jQuery(this).attr("id");
		jQuery('.'+cat_id).prop('checked', jQuery(this).is(":checked"));
		if (jQuery(this).is(":checked")) {
			jQuery('#lbl_'+cat_id).text('Uncheck All');
		} else {
			jQuery('#lbl_'+cat_id).text('Check All');
		}
		
	});
	jQuery('.delete_prod').click(function(){
		if(confirm ("Are you sure want to delete this product?")) {
			var prod_id = jQuery(this).attr("dataid");
			jQuery('#prod_'+prod_id).remove();
		}
		
	});
	
	jQuery(function() {
		
		jQuery( "#sortable" ).sortable();
    	jQuery( "#sortable" ).disableSelection();
	});

	jQuery(".container").shapeshift({
    	minColumns: 3
	});
	
	jQuery( "#save_selection" ).click(function() {
		jQuery('#save_selection').val('Saving...');
  		var fdata 	= jQuery( "#selection_form" ).serialize();
		var name 	= jQuery( "#selection_name" ).val();
		if(name == "") { alert ("Kindly enter some name for this selection"); return false; }
		var data 	= {
			action	: 'wcs_save_selection',
			fdata	: fdata,
			name	: name
		};
		
		jQuery.post(ajaxurl, data, function(response) {
			jQuery('#save_selection').val('Save Selection');
			alert(response);
			window.location = "admin.php?page=acs-view-all";
		});
	});

});

function validate_cats() {
	if ( jQuery(".cats_selection:checked").length > 0 ) {
		return true;
	} else {
		alert("Please select at least one category");
		return false;
	}
	
}
function validate_product(){
	if ( jQuery(".product_selection:checked").length > 0 ) {
		return true;
	} else {
		alert("Please select at least one product");
		return false;
	}
}
</script>



