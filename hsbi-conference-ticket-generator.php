<?php
/**
 * Plugin Name: HSBI Conference Ticket Generator
 * Plugin URI: https://www.hsbi.de
 * Description: Generiert personalisierte Tickets für die "Postphotographic Images Conference"
 * Version: 1.0.0
 * Author: HSBI
 * License: GPL v2 or later
 */

// Sicherheitscheck
if (!defined('ABSPATH')) {
	exit;
}

// Plugin-Konstanten
define('HSBI_TICKET_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HSBI_TICKET_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('HSBI_TICKET_VERSION', '1.0.0');

/**
 * Haupt-Plugin-Klasse
 */
class HSBI_Conference_Ticket_Generator {
	
	public function __construct() {
		add_action('init', array($this, 'init'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_shortcode('hsbi_ticket_generator', array($this, 'render_shortcode'));
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('wp_ajax_delete_hsbi_ticket', array($this, 'delete_ticket'));
		add_action('wp_ajax_delete_all_hsbi_tickets', array($this, 'delete_all_tickets'));
		add_action('wp_ajax_delete_non_optin_hsbi_tickets', array($this, 'delete_non_optin_tickets'));
		add_action('wp_ajax_export_hsbi_tickets', array($this, 'export_tickets'));
		add_action('wp_ajax_export_hsbi_tickets_csv', array($this, 'export_tickets_csv'));
		add_action('wp_ajax_export_hsbi_tickets_images', array($this, 'export_tickets_images'));
		register_activation_hook(__FILE__, array($this, 'activate'));
		register_deactivation_hook(__FILE__, array($this, 'deactivate'));
		register_uninstall_hook(__FILE__, array('HSBI_Conference_Ticket_Generator', 'uninstall'));
	}
	
	/**
	 * Plugin initialisieren
	 */
	public function init() {
		// Plugin-Initialisierung
	}
	
	/**
	 * Plugin aktivieren - Datenbank-Tabelle erstellen
	 */
	public function activate() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'hsbi_tickets';
		$settings_table = $wpdb->prefix . 'hsbi_settings';
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`uid` varchar(32) NOT NULL,
			`name` varchar(255) NOT NULL,
			`email` varchar(255) NOT NULL,
			`organization` varchar(255) DEFAULT '',
			`created_at` datetime NOT NULL,
			`pattern_file` varchar(255) NOT NULL,
			`ticket_file` varchar(255) NOT NULL,
			`optin` tinyint(1) DEFAULT 0,
			PRIMARY KEY (`id`),
			UNIQUE KEY `uid` (`uid`),
			KEY `email` (`email`),
			KEY `created_at` (`created_at`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
		
		$sql_settings = "CREATE TABLE IF NOT EXISTS `{$settings_table}` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`setting_key` varchar(255) NOT NULL,
			`setting_value` longtext,
			`created_at` datetime NOT NULL,
			`updated_at` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`),
			UNIQUE KEY `setting_key` (`setting_key`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		dbDelta($sql_settings);
		
		// Standard-Settings einfügen
		$this->insert_default_settings();
		
		// Version speichern
		update_option('hsbi_ticket_db_version', HSBI_TICKET_VERSION);
	}
	
	/**
	 * Standard-Settings in die Settings-Tabelle einfügen
	 */
	private function insert_default_settings() {
		global $wpdb;
		$settings_table = $wpdb->prefix . 'hsbi_settings';
		
		$default_settings = array(
			'thank_you_text' => "Thank you for registering for the Postphotographic Images Conference at HSBI!\nWe've sent you an email with a confirmation link – please check your inbox to complete your registration.",
			'email_text' => "Hello {name},\n\nThank you for your interest in the Postphotographic Images Conference at HSBI!\n\nYour personal registration link has been created successfully.\nTo complete your registration and confirm your participation, please click the link below:\n\n{optin_link}\n\nOnce you've confirmed your email address, your registration will be finalized and your personal conference ticket will be sent to you by email.\n\nWe look forward to welcoming you to the conference!\n\nBest regards,\nYour HSBI Team",
			'optin_confirmation_text' => "Hello {name},\n\nThank you for confirming your registration!\n\nYour participation in the Postphotographic Images Conference at HSBI has been successfully confirmed.\nPlease find your personal conference ticket attached to this email.\n\nWe look forward to seeing you at the conference!\n\nBest regards,\nYour HSBI Team",
			'registration_complete_text' => "Thank you for registering for the Postphotographic Images Conference at HSBI!\nYour registration has been successfully completed.\nYou will receive your personal ticket and further information by email shortly."
		);
		
		foreach ($default_settings as $key => $value) {
			// Prüfen ob Setting bereits existiert
			$existing = $wpdb->get_var($wpdb->prepare(
				"SELECT id FROM {$settings_table} WHERE setting_key = %s",
				$key
			));
			
			if (!$existing) {
				$wpdb->insert(
					$settings_table,
					array(
						'setting_key' => $key,
						'setting_value' => $value,
						'created_at' => current_time('mysql')
					),
					array('%s', '%s', '%s')
				);
			}
		}
	}
	
	/**
	 * Setting aus der Datenbank laden
	 */
	private function get_setting($key, $default = '') {
		global $wpdb;
		$settings_table = $wpdb->prefix . 'hsbi_settings';
		
		$value = $wpdb->get_var($wpdb->prepare(
			"SELECT setting_value FROM {$settings_table} WHERE setting_key = %s",
			$key
		));
		
		return $value !== null ? $value : $default;
	}
	
	/**
	 * Setting in der Datenbank speichern
	 */
	private function save_setting($key, $value) {
		global $wpdb;
		$settings_table = $wpdb->prefix . 'hsbi_settings';
		
		// Prüfen ob Setting bereits existiert
		$existing = $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM {$settings_table} WHERE setting_key = %s",
			$key
		));
		
		if ($existing) {
			// Update
			$wpdb->update(
				$settings_table,
				array('setting_value' => $value),
				array('setting_key' => $key),
				array('%s'),
				array('%s')
			);
		} else {
			// Insert
			$wpdb->insert(
				$settings_table,
				array(
					'setting_key' => $key,
					'setting_value' => $value,
					'created_at' => current_time('mysql')
				),
				array('%s', '%s', '%s')
			);
		}
	}
	
	/**
	 * Plugin deaktivieren
	 */
	public function deactivate() {
		// Cleanup bei Deaktivierung - Tabellen bleiben erhalten
	}
	
	/**
	 * Plugin deinstallieren - Tabellen löschen
	 */
	public function uninstall() {
		global $wpdb;
		
		// Tabellen löschen
		$tickets_table = $wpdb->prefix . 'hsbi_tickets';
		$settings_table = $wpdb->prefix . 'hsbi_settings';
		
		$wpdb->query("DROP TABLE IF EXISTS {$tickets_table}");
		$wpdb->query("DROP TABLE IF EXISTS {$settings_table}");
		
		// WordPress-Optionen löschen
		delete_option('hsbi_ticket_db_version');
	}
	
	/**
	 * Scripts und Styles einbinden
	 */
	public function enqueue_scripts() {
		wp_enqueue_style(
			'hsbi-ticket-style',
			HSBI_TICKET_PLUGIN_URL . 'views/assets/css/main.css',
			array(),
			HSBI_TICKET_VERSION
		);
		
		wp_enqueue_script(
			'hsbi-ticket-script',
			HSBI_TICKET_PLUGIN_URL . 'views/assets/js/main.js',
			array(),
			HSBI_TICKET_VERSION,
			true
		);
		
		// Plugin-URL als JavaScript-Variable hinzufügen
		wp_localize_script('hsbi-ticket-script', 'hsbiTicket', array(
			'pluginUrl' => HSBI_TICKET_PLUGIN_URL,
			'ajaxUrl' => HSBI_TICKET_PLUGIN_URL . 'views/assets/library/ajax.php',
			'ticketPageUrl' => get_permalink() // Aktuelle Seite URL für Opt-in-Link
		));
	}
	
	/**
	 * Admin-Menü hinzufügen
	 */
	public function add_admin_menu() {
		add_menu_page(
			'HSBI Tickets',
			'HSBI Tickets',
			'edit_posts', // Editor und höher
			'hsbi-tickets',
			array($this, 'admin_page'),
			'dashicons-tickets-alt',
			30
		);
		
		// Submenu für Einstellungen
		add_submenu_page(
			'hsbi-tickets',
			'Einstellungen',
			'Einstellungen',
			'edit_posts', // Editor und höher
			'hsbi-settings',
			array($this, 'settings_page')
		);

        // Submenu für CSV-Download
		add_submenu_page(
			'hsbi-tickets',
			'CSV-Download',
			'CSV-Download',
			'manage_options', // Nur Administrator
			'hsbi-csv-download',
			array($this, 'csv_download_page')
		);

        // Submenu für Shortcode-Info
		add_submenu_page(
			'hsbi-tickets',
			'Informationen',
			'Informationen',
			'edit_posts', // Editor und höher
			'hsbi-shortcode-info',
			array($this, 'shortcode_info_page')
		);
	}
	
	/**
	 * Admin-Seite rendern
	 */
	public function admin_page() {
		include HSBI_TICKET_PLUGIN_PATH . 'views/assets/tickets/index.php';
	}
	
	/**
	 * Alle Tickets löschen (Admin)
	 */
	public function delete_all_tickets() {
		// Nonce prüfen
		if (!wp_verify_nonce($_POST['nonce'], 'delete_all_hsbi_tickets')) {
			wp_die('Sicherheitsfehler');
		}
		
		// Admin-Berechtigung prüfen
		if (!current_user_can('manage_options')) {
			wp_die('Keine Berechtigung');
		}
		
		global $wpdb;
		$table_name = $wpdb->prefix . 'hsbi_tickets';
		
		// Alle Tickets aus Datenbank holen
		$tickets = $wpdb->get_results("SELECT * FROM {$table_name}", ARRAY_A);
		
		// Dateien löschen
		$tickets_dir = HSBI_TICKET_PLUGIN_PATH . 'views/assets/tickets/';
		foreach ($tickets as $ticket) {
			$pattern_file = $tickets_dir . $ticket['pattern_file'];
			$ticket_file = $tickets_dir . $ticket['ticket_file'];
			
			if (file_exists($pattern_file)) {
				unlink($pattern_file);
			}
			if (file_exists($ticket_file)) {
				unlink($ticket_file);
			}
		}
		
		// Alle Tickets aus Datenbank löschen
		$deleted = $wpdb->query("DELETE FROM {$table_name}");
		
		wp_send_json_success(array(
			'message' => "Alle {$deleted} Tickets wurden erfolgreich gelöscht."
		));
	}
	
	/**
	 * Nur Nicht-Opt-in-Tickets löschen (Admin)
	 */
	public function delete_non_optin_tickets() {
		// Nonce prüfen
		if (!wp_verify_nonce($_POST['nonce'], 'delete_non_optin_hsbi_tickets')) {
			wp_die('Sicherheitsfehler');
		}
		
		// Admin-Berechtigung prüfen
		if (!current_user_can('manage_options')) {
			wp_die('Keine Berechtigung');
		}
		
		global $wpdb;
		$table_name = $wpdb->prefix . 'hsbi_tickets';
		
		// Nur Nicht-Opt-in-Tickets aus Datenbank holen
		$tickets = $wpdb->get_results("SELECT * FROM {$table_name} WHERE optin = 0", ARRAY_A);
		
		// Dateien löschen
		$tickets_dir = HSBI_TICKET_PLUGIN_PATH . 'views/assets/tickets/';
		foreach ($tickets as $ticket) {
			$pattern_file = $tickets_dir . $ticket['pattern_file'];
			$ticket_file = $tickets_dir . $ticket['ticket_file'];
			
			if (file_exists($pattern_file)) {
				unlink($pattern_file);
			}
			if (file_exists($ticket_file)) {
				unlink($ticket_file);
			}
		}
		
		// Nur Nicht-Opt-in-Tickets aus Datenbank löschen
		$deleted = $wpdb->query("DELETE FROM {$table_name} WHERE optin = 0");
		
		wp_send_json_success(array(
			'message' => "Alle {$deleted} Nicht-Opt-in-Tickets wurden erfolgreich gelöscht."
		));
	}
	
	/**
	 * Ticket löschen
	 */
	public function delete_ticket() {
		// Nonce prüfen
		if (!wp_verify_nonce($_POST['nonce'], 'delete_hsbi_ticket')) {
			wp_die('Sicherheitsfehler');
		}
		
		// Admin-Berechtigung prüfen
		if (!current_user_can('manage_options')) {
			wp_die('Keine Berechtigung');
		}
		
		$ticket_id = intval($_POST['ticket_id']);
		
		global $wpdb;
		$table_name = $wpdb->prefix . 'hsbi_tickets';
		
		// Ticket-Daten abrufen
		$ticket = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $ticket_id), ARRAY_A);
		
		if (!$ticket) {
			wp_send_json_error('Ticket nicht gefunden');
		}
		
		// Bild-Dateien löschen
		$tickets_dir = HSBI_TICKET_PLUGIN_PATH . 'views/assets/tickets/';
		$ticket_file = $tickets_dir . $ticket['ticket_file'];
		$pattern_file = $tickets_dir . $ticket['pattern_file'];
		
		if (file_exists($ticket_file)) {
			unlink($ticket_file);
		}
		if (file_exists($pattern_file)) {
			unlink($pattern_file);
		}
		
		// Ticket aus Datenbank löschen
		$result = $wpdb->delete($table_name, array('id' => $ticket_id), array('%d'));
		
		if ($result) {
			wp_send_json_success('Ticket erfolgreich gelöscht');
		} else {
			wp_send_json_error('Fehler beim Löschen aus der Datenbank');
		}
	}
	
	/**
	 * Tickets exportieren
	 */
	public function export_tickets() {
		// Nonce prüfen
		if (!wp_verify_nonce($_POST['nonce'], 'export_hsbi_tickets')) {
			wp_die('Sicherheitsfehler');
		}
		
		// Admin-Berechtigung prüfen
		if (!current_user_can('manage_options')) {
			wp_die('Keine Berechtigung');
		}
		
		global $wpdb;
		$table_name = $wpdb->prefix . 'hsbi_tickets';
		
		// Nur Opt-in-Tickets laden (optin = 1)
		$tickets = $wpdb->get_results("SELECT * FROM {$table_name} WHERE optin = 1 ORDER BY created_at DESC", ARRAY_A);
		
		// CSV erstellen
		$csv_content = "ID,Name,Email,Organization,Opt-in,Created At,Pattern File,Ticket File\n";
		foreach ($tickets as $ticket) {
			$csv_content .= sprintf(
				"%d,%s,%s,%s,%s,%s,%s,%s\n",
				$ticket['id'],
				'"' . str_replace('"', '""', $ticket['name']) . '"',
				'"' . str_replace('"', '""', $ticket['email']) . '"',
				'"' . str_replace('"', '""', $ticket['organization']) . '"',
				$ticket['optin'] ? 'Ja' : 'Nein',
				$ticket['created_at'],
				$ticket['pattern_file'],
				$ticket['ticket_file']
			);
		}
		
		// ZIP erstellen
		$zip = new ZipArchive();
		$zip_filename = sys_get_temp_dir() . '/hsbi-optin-tickets-export-' . date('Y-m-d-H-i-s') . '.zip';
		
		if ($zip->open($zip_filename, ZipArchive::CREATE) !== TRUE) {
			wp_die('Fehler beim Erstellen der ZIP-Datei');
		}
		
		// CSV zur ZIP hinzufügen
		$zip->addFromString('tickets.csv', $csv_content);
		
		// Bilder zur ZIP hinzufügen
		$tickets_dir = HSBI_TICKET_PLUGIN_PATH . 'views/assets/tickets/';
		foreach ($tickets as $ticket) {
			$pattern_file = $tickets_dir . $ticket['pattern_file'];
			$ticket_file = $tickets_dir . $ticket['ticket_file'];
			
			if (file_exists($pattern_file)) {
				$zip->addFile($pattern_file, 'images/' . $ticket['pattern_file']);
			}
			if (file_exists($ticket_file)) {
				$zip->addFile($ticket_file, 'images/' . $ticket['ticket_file']);
			}
		}
		
		$zip->close();
		
		// ZIP-Datei herunterladen
		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename="hsbi-optin-tickets-export-' . date('Y-m-d') . '.zip"');
		header('Content-Length: ' . filesize($zip_filename));
		readfile($zip_filename);
		
		// Temporäre Datei löschen
		unlink($zip_filename);
		exit;
	}
	
	/**
	 * Nur CSV exportieren (nur Opt-in-Daten)
	 */
	public function export_tickets_csv() {
		if (!wp_verify_nonce($_POST['nonce'], 'export_hsbi_tickets_csv')) {
			wp_die('Sicherheitsfehler');
		}
		if (!current_user_can('manage_options')) {
			wp_die('Keine Berechtigung');
		}
		
		global $wpdb;
		$table_name = $wpdb->prefix . 'hsbi_tickets';
		// Nur Opt-in-Tickets exportieren (optin = 1)
		$tickets = $wpdb->get_results("SELECT * FROM {$table_name} WHERE optin = 1 ORDER BY created_at DESC", ARRAY_A);
		
		$csv_content = "ID,Name,Email,Organization,Opt-in,Created At,Pattern File,Ticket File\n";
		foreach ($tickets as $ticket) {
			$csv_content .= sprintf(
				"%d,%s,%s,%s,%s,%s,%s,%s\n",
				$ticket['id'],
				'"' . str_replace('"', '""', $ticket['name']) . '"',
				'"' . str_replace('"', '""', $ticket['email']) . '"',
				'"' . str_replace('"', '""', $ticket['organization']) . '"',
				$ticket['optin'] ? 'Ja' : 'Nein',
				$ticket['created_at'],
				$ticket['pattern_file'],
				$ticket['ticket_file']
			);
		}
		
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="hsbi-optin-tickets-' . date('Y-m-d') . '.csv"');
		echo $csv_content;
		exit;
	}
	
	/**
	 * Nur Bilder exportieren (nur Opt-in-Daten)
	 */
	public function export_tickets_images() {
		if (!wp_verify_nonce($_POST['nonce'], 'export_hsbi_tickets_images')) {
			wp_die('Sicherheitsfehler');
		}
		if (!current_user_can('manage_options')) {
			wp_die('Keine Berechtigung');
		}
		
		global $wpdb;
		$table_name = $wpdb->prefix . 'hsbi_tickets';
		// Nur Opt-in-Tickets exportieren (optin = 1)
		$tickets = $wpdb->get_results("SELECT * FROM {$table_name} WHERE optin = 1 ORDER BY created_at DESC", ARRAY_A);
		
		$zip = new ZipArchive();
		$zip_filename = sys_get_temp_dir() . '/hsbi-optin-tickets-images-' . date('Y-m-d-H-i-s') . '.zip';
		
		if ($zip->open($zip_filename, ZipArchive::CREATE) !== TRUE) {
			wp_die('Fehler beim Erstellen der ZIP-Datei');
		}
		
		$tickets_dir = HSBI_TICKET_PLUGIN_PATH . 'views/assets/tickets/';
		foreach ($tickets as $ticket) {
			$pattern_file = $tickets_dir . $ticket['pattern_file'];
			$ticket_file = $tickets_dir . $ticket['ticket_file'];
			
			if (file_exists($pattern_file)) {
				$zip->addFile($pattern_file, 'images/' . $ticket['pattern_file']);
			}
			if (file_exists($ticket_file)) {
				$zip->addFile($ticket_file, 'images/' . $ticket['ticket_file']);
			}
		}
		
		$zip->close();
		
		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename="hsbi-optin-tickets-images-' . date('Y-m-d') . '.zip"');
		header('Content-Length: ' . filesize($zip_filename));
		readfile($zip_filename);
		
		unlink($zip_filename);
		exit;
	}
	
	
	
	/**
	 * E-Mail-Einstellungsseite
	 */
	public function settings_page() {
		// Formular verarbeiten
		if (isset($_POST['submit'])) {
			$thank_you_text = wp_kses_post($_POST['thank_you_text']);
			$email_text = sanitize_textarea_field($_POST['email_text']);
			$optin_confirmation_text = sanitize_textarea_field($_POST['optin_confirmation_text']);
			
			$this->save_setting('thank_you_text', $thank_you_text);
			$this->save_setting('email_text', $email_text);
			$this->save_setting('optin_confirmation_text', $optin_confirmation_text);
			
			$registration_complete_text = sanitize_textarea_field($_POST['registration_complete_text']);
			$this->save_setting('registration_complete_text', $registration_complete_text);
			
			echo '<div class="notice notice-success"><p>Einstellungen gespeichert!</p></div>';
		}
		
		$current_thank_you_text = $this->get_setting('thank_you_text', 
			"We've just sent you an email – please check your inbox to confirm your registration."
		);
		
		$current_email_text = $this->get_setting('email_text', 
			"Hello {name},\n\nThank you for your interest in the Postphotographic Images Conference at HSBI!\n\nYour personal registration link has been created successfully.\nTo complete your registration and confirm your participation, please click the link below:\n\n{optin_link}\n\nOnly after confirming your email address will your registration be finalized, and your personal conference ticket will be sent to you by email.\n\nWe're looking forward to welcoming you to the conference!\n\nBest regards,\nYour HSBI Team"
		);
		
		$current_optin_confirmation_text = $this->get_setting('optin_confirmation_text', 
			"Hello {name},\n\nThank you for your confirmation!\n\nYour registration for the Postphotographic Images Conference at HSBI has been successfully confirmed.\nYour personal conference ticket is attached to this email.\n\nBest regards,\nYour HSBI Team"
		);
		
		$current_registration_complete_text = $this->get_setting('registration_complete_text', 
			"Thank you! We've sent you an email with a confirmation link.\nPlease check your inbox to complete your registration for the Postphotographic Images Conference."
		);
		
		?>
		<div class="wrap">
			<h1>E-Mail Einstellungen</h1>
			
			<form method="post" action="">
				<h2>Bestätigungstext</h2>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="thank_you_text">Danke-Seite</label>
						</th>
						<td>
							<?php
							wp_editor(
								$current_thank_you_text,
								'thank_you_text',
								array(
									'textarea_name' => 'thank_you_text',
									'media_buttons' => false,
									'textarea_rows' => 8,
									'teeny' => true,
									'tinymce' => array(
										'toolbar1' => 'bold,italic,underline,link,unlink,undo,redo',
										'toolbar2' => ''
									)
								)
							);
							?>
							<p class="description">
								Text für die Danke-Seite nach der Registrierung.<br>
								<a href="#" onclick="insertThankYouTemplate(); return false;">Vorlage einfügen</a>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="registration_complete_text">Registrierung Abgeschlossen</label>
						</th>
						<td>
							<?php
							wp_editor(
								$current_registration_complete_text,
								'registration_complete_text',
								array(
									'textarea_name' => 'registration_complete_text',
									'media_buttons' => false,
									'textarea_rows' => 8,
									'teeny' => true,
									'tinymce' => array(
										'toolbar1' => 'bold,italic,underline,link,unlink,undo,redo',
										'toolbar2' => ''
									)
								)
							);
							?>
							<p class="description">
								Text für die Registrierung-Abgeschlossen-Seite.<br>
								<a href="#" onclick="insertRegistrationCompleteTemplate(); return false;">Vorlage einfügen</a>
							</p>
						</td>
					</tr>
				</table>
				
				<h2>E-Mail-Texte</h2>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="email_text">Optin E-Mail</label>
						</th>
						<td>
							<?php
							wp_editor(
								$current_email_text,
								'email_text',
								array(
									'textarea_name' => 'email_text',
									'media_buttons' => false,
									'textarea_rows' => 8,
									'teeny' => true,
									'tinymce' => array(
										'toolbar1' => 'bold,italic,underline,link,unlink,undo,redo',
										'toolbar2' => ''
									)
								)
							);
							?>
							<p class="description">
								E-Mail mit Opt-in-Link.<br>
								<strong>Platzhalter:</strong> {name} = Name, {optin_link} = Opt-in-Link<br>
								<a href="#" onclick="insertEmailTemplate(); return false;">Vorlage einfügen</a>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="optin_confirmation_text">Abschluss E-Mail</label>
						</th>
						<td>
							<?php
							wp_editor(
								$current_optin_confirmation_text,
								'optin_confirmation_text',
								array(
									'textarea_name' => 'optin_confirmation_text',
									'media_buttons' => false,
									'textarea_rows' => 8,
									'teeny' => true,
									'tinymce' => array(
										'toolbar1' => 'bold,italic,underline,link,unlink,undo,redo',
										'toolbar2' => ''
									)
								)
							);
							?>
							<p class="description">
								Bestätigungs-E-Mail mit Ticket-Anhang.<br>
								<strong>Platzhalter:</strong> {name} = Name<br>
								<a href="#" onclick="insertOptinConfirmationTemplate(); return false;">Vorlage einfügen</a>
							</p>
						</td>
					</tr>
				</table>
				
				<?php submit_button('Einstellungen speichern'); ?>
			</form>
		</div>
		
		<script>
		function insertEmailTemplate() {
			const template = `Hello {name},

Thank you for your interest in the Postphotographic Images Conference at HSBI!

Your personal registration link has been created successfully.
To complete your registration and confirm your participation, please click the link below:

{optin_link}

Once you've confirmed your email address, your registration will be finalized and your personal conference ticket will be sent to you by email.

We look forward to welcoming you to the conference!

Best regards,
Your HSBI Team`;
			
			// WordPress-Editor verwenden
			if (typeof tinyMCE !== 'undefined' && tinyMCE.get('email_text')) {
				tinyMCE.get('email_text').setContent(template);
			} else {
				// Fallback für Textarea
				document.getElementById('email_text').value = template;
			}
		}
		
		function insertThankYouTemplate() {
			const template = `Thank you for registering for the Postphotographic Images Conference at HSBI!
We've sent you an email with a confirmation link – please check your inbox to complete your registration.`;
			
			// WordPress-Editor verwenden
			if (typeof tinyMCE !== 'undefined' && tinyMCE.get('thank_you_text')) {
				tinyMCE.get('thank_you_text').setContent(template);
			} else {
				// Fallback für Textarea
				document.getElementById('thank_you_text').value = template;
			}
		}
		
		function insertOptinConfirmationTemplate() {
			const template = `Hello {name},

Thank you for confirming your registration!

Your participation in the Postphotographic Images Conference at HSBI has been successfully confirmed.
Please find your personal conference ticket attached to this email.

We look forward to seeing you at the conference!

Best regards,
Your HSBI Team`;
			
			// WordPress-Editor verwenden
			if (typeof tinyMCE !== 'undefined' && tinyMCE.get('optin_confirmation_text')) {
				tinyMCE.get('optin_confirmation_text').setContent(template);
			} else {
				// Fallback für Textarea
				document.getElementById('optin_confirmation_text').value = template;
			}
		}
		
		function insertRegistrationCompleteTemplate() {
			const template = `Thank you for registering for the Postphotographic Images Conference at HSBI!
Your registration has been successfully completed.
You will receive your personal ticket and further information by email shortly.`;
			
			// WordPress-Editor verwenden
			if (typeof tinyMCE !== 'undefined' && tinyMCE.get('registration_complete_text')) {
				tinyMCE.get('registration_complete_text').setContent(template);
			} else {
				// Fallback für Textarea
				document.getElementById('registration_complete_text').value = template;
			}
		}
		</script>
		<?php
	}
	
	/**
	 * Shortcode-Info Seite
	 */
	public function shortcode_info_page() {
		?>
		<div class="wrap">
			<h1>Shortcode-Verwendung</h1>
			
			<div class="card">
				<h2>Grundlegender Shortcode</h2>
				<p>Verwenden Sie den folgenden Shortcode, um den Ticket-Generator auf Ihrer Website anzuzeigen:</p>
				<code>[hsbi_ticket_generator]</code>
			</div>
			
			<div class="card">
				<h2>Mit benutzerdefiniertem Titel</h2>
				<p>Sie können einen benutzerdefinierten Titel für den Generator festlegen:</p>
				<code>[hsbi_ticket_generator title="Mein Ticket Generator"]</code>
			</div>
			
			<div class="card">
				<h2>Verwendung in Beiträgen und Seiten</h2>
				<p>Der Shortcode kann in jedem WordPress-Beitrag oder jeder Seite verwendet werden. Einfach den Shortcode in den Text-Editor einfügen.</p>
			</div>
			
			<div class="card">
				<h2>Informationen</h2>
				<h3>Benutzerrechte und Berechtigungen</h3>
				
				<h4>Administrator (manage_options)</h4>
				<ul>
					<li><strong>Vollzugriff:</strong> Alle Funktionen verfügbar</li>
					<li><strong>Export-Funktionen:</strong> CSV, Bilder, kombinierter Export</li>
					<li><strong>Einstellungen:</strong> E-Mail-Templates konfigurieren</li>
					<li><strong>Ticket-Verwaltung:</strong> Ansehen, löschen, exportieren</li>
				</ul>
				
				<h4>Editor (edit_posts)</h4>
				<ul>
					<li><strong>Ticket-Übersicht:</strong> Alle Tickets einsehen</li>
					<li><strong>Einstellungen:</strong> E-Mail-Templates anpassen</li>
					<li><strong>Shortcode-Info:</strong> Verwendungsanleitung</li>
					<li><strong>Ticket ansehen:</strong> Modal mit Ticket-Details</li>
					<li><strong>Kein Export:</strong> Keine Download-Funktionen</li>
				</ul>
				
				<h4>Autor und niedriger</h4>
				<ul>
					<li><strong>Kein Zugriff:</strong> Plugin-Admin-Bereich nicht verfügbar</li>
					<li><strong>Frontend:</strong> Shortcode funktioniert normal</li>
				</ul>
			</div>
			
			<?php if (current_user_can('manage_options')): ?>
			<div class="card" style="margin-bottom: 20px; padding: 15px; background: #f0f0f1; border-left: 4px solid #d63638;">
				<h3 style="margin-top: 0; color: #d63638;">Admin-Funktionen</h3>
				<p><strong>Vorsicht:</strong> Diese Aktionen können nicht rückgängig gemacht werden!</p>
				<div style="margin-top: 10px;">
					<button class="button button-secondary" onclick="deleteAllTickets()" style="margin-right: 10px;">
						ALLE Tickets löschen
					</button>
					<button class="button button-secondary" onclick="deleteNonOptinTickets()">
						Nur Nicht-Opt-in löschen
					</button>
				</div>
			</div>
			<?php endif; ?>
		</div>
		
		<?php if (current_user_can('manage_options')): ?>
		<script>
		function deleteAllTickets() {
			if (!confirm('WARNUNG: Möchten Sie ALLE Tickets wirklich löschen?\n\nDiese Aktion kann nicht rückgängig gemacht werden!')) {
				return;
			}
			
			// AJAX-Request für Massenlöschung
			const formData = new FormData();
			formData.append('action', 'delete_all_hsbi_tickets');
			formData.append('nonce', '<?php echo wp_create_nonce('delete_all_hsbi_tickets'); ?>');
			
			fetch(ajaxurl, {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					alert('Alle Tickets wurden erfolgreich gelöscht.');
					location.reload();
				} else {
					alert('Fehler beim Löschen: ' + data.data);
				}
			})
			.catch(error => {
				alert('Fehler beim Löschen: ' + error);
			});
		}

		function deleteNonOptinTickets() {
			if (!confirm('Möchten Sie alle Nicht-Opt-in-Tickets wirklich löschen?\n\nDiese Aktion kann nicht rückgängig gemacht werden!')) {
				return;
			}
			
			// AJAX-Request für Nicht-Opt-in-Löschung
			const formData = new FormData();
			formData.append('action', 'delete_non_optin_hsbi_tickets');
			formData.append('nonce', '<?php echo wp_create_nonce('delete_non_optin_hsbi_tickets'); ?>');
			
			fetch(ajaxurl, {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					alert('Alle Nicht-Opt-in-Tickets wurden erfolgreich gelöscht.');
					location.reload();
				} else {
					alert('Fehler beim Löschen: ' + data.data);
				}
			})
			.catch(error => {
				alert('Fehler beim Löschen: ' + error);
			});
		}
		</script>
		<?php endif; ?>
		<?php
	}
	
	/**
	 * CSV-Download Seite
	 */
	public function csv_download_page() {
		?>
		<div class="wrap">
			<h1>CSV-Download</h1>
			
			<div class="card">
				<h2>Download-Optionen</h2>
				<p>Wählen Sie aus, welche Daten Sie exportieren möchten:</p>
				
				<div style="margin: 20px 0;">
					<button class="button button-primary" onclick="downloadCSV()" style="margin-right: 10px;">
						Nur CSV-Datei
					</button>
					<button class="button button-primary" onclick="downloadImages()" style="margin-right: 10px;">
						Nur Bilder (ZIP)
					</button>
					<button class="button button-primary" onclick="downloadAll()">
						CSV + Bilder (ZIP)
					</button>
				</div>
			</div>
			
			<div class="card">
				<h2>CSV-Format</h2>
				<p>Die CSV-Datei enthält folgende Spalten:</p>
				<ul>
					<li><strong>ID:</strong> Eindeutige Ticket-ID</li>
					<li><strong>Name:</strong> Name des Teilnehmers</li>
					<li><strong>Email:</strong> E-Mail-Adresse</li>
					<li><strong>Organization:</strong> Organisation</li>
					<li><strong>Opt-in:</strong> Opt-in-Status (Ja/Nein)</li>
					<li><strong>Created At:</strong> Erstellungsdatum</li>
					<li><strong>Pattern File:</strong> Pattern-Bilddatei</li>
					<li><strong>Ticket File:</strong> Ticket-Bilddatei</li>
				</ul>
			</div>
		</div>
		
		<script>
		function downloadCSV() {
			if (!confirm('Möchten Sie nur die CSV-Datei herunterladen?')) {
				return;
			}
			
			fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: 'action=export_hsbi_tickets_csv&nonce=<?php echo wp_create_nonce('export_hsbi_tickets_csv'); ?>'
			})
			.then(response => response.blob())
			.then(blob => {
				const url = window.URL.createObjectURL(blob);
				const a = document.createElement('a');
				a.href = url;
				a.download = 'hsbi-tickets-' + new Date().toISOString().split('T')[0] + '.csv';
				document.body.appendChild(a);
				a.click();
				window.URL.revokeObjectURL(url);
				document.body.removeChild(a);
			})
			.catch(error => {
				console.error('Error:', error);
				alert('Fehler beim Download der CSV-Datei');
			});
		}
		
		function downloadImages() {
			if (!confirm('Möchten Sie nur die Bilder als ZIP herunterladen?')) {
				return;
			}
			
			fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: 'action=export_hsbi_tickets_images&nonce=<?php echo wp_create_nonce('export_hsbi_tickets_images'); ?>'
			})
			.then(response => response.blob())
			.then(blob => {
				const url = window.URL.createObjectURL(blob);
				const a = document.createElement('a');
				a.href = url;
				a.download = 'hsbi-tickets-images-' + new Date().toISOString().split('T')[0] + '.zip';
				document.body.appendChild(a);
				a.click();
				window.URL.revokeObjectURL(url);
				document.body.removeChild(a);
			})
			.catch(error => {
				console.error('Error:', error);
				alert('Fehler beim Download der Bilder');
			});
		}
		
		function downloadAll() {
			if (!confirm('Möchten Sie CSV + Bilder als ZIP herunterladen?')) {
				return;
			}
			
			fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: 'action=export_hsbi_tickets&nonce=<?php echo wp_create_nonce('export_hsbi_tickets'); ?>'
			})
			.then(response => response.blob())
			.then(blob => {
				const url = window.URL.createObjectURL(blob);
				const a = document.createElement('a');
				a.href = url;
				a.download = 'hsbi-tickets-export-' + new Date().toISOString().split('T')[0] + '.zip';
				document.body.appendChild(a);
				a.click();
				window.URL.revokeObjectURL(url);
				document.body.removeChild(a);
			})
			.catch(error => {
				console.error('Error:', error);
				alert('Fehler beim Download');
			});
		}
		</script>
		<?php
	}
	
	/**
	 * Shortcode rendern
	 */
	public function render_shortcode($atts) {
		$atts = shortcode_atts(array(
			'title' => 'Conference Ticket Generator'
		), $atts);
		
		ob_start();
		
		// Opt-in-Anzeige prüfen
		if (isset($_GET['uid']) && !empty($_GET['uid'])) {
			$uid = sanitize_text_field($_GET['uid']);
			
			// Ticket-Daten aus Datenbank holen
			global $wpdb;
			$table_name = $wpdb->prefix . 'hsbi_tickets';
			$ticket = $wpdb->get_row($wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE uid = %s",
				$uid
			), ARRAY_A);
			
			if ($ticket) {
				// Opt-in Status auf 1 setzen
				$wpdb->update(
					$table_name,
					array('optin' => 1),
					array('uid' => $uid),
					array('%d'),
					array('%s')
				);
				
				// Bestätigungs-E-Mail mit Ticket-Anhang versenden
				$confirmation_subject = 'HSBI Conference - Anmeldung bestätigt';
				$confirmation_template = $this->get_setting('optin_confirmation_text', 
					"Hello {name},\n\nThank you for your confirmation!\n\nYour registration for the Postphotographic Images Conference at HSBI has been successfully confirmed.\nYour personal conference ticket is attached to this email.\n\nBest regards,\nYour HSBI Team"
				);
				
				$confirmation_message = str_replace(
					'{name}',
					$ticket['name'],
					$confirmation_template
				);
				
				// Ticket-Datei als Anhang vorbereiten
				$tickets_dir = HSBI_TICKET_PLUGIN_PATH . 'views/assets/tickets/';
				$ticket_file_path = $tickets_dir . $ticket['ticket_file'];
				
				$attachments = array();
				if (file_exists($ticket_file_path)) {
					// Temporäre Datei mit benutzerfreundlichem Namen erstellen
					$temp_dir = sys_get_temp_dir();
					$clean_name = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $ticket['name']);
					$clean_name = str_replace(' ', '_', $clean_name);
					$new_filename = $clean_name . '_Postphotographic_Images_Conference_HSBI.png';
					$temp_file_path = $temp_dir . '/' . $new_filename;
					
					// Original-Datei in temporäre Datei mit neuem Namen kopieren
					if (copy($ticket_file_path, $temp_file_path)) {
						$attachments[] = $temp_file_path;
					}
				}
				
				wp_mail(
					$ticket['email'],
					$confirmation_subject,
					$confirmation_message,
					array('Content-Type: text/plain; charset=UTF-8'),
					$attachments
				);
				
				// Temporäre Datei nach dem Versand löschen
				if (isset($temp_file_path) && file_exists($temp_file_path)) {
					unlink($temp_file_path);
				}
				
				// Danke-Anzeige - nur Text ohne Layout
				$thank_you_text = $this->get_setting('thank_you_text', 
					'Thank you for registering for the Postphotographic Images Conference at HSBI!<br>You will receive your personal conference ticket and further information by email shortly.'
				);
				
				// WordPress-Content verarbeiten (unterstützt HTML)
				echo wp_kses_post($thank_you_text);
				
				// Nach der Danke-Anzeige beenden
				return ob_get_clean();
			} else {
				echo '<div class="hsbi-error-message" style="max-width: 600px; margin: 20px auto; padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; text-align: center;">';
				echo '<h2 style="color: #721c24; margin-bottom: 15px;">Fehler</h2>';
				echo '<p style="font-size: 16px; line-height: 1.6; color: #721c24;">Ticket nicht gefunden. Bitte kontaktieren Sie den Administrator.</p>';
				echo '</div>';
			}
		}
		
		// Erfolgs-Anzeige prüfen (für andere Erfolgs-Szenarien)
		if (isset($_GET['hsbi_ticket_success']) && $_GET['hsbi_ticket_success'] == '1') {
			$name = isset($_GET['name']) ? sanitize_text_field($_GET['name']) : '';
			$thank_you_text = $this->get_setting('thank_you_text', 
				'Thank you for registering for the Postphotographic Images Conference at HSBI!<br>You will receive your personal conference ticket and further information by email shortly.'
			);
			
			echo '<div class="hsbi-success-message" style="max-width: 600px; margin: 20px auto; padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; text-align: center;">';
			echo '<h2 style="color: #155724; margin-bottom: 15px;">Vielen Dank' . ($name ? ', ' . esc_html($name) : '') . '!</h2>';
			echo '<p style="font-size: 16px; line-height: 1.6; color: #155724; margin-bottom: 20px;">' . esc_html($thank_you_text) . '</p>';
			echo '<p style="font-size: 14px; color: #6c757d;">Sie können diese Seite jetzt schließen oder ein weiteres Ticket erstellen.</p>';
			echo '</div>';
		}
		
		include HSBI_TICKET_PLUGIN_PATH . 'views/index.php';
		return ob_get_clean();
	}
}

// Plugin initialisieren
new HSBI_Conference_Ticket_Generator();
