<?php //phpcs:ignore
/**
 *
 * Plugin Name: PSOURCE Live Debug
 * Plugin URI: https://power-source.github.io/ps-live-debug/
 * Description: Aktiviert das Debuggen und fügt dem ClassicPress-Admin einen Bildschirm hinzu, um das debug.log anzuzeigen.
 * Version: 1.0.0
 * Author: PSOURCE
 * Author URI: https://github.com/Power-Source
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: ps-live-debug
 * Domain Path: /languages
 * PS Network: required
 *
 */

/*
Copyright PSOURCE

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**************************************************/
/****************** Plugin Start ******************/
/**************************************************/

// Check that the file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Es tut uns leid, aber Du kannst nicht direkt auf diese Datei zugreifen.' );
}

/**
 * Define the plugin version for internal use
 */
define( 'PS_LIVE_DEBUG_VERSION', '1.0.0' );
define( 'PS_LIVE_DEBUG_WP_CONFIG', ABSPATH . 'wp-config.php' );
define( 'PS_LIVE_DEBUG_WP_CONFIG_BACKUP_ORIGINAL', ABSPATH . 'wp-config.wpld-original-backup.php' );
define( 'PS_LIVE_DEBUG_WP_CONFIG_BACKUP', ABSPATH . 'wp-config.wpld-manual-backup.php' );
/**
 * PS_Live_Debug Class.
 */
