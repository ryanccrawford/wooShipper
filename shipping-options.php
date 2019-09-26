<?php

/*
 *  Shipping Options
 * @author ryan

 */
if (!class_exists('Shipping_Options')) {


    class Shipping_Options
    {

        public function __construct()
        {

            add_action('woocommerce_product_options_shipping', array($this, 'twdstore_add_shipping_fields'));
            add_action('woocommerce_process_product_meta', array($this,  'twdstore_add_shipping_save'));
            //add_filter('woocommerce_cart_shipping_packages', array($this, 'custom_split_shipping_packages_shipping_class'));
            add_action('woocommerce_single_product_summary', array($this, 'twdstore_display_shipping_options'));
        }

        function twdstore_add_shipping_fields()
        {

            echo '<div class="options_group">';



            echo '<h3>TWDStore Per Item Forced Shipping Options</h3>';
            //
            //            // Forced Shipping Cost
            //
            woocommerce_wp_text_input(
                array(
                    'id' => 'shipping_cost_number_field',
                    'label' => __('Force Shipping Cost $', 'woocommerce'),
                    'placeholder' => '',
                    'description' => __('<p>Enter a cost to have forced for this item. Amount here will be used for shiping cost. Calaulated by multiplying the qty ordered by this amount. The weight and size will not be used in box packing.</P>', 'woocommerce'),                     'type' => 'number',
                    'default' => '',
                    'custom_attributes' => array(
                        'step' => 'any',
                        'min' => '-1'
                    )
                )
            );

            woocommerce_wp_text_input(
                array(
                    'id' => 'additional_cost',
                    'label' => __('Handeling Fee Per Item ', 'woocommerce'),
                    'placeholder' => '0.00',
                    'description' => __('<p>This amount will be added to all shipping rates returned as part of the total cost. It is per qty buying * this amount + cost of shiping returned by the Live Rates.</P>', 'woocommerce'),
                    'type' => 'number',
                    'custom_attributes' => array(
                        'step' => 'any',
                        'min' => '0'
                    )
                )
            );



            // Select

            //            woocommerce_wp_select(
            //                    array(
            //                        'id' => 'shipper_select',
            //                        'label' => __('Shipper', 'woocommerce'),
            //                        'description' => __('<p>If you set a forced cost abouve, you must select a shipper, since they can\'t choose one.</P>', 'woocommerce'),
            //                        'default' => array(
            //                            '0' => ''),
            //                        'options' => array(
            //                            '0' => '',
            //                            'UPS' => __('UPS', 'woocommerce'),
            //                            'USPS' => __('USPS', 'woocommerce'),
            //                            'LTL' => __('LTL Freight R+L', 'woocommerce'),
            //                            'UPSFREIGHT' => __('LTL Freight UPS', 'woocommerce')
            //                        )
            //                    )
            //            );



            // Select



            woocommerce_wp_select(
                array(
                    'id' => 'hand_time_select',
                    'label' => __('Handling Time', 'woocommerce'),
                    'options' => array(
                        '24' => __('24 Hours', 'woocommerce'),
                        '36' => __('2 - 3 Days', 'woocommerce'),
                        '96' => __('3 - 5 Days', 'woocommerce'),
                        '100' => __('1 - 2 Weeks', 'woocommerce'),
                    )
                )
            );



            echo '</div>';



            echo '<div class="options_group"><h3>Drop Shipping Options</h3>';



            $drop_shippers = array();



            $the_vendors = array(
                'post_type' => 'twds_vendor',
            );



            $loop = new WP_Query($the_vendors);

            $drop_shippers[0] = 'None';

            while ($loop->have_posts()) {



                $loop->the_post();

                $name = the_title('', '', false);

                $id = get_the_ID();

                $drop_shippers[$id] = $name;
            }

            wp_reset_query();



            woocommerce_wp_select(
                array(
                    'id' => 'twds_vendor_select',
                    'label' => __('Drop Shipper', 'woocommerce'),
                    'description' => __('Select the drop ship vendor', 'woocommerce'),
                    'default' => array(
                        '0' => 'None'
                    ),
                    'options' => $drop_shippers,
                )
            );





            //            woocommerce_wp_text_input(
            //                    array(
            //                        'id' => 'number_of_packages',
            //                        'label' => __('Number of Packages ', 'woocommerce'),
            //                        'placeholder' => '0.00',
            //                        'description' => __('<p>Number of packages</P>', 'woocommerce'),
            //                        'type' => 'number',
            //                        'default' => '1',
            //                        'custom_attributes' => array(
            //                            'step' => 'any',
            //                            'min' => '1'
            //                        )
            //                    )
            //            );







            //            woocommerce_wp_select(
            //                    array(
            //                        'id' => 'shipping_restrictions',
            //                        'label' => __('Shipping Restrictions', 'woocommerce'),
            //                        'options' => array(
            //                            '1' => __('Can ship anywhere', 'woocommerce'),
            //                            '2' => __('Can ship only within USA', 'woocommerce'),
            //                            '4' => __('Lower 48 States Only', 'woocommerce'),
            //                        ),
            //                        'default' => '4',
            //                    )
            //            );



            echo '</div>';
        }

        function twdstore_add_shipping_save($post_id)
        {

            //------------------------------------------
            //First segment for shipping mod calculations
            //-------------------------------------------

            $shipping_cost_number_field = sanitize_text_field($_POST['shipping_cost_number_field']);

            if (!empty($shipping_cost_number_field)) {
                update_post_meta($post_id, 'shipping_cost_number_field', esc_attr($shipping_cost_number_field));
            }



            // Select
            //
            //            $shipper_select = sanitize_text_field($_POST['shipper_select']);
            //
            //            if (!empty($shipper_select)) {
            //
            //                update_post_meta($post_id, 'shipper_select', esc_attr($shipper_select));
            //            }



            // Select

            $handling_time_select = sanitize_text_field($_POST['hand_time_select']);

            if (!empty($handling_time_select)) {



                update_post_meta($post_id, 'hand_time_select', esc_attr($handling_time_select));
            }





            //------------------------------------
            //Second Mod for Easy Post Integration
            //------------------------------------
            //Save number_of_packages

            //            $number_of_packages = sanitize_text_field($_POST['number_of_packages']);
            //
            //            if (!empty($number_of_packages)) {
            //
            //
            //
            //                update_post_meta($post_id, 'number_of_packages', esc_attr($number_of_packages));
            //            }



            //Save additional_cost

            $additional_cost = sanitize_text_field($_POST['additional_cost']);

            if (!empty($additional_cost)) {



                update_post_meta($post_id, 'additional_cost', esc_attr($additional_cost));
            }



            //Save shipping_restrictions

            //            $shipping_restrictions = $_POST['shipping_restrictions'];
            //
            //            if (!empty($shipping_restrictions)) {
            //
            //
            //
            //                update_post_meta($post_id, 'shipping_restrictions', esc_attr($shipping_restrictions));
            //            }
            //
            //



            $twds_vendor_select = sanitize_text_field($_POST['twds_vendor_select']);

            if (!empty($twds_vendor_select)) {

                update_post_meta($post_id, 'twds_vendor_select', esc_attr($twds_vendor_select));
            }
        }

        function twdstore_display_shipping_options()
        {

            global $post;
            $shipping_cost = '';
            $product = wc_get_product($post->ID);
            $found_shipping_class = $product->get_shipping_class();
            $shipper = get_post_meta($post->ID, 'shipper_select', true);

            if ($found_shipping_class == 'free-shipping') {

                $shipping_cost = '0';
            } else {


                $shipping_cost = get_post_meta($post->ID, 'shipping_cost_number_field', true);
            }



            echo '<div class="start-twdstore-info">';
            echo '<h2>Quick Facts</h2>';
            do_action('before_shipping_information_twdstore');



            echo ' <ul class="custom-twdstore-data">';

            if (!strlen($shipping_cost)) {



                echo '<li class="fa fa-truck" aria-hidden="true">';

                echo ' Shipping - Calculated at checkout';

                echo '</li>';
            } elseif ($shipping_cost === '0' || $shipping_cost === 0) {

                echo '<li class="fa fa-truck" aria-hidden="true">';

                echo ' Shipping - Free Shipping';

                echo '</li>';
            } elseif ($shipping_cost > 0) {

                echo '<li class="fa fa-truck" aria-hidden="true">';

                echo ' Shipping -  $ ' . $shipping_cost;

                echo ' via ';

                if ($shipper == "LTL") {

                    echo 'R+L LTL Freight';

                    echo '</li>';
                } elseif ($shipper == "UPSFREIGHT") {

                    echo 'Freight';

                    echo '</li>';
                } else {

                    echo $shipper;

                    echo '</li>';
                }
            }

            do_action('after_shipping_information_twdstore');

            echo '</div>';
        }

        public function find_shipping_classes($item)
        {

            $found_shipping_class = '';

            if ($item->needs_shipping()) {

                $found_shipping_class = $item->get_shipping_class();
            }

            return $found_shipping_class;
        }

        function custom_split_shipping_packages_shipping_class($packages)
        {

            // Reset all packages

            $packages = array();

            $regular_package_items = array();

            $free_package_items = array();

            $drop_package_items = array();

            $freight_package_items = array();

            $ups_usps_fedex_items = array();

            $freight_classes = array(
                '50-0',
                '55-0',
                '60-0',
                '65-0',
                '70-0',
                '77-5',
                '85-0',
                '92-5',
                '100-0',
                '110-0',
                '125-0',
                '150-0',
                '175-0',
                '200-0',
                '250-0',
                '300-0',
                '400-0',
                '500-0'
            );



            foreach (WC()->cart->get_cart() as
                $item_key =>
                $item) {

                if ($item['data']->needs_shipping()) {

                    $ship_class = $item['data']->get_shipping_class();

                    switch ($ship_class) {

                        case 'drop-shipped':

                            $drop_package_items[$item_key] = $item;

                            break;

                        case 'free-shipping':

                            $free_package_items[$item_key] = $item;

                            break;

                        case 'calculated-shipping':

                            $ups_usps_fedex_items[$item_key] = $item;

                            break;

                        default:
                            $flag = false;
                            foreach ($freight_classes as
                                $value) {

                                if ($value == $ship_class) {

                                    $freight_package_items[$item_key] = $item;

                                    $flag = true;
                                }
                            }
                            if ($flag) {

                                $flag = false;

                                break;
                            } else {
                                $regular_package_items[$item_key] = $item;
                            }
                            break;
                    }
                }
            }

            // Create shipping packages

            if ($regular_package_items) {

                $packages[] = array(
                    'contents' => $regular_package_items,
                    'contents_cost' => array_sum(wp_list_pluck($regular_package_items, 'line_total')),
                    'applied_coupons' => WC()->cart->get_applied_coupons(),
                    'user' => array(
                        'ID' => get_current_user_id(),
                    ),
                    'destination' => array(
                        'country' => WC()->customer->get_shipping_country(),
                        'state' => WC()->customer->get_shipping_state(),
                        'postcode' => WC()->customer->get_shipping_postcode(),
                        'city' => WC()->customer->get_shipping_city(),
                        'address' => WC()->customer->get_shipping_address(),
                        'address_2' => WC()->customer->get_shipping_address_2()
                    )
                );
            }



            if ($ups_usps_fedex_items) {

                $packages[] = array(
                    'contents' => $ups_usps_fedex_items,
                    'contents_cost' => array_sum(wp_list_pluck($ups_usps_fedex_items, 'line_total')),
                    'applied_coupons' => WC()->cart->get_applied_coupons(),
                    'user' => array(
                        'ID' => get_current_user_id(),
                    ),
                    'destination' => array(
                        'country' => WC()->customer->get_shipping_country(),
                        'state' => WC()->customer->get_shipping_state(),
                        'postcode' => WC()->customer->get_shipping_postcode(),
                        'city' => WC()->customer->get_shipping_city(),
                        'address' => WC()->customer->get_shipping_address(),
                        'address_2' => WC()->customer->get_shipping_address_2()
                    )
                );
            }

            if ($free_package_items) {

                $packages[] = array(
                    'contents' => $free_package_items,
                    'contents_cost' => 0,
                    //'applied_coupons' => WC()->cart->get_applied_coupons(),
                    'user' => array(
                        'ID' => get_current_user_id(),
                    ),
                    'destination' => array(
                        'country' => WC()->customer->get_shipping_country(),
                        'state' => WC()->customer->get_shipping_state(),
                        'postcode' => WC()->customer->get_shipping_postcode(),
                        'city' => WC()->customer->get_shipping_city(),
                        'address' => WC()->customer->get_shipping_address(),
                        'address_2' => WC()->customer->get_shipping_address_2()
                    )
                );
            }



            if ($drop_package_items) {

                $packages[] = array(
                    'contents' => $drop_package_items,
                    'contents_cost' => array_sum(wp_list_pluck($drop_package_items, 'line_total')),
                    'applied_coupons' => WC()->cart->get_applied_coupons(),
                    'user' => array(
                        'ID' => get_current_user_id(),
                    ),
                    'destination' => array(
                        'country' => WC()->customer->get_shipping_country(),
                        'state' => WC()->customer->get_shipping_state(),
                        'postcode' => WC()->customer->get_shipping_postcode(),
                        'city' => WC()->customer->get_shipping_city(),
                        'address' => WC()->customer->get_shipping_address(),
                        'address_2' => WC()->customer->get_shipping_address_2()
                    )
                );
            }

            if ($freight_package_items) {

                $packages[] = array(
                    'contents' => $freight_package_items,
                    'contents_cost' => array_sum(wp_list_pluck($freight_package_items, 'line_total')),
                    'applied_coupons' => WC()->cart->get_applied_coupons(),
                    'user' => array(
                        'ID' => get_current_user_id(),
                    ),
                    'destination' => array(
                        'country' => WC()->customer->get_shipping_country(),
                        'state' => WC()->customer->get_shipping_state(),
                        'postcode' => WC()->customer->get_shipping_postcode(),
                        'city' => WC()->customer->get_shipping_city(),
                        'address' => WC()->customer->get_shipping_address(),
                        'address_2' => WC()->customer->get_shipping_address_2()
                    )
                );
            }



            return $packages;
        }
    }

    new Shipping_Options();
}
