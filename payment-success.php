<?php
session_start(); // Start the session to retrieve temporary data
require 'vendor/autoload.php'; // Include Composer autoload
require 'db.php'; // Include database connection
require 'email_functions.php';

// Define ticket types for display
$ticket_types = [
    'early_bird' => 'Early Bird',
    'regular' => 'Classical',
    'vip' => 'Special'
];

// Paystack secret key for verification
$paystack_secret_key = "sk_live_b32e244d984a7c76f4df41e22e766620d599ab10"; // Replace with your actual Paystack secret key

// Check if reference is provided
if (!isset($_GET['reference']) || empty($_GET['reference'])) {
    die('<div class="container mt-4"><div class="alert alert-danger text-center" role="alert">
            <h4 class="alert-heading">Error!</h4>
            <p>Invalid payment reference.</p>
          </div></div>');
}

$reference = $_GET['reference'];

// Verify the payment
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "accept: application/json",
        "authorization: Bearer " . $paystack_secret_key,
        "cache-control: no-cache"
    ],
));
$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    die('<div class="container mt-4"><div class="alert alert-danger text-center" role="alert">
            <h4 class="alert-heading">Error!</h4>
            <p>Failed to verify payment: ' . $err . '</p>
          </div></div>');
}

$transaction = json_decode($response);

// Check if the payment was successful
if (!$transaction->status || $transaction->data->status !== 'success') {
    die('<div class="container mt-4"><div class="alert alert-danger text-center" role="alert">
            <h4 class="alert-heading">Payment Failed!</h4>
            <p>Your payment could not be verified.</p>
          </div></div>');
}

// Retrieve user data from session
if (!isset($_SESSION['user_data'])) {
    die('<div class="container mt-4"><div class="alert alert-danger text-center" role="alert">
            <h4 class="alert-heading">Error!</h4>
            <p>User data not found.</p>
          </div></div>');
}

$user_data = $_SESSION['user_data'];
$user_id = null;
$user_index = null;

// Save the user to the database NOW that payment is confirmed
try {
    // Insert user into the database
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone_number, university) VALUES (:first_name, :last_name, :email, :phone_number, :university)");
    $stmt->bindParam(':first_name', $user_data['first_name']);
    $stmt->bindParam(':last_name', $user_data['last_name']);
    $stmt->bindParam(':email', $user_data['email']);
    $stmt->bindParam(':phone_number', $user_data['phone_number']);
    $stmt->bindParam(':university', $user_data['university']);
    $stmt->execute();

    $user_id = $conn->lastInsertId();
    
    // Get the count of users to determine the index (their ID for the event)
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_index = $result['total'];

    // Insert ticket into the database with paid status
    $stmt = $conn->prepare("INSERT INTO tickets (user_id, ticket_type, ticket_price, payment_reference, payment_status, paid_at) VALUES (:user_id, :ticket_type, :ticket_price, :payment_reference, 'paid', NOW())");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':ticket_type', $user_data['ticket_type']);
    $stmt->bindParam(':ticket_price', $user_data['ticket_price']);
    $stmt->bindParam(':payment_reference', $reference);
    $stmt->execute();

    // Clear session data
    unset($_SESSION['user_data']);
    unset($_SESSION['payment_reference']);
    unset($_SESSION['temp_user_index']);
    
    // Send confirmation email for paid tickets
    $email_sent = sendConfirmationEmail($user_data, $reference, $user_index);
    
} catch (PDOException $e) {
    die('<div class="container mt-4"><div class="alert alert-danger text-center" role="alert">
            <h4 class="alert-heading">Database Error!</h4>
            <p>' . $e->getMessage() . '</p>
          </div></div>');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Additional styling for the payment success page */
        .btn-gradient-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 2rem auto;
            width: 60%; /* Control width on desktop */
            max-width: 300px; /* Maximum width */
        }
        
        .btn-payment {
            display: block;
            width: 100%;
            text-align: center;
            padding: 12px 24px;
            color: white;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-payment:hover {
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }
        
        /* Media query for mobile devices */
        @media (max-width: 768px) {
            .btn-gradient-wrapper {
                width: 100%;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="row g-0">
                <div class="col-md-12">
                    <div class="form-content text-center">
                        <h1 class="form-title">Payment Successful!</h1>

                        <!-- Email Confirmation Message -->
                        <?php if ($email_sent): ?>
                            <div class="alert alert-success" role="alert">
                                <h4 class="alert-heading">Email Sent!</h4>
                                <p>Check your inbox for an email containing the ticket details.</p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning" role="alert">
                                <h4 class="alert-heading">Email Not Sent!</h4>
                                <p>There was an issue sending the ticket details email. Please contact support.</p>
                            </div>
                        <?php endif; ?>

                        <!-- Payment Confirmation Message -->
                        <div class="alert alert-success" role="alert">
                            <h4 class="alert-heading">Thank you, <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?>!</h4>
                            <p>Your payment has been successfully processed and your registration is complete.</p>
                            <hr>
                            <h3>Your ID: <strong><?php echo $user_index; ?></strong></h3>
                            <p class="mb-0">Please save this ID number. You will need it at the event.</p>
                        </div>
                        
                        <div class="mt-4">
                            <p>Transaction Reference: <strong><?php echo htmlspecialchars($reference); ?></strong></p>
                            <p>Amount Paid: <strong>₦<?php echo number_format($user_data['ticket_price'], 2); ?></strong></p>
                            <p>A payment confirmation email has been sent to <strong><?php echo htmlspecialchars($user_data['email']); ?></strong></p>
                        </div>
                        
                        <div class="btn-gradient-wrapper">
                            <a href="register.php" class="btn-payment">Return to Registration</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>