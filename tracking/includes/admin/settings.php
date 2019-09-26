<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

global $wpdb, $hide_save_button;

$hide_save_button = true;

$general_settings = get_option( 'woopro_sht_general_settings', array() );
$settings = get_option( 'woopro_sht_settings', array() );
if ( isset( $_POST['update_settings'] ) ) {
    if ( isset( $_POST['settings'] ) ) {
        $new_settings = $_POST['settings'];
    } else {
        $new_settings = array();
    }

    $general_settings = array_merge( $general_settings, $new_settings );
    update_option( 'woopro_sht_general_settings', $general_settings );
}
?>

<style type="text/css">
    .woopro_warning {
        background-color: #FFFFE0;
        border-color: #E6DB55;
        border-radius: 3px 3px 3px 3px;
        border-style: solid;
        border-width: 1px;
        color: #000000;
        font-family: sans-serif;
        font-size: 12px;
        line-height: 1.4em;
        padding: 12px;
    }

    .woopro_si_error {
        border-color: red !important;
    }

</style>

<div class='wrap'>

    <div id="woopro_logo_block">
        <a href="http://woopro.com" title="Go to WooPro site!" target="_blank" ><img src="<?php echo $this->plugin_url . 'assets/images/woopro_logo.png' ?>" /></a>
        <h2><?php printf( __( '%s Settings', WOOPRO_SHT_TEXT_DOMAIN ), $this->plugin['title'] ) ?></h2>
    </div>

    <div style="width: 100%;">

        <div id="tab-container">

                <div class="postbox">
                    <h3 class="hndle"><span><?php _e('General Settings', WOOPRO_SHT_TEXT_DOMAIN); ?></span></h3>
                    <div class="inside sht_general_settings">
                        <p>
                            <label>
                                <span class="title"><?php _e('Template for orders with completed shipping', WOOPRO_SHT_TEXT_DOMAIN); ?></span><br />
                                <textarea name="settings[order_completed]" rows="5" cols="50"><?php echo isset( $general_settings['order_completed'] ) ? stripslashes( $general_settings['order_completed'] ) : ''; ?></textarea>
                                <span class="description"><?php _e( 'Placeholders', WOOPRO_SHT_TEXT_DOMAIN ); ?>: {shipping_provider}, {track_number}, {track_url}</span>
                            </label>
                        </p>
                        <p style="text-align: center;">
                            <input type="submit" name="update_settings" class="button-primary" value="<?php _e('Update Settings', WOOPRO_SHT_TEXT_DOMAIN); ?>" />
                        </p>
                    </div>
                </div>

        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery('.settings_background').hide();
    });
</script>