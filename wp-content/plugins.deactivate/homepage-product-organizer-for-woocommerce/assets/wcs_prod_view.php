
<div class="wrap">
<?php if( !isset($_GET['action']) ) { ?>
<h2>View Selections </h2>
<table class="widefat" id="wcs_selections">
<thead>
    <tr>
        <th>ID</th>
        <th>Name</th>       
        <th>Short Code</th>
        <th>Actions</th>
    </tr>
</thead>
<tfoot>
    <tr>
    <th>ID</th>
    <th>Name</th>
    <th>Short Code</th>
    <th>Actions</th>
    </tr>
</tfoot>
<tbody>
<?php
global $wpdb;
$db_table_name 		= $wpdb->prefix . 'wcs_selection_data';
$selections			= $wpdb->get_results("SELECT * FROM $db_table_name", ARRAY_A);
foreach ($selections as $selection) {
	
	?>
    <tr id="row_<?php echo $selection['id']; ?>">
     <td><?php echo $selection['id']; ?></td>
     <td><?php echo $selection['name']; ?></td>
     <td><?php echo "[wcs selection=\"".$selection['id']."\"]"; ?></td>
     <td><a href="admin.php?page=acs-view-all&action=edit&selection=<?php echo $selection['id']; ?>">Edit</a> | <a href="JavaScript:void(0)" class="delete_selection" dataid="<?php echo $selection['id']; ?>"> Delete </a></td>
   </tr>
<?php } ?>
</tbody>
</table>

<?php
}
else if(isset($_GET['action'])){
	$selection 			= $_GET['selection'];
	global $wpdb;
	$db_table_name 		= $wpdb->prefix . 'wcs_selection_data';
	$selectionData		= $wpdb->get_results("SELECT * FROM $db_table_name WHERE id=$selection", ARRAY_A);
	
	$selectionUnData	= unserialize ( $selectionData[0]['data'] );
	
	$productsIds 		= $selectionUnData['products'];
	
	?> <h2> Update Selection </h2>
    <form name="selection_form" id="selection_form">
    <input type="hidden" name="selection_id" id="selection_id" value="<?php echo $selectionData[0]['id'];?>" />
    <div style="width:80%; margin:0 auto; margin-bottom:10px;"><input type="text" name="selection_name" value="<?php echo $selectionData[0]['name'];?>" id="selection_name" /><input class='button-primary' type='button' id="update_selection" name='Update' value='<?php _e('Update Selection'); ?>'/>
    </div>
    <ul id="sortable"> <?php
	foreach($productsIds as $key=>$value){
		
		?> <li> <h1><?php echo ucfirst($key); ?></h1><div class="container"> <?php
		
		foreach($value as $val) {
			$post_id = $val;
		
			$args = array( 'post_type' => 'product','post__in' => array($post_id) );

			$loop = new WP_Query( $args );

			while ( $loop->have_posts() ) : $loop->the_post(); 

				global $product; 

				echo '<div id="prod_'.get_the_ID().'">

				' . woocommerce_get_product_thumbnail('thumbnail',75,75).'

				<p>'.get_the_title().'<a href="javaScript:void(0);" style="color:red" dataid="'.get_the_ID().'" class="delete_prod">x</a></p><input type="hidden" name="products['.$key.'][]" value="'.get_the_ID().'" >

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
	
	jQuery('.delete_prod').click(function(){
		if(confirm ("Are you sure want to delete this product?")) {
			var prod_id = jQuery(this).attr("dataid");
			jQuery('#prod_'+prod_id).remove();
		}
		
	});
	
	jQuery('.delete_selection').click(function(){
		if(confirm ("Are you sure you want to delete this selection?")) {
			var sId		= jQuery( this ).attr("dataid");
			
			var data 	= {
				action	: 'wcs_delete_selection',
				id		: sId
			};
			
			jQuery.post(ajaxurl, data, function(response) {
				if( response == 1 ) {
					jQuery('#row_'+sId).remove();
					jQuery('#wcs_selections').DataTable();
				} else {
					alert("Unable to delete due to some error.");
				}
			});
		}
		
	});
	var table = jQuery('#wcs_selections').DataTable();
	jQuery(function() {
		
		jQuery( "#sortable" ).sortable();
    	jQuery( "#sortable" ).disableSelection();
	});

	jQuery(".container").shapeshift({
    	minColumns: 3
	});
	
	jQuery( "#update_selection" ).click(function() {
  		var fdata 	= jQuery( "#selection_form" ).serialize();
		var name 	= jQuery( "#selection_name" ).val();
		var sId		= jQuery( "#selection_id" ).val();
		if(name == "") { alert ("Kindly enter some name for this selection"); return false; }
		var data 	= {
			action	: 'wcs_update_selection',
			fdata	: fdata,
			name	: name,
			id		: sId
		};
		
		jQuery.post(ajaxurl, data, function(response) {
			alert(response);
			if(response == "Selection have been Updated") {
				window.location = "admin.php?page=acs-view-all";
			}
		});
	});

});


</script>



