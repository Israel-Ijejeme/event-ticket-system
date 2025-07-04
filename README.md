# Event Ticket Management System

A web-based ticket management system that allows users to register for events and administrators to manage tickets and communicate with attendees.

## Features

### User Features
- Event registration with personal information
- Secure payment processing
- QR code-based ticket system
- Email confirmation system

### Admin Features
- Secure admin dashboard
- Ticket management interface
- Bulk email communication system
- User database management

## Technical Stack

### Backend
- PHP 7.4+
- MySQL/MariaDB
- PHPMailer for email functionality
- Composer for dependency management

### Frontend
- HTML5
- CSS3 (Bootstrap 5.3.2)
- JavaScript
- Font Awesome 6.4.0
- Google Fonts (Inter)

### Dependencies
- Bootstrap
- PHPMailer
- Font Awesome
- Google Fonts

## Installation

1. Clone the repository to your XAMPP htdocs folder:
```bash
git clone [repository-url] c:/xampp/htdocs/ticket_system
```

2. Import the database schema:
- Create a new MySQL database
- Import the database structure from the SQL file

3. Configure database connection:
- Update the credentials in `db.php`

4. Install dependencies:
```bash
composer install
```

5. Configure email settings:
- Update SMTP credentials in `admin/send_email.php`

## Directory Structure

```
ticket_system/
├── admin/
│   ├── dashboard.php
│   ├── login.php
│   ├── send_email.php
│   └── tickets.php
├── assets/
│   └── css/
│       └── style.css
├── vendor/
├── db.php
├── register.php
└── payment.php
```

## Security Features

- Session-based authentication
- SQL injection prevention using prepared statements
- XSS protection with input sanitization
- Secure email configuration

## Email Configuration

The system uses Gmail SMTP for sending emails. Update the following credentials in `admin/send_email.php`:

```php
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'your-app-password';
```

## License

[Your chosen license]

## Contributors

[List of contributors]

## Support

For support, please contact [contact information]
#   e v e n t - t i c k e t - s y s t e m 
 
 #   e v e n t - t i c k e t - s y s t e m 
 
 