<?php
/**
 * WordPress-Integration für HSBI Ticket Generator Admin
 */

// WordPress ist bereits geladen (wird über Plugin aufgerufen)

// Sicherheitscheck
if (!defined('ABSPATH')) {
	http_response_code(403);
	die('Direct access not allowed');
}

// Admin-Berechtigung prüfen
if (!current_user_can('edit_posts')) {
	wp_die('Sie haben keine Berechtigung für diese Seite.');
}

// WordPress-Datenbank-Integration
global $wpdb;
$table_name = $wpdb->prefix . 'hsbi_tickets';

// Filter-Parameter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$optin_filter = "";
if ($filter === 'optin') {
    $optin_filter = "WHERE optin = 1";
} elseif ($filter === 'nooptin') {
    $optin_filter = "WHERE optin = 0";
} elseif ($filter === 'invalid') {
    $optin_filter = "WHERE optin = 0"; // Fehlerhafte Tickets werden separat geladen
}

// Tickets aus Datenbank laden
if ($filter === 'invalid') {
	// Alle Tickets laden und fehlerhafte identifizieren
	$all_tickets = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY created_at DESC", ARRAY_A);
	$invalid_tickets = array();
	
	foreach ($all_tickets as $ticket) {
		$pattern_image_path = __DIR__ . '/' . $ticket['pattern_file'];
		$ticket_image_path = __DIR__ . '/' . $ticket['ticket_file'];
		
		if (!file_exists($pattern_image_path) || !file_exists($ticket_image_path)) {
			$invalid_tickets[] = $ticket;
		}
	}
	$tickets = $invalid_tickets;
} else {
	$tickets = $wpdb->get_results(
		"SELECT * FROM {$table_name} {$optin_filter} ORDER BY created_at DESC",
		ARRAY_A
	);
	
	// Nur Tickets mit gültigen Bildern anzeigen (außer bei fehlerhaften)
	$validTickets = array();
	foreach ($tickets as $ticket) {
		$pattern_image_path = __DIR__ . '/' . $ticket['pattern_file'];
		$ticket_image_path = __DIR__ . '/' . $ticket['ticket_file'];
		
		// Prüfen ob beide Bilder existieren
		if (file_exists($pattern_image_path) && file_exists($ticket_image_path)) {
			$validTickets[] = $ticket;
		}
	}
	$tickets = $validTickets;
}

$totalTickets = count($tickets);

/**
 * Anonymisierungs-Funktion für nicht-Opt-in-Daten
 */
function anonymize_data($name, $email, $optin) {
	if ($optin == 1) {
		// Opt-in: Original-Daten anzeigen
		return array('name' => $name, 'email' => $email);
	}
	
	// Nicht Opt-in: Daten anonymisieren
	// Name: Wörter trennen und Buchstaben shuffeln
	$name_parts = explode(' ', $name);
	$anonymized_name_parts = array();
	foreach ($name_parts as $part) {
		// Original-Groß-/Kleinschreibung merken
		$original_case = array();
		for ($i = 0; $i < strlen($part); $i++) {
			$original_case[] = ctype_upper($part[$i]);
		}
		
		// Zufällige Buchstaben a-z generieren (nicht aus bestehenden)
		$anonymized_part = '';
		for ($i = 0; $i < strlen($part); $i++) {
			if (ctype_alpha($part[$i])) {
				// Zufälligen Buchstaben a-z generieren
				$random_char = chr(rand(97, 122)); // a-z
				
				// Original-Groß-/Kleinschreibung anwenden
				if ($original_case[$i]) {
					$anonymized_part .= strtoupper($random_char);
				} else {
					$anonymized_part .= $random_char;
				}
			} else {
				// Nicht-Buchstaben beibehalten
				$anonymized_part .= $part[$i];
			}
		}
		
		$anonymized_name_parts[] = $anonymized_part;
	}
	$anonymized_name = implode(' ', $anonymized_name_parts);
	
	// E-Mail: Struktur erhalten, zufällige Buchstaben a-z verwenden
	$anonymized_email = '';
	$in_domain = false;
	$after_dot = false;
	
	for ($i = 0; $i < strlen($email); $i++) {
		$char = $email[$i];
		
		if ($char === '@') {
			$anonymized_email .= '@';
			$in_domain = true;
			$after_dot = false;
		} elseif ($char === '.') {
			$anonymized_email .= '.';
			$after_dot = true;
		} elseif (ctype_alpha($char)) {
			// Zufälligen Buchstaben a-z generieren
			$random_char = chr(rand(97, 122)); // a-z
			$anonymized_email .= $random_char;
		} else {
			// Zahlen und andere Zeichen beibehalten
			$anonymized_email .= $char;
		}
	}
	
	return array('name' => $anonymized_name, 'email' => $anonymized_email);
}

