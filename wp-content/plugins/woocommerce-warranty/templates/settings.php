<?php
$all_statuses   = warranty_get_statuses();
$default_slugs  = get_option( 'wc_warranty_default_slugs' );
$order_statuses = get_terms( 'shop_order_status', array('hide_empty' => false) );
$start_status   = get_option( 'warranty_start_status', 'completed' );
?>

<?php if ( isset($_GET['updated']) ): ?>
<div class="message updated"><p><?php _e('Settings updated', 'wc_warranty'); ?></p></div>
<?php endif; ?>

<style type="text/css">
table.disabled {
    opacity: 0.3;
    -ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=30)";
    filter: alpha(opacity=30);
}

ul.statuses {
    width: 300px;
    border: 1px solid #dfdfdf;
    -moz-border-radius: 5px;
    -webkit-border-radius: 5px;
    border-radius: 5px;
}

ul.statuses li {
    margin: 0;
    padding: 5px 5px 8px 5px;
    border-bottom: 1px solid #dfdfdf;
    cursor: default;
}

ul.statuses li:last-child {
    border-bottom: none;
}

ul.statuses li span.handle img {
    vertical-align: middle;
}

ul.statuses li a.delete-status {
    float: right;
}

.edit-term img {
    vertical-align: middle;
    cursor: pointer;
}

#reorder_status {
    font-size: 15px;
}
</style>

<?php if (! WC_Warranty::$shipping_tracking ): ?>
<div class="updated fade">
    <p>
        If you want advanced shipment tracking for your RMA process - which includes automatic linking and support for 14 shipping providers - please purchase and install the <a href="http://www.woothemes.com/products/shipment-tracking/">WooCommerce Shipment Tracking</a> extension. You will still be able to provide shipping information to your customers without this plugin, but activating the plugin could enhance your customer's experience.
    </p>
</div>
<?php endif; ?>

