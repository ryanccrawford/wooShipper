<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( !class_exists( "WOOPRO_SHT_Ajax" ) ) {

    class WOOPRO_SHT_Ajax extends WOOPRO_SHT_Common {

        /**
        * PHP 5 constructor
        **/
        function __construct() {

            $this->common_construct();

            add_action( 'wp_ajax_sht_providers', array( &$this, 'ajax_providers' ) );
        }

        function ajax_providers() {
            $action = isset( $_POST['act'] ) ? $_POST['act'] : '';
            switch( $_POST['act'] ) {
                case 'add':
                    $used = isset( $_POST['used'] ) ? $_POST['used'] : 0;
                    if( isset( $_POST['title'] ) && !empty( $_POST['title'] ) ) {
                        $title = $_POST['title'];
                    } else {
                        exit( json_encode( array( 'status' =>false, 'message' => __('Title is empty', WOOPRO_SHT_TEXT_DOMAIN ) ) ) );
                    }
                    if( isset( $_POST['region'] ) && !empty( $_POST['region'] ) ) {
                        $region = $_POST['region'];
                    } else {
                        exit( json_encode( array( 'status' =>false, 'message' => __('Region field is empty', WOOPRO_SHT_TEXT_DOMAIN ) ) ) );
                    }
                    if( isset( $_POST['url'] ) && !empty( $_POST['url'] ) ) {
                        $url = $_POST['url'];
                    } else {
                        exit( json_encode( array( 'status' =>false, 'message' => __('URL is empty', WOOPRO_SHT_TEXT_DOMAIN ) ) ) );
                    }

                    $settings = get_option( 'woopro_sht_settings', array() );

                    $key = uniqid();
                    $settings[ $key ] = array(
                        'used' => $used,
                        'title' => $title,
                        'region' => $region,
                        'url' => $url
                    );

                    update_option( 'woopro_sht_settings', $settings );

                    exit( json_encode( array( 'status' => true, 'message' => $key ) ) );
                    break;
                case 'edit':
                    if( isset( $_POST['id'] ) && !empty( $_POST['id'] ) ) {
                        $key = $_POST['id'];
                    } else {
                        exit( json_encode( array( 'status' =>false, 'message' => __('Wrong ID value.', WOOPRO_SHT_TEXT_DOMAIN ) ) ) );
                    }

                    $used = isset( $_POST['used'] ) ? $_POST['used'] : 0;
                    if( isset( $_POST['title'] ) && !empty( $_POST['title'] ) ) {
                        $title = $_POST['title'];
                    } else {
                        exit( json_encode( array( 'status' =>false, 'message' => __('Title is empty', WOOPRO_SHT_TEXT_DOMAIN ) ) ) );
                    }
                    if( isset( $_POST['region'] ) && !empty( $_POST['region'] ) ) {
                        $region = $_POST['region'];
                    } else {
                        exit( json_encode( array( 'status' =>false, 'message' => __('Region field is empty', WOOPRO_SHT_TEXT_DOMAIN ) ) ) );
                    }
                    if( isset( $_POST['url'] ) && !empty( $_POST['url'] ) ) {
                        $url = $_POST['url'];
                    } else {
                        exit( json_encode( array( 'status' =>false, 'message' => __('URL is empty', WOOPRO_SHT_TEXT_DOMAIN ) ) ) );
                    }

                    $settings = get_option( 'woopro_sht_settings', array() );

                    $settings[ $key ] = array(
                        'used' => $used,
                        'title' => $title,
                        'region' => $region,
                        'url' => $url
                    );

                    update_option( 'woopro_sht_settings', $settings );
                    exit( json_encode( array( 'status' => true, 'message' => 'Success' ) ) );
                    break;
                case 'delete':
                    if( isset( $_POST['id'] ) && !empty( $_POST['id'] ) ) {
                        $id = $_POST['id'];
                    } else {
                        exit( json_encode( array( 'status' =>false, 'message' => __('Key is empty', WOOPRO_SHT_TEXT_DOMAIN ) ) ) );
                    }

                    $settings = get_option( 'woopro_sht_settings', array() );

                    if( isset( $settings[ $id ] ) ) {
                        unset( $settings[ $id ] );
                    }

                    update_option( 'woopro_sht_settings', $settings );
                    exit( json_encode( array( 'status' => true, 'message' => 'Success' ) ) );
                    break;
                case 'set_used':
                    if( isset( $_POST['id'] ) && !empty( $_POST['id'] ) ) {
                        $id = $_POST['id'];
                    } else {
                        exit( json_encode( array( 'status' =>false, 'message' => __('Key is empty', WOOPRO_SHT_TEXT_DOMAIN ) ) ) );
                    }
                    $value = 0;
                    if( isset( $_POST['value'] ) && !empty( $_POST['value'] ) ) {
                        $value = $_POST['value'];
                    }

                    $settings = get_option( 'woopro_sht_settings', array() );

                    if( isset( $settings[ $id ] ) ) {
                        $settings[ $id ]['used'] = $value;
                    } else {
                        exit( json_encode( array( 'status' =>false, 'message' => __('Key is wrong', WOOPRO_SHT_TEXT_DOMAIN ) ) ) );
                    }

                    update_option( 'woopro_sht_settings', $settings );
                    exit( json_encode( array( 'status' => true, 'message' => 'Success' ) ) );
                    break;
                case 'get_data':
                    if( isset( $_POST['id'] ) && !empty( $_POST['id'] ) ) {
                        $id = $_POST['id'];
                    } else {
                        exit( json_encode( array( 'status' =>false, 'message' => __('Key is empty', WOOPRO_SHT_TEXT_DOMAIN ) ) ) );
                    }

                   $settings = get_option( 'woopro_sht_settings', array() );
                    if( isset( $settings[ $id ] ) ) {
                        exit( json_encode( array( 'status' => true, 'message' => $settings[ $id ] ) ) );
                    } else {
                        exit( json_encode( array( 'status' =>false, 'message' => __('Key is wrong', WOOPRO_SHT_TEXT_DOMAIN ) ) ) );
                    }

                    break;
            }

            exit;
        }


    //end class
    }

    $GLOBALS['woopro_sht'] = new WOOPRO_SHT_Ajax();
}