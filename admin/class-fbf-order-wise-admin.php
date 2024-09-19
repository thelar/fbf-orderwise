<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.chapteragency.com
 * @since      1.0.0
 *
 * @package    Fbf_Order_Wise
 * @subpackage Fbf_Order_Wise/admin
 */

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Fbf_Order_Wise
 * @subpackage Fbf_Order_Wise/admin
 * @author     Kevin Price-Ward <kevin.price-ward@chapteragency.com>
 */
class Fbf_Order_Wise_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     *
     * The garages from the garages.xlsx for National fitting
     *
     * @since   1.0.0
     * @access  private
     * @var     array   $garages    Array of garages
     */
    private $garages;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_filter('wc_customer_order_xml_export_suite_format_definition', array($this, 'sv_wc_xml_export_custom_format_settings'), 10, 3);
        add_filter('wc_customer_order_xml_export_suite_orders_header', array($this, 'sv_wc_customer_order_xml_export_suite_orders_xml_data_add_attributes_to_root_element'));
        add_filter('wc_customer_order_xml_export_suite_orders_footer', array($this, 'sv_wc_customer_order_xml_export_suite_orders_xml_data_add_attributes_to_root_element_footer'));

        //add_filter('wc_customer_order_xml_export_suite_orders_xml_data', array($this, 'sv_wc_xml_export_order_name'), 10, 3);

        add_filter('wc_customer_order_export_xml_get_orders_xml_data', array($this, 'sv_wc_xml_export_order_name'), 10, 3);

        //add_filter('wc_customer_order_xml_export_suite_order_data', array($this, 'sv_wc_xml_export_order_item_format'), 10, 3);

        add_filter('wc_customer_order_export_xml_order_data', array($this, 'sv_wc_xml_export_order_item_format'), 10, 3);

        //add_filter('wc_customer_order_xml_export_suite_order_line_item', array($this, 'sv_wc_xml_export_order_line_item'), 10, 3);

        add_filter('wc_customer_order_export_xml_order_line_item', array($this, 'sv_wc_xml_export_order_line_item'), 10, 3);
        // add_filter('wc_customer_order_xml_export_suite_orders_xml', array($this, 'sv_wc_xml_export_output'), 10, 3);
        //add_filter('wc_customer_order_xml_export_suite_order_export_format', array($this, 'sv_wc_xml_export_order_format'), 10, 3);
        //add_filter('wc_customer_order_xml_export_suite_order_line_item', array($this, 'sv_wc_xml_export_line_item_addons'), 10, 3);
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Fbf_Order_Wise_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Fbf_Order_Wise_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        //wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/fbf-order-wise-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Fbf_Order_Wise_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Fbf_Order_Wise_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        //wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/fbf-order-wise-admin.js', array('jquery'), $this->version, false);
    }



    public function sv_wc_xml_export_custom_format_settings($definition, $export_type, $format)
    {

        // could also check $export_type for 'orders' or 'customers'
        if ('custom' === $format) {
            $definition['xml_encoding'] = 'UTF-8';
        }

        return $definition;
    }

    function sv_wc_customer_order_xml_export_suite_orders_xml_data_add_attributes_to_root_element($header)
    {
        return str_replace(
            '<Orders>',
            '<XMLFile xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
 <SalesOrders>',
            $header
        );
    }


    function sv_wc_customer_order_xml_export_suite_orders_xml_data_add_attributes_to_root_element_footer($footer)
    {
        return str_replace(
            '</Orders>',
            '</SalesOrders></XMLFile>',
            $footer
        );
    }

    public function sv_wc_xml_export_order_name($orders_format, $orders)
    {
        $orders_format = array(
            'SalesOrder' => $orders,
        );

        return $orders_format;
    }

    function sv_wc_xml_export_order_item_format($order_data, $order)
    {
        $a = 1;

        // Load garages from DB
        if($order->get_meta('_is_national_fitting')){
            // Try to get the garage ID, first from the _national_fitting_garage_id meta
            if($order->get_meta('_national_fitting_garage_id')&&$order->get_meta('_national_fitting_type')==='garage'){
                $garage_id = $order->get_meta('_national_fitting_garage_id');
            }else if($order->get_meta('_gs_selected_garage')&&$order->get_meta('_national_fitting_type')==='garage'){
                $garage_id = $order->get_meta('_gs_selected_garage')['id'];
            }else if($order->get_meta('_national_fitting_type')==='fit_on_drive'){
                $garage_id = 349; // Halfords
            }
            if(isset($garage_id)){
                global $wpdb;
                $table_name = $wpdb->prefix . 'fbf_garages';
                $sql = sprintf('SELECT * FROM %s WHERE centre_id = \'%s\'', $table_name, $garage_id);
                $garage_a = $wpdb->get_row($sql);
            }
        }

        // Process the garages
        /*$filename = 'garages.xlsx';
        if(function_exists('get_home_path')){
            $filepath = get_home_path() . '../supplier/azure/garages/' . $filename;
        }else{
            $filepath = ABSPATH . '../../supplier/azure/garages/' . $filename;
        }
        if(file_exists($filepath)) {
            $reader = new Xlsx();
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filepath);
            $worksheet = $spreadsheet->getActiveSheet();
            $garage_data = $worksheet->toArray();
            $this->garages = $garage_data;
        }*/

        // require_once(ABSPATH . '/wp-content/plugins/woocommerce-customer-order-xml-export-suite/includes/class-wc-customer-order-xml-export-suite-generator.php');
        // format date
        //$datetime = new DateTime($order->order_date);
        $datetime_o = $order->get_date_created();
        $date = $datetime_o->format("Y-m-d\TH:i:s");

        // set required and promise dates for national fitting
        if($order->get_meta('_is_national_fitting')){
            if($order->get_meta('_gs_selected_garage')){
                if(strpos($order->get_meta('_gs_selected_garage')['date'], '-')!==false){
                    $format = 'Y-m-d';
                }else{
                    $format = 'd/m/y';
                }

                $required_date = \DateTime::createFromFormat($format, $order->get_meta('_gs_selected_garage')['date']);
                $required_day = strtolower($required_date->format('l'));
                $days_of_week = [
                    'monday',
                    'tuesday',
                    'wednesday',
                    'thursday',
                    'friday',
                    'saturday',
                    'sunday',
                ];

                /*$search_garage_id = $order->get_meta('_gs_selected_garage')['id'];
                if(get_post_meta($order->get_ID(), '_national_fitting_type', true)==='fit_on_drive'){
                    $search_garage_id = 349; // Hardcode HME garage ID for Halfords
                }*/

                //foreach($this->garages as $garage){
                    //if((int)$garage[0]===(int)$search_garage_id){
                        //$garage_data = $garage;
                        $garage_works_monday = $garage_a->monday;
                        $garage_works_tuesday = $garage_a->tuesday;
                        $garage_works_wednesday = $garage_a->wednesday;
                        $garage_works_thursday = $garage_a->thursday;
                        $garage_works_friday = $garage_a->friday;
                        $garage_works_saturday = $garage_a->saturday;
                        $garage_works_sunday = $garage_a->sunday;
                        for($i=1;$i<=7;$i++){
                            $required_date->modify('-' . $i . 'day');
                            $check_date_day = strtolower($required_date->format('l'));
                            if(${'garage_works_' . $check_date_day}){
                                break;
                            }
                        }
                        //break;
                    //}
                //}
                $promise_date = \DateTime::createFromFormat($format, $order->get_meta('_gs_selected_garage')['date']);
                $promise_time = $order->get_meta('_gs_selected_garage')['time']==='am'?'09':'13';
                $promise_date->setTime($promise_time, 0, 0);
                $readable_date = $promise_date->format('d/m/y') . ' (' . $order->get_meta('_gs_selected_garage')['time'] . ')';
            }else{
                $promise_date = new \DateTime();
                $promise_date->modify('+3 day');
                $promise_date->setTime(0, 0, 0);
            }
        }else if($order->get_meta('_gs_selected_date')){
            $promise_date = new DateTime($order->get_meta('_gs_selected_date'));
            $promise_date->setTime(0, 0, 0);
        }else{
            $promise_date = new \DateTime();
            $promise_date->modify('+3 day');
            $promise_date->setTime(0, 0, 0);
        }

        $c_price = 0;
        foreach($order->get_coupons() as $coupon){
            $c = $coupon;
            $c_name = $c->get_name();
            $c_price+=(float)$c->get_discount();
        }

        $f_price = 0;
        foreach($order->get_fees() as $fee){
            $f_price+= abs($fee->get_amount());
        }

        // Tax code
        if(empty($order->get_taxes())){
            $tax_code = 'T0';
            $shipping_gross = $order->get_total_shipping();
            $shipping_tax = $order->get_shipping_tax();
            $shipping_net = $shipping_gross - $shipping_tax;
        }else {
            // Has VAT - if it's GB need to recalculate the tax
            $tax_code = 'T1';

            // Fix the $f_price (remove the VAT)
            $f_price = $f_price/1.2;

            $country_code = $order->get_shipping_country();
            if ($country_code === 'GB' || $country_code === 'IM' || $country_code === 'JE' || $country_code == 'GG') {
                $shipping_gross = $order->get_total_shipping();
                $shipping_net = round($shipping_gross/1.2, 2);
                $shipping_tax = $shipping_gross - $shipping_net;
            }else{
                $shipping_gross = round($order->get_total_shipping() + $order->get_shipping_tax(), 2);
                $shipping_tax = $order->get_shipping_tax();
                $shipping_net = $order->get_total_shipping();
            }
        }

        //Taken by info
        $taken_by_id = get_post_meta($order->get_id(), '_taken_by', true);
        if($taken_by_id){
            $taken_by = get_user_by('ID', $taken_by_id)->user_email;
        }else{
            $taken_by = '';
        }

        // handle ebay
        if(!empty(get_post_meta($order->get_ID(), '_ebay_order_number', true))){
            $taken_by = 'eBay';
        }

        //Extract white lettering info
        $msg = '';
        foreach ($order->get_items() as $item_id => $item_data) {
            //$product = $order->get_product_from_item($item_data);

            if(strpos($item_data->get_name(), 'White Letter')){
                $msg.= $item_data->get_name() . PHP_EOL;
            }
        }
        if(!empty($order->get_meta('_fbf_order_data_manufacturers'))){
            $msg.='Manufacturer(s): ' . $order->get_meta('_fbf_order_data_manufacturers') . PHP_EOL;
        }
        if(!empty($order->get_meta('_fbf_order_data_vehicles'))){
            $msg.='Vehicle(s): ' . $order->get_meta('_fbf_order_data_vehicles') . PHP_EOL;
        }
        $msg.='Sales Order Number: ' . $order->get_id() . PHP_EOL;
        $msg.='Customer name: ' . $order->get_formatted_billing_full_name() . PHP_EOL;
        $msg.= $order->get_customer_note() . PHP_EOL;

        //Name is either the company name or if not set the persons name
        $name = $order->get_billing_company()?:$order->get_formatted_billing_full_name();
        $shipping_name = $order->get_shipping_company()?:$order->get_formatted_shipping_full_name();

        //Shipping
        switch($order->get_shipping_method()) {
            case 'International economy':
                $shipping_method = 'FedEx International economy';
                break;
            case 'International economy freight':
                $shipping_method = 'FedEx International economy freight';
                break;
            case 'Click and collect':
            case 'Click and collect (1)':
            case 'Click and collect (2)':
            case 'Click and collect (3)':
            case 'Click and collect (4)':
            case 'Click and collect (5)':
                $shipping_method = 'Being Collected';
                break;
            case 'Standard':
            case 'Standard (1)':
            case 'Standard (2)':
            case 'Standard (3)':
            case 'Standard (4)':
            case 'Standard (5)':
                if($order->get_meta('_fbf_shipping_address_type') && $order->get_meta('_fbf_shipping_address_type')==='Commercial'){
                    $shipping_method = substr_replace($order->get_shipping_method(), ' commercial', 8, 0);
                }else{
                    $shipping_method = substr_replace($order->get_shipping_method(), ' residential', 8, 0);
                }
                break;
            case 'Free shipping on orders over £250':
            case 'Free shipping on orders over £250 (1)':
            case 'Free shipping on orders over £250 (4)':
            case 'Free shipping on orders over £250 (5)':
                if($order->get_meta('_fbf_shipping_address_type') && $order->get_meta('_fbf_shipping_address_type')==='Commercial'){
                    $shipping_method = substr_replace($order->get_shipping_method(), ' commercial', 34, 0);
                }else{
                    $shipping_method = substr_replace($order->get_shipping_method(), ' residential', 34, 0);
                }
            break;
            default:
                $shipping_method = $order->get_shipping_method();
                break;
        }

        // handle eBay
        if(!empty(get_post_meta($order->get_ID(), '_ebay_order_number', true))){
            $shipping_method = 'Standard residential';
        }

        if(strpos($order->get_shipping_method(), 'Retail')!==false){
            //It's retail fitting
            $is_retail_fitting = true;
            $delivery_gross = 0;
            $delivery_net = 0;
            $delivery_tax = 0;
            $unit_cost = 12.5;
            $shipping_line_gross = $order->get_shipping_total() + $order->get_shipping_tax();
            $shipping_line_net = $order->get_shipping_total();
            $shipping_line_tax = $order->get_shipping_tax();
            $shipping_line_qty = $shipping_line_net/$unit_cost;
        }else{
            $is_retail_fitting = false;
            $delivery_gross = $order->get_shipping_total() + $order->get_shipping_tax();
            $delivery_net = $order->get_shipping_total();
            $delivery_tax = $order->get_shipping_tax();
        }


        // Take off the Halfords cost if we are using On the drive
        if(get_post_meta($order->get_ID(), '_national_fitting_type', true)==='fit_on_drive'){
            $national_fitting_settings = $this->get_national_fitting_settings();
            $on_the_drive_cost = $national_fitting_settings['on_the_drive_cost'];
            $on_the_drive_inc_tax = $this->get_tax($national_fitting_settings['on_the_drive_cost']);
            $on_the_drive_tax = $on_the_drive_inc_tax - $on_the_drive_cost;
            $delivery_gross-= $on_the_drive_inc_tax;
            $delivery_net-= $on_the_drive_cost;
            $delivery_tax-= $on_the_drive_tax;

            $delivery_gross = round($delivery_gross, 2);
            $delivery_net = round($delivery_net, 2);
            $delivery_tax = round($delivery_tax, 2);
        }

        // ebay orders
        if(!empty(get_post_meta($order->get_ID(), '_ebay_order_number', true))){
            $msg.= 'eBay order number: ' . get_post_meta($order->get_ID(), '_ebay_order_number', true) . PHP_EOL;
        }


        // create XML feed
        $new_format = [
            // 'OrderNumber' => get_post_meta($order->id, '_order_number', true),
            'OrderNumber' => $order->get_id(),
            'OrderDate' => $date,
            'OrderAnalysis' => get_post_meta($order->get_id(), '_order_analysis', true),
            'SpecialInstructions' => $msg,
            'CustomerOrderRef' => $order->get_id(),
            'DeliveryMethod' => $shipping_method,
            'DeliveryGross' => $delivery_gross,
            'DeliveryNet' => $delivery_net,
            'DeliveryTax' => $delivery_tax,
            'DeliveryTaxCode' => $tax_code,
            'OrderGross' => $order->get_total(),
            'OrderNet' => $order->get_total() - $order->get_total_tax(),
            'OrderTax' => $order->get_total_tax(),
            'TakenBy' => $taken_by,
            'Customer' => [
                'eCommerceAccountNumber' => '',
                'StatementName' => $is_retail_fitting ? 'Web RETAIL only' : $name,
                'StatementAddress1' => $is_retail_fitting ? 'Unit 14 Drayton Manor Drive' : $order->get_billing_address_1(),
                'StatementAddress2' => $is_retail_fitting ? 'Alcester Road' : $order->get_billing_address_2(),
                'StatementTown' => $is_retail_fitting ? 'Stratford Upon Avon' : $order->get_billing_city(),
                'StatementCounty' => $is_retail_fitting ? 'Warwickshire' : $order->get_billing_state(),
                'StatementCountry' => $order->get_billing_country(),
                'StatementCountryCode' => $is_retail_fitting ? 'CV37 9RQ' : $order->get_billing_country(),
                'StatementPostcode' => $order->get_billing_postcode(),
                'StatementEmail' => $order->get_billing_email(),
                'StatementTelephone' => $is_retail_fitting ? '01789 774 884' : '',
                'InvoiceName' => $name,
                'InvoiceAddress1' => $order->get_billing_address_1(),
                'InvoiceAddress2' => $order->get_billing_address_2(),
                'InvoiceTown' => $order->get_billing_city(),
                'InvoiceCounty' => $order->get_billing_state(),
                'InvoiceCountry' => $order->get_billing_phone(),
                'InvoiceCountryCode' => $order->get_billing_phone(),
                'InvoicePostcode' => $order->get_billing_postcode(),
                'InvoiceEmail' => $order->get_billing_email(),
                'InvoiceTelephone' => $order->get_billing_phone(),
                'CustomerContact' => [
                    'Name' => $order->get_formatted_billing_full_name(),
                    'Email' => $order->get_billing_email(),
                    'Position' => '',
                    'Telephone' => $order->get_billing_phone(),
                    'Fax' => '',
                    'Mobile' => '',
                    'Extension' => '',
                    'Salutation' => ''
                ],
                'DeliveryAddress' => [
                    'Name' => $shipping_name,
                    'Contact' => $order->get_formatted_shipping_full_name(),
                    'Address1' => $order->get_shipping_address_1(),
                    'Address2' => $order->get_shipping_address_2(),
                    'Town' => $order->get_shipping_city(),
                    'County' => $order->get_shipping_state(),
                    'Country' => WC()->countries->get_countries()[$order->get_shipping_country()],
                    'CountryCode' => $order->get_shipping_country(),
                    'Postcode' => $order->get_shipping_postcode(),
                    'Email' => $order->get_billing_email(),
                    'Telephone' => $order->get_billing_phone(),
                    'DeliveryMethod' => $shipping_method
                ]
            ],
            'PricesAsNet' => 'true',
        ];

//        $payment_methods = WC()->payment_gateways()->get_available_payment_gateways();
//        ob_start();
//        print_r($payment_methods);
//        $msg = ob_get_clean();
//        mail('kevin@code-mill.co.uk', 'payment gateways', $msg);


        switch($order->get_payment_method()){
            case 'stripe':
                $payment_method = 'stripe';
                break;
            case 'ppcp-gateway':
                $payment_method = 'Paypal';
                break;
            case 'sagepaydirect':
                $card_type = $order->get_meta('_sage_card_type');
                if($card_type=='American Express'){
                    $payment_method = 'American Express';
                }else{
                    $payment_method = 'Sage Pay Credit Card';
                }
                break;
            case 'klarna_payments':
                $payment_method = 'Klarna';
                break;
            case 'cod':
                $payment_method = '';
                break;
            case 'boots_dekopay':
                $payment_method = 'Pay4Later';
                break;
            default:
                $payment_method = 'unrecognised';
                break;
        }

        // handle ebay
        if(!empty(get_post_meta($order->get_ID(), '_ebay_order_number', true))){
            $payment_method = 'eBay tgc';
        }

        if($payment_method!==''){
            $new_format['AmountPaid'] = $order->get_total();
            $new_format['Payments'] = [
                'SalesPayment' => [
                    'Description' => $payment_method,
                    'Amount' => $order->get_total()
                ]
            ];
        }

        $total_net = 0;
        $total_gross = 0;
        foreach ($order->get_items() as $item_id => $item_data) {
            $product = $order->get_product_from_item($item_data);
            $product_id = $product->get_parent_id()?:$product->get_id();

            /*$taxes = $order->get_taxes();
            $price_inc_tax = $product->get_regular_price();
            $price_exc_tax = $product->get_regular_price();

            if(!empty($taxes)){
                foreach($taxes as $tax){
                    $price_inc_tax+= ($product->get_regular_price()/100) * $tax->get_rate_percent();
                }
            }*/

            // Re-calculate
            $tax_pc = 0;
            if(!empty($order->get_taxes())){
                foreach($order->get_taxes() as $tax){
                    $tax_pc+= $tax->get_rate_percent();
                }
            }
            $product_net = $product->get_regular_price();
            $product_gross = $product_net + (($product_net/100) * $tax_pc);
            $line_net = $product_net * $item_data->get_quantity();
            $line_gross = $product_gross * $item_data->get_quantity();

            $total_net+= $line_net;
            $total_gross+= $line_gross;

            //$item_net = round($item_data->get_subtotal()/$item_data->get_quantity(), 2);
            //$item_tax = round($item_data->get_subtotal_tax()/$item_data->get_quantity(), 2);
            //$item_gross = $item_net + $item_tax;

            // skip loop if not product found
            if (!$product) {
                continue;
            }

            if(str_ends_with($product->get_sku(), '_white')){
                $sku = str_replace('_white', '', $product->get_sku());
            }else{
                $sku = $product->get_sku();
            }

            //Is it a tyre
            $cats = get_the_terms($product_id, 'product_cat');

            $item_a = [
                'Code' => $sku,
                'Quantity' => $item_data->get_quantity(),
                'eCommerceItemID' => $product->get_id(),
                'ItemGross' => $product_gross,
                'ItemNet' => $product_net,
                'TaxCode' => $tax_code
            ];

            if(!empty($cats)){
                foreach($cats as $cat){
                    if($cat->slug == 'alloy-wheel'||$cat->slug == 'steel-wheel'){
                        $wheel_items[] = $item_id;
                    }else if($cat->slug == 'tyre'){
                        $tyre_items[] = $item_id;

                        // Is it price matched
                        if(get_post_meta($product->get_id(), '_price_match', true)){
                            $item_a['Analysis'] = [
                                'L1' => 'true'
                            ];
                        }else{
                            $item_a['Analysis'] = [
                                'L1' => 'false'
                            ];
                        }
                    }
                }
            }

            // create array
            $items['SalesOrderLine'][] = $item_a;

            // Sort into shipping classes
            if($is_retail_fitting){
                $shipping_class = $product->get_shipping_class();
                $shipping_classes[$shipping_class]+=$item_data->get_quantity(); // Counts how many of each shipping class
            }
        }




        if($is_retail_fitting && !empty($shipping_classes)){
            foreach($shipping_classes as $ck => $cv){
                if($ck==='tyre'){
                    $net = 12.50;
                    $gross = 15.00;
                }else if($ck==='spacer'){
                    $net = 40.00;
                    $gross = 48.00;
                }
                if($ck==='tyre'||$ck==='spacer'){
                    $items['SalesOrderLine'][] = [
                        'Code' => 'FITTING',
                        'Quantity' => $cv,
                        'eCommerceItemID' => 'RETAIL_FITTING',
                        'ItemGross' => $gross,
                        'ItemNet' => $net,
                        'TaxCode' => $tax_code
                    ];
                }
            }
        }

        $total_net+= $new_format['DeliveryNet'];
        $total_gross+= $new_format['DeliveryGross'];
        $total_tax = $total_gross - $total_net;
        $new_format['OrderGross'] = $total_gross;
        $new_format['OrderNet'] = $total_net;
        $new_format['OrderTax'] = $total_tax;









        /*if($is_retail_fitting){
            $items['SalesOrderLine'][] = [
                'Code' => 'FITTING',
                'Quantity' => $shipping_line_qty,
                'eCommerceItemID' => 'RETAIL_FITTING',
                'ItemGross' => 15,
                'ItemNet' => 12.50,
                'TaxCode' => $tax_code
            ];
        }*/

        if($order->get_meta('_fbf_shipping_address_type')==='Commercial'){ // Checks that it's a commercial order
            if(empty($wheel_items)){ // If there are NOT any wheel items in the basket
                //Here if all items are tyres
                //Need to check here if items are all in stock with Southam
                $main_supplier_id = 88; //Micheldever??
                $i = 0;
                $instock_at_main_supplier = true;
                foreach($items['SalesOrderLine'] as $tyre){
                    $product_id = wc_get_product_id_by_sku($tyre['Code']);
                    $cat = get_the_terms($product_id, 'product_cat')[0]->slug;
                    if($cat!=='accessories'){
                        // Should be here if it's a wheel basically
                        $suppliers = get_post_meta($product_id, '_stockist_lead_times', true);
                        if(isset($suppliers[$main_supplier_id])){
                            if((int)$tyre['Quantity'] >= $suppliers[$main_supplier_id]['stock']){
                                $instock_at_main_supplier = false;
                            }
                        }else{
                            $instock_at_main_supplier = false;
                        }
                    }
                    $i++;
                }
            }else{
                $instock_at_main_supplier = false;
            }
        }else{
            $instock_at_main_supplier = false;
        }

        if($instock_at_main_supplier){
            //This is where we will add the XML to new_format
            foreach($items['SalesOrderLine'] as $k => $tyre){
                $product_id = wc_get_product_id_by_sku($tyre['Code']);
                $cat = get_the_terms($product_id, 'product_cat')[0]->slug;
                if($cat!=='accessories') {
                    $items['SalesOrderLine'][$k]['Direct'] = 'true';
                    $items['SalesOrderLine'][$k]['SelectedSupplier'] = 'SOUTHAMT';
                }
            }

            if(count($tyre_items)==count($items['SalesOrderLine'])) { //Checks that every item is a tyre
                $new_format['DeliveryMethod'] = 'Direct Delivery';
            }
        }

        // Radar tyres
        if(empty($wheel_items)){
            $only_radar = true;
            // Check for Radar tyres
            foreach($items['SalesOrderLine'] as $k => $tyre){
                $product_id = wc_get_product_id_by_sku($tyre['Code']);
                $brand_term = get_the_terms($product_id, 'pa_brand-name')[0];
                if(substr($brand_term->slug, 0, 5)!=='radar'){
                    $only_radar = false;
                }
            }

            if($only_radar){
                foreach($items['SalesOrderLine'] as $k => $tyre){
                    $suppliers = get_post_meta($product_id, '_stockist_lead_times', true);
                    $cheapest_supplier_cost = null;
                    $cheapest_supplier_name = null;
                    $cheapest_supplier_id = null;

                    foreach($suppliers as $sk => $s){
                        if($s['stock'] >= $tyre['Quantity']){
                            if(is_null($cheapest_supplier_cost)){
                                $cheapest_supplier_cost = $s['cost'];
                                $cheapest_supplier_name = $s['name'];
                                $cheapest_supplier_id = $sk;
                            }else{
                                if($s['cost'] < $cheapest_supplier_cost){
                                    $cheapest_supplier_cost = $s['cost'];
                                    $cheapest_supplier_name = $s['name'];
                                    $cheapest_supplier_id = $sk;
                                }
                            }
                        }
                    }

                    if(!is_null($cheapest_supplier_cost)){
                        $items['SalesOrderLine'][$k]['Direct'] = 'true';
                        $items['SalesOrderLine'][$k]['SelectedSupplier'] = $this->get_supplier_code($cheapest_supplier_id);
                        $items['SalesOrderLine'][$k]['SelectedSupplierCost'] = $cheapest_supplier_cost;
                    }
                }
            }
        }


        // Handle national fitting here
        if(get_post_meta($order->get_ID(), '_is_national_fitting', true)){
            $msg = '';
            // Part 1
            if(get_post_meta($order->get_ID(), '_national_fitting_type', true)==='fit_on_drive'){
                $fitting_method = 'National Fitting (On the drive)';

                // Need to set the delivery address to the Hub address which is in the hubs.xlxs sheet
                // Process the garages
                $filename = 'hubs.xlsx';
                if(function_exists('get_home_path')){
                    $filepath = get_home_path() . '../supplier/azure/garages/' . $filename;
                }else{
                    $filepath = ABSPATH . '../../supplier/azure/garages/' . $filename;
                }
                if(file_exists($filepath)) {
                    $reader = new Xlsx();
                    $reader->setReadDataOnly(true);
                    $spreadsheet = $reader->load($filepath);
                    $worksheet = $spreadsheet->getActiveSheet();
                    $hubs = $worksheet->toArray();

                    if($hub = $hubs[array_search($order->get_meta('_national_fitting_fod_hub_id'), array_column($hubs, 0))]){
                        $hub_addr_1 = $hub[1];
                        $hub_addr_2 = $hub[2];
                        $hub_town_city = $hub[3];
                        $hub_county = $hub[4];
                        $hub_postcode = $hub[5];
                        $new_format['Customer']['DeliveryAddress']['Address1'] = $hub_addr_1?:'';
                        $new_format['Customer']['DeliveryAddress']['Address2'] = $hub_addr_2?:'';
                        $new_format['Customer']['DeliveryAddress']['Town'] = $hub_town_city?:'';
                        $new_format['Customer']['DeliveryAddress']['County'] = $hub_county?:'';
                        $new_format['Customer']['DeliveryAddress']['Postcode'] = $hub_postcode?:'';
                    }
                }

                $msg.= 'Fitting address: ' . $order->get_formatted_shipping_address() . PHP_EOL . 'Halfords Booking reference: ' . $order->get_meta('_national_fitting_fod_booking_ref') . PHP_EOL;
                $msg.= sprintf('To be fitted to vehicle reg %s'.PHP_EOL, get_post_meta($order->get_ID(), '_national_fitting_reg_no', true));
                $msg = str_replace('<br/>', PHP_EOL, $msg);

            }else if(get_post_meta($order->get_ID(), '_national_fitting_type', true)==='garage'){
                $fitting_method = 'National Fitting (Garage)';

                // Adds message to comments - garage specific
                $msg.= sprintf('Please mark the goods for the attention of 4x4tyres.co.uk'.PHP_EOL.'To be fitted to vehicle reg %s'.PHP_EOL, get_post_meta($order->get_ID(), '_national_fitting_reg_no', true));

                // $contracts_agreed_col = 104; //Column DA
                if(!$garage_a->contracts_agreed){
                    $msg.= 'Contracts to be signed.' . PHP_EOL;
                }
            }

            // Set the delivery method
            $new_format['DeliveryMethod'] = $fitting_method;
            $new_format['Customer']['DeliveryAddress']['DeliveryMethod'] = $fitting_method;

            // Adds message to comments
            $msg.= sprintf('Selected fitting date: %s', $readable_date);
            $new_format['SpecialDeliveryInstructions'] = $msg;
            $new_format['SpecialInstructions'] = $new_format['SpecialInstructions'] . PHP_EOL . $msg;

            if(count($tyre_items)){
                // Here if there are tyres in order
                // Look for Micheldever or Stapletons
                $micheldever = 88;
                $stapletons = 89;
                $is_mts_supplier = false;
                foreach($items['SalesOrderLine'] as $k => $tyre){
                    $product_id = wc_get_product_id_by_sku($tyre['Code']);
                    $suppliers = get_post_meta($product_id, '_stockist_lead_times', true);
                    if(isset($suppliers[$stapletons])||isset($suppliers[$micheldever])){
                        if(isset($suppliers[$micheldever])&&(int)$tyre['Quantity']<=$suppliers[$micheldever]['stock']){
                            $items['SalesOrderLine'][$k]['Direct'] = 'true';
                            $items['SalesOrderLine'][$k]['SelectedSupplier'] = 'NTF_ONLY';
                            $items['SalesOrderLine'][$k]['SelectedSupplierCost'] = $suppliers[$micheldever]['cost'];
                        }else if(isset($suppliers[$stapletons])&&(int)$tyre['Quantity']<=$suppliers[$stapletons]['stock']){
                            $items['SalesOrderLine'][$k]['Direct'] = 'true';
                            $items['SalesOrderLine'][$k]['SelectedSupplier'] = 'STPTYRES';
                        }
                    }
                    if(isset($suppliers[$micheldever])){
                        $is_mts_supplier = true;
                    }
                }

                // Handle the OrderOnHold status
                if(get_post_meta($order->get_ID(), '_national_fitting_type', true)==='garage'){
                    $contracts_agreed_col = 104; //Column DA
                    if(!$garage_a->contracts_agreed){
                        $new_format['OrderOnHold'] = 'true';
                    }else{
                        if($is_mts_supplier){
                            $new_format['OrderOnHold'] = 'false';
                        }
                    }
                }
            }

            // Part 2
            /*$wheel_tyre_size_mapping = [
                'size_14' => 47,
                'size_15' => 48,
                'size_16' => 49,
                'size_17' => 50,
                'size_18' => 51,
                'size_19' => 52,
                'size_20' => 53,
                'size_21' => 54,
                'size_22' => 55,
                'size_23' => 56,
            ];*/
            $garage_supplier_name_col = 76;
            $fitting_sizes = [];
            foreach($items['SalesOrderLine'] as $k => $line){
                $product_id = wc_get_product_id_by_sku($line['Code']);
                $cat = get_the_terms($product_id, 'product_cat')[0]->slug;
                if($cat==='tyre'){
                    $tyre_size_term = get_the_terms($product_id, 'pa_tyre-size')[0];
                    $tyre_size = explode('-', $tyre_size_term->slug)[0];
                    if(!isset($fitting_sizes[(string)$tyre_size]['tyre'])){
                        $fitting_sizes[(string)$tyre_size]['tyre'] = (int)$line['Quantity'];
                    }else{
                        $fitting_sizes[(string)$tyre_size]['tyre']+= (int)$line['Quantity'];
                    }
                }else if($cat==='steel-wheel'||$cat==='alloy-wheel'){
                    $wheel_size_term = get_the_terms($product_id, 'pa_wheel-size')[0];
                    $wheel_size = explode('-', $wheel_size_term->slug)[0];
                    if(!isset($fitting_sizes[(string)$wheel_size]['wheel'])){
                        $fitting_sizes[(string)$wheel_size]['wheel'] = (int)$line['Quantity'];
                    }else{
                        $fitting_sizes[(string)$wheel_size]['wheel']+= (int)$line['Quantity'];
                    }
                }
            }
            if(get_post_meta($order->get_ID(), '_national_fitting_type', true)==='garage'||get_post_meta($order->get_ID(), '_national_fitting_type', true)==='fit_on_drive'){
                if(!empty($fitting_sizes)){
                    foreach($fitting_sizes as $fk => $fitting_size){
                        if(isset($fitting_size['tyre'])&&isset($fitting_size['wheel'])){
                            $qty = max($fitting_size['tyre'], $fitting_size['wheel']);
                        }else if(isset($fitting_size['tyre'])){
                            $qty = $fitting_size['tyre'];
                        }else{
                            $qty = $fitting_size['wheel'];
                        }
                        //$col = $wheel_tyre_size_mapping['size_' . $fk];
                        $fitting_sku = get_object_vars($garage_a)['ts_' . $fk];

                        $price_code_cols = [
                            'product_code',
                            'product_code_2',
                            'product_code_3',
                        ];

                        $price_cols = [
                            'cost',
                            'cost_2',
                            'cost_3',
                        ];

                        foreach($price_code_cols as $pci => $pc){
                            if($garage_a->{$pc} == $fitting_sku){
                                $fitting_price = $garage_a->{$price_cols[$pci]};
                                break;
                            }
                        }
                        $items['SalesOrderLine'][] = [
                            'Code' => $fitting_sku,
                            'Quantity' => $qty,
                            'eCommerceItemID' => 'NATIONAL_FITTING_' . $fk,
                            'TaxCode' => $tax_code,
                            'Direct' => 'true',
                            'SelectedSupplier' => $garage_a->account_number,
                            'SelectedSupplierCost' => $fitting_price?:'0',
                        ];
                    }
                }
            }

            if(get_post_meta($order->get_ID(), '_national_fitting_type', true)==='fit_on_drive'){
                // Simply add a line item for HME
                $zones = \WC_Shipping_Zones::get_zones();
                $settings = $this->get_national_fitting_settings();
                $rates = \WC_tax::find_rates(['country' => $order->get_shipping_country()]);
                $total_tax_rate = 0;
                foreach ($rates as $rate) {
                    $total_tax_rate += floatval($rate['rate']);
                }
                $hme_net = round($settings['on_the_drive_cost'], 2);
                $hme_gross = round( $hme_net * (1 + ($total_tax_rate / 100)), 2 );
                $items['SalesOrderLine'][] = [
                    'Code' => 'HME',
                    'Quantity' => 1,
                    'ItemGross' => $hme_gross,
                    'ItemNet' => $hme_net,
                    'TaxCode' => $tax_code,
                    'Direct' => 'true'
                ];

                // Now need to add back onto OrderGross, OrderNet and OrderTax to balance out
                $new_format['OrderGross']+= $hme_gross;
                $new_format['OrderNet']+= $hme_net;
                $new_format['OrderTax']+= $hme_gross - $hme_net;
            }
        }else{
            // It's not national fitting
            if(count($tyre_items)){
                $micheldever = 88;
                foreach($items['SalesOrderLine'] as $k => $tyre){
                    $product_id = wc_get_product_id_by_sku($tyre['Code']);
                    $suppliers = get_post_meta($product_id, '_stockist_lead_times', true);
                    $brand_term = get_the_terms($product_id, 'pa_brand-name')[0];
                    if(isset($suppliers[$micheldever])&&(int)$tyre['Quantity']<=$suppliers[$micheldever]['stock']){
                        $items['SalesOrderLine'][$k]['SelectedSupplier'] = $this->get_supplier_code((string)$micheldever);
                        $items['SalesOrderLine'][$k]['SelectedSupplierCost'] = $suppliers[$micheldever]['cost'];
                    }
                }
            }
        }
        $new_format['Lines'] = $items;

        //Add delivery cost for FedEx orders
        if(strpos($shipping_method, 'FedEx')!==false){
           $new_format['DeliveryCost'] = $shipping_gross;
        }

        if($f_price > 0){
            $order_from = get_post_meta($order->get_ID(), '_order_from_quote', true);
            $sales_id = get_post_meta($order_from, '_taken_by', true);

            //Add this in case $sales_id is empty which can cause sales_discount_unknown and OW errors
            if(empty($sales_id)){
                $sales_id =  get_post_meta($order->get_ID(), '_taken_by', true);
            }

            switch($sales_id){
                case 1:
                    $f_name = 'sales_discount_kp';
                    break;
                case 21:
                    $f_name = 'sales_discount_lb';
                    break;
                case 22:
                    $f_name = 'sales_discount_ct';
                    break;
                case 4227:
                    $f_name = 'sales_discount_dp';
                    break;
                case 64:
                    $f_name = 'sales_discount_im';
                    break;
                case 8248:
                    $f_name = 'sales_discount_kf';
                    break;
                case 8739:
                    $f_name = 'sales_discount_gh';
                    break;
                case 21795:
                    $f_name = 'sales_discount_mh';
                    break;
                case 43043:
                    $f_name = 'sales_discount_fd';
                    break;
                case 43044:
                    $f_name = 'sales_discount_ar';
                    break;
                case 47173:
                    $f_name = 'sales_discount_jb';
                    break;
                case 47796:
                    $f_name = 'sales_discount_hn';
                    break;
                case 50009:
                    $f_name = 'sales_discount_lh';
                    break;
                case 52604:
                    $f_name = 'sales_discount_cg';
                    break;
                default:
                    $f_name = 'sales_discount_unknown';
                    break;
            }

            /*$new_format['Dissurs'] = [
                'SalesDissur' => [
                    'Description' => $f_name,
                    'Price' => $f_price,
                    'TaxCode' => $tax_code,
                    'GrossDiscount' => 'true'
                ]
            ];*/
        }

        if($c_price > 0) {
            // If the coupon name is one of the
            if (strpos($c_name, 'checkdisc_') !== false) {
                switch ($c_name) {
                    case 'checkdisc_1':
                        $c_name = 'sales_discount_kp';
                        break;
                    case 'checkdisc_21':
                        $c_name = 'sales_discount_lb';
                        break;
                    case 'checkdisc_22':
                        $c_name = 'sales_discount_ct';
                        break;
                    case 'checkdisc_4227':
                        $c_name = 'sales_discount_dp';
                        break;
                    case 'checkdisc_64':
                        $c_name = 'sales_discount_im';
                        break;
                    case 'checkdisc_8248':
                        $c_name = 'sales_discount_kf';
                        break;
                    case 'checkdisc_8739':
                        $c_name = 'sales_discount_gh';
                        break;
                    case 'checkdisc_21795':
                        $c_name = 'sales_discount_mh';
                        break;
                    case 'checkdisc_43043':
                        $c_name = 'sales_discount_fr';
                        break;
                    case 'checkdisc_43044':
                        $c_name = 'sales_discount_ar';
                        break;
                    case 'checkdisc_47173':
                        $c_name = 'sales_discount_jb';
                        break;
                    case 'checkdisc_47796':
                        $c_name = 'sales_discount_hn';
                        break;
                    case 'checkdisc_50009':
                        $c_name = 'sales_discount_lh';
                        break;
                    case 'checkdisc_52604':
                        $c_name = 'sales_discount_cg';
                        break;
                    default:
                        $c_name = 'sales_discount_unknown';
                        break;
                }
            }
        }

        if($c_price > 0 && $f_price > 0){
            $new_format['Dissurs']['SalesDissur'] = [
                [
                    'Description' => $c_name,
                    'Price' => $c_price,
                    'TaxCode' => $tax_code,
                    'GrossDiscount' => 'false'
                ],
                [
                    'Description' => $f_name,
                    'Price' => $f_price,
                    'TaxCode' => $tax_code,
                    'GrossDiscount' => 'false'
                ]
            ];
        }else if($f_price > 0){
            $new_format['Dissurs'] = [
                'SalesDissur' => [
                    'Description' => $f_name,
                    'Price' => $f_price,
                    'TaxCode' => $tax_code,
                    'GrossDiscount' => 'false'
                ]
            ];
        }else if($c_price > 0){
            $new_format['Dissurs'] = [
                'SalesDissur' => [
                    'Description' => $c_name,
                    'Price' => $c_price,
                    'TaxCode' => $tax_code,
                    'GrossDiscount' => 'false'
                ]
            ];
        }


        // Remove $c_price and $f_price from totals
        if($c_price > 0 || $f_price > 0){
            $total_discount_net = $c_price + $f_price; // Note it is exc. of tax
            if($tax_pc > 0){
                $total_discount_gross = (($total_discount_net/100) * $tax_pc) + $total_discount_net;
            }else{
                $total_discount_gross = $total_discount_net;
            }

            $new_format['OrderGross'] = $new_format['OrderGross'] - $total_discount_gross;
            $new_format['OrderNet'] = $new_format['OrderNet'] - $total_discount_net;
            $new_format['OrderTax'] = $new_format['OrderGross'] - $new_format['OrderNet'];
        }

        // Set promise date
        if($promise_date){
            $new_format['PromisedDate'] = $promise_date->format('Y-m-d\TH:i:s');
        }

        return $new_format;
    }

    function sv_wc_xml_export_order_line_item($item_format, $order, $item)
    {

        $product = is_callable(array($item, 'get_product')) ? $item->get_product() : $order->get_product_from_item($item);

        // Tax code
        if(empty($order->get_taxes())){
            $tax_code = 'T0';
        }else{
            $tax_code = 'T1';
        }

        // bail if this line item isn't a product
        if (!($product && $product->exists())) {
            return $item_format;
        }

        $arr = array(
            'Code'               => $product->get_sku(),
            'Quantity'          => $item['qty'],
            'eCommerceItemID'      => $product->get_id(),
            'ItemGross'            => round(wc_get_price_including_tax($product), 2),
            'ItemNet'            => round(wc_get_price_excluding_tax($product), 2),
            'TaxCode'             => $tax_code
        );

        return $arr;
    }

    // public function sv_wc_xml_export_output($generated_xml, $xml_array, $orders, $ids, $export_format)
    // {

    // 	return $generated_xml;
    // }

    // function sv_wc_xml_export_order_format($orders_format, $orders)
    // {
    // 	$orders_format = array(
    // 		'OrderList' => array(
    // 			'@attributes' => array(
    // 				'StoreName' => get_home_url(),
    // 			),
    // 			'Order' => $orders,
    // 		),
    // 	);
    // 	return $orders_format;
    // }



    function sv_wc_xml_export_line_item_addons($item_format, $order, $item)
    {
        $product = is_callable(array($item, 'get_product')) ? $item->get_product() : $order->get_product_from_item($item);
        // bail if this line item isn't a product
        // if (!($product && $product->exists())) {
        // 	return $item_format;
        // }
        // $addons = [];
        // // get the possible add-ons for this line item to check if they're in the order
        // if (is_callable('WC_Product_Addons_Helper::get_product_addons')) {
        // 	$addons = WC_Product_Addons_Helper::get_product_addons($product->get_id());
        // } elseif (is_callable('get_product_addons')) {
        // 	$addons = get_product_addons($product->get_id());
        // }
        // $product_addons = sv_wc_xml_export_get_line_item_addons($item, $addons);
        // if (!empty($product_addons)) {
        // 	$item_format['AddOn'] = $product_addons;
        // }

        $item_format = [];
    }

    private function get_national_fitting_settings()
    {
        $zones = \WC_Shipping_Zones::get_zones();
        foreach ($zones as $zone) {
            if ($zone['zone_name'] === 'UK') {
                foreach ($zone['shipping_methods'] as $shipping_method) {
                    if ($shipping_method->id === 'national_fitting') {
                        return $shipping_method->instance_settings;
                    }
                }
            }
        }
    }

    private function get_tax($cost)
    {
        //Get the tax - realistically always gonna be GB but this is for future proofing
        $country = WC()->customer->get_country()?:'GB';
        $rates = \WC_tax::find_rates(['country' => $country]);
        if(!empty($rates)){
            $orig_cost = (float) $cost;

            // Add tax to the on the drive cost
            $tax_amount = 0;
            foreach($rates as $rate){
                $multiplier = $rate['rate']/100;
                $tax_amount+= $orig_cost * $multiplier;
            }
            $cost+=$tax_amount;
            $cost = round($cost, 2);
        }
        return $cost;
    }

    private function get_supplier_code($id)
    {
        $supplier = null;
        switch($id){
            case '182':
                $supplier = 'TYRESPOT';
                break;
            case '32':
                $supplier = 'EDENTYRE';
                break;
            case '88':
                $supplier = 'SOUTHAMT';
                break;
            default:
                break;
        }
        return $supplier;
    }
}


 // rename OrderLineItem  > SalesOrderLine>
