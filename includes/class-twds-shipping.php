<?php

/* TWDS class. *
*  @extends WC_Shipping_Method */
class TWDS_Shipping extends WC_Shipping_Method
{
    private $domestic = array("US");
    private $found_rates;
    private $carrier_list = ['UPS' => 'UPS', 'USPS' => 'USPS', 'FedEx' => 'FedEx',];
    public $items_to_ship = array();
    public $items_temp;
    /** * Constructor     */
    public function __construct($instance_id = 0)
    {
        $this->id = 'TWDS_SHIPPING';
        $this->instance_id = absint($instance_id);
        $this->items_to_ship['no_drop'] = array();
        $this->items_to_ship['drop'] = array();
        $this->supports = array('shipping-zones', 'instance-settings', 'instance-settings-modal',);
        $this->method_title = __('Calculated Shipping', 'twds-shipping');
        $this->method_description = __('Smart Box Packing Realtime Shipping + Per-Item Droppshipping', 'twds-shipping');
        $this->items_temp = new DVDoug\BoxPacker\BoxList();
        $this->services = array('USPS' => array('services' => array("First" => "First-Class Mail", "Priority" => "Priority Mail&#0174;", "ParcelSelect" => "USPS Parcel Select",)), 'UPS' => array('services' => array("Ground" => "Ground (UPS)", "3DaySelect" => "3 Day Select (UPS)", "2ndDayAir" => "2nd Day Air (UPS)", "NextDayAir" => "Next Day Air (UPS)",)));
        $this->init();
    }
    /** * init function.     *
     *     * @access public
     *   * @return void     */
    private function init()
    {
        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();
        // Define user set variables
        $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : $this->enabled;
        $this->title = 'jjjjj';
        $this->availability = 'all';
        $this->zip = isset($this->settings['zip']) ? $this->settings['zip'] : '';
        $this->senderName = isset($this->settings['name']) ? $this->settings['name'] : '';
        $this->senderCompanyName = isset($this->settings['company']) ? $this->settings['company'] : '';
        $this->senderAddressLine1 = isset($this->settings['street1']) ? $this->settings['street1'] : '';
        $this->senderAddressLine2 = isset($this->settings['street2']) ? $this->settings['street2'] : '';
        $this->senderCity = isset($this->settings['city']) ? $this->settings['city'] : '';
        $this->senderState = isset($this->settings['state']) ? $this->settings['state'] : '';
        $this->senderEmail = isset($this->settings['email']) ? $this->settings['email'] : '';
        $this->senderPhone = isset($this->settings['phone']) ? $this->settings['phone'] : '';
        $this->api_key = $this->settings['api_key'];
        $this->packing_method = 'box_packing';
        $this->custom_services = isset($this->settings['services']) ? $this->settings['services'] : array();
        $this->offer_rates = isset($this->settings['offer_rates']) ? $this->settings['offer_rates'] : 'all';
        $this->fallback = !empty($this->settings['fallback']) ? $this->settings['fallback'] : '';
        $this->mediamail_restriction = isset($this->settings['mediamail_restriction']) ? $this->settings['mediamail_restriction'] : array();
        $this->mediamail_restriction = array_filter((array) $this->mediamail_restriction);
        $this->enable_standard_services = true;
        $this->debug = false;
        $this->api_mode = isset($this->settings['api_mode']) ? $this->settings['api_mode'] : 'Live';
        $this->carrier = $this->settings['easypost_carrier'];
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'clear_transients'));
    }
    /**
     * environment_check.
     * @access public
     * @return void
     */
    private function environment_check()
    {
        global $woocommerce;
        $admin_page = version_compare(WOOCOMMERCE_VERSION, '2.1', '>=') ? 'wc-settings' : 'woocommerce_settings';
        if (get_woocommerce_currency() != "USD") {
            echo '<div class="error"><p>' . sprintf(__('TWDS Shipping requires that the <a href="%s">currency</a> is set to US Dollars.', 'twds-shipping'), admin_url('admin.php?page=' . $admin_page . '&tab=general')) . '</p>
</div>';
        } elseif (!in_array($woocommerce->countries->get_base_country(), $this->domestic)) {
            echo '<div class="error">               <p>' .
                sprintf(__('TWDS Shipping requires that the <a href="%s">base country/region</a> is the United States.', 'twds-shipping'), admin_url('admin.php?page=' .
                    $admin_page .
                    '&tab=general')) .
                '</p>           </div>';
        } elseif (!$this->zip && $this->enabled == 'yes') {
            echo '<div class="error">               <p>' .
                __('The zip code has not been set.', 'twds-shipping') .
                '</p>           </div>';
        }
        $error_message = '';
        // Check for Easypost.com APIKEY
        if (!$this->api_key && $this->enabled == 'yes') {
            $error_message .= '<p>' . __('Easypost.com API KEY has not been set.', 'twds-shipping') .
                '</p>';
        }
        if (!$error_message == '') {
            echo '<div class="error">';
            echo $error_message;
            echo '</div>';
        }
    }

    /*
* admin_options.
* @access public
* @return void
*/
    public function admin_options()
    {
        // Check users environment supports this method
        $this->environment_check();
        ?>
        <style>
            .twds-banner img {
                float: right;
                margin-left: 1em;
                padding: 15px 0
            }
        </style>
    <?php

            // Show settings
            parent::admin_options();
        }
        /*
* generate_services_html function.
*/
        public function generate_services_html()
        {
            ob_start();
            include('html-twds-services.php');
            return ob_get_clean();
        }

        /*
*  validate_services_field function.
*  @access public
*  @param mixed $key
*  @return void
*/
        public function validate_services_field($key)
        {
            $services = array();
            $posted_services = $_POST['easypost_service'];
            foreach ($posted_services as $code => $settings) {
                foreach ($this->services[$code]['services'] as $key => $name) {
                    $services[$code][$key]['enabled'] = isset($settings[$key]['enabled']) ? true : false;
                    $services[$code][$key]['order'] = wc_clean($settings[$key]['order']);
                }
            }
            return $services;
        }
        /**     * clear_transients function.     *     * @access public     * @return void     */
        public function clear_transients()
        {
            global $wpdb;
            $wpdb->query("DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_easypost_quote_%') OR `option_name` LIKE ('_transient_timeout_easypost_quote_%')");
        }
        /**     * init_form_fields function.     *     * @access public     * @return void     */
        public function init_form_fields()
        {
            //global $woocommerce;
            $api_mode_options = array(
                'Live' => __('Live', 'woocommerce'),
                'Test' => __('Test', 'woocommerce'),
            );
            $this->instance_form_fields = array(
                'enabled' => array(
                    'title' => __('Realtime Rates', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable', 'woocommerce'),
                    'description' => __('Enable realtime rates on Cart/Checkout page.', 'woocommerce'),
                    'default' => 'no',
                    'desc_tip' => true,
                ),
                'title' => array(
                    'title' => __('Method Title', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                    'default' => __($this->title, ''),
                    'placeholder' => __($this->title, 'woocommerce'),
                    'desc_tip' => true,
                ),
                'easypost_carrier' => array(
                    'title' => __('Easypost Carrier', 'woocommerce'),
                    'type' => 'multiselect',
                    'description' => __('Select your Easypost Carriers.', 'woocommerce'),
                    'default' => array(),
                    'css' => 'width: 450px;',
                    'class' => 'ups_packaging chosen_select',
                    'options' => $this->carrier_list,
                    'desc_tip' => true
                ),
                'debug_mode' => array(
                    'title' => __('Debug Mode', 'woocommerce'),
                    'label' => __('Enable', 'woocommerce'),
                    'type' => 'checkbox',
                    'default' => 'no',
                    'description' => __('Enable debug mode to show debugging information on your cart/checkout. Not recommended to enable this in live site with traffic.', 'woocommerce'),
                    'desc_tip' => true,
                ),
                'api' => array(
                    'title' => __('Generic API Settings:', 'woocommerce'),
                    'type' => 'title',
                    'description' => 'API Settings',
                ),
                'api_key' => array(
                    'title' => __('API KEY', 'woocommerce'),
                    'type' => 'password',
                    'description' => __('Your password'),
                    'default' => '',
                    'desc_tip' => true,
                ),            'api_mode' => array(
                    'title' => __('API Mode', 'woocommerce'),
                    'type' => 'select',
                    'default' => 'Live',
                    'options' => $api_mode_options,
                    'description' => __('Live mode is the strict choice for Customers as Test mode is strictly restricted for development purpose by Easypost.com.', 'woocommerce'),
                    'desc_tip' => true,
                ),
                'name' => array(
                    'title' => __('Sender Name', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Enter your name.', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'company' => array(
                    'title' => __('Sender Company Name', 'woocommerce'),
                    'type' => 'text',

                    'description' => __('Enter your company name.', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'street1' => array(
                    'title' => __('Sender Address Line1', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Enter your address line 1.', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'street2' => array(
                    'title' => __('Sender Address Line2', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Enter your address line 2.', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'city' => array(
                    'title' => __('Sender City', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Enter your city.', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'state' => array(
                    'title' => __('Sender State', 'w'
                        . 'oocommerce'),
                    'type' => 'text',
                    'description' => __('Enter state short code (Eg: CA).', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'email' => array(
                    'title' => __('Sender Email', 'woocommerce'),
                    'type' => 'email',
                    'description' => __('Enter sender email', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                ),            'zip' => array(
                    'title' => __('Zip Code', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Enter the postcode for the sender', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'phone' => array(
                    'title' => __('Sender Phone', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Enter sender phone', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'customs_description' => array(
                    'title' => __('Customs Description', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('Product description for International shipping.', 'woocommerce'),
                    'default' => '',
                    'css' => 'width:39%;',
                    'desc_tip' => true,
                ),
                'services' => array(
                    'type' => 'services',
                ),
                'percentage' => array(
                    'title' => __('Percentage to add to rate (Enter as a decimal)', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('The precentage amount to charge on any returned rates.', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                    'fallback' => array(
                        'title' => __('Fallback', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('If Easypost.com returns no matching rates, offer this amount for shipping so that the user can still checkout. Leave blank to disable.', 'woocommerce'),
                        'default' => '',
                        'desc_tip' => true,
                    ),
                )
            );
        }
        public function find_shipping_classes($package)
        {
            $found_shipping_classes = array();
            foreach ($package['contents'] as $item_id => $values) {
                if ($values['data']->needs_shipping()) {
                    $found_class = $values['data']->get_shipping_class();
                    if (!isset($found_shipping_classes[$found_class])) {
                        $found_shipping_classes[$found_class] = array();
                    }
                    $found_shipping_classes[$found_class][$item_id] = $values;
                }
            }
            return $found_shipping_classes;
        }
        /**     * calculate_shipping function.     *     * @access public     * @param mixed $package     * @return void     */
        public function calculate_shipping($package = array())
        {
            $shipping_classes = WC()->shipping->get_shipping_classes();
            $freight_classes = array(
                '50.0',
                '55.0',            '60.0',            '65.0',            '70.0',            '77.5',            '85.0',            '92.5',            '100.0',            '110.0',            '125.0',            '150.0',            '175.0',            '200.0',            '250.0',            '300.0',            '400.0',            '500.0'
            );
            //      $classes = $this->find_shipping_classes( $package );
            //      foreach ($classes as $class) {
            //     $shipping_classes[$class->term_id] = $class->name;
            //       }
            $this->rates = array();
            $this->unpacked_item_costs = 0;
            $domestic = in_array($package['destination']['country'], $this->domestic) ? true : false;
            //$this->debug(__('Easypost.com debug mode is on - to hide these messages, turn debug mode off in the settings.', 'twds-shipping'))
            if ($this->enable_standard_services) {
                $packedBoxes = $this->pack_order($package);
                $Dropshipping = $this->items_to_ship['drop'];
                $number_of_boxes_shipping = count($packedBoxes) + count($Dropshipping);

                if ($number_of_boxes_shipping < 1) {
                    return;
                }
                $requests = array();
                foreach ($packedBoxes as $packedBox) {
                    $boxType = $packedBox->getBox();
                    $name = $boxType->getReference();
                    $w = TWDS_Shipping::convert_to_inches($boxType->getOuterWidth());
                    $l = TWDS_Shipping::convert_to_inches($boxType->getOuterLength());

                    $h = TWDS_Shipping::convert_to_inches($boxType->getOuterDepth());
                    $wt = TWDS_Shipping::convert_to_lbs($packedBox->getWeight());
                    $wtoz = TWDS_Shipping::convert_to_oz($packedBox->getWeight());
                    if ($domestic) {
                        $request['Rate'] = $this->create_domestic_rate($package, $name, $l, $w, $h, $wt, $wtoz);
                    } else {
                        $request['Rate'] = $this->create_non_domestic_rate($package, $name, $l, $w, $h, $wt, $wtoz);
                    }
                    $request_ele = array();
                    $request_ele['request'] = $request;
                    $requests[] = $request_ele;
                }
                foreach ($Dropshipping as $item_id => $value) {
                    $item = $value['item'];
                    $w = $item->get_width();
                    $l = $item->get_length();
                    $h = $item->get_height();
                    $wt = $item->get_weight();
                    $wtoz = $item->get_weight() * 16;
                    if ($domestic) {
                        $requestdrop['Rate'] = $this->create_domestic_drop_rate($package, $value['vendor_name'], $value['vendor_address'], $value['vendor_city'],  $value['vendor_st'], $value['vendor_zip'], $l, $w, $h, $wt, $wtoz);
                    } else {
                        $requestdrop['Rate'] = $this->create_non_domestic_drop_rate($package, $value['vendor_name'], $value['vendor_address'], $value['vendor_city'],  $value['vendor_st'], $value['vendor_zip'], $l, $w, $h, $wt, $wtoz);
                    }
                    $request_drop_ele = array();
                    $request_drop_ele['request'] = $requestdrop;
                    $requests[] = $request_drop_ele;
                }
                libxml_use_internal_errors(true);
                if ($requests) {
                    if (!class_exists('EasyPost')) {
                        require_once(plugin_dir_path(dirname(__FILE__)) . "/lib/easypost-php/lib/easypost.php");
                    }
                    \EasyPost\EasyPost::setApiKey($this->settings['api_key']);
                    $responses = array();
                    //do drop shipping check here
                    foreach ($requests as $key => $package_request) {
                        // Get rates.

                        try {
                            $payload = array();

                            if (isset($package_request['request']['Rate']['FromName']) && ($package_request['request']['Rate']['FromName'] == 'drop')) {
                                $payload['from_address'] = array("name" => 'Warehouse',                            "company" => ' Reference: ' . $this->senderCompanyName,                            "street1" => $package_request['request']['Rate']['FromAddress'],                            "street2" => '',                            "city" => $package_request['request']['Rate']['FromCity'],                            "state" => $package_request['request']['Rate']['FromState'],                            "zip" => $package_request['request']['Rate']['FromZIPCode'],                            "phone" => $this->senderPhone);
                            } else {
                                $payload['from_address'] = array("name" => $this->senderName,                            "company" => $this->senderCompanyName,                            "street1" => $this->senderAddressLine1,                            "street2" => $this->senderAddressLine2,                            "city" => $this->senderCity,                            "state" => $this->senderState,                            "zip" => $this->zip,                            "phone" => $this->senderPhone);
                            }
                            $payload['to_address'] = array(                            // Name and Street1 are required fields for getting rates.

                                // But, at this point, these details are not available.
                                "name" => "Ryan",                            "street1" => "-",                            "zip" => $package_request['request']['Rate']['ToZIPCode'],                            "country" => $package_request['request']['Rate']['ToCountry']
                            );
                            if (!empty($package_request['request']['Rate']['WeightLb']) && $package_request['request']['Rate']['WeightOz'] == 0.00) {
                                $package_request['request']['Rate']['WeightOz'] = number_format($package_request['request']['Rate']['WeightLb'] * 16);
                            }
                            $payload['parcel'] = array('length' => $package_request['request']['Rate']['Length'],                            'width' => $package_request['request']['Rate']['Width'],                            'height' => $package_request['request']['Rate']['Height'],                            'weight' => floatval($package_request['request']['Rate']['WeightOz']),);
                            $payload['options'] = array("special_rates_eligibility" => 'USPS.LIBRARYMAIL');
                            $shipment = \EasyPost\Shipment::create($payload);
                            $shipment_data = $shipment->get_rates();
                            $response = json_decode($shipment_data);
                            $response_ele = array();
                            $response_ele['response'] = $response;
                            $responses[] = $response_ele;
                        } catch (Exception $e) {
                            $this->debug(__('Easypost.com - Unable to Get Rates: ', 'twds-shipping') . $e->getMessage());
                            if (TWDS_SHIPPING_ADV_DEBUG_MODE == "on") {
                                $this->debug(print_r($e, true));
                            }
                            return false;
                        }
                    }
                    $found_rates = array();
                    foreach ($responses as $response_ele) {
                        $response_obj = $response_ele['response'];
                        if (isset($response_obj->rates) && !empty($response_obj->rates)) {
                            foreach ($this->carrier as $carrier_name) {
                                foreach ($response_obj->rates as $easypost_rate) {
                                    if ($carrier_name == $easypost_rate->carrier) {
                                        $service_type = (string) $easypost_rate->service;
                                        $service_name = (string) (isset($this->custom_services[$carrier_name][$service_type]['name']) && !empty($this->custom_services[$carrier_name][$service_type]['name'])) ? $this->custom_services[$carrier_name][$service_type]['name'] : $this->services[$carrier_name]['services'][$service_type];
                                        $total_amount = $easypost_rate->rate;
                                        $estimated_days = $easypost_rate->est_delivery_days;
                                        if (isset($found_rates[$service_type])) {
                                            $found_rates[$service_type]['cost'] = $found_rates[$service_type]['cost'] + $total_amount;
                                        } else {
                                            $found_rates[$service_type]['label'] = $service_name;
                                            $found_rates[$service_type]['cost'] = $total_amount;
                                            $found_rates[$service_type]['carrier'] = $easypost_rate->carrier;
                                            $found_rates[$service_type]['days'] = $estimated_days;
                                        }
                                    }
                                }
                            }
                        } else {
                            $this->debug(__('Easypost.com - Unknown error while processing Rates.', 'twds-shipping'));
                            return;
                        }
                    }
                    if ($found_rates) {
                        foreach ($this->carrier as $carrier_name) {
                            foreach ($found_rates as $service_type => $found_rate) {
                                // Enabled check
                                if ($carrier_name == $found_rate['carrier']) {
                                    if (isset($this->custom_services[$carrier_name][$service_type]) && empty($this->custom_services[$carrier_name][$service_type]['enabled'])) {
                                        continue;
                                    }
                                    $total_amount = $found_rate['cost'];
                                    // Cost adjustment %
                                    if (!empty($this->custom_services[$carrier_name][$service_type]['adjustment_percent'])) {
                                        $total_amount = $total_amount + ($total_amount * (floatval($this->custom_services[$carrier_name][$service_type]['adjustment_percent']) / 100));
                                    }                                // Cost adjustment
                                    if (!empty($this->custom_services[$service_type][$service_type]['adjustment'])) {
                                        $total_amount = $total_amount + floatval($this->custom_services[$carrier_name][$service_type]['adjustment']);
                                    }
                                    $labelName = !empty($this->settings['services'][$carrier_name][$service_type]['name']) ? $this->settings['services'][$carrier_name][$service_type]['name'] : $this->services[$carrier_name]['services'][$service_type];
                                    $rate = array(
                                        'id' => (string) $this->id . ':' . $service_type,                                    'label' => (string) $labelName,                                    'cost' => (string) $total_amount,                                    'calc_tax' => 'per_item',                                        //add estimated days here
                                    );
                                    // Register the rate
                                    $this->add_rate($rate);
                                }
                            }
                        }
                    }                // Fallback
                    elseif ($this->fallback) {
                        $this->add_rate(array('id' => $this->id . '_fallback',                        'label' => $this->title,                        'cost' => $this->fallback,                        'sort' => 0));
                    }
                }
            }
        }
        public function create_domestic_drop_rate($package, $drop_name, $drop_address, $drop_city, $drop_state, $drop_zip, $l, $w, $h, $wt, $wtoz)
        {
            $drop_name = 'drop';
            return array('FromName' => $drop_name,            'FromAddress' => $drop_address,            'FromCity' => $drop_city,            'FromState' => $drop_state,            'FromZIPCode' => $drop_zip,            'ToZIPCode' => strtoupper(substr($package['destination']['postcode'], 0, 5)),            'ToCountry' => $package['destination']['country'],            'WeightLb' => floor($wt),            'WeightOz' => floatval($wtoz),            'PackageType' => 'Package',            'Length' => $l,            'Width' => $w,            'Height' => $h,            'ShipDate' => date("Y-m-d", (current_time('timestamp') + (60 * 60 * 24))),            'InsuredValue' => '0',            'RectangularShaped' => 'true');
        }
        public function create_non_domestic_drop_rate($package, $drop_name, $drop_address, $drop_city, $drop_state, $drop_zip, $l, $w, $h, $wt, $wtoz)
        {
            return array('FromName' => $drop_name,            'FromAddress' => $drop_address,            'FromCity' => $drop_city,            'FromState' => $drop_state,            'FromZIPCode' => $drop_zip,             'ToZIPCode' => $package['destination']['postcode'],            'ToCountry' => $package['destination']['country'],            'Amount' => 100.00,            'WeightLb' => floor($wt),            'WeightOz' => floatval($wtoz),            'PackageType' => 'Package',            'Length' => $l,            'Width' => $w,            'Height' => $h,            'ShipDate' => date("Y-m-d", (current_time('timestamp') + (60 * 60 * 24))),            'InsuredValue' => '0',            'RectangularShaped' => 'true');
        }
        public function create_domestic_rate($package, $name, $l, $w, $h, $wt, $wtoz)
        {
            $zip = str_replace(' ', '', strtoupper($this->settings['zip']));
            return array('FromZIPCode' => $zip,            'ToZIPCode' => strtoupper(substr($package['destination']['postcode'], 0, 5)),            'ToCountry' => $package['destination']['country'],            'WeightLb' => floor($wt),            'WeightOz' => floatval($wtoz),            'PackageType' => 'Package',            'Length' => $l,            'Width' => $w,            'Height' => $h,            'ShipDate' => date("Y-m-d", (current_time('timestamp') + (60 * 60 * 24))),            'InsuredValue' => '0',            'RectangularShaped' => 'true');
        }
        public function create_non_domestic_rate($package, $name, $l, $w, $h, $wt, $wtoz)
        {
            return array('FromZIPCode' => str_replace(' ', '', strtoupper($this->settings['zip'])),            'ToZIPCode' => $package['destination']['postcode'],            'ToCountry' => $package['destination']['country'],            'Amount' => 100.00,            'WeightLb' => floor($wt),            'WeightOz' => floatval($wtoz),            'PackageType' => 'Package',            'Length' => $l,            'Width' => $w,            'Height' => $h,            'ShipDate' => date("Y-m-d", (current_time('timestamp') + (60 * 60 * 24))),            'InsuredValue' => '0',            'RectangularShaped' => 'true');
        }
        public function prepare_rate($rate_code, $rate_id, $rate_name, $rate_cost)
        {
            // Name adjustment
            if (!empty($this->custom_services[$rate_code]['name'])) {
                $rate_name = $this->custom_services[$rate_code]['name'];
            }
            // Merging
            if (isset($this->found_rates[$rate_id])) {
                $rate_cost = $rate_cost + $this->found_rates[$rate_id]['cost'];
                $packages = 1 + $this->found_rates[$rate_id]['packages'];
            } else {
                $packages = 1;
            }
            // Sort
            if (isset($this->custom_services[$rate_code]['order'])) {
                $sort = $this->custom_services[$rate_code]['order'];
            } else {
                $sort = 999;
            }
            $this->found_rates[$rate_id] = array('id' => $rate_id,            'label' => $rate_name,            'cost' => $rate_cost,            'sort' => $sort,            'packages' => $packages);
        }
        public function sort_rates($a, $b)
        {
            if ($a['sort'] == $b['sort']) {
                return 0;
            }
            return ($a['sort'] < $b['sort']) ? -1 : 1;
        }
        public function pack_order($package)
        {
            $this->items_temp = new DVDoug\BoxPacker\BoxList();
            $packed_boxes = null;
            switch ($this->packing_method) {
                case 'box_packing':
                    return $this->box_shipping($package);
                case 'per_item':
                    //TODO: Impliment this. right now just to be safe still use the box packing method
                    return $this->box_shipping($package);
                default:
                    return $this->box_shipping($package);
            }
            return $packed_boxes;
        }
        public function box_shipping($package)
        {
            $items = new \DVDoug\BoxPacker\ItemList();
            $boxes = new \DVDoug\BoxPacker\BoxList();
            $this->items_temp = new \DVDoug\BoxPacker\ItemList();
            $bulkq = 1;
            $quantity = 1;
            $this->items_to_ship['no_drop'] = $this->get_items_to_ship($package);
            if (!(count($this->items_to_ship['no_drop']) > 0)) {
                return;
            }
            $boxes = $this->get_packing_boxes();
            foreach ($this->items_to_ship['no_drop'] as $post_id => $item_id) {
                $p = $package['contents'][$item_id];
                $quantity = $p['quantity'];
                $product_to_pack = wc_get_product($post_id);
                $shipping_meta = get_post_meta($post_id, '', true);
                $l = TWDS_Shipping::convert_to_mm($product_to_pack->get_length());
                $w = TWDS_Shipping::convert_to_mm($product_to_pack->get_width());
                $h = TWDS_Shipping::convert_to_mm($product_to_pack->get_height());
                $wt = TWDS_Shipping::convert_to_grams($product_to_pack->get_weight());                        //loop through the quantity and for each on add it to the list
                for ($x = 1; $x < $quantity + 1; $x++) {
                    $item_to_add = new packing_item($item_id . $x, $l, $w, $h, $wt, false);
                    $items->insert($item_to_add);
                }
            }
            //pack all items into our shipping boxes and come back with an answer
            $packer = new \TWDS\Box_Packer();
            $packer->setBoxes($boxes);
            $packer->setItems($items);
            $packedBoxes = new \DVDoug\BoxPacker\PackedBoxList();
            $packedBoxes = $packer->pack();
            return $packedBoxes;
        }
        public function get_packing_boxes()
        {
            $box = array();
            $number_of_shipping_boxes = 0;
            $the_boxes = array('post_type' => 'box',);
            $loop = new WP_Query($the_boxes);
            while ($loop->have_posts()) {
                $loop->the_post();
                $pid = get_the_ID();
                $l = floatval(get_post_meta($pid, 'box_length', true));
                $w = floatval(get_post_meta($pid, 'box_width', true));
                $h = floatval(get_post_meta($pid, 'box_height', true));
                $bw = floatval(get_post_meta($pid, 'box_weight', true));
                $mw = floatval(get_post_meta($pid, 'box_max_weight', true));
                $l = TWDS_Shipping::convert_to_mm($l);
                $w = TWDS_Shipping::convert_to_mm($w);
                $h = TWDS_Shipping::convert_to_mm($h);
                $bw = TWDS_Shipping::convert_to_grams($bw);
                $mw = TWDS_Shipping::convert_to_grams($mw);
                $box[] = new packing_box(the_title(), $l, $w, $h, $bw, $l, $w, $h, $mw);
            }
            wp_reset_query();
            $boxes = new \DVDoug\BoxPacker\BoxList();
            $number_of_shipping_boxes = count($box);
            for ($c = 0; $c < $number_of_shipping_boxes; ++$c) {
                $boxes->insert($box[$c]);
            }
            return $boxes;
        }
        public function get_vendor_data()
        {
            $vendor = array();
            $the_vendors = array('post_type' => 'twds_vendor',);
            $loop = new WP_Query($the_vendors);
            while ($loop->have_posts()) {
                $loop->the_post();
                $pid = get_the_ID();
                $name = get_post($pid, 'title', true);
                $address = get_post_meta($pid, 'twds_vendor_address', true);
                $city = get_post_meta($pid, 'twds_vendor_city', true);
                $state = get_post_meta($pid, 'twds_vendor_state', true);
                $zip = get_post_meta($pid, 'twds_vendor_zip', true);
                $vendor[] = array('name' => $name,                'address' => $address,                'city' => $city,                'state' => $state,                'zip' => $zip);
            }
            wp_reset_query();
            return $vendor;
        }
        public function get_items_to_ship($package)
        {
            $items_to_ship = array();
            $this->items_to_ship['no_drop']  = array();
            foreach ($package['contents'] as $item_id => $values) {
                if (!$values['data']->needs_shipping()) {
                    continue;
                }

                $this->items_to_ship['drop'] = null;
                $package_is_freight = false;
                //Check custom shipping fields for flags that make this method not apply

                $post_id = $values['product_id'];
                $shipping_meta = get_post_meta($post_id, '', true);
                $product_stock = $values['data']->is_in_stock();
                $Product_stock_amount = $values['data']->get_stock_quantity();
                if ($Product_stock_amount < 1 && $product_stock) {
                    if (isset($shipping_meta['twds_vendor_select'][0]) && $shipping_meta['twds_vendor_select'][0] != 'none') {
                        $vendor_meta = get_post_meta($shipping_meta['twds_vendor_select'][0], '', true);
                        $this->items_to_ship['drop'][$item_id] = array('item' => $values['data'],                          'vendor_name' =>   $vendor_meta['twds_vendor_email'][0],                          'vendor_address' =>  $vendor_meta['twds_vendor_address'][0],                          'vendor_city' =>  $vendor_meta['twds_vendor_city'][0],                          'vendor_st' =>  $vendor_meta['twds_vendor_state'][0],                          'vendor_zip' => $vendor_meta['twds_vendor_zip'][0],);
                        continue;
                    }
                }
                //First one to check is if there is a forced shipping cost attached then skip this product

                if (isset($shipping_meta['shipping_cost_number_field'][0]) && floatval($shipping_meta['shipping_cost_number_field'][0]) > 0) {
                    continue;
                }
                //Now find any additional special handling fees and add them in
                if (isset($shipping_meta['additional_cost'][0]) && floatval($shipping_meta['additional_cost'][0]) > 0) {
                    $tempfee = floatval($this->fee);
                    $tempfee += floatval($shipping_meta['additional_cost'][0]);
                    $this->fee = number_format($tempfee, 2);
                }
                //additem to list of shippable items
                $items_to_ship[$post_id] = $item_id;
            }
            return $items_to_ship;
        }
        public static function convert_to_mm($inches)
        {
            return $inches * 25.4;
        }
        public static function convert_to_inches($mm)
        {
            return $mm / 25.4;
        }
        public static function convert_to_grams($lbs = 0, $lbs_oz = 0)
        {
            $g = ($lbs * 453.5924);
            $g += ($lbs_oz * 28.34952);
            return $g;
        }
        public static function convert_to_oz($grams)
        {
            return $grams / 453.5924 * 16;
        }
        public static function convert_to_lbs($grams)
        {
            return $grams / 453.5924;
        }
        public function generate_package_id($id, $qty, $length, $width, $height, $weight)
        {
            return implode(':', array($id, $qty, $length, $width, $height, $weight));
        }
        public function debug($message, $type = 'notice')
        {
            if ($this->debug && !is_admin()) {
                //twds: is_admin check added.

                if (version_compare(WOOCOMMERCE_VERSION, '2.1', '>=')) {
                    wc_add_notice($message, $type);
                } else {
                    global $woocommerce;
                    $woocommerce->add_message($message);
                }
            }
        }
    }
