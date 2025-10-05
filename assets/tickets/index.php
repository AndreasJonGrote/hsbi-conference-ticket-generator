<?php
// Set timezone to Berlin
date_default_timezone_set('Europe/Berlin');

// Load tickets data
$ticketsFile = __DIR__ . '/tickets.json';
$tickets = [];

if (file_exists($ticketsFile)) {
    $tickets = json_decode(file_get_contents($ticketsFile), true) ?: [];
}

// Sort by creation date (newest first)
usort($tickets, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Show all tickets
$totalTickets = count($tickets);
?>
<!DOCTYPE html>
<html lang="de">
<head>
<style>
.hsbi-italic {
	font-style: italic;
}
</style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Übersicht</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .ticket-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(600px, 1fr));
            gap: 20px;
        }
        .ticket-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .ticket-info h3 {
            margin: 0 0 5px 0;
            color: #333;
        }
        .ticket-info p {
            margin: 2px 0;
            color: #666;
            font-size: 0.9rem;
        }
        .ticket-date {
            color: #999;
            font-size: 0.8rem;
        }
        .images-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .image-section {
            text-align: center;
        }
        .image-section h4 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 1rem;
        }
        .image-section img {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .muted { color:#666; }
        .no-tickets {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
        }
        @media (max-width: 768px) {
            .ticket-grid {
                grid-template-columns: 1fr;
            }
            .images-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Ticket Übersicht (<?php echo $totalTickets; ?> Tickets)</h1>
        
        <?php if (empty($tickets)): ?>
            <div class="no-tickets">
                <p>Noch keine Tickets erstellt.</p>
            </div>
        <?php else: ?>
            <div class="ticket-grid">
                <?php foreach ($tickets as $ticket): ?>
                    <div class="ticket-card">
                        <div class="ticket-header">
                            <div class="ticket-info">
                                <h3><?php echo htmlspecialchars($ticket['name']); ?></h3>
                                <p><strong>E-Mail:</strong> <?php echo htmlspecialchars($ticket['email']); ?></p>
                                <?php if (!empty($ticket['organization'])): ?>
                                    <p><strong>Organisation:</strong> <?php echo htmlspecialchars($ticket['organization']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="ticket-date">
                                <?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?>
                            </div>
                        </div>
                        
                        <div class="images-container">
                            <div class="image-section">
                                <h4>Ticket</h4>
                                <?php if (isset($ticket['ticket_file']) && is_string($ticket['ticket_file']) && file_exists(__DIR__ . '/' . $ticket['ticket_file'])): ?>
                                    <img src="<?php echo htmlspecialchars($ticket['ticket_file']); ?>" 
                                         alt="Ticket für <?php echo htmlspecialchars($ticket['name']); ?>">
                                <?php else: ?>
                                    <p class="muted hsbi-italic">Bild nicht gefunden</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="image-section">
                                <h4>Canvas</h4>
                                <?php if (isset($ticket['pattern_file']) && is_string($ticket['pattern_file']) && file_exists(__DIR__ . '/' . $ticket['pattern_file'])): ?>
                                    <img src="<?php echo htmlspecialchars($ticket['pattern_file']); ?>" 
                                         alt="Canvas für <?php echo htmlspecialchars($ticket['name']); ?>">
                                <?php else: ?>
                                    <p class="muted hsbi-italic">Bild nicht gefunden</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
