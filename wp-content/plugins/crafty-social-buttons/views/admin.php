﻿            <div class="wrap <?php echo $this->plugin_slug; ?>">
                <h2><?php _e('Crafty Social Buttons', $this->plugin_slug); ?></h2>

                <?php  $active_tab = (isset($_GET[ 'tab' ])) ? $_GET[ 'tab' ] : 'share_options'; ?>

                <h2 class="nav-tab-wrapper"> <a href="?page=<?php echo $this->plugin_slug; ?>&tab=share_options" 
                    class="nav-tab <?php echo $active_tab == 'share_options' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Share Button Options',$this->plugin_slug); ?></a>
                    <a href="?page=<?php echo $this->plugin_slug; ?>&tab=link_options" 
                       class="nav-tab <?php echo $active_tab == 'link_options' ? 'nav-tab-active' : ''; ?>">
                       <?php _e('Link Button Options',$this->plugin_slug); ?></a>
                </h2>
                        
    			<form method="post" action="options.php">
        			<?php  
					settings_fields( $this->plugin_slug );  
                
					  $tab = $this->plugin_slug.'[tab]';
					  echo '<input type="hidden" name="'.$tab.'" value="'.$active_tab.'">';  
                     
                if( $active_tab == 'share_options' ) {
						   do_settings_sections($this->plugin_slug.'-share'); 
    		       } else {  
						   echo '<input type="hidden" name="$tab" value="link">';  
                    do_settings_sections($this->plugin_slug.'-link');  
    	          }   
                  
                submit_button();  
              
            ?>
    </form>
