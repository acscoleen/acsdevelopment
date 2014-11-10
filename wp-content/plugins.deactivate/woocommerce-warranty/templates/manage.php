<?php

// products per page
$per_page       = (isset($_GET['per_page'])) ? intval($_GET['per_page']) : 25;
$current_page   = (isset($_GET['p'])) ? intval($_GET['p']) : 1;

$currency = get_woocommerce_currency_symbol();
$products = new WP_Query(array(
        'post_type'         => 'product',
        'posts_per_page'    => $per_page,
        'post_status'       => 'publish',
        'orderby'           => 'title',
        'order'             => 'ASC',
        'paged'             => $current_page
    ));

if ( isset($_GET['updated']) ) {
    echo '<div class="updated fade"><p>'. __('Product warranties saved!', 'wc_warranty') .'</p></div>';
}
?>
<form method="post" action="admin-post.php">
    <p>
        <input type="hidden" name="action" value="warranty_bulk_edit" />
        <input type="submit" class="button-primary" value="<?php _e('Save All', 'wc_warranty'); ?>" />
    </p>

    <div class="tablenav">
        <div class="tablenav-pages">
            <span class="displaying-num"><?php echo $products->found_posts; ?> items</span>
            <span class="pagination-links">
            <?php
            echo paginate_links(array(
                    'base'      => 'admin.php?page=warranty_requests&tab=manage%_%',
                    'format'    => '&p=%#%',
                    'total'     => $products->max_num_pages,
                    'current'   => $current_page,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'add_args'  => array('per_page' => $per_page)
            ));
            ?>
            </span>
        </div>

        <div class="align-left">
            <?php _e('Products per Page:', 'wc_warranty'); ?>
            <a href="<?php echo add_query_arg('per_page', 25, 'admin.php?page=warranty_requests&tab=manage'); ?>" <?php if ($per_page == 25) echo 'class="current"'; ?>>25</a> |
            <a href="<?php echo add_query_arg('per_page', 50, 'admin.php?page=warranty_requests&tab=manage'); ?>" <?php if ($per_page == 50) echo 'class="current"'; ?>>50</a> |
            <a href="<?php echo add_query_arg('per_page', 100, 'admin.php?page=warranty_requests&tab=manage'); ?>" <?php if ($per_page == 100) echo 'class="current"'; ?>>100</a>
        </div>
    </div>

    <table class="wp-list-table widefat fixed woocommerce_page_warranty_requests" cellspacing="0">
        <thead>
            <tr>
                <th scope="col" id="id" class="manage-column column-id" width="80"><?php _e('ID', 'wc_warranty'); ?></th>
                <th scope="col" id="name" class="manage-column column-name"><?php _e('Name', 'wc_warranty'); ?></th>
                <th scope="col" id="warranty_type" class="manage-column column-warranty_type"><?php _e('Warranty Type', 'wc_warranty'); ?></th>
                <th scope="col" id="warranty_label" class="manage-column column-warranty_label"><?php _e('Warranty Label', 'wc_warranty'); ?></th>
                <th scope="col" id="warranty_details" class="manage-column column-warranty_details" style="width:350px;"><?php _e('Warranty Details', 'wc_warranty'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php

        while ($products->have_posts()):
            $products->the_post();

            $_product    = get_product(get_the_ID());
            $warranty   = get_post_meta($_product->id, '_warranty', true);
            $label      = get_post_meta($_product->id, '_warranty_label', true);

            if (! $warranty ) {
                $warranty = array(
                    'type'      => 'no_warranty'
                );

                $label = 'Warranty';
            }

        ?>
            <tr id="row_<?php echo $_product->id; ?>" data-id="<?php echo $_product->id; ?>">
                <td><?php echo $_product->id; ?></td>
                <td><a href="post.php?post=<?php echo $_product->id; ?>&action=edit"><?php echo $_product->get_title(); ?></a></td>
                <td>
                    <?php if ( $_product->product_type != 'variable'): ?>
                    <select name="warranty_type[<?php echo $_product->id; ?>]" class="warranty-type" data-id="<?php echo $_product->id; ?>">
                        <option <?php selected($warranty['type'], 'no_warranty'); ?> value="no_warranty"><?php _e('No Warranty', 'wc_warranty'); ?></option>
                        <option <?php selected($warranty['type'], 'included_warranty'); ?> value="included_warranty"><?php _e('Warranty Included', 'wc_warranty'); ?></option>
                        <option <?php selected($warranty['type'], 'addon_warranty'); ?> value="addon_warranty"><?php _e('Warranty as Add-On', 'wc_warranty'); ?></option>
                    </select>
                    <?php endif; ?>
                </td>
                <td class="show_if_included_warranty show_if_addon_warranty">
                    <?php if ( $_product->product_type != 'variable'): ?>
                    <input type="text" name="warranty_label[<?php echo $_product->id; ?>]" value="<?php echo esc_attr($label); ?>" class="input-text sized warranty-label">
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ( $_product->product_type != 'variable'): ?>
                    <div class="included-form">
                        <select name="included_warranty_length[<?php echo $_product->id; ?>]" class="select short included-warranty-length" id="included_warranty_length_<?php echo $_product->id; ?>">
                            <option <?php if ($warranty['type'] == 'included_warranty' && $warranty['length'] == 'lifetime') echo 'selected'; ?> value="lifetime">Lifetime Warranty</option>
                            <option <?php if ($warranty['type'] == 'included_warranty' && $warranty['length'] == 'limited') echo 'selected'; ?> value="limited">Limited Warranty</option>
                        </select>

                        <p id="limited_warranty_row_<?php echo $_product->id; ?>">
                            <input type="text" class="input-text sized" size="3" name="limited_warranty_length_value[<?php echo $_product->id; ?>]" value="<?php if ($warranty['type'] == 'included_warranty') echo $warranty['value']; ?>">
                            <select name="limited_warranty_length_duration[<?php echo $_product->id; ?>]">
                                <option <?php if ($warranty['type'] == 'included_warranty' && $warranty['duration'] == 'days') echo 'selected'; ?> value="days">Days</option>
                                <option <?php if ($warranty['type'] == 'included_warranty' && $warranty['duration'] == 'weeks') echo 'selected'; ?> value="weeks">Weeks</option>
                                <option <?php if ($warranty['type'] == 'included_warranty' && $warranty['duration'] == 'months') echo 'selected'; ?> value="months">Months</option>
                                <option <?php if ($warranty['type'] == 'included_warranty' && $warranty['duration'] == 'years') echo 'selected'; ?> value="years">Years</option>
                            </select>
                        </p>
                    </div>

                    <div class="addon-form">
                        <p>
                            <label>
                                <?php _e( '"No Warranty" option', 'wc_warranty'); ?>
                                <input type="checkbox" name="addon_no_warranty[<?php echo $_product->id; ?>]" id="addon_no_warranty" value="yes" <?php if (isset($warranty['no_warranty_option']) && $warranty['no_warranty_option'] == 'yes') echo 'checked'; ?> class="checkbox" />
                            </label>
                            <a style="float: right;" href="#" class="button btn-add-warranty">&plus;</a>
                        </p>

                        <table class="widefat">
                            <thead>
                            <tr>
                                <th><?php _e('Cost', 'wc_warranty'); ?></th>
                                <th><?php _e('Duration', 'wc_warranty'); ?></th>
                                <th width="50">&nbsp;</th>
                            </tr>
                            </thead>
                            <tbody class="addons-tbody">
                                <?php if ( isset($warranty['addons']) ) foreach ( $warranty['addons'] as $addon ): ?>
                                <tr>
                                    <td valign="middle">
                                        <span class="input"><b>+</b> <?php echo $currency; ?></span>
                                        <input type="text" name="addon_warranty_amount[<?php echo $_product->id; ?>][]" class="input-text sized" size="2" value="<?php echo esc_attr($addon['amount']); ?>" />
                                    </td>
                                    <td valign="middle">
                                        <input type="text" class="input-text sized" size="2" name="addon_warranty_length_value[<?php echo $_product->id; ?>][]" value="<?php if ($warranty['type'] == 'included_warranty') echo esc_attr($addon['value']); ?>" />
                                        <select name="addon_warranty_length_duration[<?php echo $_product->id; ?>][]">
                                            <option <?php if ($warranty['type'] == 'addon_warranty') selected($addon['duration'], 'days'); ?> value="days"><?php _e('Days', 'wc_warranty'); ?></option>
                                            <option <?php if ($warranty['type'] == 'addon_warranty') selected($addon['duration'], 'weeks'); ?> value="weeks"><?php _e('Weeks', 'wc_warranty'); ?></option>
                                            <option <?php if ($warranty['type'] == 'addon_warranty') selected($addon['duration'], 'months'); ?> value="months"><?php _e('Months', 'wc_warranty'); ?></option>
                                            <option <?php if ($warranty['type'] == 'addon_warranty') selected($addon['duration'], 'years'); ?> value="years"><?php _e('Years', 'wc_warranty'); ?></option>
                                        </select>
                                    </td>
                                    <td><a class="button warranty_addon_remove" href="#">&times;</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>

                        </table>
                    </div>
                    <?php endif; ?>
                </td>
            </tr>
        <?php

            if ($_product->product_type == 'variable'):
                foreach ($_product->get_children() as $child):
                    $_variation = get_product($child);

                    $warranty   = get_post_meta($child, '_warranty', true);
                    $label      = get_post_meta($child, '_warranty_label', true);

                    if (! $warranty ) {
                        $warranty = array(
                            'type'      => 'no_warranty'
                        );

                        $label = 'Warranty';
                    }

                ?>
                    <tr id="row_<?php echo $child; ?>" data-id="<?php echo $child; ?>">
                        <td>&nbsp;</td>
                        <td colspan="1">&mdash; <small><?php echo $_variation->get_formatted_name(); ?></small></td>
                        <td>
                            <select name="warranty_type[<?php echo $child; ?>]" class="warranty-type" data-id="<?php echo $child; ?>">
                                <option <?php selected($warranty['type'], 'no_warranty'); ?> value="no_warranty"><?php _e('No Warranty', 'wc_warranty'); ?></option>
                                <option <?php selected($warranty['type'], 'included_warranty'); ?> value="included_warranty"><?php _e('Warranty Included', 'wc_warranty'); ?></option>
                                <option <?php selected($warranty['type'], 'addon_warranty'); ?> value="addon_warranty"><?php _e('Warranty as Add-On', 'wc_warranty'); ?></option>
                            </select>
                        </td>
                        <td class="show_if_included_warranty show_if_addon_warranty">
                            <input type="text" name="warranty_label[<?php echo $child; ?>]" value="<?php echo esc_attr($label); ?>" class="input-text sized warranty-label">
                        </td>
                        <td>
                            <div class="included-form">
                                <select name="included_warranty_length[<?php echo $child; ?>]" class="select short included-warranty-length" id="included_warranty_length_<?php echo $child; ?>">
                                    <option <?php if ($warranty['type'] == 'included_warranty' && $warranty['length'] == 'lifetime') echo 'selected'; ?> value="lifetime">Lifetime Warranty</option>
                                    <option <?php if ($warranty['type'] == 'included_warranty' && $warranty['length'] == 'limited') echo 'selected'; ?> value="limited">Limited Warranty</option>
                                </select>

                                <p id="limited_warranty_row_<?php echo $child; ?>">
                                    <input type="text" class="input-text sized" size="3" name="limited_warranty_length_value[<?php echo $child; ?>]" value="<?php if ($warranty['type'] == 'included_warranty') echo esc_attr($warranty['value']); ?>">
                                    <select name="limited_warranty_length_duration[<?php echo $child; ?>]">
                                        <option <?php if ($warranty['type'] == 'included_warranty') selected($warranty['duration'], 'days'); ?> value="days"><?php _e('Days', 'wc_warranty'); ?></option>
                                        <option <?php if ($warranty['type'] == 'included_warranty') selected($warranty['duration'], 'weeks'); ?> value="weeks"><?php _e('Weeks', 'wc_warranty'); ?></option>
                                        <option <?php if ($warranty['type'] == 'included_warranty') selected($warranty['duration'], 'months'); ?> value="months"><?php _e('Months', 'wc_warranty'); ?></option>
                                        <option <?php if ($warranty['type'] == 'included_warranty') selected($warranty['duration'], 'years'); ?> value="years"><?php _e('Years', 'wc_warranty'); ?></option>
                                    </select>
                                </p>
                            </div>

                            <div class="addon-form">
                                <p>
                                    <label>
                                        <?php _e( '"No Warranty" option', 'wc_warranty'); ?>
                                        <input type="checkbox" name="addon_no_warranty[<?php echo $child; ?>]" id="addon_no_warranty" value="yes" <?php if (isset($warranty['no_warranty_option']) && $warranty['no_warranty_option'] == 'yes') echo 'checked'; ?> class="checkbox" />
                                    </label>
                                    <a style="float: right;" href="#" class="button btn-add-warranty">&plus;</a>
                                </p>

                                <table class="widefat">
                                    <thead>
                                    <tr>
                                        <th><?php _e('Cost', 'wc_warranty'); ?></th>
                                        <th><?php _e('Duration', 'wc_warranty'); ?></th>
                                        <th width="50">&nbsp;</th>
                                    </tr>
                                    </thead>
                                    <tbody class="addons-tbody">
                                    <?php if ( isset($warranty['addons']) ) foreach ( $warranty['addons'] as $addon ): ?>
                                        <tr>
                                            <td valign="middle">
                                                <span class="input"><b>+</b> <?php echo $currency; ?></span>
                                                <input type="text" name="addon_warranty_amount[<?php echo $child; ?>][]" class="input-text sized" size="2" value="<?php echo esc_attr($addon['amount']); ?>" />
                                            </td>
                                            <td valign="middle">
                                                <input type="text" class="input-text sized" size="2" name="addon_warranty_length_value[<?php echo $child; ?>][]" value="<?php echo esc_attr($addon['value']); ?>" />
                                                <select name="addon_warranty_length_duration[<?php echo $child; ?>][]">
                                                    <option <?php selected($addon['duration'], 'days'); ?> value="days"><?php _e('Days', 'wc_warranty'); ?></option>
                                                    <option <?php selected($addon['duration'], 'weeks'); ?> value="weeks"><?php _e('Weeks', 'wc_warranty'); ?></option>
                                                    <option <?php selected($addon['duration'], 'months'); ?> value="months"><?php _e('Months', 'wc_warranty'); ?></option>
                                                    <option <?php selected($addon['duration'], 'years'); ?> value="years"><?php _e('Years', 'wc_warranty'); ?></option>
                                                </select>
                                            </td>
                                            <td><a class="button warranty_addon_remove" href="#">&times;</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>

                                </table>
                            </div>

                        </td>
                    </tr>
                <?php
                endforeach;
            endif;
        endwhile; // while ($products->have_posts)
        ?>
        </tbody>
    </table>
    <div class="tablenav">
        <div class="tablenav-pages">
            <span class="displaying-num"><?php echo $products->found_posts; ?> items</span>
            <span class="pagination-links">
            <?php
            echo paginate_links(array(
                    'base'      => 'admin.php?page=warranty_requests&tab=manage%_%',
                    'format'    => '&p=%#%',
                    'total'     => $products->max_num_pages,
                    'current'   => $current_page,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'add_args'  => array('per_page' => $per_page)
                ));
            ?>
            </span>
        </div>

        <div class="align-left">
            Products per Page:
            <a href="<?php echo add_query_arg('per_page', 25, 'admin.php?page=warranty_requests&tab=manage'); ?>" <?php if ($per_page == 25) echo 'class="current"'; ?>>25</a> |
            <a href="<?php echo add_query_arg('per_page', 50, 'admin.php?page=warranty_requests&tab=manage'); ?>" <?php if ($per_page == 50) echo 'class="current"'; ?>>50</a> |
            <a href="<?php echo add_query_arg('per_page', 100, 'admin.php?page=warranty_requests&tab=manage'); ?>" <?php if ($per_page == 100) echo 'class="current"'; ?>>100</a>
        </div>
    </div>
    <p>
        <input type="submit" class="button-primary" value="<?php _e('Save All', 'wc_warranty'); ?>" />
    </p>
</form>

<script type="text/javascript">
var tmpl = '<tr>\
                <td valign=\"middle\">\
                    <span class=\"input\"><b>+</b> <?php echo $currency; ?></span>\
                    <input type=\"text\" name=\"addon_warranty_amount[{id}][]\" class=\"input-text sized\" size=\"2\" value=\"\" />\
                </td>\
                <td valign=\"middle\">\
                    <input type=\"text\" class=\"input-text sized\" size=\"2\" name=\"addon_warranty_length_value[{id}][]\" value=\"\" />\
                    <select name=\"addon_warranty_length_duration[{id}][]\">\
                        <option value=\"days\"><?php _e('Days', 'wc_warranty'); ?></option>\
                        <option value=\"weeks\"><?php _e('Weeks', 'wc_warranty'); ?></option>\
                        <option value=\"months\"><?php _e('Months', 'wc_warranty'); ?></option>\
                        <option value=\"years\"><?php _e('Years', 'wc_warranty'); ?></option>\
                    </select>\
                </td>\
                <td><a class=\"button warranty_addon_remove\" href=\"#\">&times;</a></td>\
            </tr>';
jQuery(document).ready(function() {
    jQuery(".warranty-type").change(function() {
        var parent  = jQuery(this).parents("tr");
        var id      = jQuery(parent).data("id");

        jQuery(parent).find(".included-form").hide();
        jQuery(parent).find(".addon-form").hide();

        switch (jQuery(this).val()) {

            case 'included_warranty':
                jQuery(parent).find(".warranty-label").attr("disabled", false);
                jQuery(parent).find(".included-form").show();
                jQuery("#included_warranty_length_"+id).change();
                break;

            case 'addon_warranty':
                jQuery(parent).find(".warranty-label").attr("disabled", false);
                jQuery(parent).find(".addon-form").show();
                break;

            default:
                jQuery(parent).find(".warranty-label").attr("disabled", true);
                break;

        }
    }).change();

    jQuery(".included-warranty-length").change(function() {
        var parent  = jQuery(this).parents("tr");
        var id      = jQuery(parent).data("id");

        if (jQuery(this).val() == "lifetime") {
            jQuery("#limited_warranty_row_"+id).hide();
        } else {
            jQuery("#limited_warranty_row_"+id).show();
        }
    });

    jQuery(".btn-add-warranty").live("click", function(e) {
        e.preventDefault();

        var id = jQuery(this).parents("tr").eq(0).data("id");

        var t = tmpl.replace(new RegExp('{id}', 'g'), id);
        jQuery(this).parents("tr").find(".addons-tbody").append(t);
    });

    jQuery(".warranty_addon_remove").live("click", function(e) {
        e.preventDefault();

        jQuery(this).parents("tr").eq(0).remove();
    });
});
</script>