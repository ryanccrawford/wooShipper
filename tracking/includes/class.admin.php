<?php



if ( ! defined( 'ABSPATH' ) ) {

    exit; // Exit if accessed directly

}



if ( !class_exists( "WOOPRO_SHT_Admin" ) ) {



    class WOOPRO_SHT_Admin extends WOOPRO_SHT_Common {



        /**

        * PHP 5 constructor

        **/

        function __construct() {

            $this->common_construct();

            register_activation_hook( $this->plugin_dir . 'woo-shipping-tracker-customer-notifications.php', array( &$this, 'activation' ), 100 );



            add_action( 'admin_enqueue_scripts', array( &$this, 'include_css_js' ), 99 );

            add_action( 'admin_init', array( &$this, 'meta_init' ) );

            add_action( 'woocommerce_process_shop_order_meta', array( &$this, 'save_meta' ) );

            add_filter( 'woocommerce_settings_tabs_array', array( &$this, 'settings_tabs_array' ), 21 );

            add_action( 'woocommerce_settings_woopro-shipping-tracker', array( $this, 'settings_page' ) );

            add_filter( 'plugin_action_links_' . plugin_basename( $this->plugin_dir . 'woo-shipping-tracker-customer-notifications.php' ), array( &$this, 'plugin_action_links' ), 99 );

        }





        function plugin_action_links( $links ) {

            $links[] = '<a href="'. esc_url( get_admin_url(null, 'admin.php?page=wc-settings&tab=woopro-shipping-tracker') ) .'">' . __( 'Settings', WOOPRO_SHT_TEXT_DOMAIN ) . '</a>';

            return $links;

        }





        function settings_tabs_array( $tabs ) {

            $tabs['woopro-shipping-tracker'] = __( 'Shipping Tracker', WOOPRO_SHT_TEXT_DOMAIN );

            return $tabs;

        }





        function settings_page() {

            include_once( 'admin/settings.php');

        }





        function save_meta( $post_id, $post = null ) {

            if ( !empty( $_POST['woopro_sht_var']['track_number'] ) ) {

                update_post_meta( $post_id, 'woopro_sht_deliverer', $_POST['woopro_sht_var']['deliverer'] );

                update_post_meta( $post_id, 'woopro_sht_track_number', $_POST['woopro_sht_var']['track_number'] );

                update_post_meta( $post_id, 'woopro_sht_postal_code', $_POST['woopro_sht_var']['postal_code'] );

            } else {

                delete_post_meta( $post_id, 'woopro_sht_deliverer' );

                delete_post_meta( $post_id, 'woopro_sht_track_number' );

                delete_post_meta( $post_id, 'woopro_sht_postal_code' );

            }

        }





        function get_track_urls() {

            return apply_filters( 'woopro_sht_get_track_urls', array(

                

                array(

                    'region' => 'United States',

                    'title' => 'Fedex',

                    'url' => 'http://www.fedex.com/Tracking?action=track&tracknumbers={track_number}'

                ),

                array(

                    'region' => 'United States',

                    'title' => 'FedEx Sameday',

                    'url' => 'https://www.fedexsameday.com/fdx_dotracking_ua.aspx?tracknum={track_number}'

                ),

                array(

                    'region' => 'United States',

                    'title' => 'OnTrac',

                    'url' => 'http://www.ontrac.com/trackingdetail.asp?tracking={track_number}'

                ),

                array(

                    'region' => 'United States',

                    'title' => 'UPS',

                    'url' => 'http://wwwapps.ups.com/WebTracking/track?track=yes&trackNums={track_number}'

                ),

                array(

                    'region' => 'United States',

                    'title' => 'USPS',

                    'url' => 'https://tools.usps.com/go/TrackConfirmAction_input?qtc_tLabels1={track_number}'

                )

            ) );

        }





        function include_css_js() {

            wp_register_style( 'woopro-si-admin-style', $this->plugin_url . 'assets/css/admin_style.css' );

            wp_enqueue_style( 'woopro-si-admin-style' );

        }





        function activation() {

            $settings = $this->get_track_urls();

            $new_settings = array();

            foreach( $settings as $key=>$val ) {

                $val['used'] = 1;

                $new_settings[ uniqid() ] = $val;

            }

            add_option( 'woopro_sht_settings', $new_settings );



            $general_settings = array(

                'order_completed' => sprintf( __( 'We have shipped your order via %s. Tracking number %s. Click here to track your shipment <a href="%s" target="_blank">%s</a>', WOOPRO_SHT_TEXT_DOMAIN ), '{shipping_provider}', '{track_number}', '{track_url}', '{track_url}' )

            );



            add_option( 'woopro_sht_general_settings', $general_settings );

        }





        function meta_init() {

            add_meta_box( 'woopro_sht_shipping_tracker', __( 'Shipping Settings', WOOPRO_SHT_TEXT_DOMAIN ), array( &$this, 'order_meta_box' ), 'shop_order', 'side', 'high' );

        }





        public function order_meta_box() {

            global $post;

            $selected_value = get_post_meta( $post->ID, 'woopro_sht_deliverer', true );

            $track_number = get_post_meta( $post->ID, 'woopro_sht_track_number', true );

            $postal_code = get_post_meta( $post->ID, 'woopro_sht_postal_code', true );

            $settings = get_option( 'woopro_sht_settings', array() );

            $provider_list = array();

            ?>

            <p>

                <label>

                    <?php _e( 'Provider', WOOPRO_SHT_TEXT_DOMAIN ) ?><br />

                    <select id="woopro_sht_tracker" name="woopro_sht_var[deliverer]" class="woopro_select" style="width:100%;">

                        <option value=""></option>

                        <?php foreach ( $settings as $id => $deliverer ) {

                            if( $deliverer['used'] == 1 || $id == $selected_value ) {

                                $provider_list[ $id ] = $deliverer;

                                ?>

                                <option value="<?php echo $id; ?>" <?php selected( $id, $selected_value ) ?> <?php disabled( $deliverer['used'] != 1 ) ?>><?php echo $deliverer['title']; ?></option>

                        <?php }

                        } ?>

                    </select>

                </label>

            </p>

            <p>

                <label>

                    <?php _e( 'Tracking Number', WOOPRO_SHT_TEXT_DOMAIN ) ?><br />

                    <input type="text" name="woopro_sht_var[track_number]" value="<?php echo $track_number ?>" />

                </label>

            </p>

            <p>

                <label>

                    <?php _e( 'Postal Code', WOOPRO_SHT_TEXT_DOMAIN ) ?><br />

                    <input type="text" name="woopro_sht_var[postal_code]" value="<?php echo $postal_code ?>" />

                </label>

            </p>

            <p class="track_link"><?php _e( 'Tracking Link:', WOOPRO_SHT_TEXT_DOMAIN );?> <a href="" target="_blank"><?php _e( 'Click here to track', WOOPRO_SHT_TEXT_DOMAIN ); ?></a></p>

            <script type="text/javascript">

                var provider_list = <?php echo json_encode( $provider_list ) ?>;



                jQuery(document).ready(function() {

                    jQuery('#woopro_sht_tracker').change( function() {

                        var value = jQuery(this).val();



                        jQuery('input[name="woopro_sht_var\[track_number\]"]').parents('p').hide();

                        jQuery('input[name="woopro_sht_var\[postal_code\]"]').parents('p').hide();

                        jQuery('.track_link').hide();



                        if( value != '' ) {

                            jQuery('input[name="woopro_sht_var\[track_number\]"]').parents('p').show();

                            if( typeof provider_list[ value ].url != 'undefined' && provider_list[ value ].url.indexOf( '{postal_code}' ) >= 0 ) {

                                jQuery('input[name="woopro_sht_var\[postal_code\]"]').parents('p').show();

                            }

                            jQuery('.track_link').show();

                            generate_track_url();

                        }

                    } ).change();

                    jQuery('input[name="woopro_sht_var\[track_number\]"], input[name="woopro_sht_var\[postal_code\]"]').blur( generate_track_url );

                });



                function generate_track_url() {

                    var deliverer = jQuery('#woopro_sht_tracker').val();

                    var track_number = jQuery('input[name="woopro_sht_var\[track_number\]"]').val();

                    var postal_code = jQuery('input[name="woopro_sht_var\[postal_code\]"]').val();

                    var url = '#';

                    if( deliverer != '' && typeof provider_list[ deliverer ].url != 'undefined' ) {

                        url = provider_list[ deliverer ].url.replace(/{track_number}/g, track_number);

                        url = url.replace(/{postal_code}/g, postal_code);

                    }

                    jQuery('.track_link > a').attr('href', url );

                }

            </script>

            <?php

        }





        function get_plugin_logo_block() {

            $html = '<div class="woopro_sht_logo"></div><hr />';

            ?>



            <?php

            return $html;

        }







    //end class

    }





    $GLOBALS['woopro_sht'] = new WOOPRO_SHT_Admin();

}