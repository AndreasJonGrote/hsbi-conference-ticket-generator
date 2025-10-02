<?php
    // Set timezone to Berlin
    date_default_timezone_set('Europe/Berlin');

    // Start session for data persistence
    session_start();

    // Disable error display to prevent HTML output
    error_reporting(0);
    ini_set('display_errors', 0);

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type');

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getSessionData') {
        // Return session data for auto-fill
        $sessionData = $_SESSION['ticketFormData'] ?? null;
        echo json_encode([
            'success' => true,
            'data' => $sessionData
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
        exit;
    }

    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'No data received']);
        exit;
    }

    // Normalize and validate fields
    $name = isset($input['name']) ? trim($input['name']) : '';
    $email = isset($input['email']) ? trim($input['email']) : '';
    $organization = isset($input['organization']) ? trim($input['organization']) : '';
    $ticketBase64 = isset($input['ticketBase64']) ? $input['ticketBase64'] : '';
    $patternBase64 = isset($input['patternBase64']) ? $input['patternBase64'] : '';

    if ($name === '' || mb_strlen($name) < 2) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid name']);
        exit;
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email']);
        exit;
    }
    if ($ticketBase64 === '' || $patternBase64 === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Missing image data']);
        exit;
    }

    // Generate unique ID
    $uid = md5($name . $email . time() . rand());

    // Prepare ticket data
    $ticketData = [
        'uid' => $uid,
        'name' => $name,
        'email' => $email,
        'organization' => $organization,
        'created_at' => date('Y-m-d H:i:s'),
        'pattern_file' => "{$uid}_pattern.png",
        'ticket_file' => "{$uid}_ticket.png"
    ];

    // Save base64 images as PNG files
    function saveBase64Image($base64Data, $filename) {
        // Remove data:image/png;base64, prefix
        $base64Data = preg_replace('/^data:image\/png;base64,/', '', $base64Data);
        $imageData = base64_decode($base64Data);
        
        if ($imageData === false) {
            throw new Exception('Invalid base64 data for ' . $filename);
        }
        
        $filepath = __DIR__ . '/../tickets/' . $filename;
        $result = file_put_contents($filepath, $imageData);
        
        if ($result === false) {
            throw new Exception('Failed to save image file: ' . $filepath);
        }
        
        error_log("Successfully saved: " . $filepath . " (" . $result . " bytes)");
        return $filepath;
    }

    try {
        // Debug logging
        error_log("Processing ticket for: " . $name . " (" . $email . ")");
        error_log("Pattern base64 length: " . strlen($patternBase64));
        error_log("Ticket base64 length: " . strlen($ticketBase64));
        
        // Save pattern image (pure canvas pattern)
        $patternFile = saveBase64Image($patternBase64, "{$uid}_pattern.png");
        
        // Save ticket image
        $ticketFile = saveBase64Image($ticketBase64, "{$uid}_ticket.png");
        
        // Verify files exist before saving to JSON
        if (!file_exists($patternFile) || !file_exists($ticketFile)) {
            throw new Exception('Failed to verify saved files exist');
        }
        
        // Load existing tickets
        $ticketsFile = __DIR__ . '/../tickets/tickets.json';
        $tickets = [];
        
        if (file_exists($ticketsFile)) {
            $tickets = json_decode(file_get_contents($ticketsFile), true) ?: [];
        }
        
        // Add new ticket with verified file paths
        $ticketData['pattern_file'] = basename($patternFile);
        $ticketData['ticket_file'] = basename($ticketFile);
        $tickets[] = $ticketData;
        
        // Save updated tickets with file lock
        file_put_contents($ticketsFile, json_encode($tickets, JSON_PRETTY_PRINT), LOCK_EX);
        
        // Save form data to session for auto-fill
        $_SESSION['ticketFormData'] = [
            'name' => $name,
            'email' => $email,
            'organization' => $organization
        ];
        
        // Send success response
        echo json_encode([
            'success' => true,
            'uid' => $uid,
            'message' => 'Ticket created successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Server error: ' . $e->getMessage()
        ]);
    }
?>
