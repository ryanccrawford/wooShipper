<?php

/*

  Plugin Name: Woocommerce Twdstore Custom Backend

  Plugin URI:

  Description:

  Version: 2.0.10

  Author: Ryan Crawford

  Author URI:

  License: GPLv2

 */



if (!defined('ABSPATH')) {

    die;
}



/**

 * Check if WooCommerce is active

 * */
if ((in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) || (in_array('rl-carriers-woocommerce-shipping-method\woocommerce-shipping-rlc.php', apply_filters('active_plugins', get_option('active_plugins'))))) {

    require_once(dirname(__FILE__) . '/../woocommerce/woocommerce.php');

    require_once(dirname(__FILE__) . '/../rl-carriers-woocommerce-shipping-method/woocommerce-shipping-rlc.php');

    require_once(dirname(__FILE__) . '/loadit.php');

    require_once(dirname(__FILE__) . '/box-post-type.php');

    require_once(dirname(__FILE__) . '/drop-shippers.php');

    require_once(dirname(__FILE__) . '/shipping-time.php');

    require_once(dirname(__FILE__) . '/shipping-options.php');

    //require_once (dirname(__FILE__) . '/print-shipping-lables.php');

    // require_once (dirname(__FILE__) . '/category-filter.php');

    require_once(dirname(__FILE__) . '/twds-custom-woo.php');

    require_once(dirname(__FILE__) . '/tracking/tracking.php');

    require_once(dirname(__FILE__) . '/cap-format.php');

    if (class_exists('WC_Shipping_Method')) {

        function shipping_method_init()
        {


            require_once(dirname(__FILE__) . '/twds-shipping-ups-usps.php');
        }

        function add_method($methods)
        {

            $methods['drop_live'] = 'WC_Shipping_Drop_Live';

            return $methods;
        }

        add_action('woocommerce_shipping_init', 'shipping_method_init');

        add_filter('woocommerce_shipping_methods', 'add_method');

        add_filter('woocommerce_product_single_add_to_cart_text', 'woo_custom_cart_button_text');    // 2.1 +

        function woo_custom_cart_button_text()
        {

            return __('Buy Now!', 'woocommerce');
        }

        add_filter('woocommerce_product_add_to_cart_text', 'woo_archive_custom_cart_button_text');    // 2.1 +

        function woo_archive_custom_cart_button_text()
        {

            return __('Buy Now!', 'woocommerce');
        }



        function adjust_shipping_rates($rates, $package)
        {

            $freight_cost = 0;
            $other = array();
            $other_cost = 0;

            $has_freight = false;
            $mostCurrentRates = array();
            $totalWeight = WC()->cart->get_cart_contents_weight();
            if (isset($rates) && count($rates) > 0) {
                $mostCurrentRates = $rates;
            } else {
                $mostCurrentRates = $package['rates'];
            }

            foreach ($mostCurrentRates as $id => $rate) {


                if ($rate->method_id === 'rlc') {
                    $has_freight = true;
                    $amount = $rate->cost;

                    $amount_t = floatval($amount);

                    if ($amount_t > 0) {

                        $freight_cost = floatval($amount_t);
                    }
                } elseif ($rate->method_id != 'local_pickup') {

                    $amount = $rate->cost;

                    $amount_t = floatval($amount);

                    if ($amount_t > 0) {

                        $other[] = $amount_t;
                    }
                }
            }

            if ($other != null && count($other) > 0) {
                $other_cost = min($other);
            }
            if (count($other) == 0 && $has_freight) {
                return $mostCurrentRates;
            }
            $isitforced = WC_Shipping_RLC()->is_package_forced_freight($package);
            if (($isitforced  || $totalWeight > 149) || ($freight_cost != 0 && ($other_cost < $freight_cost) && count($other) > 0)) {

                foreach ($mostCurrentRates as $id => $rate) {

                    if ($isitforced || $totalWeight > 149) {
                        foreach ($mostCurrentRates as $id => $rate) {

                            if ($rate->method_id != 'rlc' && $rate->method_id != 'local_pickup') {

                                unset($mostCurrentRates[$id]);
                            }
                        }
                        return $mostCurrentRates;
                    }
                    if (!($isitforced) && $rate->method_id == 'rlc') {

                        unset($mostCurrentRates[$id]);
                    }
                }
            } elseif ((count($other) > 0) && ($freight_cost != 0) && (min($other) > $freight_cost)) {

                foreach ($mostCurrentRates as $id => $rate) {

                    if ($rate->method_id != 'rlc' && $rate->method_id != 'local_pickup') {

                        unset($mostCurrentRates[$id]);
                    }
                }
            } else {


                foreach ($mostCurrentRates as $id => $rate) {

                    if ($rate->method_id == 'rlc') {

                        unset($mostCurrentRates[$id]);
                    }
                }
            }


            return $mostCurrentRates;
        }

        register_activation_hook(__FILE__, 'WC_Shipping_Drop_Live_create');

        function WC_Shipping_Drop_Live_create()
        {


            global $wpdb;

            $charset_collate = $wpdb->get_charset_collate();

            $table_name = $wpdb->prefix . 'wc_shipping_drop_live';

            $version = get_option('my_plugin_version', '1.0');


            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                `id` bigint(20) UNSIGNED UNSIGNED NOT NULL AUTO_INCREMENT,
                `session_id` bigint(20) UNSIGNED NOT NULL,
                `drop_live_object` text DEFAULT NULL,
                `time_stamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            dbDelta($sql);
            if ($wpdb->last_error) {
                echo "My nuts: " . $wpdb->last_error . ' error';
            }
        }

        function weight_before_shipping_calculator()
        {

            $weight = WC()->cart->get_cart_contents_weight();


            if (isset($weight)) {


                echo '<tr class="shipping-weight"><th>Shipping Weight</th><td>' . $weight . ' lbs.</td></tr>';
            }
        }

        add_filter('woocommerce_package_rates', 'adjust_shipping_rates', 10, 2);
        add_action('woocommerce_before_shipping_calculator', 'weight_before_shipping_calculator');

        add_filter('gettext',  'register_text');
        add_filter('ngettext',  'register_text');

        function register_text($translated)
        {
            $translated = str_ireplace('Register',  'Sign Up',  $translated);
            return $translated;
        }
    }
}
