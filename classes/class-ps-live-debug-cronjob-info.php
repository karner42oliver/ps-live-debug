<?php //phpcs:ignore

// Check that the file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Es tut uns leid, aber Du kannst nicht direkt auf diese Datei zugreifen.' );
}

/**
 * PS_Live_Debug_Cronjob_Info Class.
 */
if ( ! class_exists( 'PS_Live_Debug_Cronjob_Info' ) ) {
	class PS_Live_Debug_Cronjob_Info {

		/**
		 * PS_Live_Debug_Cronjob_Info constructor.
		 *
		 * @uses PS_Live_Debug_Cronjob_Info::init()
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
			add_action( 'wp_ajax_ps-live-debug-cronjob-info-scheduled-events', array( 'PS_Live_Debug_Cronjob_Info', 'scheduled_events' ) );
			add_action( 'wp_ajax_ps-live-debug-cronjob-info-run-event', array( 'PS_Live_Debug_Cronjob_Info', 'run_event' ) );
		}

		/**
		 * Create the Schedules Events page.
		 *
		 * @uses esc_html__()
		 *
		 * @return string The html of the page viewed.
		 */
		public static function create_page() {
			?>
				<div style="display:none;" id="job-success" class="sui-notice-top sui-notice-success sui-can-dismiss">
					<div class="sui-notice-content">
						<p><strong><span class="hookname"></span></strong>&nbsp;<?php esc_html_e( 'has run successfully!', 'ps-live-debug' ); ?></p>
					</div>
					<span class="sui-notice-dismiss"><a role="button" href="#" aria-label="Dismiss" class="sui-icon-check"></a>
					</span>
				</div>
				<div style="display:none;" id="job-error" class="sui-notice-top sui-notice-error sui-can-dismiss">
					<div class="sui-notice-content">
						<p><strong><span class="hookname"></span></strong>&nbsp;<?php esc_html_e( 'could not run.', 'ps-live-debug' ); ?></p>
					</div>
					<span class="sui-notice-dismiss"><a role="button" href="#" aria-label="Dismiss" class="sui-icon-check"></a></span>
				</div>
				<div class="sui-box">
					<div class="sui-box-header">
						<h2 class="sui-box-title"><?php esc_html_e( 'Scheduled Events', 'ps-live-debug' ); ?></h2>
					</div>
					<div class="sui-box-body" id="cronjob-response">
						<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
					</div>
				</div>
			<?php
		}

		/**
		 * Create the scheduled events table.
		 *
		 * @uses _get_cron_array()
		 * @uses PS_Live_Debug_Cronjob_Info::get_events()
		 * @uses PS_Live_Debug_Cronjob_Info::get_actions()
		 * @uses wp_create_nonce()
		 * @uses wp_send_json_success()
		 * @uses human_time_diff()
		 * @uses esc_html__()
		 *
		 * @return string json success / error with the response.
		 */
		public static function scheduled_events() {

			if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
				$output = '<div class="sui-notice sui-notice-error"><p>' . esc_html__( 'WP Cron ist deaktiviert!', 'ps-live-debug' ) . '</p></div>';
			} else {
				$output = '<div class="sui-notice sui-notice-success"><p>' . esc_html__( 'WP Cron ist aktiviert!', 'ps-live-debug' ) . '</p></div>';
			}

			if ( function_exists( '_get_cron_array' ) ) {
				$cronjobs = _get_cron_array();
			} else {
				$cronjobs = get_option( 'cron' );
			}

			$output .= '<table class="sui-table striped">';
			$output .= '<thead><tr><th>' . esc_html__( 'Ereignis', 'ps-live-debug' ) . '</th><th>' . esc_html__( 'Aktionen', 'ps-live-debug' ) . '</th><th>' . esc_html__( 'Zeitplan', 'ps-live-debug' ) . '</th></tr></thead><tbody>';

			$events = PS_Live_Debug_Cronjob_Info::get_events();

			foreach ( $events as $id => $event ) {
				$output .= '<tr>';
				$output .= '<td>' . $event->hook . '<br>';

				if ( ! empty( $event->schedule ) ) {
					$output .= $event->schedule . ' ( ' . $event->interval . ' ) ';
				} else {
					$output .= esc_html__( 'single', 'ps-live-debug' ) . ' ';
				}

				$output .= '<a href="#" data-do="run-job" data-nonce="' . wp_create_nonce( $event->hook ) . '" data-hook="' . $event->hook . '" data-sig="' . $event->sig . '">Run Now</a>';
				$output .= '</td>';
				$output .= '<td>';

				$actions = array();

				foreach ( PS_Live_Debug_Cronjob_Info::get_actions( $event->hook ) as $action ) {
					$actions[] = $action['callback']['name'] . ' ( ' . $action['priority'] . ' )<br>';
				}

				$output .= implode( '', $actions );
				$output .= '</td>';
				$output .= '<td><strong>' . esc_html__( 'Nächster Lauf in', 'ps-live-debug' ) . ':</strong> ' . human_time_diff( $event->time, time() ) . '<br>' . date( 'H:i - F j, Y', $event->time ) . '</td>';
				$output .= '</tr>';
			}

			$output .= '<tfoot><tr><th>' . esc_html__( 'Aufgabe', 'ps-live-debug' ) . '</th><th>' . esc_html__( 'Aktionen', 'ps-live-debug' ) . '</th><th>' . esc_html__( 'Zeitplan', 'ps-live-debug' ) . '</th></tr></tfoot>';
			$output .= '</tbody></table>';

			$response = array(
				'message' => $output,
			);

			wp_send_json_success( $response );
		}

		/**
		 * Run the scheduled event.
		 *
		 * @uses sanitize_text_field()
		 * @uses wp_verify_nonce()
		 * @uses _get_cron_array()
		 * @uses ge_option()
		 * @uses delete_transient()
		 * @uses wp_schedule_single_event()
		 * @uses spawn_cron()
		 * @uses wp_send_json_error()
		 * @uses wp_send_json_success()
		 *
		 * @return string json success / error with the response.
		 */
		public static function run_event() {
			$hook  = sanitize_text_field( $_POST['hook'] );
			$sig   = sanitize_text_field( $_POST['sig'] );
			$nonce = sanitize_text_field( $_POST['nonce'] );

			if ( ! wp_verify_nonce( $nonce, $hook ) ) {
				wp_send_json_error();
			}

			if ( function_exists( '_get_cron_array' ) ) {
				$cronjobs = _get_cron_array();
			} else {
				$cronjobs = get_option( 'cron' );
			}

			foreach ( $cronjobs as $time => $cron ) {
				if ( isset( $cron[ $_POST['hook'] ][ $_POST['sig'] ] ) ) {
					$args = $cron[ $_POST['hook'] ][ $_POST['sig'] ]['args'];
					delete_transient( 'doing_cron' );
					wp_schedule_single_event( time() - 1, $_POST['hook'], $args );
					spawn_cron();
					wp_send_json_success();
				}
			}

			wp_send_json_error();
		}

		/**
		 * Get the scheduled events.
		 *
		 * @uses _get_cron_array()
		 * @uses get_option()
		 *
		 * @return array $events The scheduled events.
		 */
		public static function get_events() {
			if ( function_exists( '_get_cron_array' ) ) {
				$cronjobs = _get_cron_array();
			} else {
				$cronjobs = get_option( 'cron' );
			}

			$events = array();

			foreach ( $cronjobs as $time => $cron ) {
				foreach ( $cron as $hook => $tasks ) {
					foreach ( $tasks as $md5key => $data ) {
						$events[ "$hook-$md5key-$time" ] = (object) array(
							'hook'     => $hook,
							'time'     => $time,
							'sig'      => $md5key,
							'args'     => $data['args'],
							'schedule' => $data['schedule'],
							'interval' => isset( $data['interval'] ) ? $data['interval'] : null,
						);
					}
				}
			}

			return $events;
		}

		/**
		 * Get the actions of events.
		 *
		 * @param string $name The name of the event.
		 *
		 * @uses PS_Live_Debug_Cronjob_Info::populate_actions
		 *
		 * @return array $actions The scheduled events.
		 */
		public static function get_actions( $name ) {
			global $wp_filter;

			$actions = array();

			if ( isset( $wp_filter[ $name ] ) ) {
				$action = $wp_filter[ $name ];

				foreach ( $action as $priority => $callbacks ) {
					foreach ( $callbacks as $callback ) {
						$callback = PS_Live_Debug_Cronjob_Info::populate_actions( $callback );

						$actions[] = array(
							'priority' => $priority,
							'callback' => $callback,
						);
					}
				}
			}

			return $actions;
		}

		/**
		 * Populate the actions of events.
		 *
		 * @param array $actions The actions of the event.
		 *
		 * @uses PS_Live_Debug_Cronjob_Info::populate_actions
		 *
		 * @return array $actions The actions.
		 */
		public static function populate_actions( $actions ) {
			if ( is_string( $actions['function'] ) && ( false !== strpos( $actions['function'], '::' ) ) ) {
				$actions['function'] = explode( '::', $actions['function'] );
			}

			if ( is_array( $actions['function'] ) ) {
				if ( is_object( $actions['function'][0] ) ) {
					$class  = get_class( $actions['function'][0] );
					$access = '->';
				} else {
					$class  = $actions['function'][0];
					$access = '::';
				}

				$actions['name'] = $class . $access . $actions['function'][1] . '()';
			} elseif ( is_object( $actions['function'] ) ) {
				if ( is_a( $actions['function'], 'Closure' ) ) {
					$actions['name'] = 'Closure';
				} else {
					$class           = get_class( $actions['function'] );
					$actions['name'] = $class . '->__invoke()';
				}
			} else {
				$actions['name'] = $actions['function'] . '()';
			}

			return $actions;
		}
	}
}
