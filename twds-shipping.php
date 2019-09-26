<?php

// Exit if accessed directly}
if (!defined('ABSPATH')) {
        exit; 
}

        /** * UPS and USPS class */
        
        if (!class_exists('TWDS_Easypost_WooCommerce_Shipping')) {  
              class TWDS_Easypost_WooCommerce_Shipping {    
                        
                public  function __construct() {     
                    add_action('woocommerce_shipping_init', array( $this, 'shipping_init'));  
                    add_filter('woocommerce_shipping_methods', array( $this,'add_method'));      
                                       
                }      
                                               
                public function shipping_init() { 
                                                    
                     include_once( 'includes/class-twds-shipping.php' );     
                                                             
                }    
                
                public function add_method($methods) {   
                                                                                        
                    $methods[] = 'TWDS_Shipping';        
                    return $methods;        
                }      
            
            }     
            
            new TWDS_Easypost_WooCommerce_Shipping();               
                      
        }