// Alle Tickets aus DB laden für korrekte Zählung
$all_tickets_from_db = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY created_at DESC", ARRAY_A);

// Gültige und fehlerhafte Tickets zählen
$validTickets = 0;
$invalidTickets = 0;
$optinTickets = 0;
$noOptinTickets = 0;

foreach ($all_tickets_from_db as $ticket) {
	$pattern_image_path = __DIR__ . '/' . $ticket['pattern_file'];
	$ticket_image_path = __DIR__ . '/' . $ticket['ticket_file'];
	
	if (file_exists($pattern_image_path) && file_exists($ticket_image_path)) {
		// Gültiges Ticket
		$validTickets++;
		if ($ticket['optin'] == 1) {
			$optinTickets++;
		} else {
			$noOptinTickets++;
		}
	} else {
		// Fehlerhaftes Ticket
		$invalidTickets++;
	}
}
?>

<style>
    .optin-status {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 3px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .optin-yes {
        background: #d4edda;
        color: #155724;
    }
    .optin-no {
        background: #f8d7da;
        color: #721c24;
    }
    .delete-link {
        color: #d63638;
        text-decoration: none;
        opacity: 0;
        transition: opacity 0.2s;
        font-weight: 500;
        font-size: 13px;
    }
    .row-title {
        font-weight: 600;
    }
    .delete-link:hover {
        color: #b32d2e;
        text-decoration: underline;
    }
    tr:hover .delete-link {
        opacity: 1;
    }
    .ticket-images {
        display: flex;
        gap: 10px;
    }
    .ticket-image {
        max-width: 50px;
        height: auto;
        border: 1px solid #c3c4c7;
        border-radius: 3px;
    }
</style>

<div class="wrap">
    <h1>Ticket Übersicht (<?php 
        if ($filter === 'all') {
            echo $validTickets . ' Tickets';
        } elseif ($filter === 'optin') {
            echo $optinTickets . ' Tickets - nur Opt-in';
        } elseif ($filter === 'nooptin') {
            echo $noOptinTickets . ' Tickets - nur ohne Opt-in';
        } elseif ($filter === 'invalid') {
            echo $invalidTickets . ' Tickets - fehlerhafte';
        }
    ?>)</h1>
    
            <!-- WordPress Navigation -->
            <div class="tablenav top">
                <div class="alignleft">
                    <a href="?page=hsbi-tickets&filter=all" class="button <?php echo $filter === 'all' ? 'button-primary' : ''; ?>">
                        Alle Tickets (<?php echo $validTickets; ?>)
                    </a>
                    <a href="?page=hsbi-tickets&filter=optin" class="button <?php echo $filter === 'optin' ? 'button-primary' : ''; ?>">
                        Nur Opt-in (<?php echo $optinTickets; ?>)
                    </a>
                    <a href="?page=hsbi-tickets&filter=nooptin" class="button <?php echo $filter === 'nooptin' ? 'button-primary' : ''; ?>">
                        Nur ohne Opt-in (<?php echo $noOptinTickets; ?>)
                    </a>
                    <a href="?page=hsbi-tickets&filter=invalid" class="button <?php echo $filter === 'invalid' ? 'button-primary' : ''; ?>">
                        Fehlerhafte (<?php echo $invalidTickets; ?>)
                    </a>
                </div>
            </div>
    
    <?php if ($filter === 'all' || $filter === 'optin' || $filter === 'nooptin' || $filter === 'invalid'): ?>
        
        <!-- Tickets Tabelle -->
        <?php if (empty($tickets)): ?>
            <div class="no-tickets">
                <p>Noch keine Tickets erstellt.</p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>E-Mail</th>
                        <th>Organisation</th>
                        <th>Opt-in</th>
                        <th>Erstellt</th>
                        <th>Pattern</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): 
                        // Daten anonymisieren falls kein Opt-in
                        $display_data = anonymize_data($ticket['name'], $ticket['email'], $ticket['optin']);
                    ?>
                    <tr data-ticket-id="<?php echo $ticket['id']; ?>" data-ticket-uid="<?php echo $ticket['uid']; ?>">
                        <td>
                            <strong class="row-title <?php echo $ticket['optin'] ? '' : 'anonymized'; ?>"><?php echo htmlspecialchars($display_data['name']); ?></strong>
                            <div class="row-actions">
                                <?php if ($ticket['optin']): ?>
                                    <span class="view">
                                        <a href="#" onclick="viewTicket(<?php echo $ticket['id']; ?>); return false;">
                                            Ticket ansehen
                                        </a>
                                    </span> | 
                                <?php endif; ?>
                                <span class="delete">
                                    <a href="#" onclick="deleteTicket(<?php echo $ticket['id']; ?>, '<?php echo htmlspecialchars($display_data['name']); ?>'); return false;">
                                        Ticket löschen
                                    </a>
                                </span>
                            </div>
                        </td>
                        <td class="<?php echo $ticket['optin'] ? '' : 'anonymized'; ?>"><?php echo htmlspecialchars($display_data['email']); ?></td>
                        <td><?php echo !empty($ticket['organization']) ? htmlspecialchars($ticket['organization']) : '—'; ?></td>
                        <td>
                            <span class="optin-status <?php echo $ticket['optin'] ? 'optin-yes' : 'optin-no'; ?>">
                                <?php echo $ticket['optin'] ? 'Ja' : 'Nein'; ?>
                            </span>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?></td>
                        <td>
                            <?php 
                                $pattern_image_path = __DIR__ . '/' . $ticket['pattern_file'];
                                $pattern_image_url = plugin_dir_url(__FILE__) . $ticket['pattern_file'];
                                if (file_exists($pattern_image_path)): ?>
                                    <img src="<?php echo esc_url($pattern_image_url); ?>" 
                                         alt="Pattern" class="ticket-image">
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Ticket Modal -->
<div id="ticketModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeTicketModal()">&times;</span>
        <h2>Ticket Details</h2>
        <div id="ticketModalContent">
            <!-- Inhalt wird dynamisch geladen -->
        </div>
    </div>
