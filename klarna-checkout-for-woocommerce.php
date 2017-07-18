<?php
/*
 * Plugin Name: Klarna Checkout for WooCommerce
 * Plugin URI: https://krokedil.com/
 * Description: Klarna Checkout payment gateway for WooCommerce.
 * Author: Krokedil
 * Author URI: https://krokedil.com/
 * Version: 0.1-alpha
 * Text Domain: klarna-checkout-for-woocommerce
 * Domain Path: /languages
 *
 * Copyright (c) 2017 Krokedil
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Required minimums and constants
 */
define( 'KLARNA_CHECKOUT_FOR_WOOCOMMERCE_VERSION', '1.0' );
define( 'KLARNA_CHECKOUT_FOR_WOOCOMMERCE_MIN_PHP_VER', '5.3.0' );
define( 'KLARNA_CHECKOUT_FOR_WOOCOMMERCE_MIN_WC_VER', '2.5.0' );
define( 'KLARNA_CHECKOUT_FOR_WOOCOMMERCE_MAIN_FILE', __FILE__ );
define( 'KLARNA_CHECKOUT_FOR_WOOCOMMERCE_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

if ( ! class_exists( 'Klarna_Checkout_For_WooCommerce' ) ) {
	/**
	 * Class Klarna_Checkout_For_WooCommerce
	 */
	class Klarna_Checkout_For_WooCommerce {

		/**
		 * The reference the *Singleton* instance of this class.
		 *
		 * @var $instance
		 */
		private static $instance;

		/**
		 * Reference to logging class.
		 *
		 * @var $log
		 */
		private static $log;

		/**
		 * Returns the *Singleton* instance of this class.
		 *
		 * @return self::$instance The *Singleton* instance.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Private clone method to prevent cloning of the instance of the
		 * *Singleton* instance.
		 *
		 * @return void
		 */
		private function __clone() {
		}

		/**
		 * Private unserialize method to prevent unserializing of the *Singleton*
		 * instance.
		 *
		 * @return void
		 */
		private function __wakeup() {
		}

		/**
		 * Notices (array)
		 *
		 * @var array
		 */
		public $notices = array();

		/**
		 * Protected constructor to prevent creating a new instance of the
		 * *Singleton* via the `new` operator from outside of this class.
		 */
		protected function __construct() {
			add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );
			add_action( 'plugins_loaded', array( $this, 'init' ) );
			add_action( 'init', array( $this, 'add_kco_endpoint' ) );
		}

		/**
		 * Init the plugin after plugins_loaded so environment variables are set.
		 */
		public function init() {
			// Init the gateway itself.
			$this->init_gateways();
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		}

		/**
		 * Adds plugin action links
		 *
		 * @param array $links Plugin action link before filtering.
		 *
		 * @return array Filtered links.
		 */
		public function plugin_action_links( $links ) {
			$setting_link = $this->get_setting_link();
			$plugin_links = array(
				'<a href="' . $setting_link . '">' . __( 'Settings', 'klarna-checkout-for-woocommerce' ) . '</a>',
				'<a href="https://krokedil.se/">' . __( 'Docs', 'klarna-checkout-for-woocommerce' ) . '</a>',
				'<a href="http://krokedil.se/">' . __( 'Support', 'klarna-checkout-for-woocommerce' ) . '</a>',
			);
			return array_merge( $plugin_links, $links );
		}

		/**
		 * Get setting link.
		 *
		 * @since 1.0.0
		 *
		 * @return string Setting link
		 */
		public function get_setting_link() {
			$use_id_as_section = function_exists( 'WC' ) ? version_compare( WC()->version, '2.6', '>=' ) : false;
			$section_slug = $use_id_as_section ? 'klarna_checkout' : strtolower( 'Klarna_Checkout_For_WooCommerce_Gateway' );
			return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $section_slug );
		}

		/**
		 * Display any notices we've collected thus far (e.g. for connection, disconnection)
		 */
		public function admin_notices() {
			foreach ( (array) $this->notices as $notice_key => $notice ) {
				echo "<div class='" . esc_attr( $notice['class'] ) . "'><p>";
				echo wp_kses( $notice['message'], array( 'a' => array( 'href' => array() ) ) );
				echo '</p></div>';
			}
		}

		/**
		 * Initialize the gateway. Called very early - in the context of the plugins_loaded action
		 *
		 * @since 1.0.0
		 */
		public function init_gateways() {
			if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
				return;
			}

			include_once( KLARNA_CHECKOUT_FOR_WOOCOMMERCE_PLUGIN_PATH . '/includes/class-klarna-checkout-for-woocommerce-gateway.php' );
			include_once( KLARNA_CHECKOUT_FOR_WOOCOMMERCE_PLUGIN_PATH . '/includes/class-klarna-checkout-for-woocommerce-api.php' );
			include_once( KLARNA_CHECKOUT_FOR_WOOCOMMERCE_PLUGIN_PATH . '/includes/class-klarna-checkout-for-woocommerce-ajax.php' );
			include_once( KLARNA_CHECKOUT_FOR_WOOCOMMERCE_PLUGIN_PATH . '/includes/class-klarna-checkout-for-woocommerce-order-lines.php' );
			include_once( KLARNA_CHECKOUT_FOR_WOOCOMMERCE_PLUGIN_PATH . '/includes/class-klarna-checkout-for-woocommerce-endpoints.php' );

			load_plugin_textdomain( 'klarna-checkout-for-woocommerce', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );
		}

		/**
		 * Add the gateways to WooCommerce
		 *
		 * @param  array $methods Payment methods.
		 * @return array $methods Payment methods.
		 * @since  1.0.0
		 */
		public function add_gateways( $methods ) {
			$methods[] = 'Klarna_Checkout_For_WooCommerce_Gateway';
			return $methods;
		}

		/**
		 * Instantiate WC_Logger class.
		 *
		 * @param string $message Log message.
		 */
		public static function log( $message ) {
			if ( empty( self::$log ) ) {
				self::$log = new WC_Logger();
			}
			self::$log->add( 'klarna-checkout-for-woocommerce', $message );
		}

		/**
		 * Adds KCO page endpoint.
		 */
		public function add_kco_endpoint() {
			add_rewrite_endpoint( 'special-page', EP_ROOT | EP_PAGES );
		}

	}
	Klarna_Checkout_For_WooCommerce::get_instance();
}