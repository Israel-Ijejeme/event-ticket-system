<?php
session_start();
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Fetch admin from the database
    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        // Password is correct
        $_SESSION['admin'] = $admin['username'];
        header('Location: dashboard.php');
        exit();
    } else {
        // Invalid username or password
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: #f5f7fa;
            color: #2c3e50;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            width: 100%;
            padding: 20px;
        }
        .form-container {
            max-width: 900px;
            margin: 0 auto;
            background-color: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .form-content {
            padding: 40px;
        }
        .form-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .form-subtitle {
            color: #7f8c8d;
            margin-bottom: 30px;
            font-weight: 500;
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
            border-color: #305CDE;
            box-shadow: 0 0 0 0.2rem rgba(48, 92, 222, 0.1);
        }
        .btn-payment {
            background: linear-gradient(135deg, #305CDE, #265AC3);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
        }
        .btn-payment:hover {
            background: linear-gradient(135deg, #265AC3, #1F4DA6);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(48, 92, 222, 0.2);
        }
        .btn-gradient-wrapper {
            margin-top: 25px;
        }
        .illustration-container {
            background: linear-gradient(135deg, #305CDE, #265AC3);
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }
        .illustration-container img {
            max-width: 100%;
            height: auto;
        }
        .alert {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-danger {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
        }
        .input-group-text {
            background-color: transparent;
            border-left: none;
            cursor: pointer;
        }
        .password-container {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #7f8c8d;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="row g-0">
                <div class="col-md-7">
                    <div class="form-content">
                        <h1 class="form-title">Admin Login</h1>
                        <p class="form-subtitle">Enter username and password to access admin dashboard</p>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="row mb-4">
                                <div class="col-md-12 mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="password-container">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                                        </div>
                                        <button type="button" class="password-toggle" onclick="togglePassword()">
                                            <i class="fas fa-eye" id="toggle-icon"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="btn-gradient-wrapper">
                                <button type="submit" class="btn-payment">
                                    <i class="fas fa-sign-in-alt me-2"></i> Login
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="illustration-container">
                        <img src="../wp-content/uploads/2024/04/nacomes1-removebg-preview.png" alt="Login Illustration">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggle-icon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>