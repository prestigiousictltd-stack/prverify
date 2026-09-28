<?php
require_once 'config/admin_config.php';
requireAdminLogin();

$conn = getDBConnection();
$message = '';
$error = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nin_cost = $_POST['nin_cost'];
    $phone_cost = $_POST['phone_cost'];
    $demographic_cost = $_POST['demographic_cost'];
     
    // Update settings
    $settings = [
        'nin_verification_cost' => $nin_cost,
        'phone_verification_cost' => $phone_cost,
        'demographic_verification_cost' => $demographic_cost
    ];
    
    $success = true;
    foreach ($settings as $key => $value) {
        $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $value, $key);
        if (!$stmt->execute()) {
            $success = false;
            $error = "Failed to update $key";
            break;
        }
        $stmt->close();
    }
    
    if ($success) {
        $message = "Settings updated successfully!";
        logAdminActivity($_SESSION['admin_id'], 'SETTINGS_UPDATE', 'Updated verification costs and bank settings');
    }
}

// Get current settings
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Murna Foundation Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
     .navbar-top {
        background: linear-gradient(90deg, #0a1a2f 0%, #0d3b66 50%, #00b4d8 100%);
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.35);
      
    }
    .navbar-top .navbar-brand,
    .navbar-top .nav-link {
        color: #e0f7ff;
        font-weight: 500;
        transition: color 0.3s ease;
    }
    .navbar-top .nav-link:hover {
        color: #00e5ff;
        text-shadow: 0 0 8px rgba(0, 229, 255, 0.7);
    }

    /* ===== Sidebar (Dark Blue → Cyan gradient) ===== */
    .sidebar {
        min-height: 100vh;
        background: linear-gradient(180deg, #0a1a2f 0%, #0d3b66 60%, #00b4d8 100%);
        box-shadow: 3px 0 15px rgba(0, 0, 0, 0.25);
        
    }

    .sidebar .nav-link {
        color: rgba(224, 247, 255, 0.85);
        padding: 12px 20px;
        margin: 6px 10px;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .sidebar .nav-link:hover {
        background: rgba(0, 229, 255, 0.18);
        color: #00e5ff;
        transform: translateX(5px);
        box-shadow: inset 3px 0 0 #00e5ff;
    }

    .sidebar .nav-link.active {
        background: linear-gradient(90deg, rgba(0, 180, 216, 0.4), rgba(0, 229, 255, 0.15));
        color: #ffffff;
        border-left: 4px solid #00e5ff;
        box-shadow: 0 0 12px rgba(0, 229, 255, 0.5);
    }

    .sidebar .nav-link i {
        margin-right: 12px;
        color: #00e5ff;
    }
  </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0 sidebar">
                <div class="text-center py-4">
                    <div class="logo-icon">
              <img 
                src="../company_logo.jpg" 
                alt="Company Logo"
                style="
                    width:150px;
                    height:100px;
                    object-fit:auto;
                    border-radius:20px;
                    padding:9px;
                    background:#fff;
                    box-shadow:0 2px 8px rgba(0,0,0,0.15);
                    border:2px solid #112b4f;
                "
                >
        
        
        </div>
            <h5 class="text-white mt-2">PRverify Service</h5>
                    <small class="text-white-50">Admin Panel</small>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a class="nav-link" href="users.php"><i class="fas fa-users"></i> Users</a>
                    <a class="nav-link" href="verifications.php"><i class="fas fa-check-circle"></i> Verifications</a>
                    <a class="nav-link" href="transactions.php"><i class="fas fa-money-bill-wave"></i> Transactions</a>
                    <a class="nav-link active" href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-0">
                <nav class="navbar navbar-top navbar-expand-lg px-4">
                    <div class="container-fluid">
                        <span class="navbar-brand">System Settings</span>
                        <div class="ms-auto">
                            <span class="badge bg-info"><?php echo $_SESSION['admin_role']; ?></span>
                        </div>
                    </div>
                </nav>
                
                <div class="p-4">
                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="settings-card">
                                    <h5><i class="fas fa-shield-alt"></i> Verification Costs</h5>
                                    <hr>
                                    <div class="mb-3">
                                        <label for="nin_cost" class="form-label">NIN Verification Cost (₦)</label>
                                        <input type="number" class="form-control" id="nin_cost" name="nin_cost" 
                                               value="<?php echo $settings['nin_verification_cost'] ?? 100; ?>" step="0.01" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="phone_cost" class="form-label">Phone Verification Cost (₦)</label>
                                        <input type="number" class="form-control" id="phone_cost" name="phone_cost" 
                                               value="<?php echo $settings['phone_verification_cost'] ?? 100; ?>" step="0.01" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="demographic_cost" class="form-label">Demographic Verification Cost (₦)</label>
                                        <input type="number" class="form-control" id="demographic_cost" name="demographic_cost" 
                                               value="<?php echo $settings['demographic_verification_cost'] ?? 100; ?>" step="0.01" required>
                                    </div>
                                </div>
                            </div>
                            
                          
                       
                            <div class="col-md-6">
                                <div class="settings-card">
                                    <h5><i class="fas fa-chart-bar"></i> System Information</h5>
                                    <hr>
                                    <p><strong>Total Users:</strong> 
                                        <?php
                                        $conn2 = getDBConnection();
                                        $result = $conn2->query("SELECT COUNT(*) as total FROM users");
                                        echo $result->fetch_assoc()['total'];
                                        $conn2->close();
                                        ?>
                                    </p>
                                    <p><strong>Admin Last Login:</strong> 
                                        <?php
                                        $conn2 = getDBConnection();
                                        $stmt = $conn2->prepare("SELECT last_login FROM admin_users WHERE id = ?");
                                        $stmt->bind_param("i", $_SESSION['admin_id']);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        $admin = $result->fetch_assoc();
                                        echo $admin['last_login'] ? date('F d, Y H:i:s', strtotime($admin['last_login'])) : 'First login';
                                        $conn2->close();
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Save All Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>