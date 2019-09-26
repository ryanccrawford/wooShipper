<?php

/**
 * Functions used by plugins
 */
if (!class_exists('Twds_Dependencies'))
	require_once 'class-twds-dependencies.php';
require_once('./includes/vendor/autoload.php');
/**
 * WC Detection
 */
if (!function_exists('twds_is_woocommerce_active')) {

	function twds_is_woocommerce_active()
	{
		return Twds_Dependencies::woocommerce_active_check();
	}
}
