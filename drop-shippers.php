<?php



/* * * Drop Shippers   */

/** * Description of drop-shippers * * @author ryan */



if (!class_exists('Shipping_Vendors')) {

    class Shipping_Vendors

    {



        public function __construct()

        {

            add_action('init', array($this,                'twds_vendor_post_type'));

            add_filter('manage_edit_twds_vendor_columns', array($this,                'edit_twds_vendor_columns'));

            add_filter('post_updated_messages', array($this,                'twds_vendor_post_type_messages'));

            add_action('add_meta_boxes', array($this,                'twds_vendor_meta'));

            add_action('save_post', array($this,                'func_save_twsd_vendor_meta_now'));
        }
        public function edit_twds_vendor_columns($columns)

        {

            $columns = array('cb'      => '<input type="checkbox" />',                'title'   => __('Drop Shipper'),                'address' => __('Address'),                'city'    => __('City'),                'state'   => __('State'),                'zip'     => __('Zip'),                'email'   => __('Email'),);

            return $columns;
        }
        public function manage_twds_vendor_columns($column, $post_id)

        {

            switch ($column) {

                case 'address':

                    $address = get_post_meta($post_id, 'twds_vendor_address', true);

                    if (empty($address)) {

                        echo __('Unknown');
                    } else {

                        echo __($address);
                    }

                    break;

                case 'city':

                    $city = get_post_meta($post_id, 'twds_vendor_city', true);

                    if (empty($city)) {

                        echo __('Unknown');
                    } else {

                        echo __($city);
                    }

                    break;

                case 'state':

                    $state = get_post_meta($post_id, 'twds_vendor_state', true);

                    if (empty($state)) {

                        echo __('Unknown');
                    } else {

                        echo __($state);
                    }

                    break;

                case 'zip':

                    $zip = get_post_meta($post_id, 'twds_vendor_zip', true);

                    if (empty($zip)) {

                        echo __('Unknown');
                    } else {

                        echo __($zip);
                    }

                    break;

                case 'email':

                    $email = get_post_meta($post_id, 'twds_vendor_email', true);

                    if (empty($email)) {

                        echo __('Unknown');
                    } else {

                        echo __($email);
                    }

                    break;

                default:

                    break;
            }
        }
        public function twds_vendor_post_type()

        {

            $labels = array(

                'name'  => 'Vendors',

                'singular_name'      => 'Vendor',

                'menu_name'          => 'Drop Shipping Vendors',

                'name_admin_bar'     => 'Vendors',

                'add_new'            => 'Add New',

                'add_new_item'       => 'Add New Vendor',

                'new_item'           => 'New Vendor',

                'edit_item'          => 'Edit Vendor',

                'view_item'          => 'View Vendor',

                'all_items'          => 'All Vendors',

                'search_items'       => 'Search Vendors',

                'parent_item_colon'  => 'Parent Vendors:',

                'not_found'          => 'No Vendors found.',

                'not_found_in_trash' => 'No Vendors found in Trash.'

            );



            $args = array(

                'public'              => false,

                'exclude_from_search' => true,

                'show_ui'             => true,

                'show_in_admin_bar'   => true,

                'hierarchical'        => false,

                'supports '           => array(

                    'title',

                    'custom-fields',

                ),

                'menu_position'       => 21,

                'menu_icon'           => 'dashicons-store',

                'labels'              => $labels,

                'description'         => 'Shipping Vendors used for shippments'

            );



            register_post_type('twds_vendor', $args);



            remove_post_type_support('twds_vendor', 'editor');



            //remove_post_type_support( 'twds_vendor', 'title' );

        }



        public function twds_vendor_post_type_messages($messages)

        {



            $messages['twds_vendor'] = array(

                0  => '',

                1  => 'Vendor updated.',

                2  => 'Field updated.',

                3  => 'Field deleted.',

                4  => 'Vendor updated.',

                5  => '',

                6  => 'Vendor created.',

                7  => 'Vendor saved.',

                8  => 'Vendor submitted.',

                9  => '',

                10 => 'Vendor draft updated.',

            );

            return $messages;
        }





        public function twds_vendor_meta()

        {



            add_meta_box('twds_vendor_box', 'Vendor Shipping Address', array(

                $this,

                'twds_vendor_box_content'
            ), 'twds_vendor', 'normal', 'high');
        }



        public function twds_vendor_box_content($post)

        {



            $post_id = get_the_ID();



            wp_nonce_field(basename(__FILE__), 'twds_vendor_box_content_nonce');



            $stored_meta = get_post_meta($post_id);

            ?>



                <label for="twds_vendor_address">Address</label>

                <input type="text" id="twds_vendor_address" name="twds_vendor_address" placeholder="Address" value="<?php

                                                                                                                                if (isset($stored_meta['twds_vendor_address'][0])) {

                                                                                                                                    echo $stored_meta['twds_vendor_address'][0];
                                                                                                                                }            ?>" /></br>

                <label for="twds_vendor_city">City</label>

                <input type="text" id="twds_vendor_city" name="twds_vendor_city" placeholder="City" value="<?php if (isset($stored_meta['twds_vendor_city'][0])) {

                                                                                                                            echo $stored_meta['twds_vendor_city'][0];
                                                                                                                        }            ?>" /></br>

                <?php



                            $states['US'] = array('AL' => __('Alabama', 'woocommerce'),     'AK' => __('Alaska', 'woocommerce'),  'AZ' => __('Arizona', 'woocommerce'),     'AR' => __('Arkansas', 'woocommerce'),    'CA' => __('California', 'woocommerce'),  'CO' => __('Colorado', 'woocommerce'),    'CT' => __('Connecticut', 'woocommerce'),     'DE' => __('Delaware', 'woocommerce'),    'DC' => __('District Of Columbia', 'woocommerce'),    'FL' => __('Florida', 'woocommerce'),     'GA' => _x('Georgia', 'US state of Georgia', 'woocommerce'),  'HI' => __('Hawaii', 'woocommerce'),  'ID' => __('Idaho', 'woocommerce'),   'IL' => __('Illinois', 'woocommerce'),    'IN' => __('Indiana', 'woocommerce'),     'IA' => __('Iowa', 'woocommerce'),    'KS' => __('Kansas', 'woocommerce'),  'KY' => __('Kentucky', 'woocommerce'),    'LA' => __('Louisiana', 'woocommerce'),   'ME' => __('Maine', 'woocommerce'),   'MD' => __('Maryland', 'woocommerce'),    'MA' => __('Massachusetts', 'woocommerce'),   'MI' => __('Michigan', 'woocommerce'),    'MN' => __('Minnesota', 'woocommerce'),   'MS' => __('Mississippi', 'woocommerce'),     'MO' => __('Missouri', 'woocommerce'),    'MT' => __('Montana', 'woocommerce'),     'NE' => __('Nebraska', 'woocommerce'),    'NV' => __('Nevada', 'woocommerce'),  'NH' => __('New Hampshire', 'woocommerce'),   'NJ' => __('New Jersey', 'woocommerce'),  'NM' => __('New Mexico', 'woocommerce'),  'NY' => __('New York', 'woocommerce'),    'NC' => __('North Carolina', 'woocommerce'),  'ND' => __('North Dakota', 'woocommerce'),    'OH' => __('Ohio', 'woocommerce'),    'OK' => __('Oklahoma', 'woocommerce'),    'OR' => __('Oregon', 'woocommerce'),  'PA' => __('Pennsylvania', 'woocommerce'),    'RI' => __('Rhode Island', 'woocommerce'),    'SC' => __('South Carolina', 'woocommerce'),  'SD' => __('South Dakota', 'woocommerce'),    'TN' => __('Tennessee', 'woocommerce'),   'TX' => __('Texas', 'woocommerce'),   'UT' => __('Utah', 'woocommerce'),    'VT' => __('Vermont', 'woocommerce'),     'VA' => __('Virginia', 'woocommerce'),    'WA' => __('Washington', 'woocommerce'),  'WV' => __('West Virginia', 'woocommerce'),   'WI' => __('Wisconsin', 'woocommerce'),   'WY' => __('Wyoming', 'woocommerce'),     'AA' => __('Armed Forces (AA)', 'woocommerce'),   'AE' => __('Armed Forces (AE)', 'woocommerce'),   'AP' => __('Armed Forces (AP)', 'woocommerce'),);

                            echo  '<label for="twds_vendor_state">State</label>';

                            echo '<select id="twds_vendor_state" name="twds_vendor_state" class="select short">';

                            foreach ($states['US'] as $k => $v) {

                                echo '<option value="' . $v . '"' . selected(($stored_meta['twds_vendor_state'][0]), $v, true) . "" . '>' .  $v . '</option>';
                            }
                            echo '</select> ';              ?></br> <label for="twds_vendor_zip">Zip</label> <input type="text" id="twds_vendor_zip" name="twds_vendor_zip" placeholder="Zip" value="<?php if (isset($stored_meta['twds_vendor_zip'][0])) {

                                                                                                                                                                                                                        echo $stored_meta['twds_vendor_zip'][0];
                                                                                                                                                                                                                    }            ?>" /></br> <label for="twds_vendor_email">Email</label> <input type="text" id="twds_vendor_email" name="twds_vendor_email" placeholder="Email" value="<?php if (isset($stored_meta['twds_vendor_email'][0])) {

                                                                                                                                                                                                                                                                                                                                                                                                            echo $stored_meta['twds_vendor_email'][0];
                                                                                                                                                                                                                                                                                                                                                                                                        }                   ?>" /></br> <?php

                                                                                                                                                                                                                                                        }
                                                                                                                                                                                                                                                        public function func_save_twsd_vendor_meta_now()

                                                                                                                                                                                                                                                        {

                                                                                                                                                                                                                                                            $post_id = get_the_ID();

                                                                                                                                                                                                                                                            // Verify that the nonce is valid.

                                                                                                                                                                                                                                                            $nonce = $_POST['twds_vendor_box_content_nonce'];

                                                                                                                                                                                                                                                            if (!wp_verify_nonce($nonce, basename(__FILE__))) {

                                                                                                                                                                                                                                                                return $post_id;
                                                                                                                                                                                                                                                            }
                                                                                                                                                                                                                                                            if (defined('DOING_AUTOSAVE')) {

                                                                                                                                                                                                                                                                return $post_id;
                                                                                                                                                                                                                                                            }
                                                                                                                                                                                                                                                            if ('twds_vendor' == $_POST['post_type']) {

                                                                                                                                                                                                                                                                if (!current_user_can('edit_page', $post_id)) {

                                                                                                                                                                                                                                                                    return $post_id;
                                                                                                                                                                                                                                                                }
                                                                                                                                                                                                                                                            } else {

                                                                                                                                                                                                                                                                if (!current_user_can('edit_post', $post_id)) {

                                                                                                                                                                                                                                                                    return $post_id;
                                                                                                                                                                                                                                                                }
                                                                                                                                                                                                                                                            }
                                                                                                                                                                                                                                                            $twds_vendor_address = $_POST['twds_vendor_address'];

                                                                                                                                                                                                                                                            update_post_meta($post_id, 'twds_vendor_address', $twds_vendor_address);

                                                                                                                                                                                                                                                            $twds_vendor_city = $_POST['twds_vendor_city'];

                                                                                                                                                                                                                                                            update_post_meta($post_id, 'twds_vendor_city', $twds_vendor_city);

                                                                                                                                                                                                                                                            $twds_vendor_state = $_POST['twds_vendor_state'];

                                                                                                                                                                                                                                                            update_post_meta($post_id, 'twds_vendor_state', $twds_vendor_state);

                                                                                                                                                                                                                                                            $twds_vendor_zip = $_POST['twds_vendor_zip'];

                                                                                                                                                                                                                                                            update_post_meta($post_id, 'twds_vendor_zip', $twds_vendor_zip);

                                                                                                                                                                                                                                                            $twds_vendor_email = $_POST['twds_vendor_email'];

                                                                                                                                                                                                                                                            update_post_meta($post_id, 'twds_vendor_email', $twds_vendor_email);
                                                                                                                                                                                                                                                        }
                                                                                                                                                                                                                                                    }
                                                                                                                                                                                                                                                    new Shipping_Vendors();
                                                                                                                                                                                                                                                }
