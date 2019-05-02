<?php

/**
 * Plugin Name:     WPForms WooCommerce Registration
 * Plugin URI:      https://bizbudding.com
 * Description:     Add users to WooCommerce Memberships with WPForms.
 * Version:         0.1.0
 *
 * Author:          BizBudding, Mike Hemberger
 * Author URI:      https://bizbudding.com
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main WPForms_Woo_Memberships Class.
 *
 * @since 0.1.0
 */
final class WPForms_Woo_Memberships {

	/**
	 * @var   WPForms_Woo_Memberships The one true WPForms_Woo_Memberships
	 * @since 0.1.0
	 */
	private static $instance;

	/**
	 * Main WPForms_Woo_Memberships Instance.
	 *
	 * Insures that only one instance of WPForms_Woo_Memberships exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   0.1.0
	 * @static  var array $instance
	 * @uses    WPForms_Woo_Memberships::setup_constants() Setup the constants needed.
	 * @uses    WPForms_Woo_Memberships::includes() Include the required files.
	 * @uses    WPForms_Woo_Memberships::hooks() Activate, deactivate, etc.
	 * @see     WPForms_Woo_Memberships()
	 * @return  object | WPForms_Woo_Memberships The one true WPForms_Woo_Memberships
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup
			self::$instance = new WPForms_Woo_Memberships;
			// Methods
			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->hooks();
		}
		return self::$instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since   0.1.0
	 * @access  protected
	 * @return  void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'textdomain' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @since   0.1.0
	 * @access  protected
	 * @return  void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'textdomain' ), '1.0' );
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access  private
	 * @since   0.1.0
	 * @return  void
	 */
	private function setup_constants() {

		// Plugin version.
		if ( ! defined( 'WPFORMS_WOO_MEMBERSHIPS_VERSION' ) ) {
			define( 'WPFORMS_WOO_MEMBERSHIPS_VERSION', '0.1.0' );
		}

		// Plugin Folder Path.
		if ( ! defined( 'WPFORMS_WOO_MEMBERSHIPS_DIR' ) ) {
			define( 'WPFORMS_WOO_MEMBERSHIPS_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Includes Path.
		if ( ! defined( 'WPFORMS_WOO_MEMBERSHIPS_INCLUDES_DIR' ) ) {
			define( 'WPFORMS_WOO_MEMBERSHIPS_INCLUDES_DIR', WPFORMS_WOO_MEMBERSHIPS_DIR . 'includes/' );
		}

		// Plugin Folder URL.
		if ( ! defined( 'WPFORMS_WOO_MEMBERSHIPS_URL' ) ) {
			define( 'WPFORMS_WOO_MEMBERSHIPS_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin Root File.
		if ( ! defined( 'WPFORMS_WOO_MEMBERSHIPS_FILE' ) ) {
			define( 'WPFORMS_WOO_MEMBERSHIPS_FILE', __FILE__ );
		}

		// Plugin Base Name
		if ( ! defined( 'WPFORMS_WOO_MEMBERSHIPS_BASENAME' ) ) {
			define( 'WPFORMS_WOO_MEMBERSHIPS_BASENAME', dirname( plugin_basename( __FILE__ ) ) );
		}

	}

	/**
	 * Include required files.
	 *
	 * @access  private
	 * @since   0.1.0
	 * @return  void
	 */
	private function includes() {
		// Include vendor libraries.
		require_once __DIR__ . '/vendor/autoload.php';
		// Includes.
		foreach ( glob( WPFORMS_WOO_MEMBERSHIPS_INCLUDES_DIR . '*.php' ) as $file ) { include $file; }
	}

	/**
	 * Run the hooks.
	 *
	 * @since   0.1.0
	 * @return  void
	 */
	public function hooks() {

		add_action( 'admin_init', array( $this, 'updater' ) );

		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
	}

	/**
	 * Setup the updater.
	 *
	 * composer require yahnis-elsts/plugin-update-checker
	 *
	 * @uses    https://github.com/YahnisElsts/plugin-update-checker/
	 *
	 * @return  void
	 */
	public function updater() {

		// Bail if current user cannot manage plugins.
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		// Bail if plugin updater is not loaded.
		if ( ! class_exists( 'Puc_v4_Factory' ) ) {
			return;
		}

		// Setup the updater.
		// $updater = Puc_v4_Factory::buildUpdateChecker( 'https://github.com/bizbudding/starter-plugin/', __FILE__, 'textdomain' );
	}

	/**
	 * Plugin activation.
	 *
	 * @return  void
	 */
	public function activate() {
		flush_rewrite_rules();
	}

}

/**
 * The main function for that returns WPForms_Woo_Memberships
 *
 * The main function responsible for returning the one true WPForms_Woo_Memberships
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $plugin = WPForms_Woo_Memberships(); ?>
 *
 * @since 0.1.0
 *
 * @return object|WPForms_Woo_Memberships The one true WPForms_Woo_Memberships Instance.
 */
function WPForms_Woo_Memberships() {
	return WPForms_Woo_Memberships::instance();
}

// Get WPForms_Woo_Memberships Running.
WPForms_Woo_Memberships();