<form method="post" action="admin-post.php">
    <div class="subsubsub_section">
        <ul class="subsubsub">
            <li><a href="#general_section" class="current"><?php _e('Warranty Settings', 'wc_warranty'); ?></a> | </li>
            <li><a href="#rma_section"><?php _e('RMA Code', 'wc_warranty'); ?></a> | </li>
            <li><a href="#emails_section"><?php _e('Emails', 'wc_warranty'); ?></a></li>
        </ul>
        <br class="clear">

        <div class="section" id="general_section">
            <h3><?php _e('Warranty Settings', 'wc_warranty'); ?></h3>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <label for="order_status">Order Status</label>
                    </th>
                    <td>
                        <select name="start_status" id="start_status" class="chzn-select" style="width: 200px;">
                            <?php foreach ( $order_statuses as $order_status ): ?>
                            <option value="<?php echo $order_status->name; ?>" <?php if ($order_status->name == $start_status) echo 'selected'; ?>><?php echo $order_status->name; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('', 'wc_warranty'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="warranty_button_text"><?php _e('Warranty Button Text', 'wc_warranty'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="warranty_button_text" id="warranty_button_text" value="<?php echo esc_attr(get_option('wc_warranty_button_title', 'Request Warranty')); ?>" />
                        <span class="description"><?php _e('Default: Request Warranty', 'wc_warranty'); ?></span>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">
                        <label for="warranty_reason"><?php _e('Warranty Reasons', 'wc_warranty'); ?></label>
                        <p class="description"><?php _e('One reason per line', 'wc_warranty'); ?></p>
                    </th>
                    <td>
                        <textarea id="warranty_reason" name="warranty_reason" style="width:400px;height:100px;"><?php echo esc_attr( get_option('warranty_reason', '') ); ?></textarea>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">
                        <label for="warranty_question"><?php _e('Warranty Question', 'wc_warranty'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="warranty_question" id="warranty_question" class="regular-text" value="<?php echo esc_attr( get_option('warranty_question', '') ); ?>" />
                        <label>
                            <?php $check = get_option( 'warranty_require_question', 'no' ); ?>
                            <input type="checkbox" name="warranty_require_question" value="yes" <?php echo ($check == 'yes') ? 'checked' : ''; ?> /> <?php _e('Required', 'wc_warranty'); ?>
                        </label>
                        <p class="description"><?php _e('Leave empty to disable.', 'wc_warranty'); ?></p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">
                        <label for="warranty_upload"><?php _e('Allow attachment upload', 'wc_warranty'); ?></label>
                    </th>
                    <td>
                        <?php $upload = get_option( 'warranty_upload', 'no' ); ?>
                        <select name="warranty_upload">
                            <option value="no" <?php echo ( $upload == 'no' ) ? 'selected' : ''; ?>><?php _e('No', 'wc_warranty'); ?></option>
                            <option value="yes" <?php echo ( $upload == 'yes' ) ? 'selected' : ''; ?>><?php _e('Yes', 'wc_warranty'); ?></option>
                        </select>
                        <label>
                            <?php
                            $check = get_option('warranty_require_upload', 'no');
                            ?>
                            <input type="checkbox" name="warranty_require_upload" value="yes" <?php echo ($check == 'yes') ? 'checked' : ''; ?> /> <?php _e('Required', 'wc_warranty'); ?>
                        </label>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">
                        <label for="warranty_upload_title"><?php _e('Upload Field Title', 'wc_warranty'); ?></label>
                    </th>
                    <td>
                        <input type="text" class="regular-text" name="warranty_upload_title" id="warranty_upload_title" value="<?php echo esc_attr(get_option('warranty_upload_title', '')); ?>" />
                    </td>
                </tr>
            </table>
        </div>

        <div id="rma_section" class="section" style="display:none;">
            <h3><?php _e('RMA Code Format', 'wc_warranty'); ?></h3>

            <?php
            $saved_ram  = get_option( 'warranty_saved_rma', 0 );
            $last_rma   = get_option( 'warranty_last_rma', 0 );
            ?>
            <table class="form-table" id="rma_table">
                <tr>
                    <th><label for="rma_start"><?php _e('RMA Code Start', 'wc_warranty'); ?></label></th>
                    <td>
                        <input type="text" name="rma_start" id="rma_start" value="<?php echo $last_rma; ?>" />
                        <p class="description"><?php _e('The starting number for the incrementing portion of the code', 'wc_warranty'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th><label for="rma_length"><?php _e('RMA Code Length', 'wc_warranty'); ?></label></th>
                    <td>
                        <input type="text" name="rma_length" id="rma_length" value="<?php echo get_option( 'warranty_rma_length', 1); ?>" />
                        <p class="description"><?php _e('The desired minimum length of the incrementing portion of the code.'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th><label for="rma_prefix"><?php _e('RMA Code Prefix', 'wc_warranty'); ?></label></th>
                    <td>
                        <input type="text" name="rma_prefix" id="rma_prefix" value="<?php echo get_option( 'warranty_rma_prefix', '' ); ?>" />
                        <p class="description"><?php _e('You may use {DD}, {MM} and {YYYY} for the current day, month and year respectively', 'wc_warranty'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th><label for="rma_suffix"><?php _e('RMA Code Suffix', 'wc_warranty'); ?></label></th>
                    <td>
                        <input type="text" name="rma_suffix" id="rma_suffix" value="<?php echo get_option( 'warranty_rma_suffix', '' ); ?>" />
                        <p class="description"><?php _e('You may use {DD}, {MM} and {YYYY} for the current day, month and year respectively', 'wc_warranty'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div id="emails_section" class="section" style="display:none;">
            <h3><?php _e('Emails', 'wc_warranty'); ?></h3>

            <p class="description">
                <?php _e('Available variables:', 'wc_warranty'); ?>
                <code>{order_id}</code>, <code>{rma_code}</code>, <code>{shipping_code}</code>,
                <code>{product_id}</code>, <code>{product_name}</code>, <code>{warranty_status}</code>,
                <code>{warranty_reason}</code>, <code>{attachment_name}</code>, <code>{attachment_url}</code>,
                <code>{customer_name}</code>, <code>{customer_email}</code>, <code>{customer_shipping_code}</code>,
                <code>{store_shipping_code}</code>, <code>{warranty_request_url}</code>, <code>{store_url}</code>
            </p>

            <?php
            $emails         = get_option( 'warranty_emails', array() );
            ?>
            <table class="wp-list-table widefat fixed posts generic-table">
                <thead>
                    <tr>
                        <th scope="col" id="status" class="manage-column column-status"><?php _e('Status', 'wc_warranty'); ?></th>
                        <th scope="col" id="send_to" class="manage-column column-send_to" style=""><?php _e('Send To', 'wc_warranty'); ?></th>
                        <th scope="col" id="subject" class="manage-column column-subject" width="27%"><?php _e('Subject', 'wc_warranty'); ?></th>
                        <th scope="col" id="message" class="manage-column column-message" width="35%"><?php _e('Message', 'wc_warranty'); ?></th>
                        <th scope="col" id="delete" class="manage-column column-delete" width="30"></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <td colspan="4">
                            <a class="button add-email" href="#">+ Add Email</a>
                        </td>
                    </tr>
                </tfoot>
                <tbody id="emails_tbody">
                    <?php
                    if (! empty($emails) ):
                        $idx = 0;

                        foreach ( $emails as $email_status => $status_email ):
                            foreach ( $status_email as $email ):
                    ?>
                    <tr id="email_<?php echo $idx; ?>">
                        <td>
                            <select name="status[<?php echo $idx; ?>]">
                                <?php foreach ( $all_statuses as $status ): ?>
                                <option value="<?php echo $status->slug; ?>" <?php echo ($status->slug == $email_status) ? 'selected' : ''; ?>><?php echo $status->name; ?></option>
                                <?php endforeach; ?>
                                <option value="Request Tracking" <?php echo ('Request Tracking' == $email_status) ? 'selected' : ''; ?>>Request Tracking</option>
                            </select>
                        </td>
                        <td>
                            <select name="send_to[<?php echo $idx; ?>]">
                                <option value="customer" <?php echo ($email['recipient'] == 'customer') ? 'selected' : ''; ?>>Customer</option>
                                <option value="admin" <?php echo ($email['recipient'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                <option value="both" <?php echo ($email['recipient'] == 'both') ? 'selected' : ''; ?>>Customer &amp; Admin</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="subject[<?php echo $idx; ?>]" value="<?php echo esc_attr($email['subject']); ?>" class="regular-text" />
                        </td>
                        <td>
                            <textarea name="message[<?php echo $idx; ?>]" rows="3" style="width: 99%;"><?php echo esc_attr($email['message']); ?></textarea>
                        </td>
                        <td><a class="button delete-row" href="#">&times;</a></td>
                    </tr>
                    <?php       $idx++;
                            endforeach;
                        endforeach;
                    else:
                    ?>
                    <tr id="email_0">
                        <td>
                            <select name="status[0]">
                                <?php foreach ( $all_statuses as $status ): ?>
                                <option value="<?php echo $status->slug; ?>"><?php echo $status->name; ?></option>
                                <?php endforeach; ?>
                                <option value="Request Tracking">Request Tracking</option>
                            </select>
                        </td>
                        <td>
                            <select name="send_to[0]">
                                <option value="customer">Customer</option>
                                <option value="admin">Admin</option>
                                <option value="both">Customer &amp; Admin</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="subject[0]" value="" class="regular-text" />
                        </td>
                        <td>
                            <textarea name="message[0]" rows="3" style="width: 99%;"></textarea>
                        </td>
                        <td></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="submit">
        <input type="hidden" name="action" value="wc_warranty_update" />
        <input type="submit" name="submit" value="<?php _e('Update Settings', 'wc_warranty'); ?>" class="button-primary" />
    </p>
</form>
<script type="text/javascript">
<?php
$js_statuses = array();
foreach ( $all_statuses as $status ) {
    if ( !isset($status->slug) || empty($status->slug) ) $status->slug = $status->name;
    $js_statuses[] = array('slug' => $status->slug, 'name' => $status->name);
}
?>
var statuses = <?php echo json_encode($js_statuses); ?>;
jQuery(document).ready(function($) {
    // Subsubsub tabs
    jQuery('div.subsubsub_section ul.subsubsub li a:eq(0)').addClass('current');
    jQuery('div.subsubsub_section .section:gt(0)').hide();

    jQuery('div.subsubsub_section ul.subsubsub li a').click(function(){
        var $clicked = jQuery(this);
        var $section = $clicked.closest('.subsubsub_section');
        var $target  = $clicked.attr('href');

        $section.find('a').removeClass('current');

        if ( $section.find('.section:visible').size() > 0 ) {
            $section.find('.section:visible').fadeOut( 100, function() {
                $section.find( $target ).fadeIn('fast');
            });
        } else {
            $section.find( $target ).fadeIn('fast');
        }

        $clicked.addClass('current');
        jQuery('#last_tab').val( $target );

        return false;
    });

    $(".chzn-select").chosen();

    var url_hash = window.location.hash;
    if (url_hash != "") {
        jQuery("a[href="+ url_hash +"]").click();
    }

    $(".add-email").click(function(e) {
        e.preventDefault();

        var idx     = 1;

        while ( $("#email_"+ idx).length > 0 ) {
            idx++;
        }

        var html    = '<tr id="email_'+ idx +'"><td><select name="status['+ idx + ']">';

        for (var x = 0; x < statuses.length; x++) {
            html += '<option value="'+ statuses[x].slug +'">'+ statuses[x].name +'</option>';
        }
        html        += '<option value="Request Tracking">Request Tracking</option>';
        html        += '</select></td>';
        html        += '<td><select name="send_to['+ idx +']"><option value="customer">Customer</option><option value="admin">Admin</option><option value="both">Customer &amp; Admin</option></select></td>';
        html        += '<td><input type="text" name="subject['+ idx +']" value="" class="regular-text" /></td>';
        html        += '<td><textarea name="message['+ idx +']" rows="3" style="width: 99%;"></textarea></td>';
        html        += '<td><a class="button delete-row" href="#">&times;</a></td></tr>';

        $("#emails_tbody").append(html);
    });

    $(".delete-row").live("click", function(e) {
        e.preventDefault();

        $(this).parents("tr").remove();
    });

    /*$("table.disabled input").each(function() {
        $(this).attr("readonly", true);
    });

    $(".edit-rma-table").click(function(e) {
        e.preventDefault();

        $("#rma_table").removeClass("disabled");
        $("#rma_table").find("input").each(function() {
            $(this).attr("readonly", false);
        });
    });*/

    $("ul.statuses li.static").each(function () {
        $(this).attr("id", "status-" + $(this).index());
    });

    // Sorting
    jQuery('ul.statuses').sortable({
        items:'li',
        cursor:'move',
        axis:'y',
        //handle: '.handle',
        scrollSensitivity:40,
        cancel: "li.static",
        helper:function(e,ui){
            ui.children().each(function(){
                jQuery(this).width(jQuery(this).width());
            });

            return ui;
        },
        start:function(event,ui){
            ui.item.css('background-color','#f6f6f6');
        },
        stop:function(event,ui){
            ui.item.removeAttr('style');
        },
        update: function(event, ui) {
            /*$("ul.statuses li.static").each(function() {
               var desiredLocation = $(this).attr("id").replace("status-","");
               var currentLocation = $(this).index();
               while(currentLocation < desiredLocation) {
                 $(this).next().insertBefore(this);
                  currentLocation++;
                }
                while(currentLocation > desiredLocation) {
                 $(this).prev().insertAfter(this);
                  currentLocation--;
                }
            });*/
        }
    });

    // Edit Icon
    jQuery("li.status").hover(
        function() {
            $(this).find("span.edit-term").show();
        },
        function() {
            $(this).find("span.edit-term").hide();
        }
    );

    // Add status
    jQuery(".add-status").click(function() {
        var status = $("#new_status").val();

        if ( status == "" ) return;

        var data = {"action": "wc_warranty_new_status", "status": status};

        $.post(ajaxurl, data, function(resp) {
            var response = JSON.parse(resp);
            $("ul.statuses").append('<li class="status sortable" id="status-'+ response.order +'"><span class="handle"><img src="<?php echo plugins_url() .'/woocommerce-warranty/assets/images/drag_handle.png'; ?>" /></span><a href="#" class="delete-status" data-id="'+ response.id +'">&times; Delete</a><input type="hidden" name="sort_order[]" value="'+ response.slug +'" />'+ response.name +'</li>');
            $("ul.statuses").sortable("refresh");

            $("#new_status").val('');
        });
    });

    jQuery(".edit-term").live("click", function() {
        var term_id = $(this).data("term_id");
        var term = prompt("<?php _e('Enter the new name for this status', 'wc_warranty'); ?>");

        if (term)
            window.location.href = 'admin-post.php?action=wc_warranty_rename_status&id='+ term_id +'&name='+ encodeURIComponent(term);
    });

    jQuery(".delete-status").live("click", function(e) {
        e.preventDefault();

        var id = $(this).data("id");
        $(this).parents("li").remove();
        $.post(ajaxurl, {"action": "wc_warranty_delete_status", "id": id});
    });

    jQuery("#update_order").click(function() {
        $(this)
            .val('Please wait')
            .attr("disabled", true);
        var data = $("ul.statuses").sortable("serialize");
        data += "&action=wc_warranty_reorder";

        var that = this;
        $.post(ajaxurl, data, function() {
            $("#reorder_status")
                .html("&#10003;")
                .fadeOut(5000);

            $(that)
                .val("Update Order")
                .attr("disabled", false);
        });
    });
});
</script>
