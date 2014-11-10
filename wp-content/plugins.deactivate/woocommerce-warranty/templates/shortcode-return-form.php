<?php global $post, $woocommerce; ?>
<form name="warranty_form" id="warranty_form" method="POST" action="" enctype="multipart/form-data" >
    
    <?php $woocommerce->show_messages(); ?>

    <p>
        <label for="first_name"><?php _e('Name', 'wc_warranty'); ?></label><br />
        <input type="text" name="first_name" id="first_name" placeholder="First" />
        <input type="text" name="last_name" id="last_name" placeholder="Last" />
    </p>

    <p>
        <label for="email"><?php _e('Email Address', 'wc_warranty'); ?></label><br/>
        <input type="text" name="email" id="email" />
    </p>

    <p>
        <label for="order_id"><?php _e('Order Number', 'wc_warranty'); ?></label><br/>
        <input type="text" name="order_id" id="order_id" />
    </p>

    <p>
        <label for="product"><?php _e('Product', 'wc_warranty'); ?></label><br/>
        <input type="text" name="product" id="product" />
    </p>

    <p>
        <label for="issue"><?php _e('Describe the issue', 'wc_warranty'); ?></label><br/>
        <textarea style="width:250px;" rows="4" name="issue" id="issue"></textarea>
    </p>

    <p>
        <input type="hidden" name="return" value="<?php echo $post->ID; ?>" />
        <input type="hidden" name="req" value="new_return" />
        <input type="submit" name="submit" value="Submit" class="button">
    </p>

</form>
<script type="text/javascript">
jQuery(document).ready(function() {
    jQuery("#warranty_form").submit(function() {
        var is_error = false;

        jQuery("#warranty_form").find("input[type=text], textarea").each(function() {
            if (! jQuery(this).val() ) {
                is_error = true;
                return false;
            }
        });

        if ( is_error ) {
            alert("Please complete the form");
            return false;
        }
    });
});
</script>