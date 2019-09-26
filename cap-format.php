<?php


/*
* Box Post Type for the Box Packer Shipping Plugin */
if (!class_exists('Cap_Format')) {

    class Cap_Format
    {

        public function __construct()
        {

            add_filter('woocommerce_get_order_address', array($this, 'create_caps'));
        }


        public function create_caps($type = 'billing')
        {
            return apply_filters('woocommerce_get_order_address', array_merge($this->data[$type], $this->get_prop($type, 'view')), $type, $this);
        }
    }

    new Cap_Format();
}
