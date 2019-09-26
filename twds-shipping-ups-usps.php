<?php

if (!defined('ABSPATH')) {

    exit;
}

class WC_Shipping_Drop_Live extends WC_Shipping_Method
{

    private $domestic     = array(
        "US"
    );
    private $found_rates;
    private $carrier_list = array(
        'UPS'  => 'UPS',
        'USPS' => 'USPS',
    );

    public   $items_to_ship = array();
    public   $items_temp;
    public   $forced_cost_items;
    public   $packed_boxes;
    public   $order;
    protected static $_instance = null;

    public static function instance()
    {

        if (is_null(self::$_instance)) {

            self::$_instance = new self();
        }

        return self::$_instance;
    }



    public  function __construct($instance_id = 0)
    {

        $this->id = 'drop_live';

        $this->instance_id = absint($instance_id);

        $this->method_title = __('Drop Live Shipping', 'woocommerce');

        $this->method_description = __('Gives Live UPS and USPS Rates, and Calculates Drop Shipping Rates From Multiple Locations.', 'woocommerce');

        $this->supports   = array(
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        );

        $this->title = 'TWD Shipping';

        $this->items_to_ship['no_drop'] = array();

        $this->items_to_ship['drop'] = array();
        $this->custom_services = array();

        //$this->services = $this->get_option('services');
        $this->services = array(
            // Domestic & International



            'USPS'  => array(
                // Services which costs are merged if returned (cheapest is used). This gives us the best possible rate.



                'services' => array(
                    "First"        => "First-Class Mail",
                    "Priority"     => "Priority Mail&#0174;",
                    //    "Express" => "Priority Mail Express&#8482;",
                    //  "ParcelSelect" => "USPS Parcel Select",

                )
            ),
            'UPS'   => array(
                'services' => array(
                    "Ground"     => "Ground (UPS)",
                    "3DaySelect" => "3 Day Select (UPS)",

                    "2ndDayAir"  => "2nd Day Air (UPS)",

                    "NextDayAir" => "Next Day Air (UPS)",

                )
            )
        );



        $this->init();
    }



