<?php
session_start(); // Start session to store user data temporarily
require 'vendor/autoload.php'; // Include Composer autoload
require 'db.php'; // Include database connection
require 'email_functions.php'; // Include email functions

// Add PHPMailer namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$payment_initiated = false;
$payment_reference = '';
$ticket_type = '';
$ticket_price = 0;

// Define ticket prices
$ticket_prices = [
    'early_bird' => 2000, 
    'regular' => 3000,
    'vip' => 7000
];

// Count early bird ticket purchases
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as early_bird_count FROM tickets WHERE ticket_type = 'early_bird' AND payment_status = 'paid'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $early_bird_count = $result['early_bird_count'];
    
    // Check if early bird tickets are still available (limit of 50)
    $early_bird_available = ($early_bird_count < 50);
} catch (PDOException $e) {
    // If there's an error, assume early bird tickets are not available
    $early_bird_available = false;
    $early_bird_count = 0;
}

// Check if a ticket type is passed in the URL
$preselected_ticket = '';
if (isset($_GET['ticket'])) {
    $ticket_param = htmlspecialchars($_GET['ticket'], ENT_QUOTES, 'UTF-8');
    if (in_array($ticket_param, ['early_bird', 'regular', 'vip'])) {
        // Only set early_bird if still available
        if ($ticket_param == 'early_bird' && !$early_bird_available) {
            $preselected_ticket = 'regular'; // Default to regular if early bird is sold out
        } else {
            $preselected_ticket = $ticket_param;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input data
    $first_name = htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $last_name = htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone_number = htmlspecialchars($_POST['phone_number'] ?? '', ENT_QUOTES, 'UTF-8');
    $ticket_type = htmlspecialchars($_POST['ticket_type'] ?? '', ENT_QUOTES, 'UTF-8');
    $university = htmlspecialchars($_POST['university'] ?? '', ENT_QUOTES, 'UTF-8');

    // Check for empty required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($ticket_type) || empty($university)) {
        die('<div class="container mt-4"><div class="alert alert-danger text-center" role="alert">
                <h4 class="alert-heading">Error!</h4>
                <p>Please fill in all required fields.</p>
              </div></div>');
    }

    // Validate ticket type
    if (!array_key_exists($ticket_type, $ticket_prices)) {
        die('<div class="container mt-4"><div class="alert alert-danger text-center" role="alert">
                <h4 class="alert-heading">Error!</h4>
                <p>Invalid ticket type selected.</p>
              </div></div>');
    }
    
    // Check if early bird is selected but sold out (double check)
    if ($ticket_type == 'early_bird' && !$early_bird_available) {
        die('<div class="container mt-4"><div class="alert alert-danger text-center" role="alert">
                <h4 class="alert-heading">Sorry!</h4>
                <p>Early Bird tickets are sold out. Please select another ticket type.</p>
              </div></div>');
    }

    // Set the price based on the selected ticket type
    $ticket_price = $ticket_prices[$ticket_type];
    
    // Generate a unique reference for this payment
    $payment_reference = 'REF' . time() . rand(1000, 9999);
    
    // Store user data in session for retrieval after payment
    $_SESSION['user_data'] = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'phone_number' => $phone_number,
        'university' => $university,
        'ticket_type' => $ticket_type,
        'ticket_price' => $ticket_price
    ];
    
    $_SESSION['payment_reference'] = $payment_reference;
    
    $payment_initiated = true;
    
    // For Early Bird (free) tickets, we process immediately
    if ($ticket_price == 0) {
        try {
            // Insert user into the database
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone_number, university) VALUES (:first_name, :last_name, :email, :phone_number, :university)");
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone_number', $phone_number);
            $stmt->bindParam(':university', $university);
            $stmt->execute();

            $user_id = $conn->lastInsertId();

            // Get the count of users to determine the index
            $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_index = $result['total'];

            // Store user index in session
            $_SESSION['user_index'] = $user_index;

            // Insert ticket record with paid status for free tickets
            $stmt = $conn->prepare("INSERT INTO tickets (user_id, ticket_type, ticket_price, payment_reference, payment_status, paid_at) VALUES (:user_id, :ticket_type, :ticket_price, :payment_reference, 'paid', NOW())");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':ticket_type', $ticket_type);
            $stmt->bindParam(':ticket_price', $ticket_price);
            $stmt->bindParam(':payment_reference', $payment_reference);
            $stmt->execute();
            
            // Send confirmation email for free tickets
            $email_sent = sendConfirmationEmail($_SESSION['user_data'], $payment_reference, $user_index);
            
            if ($email_sent) {
                echo '<div class="container mt-4"><div class="alert alert-success text-center" role="alert">
                        <h4 class="alert-heading">Email Sent!</h4>
                        <p>A confirmation email has been sent to your email address.</p>
                      </div></div>';
            } else {
                echo '<div class="container mt-4"><div class="alert alert-warning text-center" role="alert">
                        <h4 class="alert-heading">Email Not Sent!</h4>
                        <p>There was an issue sending the confirmation email. Please contact support.</p>
                      </div></div>';
            }
            
        } catch (PDOException $e) {
            // Handle database errors
            die('<div class="container mt-4"><div class="alert alert-danger text-center" role="alert">
                    <h4 class="alert-heading">Database Error!</h4>
                    <p>' . $e->getMessage() . '</p>
                  </div></div>');
        }
    } else {
        // For paid tickets, calculate a temporary user index for display
        try {
            $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_index = $result['total'] + 1; // Add 1 since user isn't in DB yet
            $_SESSION['temp_user_index'] = $user_index;
        } catch (PDOException $e) {
            $_SESSION['temp_user_index'] = 'TBD'; // If query fails, use placeholder
        }
    }
}

// Paystack configuration
$paystack_public_key = "pk_live_377f0bb3f0c0c21805613195e2de57a5fce355e5"; // Replace with your actual Paystack public key
$currency = "NGN"; // Set your currency code
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://js.paystack.co/v1/inline.js"></script>
</head>
<body>
    <div class="container">
        <?php if ($payment_initiated): ?>
            <?php if ($ticket_price == 0): ?>
                <!-- Free Ticket Confirmation -->
                <div class="form-container">
                    <div class="row g-0">
                        <div class="col-md-12">
                            <div class="form-content text-center">
                                <h1 class="form-title">Registration Successful!</h1>
                                <div class="alert alert-success" role="alert">
                                    <h4 class="alert-heading">Thank you, <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>!</h4>
                                    <p>Your Early Bird ticket registration is complete.</p>
                                    <hr>
                                    <h3>Your ID: <strong><?php echo $_SESSION['user_index']; ?></strong></h3>
                                    <p class="mb-0">Please save this ID number. You will need it at the event.</p>
                                </div>
                                
                                <div class="mt-4">
                                    <p>A confirmation email has been sent to <strong><?php echo htmlspecialchars($email); ?></strong></p>
                                </div>
                                
                                <div class="btn-gradient-wrapper mt-4">
                                    <a href="register.php" class="btn-payment">Return to Registration</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Paid Ticket - Payment Screen -->
                <div class="form-container">
                    <div class="row g-0">
                        <div class="col-md-12">
                            <div class="form-content text-center">
                                <h1 class="form-title">Complete Your Payment</h1>
                                <p class="form-subtitle">
                                    Thank you for registering!<br>
                                    Your temporary ID is: <strong><?php echo $_SESSION['temp_user_index']; ?></strong> (confirmed after payment)<br>
                                    Please complete your payment of <strong>₦<?php echo number_format($ticket_price, 2); ?></strong> to finalize your registration.
                                </p>
                                
                                <div class="btn-gradient-wrapper mt-4">
                                    <button type="button" onclick="payWithPaystack()" class="btn-payment">Pay Now</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <script>
                    function payWithPaystack() {
                        let handler = PaystackPop.setup({
                            key: '<?php echo $paystack_public_key; ?>',
                            email: '<?php echo $email; ?>',
                            amount: <?php echo $ticket_price * 100; ?>, // Convert to kobo
                            currency: '<?php echo $currency; ?>',
                            ref: '<?php echo $payment_reference; ?>',
                            firstname: '<?php echo $first_name; ?>',
                            lastname: '<?php echo $last_name; ?>',
                            metadata: {
                                custom_fields: [
                                    {
                                        display_name: "Ticket Type",
                                        variable_name: "ticket_type",
                                        value: '<?php echo $ticket_type; ?>'
                                    },
                                    {
                                        display_name: "Phone Number",
                                        variable_name: "phone_number",
                                        value: '<?php echo $phone_number; ?>'
                                    },
                                    {
                                        display_name: "University",
                                        variable_name: "university",
                                        value: '<?php echo $university; ?>'
                                    }
                                ]
                            },
                            callback: function(response) {
                                // Redirect to success page with verification info
                                window.location.href = "payment-success.php?reference=" + response.reference;
                            },
                            onClose: function() {
                                alert('Transaction was not completed, window closed.');
                            }
                        });
                        handler.openIframe();
                    }
                    
                    // Auto-trigger payment on page load
                    window.onload = function() {
                        payWithPaystack();
                    };
                </script>
            <?php endif; ?>
        <?php else: ?>
        <div class="form-container">
            <div class="row g-0">
                <div class="col-md-7">
                    <div class="form-content">
                        <h1 class="form-title">Just one last step!</h1>
                        <p class="form-subtitle">
                            We need you to help us with some basic information for your ticket purchase. <br> Here are
                            our
                            <a href="#" class="text-primary">terms and conditions</a>, kindly read through.
                        </p>
                        
                        <?php if ($early_bird_available): ?>
                        <div class="alert alert-info">
                            <strong>Early Bird Special:</strong> Only <?php echo (50 - $early_bird_count); ?> free early bird tickets remaining!
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <strong>Early Bird Tickets Sold Out!</strong> All 50 early bird tickets have been claimed.
                        </div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">
                                        First name:
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="help-icon">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                        </svg>
                                    </label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">
                                        Last Name:
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="help-icon">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                        </svg>
                                    </label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">
                                        Email:
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="help-icon">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                        </svg>
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone_number" class="form-label">
                                        Phone Number:
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="help-icon">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                        </svg>
                                    </label>
                                    <input type="tel" class="form-control" id="phone_number" name="phone_number">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="ticket_type" class="form-label">
                                        Ticket Type:
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="help-icon">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                        </svg>
                                    </label>
                                    <select class="form-select" id="ticket_type" name="ticket_type" required>
                                        <option value="" disabled <?php echo empty($preselected_ticket) ? 'selected' : ''; ?>>Select Ticket Type</option>
                                        <?php if ($early_bird_available): ?>
                                        <option value="early_bird" <?php echo $preselected_ticket === 'early_bird' ? 'selected' : ''; ?>>Early Bird (₦2,000)</option>
                                        <?php endif; ?>
                                        <option value="regular" <?php echo $preselected_ticket === 'regular' ? 'selected' : ''; ?>>Classic (₦3,000)</option>
                                        <option value="vip" <?php echo $preselected_ticket === 'vip' ? 'selected' : ''; ?>>Special - Feeding + Accomodation (₦7,000)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="university" class="form-label">
                                        University:
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="help-icon">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                        </svg>
                                    </label>
                                    <input type="text" class="form-control" id="university" name="university" required>
                                </div>
                            </div>

                            <div class="btn-gradient-wrapper">
                                <button type="submit" class="btn-payment">Continue to Payment</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="illustration-container">
                        <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/Artboard%203-tNxjZCebOEc7wRwPLA5hdsSZJ1DqjP.png" alt="">
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>