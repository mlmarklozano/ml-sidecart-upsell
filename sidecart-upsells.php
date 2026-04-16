<?php
/**
 * Plugin Name:       DH - Side Cart Upsells
 * Plugin URI:        https://github.com/mlmarklozano/ml-sidecart-upsell
 * Description:       Adds a dynamic "Don't miss out" upsell section inside the Elementor WooCommerce side cart.
 * Version:           1.0.0
 * Author:            Mark Lozano
 * Text Domain:       sidecart-upsells
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 7.0
 * WC tested up to:   9.0
 */

defined( 'ABSPATH' ) || exit;

define( 'SCU_VERSION', '1.0.0' );
define( 'SCU_PATH', plugin_dir_path( __FILE__ ) );
define( 'SCU_URL', plugin_dir_url( __FILE__ ) );

require_once SCU_PATH . 'includes/class-scu-upsells.php';

add_action( 'plugins_loaded', 'scu_init' );

/**
 * Initialise the plugin after all plugins are loaded.
 * Bails silently when WooCommerce is not active.
 */
function scu_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	SCU_Upsells::get_instance();
}