</div>

<style>
    /* Blur-Effekt für anonymisierte Daten */
    .anonymized {
        filter: blur(2px);
    }
    
    /* Modal Styles */
    .modal {
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }
    
    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 800px;
        border-radius: 8px;
        position: relative;
    }
    
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        position: absolute;
        top: 10px;
        right: 15px;
    }
    
    .close:hover {
        color: #000;
    }
    
    .ticket-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .info-column p {
        margin: 8px 0;
    }
    
    .ticket-image-modal {
        text-align: center;
    }
    
    .ticket-image-modal img {
        max-width: 100%;
        height: auto;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    /* WordPress Standard .row-actions */
    .row-actions {
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    tr:hover .row-actions {
        opacity: 1;
    }
    
    /* Pattern-Spalte schmaler und rechtsbündig */
    .wp-list-table th:nth-child(6),
    .wp-list-table td:nth-child(6) {
        width: 80px;
        text-align: right;
    }
    
    .ticket-images {
        text-align: right;
    }
</style>

<script>
function viewTicket(ticketId) {
    // Modal öffnen
    document.getElementById('ticketModal').style.display = 'block';
    
    // Ticket-Daten laden (vereinfacht - in echter Implementierung würde AJAX verwendet)
    const ticketData = getTicketData(ticketId);
    if (ticketData) {
        document.getElementById('ticketModalContent').innerHTML = `
            <div class="ticket-details">
                <div class="ticket-info-grid">
                    <div class="info-column">
                        <p><strong>Name:</strong> ${ticketData.name}</p>
                        <p><strong>E-Mail:</strong> ${ticketData.email}</p>
                        <p><strong>Organisation:</strong> ${ticketData.organization || '—'}</p>
                    </div>
                    <div class="info-column">
                        <p><strong>Opt-in:</strong> ${ticketData.optin ? 'Ja' : 'Nein'}</p>
                        <p><strong>Erstellt:</strong> ${ticketData.created_at}</p>
                    </div>
                </div>
                
                <div class="ticket-image-modal">
                    <img src="${ticketData.ticket_url}" alt="Ticket" />
                </div>
            </div>
        `;
    }
}

function closeTicketModal() {
    document.getElementById('ticketModal').style.display = 'none';
}

// Modal schließen beim Klick außerhalb
window.onclick = function(event) {
    const modal = document.getElementById('ticketModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// ESC-Taste zum Schließen
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeTicketModal();
    }
});

function getTicketData(ticketId) {
    // Vereinfachte Implementierung - in echter Anwendung würde AJAX verwendet
    const row = document.querySelector(`tr[data-ticket-id="${ticketId}"]`);
    if (!row) return null;
    
    // Da nur Opt-in-Tickets das Modal öffnen können, ist optin immer true
    const name = row.querySelector('.row-title').textContent;
    const email = row.querySelector('td:nth-child(2)').textContent;
    const organization = row.querySelector('td:nth-child(3)').textContent;
    const optin = true; // Immer true, da nur Opt-in-Tickets das Modal öffnen
    const created = row.querySelector('td:nth-child(5)').textContent;
    
    // Bild-URLs konstruieren - UID verwenden
    const baseUrl = '<?php echo plugin_dir_url(__FILE__); ?>';
    const ticketUid = row.getAttribute('data-ticket-uid');
    const patternUrl = baseUrl + `${ticketUid}_pattern.png`;
    const ticketUrl = baseUrl + `${ticketUid}_ticket.png`;
    
    return {
        name: name,
        email: email,
        organization: organization,
        optin: optin,
        created_at: created,
        pattern_url: patternUrl,
        ticket_url: ticketUrl
    };
}


function deleteTicket(ticketId, ticketName) {
    if (!confirm('Möchten Sie das Ticket von "' + ticketName + '" wirklich löschen?\n\nDiese Aktion kann nicht rückgängig gemacht werden.')) {
        return;
    }
    
    // AJAX-Request zum Löschen
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=delete_hsbi_ticket&ticket_id=' + ticketId + '&nonce=<?php echo wp_create_nonce('delete_hsbi_ticket'); ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Ticket erfolgreich gelöscht!');
            location.reload();
        } else {
            alert('Fehler beim Löschen: ' + (data.data || 'Unbekannter Fehler'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Fehler beim Löschen des Tickets');
    });
}

function exportTickets() {
    if (!confirm('Möchten Sie alle Tickets als CSV + Bilder als ZIP exportieren?\n\nDies kann bei vielen Tickets etwas dauern.')) {
        return;
    }
    
    // AJAX-Request zum Export
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
        alert('Fehler beim Export der Tickets');
    });
}
</script>
