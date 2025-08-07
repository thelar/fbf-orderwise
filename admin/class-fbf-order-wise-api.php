<?php


class Fbf_Order_Wise_Api
{

    private $version;
    private $plugin;



    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('parse_request', array($this, 'endpoint'), 0);
        add_action('init', array($this, 'add_endpoint'));
    }

    public function enqueue_styles()
    { }

    public function enqueue_scripts()
    { }

    // /api/v2/orderwise_export
    // /api/v2/orderwise_success

    public function endpoint()
    {
        global $wp;

        $endpoint_vars = $wp->query_vars;

        // if endpoint
        if ($wp->request == 'api/v2/orderwise_export') {
            // Your own function to process end pint
            $this->processEndPointXML($_REQUEST);
            exit;
        } elseif ($wp->request == 'api/v2/orderwise_success') {
            // Your own function to process end pint
            $this->processEndPointResponse($_REQUEST);
            exit;
        } elseif ($wp->request == 'api/v2/orderwise_dispatch') {
            // Your own function to process end pint
            $this->processEndPointDispatch($_REQUEST);
            exit;
        }
    }


    public function add_endpoint()
    {

        add_rewrite_endpoint('orderwise', EP_PERMALINK | EP_PAGES, true);
    }

    public function processEndPointXML($request)
    {
        global $wpdb;
        $endpoint = 'orderwise_export';
        $table_name = $wpdb->prefix . 'fbf_orderwise_log';
        $inserted = $wpdb->insert(
            $table_name,
            [
                'starttime' => date('Y-m-d H:i:s', time()),
                'endpoint' => $endpoint,
                'log' => json_encode($request)
            ]
        );
        if($inserted){
            $response_update = $wpdb->update(
                $table_name,
                [
                    'response' => 'OK'
                ],
                [
                    'id' => $wpdb->insert_id
                ]
            );
        }

        // auth checks
        // get the latest export id
        $export_id = $this->getLatestExportId();

        if(!$export_id) {
            echo 'No Exports Found';
            exit;
        }


        // get export
        $export = wc_customer_order_csv_export_get_export(wc_clean($export_id));
        // check export
        if (!$export) {
            echo 'Error: Export not found';
            exit;
        }

        $dom = new DOMDocument();
        $dom->loadXML($export->get_output());
        $root = $dom->documentElement;
        $orders = $root->getElementsByTagName('SalesOrder');
        $ordersToDelete = [];
        foreach($orders as $order){
            $id = $order->getElementsByTagName('OrderNumber')->item(0)->textContent;
            $order_wc = wc_get_order($id);
            $status = $order_wc->get_status();
            if($status!=='processing'&&$status!=='on-backorder'){
                $ordersToDelete[] = $order;
            }
        }
        foreach($ordersToDelete as $node){
            $node->parentNode->removeChild($node);
        }
        $xml = $dom->saveXML();

        /*echo '<pre>';
        var_dump($xml);
        echo '</pre>';
        die();*/

        $output_type = $export->get_output_type();

        $filename = $export->get_filename();

        // we are intentionally using text/xml here to prevent a console warning
        $content_type = 'text/xml';

        header('Content-type: ' . $content_type);
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        /*$file_size = $export->get_file_size();

        if ($file_size && 0 < $file_size) {
            header('Content-Length: ' . $file_size);
        }*/

        $output_resource = fopen('php://output', 'w');
        //echo $xml;

        //$export->stream_output_to_resource($output_resource);
        echo $dom->saveXML();

        fclose($output_resource);
    }

    public function processEndPointResponse()
    {
        global $wpdb;
        $endpoint = 'orderwise_success';
        $table_name = $wpdb->prefix . 'fbf_orderwise_log';
        $inserted = $wpdb->insert(
            $table_name,
            [
                'starttime' => date('Y-m-d H:i:s', time()),
                'endpoint' => $endpoint,
                'log' => $_POST['ExportData']
            ]
        );

        try {
            // dev
            // $post_received = '12345~17~19';

            // receive POST var of tilda separated order numbers
            $post_received = $_POST['ExportData'];

            // create array from POST
            $result = [];
            foreach (explode('~', $post_received) as $order_id) {

                // exit if not
                if (!$order_id) {
                    return;
                }

                $order = wc_get_order($order_id);

                // mark the received order numbers as status == processing
                if ($order) {
                    $order_status = $order->get_status();
                    if($order_status==='proccessing'){
                        $order->update_status('imported');
                        $completed[] = $order_id;
                        $response['completed'][] = $order_id;
                    }else if($order_status==='on-backorder'){
                        $order->update_status('awaiting-bos');
                        $completed[] = $order_id;
                        $response['completed'][] = $order_id;
                    }
                } else {
                    $failed[] = $order_id;
                    $response['failed'][] = $order_id;
                }
                $response['status'] = 'OK';
            }

        } catch (exception $e) {
            //code to handle the exception
            $response['status'] = 'error';
            $response['error'] = $e->getMessage();
        }
        ob_start();
        print_r($response);
        $response_text = ob_get_clean();

        if($inserted){
            $response_update = $wpdb->update(
                $table_name,
                [
                    'response' => $response_text
                ],
                [
                    'id' => $wpdb->insert_id
                ]
            );
        }
    }

    public function processEndPointDispatch()
    {
        global $wpdb;
        $endpoint = 'orderwise_dispatch';
        $table_name = $wpdb->prefix . 'fbf_orderwise_log';
        $inserted = $wpdb->insert(
            $table_name,
            [
                'starttime' => date('Y-m-d H:i:s', time()),
                'endpoint' => $endpoint,
                'log' => $_POST['ExportData']
            ]
        );

        ob_start();

        try {
            // receive POST var of tilda separated order numbers
            $post_received = $_POST['ExportData'];
            $errors = [];
            $success = [];

            $xml = new SimpleXMLElement($post_received);
            foreach($xml->order as $orderxml){
                $order_num = (string)$orderxml->orderNo;
                $order_status = (string)$orderxml->orderStatus;

                $order = wc_get_order($order_num);
                if(!$order){
                    $errors[$order_num][] = 'No such orderNo exists';
                    break;
                }else{
                    $success[$order_num][] = 'Order exists';
                }

                switch($order_status){
                    case 'Awaiting despatch':
                    case 'On hold':
                    case 'Partially despatched':
                        if($order->get_status()!=='awaiting-despatch'&&$order->get_status()!=='awaiting-bos'){
                            if($order->update_status('awaiting-despatch')){
                                $success[$order_num][] = 'Order status updated to ' . $order_status;
                            }else{
                                $errors[$order_num][] = 'Could not update status to ' . $order_status;
                            }

                            /*if(!empty($this->has_delivery($orderxml->deliveries))){
                                $order->add_order_note($this->get_delivery_note($orderxml->deliveries));
                            }*/

                            if(!empty($this->has_delivery($orderxml->deliveries))){
                                $order->add_order_note($this->get_delivery_note($orderxml->deliveries), true);
                            }

                        }else{
                            $errors[$order_num][] = 'Order status is already awaiting-despatch';
                        }
                        break;
                    case 'Completed':
                        $is_national_fitting = get_post_meta($order->get_id(), '_is_national_fitting', true);
                        if(!$is_national_fitting){ // Only send the delivery email if it's not a Fitting order
                            if($order->get_status()!=='completed'){
                                if($order->update_status('completed')){
                                    $success[$order_num][] = 'Order status updated to ' . $order_status;

                                    // Set the _delivery_info meta for eBay orders only
                                    if(get_post_meta($order->get_id(), '_ebay_order_number', true)){

                                        //update_post_meta($order->get_id(), '_delivery_info', $orderxml);

                                        if (is_plugin_active('fbf-ebay-packages/fbf-ebay-packages.php')) {
                                            require_once plugin_dir_path(WP_PLUGIN_DIR . '/fbf-ebay-packages/fbf-ebay-packages.php') . 'includes/class-fbf-ebay-packages-list-item.php';
                                            $item = new Fbf_Ebay_Packages_List_Item(null, null);
                                            $fulfillment = $item->fulfill_order($orderxml, get_post_meta($order->get_id(), '_ebay_order_number', true));
                                        }

                                        $subject = sprintf('OrderWise eBay fulfillment report for order: %s', $order->get_id());
                                        $from = 'website@4x4tyres.co.uk';
                                        $to = 'kevin.price-ward@4x4tyres.co.uk,josh.barbor@4x4tyres.co.uk';

                                        $headers = "From: 4x4 Website <" . $from . '>' . PHP_EOL;
                                        $headers.= "Reply-To: " . $from . PHP_EOL;
                                        $headers .= "MIME-Version: 1.0" . PHP_EOL;
                                        $headers .= "Content-Type: text/html; charset=ISO-8859-1" . PHP_EOL;

                                        $message = sprintf('<h1>eBay order number: <strong>%s</strong></h1>', get_post_meta($order->get_id(), '_ebay_order_number', true));

                                        if($fulfillment){
                                            ob_start();
                                            echo '<pre>';
                                            print_r($fulfillment);
                                            echo '</pre>';
                                            $message.= ob_get_clean();
                                        }else{
                                            $message.= sprintf('<p>%s</p>', '$fulfillment not set');
                                        }
                                        wp_mail($to, $subject, $message, $headers);
                                    }
                                }else{
                                    $errors[$order_num][] = 'Could not update status to ' . $order_status;
                                }


                                if(!empty($this->has_delivery($orderxml->deliveries))){
                                    // Send out the delivery email here
                                    if($this->get_courier_name($orderxml->deliveries, $order)==='DX'||$this->get_courier_name($orderxml->deliveries, $order)==='APC'){
                                        $email_new_order = WC()->mailer()->get_emails()['WC_Order_Delivery'];
                                        $email_new_order->set_tracking($this->get_delivery_note($orderxml->deliveries));
                                        $email_new_order->set_tracking_link($this->get_tracking_links($orderxml->deliveries, $order));
                                        $email_new_order->set_delivery_logo($this->get_delivery_logo($orderxml->deliveries, $order));
                                        $email_new_order->set_help_text($this->get_help_text($orderxml->deliveries, $order));
                                        $email_new_order->set_courier_to($this->get_courier_name($orderxml->deliveries, $order));
                                        $email_new_order->set_test_mode(false);

                                        // Sending the new Order email notification for an $order_id (order ID)
                                        $email_new_order->trigger( $order->get_order_number() );
                                    }
                                    $order->add_order_note($this->get_delivery_note($orderxml->deliveries, true), false);
                                }else{
                                    // Here when we need to send an 'order complete' email for mailorder orders but we don't have tracking info - e.g. when the order is fulfilled by a supplier
                                    $email_new_order = WC()->mailer()->get_emails()['WC_Order_Complete'];
                                    $email_new_order->set_test_mode(false);
                                    $email_new_order->trigger( $order->get_order_number() );

                                    $order->add_order_note('Order completed without Tracking', false);
                                }
                            }else{
                                $errors[$order_num][] = 'Order status is already completed';
                            }
                        }else{
                            if(!empty($this->has_delivery($orderxml->deliveries))){
                                $order->add_order_note($this->get_delivery_note($orderxml->deliveries, true), false);
                            }
                            $order->add_order_note('Fitting order so not setting status to Completed', false);
                        }
                        break;
                    default:
                        $errors[$order_num][] = $order_status . ' is not catered for';
                        break;
                }
            }

            $response['status'] = 'OK';
            $response['order_errors'] = $errors;
            $response['order_success'] = $success;


            //mail($email, $subject, $msg, $headers);

        } catch (exception $e) {
            //code to handle the exception
            $response['status'] = 'error';
            $response['error'] = $e->getMessage();
        }

        print_r($response);
        $response_text = ob_get_clean();

        $subject = 'Order Wise report';
        $from = 'website@4x4tyres.co.uk';
        $to = 'kevin.price-ward@4x4tyres.co.uk';

        $headers = "From: 4x4 Website <" . $from . '>' . PHP_EOL;
        $headers.= "Reply-To: " . $from . PHP_EOL;
        $headers .= "MIME-Version: 1.0" . PHP_EOL;
        $headers .= "Content-Type: text/html; charset=ISO-8859-1" . PHP_EOL;

        //wp_mail($to, $subject, $response_text, $headers);


        if($inserted){
            $response_update = $wpdb->update(
                $table_name,
                [
                    'response' => $response_text
                ],
                [
                    'id' => $wpdb->insert_id
                ]
            );
        }

        //Log deko response if necessary
        if($update_deko){
            ob_start();
            print_r($curlResponse);
            $log = ob_get_clean();


            $deko_insert = $wpdb->insert(
                $table_name,
                [
                    'starttime' => date('Y-m-d H:i:s', time()),
                    'endpoint' => 'deko_fulfillment',
                    'log' => $log,
                    'response' => $deko_response,
                ]
            );
        }
    }

    private function get_delivery_note($deliveries, $is_html=false)
    {
        //Handle note to customer
        if($is_html){
            $eol = PHP_EOL;
        }else{
            $eol = '<br/>';
        }
        $delivery_note = '';
        foreach($deliveries as $delivery){
            $del_num = (string)$delivery->deliveryNumber;
            $del_date = (string)$delivery->deliveryDate;
            $del_courier_name = (string)$delivery->courierName;

            $delivery_note.= sprintf('<strong>Delivery number:</strong> %s' . $eol, $del_num);
            $delivery_note.= sprintf('<strong>Dispatch date:</strong> %s' . $eol, $del_date);
            $delivery_note.= sprintf('<strong>Courier:</strong> %s' . $eol, $del_courier_name);

            foreach($delivery->consignmentNumbers as $consignmentNumber){
                $del_consignment_num = (string)$consignmentNumber->consignmentNumber;

                $delivery_note.= sprintf('<strong>Consignment:</strong> %s' . $eol, $del_consignment_num);
            }
        }
        return $delivery_note;
    }

    private function get_tracking_links($deliveries, \WC_Order $order)
    {
        $consignment_number = (string)$deliveries->consignmentNumbers->consignmentNumber[0];
        $delivery_postcode = preg_replace('/\s+/', '', $order->get_shipping_postcode());
        if($this->get_courier_name($deliveries, $order)==='DX'){
            return 'https://dx-track.com/track/4X4.aspx?consno=' . $consignment_number . '&postcode='.$delivery_postcode;
        }else if($this->get_courier_name($deliveries, $order)==='APC'){
            return 'https://apc-overnight.com/track-parcel.php?id=' . $consignment_number . '&postcode'.$delivery_postcode;
        }
    }

    private function get_delivery_logo($deliveries, \WC_Order $order)
    {
        $consignment_number = (string)$deliveries->consignmentNumbers->consignmentNumber[0];
        $delivery_postcode = preg_replace('/\s+/', '', $order->get_shipping_postcode());
        if($this->get_courier_name($deliveries, $order)==='DX'){
            $url = sprintf('https://dx-track.com/track/4X4.aspx?consno=%s&postcode=%s', $consignment_number, $delivery_postcode);
            $logo_html = sprintf('<div style="margin: 0 0 12px;"><a href="%s"><img src="https://4x4tyres.co.uk/app/uploads/email/img/Email_DX_logo.png" alt="DX Logo" width="84" height="42"/></a></div>', $url);
            return $logo_html;
        }else{
            return '';
        }
    }

    private function get_help_text($deliveries, \WC_Order $order)
    {
        if($this->get_courier_name($deliveries, $order)==='DX'){
            return 'For further help with your delivery you can contact DX directly on 0333 241 5109.';
        }else if($this->get_courier_name($deliveries, $order)==='APC'){
            return 'For further help with your delivery you can contact APC directly on 0800 37 37 37.';
        }
    }

    private function get_courier_name($deliveries, \WC_Order $order)
    {
        switch((string)$deliveries->courierName){
            case 'DX':
            case 'DX DM6 Lite':
            case 'DX DM6 Lite MTS':
                $courier_name = 'DX';
                break;
            case 'APC_4x4':
            case 'APC_Oponeo':
                $courier_name = 'APC';
                break;
            default:
                $courier_name = null;
                break;
        }
        return $courier_name;
    }

    private function has_delivery($deliveriesXML)
    {
        $has_delivery = false;
        /*
        foreach($deliveriesXML as $delivery){
            if(is_array($delivery)){
                $has_delivery = true;
                break;
            }
        }*/
        if(key_exists('deliveryNumber', $deliveriesXML)&&!empty((string)$deliveriesXML->deliveryNumber)){
            $has_delivery = true;
        }
        return $has_delivery;
    }

    // find last entry which starts with wc_customer_order_export_background_export_job_

    public function getLatestExportId()
    {

        // for dev
        // $export_id = 'd8be76d4479079623e23bc32b8235aca';

        // call wp database and get last added export_job
        global $wpdb;
        $query = "SELECT * FROM wp_options WHERE option_name LIKE 'wc_customer_order_export_background_export_job_%' ORDER BY option_id DESC LIMIT 1";
        $result = $wpdb->get_row($query);

        // empty message
        if(!$result) {
            echo 'No Exports Found';
            exit;
        }

        // get option name and strip to get id
        $option_name = $result->option_name;
        $export_id = preg_replace('/^wc_customer_order_export_background_export_job_/', '', $option_name);

        // return
        return $export_id;
    }
}
