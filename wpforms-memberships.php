<?php

/**
 * Plugin Name:     WPForms WooCommerce Registration
 * Plugin URI:      https://bizbudding.com
 * Description:     Add users to WooCommerce Memberships with WPForms.
 * Version:         0.4.0
 *
 * Author:          BizBudding, Mike Hemberger
 * Author URI:      https://bizbudding.com
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main BB_WPForms_Memberships Class.
 *
 * @since 0.1.0
 */
final class BB_WPForms_Memberships {

	/**
	 * @var   BB_WPForms_Memberships The one true BB_WPForms_Memberships
	 * @since 0.1.0
	 */
	private static $instance;

	/**
	 * Main BB_WPForms_Memberships Instance.
	 *
	 * Insures that only one instance of BB_WPForms_Memberships exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   0.1.0
	 * @static  var array $instance
	 * @uses    BB_WPForms_Memberships::setup_constants() Setup the constants needed.
	 * @uses    BB_WPForms_Memberships::includes() Include the required files.
	 * @uses    BB_WPForms_Memberships::hooks() Activate, deactivate, etc.
	 * @see     BB_WPForms_Memberships()
	 * @return  object | BB_WPForms_Memberships The one true BB_WPForms_Memberships
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup
			self::$instance = new BB_WPForms_Memberships;
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
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wpforms-memberships' ), '1.0' );
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
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wpforms-memberships' ), '1.0' );
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
			define( 'WPFORMS_WOO_MEMBERSHIPS_VERSION', '0.4.0' );
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
		$updater = Puc_v4_Factory::buildUpdateChecker( 'https://github.com/bizbudding/wpforms-memberships/', __FILE__, 'wpforms-memberships' );
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
 * The main function for that returns BB_WPForms_Memberships
 *
 * The main function responsible for returning the one true BB_WPForms_Memberships
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $plugin = BB_WPForms_Memberships(); ?>
 *
 * @since 0.1.0
 *
 * @return object|BB_WPForms_Memberships The one true BB_WPForms_Memberships Instance.
 */
function BB_WPForms_Memberships() {
	return BB_WPForms_Memberships::instance();
}

// Get BB_WPForms_Memberships Running.
BB_WPForms_Memberships();
