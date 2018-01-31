<?php
/**
 * This file is used for connecting to Imagga API via AJAX.
 *
 * @package Imagga
 */

require_once IMAGGA_ADMIN_PATH . 'api-handler/class/class-imagga-api.php';

/**
 * Ajax function used for connecting to Imagga Api.
 *
 * @since 1.0.2
 */
function imagga_ping_server(){
	$api = new Imagga_Api();
	$api->imagga_test_server();
	die();
}
add_action( 'wp_ajax_imagga_ping_server', 'imagga_ping_server' );