if ( ! class_exists( 'PS_Live_Debug' ) ) {
	class PS_Live_Debug {

		/**
		 * PS_Live_Debug constructor.
		 *
		 * @uses PS_Live_Debug::init()
		 *
		 * @return void
		 */
		public function __construct() {
			$this->init();
		}

		/**
		 * Plugin initialization.
		 *
		 * @uses add_action()
		 *
		 * @return void
		 */
		public function init() {
			add_action( 'init', array( 'PS_Live_Debug', 'create_menus' ) );
			add_action( 'wp_default_scripts', array( 'PS_Live_Debug', 'remove_jquery_ui_scripts' ), 10 );
			add_action( 'admin_enqueue_scripts', array( 'PS_Live_Debug', 'enqueue_scripts_styles' ) );
			add_action( 'wp_ajax_ps-live-debug-accept-risk', array( 'PS_Live_Debug', 'accept_risk' ) );
		}

		/**
		 * Accept Risk Popup.
		 *
		 * @uses update_option()
		 * @uses esc_html__()
		 * @uses wp_send_json_success()
		 *
		 * @return string json success with the response.
		 */
		public static function accept_risk() {
			update_option( 'PS_LIVE_DEBUG_risk', 'yes' );

			$response = array(
				'message' => esc_html__( 'Risiko akzeptiert.', 'ps-live-debug' ),
			);

			wp_send_json_success( $response );
		}

		/**
		 * Activation Hook.
		 *
		 * @uses get_site_url()
		 * @uses update_option()
		 * @uses PS_Live_Debug_Helper::create_debug_log()
		 *
		 * @return void
		 */
		public static function on_activate() {
			$host = str_replace( array( 'http://', 'https://' ), '', get_site_url() );

			update_option( 'PS_LIVE_DEBUG_ssl_domain', $host );

			PS_Live_Debug_Helper::create_debug_log();
			PS_Live_Debug_Helper::get_first_backup();
		}

		/**
		 * Deactivation Hook.
		 *
		 * @uses delete_option()
		 *
		 * @return void
		 */
		public static function on_deactivate() {
			delete_option( 'PS_LIVE_DEBUG_risk' );
			delete_option( 'PS_LIVE_DEBUG_ssl_domain' );
			delete_option( 'PS_LIVE_DEBUG_log_file' );

			PS_Live_Debug_Helper::clear_manual_backup();
		}

		/**
		 * Create the Admin Menus.
		 *
		 * @uses is_multisite()
		 * @uses add_action()
		 *
		 * @return void
		 */
		public static function create_menus() {
			if ( ! is_multisite() ) {
				add_action( 'admin_menu', array( 'PS_Live_Debug', 'populate_admin_menu' ) );
			} else {
				add_action( 'network_admin_menu', array( 'PS_Live_Debug', 'populate_admin_menu' ) );
			}
		}

		/**
		 * Populate the Admin menu.
		 *
		 * @uses add_menu_page()
		 * @uses esc_html__()
		 *
		 * @return void
		 */
		public static function populate_admin_menu() {
			add_menu_page(
				esc_html__( 'PS-Debug-Tool', 'ps-live-debug' ),
				esc_html__( 'PS-Debug-Tool', 'ps-live-debug' ),
				'manage_options',
				'ps-live-debug',
				array( 'PS_Live_Debug', 'create_page' ),
				'dashicons-media-code'
			);
		}

		/**
		 * Enqueue scripts and styles.
		 *
		 * @param string $hook ClassicPress generated class for the current page.
		 *
		 * @uses wp_enqueue_style()
		 * @uses plugin_dir_url()
		 * @uses add_filter()
		 *
		 * @return void
		 */
		public static function enqueue_scripts_styles( $hook ) {
			if ( 'toplevel_page_ps-live-debug' === $hook ) {
				wp_enqueue_style(
					'wphb-psource-sui',
					plugin_dir_url( __FILE__ ) . 'assets/sui/css/shared-ui.min.css',
					'2.2.10'
				);
				wp_enqueue_script(
					'wphb-psource-sui',
					plugin_dir_url( __FILE__ ) . 'assets/sui/js/shared-ui.min.js',
					array( 'jquery' ),
					'2.2.10',
					true
				);
				wp_enqueue_style(
					'ps-live-debug',
					plugin_dir_url( __FILE__ ) . 'assets/styles.css',
					array( 'wphb-psource-sui' ),
					PS_LIVE_DEBUG_VERSION
				);
				wp_enqueue_script(
					'ps-live-debug',
					plugin_dir_url( __FILE__ ) . 'assets/scripts.js',
					array( 'wphb-psource-sui' ),
					PS_LIVE_DEBUG_VERSION,
					true
				);
				add_filter( 'admin_body_class', array( 'PS_Live_Debug', 'admin_body_classes' ) );
			}
		}

		/**
		 * Remove jQuery UI scripts from WordPress registry.
		 * Prevents deprecated jQuery UI warnings in ClassicPress 2.2.0+.
		 *
		 * @param WP_Scripts $scripts The WP_Scripts object.
		 *
		 * @return void
		 */
		public static function remove_jquery_ui_scripts( $scripts ) {
			// List of jQuery UI scripts to remove from the registry
			$ui_scripts = array(
				'jquery-ui',
				'jquery-ui-core',
				'jquery-ui-menu',
				'jquery-ui-position',
				'jquery-ui-widget',
				'jquery-ui-mouse',
				'jquery-ui-draggable',
				'jquery-ui-droppable',
				'jquery-ui-resizable',
				'jquery-ui-selectable',
				'jquery-ui-sortable',
				'jquery-ui-accordion',
				'jquery-ui-autocomplete',
				'jquery-ui-button',
				'jquery-ui-datepicker',
				'jquery-ui-dialog',
				'jquery-ui-slider',
				'jquery-ui-tabs',
				'jquery-ui-progressbar',
				'jquery-ui-spinbutton',
				'jquery-ui-tooltip',
				'jquery-ui-spinner',
			);

			// Remove all jQuery UI scripts before they can be enqueued
			foreach ( $ui_scripts as $script_handle ) {
				if ( isset( $scripts->registered[ $script_handle ] ) ) {
					unset( $scripts->registered[ $script_handle ] );
				}
			}
		}

		/**
		 * Add Shared UI Classes to body.
		 *
		 * @param string $classes Maybe existing classes.
		 *
		 * @return string $classes Updated classes list including the Shared-UI classes.
		 */
		public static function admin_body_classes( $classes ) {
			$classes .= ' sui-2-2-10 ';

			return $classes;
		}

		/**
		 * Create the CP Live Debug page.
		 *
		 * @uses get_option()
		 * @uses esc_attr()
		 * @uses esc_html_e()
		 * @uses _e()
		 * @uses PS_Live_Debug_ClassicPress_Info::create_page()
		 * @uses PS_Live_Debug_Server_Info::create_page();
		 * @uses PS_Live_Debug_Cronjob_Info::create_page();
		 * @uses PS_Live_Debug_Tools::create_page();
		 * @uses PS_Live_Debug_PSOURCE::create_page();
		 * @uses PS_Live_Debug_Live_Debug::create_page();
		 * @uses PS_Live_Debug_Live_Debug::create_page();
		 *
		 * @return string html The html of the page viewed.
		 */
		public static function create_page() {
			if ( ! empty( $_GET['subpage'] ) ) {
				$subpage = esc_attr( $_GET['subpage'] );
			}
			?>
			<div class="sui-wrap">
				<div class="sui-header">
					<h1 class="sui-header-title">PSOURCE Live Debug</h1>
				</div>
				<div class="sui-row-with-sidenav">
					<div class="sui-sidenav">
						<ul class="sui-vertical-tabs sui-sidenav-hide-md">
							<li class="sui-vertical-tab <?php echo ( empty( $subpage ) ) ? 'current' : ''; ?>">
								<a href="?page=ps-live-debug"><?php esc_html_e( 'Live Debug', 'ps-live-debug' ); ?></a>
							</li>
							<li class="sui-vertical-tab <?php echo ( ! empty( $subpage ) && 'ClassicPress' === $subpage ) ? 'current' : ''; ?>">
								<a href="?page=ps-live-debug&subpage=ClassicPress"><?php esc_html_e( 'ClassicPress', 'ps-live-debug' ); ?></a>
							</li>
							<li class="sui-vertical-tab <?php echo ( ! empty( $subpage ) && 'Server' === $subpage ) ? 'current' : ''; ?>">
								<a href="?page=ps-live-debug&subpage=Server"><?php esc_html_e( 'Server', 'ps-live-debug' ); ?></a>
							</li>
							<li class="sui-vertical-tab <?php echo ( ! empty( $subpage ) && 'Cron' === $subpage ) ? 'current' : ''; ?>">
								<a href="?page=ps-live-debug&subpage=Cron"><?php esc_html_e( 'Geplante Ereignisse', 'ps-live-debug' ); ?></a>
							</li>
							<li class="sui-vertical-tab <?php echo ( ! empty( $subpage ) && 'Tools' === $subpage ) ? 'current' : ''; ?>">
								<a href="?page=ps-live-debug&subpage=Tools"><?php esc_html_e( 'Werkzeug', 'ps-live-debug' ); ?></a>
							</li>
							<li class="sui-vertical-tab <?php echo ( ! empty( $subpage ) && 'PSOURCE' === $subpage ) ? 'current' : ''; ?>">
								<a href="?page=ps-live-debug&subpage=PSOURCE"><?php esc_html_e( 'PSOURCE', 'ps-live-debug' ); ?></a>
							</li>
						</ul>
						<div class="sui-sidenav-hide-lg">
							<select class="sui-mobile-nav" style="display: none;" onchange="location = this.value;">
								<option value="?page=ps-live-debug" <?php echo ( empty( $subpage ) ) ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Live Debug', 'ps-live-debug' ); ?></option>
								<option value="?page=ps-live-debug&subpage=ClassicPress" <?php echo ( ! empty( $subpage ) && 'ClassicPress' === $subpage ) ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'ClassicPress', 'ps-live-debug' ); ?></option>
								<option value="?page=ps-live-debug&subpage=Server" <?php echo ( ! empty( $subpage ) && 'Server' === $subpage ) ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Server', 'ps-live-debug' ); ?></option>
								<option value="?page=ps-live-debug&subpage=Cron" <?php echo ( ! empty( $subpage ) && 'Cron' === $subpage ) ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Geplante Ereignisse', 'ps-live-debug' ); ?></option>
								<option value="?page=ps-live-debug&subpage=Tools" <?php echo ( ! empty( $subpage ) && 'Tools' === $subpage ) ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Werkzeuge', 'ps-live-debug' ); ?></option>
								<option value="?page=ps-live-debug&subpage=PSOURCE" <?php echo ( ! empty( $subpage ) && 'PSOURCE' === $subpage ) ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'PSOURCE', 'ps-live-debug' ); ?></option>
							</select>
						</div>
					</div>
					<?php
					if ( ! empty( $subpage ) ) {
						switch ( $subpage ) {
							case 'ClassicPress':
								PS_Live_Debug_ClassicPress_Info::create_page();
								break;
							case 'Server':
								PS_Live_Debug_Server_Info::create_page();
								break;
							case 'Cron':
								PS_Live_Debug_Cronjob_Info::create_page();
								break;
							case 'Tools':
								PS_Live_Debug_Tools::create_page();
								break;
							case 'PSOURCE':
								PS_Live_Debug_PSOURCE::create_page();
								break;
							default:
								PS_Live_Debug_Live_Debug::create_page();
						}
					} else {
						PS_Live_Debug_Live_Debug::create_page();
					}
					?>
				</div>
				<?php
				$first_time_running = get_option( 'PS_LIVE_DEBUG_risk' );

				if ( empty( $first_time_running ) ) {
					?>
					<div class="sui-dialog sui-dialog-sm" aria-hidden="true" tabindex="-1" id="safety-popup">
						<div class="sui-dialog-overlay" data-a11y-dialog-hide></div>
						<div class="sui-dialog-content" aria-labelledby="dialogTitle" aria-describedby="dialogDescription" role="dialog">
							<div class="sui-box" role="document">
								<div class="sui-box-header">
									<h3 class="sui-box-title">Safety First!</h3>
								</div>
								<div class="sui-box-body">
									<p>
									<?php
										_e( 'PS-Debug-Tool ermöglicht das Debuggen, überprüft Dateien und führt verschiedene Tests durch, um Informationen über Deine Installation zu sammeln.', 'ps-live-debug' );
									?>
									</p>
									<p>
									<?php
										_e( 'Stelle sicher, dass Du zuerst eine <strong>vollständige Sicherung</strong> hast, bevor Du mit einem der Tools fortfährst.', 'ps-live-debug' );
									?>
									</p>
								</div>
								<div class="sui-box-footer">
									<a href="?page=ps-live-debug&wplddlwpconfig=true" class="sui-modal-close sui-button sui-button-green"><?php esc_html_e( 'Lade wp-config herunter', 'ps-live-debug' ); ?></a>
									<button id="riskaccept" class="sui-modal-close sui-button sui-button-blue"><?php esc_html_e( 'ich verstehe', 'ps-live-debug' ); ?></button>
								</div>
							</div>
						</div>
					</div>
					<?php
				}
				?>
			</div>
			<?php
		}
	}

	// Activation Hook
	register_activation_hook( __FILE__, array( 'PS_Live_Debug', 'on_activate' ) );

	// Deactivation Hook
	register_deactivation_hook( __FILE__, array( 'PS_Live_Debug', 'on_deactivate' ) );

	// Require extra files
	require_once plugin_dir_path( __FILE__ ) . '/classes/class-ps-live-debug-live-debug.php';
	require_once plugin_dir_path( __FILE__ ) . '/classes/class-ps-live-debug-wordpress-info.php';
	require_once plugin_dir_path( __FILE__ ) . '/classes/class-ps-live-debug-server-info.php';
	require_once plugin_dir_path( __FILE__ ) . '/classes/class-ps-live-debug-cronjob-info.php';
	require_once plugin_dir_path( __FILE__ ) . '/classes/class-ps-live-debug-tools.php';
	require_once plugin_dir_path( __FILE__ ) . '/classes/class-ps-live-debug-psource.php';
	require_once plugin_dir_path( __FILE__ ) . '/classes/class-ps-live-debug-helper.php';

	// Initialize Classes.
	new PS_Live_Debug();
	new PS_Live_Debug_Live_Debug();
	new PS_Live_Debug_ClassicPress_Info();
	new PS_Live_Debug_Server_Info();
	new PS_Live_Debug_Cronjob_Info();
	new PS_Live_Debug_Tools();
	new PS_Live_Debug_PSOURCE();
	new PS_Live_Debug_Helper();
}
