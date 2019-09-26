<?php

/* * Print_Shipping_Lables */
if (!class_exists('Print_Shipping_Lables')) {

    class Print_Shipping_Lables
    {
        function __construct()
        {
            add_action('woocommerce_order_actions', array($this, 'add_order_meta_box_actions'));
            add_action('woocommerce_order_action_print_postage', array($this, 'process_order_meta_box_actions'));
            add_action('add_meta_boxes', array($this, 'es_add_boxes'));
        }
        function es_add_boxes()
        {
            add_meta_box('easypost_data', __('EasyPost', 'woocommerce'), array($this, 'woocommerce_easypost_meta_box'), 'shop_order', 'normal', 'low');
        }
        function woocommerce_easypost_meta_box($post)
        {
            print sprintf("<a href='%2\$s' style='text-align:center;display:block;'><img style='max-width:%1\$s' src='%2\$s' ></a>", '450px', get_post_meta($post->ID, 'easypost_shipping_label', true));
        }

        function add_order_meta_box_actions($actions)
        {
            $actions['print_postage'] = 'Purchase Shipping Lables';

            return $actions;
        }

        function process_order_meta_box_actions($order)
        {

            $order_id = $order->id;
            try {

                $order = new WC_Order($order_id);

                $shipping = $order->get_formatted_shipping_address();

                $method = $order->get_shipping_methods();

                $method = array_values($method);

                $shipping_method = $method[0]['method_id'];

                $ship_arr = explode('|', $shipping_method);

                if (count($ship_arr) <= 2) {
                    require_once('lib/easypost-php/lib/easypost.php');
                    $shipment = \EasyPost\Shipment::retrieve($ship_arr[0]);

                    $shipment->to_address->name = sprintf("%s %s", $order->shipping_first_name, $order->shipping_last_name);

                    $shipment->to_address->phone = $order->billing_phone;

                    $parcel = \EasyPost\Parcel::create(
                        array(
                            "length" => $shipment->parcel->length,
                            "width" => $shipment->parcel->width,
                            "height" => $shipment->parcel->height,
                            "predefined_package" => null,
                            "weight" => $shipment->parcel->weight,
                        )
                    );
                    $from_address = \EasyPost\Address::create(
                        array(
                            "company" => $shipment->from_address->company,
                            "street1" => $shipment->from_address->street1,
                            "street2" => $shipment->from_address->street2,
                            "city" => $shipment->from_address->city,
                            "state" => $shipment->from_address->state,
                            "zip" => $shipment->from_address->zip,
                            "phone" => $shipment->from_address->phone,
                        )
                    );
                    $to_address = \EasyPost\Address::create(
                        array(
                            "name" => sprintf("%s %s", $order->shipping_first_name, $order->shipping_last_name),
                            "street1" => $shipment->to_address->street1,
                            "street2" => $shipment->to_address->street2,
                            "city" => $shipment->to_address->city,
                            "state" => $shipment->to_address->state,
                            "zip" => $shipment->to_address->zip,
                            "phone" => $order->billing_phone
                        )
                    );

                    $shipment = \EasyPost\Shipment::create(
                        array(
                            "from_address" => $from_address,
                            "to_address" => $to_address,
                            "parcel" => $parcel,
                        )
                    );
                    $rates = $shipment->get_rates();
                    foreach ($shipment->rates as $idx => $r) {
                        if (sprintf("%s-%s", $r->carrier, $r->service) == $ship_arr[0]) {
                            $index = $idx;
                            break;
                        }
                    }
                    $shipment->buy($shipment->rates[$index]);
                    update_post_meta($order_id, 'easypost_shipping_label', $shipment->postage_label->label_url);
                    $order->add_order_note(sprintf("Your shipping label is available here: '%s'", $shipment->postage_label->label_url));
                }
            } catch (Exception $e) {            //mail('seanvoss@gmail.com', 'Error from WordPress - EasyPost', var_export($e,1));        }  
            }
        }
    }
    new Print_Shipping_Lables();
}
