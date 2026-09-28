<?php
require_once 'config/admin_config.php';

if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
    } else {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id, username, email, password, full_name, role, status FROM admin_users WHERE (username = ? OR email = ?) AND status = 'active'");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['admin_id'] = $row['id'];
                $_SESSION['admin_username'] = $row['username'];
                $_SESSION['admin_email'] = $row['email'];
                $_SESSION['admin_fullname'] = $row['full_name'];
                $_SESSION['admin_role'] = $row['role'];
                $_SESSION['admin_last_activity'] = time();
                
                
                
                
                // Update last login
                $updateStmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $updateStmt->bind_param("i", $row['id']);
                $updateStmt->execute();
                $updateStmt->close();
                
                logAdminActivity($row['id'], 'LOGIN', 'Admin logged in successfully');
                
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Invalid username or password';
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Murna Foundation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>

           :root {
      /* Silver palette */
      --silver: #C0C0C0;
      --silver-light: #E8E8E8;
      --silver-dark: #8A8A8A;
      --silver-mid: #D4D4D4;
      /* Dark blue palette */
      --blue-deep: #0A1A2F;
      --blue-dark: #112B4F;
      --blue-mid: #1E3A5F;
      --blue-soft: #2A4A6F;
      --blue-new: rgba(53,38,101,0.18);
      --cyan: #1f82b6;
     
      /* Neutrals */
      --black: #0B0F14;
      --black-soft: #141A22;
      --card: #1A2432;
      --card-light: #1F2B3C;
      --border: rgba(192, 192, 192, 0.18);
      --text: #0A1A2F;
      --text-muted: #9AA8B9;
      --white: #FFFFFF;
      --input-bg: rgba(192, 192, 192, 0.06);
      /* Accent (silver as primary) */
      --accent: #C0C0C0;
      --accent-light: #D9D9D9;
      --accent-dark: #8A8A8A;
    }
        body {
            background: linear-gradient(135deg, #1f82b6 0%, #112b4f 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h3 {
            color: #333;
            font-weight: 600;
        }
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .btn-login {
            background: linear-gradient(135deg, #1f82b6 0%, #112b4f 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            width: 100%;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
             <div class="logo-icon">
              <img 
                src="../company_logo.jpg" 
                alt="Prestigious Logo"
                style="
                    width:300px;
                    height:150px;
                    object-fit:auto;
                    border-radius:20px;
                    padding:20px;
                    background:#fff;
                    box-shadow:0 2px 8px rgba(0,0,0,0.15);
                    border:2px solid #112b4f;
                "
                >

        
        </div>
             <h3>Prverify Admin</h3>
             <p>Please login to access the dashboard</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" class="form-control" id="username" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>