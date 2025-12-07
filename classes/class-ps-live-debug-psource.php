<?php //phpcs:ignore

// Check that the file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Es tut uns leid, aber Du kannst nicht direkt auf diese Datei zugreifen.' );
}

/**
 * PS_Live_Debug_Server_Info Class.
 */
if ( ! class_exists( 'PS_Live_Debug_PSOURCE' ) ) {
	class PS_Live_Debug_PSOURCE {

		/**
		 * PS_Live_Debug_PSOURCE constructor.
		 *
		 * @uses PS_Live_Debug_PSOURCE::init()
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
			add_action( 'wp_ajax_ps-live-debug-gather-snapshot-constants', array( 'PS_Live_Debug_PSOURCE', 'gather_snapshot_info' ) );
			//add_action( 'wp_ajax_ps-live-debug-gather-shipper-constants', array( 'PS_Live_Debug_PSOURCE', 'gather_shipper_info' ) );
			//add_action( 'wp_ajax_ps-live-debug-gather-dashboard-constants', array( 'PS_Live_Debug_PSOURCE', 'gather_dashboard_info' ) );
		}

		/**
		 * Create the Psource page.
		 *
		 * @uses esc_html__()
		 *
		 * @return string html The html of the page viewed.
		 */
		public static function create_page() {
			?>
				<div class="sui-box">
					<div class="sui-box-body">
						<div class="sui-tabs">
							<div data-tabs>
								<div><?php esc_html_e( 'Snapshot', 'ps-live-debug' ); ?></div>
							</div>
							<div data-panes>
								<div id="psource-snapshot-info" class="active">
									<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php
		}

		/**
		 * Gather Snapshot plugin information.
		 *
		 * @uses PS_Live_Debug_Helper::format_constant()
		 * @uses PS_Live_Debug_Helper::table_psource_constants()
		 * @uses PS_Live_Debug_Helper::table_psource_actions_filters()
		 * @uses wp_send_json_success()
		 *
		 * @return string json success with the response.
		 */
		public static function gather_snapshot_info() {
			$defines = array(
				array(
					'SNAPSHOT_ATTEMPT_SYSTEM_BACKUP',
					'FALSE',
					'Backups: Nur verwaltete Sicherungen. Erwartet true oder false. Wenn diese Option auf „true“ gesetzt ist, versuchen verwaltete Sicherungen, Systembinärdateien für die Sicherung zu verwenden. Dies sollte im Allgemeinen viel schneller sein (oder die für ein verwaltetes Backup benötigte Zeit sogar exponentiell verringern) als normale verwaltete Backups über PHP, erfordert jedoch: Serverunterstützung zum Ausführen von Binärdateien von PHP (PHP-Funktionen escapeshellarg, escapeshellcmd und exec sind vorhanden und verfügbar), und Vorhandensein der erwarteten Binärdateien, die für die Sicherung benötigt werden (zip, ln, rm, mysqdump und optional find). Wenn diese Option aktiv ist und die aufgeführten Voraussetzungen nicht erfüllt sind, fahren wir mit der standardmäßigen verwalteten Backup-Erstellung über PHP fort und protokollieren eine Warnung mit folgendem Inhalt: „Angeforderte Systemsicherung kann nicht durchgeführt werden".',
				),
				array(
					'SNAPSHOT_BACKTRACE_ALL',
					'FALSE',
					'Backup: Nur verwaltete Sicherungen. Erwartet true oder false. Wenn auf „true“ gesetzt, erzwingt diese Definition die Protokollierung aller Protokollaufrufe für verwaltete Sicherungen, unabhängig von ihrer Ebene (sehr ausführliche Protokolldatei)..',
				),
				array(
					'SNAPSHOT_CHANGED_ADMIN_URL',
					'EMPTY STRING',
					'Backup: Nur verwaltete Sicherungen. Dies ist zu verwenden, wenn der Benutzer seinen Admin über .htaccess verschoben hat (z. B. wurde dies ursprünglich gemeldet, als der Benutzer das Plugin WP Hide & Security Enhancer dafür verwendet hat). Wenn der Administrator über .htaccess verschoben wird, funktionieren geplante verwaltete Sicherungen nicht. Erwartet die URL des verschobenen Administrators. Wenn der neue Administrator beispielsweise http://leomilo.com/dashboard ist, sollte diese Definition wie folgt lauten: define( \'SNAPSHOT_CHANGED_ADMIN_URL\', \'http://leomilo.com/dashboard\' );',
				),
				array(
					'SNAPSHOT_FILESET_CHUNK_SIZE',
					'250',
					'Backup: Nur verwaltete Sicherungen. Dies ist die Anzahl der Dateien, die für jeden Schritt der Backup-Erstellung verarbeitet werden. Hinweis: Diese Einstellung hat keine Auswirkung, wenn die Sicherung über Systembinärdateien erfolgt (d. h. wenn SNAPSHOT_ATTEMPT_SYSTEM_BACKUP in Kraft ist, v3.1.5 und höher)..',
				),
				array(
					'SNAPSHOT_FILESET_LARGE_FILE_SIZE',
					'1073741824',
					'Backup: Nur verwaltete Sicherungen. Bei der Verarbeitung der Dateien für die Backup-Erstellung wird jede Datei nach ihrer Größe abgefragt. Wenn die Größe einer verarbeiteten Datei diesen Schwellenwert überschreitet, protokollieren wir eine Warnung mit folgendem Inhalt: "Verarbeitung einer großen Datei: --Dateiname-- (--Dateigröße--)". Standardmäßig ändert das Ändern dieses Werts nur die Größe, die zum Protokollieren dieser Warnung verwendet wird. Es ist auch möglich, übergroße Dateien mit einem Codeausschnitt wie diesem automatisch abzulehnen: add_filter( \'snapshot-queue-fileset-reject_oversized\', \'__return_false\' );',
				),
				array(
					'SNAPSHOT_FILESET_USE_PRECACHE',
					'FALSE',
					'Backup: Nur verwaltete Sicherungen. Standardmäßig wird die Liste der Dateien zu Beginn jedes Dateiverarbeitungs-Sicherungsschritts gescannt. Wenn diese Einstellung aktiviert ist, erfolgt dies nur einmal und die Liste wird zwischengespeichert. Jeder nachfolgende Schritt arbeitet diesen Cache ab.',
				),
				array(
					'SNAPSHOT_FORCE_ZIP_LIBRARY',
					'ARCHIVE',
					'Backup: Snapshots und verwaltete Sicherungen. Erwartet "archive" oder "pclzip". Diese Option erzwingt die Auswahl der internen ZIP-Bibliothek, die zum Erstellen von Sicherungen verwendet wird.',
				),
				array(
					'SNAPSHOT_IGNORE_SYMLINKS',
					'FALSE',
					'Backup: Nur verwaltete Sicherungen. Ein Symlink – oder symbolischer Link – ist eine spezielle Datei, die eigentlich ein Verweis auf eine andere Datei oder einen anderen Ordner ist. Mit Symlinks können Sie Ihre Plugins und Themes in einem separaten Ordner aufbewahren und – mit Symlinks – auf jede Ihrer Installationen verweisen. Jede Installation verwendet die gleichen Dateien, was Änderungen und Wartung zum Kinderspiel macht. Erwartet true oder false. Wenn diese Option gesetzt ist, erzwingt diese Option, dass verwaltete Sicherungen keinen symbolischen Links folgen. Die symbolisch verknüpften Dateien werden nicht in die endgültige Sicherung eingeschlossen.',
				),
				array(
					'SNAPSHOT_MB_BREADTH_FIRST',
					'FALSE',
					'Backups: Nur verwaltete Sicherungen. true oder false. Wenn auf „true“ gesetzt, versuchen verwaltete Sicherungen, unsere neue Engine zu verwenden, um Dateien zu verarbeiten (nur Dateien – Datenbanken sind davon nicht betroffen). Wenn dies aktiv ist, verarbeitet die Engine dies auch mit dem bei SNAPSHOT_FILESET_CHUNK_SIZE festgelegten Wert. Wenn es also immer noch Probleme mit den Dateien gibt, kannst Du so etwas wie define( \'SNAPSHOT_FILESET_CHUNK_SIZE\', 100 ); define( \'SNAPSHOT_MB_BREADTH_FIRST\', true ); in die wp-config.php eintragen',
				),
				array(
					'SNAPSHOT_NO_SYSTEM_BACKUP',
					'FALSE',
					'Backup: Nur verwaltete Sicherungen. Erwartet true oder false. Wenn auf „true“ gesetzt, zwingt diese Definition das Snapshot-Plugin dazu, nicht zu versuchen, Systembinärdateien für verwaltete Backups zu verwenden. In der Tat kehrt SNAPSHOT_ATTEMPT_SYSTEM_BACKUP definieren um. Eingeschränkte Nutzbarkeit.',
				),
				array(
					'SNAPSHOT_SESSION_PROTECT_DATA',
					'FALSE',
					'Backup: Nur verwaltete Sicherungen. Erwartet true oder false. Wenn auf true gesetzt, erzwingt diese Definition die Verschlüsselung der Sitzungsdaten. Dieses Verhalten kann in Kombination mit der Option SNAPSHOT_FILESET_USE_PRECACHE nützlich sein. Es fügt auch jedem einzelnen Sicherungsschritt einen gewissen Verarbeitungsaufwand hinzu. Eingeschränkte Nutzbarkeit.',
				),
				array(
					'SNAPSHOT_SYSTEM_DEBUG_OUTPUT',
					'FALSE',
					'Backup: Nur verwaltete Sicherungen. Erwartet true oder false. Nur wirksam, wenn SNAPSHOT_ATTEMPT_SYSTEM_BACKUP aktiviert ist. Wenn diese Option auf „true“ gesetzt ist, protokolliert sie jeden tatsächlichen Befehl, der zur Ausführung an die Systembinärdateien übergeben wird. Diese Option kann beim Debuggen von auf Systembinärdateien basierenden verwalteten Sicherungen nützlich sein – dies ist auch ein gewisses Sicherheitsrisiko, da es Dinge wie Datenbankkennwörter im Klartext in den Protokolldateien offenlegt. Sei vorsichtig.',
				),
				array(
					'SNAPSHOT_SYSTEM_ZIP_ONLY',
					'FALSE',
					'Backup: Nur verwaltete Sicherungen. Erwartet true oder false. Bei der Verarbeitung von Dateien mit aktiviertem SNAPSHOT_ATTEMPT_SYSTEM_BACKUP verwenden wir standardmäßig die „find“-Binärdatei, um zuerst alle Dateien zu finden (falls die Find-Binärdatei verfügbar ist) und die Ausgabe an zip weiterzuleiten. Dies kann verhindert werden, indem diese Definition auf wahr gesetzt wird, in diesem Fall wird die Find-Binärdatei überhaupt nicht verwendet. In diesem Szenario verwenden wir nur die zip-Binärdatei und -x-Flags, um Ausschlüsse zu verarbeiten. Dasselbe passiert, wenn keine Find-Binärdatei verfügbar ist. Bei der Verwendung von find schließen wir standardmäßig auch große Dateien automatisch aus dem Archiv aus – es sei denn, der Größen-Getter für übergroße Dateien gibt 0 zurück (kann per Filter optimiert werden), in diesem Fall werden wir sie einschließen.',
				),
				array(
					'SNAPSHOT_TABLESET_CHUNK_SIZE',
					'1000',
					'Backup: Nur verwaltete Sicherungen. Dies ist die Anzahl der Tabellenzeilen, die für jeden Schritt der Sicherungserstellung verarbeitet werden. Die Größe kann als Anzahl der zu sichernden Zeilen pro Tabelle und Anforderung definiert werden. Dies steuert die Sicherungsverarbeitung, wenn Sie einen neuen Snapshot erstellen. Während des Sicherungsvorgangs fordert Snapshot den Server auf, jede Tabelle zu sichern. Sie können dies in den Fortschrittsanzeigen sehen, wenn Sie einen neuen Snapshot erstellen. In den meisten Situationen versucht dieser Sicherungsvorgang, die Tabelle in einem Schritt zu sichern. Bei einigen Serverkonfigurationen ist das Timeout jedoch sehr niedrig eingestellt oder die Tabellengröße ist sehr groß und verhindert, dass der Sicherungsvorgang abgeschlossen wird. Um dies zu kontrollieren, teilt der Snapshot-Backup-Prozess die Anforderungen in kleinere „Arbeitsblöcke“ auf, die an den Server gesendet werden. Angenommen, Sie haben eine Tabelle mit 80.000 Datensätzen. Dies würde mehr als die normalen 3 Minuten oder weniger dauern, die die meisten Server für die Verarbeitung einer einzelnen Anfrage zulassen. Indem Sie die Segmentgröße auf 1000 setzen, zerlegt der Snapshot-Prozess die Tabelle in 80 kleine Teile. Diese 1000 Datensätze pro Anforderung sollten innerhalb des zulässigen Serverzeitlimits abgeschlossen werden. Hinweis: Diese Einstellung hat keine Auswirkung, wenn die Sicherung über Systembinärdateien erfolgt (d. h. wenn SNAPSHOT_ATTEMPT_SYSTEM_BACKUP in Kraft ist)..',
				),
				array(
					'PSOURCE_SNAPSHOT_DESTINATIONS_EXCLUDE',
					'EMPTY STRING',
					'Backup: Nur Snapshot-Backups. Erwartet eine durch Kommas getrennte Zeichenfolge von auszuschließenden Zielen. Mögliche Ziele sind: „SnapshotDestinationFTP“, „SnapshotDestinationGoogleDrive“, „Snapshot_Model_Destination_AWS“, SnapshotDestinationDropbox“. Wenn eine nicht leere Zeichenfolge festgelegt ist, wird die Unterstützung für übereinstimmende Ziele nicht einmal geladen.',
				),
			);

			foreach ( $defines as $key => $define ) {
				$constants[ $key ][0] = $define[0];
				$constants[ $key ][1] = $define[1];
				$constants[ $key ][2] = PS_Live_Debug_Helper::format_constant( $define[0] );
				$constants[ $key ][3] = $define[2];
			}

			$output = PS_Live_Debug_Helper::table_psource_constants( $constants );

			$actions_filters = array(
				'actions' => array(
					'snapshot-full_backups-restore-tables',
					'snapshot-destinations-render_list-before',
					'snapshot_destinations_loaded',
					'snapshot_register_destination',
					'snapshot-config-loaded',
					'snapshot_class_loader_pre_processing',
					'Snapshot_Controller_Full_Ajax::get_filter( \'ajax-error-stop\' );',
					'Snapshot_Controller_Full_Cron::get_filter( \'cron-error-stop\' );',
				),
				'filters' => array(
					'snapshot_home_path',
					'snapshot_current_path',
					'snapshot-queue-tableset-full',
					'snapshot-queue-fileset-preprocess',
					'snapshot-queue-fileset-reject_oversized',
					'snapshot-queue-fileset-filesize_threshold',
					'snapshot-mocks-api_response-code',
					'snapshot-mocks-api_response-body',
					'snapshot-full_backups-log_enabled',
					'snapshot-full_backups-log_enabled-explicit',
					'snapshot-full_backups-log_enabled-implicit',
					'snapshot_limit_of_files_per_session',
					'Snapshot_Model_Full_Remote_Storage::get_filter( \'api-space-used\' );',
					'Snapshot_Model_Full_Remote_Storage::get_filter( \'api-space-total\' );',
					'Snapshot_Model_Full_Remote_Storage::get_filter( \'api-space-free\' );',
					'Snapshot_Model_Full_Remote_Storage::get_filter( \'backups-get\' );',
					'Snapshot_Model_Full_Remote_Storage::get_filter( \'backups-refresh\' );',
					'Snapshot_Model_Full_Remote_Storage::get_filter( \'cache_expiration\' );',
					'Snapshot_Model_Full_Backup::get_filter( \'get_backups\' );',
					'Snapshot_Model_Full_Backup::get_filter( \'has_dashboard\' );',
					'Snapshot_Model_Full_Backup::get_filter( \'is_dashboard_active\' );',
					'Snapshot_Model_Full_Backup::get_filter( \'has_dashboard_key\' );',
					'Snapshot_Model_Full_Backup::get_filter( \'is_active\' );',
					'Snapshot_Model_Full_Backup::get_filter( \'schedule_frequencies\' );',
					'Snapshot_Model_Full_Backup::get_filter( \'schedule_frequency\' );',
					'Snapshot_Model_Full_Backup::get_filter( \'schedule_times\' );',
					'Snapshot_Model_Full_Backup::get_filter( \'schedule_time\' );',
					'Snapshot_Model_Full_Backup::get_filter( \'has_backups\' );',
					'Snapshot_Model_Full_Remote_Api::get_filter( \'domain\' );',
					'Snapshot_Model_Full_Remote_Api::get_filter( \'api_key\' );',
					'Snapshot_Model_Full_Remote_Help::get_filter( \'help_url\' );',
					'Snapshot_Model_Time::get_filter( \'local_timestamp\' );',
					'Snapshot_Model_Time::get_filter( \'utc_timestamp\' );',
					'Snapshot_Controller_Full_Cron::get_filter( \'kickstart-delay\' );',
					'Snapshot_Controller_Full_Cron::get_filter( \'next_backup_start\' );',
					'Snapshot_Controller_Full_Cron::get_filter( \'backup-kickstart\' );',
				),
			);

			$output .= PS_Live_Debug_Helper::table_psource_actions_filters( $actions_filters );

			$response = array(
				'message' => $output,
			);

			wp_send_json_success( $response );
		}
	}
}
