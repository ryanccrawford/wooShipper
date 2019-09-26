<?php

/*
 *  Shipping Options
 * @author ryan

 */
if (!class_exists('Category_Hide_Category')) {


    class Category_Hide_Category
    {

        public function __construct()
        {

            add_filter('get_terms', array($this, 'get_subcategory_terms'), 10, 3);
            //add_action('woocommerce_product_options_shipping', array($this, 'twdstore_add_shipping_fields'));
            //add_action('woocommerce_process_product_meta', array($this,  'twdstore_add_shipping_save'));
            // add_action('woocommerce_single_product_summary', array($this, 'twdstore_display_shipping_options'));
        }

        function get_subcategory_terms($terms, $taxonomies, $args)
        {

            $new_terms = array();

            // if a product category and on the shop page
            if (in_array('product_cat', $taxonomies) && !is_admin() && is_shop()) {

                foreach ($terms as $key => $term) {

                    if (!in_array($term->slug, array('donation'))) {
                        $new_terms[] = $term;
                    }
                }

                $terms = $new_terms;
            }

            return $terms;
        }
    }

    new Category_Hide_Category();
}
