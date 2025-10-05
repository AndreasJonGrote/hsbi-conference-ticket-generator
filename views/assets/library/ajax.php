<?php

    /**
     * WordPress-Integration für HSBI Ticket Generator
     */

    // WordPress laden
    $wp_load_path = '../../../../wp-load.php';
    if (!file_exists($wp_load_path)) {
        $wp_load_path = '../../../../../wp-load.php';
    }
    if (!file_exists($wp_load_path)) {
        $wp_load_path = '../../../../../../wp-load.php';
    }

    if (!file_exists($wp_load_path)) {
        echo json_encode(['error' => 'WordPress not found at any path']);
        exit;
    }

    // WordPress mit Fehlerbehandlung laden
    try {
        require_once($wp_load_path);
    } catch (Exception $e) {
        echo json_encode(['error' => 'WordPress load error: ' . $e->getMessage()]);
        exit;
    }

    // Sicherheitscheck
    if (!defined('ABSPATH')) {
        http_response_code(403);
        echo json_encode(['error' => 'WordPress not loaded - ABSPATH not defined']);
        exit;
    }

    date_default_timezone_set('Europe/Berlin');

    // Session sicher starten
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type');

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getSessionData') {
        try {
            $sessionData = $_SESSION['ticketFormData'] ?? null;
            
            echo json_encode([
                'success' => true,
                'data' => $sessionData
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Session error: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE || !$input) {
        http_response_code(400);
        echo json_encode(['error' => json_last_error() !== JSON_ERROR_NONE ? 
            sprintf('Invalid JSON: %s', json_last_error_msg()) : 'No data received']);
        exit;
    }

    $required = ['name', 'email', 'ticketBase64', 'patternBase64'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => sprintf('Missing field: %s', $field)]);
            exit;
        }
    }

    # honeypot
    if (isset($input['salutation']) && !empty($input['salutation'])) {
        // Honeypot erkannt - als Erfolg ausgeben um Bots zu täuschen
        echo json_encode([
            'success' => true,
            'uid' => 'hp_' . time(),
            'message' => 'Ticket created successfully'
        ]);
        exit;
    }

    $uid = md5($input['name'] . $input['email'] . time() . rand());
    $ticketData = [
        'uid' => $uid,
        'name' => $input['name'],
        'email' => $input['email'],
        'organization' => $input['organization'] ?? '',
        'created_at' => date('Y-m-d H:i:s'),
        'pattern_file' => "{$uid}_pattern.png",
        'ticket_file' => "{$uid}_ticket.png"
    ];

    function saveBase64Image($base64Data, $filename) {
        $base64Data = preg_replace('/^data:image\/png;base64,/', '', $base64Data);
        $imageData = base64_decode($base64Data);
        
        if ($imageData === false) {
            throw new Exception(sprintf(
                'Invalid base64 data for %s',
                $filename
            ));
        }
        
        $tickets_dir = __DIR__ . '/../tickets/';
        
        // Tickets-Ordner erstellen falls nicht vorhanden
        if (!file_exists($tickets_dir)) {
            if (!mkdir($tickets_dir, 0755, true)) {
                throw new Exception(sprintf(
                    'Failed to create tickets directory: %s',
                    $tickets_dir
                ));
            }
        }
        
        // Ordner-Berechtigung prüfen
        if (!is_writable($tickets_dir)) {
            throw new Exception(sprintf(
                'Tickets directory is not writable: %s',
                $tickets_dir
            ));
        }
        
        $filepath = $tickets_dir . $filename;
        $result = file_put_contents($filepath, $imageData);
        
        if ($result === false) {
            throw new Exception(sprintf(
                'Failed to save image file: %s',
                $filepath
            ));
        }
        
        return $filepath;
    }

    try {
        $patternFile = saveBase64Image($input['patternBase64'], "{$uid}_pattern.png");
        $ticketFile = saveBase64Image($input['ticketBase64'], "{$uid}_ticket.png");
        
        if (!file_exists($patternFile)) {
            throw new Exception('Pattern file does not exist: ' . $patternFile);
        }
        if (!file_exists($ticketFile)) {
            throw new Exception('Ticket file does not exist: ' . $ticketFile);
        }
        
        // WordPress-Datenbank-Integration
        global $wpdb;
        $table_name = $wpdb->prefix . 'hsbi_tickets';
        
        $ticketData['pattern_file'] = basename($patternFile);
        $ticketData['ticket_file'] = basename($ticketFile);
        
        // In Datenbank speichern
        $result = $wpdb->insert(
            $table_name,
            array(
                'uid' => $ticketData['uid'],
                'name' => $ticketData['name'],
                'email' => $ticketData['email'],
                'organization' => $ticketData['organization'],
                'created_at' => $ticketData['created_at'],
                'pattern_file' => $ticketData['pattern_file'],
                'ticket_file' => $ticketData['ticket_file'],
                'optin' => 0
            ),
            array(
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d'
            )
        );
        
         if ($result === false) {
             throw new Exception('Failed to save ticket to database: ' . $wpdb->last_error);
         }

         // E-Mail mit Opt-in-Link versenden
         // Ticket-Seiten-URL aus JavaScript-Parameter holen
         $ticket_page_url = isset($input['ticketPageUrl']) ? $input['ticketPageUrl'] : home_url();
         $optin_link = $ticket_page_url . '?uid=' . $uid;
         $email_subject = 'Ihr HSBI Conference Ticket - Opt-in erforderlich';
         
         // E-Mail-Text aus Settings-Tabelle laden
         global $wpdb;
         $settings_table = $wpdb->prefix . 'hsbi_settings';
         $email_template = $wpdb->get_var($wpdb->prepare(
             "SELECT setting_value FROM {$settings_table} WHERE setting_key = %s",
             'email_text'
         ));
         
         if (!$email_template) {
             $email_template = "Hello {name},\n\nThank you for your interest in the Postphotographic Images Conference at HSBI!\n\nYour personal registration link has been created successfully.\nTo complete your registration and confirm your participation, please click the link below:\n\n{optin_link}\n\nOnly after confirming your email address will your registration be finalized, and your personal conference ticket will be sent to you by email.\n\nWe're looking forward to welcoming you to the conference!\n\nBest regards,\nYour HSBI Team";
         }
         
         $email_message = str_replace(
             array('{name}', '{optin_link}'),
             array($input['name'], $optin_link),
             $email_template
         );

         $email_sent = wp_mail(
             $input['email'],
             $email_subject,
             $email_message,
             array('Content-Type: text/plain; charset=UTF-8')
         );

         if (!$email_sent) {
             error_log('Failed to send opt-in email to: ' . $input['email']);
         }

         $_SESSION['ticketFormData'] = [
             'name' => $input['name'],
             'email' => $input['email'],
             'organization' => $input['organization'] ?? ''
         ];

         // JSON ausgeben
         echo json_encode([
             'success' => true,
             'uid' => $uid,
             'message' => 'Ticket created successfully'
         ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => sprintf('Server error: %s', $e->getMessage())
        ]);
    }
    
?>