    public  function init()
    {


        $this->init_form_fields();

        $this->init_settings();

        $this->tax_status = $this->get_option('tax_status');

        $this->cost = $this->get_option('cost');

        $this->enabled = $this->get_option('enabled');

        $this->zip = $this->get_option('zip');

        $this->senderName = $this->get_option('name');

        $this->senderCompanyName = $this->get_option('company');

        $this->senderAddressLine1 = $this->get_option('street1');

        $this->senderAddressLine2 = $this->get_option('street2');

        $this->senderCity = $this->get_option('city');

        $this->senderState = $this->get_option('state');

        $this->senderEmail = $this->get_option('email');

        $this->senderPhone = $this->get_option('phone');

        $this->carrier = $this->get_option('easypost_carrier');

        $this->api_key = $this->get_option('api_key');

        $this->packing_method = 'box_packing';

        $this->custom_services = $this->get_option('services');

        $this->cost_per_unit = $this->get_option('cost_per_unit');

        $this->offer_rates = 'all';

        $this->fallback = $this->get_option('fallback');

        $this->mediamail_restriction = $this->get_option('mediamail_restriction');

        $this->mediamail_restriction = array_filter((array) $this->mediamail_restriction);

        $this->enable_standard_services = true;

        $this->api_mode = $this->get_option('api_mode');

        // Actions


        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));

        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'clear_transients'));
    }

    public function init_form_fields()
    {

        $this->generate_services_html();

        $this->instance_form_fields = array(
            'title'               => array(
                'title'       => __('Title', 'woocommerce'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                'default'     => __('Local Pickup', 'woocommerce'),
                'desc_tip'    => true
            ),
            'easypost_carrier'    => array(
                'title'       => __('Carriers', 'woocommerce'),
                'type'        => 'multiselect',
                'description' => __('Select the Carriers to use.', 'woocommerce'),
                'default'     => array(),
                'css'         => 'width: 450px;',
                'class'       => 'ups_packaging chosen_select',
                'options'     => $this->carrier_list,
                'desc_tip'    => true
            ),
            'api'                 => array(
                'title'       => __('API Settings:', 'woocommerce'),
                'type'        => 'title',
                'description' => 'API Settings',
            ),
            'api_key'             => array(
                'title'       => __('API KEY', 'woocommerce'),
                'type'        => 'text',
                'description' => __('API KEY'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'api_mode'            => array(
                'title'       => __('API Mode', 'woocommerce'),
                'type'        => 'select',
                'default'     => 'Live',
                'options'     => array(
                    'Live' => __('Live', 'woocommerce'),
                    'Test' => __('Test', 'woocommerce'),
                ),
                'description' => __('Use live or testing endpoints?', 'woocommerce'),
                'desc_tip'    => true,
            ),
            'name'                => array(
                'title'       => __('Sender Name', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Enter your name.', 'woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'company'             => array(
                'title'       => __('Sender Company Name', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Enter your company name.', 'woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'street1'             => array(
                'title'       => __('Sender Address Line1', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Enter your address line 1.', 'woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'street2'             => array(
                'title'       => __('Sender Address Line2', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Enter your address line 2.', 'woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'city'                => array(
                'title'       => __('Sender City', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Enter your city.', 'woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'state'               => array(
                'title'       => __('Sender State', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Enter state short code (Eg: CA).', 'woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'email'               => array(
                'title'       => __('Sender Email', 'woocommerce'),
                'type'        => 'email',
                'description' => __('Enter sender email', 'woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'zip'                 => array(
                'title'       => __('Zip Code', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Enter the postcode for the sender', 'woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'phone'               => array(
                'title'       => __('Sender Phone', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Enter sender phone', 'woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'customs_description' => array(
                'title'       => __('Customs Description', 'woocommerce'),
                'type'        => 'textarea',
                'description' => __('For International shipping. What will display on the customs forms?', 'woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'services'            => array(
                'title'       => __('Services', 'woocommerce'),
                'type'        => 'services',
                'description' => __('Select the Services To Use.', 'woocommerce'),

            ),

            'cost_per_unit'                => array(
                'title'       => __('Additional Costt Per Weight Unit', 'woocommerce'),
                'type'        => 'text',
                'placeholder' => '',
                'description' => __('To add an amount to the cost of shipping multiplied by the total weight enter a number greater than 0', 'woocommerce'),
                'default'     => '',
                'desc_tip'    => true
            ),


            'tax_status'          => array(
                'title'   => __('Tax Status', 'woocommerce'),
                'type'    => 'select',
                'class'   => 'wc-enhanced-select',
                'default' => 'taxable',
                'options' => array(
                    'taxable' => __('Taxable', 'woocommerce'),
                    'none'    => _x('None', 'Tax status', 'woocommerce')
                )
            ),
            'cost'                => array(
                'title'       => __('Cost', 'woocommerce'),
                'type'        => 'text',
                'placeholder' => '',
                'description' => __('Amount to add to the shipping cost, like a handeling fee'),
                'default'     => '',
                'desc_tip'    => true
            )
        );
    }

    public function create_services()
    {
        $services = array();
        foreach ($this->services as $item) {
            foreach ($item as $service) {
                foreach ($service as $key => $serv) {
                    $services[$key] = $serv;
                }
            }
        }
        return $services;
    }

    public function get_package_item_qty($package)
    {

        $total_quantity = 0;

        foreach ($package['contents'] as $item_id => $values) {

            if ($values['quantity'] > 0 && $values['data']->needs_shipping()) {

                $total_quantity += $values['quantity'];
            }
        }

        return $total_quantity;
    }

    public function is_available($package)
    {



        $is_available = true;



        return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package);
    }

    public function create_parcel(\DVDoug\BoxPacker\PackedBox $packed_box)
    {

        $weightGrams = 1;

        $boxUsed = $packed_box->getBox();

        if (!$packed_box->getWeight()) {
            $tempitems = $packed_box->getItems();
            foreach ($tempitems as $tI) {
                $weightGrams = +$tI->getWeight();
            }
        } else {
            $weightGrams = floatval($packed_box->getWeight());
        }


        $w = WC_Shipping_Drop_Live::convert_to_inches($boxUsed->getOuterWidth());

        $l = WC_Shipping_Drop_Live::convert_to_inches($boxUsed->getOuterLength());

        $h = WC_Shipping_Drop_Live::convert_to_inches($boxUsed->getOuterDepth());

        $wt = WC_Shipping_Drop_Live::convert_to_lbs($weightGrams);

        $wtoz = WC_Shipping_Drop_Live::convert_to_oz($weightGrams);

        $parcel['parcel'] = array(
            'height' => $h,
            'width' => $w,
            'length' => $l,
            'weight' => $wtoz
        );


        return $parcel;
    }

    public function create_parcel_from_array($array_box = array())
    {

        $weightGrams = 1;



        $w =  $array_box['w'];

        $l =  $array_box['l'];

        $h =  $array_box['h'];

        $wt = $array_box['wt'];

        $wtoz = WC_Shipping_Drop_Live::convert_to_oz($array_box['wt']);

        $parcel = array(
            'parcel' => array(
                'height' => $h,
                'width' => $w,
                'length' => $l,
                'weight' => $wtoz
            )
        );

        return $parcel;
    }


    public function get_shipment_addresses($package)
    {

        $FromZIPCode = str_replace(' ', '', strtoupper($this->get_option('zip')));
        $ToZIPCode = strtoupper(substr($package['destination']['postcode'], 0, 5));
        $ToCity = $package['destination']['city'];
        $ToState = $package['destination']['state'];
        //$ToCountry = $package['destination']['country'];
        $addresses = array();

        if ($this->domestic) {
            $addresses = array(
                'from_address' => array(
                    "name"    => $this->senderName,
                    "company" => $this->senderName,
                    "street1" => $this->senderAddressLine1,
                    "street2" => $this->senderAddressLine2,
                    "city"    => $this->senderCity,
                    "state"   => $this->senderState,
                    "zip"     => $FromZIPCode,
                    "phone"   => $this->senderPhone
                ), 'to_address' => array(
                    "name"    => "Ryan Quote",
                    "street1" =>  "1000 test rd",
                    "city"    => $ToCity,
                    "state"   => $ToState,
                    "zip"     => $ToZIPCode,
                )

            );
        } else {
            //TODO: create international Shipment adresses
        }

        return $addresses;
    }

    public function get_drop_shipment_order($Dropshipping)
    {

        //TODO: THIS NEEDS TO BE FIXED
        if (!empty($Dropshipping)) {

            foreach ($Dropshipping as $item_id => $value) {



                $item = $value['item'];

                $w = $item->get_width();

                $l = $item->get_length();

                $h = $item->get_height();

                $wt = $item->get_weight();

                $wtoz = $item->get_weight() * 16;

                $qty = $value['qty'];

                $parcel = null;

                for ($z = 0; $z < $qty; $z++) {

                    $parcel = $this->create_parcel_from_array(
                        array(
                            'width' => $w,
                            'length' => $l,
                            'height' => $h,
                            'weightoz' => $wtoz
                        )
                    );

                    $drop_shipments[] =  $parcel;

                    $domestic = in_array($package['destination']['country'], $this->domestic) ? true : false;
                    
                    if ($domestic) {



                        $requestdrop['Rate'] = $this->create_domestic_drop_rate($package, $value['vendor_name'], $value['vendor_address'], $value['vendor_city'], $value['vendor_st'], $value['vendor_zip'], $l, $w, $h, $wt, $wtoz);
                    } else {



                        $requestdrop['Rate'] = $this->create_non_domestic_drop_rate($package, $value['vendor_name'], $value['vendor_address'], $value['vendor_city'], $value['vendor_st'], $value['vendor_zip'], $l, $w, $h, $wt, $wtoz);
                    }
                }
            }
        }
    }

    public function calculate_shipping($package = array())
    {

        if (!class_exists('EasyPost')) {

            require_once(dirname(__FILE__) . "/lib/easypost.php");
        }

        if ($this->enable_standard_services) {

            //Load Dependencies

            require_once('loadit.php');

            //initialize Var
            $this->rates = array();
            $this->packed_boxes = null;
            $package['packed-boxes'] = array();
            $package['items-drop-shipping'] = array();


            //Pack Order Using DVDDoug's Modified Packing Methods
            //This will also take out any drop shipping packages and add them to the items_to_ship['drop'] var
            $this->packed_boxes = $this->pack_order($package);

            //Get the drop shipped packages
            $Dropshipping = $this->items_to_ship['drop'];

            //see if we actually need to go any further by counting the boxes we need to ship

            $number_of_boxes_shipping = $this->packed_boxes->count(); //+ count($Dropshipping);
            if (count($Dropshipping) != null) {
                $number_of_boxes_shipping = $number_of_boxes_shipping + count($Dropshipping);
            }

            if ($number_of_boxes_shipping < 1) {
                return;
            }

            libxml_use_internal_errors(true);

            $order = $this->create_easypost_order($package);

            if (($response = $this->get_easypost_response($order)) == null) {
                return;
            }


            if (($found_rates = $this->check_for_rates($response)) == null) {

                return;
            }

            $this->add_rates($found_rates, $package, $this->packed_boxes);

            return;
        }

        return;
    }

    public function get_weight_of_package($package)
    { }

    public function return_package_only_with($package, $with = '')
    {
        //$with can be 'forced', 'freight', 'free', 'dropship', 
        switch ($with) {
            case 'forced':
                return $this->get_forced($package);
            case 'freight':
                return $this->get_freight($package);
            case 'free':
                return $this->get_free($package);
            case 'dropship':
                return $this->get_dropship($package);
            default:
                return false;
        }
    }

    function get_forced($package)
    { }
    function get_freight($package)
    { }

    function get_free($package)
    { }

    function get_dropship($package)
    { }

    public function add_rates($found_rates, $package, \DVDoug\BoxPacker\PackedBoxList $packed_boxes)
    {

        foreach ($this->carrier as $carrier_name) {

            foreach ($found_rates as $service_type => $found_rate) {

                // Enabled check

                if ($carrier_name == $found_rate['carrier']) {

                    if (isset($this->custom_services[$carrier_name][$service_type]) && empty($this->custom_services[$carrier_name][$service_type]['enabled'])) {

                        continue;
                    }

                    $total_amount = $found_rate['cost'];

                    $weight = WC_Shipping_Drop_Live::convert_to_lbs(floatval($packed_boxes->getTotalWeight()));

                    if ($weight <= 0) {

                        $weight = WC()->cart->get_cart_contents_weight();
                        if ($weight <= 0) {
                            $weight = 1;
                        }
                    }

                    if (floatval($total_amount) > 0) {

                        //add extra cost per unit of weight

                        if (isset($this->cost_per_unit) && floatval($this->cost_per_unit) > 0) {
                            //get weight of order

                            //multiply weight times the amount to charge

                            if (floatval($weight) > 0) {

                                $added_cost = (floatval($weight) * floatval($this->cost_per_unit));

                                $total_amount = $total_amount + $added_cost;
                            }
                        }
                        //add extra cost per box

                        if (isset($this->cost) && floatval($this->cost) > 0) {

                            if ($packed_boxes->count() > 0) {

                                $extra_amount = floatval($this->packed_boxes->count()) * floatval($this->cost);

                                $total_amount = $total_amount + $extra_amount;
                            }
                        }
                    }

                    if (!empty($this->custom_services[$service_type][$service_type]['adjustment'])) {

                        $total_amount = $total_amount + floatval($this->custom_services[$carrier_name][$service_type]['adjustment']);
                    }

                    $labelName = !empty($this->services[$carrier_name][$service_type]['name']) ? $this->services[$carrier_name][$service_type]['name'] : $this->services[$carrier_name]['services'][$service_type];

                    $rate = array(
                        'id'      => (string) $this->id . ':' . $service_type,
                        'label'   => (string) $labelName,
                        'cost'    => (string) $total_amount,
                        'package' => $package,
                        'meta_data' => array(
                            'days' =>  (string) $found_rates[$service_type]['days'],
                            'total_weight' => floatval($weight),
                        )
                    );

                    // Register the rate
                    $this->add_rate($rate);
                }
            }
        }
    }


    public function check_for_rates($response)
    {

        $found_rates = array();

        if (isset($response->rates) && !empty($response->rates)) {

            foreach ($this->carrier as $carrier_name) {

                foreach ($response->rates as $easypost_rate) {

                    if ($carrier_name == $easypost_rate->carrier) {

                        $service_type = (string) $easypost_rate->service;

                        $service_name = (string) (isset($this->custom_services[$carrier_name][$service_type]['name']) && !empty($this->custom_services[$carrier_name][$service_type]['name'])) ? $this->custom_services[$carrier_name][$service_type]['name'] : $this->services[$carrier_name]['services'][$service_type];

                        $total_amount = $easypost_rate->rate;

                        $estimated_days = $easypost_rate->delivery_days;

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

            return array();
        }

        return $found_rates;
    }

    public function get_easypost_response($order)
    {

        try {




            \EasyPost\EasyPost::setApiKey($this->api_key);

            $order_response = \EasyPost\Order::create($order);

            return json_decode($order_response);
        } catch (Exception $e) {

            return null;
        }
    }

    public function create_easypost_order($package = array())
    {

        $shipments = array();

        //create parcels from packed boxes
        foreach (clone $this->packed_boxes as $packedBox) {

            $shipments[] =  $this->create_parcel($packedBox);
        }

        $addresses = $this->get_shipment_addresses($package);

        //TODO: Finiish Implimenting this
        //$drop_ship_order_response = $this->get_drop_shipment_order();


        return array(
            'order' => array(
                'to_address' => $addresses['to_address'],
                'from_address' => $addresses['from_address'],
                'shipments' => $shipments,
            )
        );
    }

    public function create_domestic_drop_rate($package, $drop_name, $drop_address, $drop_city, $drop_state, $drop_zip, $l, $w, $h, $wt, $wtoz)
    {




        return array(
            'FromName'          => isset($drop_name) ? $drop_name : "Name",
            'FromAddress'       => $drop_address,
            'FromCity'          => $drop_city,
            'FromState'         => $drop_state,
            'FromZIPCode'       => $drop_zip,
            'ToZIPCode'         => strtoupper(substr($package['destination']['postcode'], 0, 5)),
            'ToCountry'         => $package['destination']['country'],
            'WeightLb'          => floor($wt),
            'WeightOz'          => floatval($wtoz),
            'PackageType'       => 'Package',
            'Length'            => $l,
            'Width'             => $w,
            'Height'            => $h,
            'ShipDate'          => date("Y-m-d", (current_time('timestamp') + (60 * 60 * 24))),
            'InsuredValue'      => '0',
            'RectangularShaped' => 'true'
        );
    }

    public function create_domestic_rate($package, $name, $l, $w, $h, $wt, $wtoz)
    {

        $fromzip = str_replace(' ', '', strtoupper($this->get_option('zip')));
        $toZip = strtoupper(substr($package['destination']['postcode'], 0, 5));

        $shipment = array(
            'FromZIPCode'       => $fromzip,
            'ToZIPCode'         => $toZip,
            'ToCountry'         => $package['destination']['country'],
            'WeightLb'          => floor($wt),
            'WeightOz'          => floatval($wtoz),
            'PackageType'       => 'Package',
            'Length'            => $l,
            'Width'             => $w,
            'Height'            => $h,
            'ShipDate'          => date("Y-m-d", (current_time('timestamp') + (60 * 60 * 24))),
            'InsuredValue'      => '0',
            'RectangularShaped' => 'true'
        );

        return $shipment;
    }

    public function create_non_domestic_rate($package, $name, $l, $w, $h, $wt, $wtoz)
    {



        return array(
            'FromZIPCode'       => str_replace(' ', '', strtoupper($this->get_option('zip'))),
            'ToZIPCode'         => $package['destination']['postcode'],
            'ToCountry'         => $package['destination']['country'],
            'Amount'            => 100.00,
            'WeightLb'          => floor($wt),
            'WeightOz'          => floatval($wtoz),
            'PackageType'       => 'Package',
            'Length'            => $l,
            'Width'             => $w,
            'Height'            => $h,
            'ShipDate'          => date("Y-m-d", (current_time('timestamp') + (60 * 60 * 24))),
            'InsuredValue'      => '0',
            'RectangularShaped' => 'true'
        );
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



        $this->found_rates[$rate_id] = array(
            'id'       => $rate_id,
            'label'    => $rate_name,
            'cost'     => $rate_cost,
            'sort'     => $sort,
            'packages' => $packages
        );
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

        //$boxes = new \DVDoug\BoxPacker\BoxList();

        $this->items_temp = new \DVDoug\BoxPacker\ItemList();

        //$bulkq = 1;

        $quantity = 1;

        $this->items_to_ship['no_drop'] = $this->get_items_to_ship($package);

        if (!(count($this->items_to_ship['no_drop']) > 0)) {

            return;
        }

        $items = new \DVDoug\BoxPacker\ItemList();

        $boxes = $this->get_packing_boxes();

        foreach ($this->items_to_ship['no_drop'] as $post_id => $item_id) {

            $p = $package['contents'][$item_id];

            $quantity = $p['quantity'];

            $product_to_pack = wc_get_product($post_id);

            $dimensions = wc_get_product($post_id)->get_dimensions(false);

            $ld =  $dimensions['length'] <= 0 ? WC_Shipping_Drop_Live::convert_to_mm(13) : WC_Shipping_Drop_Live::convert_to_mm($dimensions['length']);

            $wd = $dimensions['width'] <= 0 ? WC_Shipping_Drop_Live::convert_to_mm(13) : WC_Shipping_Drop_Live::convert_to_mm($dimensions['width']);

            $hd = $dimensions['height'] <= 0 ? WC_Shipping_Drop_Live::convert_to_mm(13) : WC_Shipping_Drop_Live::convert_to_mm($dimensions['height']);

            $wtd = WC_Shipping_Drop_Live::convert_to_grams(wc_get_product($post_id)->get_weight());

            $l =  $product_to_pack->get_length() <= 0 ? WC_Shipping_Drop_Live::convert_to_mm(13) : WC_Shipping_Drop_Live::convert_to_mm($product_to_pack->get_length());

            $w = $product_to_pack->get_width() <= 0 ? WC_Shipping_Drop_Live::convert_to_mm(13) : WC_Shipping_Drop_Live::convert_to_mm($product_to_pack->get_width());

            $h = $product_to_pack->get_height() <= 0 ? WC_Shipping_Drop_Live::convert_to_mm(13) : WC_Shipping_Drop_Live::convert_to_mm($product_to_pack->get_height());

            $wt = WC_Shipping_Drop_Live::convert_to_grams($product_to_pack->get_weight());



            //loop through the quantity and for each on add it to the list

            for ($x = 1; $x < $quantity + 1; $x++) {

                $item_to_add = new packing_item($item_id . $x, $l, $w, $h, $wt, false);

                $items->insert($item_to_add);
            }
        }

        //pack all items into our shipping boxes and come back with an answer

        $packer = new \Box_Packer();

        $packer->setBoxes($boxes);

        $packer->setItems($items);

        $packedBoxes = $packer->pack();

        return $packedBoxes;
    }

    public function get_packing_boxes()
    {

        $box = array();



        $number_of_shipping_boxes = 0;



        $the_boxes = array(
            'post_type' => 'box',
        );



        $loop = new WP_Query($the_boxes);



        while ($loop->have_posts()) {



            $loop->the_post();

            $pid = get_the_ID();

            $l = floatval(get_post_meta($pid, 'box_length', true));

            $w = floatval(get_post_meta($pid, 'box_width', true));

            $h = floatval(get_post_meta($pid, 'box_height', true));

            $bw = floatval(get_post_meta($pid, 'box_weight', true));

            $mw = floatval(get_post_meta($pid, 'box_max_weight', true));



            $l = WC_Shipping_Drop_Live::convert_to_mm($l);

            $w = WC_Shipping_Drop_Live::convert_to_mm($w);

            $h = WC_Shipping_Drop_Live::convert_to_mm($h);

            $bw = WC_Shipping_Drop_Live::convert_to_grams($bw);

            $mw = WC_Shipping_Drop_Live::convert_to_grams($mw);





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



        $the_vendors = array(
            'post_type' => 'twds_vendor',
        );



        $loop = new WP_Query($the_vendors);



        while ($loop->have_posts()) {



            $loop->the_post();

            $pid = get_the_ID();

            $name = get_post($pid, 'title', true);

            $address = get_post_meta($pid, 'twds_vendor_address', true);

            $city = get_post_meta($pid, 'twds_vendor_city', true);

            $state = get_post_meta($pid, 'twds_vendor_state', true);

            $zip = get_post_meta($pid, 'twds_vendor_zip', true);

            $vendor[] = array(
                'name'    => $name,
                'address' => $address,
                'city'    => $city,
                'state'   => $state,
                'zip'     => $zip
            );
        }

        wp_reset_query();

        return $vendor;
    }

    public function get_items_to_ship($package)
    {



        $items_to_ship = array();



        $this->items_to_ship['no_drop'] = array();



        foreach ($package['contents'] as
            $item_id =>
            $values) {

            if (!$values['data']->needs_shipping()) {

                continue;
            }

            $found_shipping_class = $this->find_shipping_classes($values['data']);

            if ($found_shipping_class == 'free-shipping') {

                continue;
            }

            //Determine if needs dropped
            // $this->items_to_ship['drop'] = ;
            //Check custom shipping fields for flags that make this method not apply

            $post_id = $values['product_id'];

            $shipping_meta = get_post_meta($post_id, '', true);

            $p = $package['contents'][$item_id];

            $quantity = $p['quantity'];



            $product_stock = $values['data']->is_in_stock();

            $Product_stock_amount = $values['data']->get_stock_quantity();





            if ($Product_stock_amount < 1 && $product_stock) {



                if (isset($shipping_meta['twds_vendor_select'][0]) && $shipping_meta['twds_vendor_select'][0] != 'none') {



                    $vendor_meta = get_post_meta($shipping_meta['twds_vendor_select'][0], '', true);



                    $this->items_to_ship['drop'][$item_id] = array(
                        'item'           => $values['data'],
                        'vendor_name'    => $vendor_meta['twds_vendor_email'][0],
                        'vendor_address' => $vendor_meta['twds_vendor_address'][0],
                        'vendor_city'    => $vendor_meta['twds_vendor_city'][0],
                        'vendor_st'      => $vendor_meta['twds_vendor_state'][0],
                        'vendor_zip'     => $vendor_meta['twds_vendor_zip'][0],
                        'qty'            => $quantity,
                    );



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

    public function clear_transients()
    {



        global $wpdb;



        $wpdb->query("DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_easypost_quote_%') OR `option_name` LIKE ('_transient_timeout_easypost_quote_%')");
    }

    public  function generate_package_id($id, $qty, $length, $width, $height, $weight)
    {

        return implode(':', array(
            $id,
            $qty,
            $length,
            $width,
            $height,
            $weight
        ));
    }

    public function validate_services_field($key)
    {

        $services = array();

        $posted_services = $_POST['easypost_service'];



        foreach ($posted_services as
            $code =>
            $settings) {



            foreach ($this->services[$code]['services'] as $key => $name) {



                $services[$code][$key]['enabled'] = isset($settings[$key]['enabled']) ? true : false;

                $services[$code][$key]['order'] = wc_clean($settings[$key]['order']);
            }
        }



        return $services;
    }

    public function generate_services_html()
    {

        ob_start();

        include('html-twds-services.php');

        return ob_get_clean();
    }

    public function find_shipping_classes($item)
    {

        $found_shipping_class = '';

        if ($item->needs_shipping()) {

            $found_shipping_class = $item->get_shipping_class();
        }

        return $found_shipping_class;
    }
}

function WC_Shipping_Drop_Live()
{

    return WC_Shipping_Drop_Live::instance();
}
