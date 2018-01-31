<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://www.themeisle.com
 * @since             1.0.0
 * @package           Imagga
 *
 * @wordpress-plugin
 * Plugin Name:       Imagga Auto Tagging
 * Plugin URI:        http://www.themeisle.com
 * Description:       Imagga Auto Tagging is a plugin that generate tags to posts based on the post thumbnail.
 * Version:           1.0.2
 * Author:            Themeisle
 * Author URI:        http://www.themeisle.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       imagga
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

define( 'IMAGGA_URL', plugin_dir_url( __FILE__ ) );
define( 'IMAGGA_PATH', plugin_dir_path( __FILE__ ) );
define( 'IMAGGA_ADMIN_PATH', plugin_dir_path( __FILE__ )  . 'admin/' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-imagga-activator.php
 */
function activate_imagga() {
	require_once IMAGGA_PATH . 'includes/class-imagga-activator.php';
	Imagga_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-imagga-deactivator.php
 */
function deactivate_imagga() {
	require_once IMAGGA_PATH . 'includes/class-imagga-deactivator.php';
	Imagga_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_imagga' );
register_deactivation_hook( __FILE__, 'deactivate_imagga' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require IMAGGA_PATH . 'includes/class-imagga.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_imagga() {

	$plugin = new Imagga();
	$plugin->run();

}
run_imagga();
