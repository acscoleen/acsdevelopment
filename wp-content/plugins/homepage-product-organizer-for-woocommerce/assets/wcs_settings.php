
<div class="wrap">
<h2>Add Style </h2>
<?php if(isset ($_POST['Save']) && count($_POST) > 0 ) {
	if( update_option('wcs_style_options', $_POST['wcs_style']) ) {
		echo "<span style='color:#088A08'>Data have been added/updated successfully.</span>";
	}
	
}?>
<form method="post" action="">
<table>
<tr>
<td valign="top">CSS <span style="font-style:italic; color:#333; font-size:12px">add css without &lt;style&gt; &lt;/style&gt; tags</span></td></tr>
<tr>
<td><textarea cols="70" rows="20" name="wcs_style"><?php echo get_option('wcs_style_options'); ?></textarea></td>
</tr>
<tr>
<td align="right">
<input type="submit" class="button-primary" name="Save" value="Save" />
</td>
</table>
</form>


