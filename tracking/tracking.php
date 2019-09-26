<?php
//PACKAGE TRACKING 
if ( ! class_exists( 'WC_Dependencies' ) ){
    require_once 'woo-includes/class-wc-dependencies.php';
}
   
    define( 'WOOPRO_SHT_VER', '1.0.7' );

    if( !defined('WOOPRO_SHT_TEXT_DOMAIN') ) {
      define('WOOPRO_SHT_TEXT_DOMAIN', 'woo-shipping-tracker-customer-notifications');
    }

    require_once 'includes/class.common.php';

    if ( defined( 'DOING_AJAX' ) ) {
        require_once 'includes/class.ajax.php';
    } elseif ( is_admin() ) {
        require_once 'includes/class.admin.php';
    }



