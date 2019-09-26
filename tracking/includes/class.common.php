<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( !class_exists( "WOOPRO_SHT_Common" ) ) {

    class WOOPRO_SHT_Common {

        var $plugin_dir;
        var $plugin_url;

        var $plugin;

        protected $_entities;

        protected static $_instance = null;
        var $prefix = 'woopro-sht';


        public function __clone() {
            _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', WOOPRO_SI_TEXT_DOMAIN ), '2.1' );
        }


        public function __wakeup() {
            _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', WOOPRO_SI_TEXT_DOMAIN ), '2.1' );
        }

        /**
        * Main constructor
        **/
        public function common_construct() {

            //setup proper directories
            if ( is_multisite() && defined( 'WPMU_PLUGIN_URL' ) && defined( 'WPMU_PLUGIN_DIR' ) && file_exists( WPMU_PLUGIN_DIR . '/woo-shipping-tracker-customer-notifications.php' ) ) {
                $this->plugin_dir = WPMU_PLUGIN_DIR . '/woo-shipping-tracker-customer-notifications/';
                $this->plugin_url = WPMU_PLUGIN_URL . '/woo-shipping-tracker-customer-notifications/';
            } else if ( defined( 'WP_PLUGIN_URL' ) && defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/woo-shipping-tracker-customer-notifications/woo-shipping-tracker-customer-notifications.php' ) ) {
                $this->plugin_dir = WP_PLUGIN_DIR . '/woo-shipping-tracker-customer-notifications/';
                $this->plugin_url = WP_PLUGIN_URL . '/woo-shipping-tracker-customer-notifications/';
            } else if ( defined('WP_PLUGIN_URL' ) && defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/woo-shipping-tracker-customer-notifications.php' ) ) {
                $this->plugin_dir = WP_PLUGIN_DIR;
                $this->plugin_url = WP_PLUGIN_URL;
            }

            //set plugin data
            $this->_set_plugin_data();
            add_action( 'plugins_loaded', array( &$this, '_load_textdomain' ) );

            add_action( 'woocommerce_view_order', array( $this, 'display_tracking_info' ) );
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_display' ) );
            add_filter( 'wc_customer_order_csv_export_order_headers', array( $this, 'add_tracking_info_to_csv_export_column_headers' ) );
            add_filter( 'wc_customer_order_csv_export_order_row', array( $this, 'add_tracking_info_to_csv_export_column_data' ), 10, 3 );
            add_filter( 'woocommerce_subscriptions_renewal_order_meta_query', array( $this, 'woocommerce_subscriptions_renewal_order_meta_query' ), 10, 4 );

        }

        public function display_tracking_info( $order_id, $for_email = false ) {
            $tracking_provider = get_post_meta( $order_id, 'woopro_sht_deliverer', true );
            $tracking_number   = get_post_meta( $order_id, 'woopro_sht_track_number', true );
            $postal_code          = get_post_meta( $order_id, 'woopro_sht_postal_code', true );

            if ( empty( $tracking_number ) ) {
                return '';
            }

            if ( empty( $postal_code ) ) {
                $postal_code = get_post_meta( $order_id, '_billing_postcode', true );
            }

            $settings = get_option( 'woopro_sht_settings', array() );
            $general_settings = get_option( 'woopro_sht_general_settings', array() );
            if ( !empty( $tracking_provider ) && !empty( $settings[ $tracking_provider ]['url'] ) ) {
                $link = str_replace( array('{track_number}', '{postal_code}'), array( $tracking_number, $postal_code ), $settings[ $tracking_provider ]['url'] );
                $order = wc_get_order( $order_id );

                if( $order->status == 'completed' ) {
                    echo wpautop( str_replace( array( '{shipping_provider}', '{track_number}', '{track_url}' ), array( $settings[ $tracking_provider ]['title'], $tracking_number, $link ), $general_settings['order_completed'] ) );
                }
            }
        }

        public function email_display( $order ) {
            $this->display_tracking_info( $order->id, true );
        }

        public function add_tracking_info_to_csv_export_column_headers( $headers ) {

            $headers['tracking_provider']        = 'tracking_provider';
            $headers['tracking_number']          = 'tracking_number';
            $headers['postal_code']              = 'postal_code';

            return $headers;
        }

        public function add_tracking_info_to_csv_export_column_data( $order_data, $order, $csv_generator ) {
            $tracking_provider = get_post_meta( $order->ID, 'woopro_sht_deliverer', true );
            $tracking_number   = get_post_meta( $order->ID, 'woopro_sht_track_number', true );
            $postal_code          = get_post_meta( $order->ID, 'woopro_sht_postal_code', true );

            $tracking_data = array(
                'tracking_provider'        => $tracking_provider,
                'tracking_number'          => $tracking_number,
                'postal_code'              => $postal_code
            );

            $new_order_data = array();

            if ( isset( $csv_generator->order_format ) && ( 'default_one_row_per_item' == $csv_generator->order_format || 'legacy_one_row_per_item' == $csv_generator->order_format ) ) {
                foreach ( $order_data as $data ) {
                    $new_order_data[] = array_merge( (array) $data, $tracking_data );
                }
            } else {
                $new_order_data = array_merge( $order_data, $tracking_data );
            }

            return $new_order_data;
        }

        public function woocommerce_subscriptions_renewal_order_meta_query( $order_meta_query, $original_order_id, $renewal_order_id, $new_order_role ) {
            $order_meta_query .= " AND `meta_key` NOT IN ( 'woopro_sht_deliverer', 'woopro_sht_track_number', 'woopro_sht_postal_code' )";
            return $order_meta_query;
        }


        /**
         * Load translate textdomain file.
         */
        function _load_textdomain() {
            load_plugin_textdomain( WOOPRO_SHT_TEXT_DOMAIN, false, dirname( 'woo-shipping-tracker-customer-notifications/woo-shipping-tracker-customer-notifications.php' ) . '/languages/' );
        }

        /*
        *
        */
        function _set_plugin_data() {
            //default values
            $this->plugin['title'] = 'Woocommerce Shipping Tracker';

        }

        /*
        * JS redirect
        */
        function js_redirect( $url ) {

            //for blank redirects
            if ( '' == $url ) {
                $url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
            }

            $funtext="echo \"<script type='text/javascript'>window.location = '" . $url . "'</script>\";";
            register_shutdown_function(create_function('',$funtext));

            if ( 1 < ob_get_level() ) {
                while ( ob_get_level() > 1 ) {
                    ob_end_clean();
                }
            }

            ?>
                <script type="text/javascript">
                    window.location = '<?php echo $url; ?>';
                </script>
            <?php
            exit;
        }

    //end class
    }

}