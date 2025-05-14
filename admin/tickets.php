<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit();
}

require '../db.php';

// Fetch all tickets with university and ticket_type information
$stmt = $conn->query("SELECT tickets.id, users.first_name, users.last_name, users.email, 
                      users.university, tickets.ticket_type, tickets.is_scanned, 
                      tickets.scanned_at 
                      FROM tickets 
                      JOIN users ON tickets.user_id = users.id");
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique ticket types for dropdown
$ticketTypes = [];
foreach ($tickets as $ticket) {
    if (!empty($ticket['ticket_type']) && !in_array($ticket['ticket_type'], $ticketTypes)) {
        $ticketTypes[] = $ticket['ticket_type'];
    }
}
sort($ticketTypes);

// Function to format ticket type
function formatTicketType($ticketType) {
    switch (strtolower($ticketType)) {
        case 'vip':
            return 'Special';
        case 'early_bird':
            return 'Early Bird';
        case 'regular':
            return 'Classical';
        default:
            // For any other types, use title case
            return ucwords(str_replace('_', ' ', $ticketType));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Enhanced professional styling */
        body {
            background-color: #f5f7fa;
            color: #2c3e50;
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #2a3f54, #1a2942);
            color: white;
            padding: 20px;
            position: fixed;
            height: 100%;
            top: 0;
            bottom: 0;
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
            z-index: 100;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }
        .sidebar-brand {
            padding: 15px 0;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar-brand h3 {
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0;
        }
        .sidebar a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: block;
            padding: 12px 15px;
            margin: 8px 0;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 500;
        }
        .sidebar a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }
        .sidebar a:last-child {
            margin-top: auto;
            margin-bottom: 20px;
        }
        .main-content {
            flex: 1;
            padding: 30px;
            margin-left: 280px;
            background-color: #f5f7fa;
            transition: margin-left 0.3s;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        .greeting {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .card {
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 25px;
            overflow: hidden;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: white;
            border-bottom: 1px solid #f1f1f1;
            padding: 20px;
        }
        .card-title {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
        }
        .card-body {
            padding: 20px;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            border-top: none;
            padding: 15px;
        }
        .table td {
            padding: 15px;
            vertical-align: middle;
            font-weight: 500;
        }
        .table tr:hover {
            background-color: #f8f9fa;
        }
        .badge-status {
            padding: 5px 10px;
            border-radius: 30px;
            font-weight: 500;
            font-size: 0.75rem;
        }
        .badge-scanned {
            background-color: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
        }
        .badge-pending {
            background-color: rgba(52, 152, 219, 0.1);
            color: #3498db;
        }
        .btn-back {
            background: linear-gradient(135deg, #2a3f54, #1a2942);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        .btn-back:hover {
            background: linear-gradient(135deg, #1a2942, #0d1520);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .btn-access-granted {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-access-granted:hover {
            background: linear-gradient(135deg, #27ae60, #219a52);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.2);
        }
        .btn-disabled {
            background: #d3d3d3;
            color: #7f8c8d;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: 500;
            cursor: not-allowed;
        }
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }
        .search-container {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
            align-items: flex-end; /* Align items to the bottom */
        }
        .search-container .form-group {
            flex: 1;
            min-width: 200px;
            max-width: 300px;
        }
        .search-container select, .search-container input {
            padding: 10px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background-color: white;
            height: 42px; /* Standardize height for all inputs */
        }
        .search-container select:focus, .search-container input:focus {
            border-color: #2a3f54;
            box-shadow: 0 0 5px rgba(42, 63, 84, 0.2);
            outline: none;
        }
        .clear-filters {
            background-color: #e9ecef;
            color: #2c3e50;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
            height: 42px; /* Match height with inputs */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .clear-filters:hover {
            background-color: #dee2e6;
            transform: translateY(-2px);
        }
        .form-label {
            margin-bottom: 8px;
            font-weight: 500;
        }
        /* Download button styles */
        .btn-download {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
            height: 42px; /* Match height with other buttons/inputs */
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: auto; /* Push to right end */
        }
        .btn-download:hover {
            background: linear-gradient(135deg, #2980b9, #1f6aa5);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.2);
        }
        .dropdown-menu {
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
        }
        .dropdown-item {
            padding: 10px 15px;
            transition: all 0.2s;
        }
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h3>Admin Portal</h3>
        </div>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="tickets.php" class="active"><i class="fas fa-ticket-alt"></i> Manage Tickets</a>
        <a href="send_email.php"><i class="fas fa-envelope"></i> Send Email</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div>
                <h1 class="greeting">Manage Tickets</h1>
                <p class="date-display" id="current-date"></p>
            </div>
        </div>
        
        <a href="dashboard.php" class="btn btn-back"><i class="fas fa-arrow-left me-2"></i> Back to Dashboard</a>
        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-ticket-alt me-2"></i> Event Tickets</h5>
            </div>
            <div class="card-body">
                <!-- Search Filters -->
                <div class="search-container">
                    <div class="form-group">
                        <label for="search-id" class="form-label">Ticket ID</label>
                        <input type="text" id="search-id" class="form-control" placeholder="Search by ID">
                    </div>
                    
                    <div class="form-group">
                        <label for="filter-ticket-type" class="form-label">Ticket Type</label>
                        <select id="filter-ticket-type" class="form-select">
                            <option value="">All Ticket Types</option>
                            <?php foreach ($ticketTypes as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>"><?php echo formatTicketType($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" style="visibility: hidden;">Action</label> <!-- Invisible label for alignment -->
                        <button id="clear-filters" class="clear-filters w-100">
                            <i class="fas fa-times me-2"></i>Clear Filters
                        </button>
                    </div>
                    
                    <!-- Download Button with Dropdown -->
                    <div class="form-group" style="max-width: 180px;">
                        <label class="form-label" style="visibility: hidden;">Download</label> <!-- Invisible label for alignment -->
                        <div class="dropdown">
                            <button class="btn-download w-100 dropdown-toggle" type="button" id="downloadDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-download me-2"></i>Download
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="downloadDropdown">
                                <li><a class="dropdown-item" href="#" onclick="downloadTableImage('png')">PNG Image</a></li>
                                <li><a class="dropdown-item" href="#" onclick="downloadTableImage('jpg')">JPEG Image</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive" id="tickets-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>University</th>
                                <th>Ticket Type</th>
                                <th>Time Admitted</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                            <tr id="ticket-<?php echo $ticket['id']; ?>" 
                                data-university="<?php echo htmlspecialchars($ticket['university']); ?>" 
                                data-ticket-type="<?php echo htmlspecialchars($ticket['ticket_type']); ?>">
                                <td>#<?php echo $ticket['id']; ?></td>
                                <td><?php echo $ticket['first_name'] . ' ' . $ticket['last_name']; ?></td>
                                <td><?php echo $ticket['email']; ?></td>
                                <td><?php echo $ticket['university']; ?></td>
                                <td><?php echo formatTicketType($ticket['ticket_type']); ?></td>
                                <td><?php echo $ticket['scanned_at'] ? $ticket['scanned_at'] : 'N/A'; ?></td>
                                <td>
                                    <?php if (!$ticket['is_scanned']): ?>
                                        <button class="btn btn-access-granted" onclick="markAsScanned(<?php echo $ticket['id']; ?>)">Grant Access</button>
                                    <?php else: ?>
                                        <button class="btn btn-disabled" disabled><i class="fas fa-check"></i> Processed</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add html2canvas library -->
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Combined filter function
        function filterTickets() {
            const idSearch = document.getElementById('search-id').value.trim().toLowerCase();
            const ticketTypeFilter = document.getElementById('filter-ticket-type').value;
            
            const rows = document.querySelectorAll('.table tbody tr');
            
            rows.forEach(row => {
                const ticketId = row.querySelector('td:first-child').textContent.toLowerCase();
                const ticketType = row.getAttribute('data-ticket-type');
                
                // Check if the row matches all active filters
                const matchesId = idSearch === '' || ticketId.includes(idSearch);
                const matchesTicketType = ticketTypeFilter === '' || ticketType === ticketTypeFilter;
                
                // Show/hide row based on filter match
                if (matchesId && matchesTicketType) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        // Add event listeners to all filters
        document.getElementById('search-id').addEventListener('input', filterTickets);
        document.getElementById('filter-ticket-type').addEventListener('change', filterTickets);
        
        // Clear filters button
        document.getElementById('clear-filters').addEventListener('click', function() {
            document.getElementById('search-id').value = '';
            document.getElementById('filter-ticket-type').value = '';
            filterTickets(); // Re-run the filter to show all rows
        });

        // Download table as image function
        function downloadTableImage(format) {
            // Show a loading indicator or message
            const loadingMessage = document.createElement('div');
            loadingMessage.style.position = 'fixed';
            loadingMessage.style.top = '50%';
            loadingMessage.style.left = '50%';
            loadingMessage.style.transform = 'translate(-50%, -50%)';
            loadingMessage.style.padding = '15px 30px';
            loadingMessage.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
            loadingMessage.style.color = 'white';
            loadingMessage.style.borderRadius = '8px';
            loadingMessage.style.zIndex = '9999';
            loadingMessage.textContent = 'Generating image...';
            document.body.appendChild(loadingMessage);
            
            // Get current date for filename
            const date = new Date();
            const dateString = date.toISOString().split('T')[0];
            const filename = `tickets_${dateString}.${format}`;
            
            // Use html2canvas to capture the tickets table
            html2canvas(document.getElementById('tickets-table'), {
                scale: 2, // Higher scale for better quality
                backgroundColor: '#ffffff',
                logging: false,
                useCORS: true
            }).then(canvas => {
                // Remove loading message
                document.body.removeChild(loadingMessage);
                
                // Convert to the requested format
                let imageUrl;
                if (format === 'jpg') {
                    imageUrl = canvas.toDataURL('image/jpeg', 0.9);
                } else {
                    imageUrl = canvas.toDataURL('image/png');
                }
                
                // Create download link
                const downloadLink = document.createElement('a');
                downloadLink.href = imageUrl;
                downloadLink.download = filename;
                downloadLink.style.display = 'none';
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
                
                // Show success message
                const successMessage = document.createElement('div');
                successMessage.style.position = 'fixed';
                successMessage.style.top = '10%';
                successMessage.style.left = '50%';
                successMessage.style.transform = 'translateX(-50%)';
                successMessage.style.padding = '15px 30px';
                successMessage.style.backgroundColor = 'rgba(46, 204, 113, 0.9)';
                successMessage.style.color = 'white';
                successMessage.style.borderRadius = '8px';
                successMessage.style.zIndex = '9999';
                successMessage.textContent = 'Download complete!';
                document.body.appendChild(successMessage);
                
                // Remove success message after 3 seconds
                setTimeout(() => {
                    document.body.removeChild(successMessage);
                }, 3000);
            }).catch(error => {
                // Remove loading message
                document.body.removeChild(loadingMessage);
                
                // Show error message
                console.error('Error generating image:', error);
                alert('Error generating image. Please try again.');
            });
        }

        // Mark as Scanned Function
        function markAsScanned(ticketId) {
            const formData = new FormData();
            formData.append('ticket_id', ticketId);

            fetch('mark_scanned.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const row = document.getElementById('ticket-' + ticketId);
                    if (row) {
                        // Update the status badge
                        const statusBadge = row.querySelector('.badge-status');
                        if (statusBadge) {
                            statusBadge.classList.remove('badge-pending');
                            statusBadge.classList.add('badge-scanned');
                            statusBadge.textContent = 'Scanned';
                        }

                        // Update the scanned time
                        const scannedTimeCell = row.querySelector('td:nth-child(6)');
                        if (scannedTimeCell) {
                            scannedTimeCell.textContent = data.scanned_at || 'N/A';
                        }

                        // Replace the "Grant Access" button with a disabled "Processed" button
                        const actionCell = row.querySelector('td:nth-child(7)');
                        if (actionCell) {
                            actionCell.innerHTML = '<button class="btn btn-disabled" disabled><i class="fas fa-check"></i> Processed</button>';
                        }
                    }
                } else {
                    alert('Failed to mark as scanned: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while marking the ticket as scanned.');
            });
        }

        // Set current date in the header
        document.addEventListener('DOMContentLoaded', function() {
            const dateElement = document.getElementById('current-date');
            if (dateElement) {
                const today = new Date();
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                dateElement.textContent = today.toLocaleDateString('en-US', options);
            }
        });
    </script>
</body>
</html>