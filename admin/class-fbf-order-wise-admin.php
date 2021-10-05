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

        // require_once(ABSPATH . '/wp-content/plugins/woocommerce-customer-order-xml-export-suite/includes/class-wc-customer-order-xml-export-suite-generator.php');

        // format date
        //$datetime = new DateTime($order->order_date);
        $datetime_o = $order->get_date_created();
        $date = $datetime_o->format("Y-m-d\TH:i:s");

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


        //Extract white lettering info
        $msg = '';
        foreach ($order->get_items() as $item_id => $item_data) {
            //$product = $order->get_product_from_item($item_data);

            if(strpos($item_data->get_name(), 'White Lettering')){
                $msg.= $item_data->get_name() . '\r\n';
            }
        }
        if(!empty($order->get_meta('_fbf_order_data_manufacturers'))){
            $msg.='Manufacturer(s): ' . $order->get_meta('_fbf_order_data_manufacturers') . '\r\n';
        }
        if(!empty($order->get_meta('_fbf_order_data_vehicles'))){
            $msg.='Vehicle(s): ' . $order->get_meta('_fbf_order_data_vehicles') . '\r\n';
        }
        $msg.= $order->get_customer_note();

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

        if($payment_method!==''){
            $new_format['AmountPaid'] = $order->get_total();
            $new_format['Payments'] = [
                'SalesPayment' => [
                    'Description' => $payment_method,
                    'Amount' => $order->get_total()
                ]
            ];
        }

        // Line Items
        $tyre_items = [];
        $shipping_classes = [];
        $promise_date = new DateTime();
        $promise_date->modify('+3 day');
        foreach ($order->get_items() as $item_id => $item_data) {
            $product = $order->get_product_from_item($item_data);
            $product_id = $product->get_parent_id()?:$product->get_id();
            $taxes = $order->get_taxes();
            $price_inc_tax = $product->get_regular_price();
            $price_exc_tax = $product->get_regular_price();
            if(!empty($taxes)){
                foreach($taxes as $tax){
                    $price_inc_tax+= ($product->get_regular_price()/100) * $tax->get_rate_percent();
                }
            }

            // skip loop if not product found
            if (!$product) {
                continue;
            }

            // create array
            $items['SalesOrderLine'][] = [
                'eCommerceCode' => $product->get_sku(),
                'Code' => $product->get_sku(),
                'Quantity' => $item_data['qty'],
                'eCommerceItemID' => $product->get_id(),
                'ItemGross' => round($price_inc_tax, 2),
                'ItemNet' => round($price_exc_tax, 2),
                'TaxCode' => $tax_code
            ];

            //Is it a tyre
            $cats = get_the_terms($product_id, 'product_cat');

            if(!empty($cats)){
                foreach($cats as $cat){
                    if($cat->slug == 'tyre'){
                        $tyre_items[] = $item_id;
                    }
                }
            }

            // Sort into shipping classes
            if($is_retail_fitting){
                $shipping_class = $product->get_shipping_class();
                $shipping_classes[$shipping_class]+=$item_data->get_quantity(); // Counts how many of each shipping class
            }

            // Get promised date
            if(!empty($product->get_meta('_expected_back_in_stock_date')) && $product->get_stock_quantity() < 0){ // If it's less than 0 - we can assume that customer has ordered more than were in stock
                $product_promise_date = new DateTime($product->get_meta('_expected_back_in_stock_date'));
                $product_promise_date->modify('+7 day');

                // if meta date is earlier than current day, treat it as empty
                $today = new DateTime();
                if($product_promise_date < $today){
                    $product_promise_date = $today->modify('+60 day');
                }
            }else if(empty($product->get_meta('_expected_back_in_stock_date')) && $product->get_stock_quantity() < 0){
                $product_promise_date = new DateTime();
                $product_promise_date->modify('+60 day');
            }else{
                $product_promise_date = new DateTime();
                $product_promise_date->modify('+3 day');
            }
            if($product_promise_date >= $promise_date){
                $promise_date = $product_promise_date;
                $new_format['PromisedDate'] = str_replace('+0000', '', $promise_date->format(DateTimeInterface::ISO8601));
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
                        'eCommerceCode' => 'FITTING',
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






        /*if($is_retail_fitting){
            $items['SalesOrderLine'][] = [
                'eCommerceCode' => 'FITTING',
                'Code' => 'FITTING',
                'Quantity' => $shipping_line_qty,
                'eCommerceItemID' => 'RETAIL_FITTING',
                'ItemGross' => 15,
                'ItemNet' => 12.50,
                'TaxCode' => $tax_code
            ];
        }*/

        if(strpos($shipping_method, 'Standard commercial')!==false){ // Checks that it's a commercial order
            if(count($tyre_items)==count($items['SalesOrderLine'])){ //Checks that every item is a tyre
                //Here if all items are tyres
                //Need to check here if items are all in stock with Southam
                $main_supplier_id = 88; //Micheldever??
                $i = 0;
                $instock_at_main_supplier = true;
                foreach($items['SalesOrderLine'] as $tyre){
                    $product_id = wc_get_product_id_by_sku($tyre['eCommerceCode']);
                    $suppliers = get_post_meta($product_id, '_stockist_lead_times', true);
                    if(isset($suppliers[$main_supplier_id])){
                        if((int)$tyre['Quantity'] >= $suppliers[$main_supplier_id]['stock']){
                            $instock_at_main_supplier = false;
                        }
                    }else{
                        $instock_at_main_supplier = false;
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
                $items['SalesOrderLine'][$k]['Direct'] = 'true';
                $items['SalesOrderLine'][$k]['SelectedSupplier'] = 'SOUTHAMT';
            }
            $new_format['DeliveryMethod'] = 'Direct Delivery';
        }


        $new_format['Lines'] = $items;

        //Add delivery cost for FedEx orders
        if(strpos($shipping_method, 'FedEx')!==false){
           $new_format['DeliveryCost'] = $shipping_gross;
        }

        /*if($c_price > 0){
            // If the coupon name is one of the
            if(strpos($c_name, 'checkdisc_')!==false){
                switch($c_name){
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
                    default:
                        $c_name = 'sales_discount_unknown';
                        break;
                }
            }else if(strpos($c_name, 'custdisc_')!==false){
                // get taken by meta on original quote
                $order_from = get_post_meta($order->get_ID(), '_order_from_quote', true);
                $sales_id = get_post_meta($order_from, '_taken_by', true);
                switch($sales_id){
                    case 1:
                        $c_name = 'sales_discount_kp';
                        break;
                    case 21:
                        $c_name = 'sales_discount_lb';
                        break;
                    case 22:
                        $c_name = 'sales_discount_ct';
                        break;
                    case 4227:
                        $c_name = 'sales_discount_dp';
                        break;
                    case 64:
                        $c_name = 'sales_discount_im';
                        break;
                    default:
                        $c_name = 'sales_discount_unknown';
                        break;
                }
            }

            $new_format['Dissurs'] = [
                'SalesDissur' => [
                    'Description' => $c_name,
                    'Price' => $c_price,
                    'TaxCode' => $tax_code,
                    'GrossDiscount' => 'false'
                ]
            ];
        }*/



        if($f_price > 0){
            $order_from = get_post_meta($order->get_ID(), '_order_from_quote', true);
            $sales_id = get_post_meta($order_from, '_taken_by', true);
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
            'eCommerceCode'     => $product->get_sku(),
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
}


 // rename OrderLineItem  > SalesOrderLine>
