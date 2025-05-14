<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit();
}

require '../db.php';

// Fetch all users
$stmt = $conn->query("SELECT * FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch users joined in the last 24 hours
$stmt = $conn->query("SELECT COUNT(*) as recent_users FROM users WHERE created_at >= NOW() - INTERVAL 1 DAY");
$recent_users = $stmt->fetch(PDO::FETCH_ASSOC)['recent_users'];

// Fetch total users
$stmt = $conn->query("SELECT COUNT(*) as total_users FROM users");
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
            height: 100%; /* Full height */
            top: 0; /* Align to top */
            bottom: 0; /* Stretch to bottom */
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
            z-index: 100;
            display: flex;
            flex-direction: column;
            overflow-y: auto; /* Allow scrolling if content is too tall */
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
        /* Push logout to bottom of sidebar */
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
            min-height: 100vh; /* Ensure main content is at least full height */
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
        .stat-card {
            padding: 20px;
        }
        .stat-icon {
            height: 60px;
            width: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            margin-bottom: 15px;
            font-size: 24px;
        }
        .users-icon {
            background-color: rgba(52, 152, 219, 0.1);
            color: #3498db;
        }
        .recent-icon {
            background-color: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
            color: #2c3e50;
        }
        .stat-label {
            font-size: 1rem;
            color: #7f8c8d;
            font-weight: 500;
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
        .badge-active {
            background-color: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
        }
        .badge-inactive {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        .toast {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 15px 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            animation: slideIn 0.3s ease-out;
            max-width: 350px;
        }
        .toast-success {
            border-left: 4px solid #2ecc71;
        }
        .toast-icon {
            margin-right: 15px;
            font-size: 1.5rem;
        }
        .toast-success .toast-icon {
            color: #2ecc71;
        }
        .toast-body {
            flex: 1;
        }
        .toast-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        .toast-message {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        .toast-close {
            color: #bdc3c7;
            cursor: pointer;
            padding: 5px;
            transition: color 0.3s;
        }
        .toast-close:hover {
            color: #7f8c8d;
        }
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }
        .fade-out {
            animation: fadeOut 0.3s forwards;
        }
        /* Add media query for responsive design */
        @media (max-height: 600px) {
            .sidebar {
                overflow-y: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h3>Admin Portal</h3>
        </div>
        <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="tickets.php"><i class="fas fa-ticket-alt"></i> Manage Tickets</a>
        <a href="send_email.php"><i class="fas fa-envelope"></i> Send Email</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div>
                <h1 class="greeting" id="greeting">Welcome, <?php echo $_SESSION['admin']; ?></h1>
                <p class="date-display" id="current-date"></p>
            </div>
        </div>

        <!-- Toast container -->
        <div class="toast-container">
            <div class="toast toast-success" id="login-toast">
                <div class="toast-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="toast-body">
                    <div class="toast-title">Login Successful</div>
                    <div class="toast-message">Welcome to your dashboard!</div>
                </div>
                <div class="toast-close" onclick="dismissToast(this.parentNode)">
                    <i class="fas fa-times"></i>
                </div>
            </div>
        </div>

        <!-- Analytics Cards -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="stat-card">
                        <div class="stat-icon recent-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="stat-label">New Users (Last 24 Hours)</div>
                        <div class="stat-value"><?php echo $recent_users; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="stat-card">
                        <div class="stat-icon users-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-label">Total Users</div>
                        <div class="stat-value"><?php echo $total_users; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Registered Users Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Registered Users</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Email</th>
                                <th>Phone Number</th>
                                <th>University</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['first_name']; ?></td>
                                <td><?php echo $user['last_name']; ?></td>
                                <td><?php echo $user['email']; ?></td>
                                <td><?php echo $user['phone_number']; ?></td>
                                <td><?php echo $user['university']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Time-based greeting function
        function updateGreeting() {
            const hour = new Date().getHours();
            const username = "<?php echo $_SESSION['admin']; ?>";
            let greeting = "";
            
            if (hour >= 5 && hour < 12) {
                greeting = `Good Morning, ${username}`;
            } else if (hour >= 12 && hour < 18) {
                greeting = `Good Afternoon, ${username}`;
            } else {
                greeting = `Good Evening, ${username}`;
            }
            
            document.getElementById('greeting').textContent = greeting;
        }
        
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
        
        // Dismiss toast notification
        function dismissToast(toast) {
            toast.classList.add('fade-out');
            setTimeout(() => {
                toast.style.display = 'none';
            }, 300);
        }
        
        // Auto dismiss toast after 5 seconds
        setTimeout(() => {
            const toast = document.getElementById('login-toast');
            if (toast) {
                dismissToast(toast);
            }
        }, 5000);
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateGreeting();
            updateDate();
            
            // Show toast message
            const toastElement = document.getElementById('login-toast');
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
            
            // Update greeting and date every minute
            setInterval(() => {
                updateGreeting();
                updateDate();
            }, 60000);
        });
    </script>
</body>
</html>