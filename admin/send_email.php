<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit();
}

require '../db.php';
require '../vendor/autoload.php'; // Include Composer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$successMessage = null;
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    
    // Option to send to all users or specific email
    $sendToAll = isset($_POST['send_to_all']) && $_POST['send_to_all'] === 'yes';
    $specificEmail = $_POST['specific_email'] ?? '';
    
    // Get users from database if sending to all
    $emails = [];
    if ($sendToAll) {
        try {
            $stmt = $conn->prepare("SELECT email FROM users");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($users as $user) {
                $emails[] = $user['email'];
            }
            
            if (empty($emails)) {
                $errorMessage = "No users found in the database.";
            }
        } catch (PDOException $e) {
            $errorMessage = "Database error: " . $e->getMessage();
        }
    } else if (!empty($specificEmail)) {
        $emails[] = $specificEmail;
    } else {
        $errorMessage = "Please either provide a specific email or choose to send to all users.";
    }
    
    if (empty($errorMessage) && !empty($emails)) {
        // Initialize PHPMailer
        $mail = new PHPMailer(true);
        
        try {
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'infonacomes@gmail.com'; // Your Gmail address
            $mail->Password = 'zrgcofgsccmcawqk'; // Your Gmail App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Using SMTPS instead of STARTTLS
            $mail->Port = 465; // Using port 465 instead of 587
            
            // Set debug level for testing - remove in production
            $mail->SMTPDebug = 0; // 0 = off, 1 = client messages, 2 = client and server messages
            
            // Sender settings
            $mail->setFrom('infonacomes@gmail.com', 'Event Admin');
            
            // Track successful and failed emails
            $successCount = 0;
            $failedEmails = [];
            
            // Send to each recipient individually
            foreach ($emails as $email) {
                $mail->clearAddresses(); // Clear previous recipients
                $mail->addAddress($email);
                
                // Email content
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $message;
                $mail->AltBody = strip_tags($message);
                
                // Send email
                if ($mail->send()) {
                    $successCount++;
                } else {
                    $failedEmails[] = $email;
                }
            }
            
            // Set success/error message
            if ($successCount > 0) {
                $successMessage = "Successfully sent email to $successCount " . ($successCount == 1 ? "recipient" : "recipients");
                if (!empty($failedEmails)) {
                    $errorMessage = "Failed to send to: " . implode(", ", $failedEmails);
                }
            } else {
                $errorMessage = "Failed to send any emails. Please check your SMTP settings.";
            }
        } catch (Exception $e) {
            $errorMessage = "Mailer Error: " . $mail->ErrorInfo;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Email</title>
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
        .date-display {
            font-size: 1rem;
            color: #7f8c8d;
            font-weight: 500;
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
        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        .form-control {
            padding: 12px 15px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 500;
        }
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.1);
        }
        .btn-email {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-email:hover {
            background: linear-gradient(135deg, #2980b9, #204d74);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.2);
        }
        .alert {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
            border-left: 4px solid #2ecc71;
        }
        .alert-danger {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
        }
        .form-check {
            margin-bottom: 15px;
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
        <a href="tickets.php"><i class="fas fa-ticket-alt"></i> Manage Tickets</a>
        <a href="send_email.php" class="active"><i class="fas fa-envelope"></i> Send Email</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div>
                <h1 class="greeting">Send Email to Users</h1>
                <p class="date-display" id="current-date"></p>
            </div>
        </div>

        <?php if ($successMessage): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i> <?php echo $successMessage; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $errorMessage; ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-envelope me-2"></i> Compose Email</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Recipients</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="recipient_option" id="sendToOne" value="one" checked>
                            <label class="form-check-label" for="sendToOne">Send to Specific Email</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="recipient_option" id="sendToAll" value="all">
                            <label class="form-check-label" for="sendToAll">Send to All Users</label>
                        </div>
                    </div>
                    
                    <div class="mb-3" id="specificEmailField">
                        <label for="specific_email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-at"></i></span>
                            <input type="email" class="form-control" id="specific_email" name="specific_email" placeholder="Enter email address">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subject" class="form-label">Email Subject</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-heading"></i></span>
                            <input type="text" class="form-control" id="subject" name="subject" placeholder="Enter email subject" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">Email Content</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-edit"></i></span>
                            <textarea class="form-control" id="message" name="message" rows="8" placeholder="Compose your message here..." required></textarea>
                        </div>
                        <small class="text-muted">You can use HTML formatting in your message.</small>
                    </div>
                    
                    <input type="hidden" id="send_to_all" name="send_to_all" value="no">
                    
                    <button type="submit" class="btn-email">
                        <i class="fas fa-paper-plane me-2"></i> Send Email
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Format current date
        function updateDate() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', options);
        }

        // Toggle between sending options
        document.addEventListener('DOMContentLoaded', function() {
            updateDate();
            
            const sendToOne = document.getElementById('sendToOne');
            const sendToAll = document.getElementById('sendToAll');
            const specificEmailField = document.getElementById('specificEmailField');
            const sendToAllInput = document.getElementById('send_to_all');
            
            // Set initial state
            specificEmailField.style.display = sendToOne.checked ? 'block' : 'none';
            
            // Add event listeners
            sendToOne.addEventListener('change', function() {
                specificEmailField.style.display = 'block';
                sendToAllInput.value = 'no';
            });
            
            sendToAll.addEventListener('change', function() {
                specificEmailField.style.display = 'none';
                sendToAllInput.value = 'yes';
            });
        });
    </script>
</body>
</html>