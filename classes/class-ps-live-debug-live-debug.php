<?php // phpcs:ignore

// Check that the file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Es tut uns leid, aber Du kannst nicht direkt auf diese Datei zugreifen.' );
}

/**
 * PS_Live_Debug_Live_Debug Class.
 */
if ( ! class_exists( 'PS_Live_Debug_Live_Debug' ) ) {
	class PS_Live_Debug_Live_Debug {

		/**
		 * PS_Live_Debug_Live_Debug constructor.
		 *
		 * @uses PS_Live_Debug_Live_Debug::init()
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
		public static function init() {
			add_action( 'wp_ajax_ps-live-debug-read-log', array( 'PS_Live_Debug_Live_Debug', 'read_debug_log' ) );
			add_action( 'wp_ajax_ps-live-debug-select-log', array( 'PS_Live_Debug_Live_Debug', 'select_log_file' ) );
			add_action( 'wp_ajax_ps-live-debug-clear-debug-log', array( 'PS_Live_Debug_Live_Debug', 'clear_debug_log' ) );
			add_action( 'wp_ajax_ps-live-debug-delete-debug-log', array( 'PS_Live_Debug_Live_Debug', 'delete_debug_log' ) );
			add_action( 'wp_ajax_ps-live-debug-create-backup', array( 'PS_Live_Debug_Live_Debug', 'create_wp_config_backup' ) );
			add_action( 'wp_ajax_ps-live-debug-restore-backup', array( 'PS_Live_Debug_Live_Debug', 'restore_wp_config_backup' ) );
			add_action( 'wp_ajax_ps-live-debug-enable', array( 'PS_Live_Debug_Live_Debug', 'enable_wp_debug' ) );
			add_action( 'wp_ajax_ps-live-debug-disable', array( 'PS_Live_Debug_Live_Debug', 'disable_wp_debug' ) );
			add_action( 'wp_ajax_ps-live-debug-enable-script-debug', array( 'PS_Live_Debug_Live_Debug', 'enable_script_debug' ) );
			add_action( 'wp_ajax_ps-live-debug-disable-script-debug', array( 'PS_Live_Debug_Live_Debug', 'disable_script_debug' ) );
			add_action( 'wp_ajax_ps-live-debug-enable-savequeries', array( 'PS_Live_Debug_Live_Debug', 'enable_savequeries' ) );
			add_action( 'wp_ajax_ps-live-debug-disable-savequeries', array( 'PS_Live_Debug_Live_Debug', 'disable_savequeries' ) );
			add_action( 'admin_init', array( 'PS_Live_Debug_Live_Debug', 'download_config_backup' ) );
		}

		/**
		 * Create the Live Debug page.
		 *
		 * @uses wp_normalize_path()
		 * @uses get_option
		 * @uses RecursiveIteratorIterator
		 * @uses getExtension()
		 * @uses esc_html__()
		 * @uses esc_html_e()
		 * @uses wp_create_nonce()
		 *
		 * @return string The html of the page viewed.
		 */
		public static function create_page() {
			$option_log_name = wp_normalize_path( get_option( 'PS_LIVE_DEBUG_log_file' ) );
			?>
				<div class="sui-box">
					<div class="sui-box-body">
						<div class="sui-form-field">
							<label for="ps-live-debug-area" class="sui-label"><?php echo esc_html__( 'Ansicht', 'ps-live-debug' ) . ': ' . $option_log_name; ?></label>
							<textarea id="ps-live-debug-area" name="ps-live-debug-area" class="sui-form-control"></textarea>
						</div>
						<?php
						$path = wp_normalize_path( ABSPATH );
						$logs = array();

						foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $path ) ) as $file ) {
							if ( is_file( $file ) && 'log' === $file->getExtension() ) {
								$logs[] = wp_normalize_path( $file );
							}
						}

						$debug_log = wp_normalize_path( WP_CONTENT_DIR . '/debug.log' );
						?>
						<select id="log-list" name="select-list">
							<?php
							foreach ( $logs as $log ) {
								$selected = '';
								$log_name = date( 'M d Y H:i:s', filemtime( $log ) ) . ' - ' . basename( $log );

								if ( get_option( 'PS_LIVE_DEBUG_log_file' ) === $log ) {
									$selected = 'selected="selected"';
								}

								echo '<option data-nonce="' . wp_create_nonce( $log ) . '" value="' . $log . '" ' . $selected . '>' . $log_name . '</option>';
							}
							?>
						</select>
					</div>
					<div class="sui-box-body">
						<div class="sui-row">
							<div class="sui-col-md-4 sui-col-lg-4 text-center">
									<button id="ps-live-debug-clear" data-log="<?php echo $option_log_name; ?>" data-nonce="<?php echo wp_create_nonce( $option_log_name ); ?>" type="button" class="sui-button sui-button-primary"><i class="sui-icon-loader sui-loading" aria-hidden="true"></i> <?php esc_html_e( 'Log leeren', 'ps-live-debug' ); ?></button>
							</div>
							<div class="sui-col-md-4 sui-col-lg-4 text-center">
									<button id="ps-live-debug-delete" data-log="<?php echo $option_log_name; ?>" data-nonce="<?php echo wp_create_nonce( $option_log_name ); ?>" type="button" class="sui-button sui-button-red"><i class="sui-icon-loader sui-loading" aria-hidden="true"></i> <?php esc_html_e( 'Log löschen', 'ps-live-debug' ); ?></button>
							</div>
							<div class="sui-col-md-4 sui-col-lg-4 text-center">
								<label class="sui-toggle">
									<input type="checkbox" id="toggle-auto-refresh">
									<span class="sui-toggle-slider"></span>
								</label>
								<label for="toggle-auto-refresh"><?php esc_html_e( 'Auto Refresh Log', 'ps-live-debug' ); ?></label>
							</div>
						</div>
						<div class="sui-box-settings-row divider"></div>
						<div class="sui-row mt30">
						<?php if ( ! PS_Live_Debug_Live_Debug::check_wp_config_backup() ) { ?>
							<div class="sui-col-lg-12 text-center">
								<button id="ps-live-debug-backup" type="button" class="sui-button sui-button-green"><i class="sui-icon-loader sui-loading" aria-hidden="true"></i> <?php esc_html_e( 'Backup wp-config und Optionen anzeigen', 'ps-live-debug' ); ?></button>
							</div>
							<?php } else { ?>
							<div class="sui-col-md-6 sui-col-lg-3 text-center">
								<button id="ps-live-debug-restore" type="button" class="sui-button sui-button-primary"><i class="sui-icon-loader sui-loading" aria-hidden="true"></i> <?php esc_html_e( 'wp-config wiederherstellen', 'ps-live-debug' ); ?></button>
							</div>
							<div class="sui-col-md-6 sui-col-lg-3 text-center">
								<span class="sui-tooltip sui-tooltip-top sui-tooltip-constrained" data-tooltip="Die WP_DEBUG Konstante kann verwendet werden, um den 'Debug'-Modus in ClassicPress zu aktivieren. Dies wird WP_DEBUG, WP_DEBUG_LOG aktivieren und WP_DEBUG_DISPLAY sowie display_errors deaktivieren.">
									<label class="sui-toggle">
										<input type="checkbox" id="toggle-wp-debug" <?php echo ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'checked' : ''; ?> >
										<span class="sui-toggle-slider"></span>
									</label>
									<label for="toggle-wp-debug"><?php esc_html_e( 'WP Debug', 'ps-live-debug' ); ?></label>
								</span>
							</div>
							<div class="sui-col-md-6 sui-col-lg-3 text-center">
								<span class="sui-tooltip sui-tooltip-top sui-tooltip-constrained" data-tooltip="Die SCRIPT_DEBUG Konstante erzwingt, dass ClassicPress die 'dev'-Versionen einiger Kern-CSS- und JavaScript-Dateien verwendet, anstatt der normalerweise geladenen minifizierten Versionen.">
									<label class="sui-toggle">
										<input type="checkbox" id="toggle-script-debug" <?php echo ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? 'checked' : ''; ?> >
										<span class="sui-toggle-slider"></span>
									</label>
									<label for="toggle-script-debug"><?php esc_html_e( 'Script Debug', 'ps-live-debug' ); ?></label>
								</span>
							</div>
							<div class="sui-col-md-6 sui-col-lg-3 text-center">
								<span class=" sui-tooltip sui-tooltip-top sui-tooltip-constrained" data-tooltip="Die SAVEQUERIES Konstante bewirkt, dass jede Abfrage zusammen mit der Ausführungszeit und der aufrufenden Funktion in der Datenbank gespeichert wird. Das Array wird in der globalen Variable $wpdb->queries gespeichert.">
									<label class="sui-toggle">
										<input type="checkbox" id="toggle-savequeries" <?php echo ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) ? 'checked' : ''; ?> >
										<span class="sui-toggle-slider"></span>
									</label>
									<label for="toggle-savequeries"><?php esc_html_e( 'Abfragen speichern', 'ps-live-debug' ); ?></label>
								</span>
							</div>
							<?php } ?>
						</div>
					</div>
					<div class="sui-box-footer">
						<p class="sui-description">
							<?php
							// translators: %1$s ClassicPress installation path.
							echo sprintf( __( 'Wenn Du das wp-config.php-Backup während der Aktivierung nicht heruntergeladen und überprüft haben, kannst Du auch zwei zusätzliche Backups über FTP im Verzeichnis <code>%1$s</code> finden: <code>wp-config.wpld-manual-backup.php</code> und <code>wp-config.wpld-original-backup.php</code>.', 'ps-live-debug' ), wp_normalize_path( ABSPATH ) );
							?>
							<br><br>
							<?php _e( "<strong>Um eine der oben genannten Debugging-Optionen manuell zu aktivieren, kannst Du Deine wp-config.php bearbeiten und die folgenden Konstanten direkt über der Zeile '/* That's all, stop editing! Happy blogging. */' hinzufügen.</strong>", 'ps-live-debug' ); ?>
							<br><br>
							<?php _e( "<strong>CP Debug: <code>define( 'WP_DEBUG', true ); define( 'WP_DEBUG_LOG', true ); define( 'WP_DEBUG_DISPLAY', false ); @ini_set( 'display_errors', 0 );</code>", 'ps-live-debug' ); ?>
							<br>
							<?php _e( "<strong>Script Debug: <code>define( 'SCRIPT_DEBUG', true );</code>", 'ps-live-debug' ); ?>
							<br>
							<?php _e( "<strong>Save Queries: <code>define( 'SAVEQUERIES', true );</code>", 'ps-live-debug' ); ?>
							<br><br>
							<?php esc_html_e( 'Weitere Informationen findest Du jederzeit unter', 'ps-live-debug' ); ?> <a target="_blank" rel="noopener" href="https://codex.wordpress.org/Debugging_in_ClassicPress"><?php esc_html_e( 'Debugging in ClassicPress', 'ps-live-debug' ); ?></a>.
						</p>
					</div>
				</div>
			<?php
		}

		/**
		 * Force download wp-config original backup
		 */
		public static function download_config_backup() {
			if ( ! empty( $_GET['wplddlwpconfig'] ) && 'true' === $_GET['wplddlwpconfig'] ) {
				$filename = 'wp-config-' . str_replace( array( 'http://', 'https://' ), '', get_site_url() ) . '-' . date( 'Ymd-Hi' ) . '-backup.php';
				header( 'Content-type: textplain;' );
				header( 'Content-disposition: attachment; filename= ' . $filename );
				readfile( PS_LIVE_DEBUG_WP_CONFIG_BACKUP_ORIGINAL );
				exit();
			}
		}
		/**
		 * Check if original wp-config.php backup exists.
		 *
		 * @uses file_exists()
		 *
		 * @return bool true/false depending if the backup exists.
		 */
		public static function check_wp_config_original_backup() {
			if ( file_exists( PS_LIVE_DEBUG_WP_CONFIG_BACKUP_ORIGINAL ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Check if manual wp-config.php backup exists.
		 *
		 * @uses file_exists()
		 *
		 * @return bool true/false depending if the backup exists.
		 */
		public static function check_wp_config_backup() {
			if ( file_exists( PS_LIVE_DEBUG_WP_CONFIG_BACKUP ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Creates a backup of wp-config.php.
		 *
		 * @uses wp_send_json_error()
		 * @uses wp_send_json_success()
		 * @uses esc_html__()
		 *
		 * @return string json success / error with the response.
		 */
		public static function create_wp_config_backup() {
			if ( ! copy( PS_LIVE_DEBUG_WP_CONFIG, PS_LIVE_DEBUG_WP_CONFIG_BACKUP ) ) {
				$response = array(
					'message' => esc_html__( 'wp-config.php backup failed.', 'ps-live-debug' ),
				);

				wp_send_json_error( $response );
			}

			$response = array(
				'message' => esc_html__( 'wp-config.php backup was created.', 'ps-live-debug' ),
			);

			wp_send_json_success( $response );
		}

		/**
		 * Restores a backup of wp-config.php.
		 *
		 * @uses wp_send_json_error()
		 * @uses wp_send_json_success()
		 * @uses esc_html__()
		 *
		 * @return string json success / error with the response.
		 */
		public static function restore_wp_config_backup() {
			if ( ! copy( PS_LIVE_DEBUG_WP_CONFIG_BACKUP, PS_LIVE_DEBUG_WP_CONFIG ) ) {
				$response = array(
					'message' => esc_html__( 'wp-config.php restore failed.', 'ps-live-debug' ),
				);

				wp_send_json_error( $response );
			}

			unlink( PS_LIVE_DEBUG_WP_CONFIG_BACKUP );

			$response = array(
				'message' => esc_html__( 'wp-config.php backup was restored.', 'ps-live-debug' ),
			);

			wp_send_json_success( $response );
		}

		/**
		 * Enables WP_DEBUG.
		 *
		 * @uses PS_Live_Debug_Live_Debug::enable_wp_debug_log()
		 * @uses PS_Live_Debug_Live_Debug::disable_wp_debug_display()
		 * @uses PS_Live_Debug_Live_Debug::disable_wp_debug_ini_set_display()
		 * @uses esc_html__()
		 * @uses wp_send_json_succes()
		 *
		 * @return string json success with the response.
		 */
		public static function enable_wp_debug() {
			$not_found        = true;
			$editing_wpconfig = file( PS_LIVE_DEBUG_WP_CONFIG );

			file_put_contents( PS_LIVE_DEBUG_WP_CONFIG, '' );

			$write_wpconfig = fopen( PS_LIVE_DEBUG_WP_CONFIG, 'w' );

			foreach ( $editing_wpconfig as $line ) {
				if ( false !== strpos( $line, "'WP_DEBUG'" ) || false !== strpos( $line, '"WP_DEBUG"' ) ) {
					$line      = "define( 'WP_DEBUG', true ); // Added by CP Live Debug" . PHP_EOL;
					$not_found = false;
				}

				fputs( $write_wpconfig, $line );
			}

			if ( $not_found ) {
				$editing_wpconfig = file( PS_LIVE_DEBUG_WP_CONFIG );

				file_put_contents( PS_LIVE_DEBUG_WP_CONFIG, '' );

				$write_wpconfig = fopen( PS_LIVE_DEBUG_WP_CONFIG, 'w' );

				foreach ( $editing_wpconfig as $line ) {
					if ( false !== strpos( $line, 'stop editing!' ) ) {
						fputs( $write_wpconfig, "define( 'WP_DEBUG', true ); // Added by CP Live Debug" . PHP_EOL );
					}
					fputs( $write_wpconfig, $line );
				}
			}

			fclose( $write_wpconfig );

			PS_Live_Debug_Live_Debug::enable_wp_debug_log();
			PS_Live_Debug_Live_Debug::disable_wp_debug_display();
			PS_Live_Debug_Live_Debug::disable_wp_debug_ini_set_display();

			$response = array(
				'message' => esc_html__( 'WP_DEBUG was enabled.', 'ps-live-debug' ),
			);

			wp_send_json_success( $response );
		}

		/**
		 * Disables WP_DEBUG
		 *
		 * @uses PS_Live_Debug_Live_Debug::enable_wp_debug_log()
		 * @uses PS_Live_Debug_Live_Debug::disable_wp_debug_display()
		 * @uses PS_Live_Debug_Live_Debug::disable_wp_debug_ini_set_display()
		 * @uses esc_html__()
		 * @uses wp_send_json_succes()
		 *
		 * @return string json success with the response.
		 */
		public static function disable_wp_debug() {
			$editing_wpconfig = file( PS_LIVE_DEBUG_WP_CONFIG );

			file_put_contents( PS_LIVE_DEBUG_WP_CONFIG, '' );

			$write_wpconfig = fopen( PS_LIVE_DEBUG_WP_CONFIG, 'w' );

			foreach ( $editing_wpconfig as $line ) {
				if ( false !== strpos( $line, "'WP_DEBUG'" ) || false !== strpos( $line, '"WP_DEBUG"' ) ) {
					$line = "define( 'WP_DEBUG', false ); // Added by CP Live Debug" . PHP_EOL;
				}

				fputs( $write_wpconfig, $line );
			}

			fclose( $write_wpconfig );

			PS_Live_Debug_Live_Debug::disable_wp_debug_log();
			PS_Live_Debug_Live_Debug::disable_wp_debug_display();
			PS_Live_Debug_Live_Debug::disable_wp_debug_ini_set_display();

			$response = array(
				'message' => esc_html__( 'WP_DEBUG was disabled.', 'ps-live-debug' ),
			);

			wp_send_json_success( $response );
		}

		/**
		 * Enable WP_DEBUG_LOG.
		 *
		 * @return void
		 */
		public static function enable_wp_debug_log() {
			$not_found        = true;
			$editing_wpconfig = file( PS_LIVE_DEBUG_WP_CONFIG );

			file_put_contents( PS_LIVE_DEBUG_WP_CONFIG, '' );

			$write_wpconfig = fopen( PS_LIVE_DEBUG_WP_CONFIG, 'w' );

			foreach ( $editing_wpconfig as $line ) {
				if ( false !== strpos( $line, "'WP_DEBUG_LOG'" ) || false !== strpos( $line, '"WP_DEBUG_LOG"' ) ) {
					$line      = "define( 'WP_DEBUG_LOG', true ); // Added by CP Live Debug" . PHP_EOL;
					$not_found = false;
				}

				fputs( $write_wpconfig, $line );
			}

			if ( $not_found ) {
				$editing_wpconfig = file( PS_LIVE_DEBUG_WP_CONFIG );

				file_put_contents( PS_LIVE_DEBUG_WP_CONFIG, '' );

				$write_wpconfig = fopen( PS_LIVE_DEBUG_WP_CONFIG, 'w' );

				foreach ( $editing_wpconfig as $line ) {
					if ( false !== strpos( $line, 'stop editing!' ) ) {
						fputs( $write_wpconfig, "define( 'WP_DEBUG_LOG', true ); // Added by CP Live Debug" . PHP_EOL );
					}

					fputs( $write_wpconfig, $line );
				}
			}

			fclose( $write_wpconfig );
		}

		/**
		 * Disable WP_DEBUG_LOG.
		 *
		 * @return void
		 */
		public static function disable_wp_debug_log() {
			$not_found        = true;
			$editing_wpconfig = file( PS_LIVE_DEBUG_WP_CONFIG );

			file_put_contents( PS_LIVE_DEBUG_WP_CONFIG, '' );

			$write_wpconfig = fopen( PS_LIVE_DEBUG_WP_CONFIG, 'w' );

			foreach ( $editing_wpconfig as $line ) {
				if ( false !== strpos( $line, "'WP_DEBUG_LOG'" ) || false !== strpos( $line, '"WP_DEBUG_LOG"' ) ) {
					$line      = "define( 'WP_DEBUG_LOG', false ); // Added by CP Live Debug" . PHP_EOL;
					$not_found = false;
				}
				fputs( $write_wpconfig, $line );
			}

			if ( $not_found ) {
				$editing_wpconfig = file( PS_LIVE_DEBUG_WP_CONFIG );

				file_put_contents( PS_LIVE_DEBUG_WP_CONFIG, '' );

				$write_wpconfig = fopen( PS_LIVE_DEBUG_WP_CONFIG, 'w' );

				foreach ( $editing_wpconfig as $line ) {
					if ( false !== strpos( $line, 'stop editing!' ) ) {
						fputs( $write_wpconfig, "define( 'WP_DEBUG_LOG', false ); // Added by CP Live Debug" . PHP_EOL );
					}

					fputs( $write_wpconfig, $line );
				}
			}

			fclose( $write_wpconfig );
		}

		/**
		 * Disable WP_DEBUG_DISPLAY.
		 *
		 * @return void
		 */
		public static function disable_wp_debug_display() {
			$not_found        = true;
			$editing_wpconfig = file( PS_LIVE_DEBUG_WP_CONFIG );

			file_put_contents( PS_LIVE_DEBUG_WP_CONFIG, '' );

			$write_wpconfig = fopen( PS_LIVE_DEBUG_WP_CONFIG, 'w' );

			foreach ( $editing_wpconfig as $line ) {
				if ( false !== strpos( $line, "'WP_DEBUG_DISPLAY'" ) || false !== strpos( $line, '"WP_DEBUG_DISPLAY"' ) ) {
					$line      = "define( 'WP_DEBUG_DISPLAY', false ); // Added by CP Live Debug" . PHP_EOL;
					$not_found = false;
				}

				fputs( $write_wpconfig, $line );
			}

			if ( $not_found ) {
				$editing_wpconfig = file( PS_LIVE_DEBUG_WP_CONFIG );

				file_put_contents( PS_LIVE_DEBUG_WP_CONFIG, '' );

				$write_wpconfig = fopen( PS_LIVE_DEBUG_WP_CONFIG, 'w' );

				foreach ( $editing_wpconfig as $line ) {
					if ( false !== strpos( $line, 'stop editing!' ) ) {
						fputs( $write_wpconfig, "define( 'WP_DEBUG_DISPLAY', false ); // Added by CP Live Debug" . PHP_EOL );
					}

					fputs( $write_wpconfig, $line );
				}
			}

			fclose( $write_wpconfig );
		}

		/**
		 * Disable ini_set display_errors.
		 *
		 * @return void
		 */
		public static function disable_wp_debug_ini_set_display() {
			$not_found        = true;
			$editing_wpconfig = file( PS_LIVE_DEBUG_WP_CONFIG );

			file_put_contents( PS_LIVE_DEBUG_WP_CONFIG, '' );

			$write_wpconfig = fopen( PS_LIVE_DEBUG_WP_CONFIG, 'w' );

			foreach ( $editing_wpconfig as $line ) {
				if ( false !== strpos( $line, "'display_errors'" ) || false !== strpos( $line, '"display_errors"' ) ) {
					$line      = "@ini_set( 'display_errors', 0 ); // Added by CP Live Debug" . PHP_EOL;
					$not_found = false;
				}

				fputs( $write_wpconfig, $line );
			}

			if ( $not_found ) {
				$editing_wpconfig = file( PS_LIVE_DEBUG_WP_CONFIG );

				file_put_contents( PS_LIVE_DEBUG_WP_CONFIG, '' );

				$write_wpconfig = fopen( PS_LIVE_DEBUG_WP_CONFIG, 'w' );

				foreach ( $editing_wpconfig as $line ) {
					if ( false !== strpos( $line, 'stop editing!' ) ) {
						fputs( $write_wpconfig, "@ini_set( 'display_errors', 0 ); // Added by CP Live Debug" . PHP_EOL );
					}

					fputs( $write_wpconfig, $line );
				}
			}

			fclose( $write_wpconfig );
		}

		/**
		 * Enable SCRIPT_DEBUG.
		 *
		 * @uses esc_html__()
		 * @uses wp_send_json_succes()
		 *
		 * @return string json success with the response.
		 */
		public static function enable_script_debug() {
			$not_found        = true;
			$editing_wpconfig = file( PS_LIVE_DEBUG_WP_CONFIG );

			file_put_contents( PS_LIVE_DEBUG_WP_CONFIG, '' );

			$write_wpconfig = fopen( PS_LIVE_DEBUG_WP_CONFIG, 'w' );

			foreach ( $editing_wpconfig as $line ) {
				if ( false !== strpos( $line, "'SCRIPT_DEBUG'" ) || false !== strpos( $line, '"SCRIPT_DEBUG"' ) ) {
					$line      = "define( 'SCRIPT_DEBUG', true ); // Added by CP Live Debug" . PHP_EOL;
					$not_found = false;
				}
				fputs( $write_wpconfig, $line );
			}

			if ( $not_found ) {
				$editing_wpconfig = file( PS_LIVE_DEBUG_WP_CONFIG );

				file_put_contents( PS_LIVE_DEBUG_WP_CONFIG, '' );

				$write_wpconfig = fopen( PS_LIVE_DEBUG_WP_CONFIG, 'w' );

				foreach ( $editing_wpconfig as $line ) {
					if ( false !== strpos( $line, 'stop editing!' ) ) {
						fputs( $write_wpconfig, "define( 'SCRIPT_DEBUG', true ); // Added by CP Live Debug" . PHP_EOL );
					}

					fputs( $write_wpconfig, $line );
				}
			}

			fclose( $write_wpconfig );

			$response = array(
				'message' => esc_html__( 'SCRIPT_DEBUG was enabled.', 'ps-live-debug' ),
			);

			wp_send_json_success( $response );
		}

		/**
		 * Disable SCRIPT_DEBUG.
		 *
		 * @uses esc_html__()
		 * @uses wp_send_json_succes()
		 *
		 * @return string json success with the response.
		 */
		public static function disable_script_debug() {
			$not_found        = true;
			$editing_wpconfig = file( PS_LIVE_DEBUG_WP_CONFIG );

			file_put_contents( PS_LIVE_DEBUG_WP_CONFIG, '' );

			$write_wpconfig = fopen( PS_LIVE_DEBUG_WP_CONFIG, 'w' );

			foreach ( $editing_wpconfig as $line ) {
				if ( false !== strpos( $line, "'SCRIPT_DEBUG'" ) || false !== strpos( $line, '"SCRIPT_DEBUG"' ) ) {
					$line      = "define( 'SCRIPT_DEBUG', false ); // Added by CP Live Debug" . PHP_EOL;
					$not_found = false;
				}

				fputs( $write_wpconfig, $line );
			}

			if ( $not_found ) {
				$editing_wpconfig = file( PS_LIVE_DEBUG_WP_CONFIG );

				file_put_contents( PS_LIVE_DEBUG_WP_CONFIG, '' );

				$write_wpconfig = fopen( PS_LIVE_DEBUG_WP_CONFIG, 'w' );

				foreach ( $editing_wpconfig as $line ) {
					if ( false !== strpos( $line, 'stop editing!' ) ) {
						fputs( $write_wpconfig, "define( 'SCRIPT_DEBUG', false ); // Added by CP Live Debug" . PHP_EOL );
					}

					fputs( $write_wpconfig, $line );
				}
			}

			fclose( $write_wpconfig );

			$response = array(
				'message' => esc_html__( 'SCRIPT_DEBUG was disabled.', 'ps-live-debug' ),
			);

			wp_send_json_success( $response );
		}

		/**
		 * Enable SAVEQUERIES.
		 *
		 * @uses esc_html__()
		 * @uses wp_send_json_succes()
		 *
		 * @return string json success with the response.
		 */
		public static function enable_savequeries() {
			$not_found        = true;
			$editing_wpconfig = file( PS_LIVE_DEBUG_WP_CONFIG );

			file_put_contents( PS_LIVE_DEBUG_WP_CONFIG, '' );

			$write_wpconfig = fopen( PS_LIVE_DEBUG_WP_CONFIG, 'w' );

			foreach ( $editing_wpconfig as $line ) {
				if ( false !== strpos( $line, "'SAVEQUERIES'" ) || false !== strpos( $line, '"SAVEQUERIES"' ) ) {
					$line      = "define( 'SAVEQUERIES', true ); // Added by CP Live Debug" . PHP_EOL;
					$not_found = false;
				}

				fputs( $write_wpconfig, $line );
			}

			if ( $not_found ) {
				$editing_wpconfig = file( PS_LIVE_DEBUG_WP_CONFIG );

				file_put_contents( PS_LIVE_DEBUG_WP_CONFIG, '' );

				$write_wpconfig = fopen( PS_LIVE_DEBUG_WP_CONFIG, 'w' );

				foreach ( $editing_wpconfig as $line ) {
					if ( false !== strpos( $line, 'stop editing!' ) ) {
						fputs( $write_wpconfig, "define( 'SAVEQUERIES', true ); // Added by CP Live Debug" . PHP_EOL );
					}

					fputs( $write_wpconfig, $line );
				}
			}

			fclose( $write_wpconfig );

			$response = array(
				'message' => esc_html__( 'SAVEQUERIES was enabled.', 'ps-live-debug' ),
			);

			wp_send_json_success( $response );
		}

		/**
		 * Disable SAVEQUERIES.
		 *
		 * @uses esc_html__()
		 * @uses wp_send_json_succes()
		 *
		 * @return string json success with the response.
		 */
		public static function disable_savequeries() {
			$not_found        = true;
			$editing_wpconfig = file( PS_LIVE_DEBUG_WP_CONFIG );

			file_put_contents( PS_LIVE_DEBUG_WP_CONFIG, '' );

			$write_wpconfig = fopen( PS_LIVE_DEBUG_WP_CONFIG, 'w' );

			foreach ( $editing_wpconfig as $line ) {
				if ( false !== strpos( $line, "'SAVEQUERIES'" ) || false !== strpos( $line, '"SAVEQUERIES"' ) ) {
					$line      = "define( 'SAVEQUERIES', false ); // Added by CP Live Debug" . PHP_EOL;
					$not_found = false;
				}

				fputs( $write_wpconfig, $line );
			}

			if ( $not_found ) {
				$editing_wpconfig = file( PS_LIVE_DEBUG_WP_CONFIG );

				file_put_contents( PS_LIVE_DEBUG_WP_CONFIG, '' );

				$write_wpconfig = fopen( PS_LIVE_DEBUG_WP_CONFIG, 'w' );

				foreach ( $editing_wpconfig as $line ) {
					if ( false !== strpos( $line, 'stop editing!' ) ) {
						fputs( $write_wpconfig, "define( 'SAVEQUERIES', false ); // Added by CP Live Debug" . PHP_EOL );
					}

					fputs( $write_wpconfig, $line );
				}
			}

			fclose( $write_wpconfig );

			$response = array(
				'message' => esc_html__( 'SAVEQUERIES was disabled.', 'ps-live-debug' ),
			);

			wp_send_json_success( $response );
		}

		/**
		 * Read log.
		 *
		 * @uses get_option()
		 * @uses esc_html__()
		 * @uses wp_die()
		 *
		 * @return string $debug_contents The content of debug.log
		 */
		public static function read_debug_log() {
			$log_file = get_option( 'PS_LIVE_DEBUG_log_file' );

			if ( file_exists( $log_file ) ) {
				if ( 2000000 > filesize( $log_file ) ) {
					$debug_contents = file_get_contents( $log_file );
					if ( empty( $debug_contents ) ) {
						// translators: %1$s log filename.
						$debug_contents = sprintf( esc_html__( 'Awesome! %1$s scheint leer zu sein.', 'ps-live-debug' ), basename( $log_file ) );
					}
				} else {
					// translators: %1$s log filename.
					$debug_contents = sprintf( esc_html__( '%1$s is over 2 MB. Please open it via FTP.', 'ps-live-debug' ), basename( $log_file ) );
				}
			} else {
				// translators: %1$s log filename.
				$debug_contents = sprintf( esc_html__( 'Could not find %1$s file.', 'ps-live-debug' ), basename( $log_file ) );

			}

			echo $debug_contents;

			wp_die();
		}

		/**
		 * Select log.
		 *
		 * @uses sanitize_text_field()
		 * @uses wp_verify_nonce()
		 * @uses update_option()
		 * @uses wp_send_json_error()
		 * @uses wp_send_json_success()
		 *
		 * @return string json success / error with the response.
		 */
		public static function select_log_file() {
			$nonce    = sanitize_text_field( $_POST['nonce'] );
			$log_file = sanitize_text_field( $_POST['log'] );

			if ( ! wp_verify_nonce( $nonce, $log_file ) ) {
				wp_send_json_error();
			}

			if ( 'log' != substr( strrchr( $log_file, '.' ), 1 ) ) {
				wp_send_json_error();
			}

			update_option( 'PS_LIVE_DEBUG_log_file', $log_file );

			wp_send_json_success();
		}

		/**
		 * Clear log.
		 *
		 * @uses sanitize_text_field()
		 * @uses wp_verify_nonce()
		 * @uses wp_send_json_error()
		 * @uses wp_send_json_success()
		 *
		 * @return string json success / error with the response.
		 */
		public static function clear_debug_log() {
			$nonce    = sanitize_text_field( $_POST['nonce'] );
			$log_file = sanitize_text_field( $_POST['log'] );

			if ( ! wp_verify_nonce( $nonce, $log_file ) ) {
				$response = array(
					'message' => esc_html__( 'Could not validate nonce', 'ps-live-debug' ),
				);
				wp_send_json_error( $response );
			}

			if ( 'log' != substr( strrchr( $log_file, '.' ), 1 ) ) {
				$response = array(
					'message' => esc_html__( 'This is not a log file.', 'ps-live-debug' ),
				);

				wp_send_json_error( $response );
			}

			file_put_contents( $log_file, '' );

			$response = array(
				'message' => esc_html__( '.log was cleared', 'ps-live-debug' ),
			);

			wp_send_json_success( $response );
		}

		/**
		 * Delete log.
		 *
		 * @uses sanitize_text_field()
		 * @uses wp_verify_nonce()
		 * @uses update_option()
		 * @uses PS_Live_Debug_Helper::create_debug_log()
		 * @uses wp_send_json_error()
		 * @uses wp_send_json_success()
		 *
		 * @return string json success / error with the response.
		 */
		public static function delete_debug_log() {
			$nonce    = sanitize_text_field( $_POST['nonce'] );
			$log_file = sanitize_text_field( $_POST['log'] );

			if ( ! wp_verify_nonce( $nonce, $log_file ) ) {
				wp_send_json_error();
			}

			if ( 'log' != substr( strrchr( $log_file, '.' ), 1 ) ) {
				wp_send_json_error();
			}

			unlink( $log_file );

			PS_Live_Debug_Helper::create_debug_log();

			$log_file = wp_normalize_path( WP_CONTENT_DIR . '/debug.log' );

			update_option( 'PS_LIVE_DEBUG_log_file', $log_file );

			wp_send_json_success();
		}
	}
}
