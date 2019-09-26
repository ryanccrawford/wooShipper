<?php
if (!class_exists('TWDS_Custom_Woo')) {

    class TWDS_Custom_Woo {

        public function __construct() {

            add_action('woocommerce_product_options_general_product_data', array($this, 'twdstore_add_custom_general_fields'));

            add_action('woocommerce_product_options_inventory_product_data', array($this, 'twdstore_add_custom_inventory_fields'));

            add_action('woocommerce_process_product_meta', array($this, 'twdstore_add_custom_general_fields_save'));

            add_action('woocommerce_process_product_meta', array($this, 'twdstore_add_custom_inventory_fields_save'));

            add_action('before_shipping_information_twdstore', array($this, 'twdstore_before_display'));

            add_action('after_shipping_information_twdstore', array($this, 'twdstore_display_custom_data'));
        }

        public function twdstore_add_custom_inventory_fields() {

            echo '<div class="options_group">';

            woocommerce_wp_text_input(
                    array(
                        'id'                => 'bulk_qty_is',
                        'label'             => __('If bulk how many come in bulk :', 'woocommerce'),
                        'placeholder'       => '',
                        'description'       => __('<p>This is for display on the frontend the number of items come in this bulk package.</P>', 'woocommerce'),
                        'type'              => 'number',
                        'custom_attributes' => array(
                            'step' => '1',
                            'min'  => '0'
                        )
                    )
            );
            echo '</div>';
        }

        public function twdstore_add_custom_general_fields() {

            echo '<div class="options_group">';

            woocommerce_wp_text_input(
                    array(
                        'id'          => 'mpn',
                        'label'       => __('MPN', 'woocommerce'),
                        'placeholder' => '',
                        'type'        => 'text',
                    )
            );

            // Select

            array(
                'id'      => 'condition_select',
                'label'   => __('Product Condition', 'woocommerce'),
                'options' => array(
                    'New'         => __('New', 'woocommerce'),
                    'Used'        => __('Used', 'woocommerce'),
                    'Refurbished' => __('Refurbished', 'woocommerce')
                )
            );


            echo '</div>';
        }

        function twdstore_display_custom_data() {

            global $post;


            $handling_time = get_post_meta($post->ID, 'hand_time_select', true);

            echo '<li class="fa fa-calendar" aria-hidden="true"> Availability ';
            if (wc_get_product($post->ID)->get_stock_quantity() < 1 && wc_get_product($post->ID)->get_backorders() == 'no') {
                $handling_time = ' Call to Order';
            
                
            } elseif
                (wc_get_product($post->ID)->get_stock_quantity() < 1 && wc_get_product($post->ID)->get_backorders() == 'yes') {
                
                $handling_time = ' Back Ordered';

            }elseif (wc_get_product($post->ID)->get_stock_quantity() < 1 && wc_get_product($post->ID)->get_backorders() == 'hide') {

                $handling_time = '3 - 4 Days (Drop Shipped)';

            }elseif ($handling_time === '24') {
            
                echo ' - ';

                echo '24 Hours';

                echo '</li>';
                
            }elseif ($handling_time === '48') {


                echo ' - ';

                echo '1 - 2 Days';

                echo '</li>';
            }

            elseif ($handling_time === '36') {

                echo ' - ';

                echo '2 - 3 Days';

                echo '</li>';
            }


            elseif ($handling_time === '96') {


               
                echo ' - ' .

                '3 - 4 Days' .

                '</li>';
            }



            elseif (empty($handling_time)) {

            
                echo ' - ';

                echo 'Can Vary';

                echo '</li>';
            }elseif ($handling_time === 'Call to Order' || $handling_time === 'Back Ordered' || $handling_time = '3 - 4 Days (Drop Shipped)') {

                

                echo ' - ' . $handling_time;

                echo '</li>';
            }




            $condition = get_post_meta($post->ID, 'condition_select', true);


            if (empty($condition)){
    
                $condition = "New";
                
            }
    
  
            if ($condition === "New" || $condition === "new") {

                echo '<li class="fa fa-battery-full" aria-hidden="true">';
            }

            if ($condition === "Used" || $condition === "used") {


                echo '<li class="fa fa-battery-half" aria-hidden="true">';

            }

            if ($condition === "Refurbished" || $condition === "refurbished") {

                echo '<li class="fa fa-recycle" aria-hidden="true">';
            }

                echo ' Product Condition - ' . $condition;

                echo '</li>';


            echo '</ul>';
        }

        function twdstore_add_custom_general_fields_save($post_id) {


            // Text Field


            $mpn = $_POST['mpn'];


            if (!empty($mpn)) {


                update_post_meta($post_id, 'mpn', esc_attr($mpn));
            }

            // Select


            $condition_select = $_POST['condition_select'];




            if (!empty($condition_select)) {

                update_post_meta($post_id, 'condition_select', esc_attr($condition_select));
            }
        }

        function twdstore_add_custom_inventory_fields_save($post_id) {



            // Text Field


            $bulk_qty_is = $_POST['bulk_qty_is'];


            if (!empty($bulk_qty_is)) {


                update_post_meta($post_id, 'bulk_qty_is', esc_attr($bulk_qty_is));
            }
        }

        function twdstore_before_display() {

            global $post;


            //apply_filters( 'woocommerce_product_title', $title, $this );


            $bulk_qty_is = get_post_meta($post->ID, 'bulk_qty_is', true);


            if (!empty($bulk_qty_is)) {
                ?>

                <div class="bulk-qty">BULK ITEM SOLD IN LOTS OF <?php echo $bulk_qty_is; ?>. QTY OF ONE BUYS ONE BULK LOT</div>

                <?php
            }
        }

    }

    new TWDS_Custom_Woo();
}