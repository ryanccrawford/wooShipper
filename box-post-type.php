<?php


/*
* Box Post Type for the Box Packer Shipping Plugin */
if (!class_exists('Box_Post_Type')) {

    class Box_Post_Type
    {

        public function __construct()
        {

            add_filter('manage_edit-box_columns', array($this,  'edit_box_columns'));
            add_action('manage_box_posts_custom_column', array($this,  'manage_box_columns'), 10, 2);
            add_action('init', array($this, 'boxes_post_type'));
            add_filter('post_updated_messages', array($this, 'boxes_post_type_messages'));
            add_action('add_meta_boxes', array($this, 'dimensions_meta'));
            add_action('save_post', array($this, 'func_save_meta_now'));
            add_filter('wp_insert_post_data', array($this, 'modify_box_title'), '99', 1);
        }


        public function edit_box_columns($columns)
        {

            $columns = array(

                'cb'        => '<input type="checkbox" />',
                'title'     => __('Box'),
                'length'    => __('Length'),
                'width'     => __('Width'),
                'height'    => __('Height'),
                'capacity'  => __('Capacity')
            );

            return $columns;
        }

        public function manage_box_columns($column, $post_id)
        {

            switch ($column) {
                case 'length':
                        
                    $length = get_post_meta($post_id, 'box_length', true);

                    if (empty($length)) {
                        echo __('Unknown');
                    } else {
                        echo __('%s inches'), $length;
                    }

                    break;

                case 'width':
                    $width = get_post_meta($post_id, 'box_width', true);

                    if (empty($width)) {
                        echo __('Unknown');
                    } else {
                        echo  __('%s inches'), $width;
                    }

                    break;

                case 'height':
                    $height = get_post_meta($post_id, 'box_height', true);

                    if (empty($height)) {
                        echo __('Unknown');
                    } else {
                        echo  __('%s inches'), $height;
                    }
                    break;

                case 'capacity':
                    $capacity = get_post_meta($post_id, 'box_max_weight', true);

                    if (empty($capacity)) {
                        echo __('Unknown');
                    } else {
                        echo __('%s lbs'), $capacity;
                    }

                    break;

                default:
                    break;
            }
        }

        public function boxes_post_type()
        {

            $labels = array(
                'name' => 'Boxes',
                'singular_name' => 'Box',
                'menu_name' => 'Shipping Boxes',
                'name_admin_bar' => 'Boxes',
                'add_new' => 'Add New',
                'add_new_item' => 'Add New Box',
                'new_item' => 'New Box',
                'edit_item' => 'Edit Box',
                'view_item' => 'View Box',
                'all_items' => 'All Boxes',
                'search_items' => 'Search Boxes',
                'parent_item_colon' => 'Parent Boxes:',
                'not_found' => 'No boxes found.',
                'not_found_in_trash' => 'No boxes found in Trash.'
            );

            $args = array(
                'public' => false,
                'exclude_from_search' => true,
                'show_ui' => true,
                'show_in_admin_bar' => true,
                'hierarchical' => false,
                'supports ' => array(
                    'title',
                    'custom-fields',
                ),
                'menu_position' => 20,
                'menu_icon' => 'dashicons-archive',
                'labels' => $labels,
                'description' => 'Shipping boxes used for shippments'
            );
            register_post_type('box', $args);

            remove_post_type_support('box', 'editor');

            //remove_post_type_support( 'box', 'title' );
        }

        public function boxes_post_type_messages($messages)
        {
            $messages['box'] = array(
                0 => '',
                1 => 'Box updated.',
                2 => 'Field updated.',
                3 => 'Field deleted.',
                4 => 'Box updated.',
                5 => '',
                6 => 'Box created.',
                7 => 'Box saved.',
                8 => 'Box submitted.',
                9 => '',
                10 => 'Box draft updated.',
            );

            return $messages;
        }

        public function dimensions_meta()
        {

            add_meta_box('box_dimensions_box', 'Box Dimensions', 'box_dimensions_box_content', 'box', 'normal', 'high');

            function box_dimensions_box_content($post)
            {

                $post_id = get_the_ID();

                wp_nonce_field(basename(__FILE__), 'box_dimensions_box_content_nonce');

                $stored_meta = get_post_meta($post_id);

                ?>

                    <label for="box_length">Length</label>
                    <input type="text" id="box_length" name="box_length" placeholder="size in inches" value="<?php

                                                                                                                                if (isset($stored_meta['box_length'][0])) {
                                                                                                                                    echo $stored_meta['box_length'][0];
                                                                                                                                } ?>" />
                    </br>
                    <label for="box_width">Width</label>
                    <input type="text" id="box_width" name="box_width" placeholder="size in inches" value="<?php
                                                                                                                            if (isset($stored_meta['box_width'][0])) {
                                                                                                                                echo $stored_meta['box_width'][0];
                                                                                                                            } ?>" />
                    </br>
                    <label for="box_height">Height</label>
                    <input type="text" id="box_height" name="box_height" placeholder="size in inches" value="<?php
                                                                                                                                if (isset($stored_meta['box_height'][0])) {
                                                                                                                                    echo $stored_meta['box_height'][0];
                                                                                                                                } ?>" />
                    </br>
                    <label for="box_weight">Weight</label>
                    <input type="text" id="box_weight" name="box_weight" placeholder="size in lbs" value="<?php
                                                                                                                            if (isset($stored_meta['box_weight'][0])) {
                                                                                                                                echo $stored_meta['box_weight'][0];
                                                                                                                            } ?>" />
                    </br>
                    <label for="box_max_weight">Max Weight</label>
                    <input type="text" id="box_max_weight" name="box_max_weight" placeholder="weight in lbs" value="<?php
                                                                                                                                    if (isset($stored_meta['box_max_weight'][0])) {
                                                                                                                                        echo $stored_meta['box_max_weight'][0];
                                                                                                                                    } ?>" />
                    </br>
    <?php
                }
            }

            public function func_save_meta_now()
            {
                $post_id = get_the_ID();

                // Verify that the nonce is valid.

                $nonce = $_POST['box_dimensions_box_content_nonce'];
                if (!wp_verify_nonce($nonce, basename(__FILE__))) {
                    return $post_id;
                }
                if (defined('DOING_AUTOSAVE')) {
                    return $post_id;
                }
                if ('box' == $_POST['post_type']) {
                    if (!current_user_can('edit_page', $post_id)) {
                        return $post_id;
                    }
                } else {
                    if (!current_user_can('edit_post', $post_id)) {
                        return $post_id;
                    }
                }
                $box_length = sanitize_text_field($_POST['box_length']);

                update_post_meta($post_id, 'box_length', $box_length);

                $box_width = sanitize_text_field($_POST['box_width']);

                update_post_meta($post_id, 'box_width', $box_width);

                $box_height = sanitize_text_field($_POST['box_height']);

                update_post_meta($post_id, 'box_height', $box_height);

                $box_weight = sanitize_text_field($_POST['box_weight']);

                update_post_meta($post_id, 'box_weight', $box_weight);

                $box_max_weight = sanitize_text_field($_POST['box_max_weight']);

                update_post_meta($post_id, 'box_max_weight', $box_max_weight);
            }

            public function modify_box_title($data)
            {

                if ($data['post_type'] == 'box' && isset($_POST['box_length'])) {
                    $box_length = sanitize_text_field($_POST['box_length']);

                    $box_width = sanitize_text_field($_POST['box_width']);

                    $box_height = sanitize_text_field($_POST['box_height']);

                    $box_max_weight = sanitize_text_field($_POST['box_max_weight']);

                    $title = $box_length . 'X' . $box_width . 'X' . $box_height . ' ' . $box_max_weight . 'Lbs Capacity';

                    $data['post_title'] = $title;
                }
                return $data;
            }
        }

        new Box_Post_Type();
    }
