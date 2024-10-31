<?php

/**
 * Plugin Name: Quiver Delivery
 * Plugin URI: https://wordpress.org/plugins/quiver-delivery/
 * Description: Quiver provides fast and emissionless urban delivery. Magic.
 * Version: 1.0.16
 * Author: Quiver
 * Author URI: https://quiver.co.uk/
 * Text Domain: quiver-delivery
 * Domain Path: /languages
 * Requires at least: 4.6
 * Requires PHP: 7.0
 */


if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('Quiver_Delivery')) :

	class Quiver_Delivery
	{

		private $version = "1.0.16";

		/**
		 * Instance
		 *
		 * @var Quiver_Delivery
		 */
		protected static $_instance = null;

		private $_shipping_method = null;

		/**
		 * Construct the plugin.
		 */
		public function __construct()
		{
			add_action('init', array($this, 'load_plugin'), 0);
		}

		/**
		 * Main Quiver Delivery Instance.
		 *
		 * Ensures only one instance is loaded or can be loaded.
		 *
		 * @static
		 * @return self Main instance.
		 */
		public static function instance()
		{
			if (is_null(self::$_instance)) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Load the plugin.
		 */
		public function load_plugin()
		{
			// Checks if WooCommerce is installed.
			if (class_exists('WC_Shipping_Method')) {
				$this->includes();
				$this->init_hooks();
			} else {
				// Throw an admin error informing the user this plugin needs WooCommerce to function
				add_action('admin_notices', array($this, 'notice_wc_required'));
			}
		}

		public function includes()
		{
			include_once('includes/class-quiver-delivery-logger.php');
			include_once('includes/class-quiver-delivery-shipping-method.php');

			$this->_shipping_method = new Quiver_Delivery_Shipping_Method();
		}

		public function init_hooks()
		{
			add_filter('woocommerce_checkout_update_order_review', array($this, 'clear_shipping_rates_cache'));
			add_action('woocommerce_shipping_init', array($this, 'add_shipping_method'));
			add_filter('woocommerce_shipping_methods', array($this, 'add_shipping_method'));
			add_action('woocommerce_thankyou', array($this, 'create_delivery'));
		}

		function clear_shipping_rates_cache()
		{
			$this->_shipping_method->clear_shipping_rates_cache();
		}

		/**
		 * Create a Delivery.
		 */
		public function create_delivery($order_id)
		{
			$this->_shipping_method->create_delivery($order_id);
		}

		/**
		 * Add a new Shipping Method.
		 */
		public function add_shipping_method($test)
		{
			$method = 'Quiver_Delivery_Shipping_Method';
			if (gettype($test) === 'array') {
				$test[] = $method;
				return $test;
			} else {
				return [$method];
			}
		}
	}

endif;

if (!function_exists('Quiver')) {

	function Quiver()
	{
		return Quiver_Delivery::instance();
	}

	$Quiver = Quiver();
}
