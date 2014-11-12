<div class="wrap woocommerce">
    <div class="icon32"><img src="<?php echo plugins_url() .'/woocommerce-warranty/assets/images/icon.png'; ?>" /><br></div>
    <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
        <a href="admin.php?page=warranty_requests&amp;tab=list" class="nav-tab <?php echo ($tab == 'list') ? 'nav-tab-active' : ''; ?>"><?php _e('RMA Requests', 'wc_warranty'); ?></a>
        <a href="admin.php?page=warranty_requests&amp;tab=new" class="nav-tab <?php echo ($tab == 'new') ? 'nav-tab-active' : ''; ?>"><?php _e('Create Request', 'wc_warranty'); ?></a>
        <a href="admin.php?page=warranty_requests&amp;tab=manage" class="nav-tab <?php echo ($tab == 'manage') ? 'nav-tab-active' : ''; ?>"><?php _e('Manage Warranties', 'wc_warranty'); ?></a>
        <a href="admin.php?page=warranty_requests&amp;tab=report" class="nav-tab <?php echo $tab == 'report' ? 'nav-tab-active' : ''; ?>"><?php _e('Reports', 'wc_warranty'); ?></a>
    </h2>
