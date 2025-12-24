<?php //phpcs:ignore

// Check that the file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Es tut uns leid, aber Du kannst nicht direkt auf diese Datei zugreifen.' );
}

/**
 * PS_Live_Debug_Server_Info Class.
 */
if ( ! class_exists( 'PS_Live_Debug_Tools' ) ) {
	class PS_Live_Debug_Tools {

		/**
		 * PS_Live_Debug_Tools constructor.
		 *
		 * @uses PS_Live_Debug_Tools::init()
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
			add_action( 'wp_ajax_ps-live-debug-tools-ssl-information', array( 'PS_Live_Debug_Tools', 'ssl_information' ) );
			add_action( 'wp_ajax_ps-live-debug-tools-checksums-check', array( 'PS_Live_Debug_Tools', 'run_checksums_check' ) );
			add_action( 'wp_ajax_ps-live-debug-tools-view-diff', array( 'PS_Live_Debug_Tools', 'view_file_diff' ) );
			add_action( 'wp_ajax_ps-live-debug-tools-wp-mail', array( 'PS_Live_Debug_Tools', 'send_mail' ) );
		}

		/**
		 * Create the Tools page.
		 *
		 * @uses wp_get_current_user()
		 * @uses get_bloginfo()
		 * @uses esc_html__()
		 * @uses esc_html_e()
		 * @uses get_option()
		 * @uses get_site_url()
		 *
		 * @return string The html of the page viewed.
		 */
		public static function create_page() {
			$current_user = wp_get_current_user();
			$wp_address   = get_bloginfo( 'url' );
			$wp_name      = get_bloginfo( 'name' );
			$date         = date( 'F j, Y' );
			$time         = date( 'g:i a' );

			// translators: %s: website url.
			$email_subject = sprintf( esc_html__( 'Testnachricht von %s', 'ps-live-debug' ), $wp_address );

			$email_body = sprintf(
				// translators: %1$s: website name. %2$s: website url. %3$s: date. %4$s: time
				esc_html__( 'Hallo. Diese Testnachricht wurde von %1$s (%2$s) am %3$s um %4$s gesendet. Da Du dies liest, funktioniert es offensichtlich!', 'ps-live-debug' ),
				$wp_name,
				$wp_address,
				$date,
				$time
			);

			if ( ! empty( get_option( 'PS_LIVE_DEBUG_ssl_domain' ) ) ) {
				$host = get_option( 'PS_LIVE_DEBUG_ssl_domain' );
			} else {
				$host = str_replace( array( 'http://', 'https://' ), '', get_site_url() );
			}
			?>
				<div class="sui-box">
					<div class="sui-box-body">
						<div class="sui-tabs">
							<div data-tabs>
								<div class="active"><?php esc_html_e( 'SSL Information', 'ps-live-debug' ); ?></div>
								<div><?php esc_html_e( 'Prüfsummen Check', 'ps-live-debug' ); ?></div>
								<div><?php esc_html_e( 'wp_mail() Check', 'ps-live-debug' ); ?></div>
							</div>
							<div data-panes>
								<div id="ssl-holder" class="active">
									<form action="#" method="POST" id="check-ssl">
										<div class="sui-with-button">
												<input id="ssl-host" type="text" class="sui-form-control" value="<?php echo $host; ?>">
												<input type="submit" class="sui-button sui-button-lg sui-button-green" value="<?php esc_html_e( 'Verifizieren', 'ps-live-debug' ); ?>">
										</div>
									</form>
									<div id="ssl-response"></div>
								</div>
								<div id="checksums-response">
									<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
								</div>
								<div id="mail-check-box">
									<form action="#" id="ps-live-debug-mail-check" method="POST">
										<div class="sui-form-field">
											<label for="email" class="sui-label"><?php esc_html_e( 'E-mail', 'ps-live-debug' ); ?></label>
											<input type="email" id="email" name="email" class="sui-form-control" value="<?php echo $current_user->user_email; ?>">
										</div>
										<div class="sui-form-field">
											<label for="email_subject" class="sui-label"><?php esc_html_e( 'Betreff', 'ps-live-debug' ); ?></label>
											<input type="text" id="email_subject" name="email_subject" class="sui-form-control" value="<?php echo $email_subject; ?>">
										</div>
										<div class="sui-form-field">
											<label for="email_message" class="sui-label"><?php esc_html_e( 'Nachricht', 'ps-live-debug' ); ?></label>
											<textarea id="email_message" name="email_message" class="sui-form-control" rows="4"><?php echo $email_body; ?></textarea>
										</div>
										<div class="sui-form-field">
											<input type="submit" class="sui-button sui-button-green" value="<?php esc_html_e( 'Testmail senden', 'ps-live-debug' ); ?>">
										</div>
									</form>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="sui-dialog sui-dialog-lg" aria-hidden="true" tabindex="-1" id="checksums-popup">
					<div class="sui-dialog-overlay" data-a11y-dialog-hide></div>
					<div class="sui-dialog-content" aria-labelledby="dialogTitle" aria-describedby="dialogDescription" role="dialog">
						<div class="sui-box" role="document">
							<div class="sui-box-header">
								<h3 class="sui-box-title"></h3>
								<div class="sui-actions-right">
									<button data-a11y-dialog-hide class="sui-dialog-close" aria-label="Schließe dieses Dialogfenster"></button>
								</div>
							</div>
							<div class="sui-box-body">
								<div class="diff-holder"><i class="sui-icon-loader sui-loading" aria-hidden="true"></i></div>
							</div>
						</div>
					</div>
				</div>
			<?php
		}

		/**
		 * Get information on the SSL certificate.
		 *
		 * @uses sanitize_text_field()
		 * @uses wp_remote_get()
		 * @uses is_wp_error()
		 * @uses get_error_message()
		 * @uses esc_html__()
		 * @uses wp_remote_retrieve_body()
		 * @uses wp_send_json_success()
		 *
		 * @return string json success with the response.
		 */
		public static function ssl_information() {
			$host         = sanitize_text_field( $_POST['host'] );
			$api_response = wp_remote_get(
				'https://api.ssllabs.com/api/v3/analyze',
				array(
					'body' => array(
						'host'            => $host,
						'publish'         => 'off',
						'start_new'       => 'on',
						'from_cache'      => 'off',
						'max_age'         => null,
						'all'             => 'done',
						'ignore_mismatch' => 'off',
					),
				)
			);

			if ( is_wp_error( $api_response ) ) {
				$error_message = $api_response->get_error_message();
				$output        = '<div class="sui-notice sui-notice-error"><p>';
				$output       .= esc_html__( 'Etwas ist schief gelaufen', 'ps-live-debug' ) . ': ' . $error_message; // phpcs:ignore
				$output       .= '</p></div>';
				$output       .= '<table class="sui-table striped">';
			} else {
				$call = json_decode( wp_remote_retrieve_body( $api_response ), true );

				if ( 'IN_PROGRESS' === $call['status'] ) {
					$progress       = 0;
					$progress_count = 0;

					foreach ( $call['endpoints'] as $key => $endpoint ) {
						if ( ! empty( $call['endpoints'][ $key ]['progress'] ) ) {
							$progress = $progress + $call['endpoints'][ $key ]['progress'];
							$progress_count++;
						}
					}
					if ( 0 != $progress ) {
						$prototal = floor( $progress / $progress_count );
					} else {
						$prototal = 0;
					}

					$output  = '<div class="sui-notice sui-notice-info"><p>';
					$output .= esc_html__( 'Testen und Sammeln von Informationen für:', 'ps-live-debug' ) . '<strong> '.  $host . '</strong>. ' . esc_html( 'Dies kann eine Weile dauern, also stelle sicher, dass diese Seite geöffnet bleibt!' , 'ps-live-debug' ); // phpcs:ignore
					$output .= '</p></div>';
					$output .= '<div class="sui-progress-block"><div class="sui-progress"><div class="sui-progress-text sui-icon-loader sui-loading">';
					$output .= '<span>' . $prototal . '%</span>';
					$output .= '</div><div class="sui-progress-bar">';
					$output .= '<span style="width: ' . $prototal . '%"></span>';
					$output .= '</div></div></div>';

					$response = array(
						'message' => $output,
						'status'  => 'in_progress',
					);
				} elseif ( 'ERROR' === $call['status'] ) {
					$output  = '<div class="sui-notice sui-notice-error"><p>';
					$output .= $call['status'] . ': ' . $call['statusMessage']; // phpcs:ignore
					$output .= '</p></div>';
					$output .= '<table class="sui-table striped">';
					$output .= '<thead><tr><th>' . esc_html__( 'Titel', 'ps-live-debug' ) . '</th><th>' . esc_html__( 'Wert', 'ps-live-debug' ) . '</th></tr></thead>';
					$output .= '<tbody>';
					$output .= '<tr><td>' . esc_html__( 'Host', 'ps-live-debug' ) . '</td><td>' . $call['host'] . '</td></tr>';
					$output .= '<tr><td>' . esc_html__( 'Port', 'ps-live-debug' ) . '</td><td>' . $call['port'] . '</td></tr>';
					$output .= '<tr><td>' . esc_html__( 'Protokol', 'ps-live-debug' ) . '</td><td>' . $call['protocol'] . '</td></tr>';
					$output .= '</tbody>';
					$output .= '<tfoot><tr><th>' . esc_html__( 'Titel', 'ps-live-debug' ) . '</th><th>' . esc_html__( 'Wert', 'ps-live-debug' ) . '</th></tr></tfoot>';
					$output .= '</table>';

					$response = array(
						'message' => $output,
						'status'  => 'error',
					);
				} elseif ( 'READY' === $call['status'] ) {
					$output  = '<div class="sui-notice sui-notice-success"><p>';
					$output .= esc_html__( 'Erfolg! Gültige SSL-Informationen erhalten für', 'ps-live-debug' ) . ': ' . $call['host'];
					$output .= '</p></div>';
					$output .= '<table class="sui-table striped">';
					$output .= '<thead><tr><th>' . esc_html__( 'Titel', 'ps-live-debug' ) . '</th><th>' . esc_html__( 'Wert', 'ps-live-debug' ) . '</th></tr></thead>';
					$output .= '<tbody>';
					$output .= '<tr><td>' . esc_html__( 'Host', 'ps-live-debug' ) . '</td><td>' . $call['host'] . '</td></tr>';
					$output .= '<tr><td>' . esc_html__( 'Port', 'ps-live-debug' ) . '</td><td>' . $call['port'] . '</td></tr>';
					$output .= '<tr><td>' . esc_html__( 'Protokol', 'ps-live-debug' ) . '</td><td>' . $call['protocol'] . '</td></tr>';
					$output .= '<tr><td>' . esc_html__( 'Alternative Namen', 'ps-live-debug' ) . '</td><td>';
					$output .= implode( '<br>', $call['certs'][0]['altNames'] );
					$output .= '</td></tr>';
					$output .= '<tr><td>' . esc_html__( 'IP Addresse', 'ps-live-debug' ) . '</td><td>' . $call['endpoints'][0]['ipAddress'] . '</td></tr>';
					$output .= '<tr><td>' . esc_html__( 'Aussteller', 'ps-live-debug' ) . '</td><td>' . $call['certs'][1]['commonNames'][0] . '</td></tr>';
					$output .= '<tr><td>' . esc_html__( 'Zertifikat-ID', 'ps-live-debug' ) . '</td><td>' . $call['certs'][0]['id'] . '</td></tr>';
					$output .= '<tr><td>' . esc_html__( 'Protokolle', 'ps-live-debug' ) . '</td><td>';

					foreach ( $call['endpoints'][0]['details']['protocols'] as $protocol ) {
						$output .= $protocol['name'] . $protocol['version'] . '<br>';
					}

					$output .= '</td></tr>';
					$output .= '</tbody>';
					$output .= '<tfoot><tr><th>' . esc_html__( 'Titel', 'ps-live-debug' ) . '</th><th>' . esc_html__( 'Wert', 'ps-live-debug' ) . '</th></tr></tfoot>';
					$output .= '</table>';

					$response = array(
						'message' => $output,
						'status'  => 'ready',
					);
				} else {
					$output  = '<div class="sui-notice sui-notice-info"><p>';
					$output .= esc_html__( 'Testen und Sammeln von Informationen für:', 'ps-live-debug' ) . '<strong> '.  $host . '</strong>. ' . esc_html( 'Dies kann eine Weile dauern, also stelle sicher, dass diese Seite geöffnet bleibt!' , 'ps-live-debug' ); // phpcs:ignore
					$output .= '</p></div>';
					$output .= '<div class="sui-progress-block"><div class="sui-progress"><div class="sui-progress-text sui-icon-loader sui-loading">';
					$output .= '<span>0%</span>';
					$output .= '</div><div class="sui-progress-bar">';
					$output .= '<span style="width: 0"></span>';
					$output .= '</div></div></div>';

					$response = array(
						'message' => $output,
						'status'  => 'else',
					);
				}
			}

			wp_send_json_success( $response );
		}

		/**
		 * Gathers checksums from ClassicPress API and cross checks the core files in the current installation.
		 *
		 * @uses PS_Live_Debug_Tools::call_checksums_api()
		 * @uses PS_Live_Debug_Tools::parse_checksums_results()
		 * @uses PS_Live_Debug_Tools::create_checksums_response()
		 *
		 * @return void
		 */
		public static function run_checksums_check() {
			$checksums = PS_Live_Debug_Tools::call_checksums_api();
			$files     = PS_Live_Debug_Tools::parse_checksums_results( $checksums );

			PS_Live_Debug_Tools::create_checksums_response( $files );
		}

		/**
		* Calls the ClassicPress API on the checksums endpoint.
		*
		* @uses get_bloginfo()
		* @uses get_locale()
		* @uses get_bloginfo()
		* @uses wp_remote_get()
		* @uses wp_remote_retrieve_body()
		*
		* @return array $checksumapibody Array of files and their checksums.
		*/
		public static function call_checksums_api() {
			$wpversion       = get_bloginfo( 'version' );
			$wplocale        = get_locale();
			       $checksumapi     = wp_remote_get( 'https://api.classicpress.net/core/checksums/1.0/?version=' . $wpversion . '&locale=' . $wplocale, array( 'timeout' => 10000 ) );
			       $checksumapibody = json_decode( wp_remote_retrieve_body( $checksumapi ), true );

			       if ( ! is_array( $checksumapibody ) || ! isset( $checksumapibody['checksums'] ) || ! is_array( $checksumapibody['checksums'] ) ) {
				       return array( 'checksums' => array() );
			       }

			       foreach ( $checksumapibody['checksums'] as $file => $checksum ) {
				       if ( false !== strpos( $file, 'wp-content/' ) ) {
					       unset( $checksumapibody['checksums'][ $file ] );
				       }
			       }

			       return $checksumapibody;
		}

		/**
		* Parses the results from the ClassicPress API call
		*
		* @param array $checksums The checksums list from ClassicPress API.

		* @uses esc_html__()
		*
		* @return array $files The files that have a wrong checksum.
		*/
		public static function parse_checksums_results( $checksums ) {
			$filepath = ABSPATH;
			$files    = array();


			       if ( ! is_array( $checksums ) || ! isset( $checksums['checksums'] ) || ! is_array( $checksums['checksums'] ) ) {
				       return $files;
			       }

			       foreach ( $checksums['checksums'] as $file => $checksum ) {
				       if ( file_exists( $filepath . $file ) && md5_file( $filepath . $file ) !== $checksum ) {
					       $reason = '<button data-do="ps-live-debug-diff" class="sui-button sui-button-red" data-file="' . $file . '">' . esc_html__( 'Änderungen anzeigen', 'ps-live-debug' ) . '</button>';
					       array_push( $files, array( $file, $reason ) );
				       } elseif ( ! file_exists( $filepath . $file ) ) {
					       $reason = esc_html__( 'Datei nicht gefunden', 'ps-live-debug' );
					       array_push( $files, array( $file, $reason ) );
				       }
			       }

			return $files;
		}

		/**
		* Generates the response
		*
		* @param array $files The files that have wrong checksums.
		*
		* @uses wp_normalize_path()
		* @uses wp_send_json_success()
		* @uses esc_html__()
		*
		* @return string json success with the response.
		*/
		public static function create_checksums_response( $files ) {
			$filepath = wp_normalize_path( ABSPATH );
			$output   = '';

			if ( empty( $files ) ) {
				$output .= '<div class="sui-notice sui-notice-success"><p>';
				$output .= esc_html__( 'Alle Prüfsummen sind bestanden. Alles scheint in Ordnung zu sein!', 'ps-live-debug' );
				$output .= '</p></div>';
			} else {
				$output .= '<div class="sui-notice sui-notice-error"><p>';
				$output .= esc_html__( 'Es scheint, dass einige Dateien geändert wurden.', 'ps-live-debug' );
				$output .= '<br>' . esc_html__( "Dies kann ein falsch positives Ergebnis sein, wenn Ihre Installation übersetzte Versionen enthält. Eine einfache Möglichkeit, dies zu beheben, besteht darin, ClassicPress neu zu installieren, aber keine Sorge, da dies nur die ClassicPress-Kerndateien betrifft.", 'ps-live-debug' );
				$output .= '</p></div><table class="sui-table striped"><thead><tr><th>';
				$output .= esc_html__( 'Datei', 'ps-live-debug' );
				$output .= '</th><th>';
				$output .= esc_html__( 'Grund', 'ps-live-debug' );
				$output .= '</th></tr></thead><tbody>';

				foreach ( $files as $tampered ) {
					$output .= '<tr>';
					$output .= '<td>' . $filepath . $tampered[0] . '</td>';
					$output .= '<td>' . $tampered[1] . '</td>';
					$output .= '</tr>';
				}

				$output .= '<tfoot><tr><th>' . esc_html__( 'Datei', 'ps-live-debug' ) . '</th><th>' . esc_html__( 'Grund', 'ps-live-debug' ) . '</th></tr></tfoot>';
				$output .= '</tbody>';
				$output .= '</table>';
			}

			$response = array(
				'message' => $output,
			);

			wp_send_json_success( $response );
		}

		/**
		* Generates Diff view
		*
		* @uses current_user_can()
		* @uses wp_normalize_path()
		* @uses get_bloginfo()
		* @uses wp_remote_get()
		* @uses wp_remote_retrieve_body()
		* @uses esc_html__()
		* @uses wp_send_json_error()
		* @uses wp_send_json_success()
		*
		* @return string json success / error with the response.
		*/
		public static function view_file_diff() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Weiß deine Mutter, dass du das machst?', 'ps-live-debug' ),
					)
				);
			}

			$filepath    = wp_normalize_path( ABSPATH );
			$file        = $_POST['file'];
			$actual_file = wp_normalize_path( realpath( "{$filepath}{$file}" ) );

			if ( empty( $actual_file ) || ! is_readable( $actual_file ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Kann das nicht.', 'ps-live-debug' ),
					)
				);
			}

			if ( ! preg_match( '/^' . preg_quote( $filepath, '/' ) . '/', $actual_file ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Werde dies nicht tun.', 'ps-live-debug' ),
					)
				);
			}

			$wpversion        = get_bloginfo( 'version' );
			$local_file_body  = file_get_contents( $actual_file, FILE_USE_INCLUDE_PATH );
			$remote_file      = wp_remote_get( 'https://core.svn.wordpress.org/tags/' . $wpversion . '/' . $file );
			$remote_file_body = wp_remote_retrieve_body( $remote_file );
			$diff_args        = array(
				'show_split_view' => true,
			);

			$output  = '<table class="diff"><thead><tr class="diff-sub-title"><th>';
			$output .= esc_html__( 'Original', 'ps-live-debug' );
			$output .= '</th><th>';
			$output .= esc_html__( 'Geändert', 'ps-live-debug' );
			$output .= '</th></tr></table>';
			$output .= wp_text_diff( $remote_file_body, $local_file_body, $diff_args );

			$response = array(
				'message' => $output,
			);

			wp_send_json_success( $response );
		}

		/**
		 * Checks if wp_mail() works.
		 *
		 * @uses current_user_can()
		 * @uses sanitize_email()
		 * @uses sanitize_text_field()
		 * @uses sanitize_textarea_field()
		 * @uses wp_mail()
		 * @uses esc_html__()
		 * @uses wp_send_json_error()
		 * @uses wp_send_json_success()
		 *
		 * @return string json success / error with the response.
		 */
		public static function send_mail() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Weiß deine Mutter, dass du das machst?', 'ps-live-debug' ),
					)
				);
			}

			$output        = '';
			$sendmail      = false;
			$email         = sanitize_email( $_POST['email'] );
			$email_subject = sanitize_text_field( $_POST['email_subject'] );
			$email_message = sanitize_textarea_field( $_POST['email_message'] );

			$sendmail = wp_mail( $email, $email_subject, $email_message );

			if ( ! empty( $sendmail ) ) {
				$output .= '<div class="sui-notice sui-notice-success"><p>';
				$output .= __( "Du hast gerade eine E-Mail mit <code>wp_mail()</code> gesendet und es scheint zu funktionieren. Bitte überprüfe Deinen Posteingang und Spam-Ordner, um zu sehen, ob Du es erhalten hast.", 'ps-live-debug' );
				$output .= '</p></div>';
			} else {
				$output .= '<div class="sui-notice sui-notice-error"><p>';
				$output .= esc_html__( 'Hier ist ein Problem beim Senden der E-Mail aufgetreten.', 'ps-live-debug' );
				$output .= '</p></div>';
			}

			$response = array(
				'message' => $output,
			);

			wp_send_json_success( $response );
		}
	}
}
