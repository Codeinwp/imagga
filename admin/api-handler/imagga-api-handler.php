<?php
/**
 *
 */

require_once IMAGGA_ADMIN_PATH . 'api-handler/class/class-imagga-api.php';

function imagga_ping_server(){
	Imagga_Api::imagga_test_server();
	die();
}
add_action( 'wp_ajax_imagga_ping_server', 'imagga_ping_server' );