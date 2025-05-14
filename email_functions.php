<?php
// email_functions.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendConfirmationEmail($user_data, $payment_reference, $user_index) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'infonacomes@gmail.com'; // Replace with your email
        $mail->Password = 'zrgcofgsccmcawqk'; // Replace with your email password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use SSL
        $mail->Port = 465; // Port for SSL

        // Recipients
        $mail->setFrom('infonacomes@gmail.com', 'Event Team');
        $mail->addAddress($user_data['email'], $user_data['first_name'] . ' ' . $user_data['last_name']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Event Ticket';

        // Ticket-like HTML template
        $ticket_types = [
            'early_bird' => 'Early Bird',
            'regular' => 'Regular',
            'vip' => 'VIP'
        ];
        $ticket_type_display = $ticket_types[$user_data['ticket_type']] ?? $user_data['ticket_type'];

        // Hosted PNG icons for social media
        $facebook_icon = 'https://example.com/path/to/facebook-icon.png'; // Replace with actual URL
        $instagram_icon = 'https://example.com/path/to/instagram-icon.png'; // Replace with actual URL
        $youtube_icon = 'https://example.com/path/to/youtube-icon.png'; // Replace with actual URL
        $telegram_icon = 'https://example.com/path/to/telegram-icon.png'; // Replace with actual URL
        $linkedin_icon = 'https://example.com/path/to/linkedin-icon.png'; // Replace with actual URL
        $twitter_icon = 'https://example.com/path/to/twitter-icon.png'; // Replace with actual URL

        $body = '
        <html>
        <body style="font-family: Arial, sans-serif; background-color: #f5f7fa; padding: 20px;">
            <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); padding: 20px;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <h1 style="color: #4a1d96;">Your Event Ticket</h1>
                </div>
                
                <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <h2 style="margin-top: 0; color: #4a1d96;">Ticket Details</h2>
                    <p><strong>Full Name:</strong> ' . htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']) . '</p>
                    <p><strong>Email:</strong> ' . htmlspecialchars($user_data['email']) . '</p>
                    <p><strong>Phone Number:</strong> ' . htmlspecialchars($user_data['phone_number']) . '</p>
                    <p><strong>University:</strong> ' . htmlspecialchars($user_data['university']) . '</p>
                    <p><strong>Ticket Type:</strong> ' . $ticket_type_display . '</p>
                    <p><strong>Ticket ID:</strong> ' . $user_index . '</p>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <p>Thank you for registering! Present this ticket at the event for entry.</p>
                    <p><strong>Event Team</strong></p>
                </div>

                <!-- Social Media Icons -->
                <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                    <p style="margin-bottom: 15px; color: #7f8c8d;">Follow us on social media:</p>
                    <a href="https://facebook.com/officialnacomes" target="_blank" style="text-decoration: none; margin: 0 10px;">
                        <img src="' . $facebook_icon . '" alt="Facebook" style="width: 24px; height: 24px;">
                    </a>
                    <a href="https://instagram.com/officialnacomes" target="_blank" style="text-decoration: none; margin: 0 10px;">
                        <img src="' . $instagram_icon . '" alt="Instagram" style="width: 24px; height: 24px;">
                    </a>
                    <a href="https://youtube.com" target="_blank" style="text-decoration: none; margin: 0 10px;">
                        <img src="' . $youtube_icon . '" alt="YouTube" style="width: 24px; height: 24px;">
                    </a>
                    <a href="https://t.me" target="_blank" style="text-decoration: none; margin: 0 10px;">
                        <img src="' . $telegram_icon . '" alt="Telegram" style="width: 24px; height: 24px;">
                    </a>
                    <a href="https://linkedin.com/officialnacomes" target="_blank" style="text-decoration: none; margin: 0 10px;">
                        <img src="' . $linkedin_icon . '" alt="LinkedIn" style="width: 24px; height: 24px;">
                    </a>
                    <a href="https://twitter.com/officialnacomes" target="_blank" style="text-decoration: none; margin: 0 10px;">
                        <img src="' . $twitter_icon . '" alt="Twitter" style="width: 24px; height: 24px;">
                    </a>
                </div>
            </div>
        </body>
        </html>';

        $mail->Body = $body;
        $mail->AltBody = strip_tags($body); // Plain text alternative
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>