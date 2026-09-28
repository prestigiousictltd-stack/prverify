<?php
require_once 'config/admin_config.php';
requireAdminLogin();

$conn = getDBConnection();

// Get statistics
$stats = [];

// Total users
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
$stats['total_users'] = $result->fetch_assoc()['total'];

// Total verifications
$result = $conn->query("SELECT SUM(nin_verifications) as nin, SUM(phone_verifications) as phone, SUM(demographic_verifications) as demo FROM users");
$verifications = $result->fetch_assoc();
$stats['total_verifications'] = array_sum($verifications);

// Total funds (sum of all account balances)
$result = $conn->query("SELECT SUM(balance) as total FROM accounts");
$stats['total_funds'] = $result->fetch_assoc()['total'] ?? 0;

// Recent transactions
$recent_transactions = $conn->query("
    SELECT t.*, u.email, u.full_name, u.org_name 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    ORDER BY t.created_at DESC 
    LIMIT 10
");

// Recent users
$recent_users = $conn->query("
    SELECT * FROM users 
    ORDER BY created_at DESC 
    LIMIT 10
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Murna Foundation Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* ===== Body ===== */
    body {
        background: #c0c0c0; /* silver */
        background: linear-gradient(135deg, #d3d3d3 0%, #c0c0c0 100%);
        min-height: 100vh;
        font-family: 'Segoe UI', Tahoma, sans-serif;
        color: #0a1a2f;
    }

    /* ===== Top Navigation (Dark Blue → Cyan) ===== */
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

    /* ===== Stat Cards (White) ===== */
    .stat-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 22px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
        border-top: 4px solid #00b4d8;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 10px 25px rgba(0, 180, 216, 0.35);
    }

    .stat-card h3,
    .stat-card h5 {
        color: #0a1a2f;
        font-weight: 600;
    }

    .stat-card p {
        color: #4a5568;
    }

    .stat-icon {
        font-size: 42px;
        opacity: 0.15;
        position: absolute;
        right: 20px;
        top: 20px;
        color: #00b4d8;
    }

    /* ===== Scrollbar (optional, nice touch) ===== */
    ::-webkit-scrollbar { width: 10px; }
    ::-webkit-scrollbar-track { background: #d3d3d3; }
    ::-webkit-scrollbar-thumb {
        background: linear-gradient(180deg, #0d3b66, #00b4d8);
        border-radius: 5px;
    }
    ::-webkit-scrollbar-thumb:hover { background: #00e5ff; }
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
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-users"></i> Users
                    </a>
                    <a class="nav-link" href="verifications.php">
                        <i class="fas fa-check-circle"></i> Verifications
                    </a>
                    <a class="nav-link" href="transactions.php">
                        <i class="fas fa-money-bill-wave"></i> Transactions
                    </a>
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-0">
                <nav class="navbar navbar-top navbar-expand-lg px-4">
                    <div class="container-fluid">
                        <span class="navbar-brand">Welcome, <?php echo $_SESSION['admin_fullname']; ?></span>
                        <div class="ms-auto">
                            <span class="badge bg-info"><?php echo $_SESSION['admin_role']; ?></span>
                        </div>
                    </div>
                </nav>
                
                <div class="p-4">
                    <h3>Dashboard Overview</h3>
                    
                    <!-- Statistics Cards -->
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="stat-card position-relative">
                                <h6>Total Users</h6>
                                <h3><?php echo number_format($stats['total_users']); ?></h3>
                                <i class="fas fa-users stat-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card position-relative">
                                <h6>Total Verifications</h6>
                                <h3><?php echo number_format($stats['total_verifications']); ?></h3>
                                <i class="fas fa-check-circle stat-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card position-relative">
                                <h6>Total Funds</h6>
                                <h3>₦<?php echo number_format($stats['total_funds'], 2); ?></h3>
                                <i class="fas fa-money-bill stat-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card position-relative">
                                <h6>Average Balance</h6>
                                <h3>₦<?php echo $stats['total_users'] > 0 ? number_format($stats['total_funds'] / $stats['total_users'], 2) : '0'; ?></h3>
                                <i class="fas fa-chart-line stat-icon"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Verification Distribution</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="verificationChart" height="250"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Recent Transactions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr><th>User</th><th>Amount</th><th>Status</th><th>Date</th></tr>
                                            </thead>
                                            <tbody>
                                                <?php while($trans = $recent_transactions->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($trans['full_name'] ?? $trans['org_name']); ?></td>
                                                    <td>₦<?php echo number_format($trans['amount'], 2); ?></td>
                                                    <td><span class="badge bg-<?php echo $trans['status'] == 'success' ? 'success' : ($trans['status'] == 'pending' ? 'warning' : 'danger'); ?>"><?php echo $trans['status']; ?></span></td>
                                                    <td><?php echo date('M d, H:i', strtotime($trans['created_at'])); ?></td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Users -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6>Recent Registered Users</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr><th>Name/Organization</th><th>Email</th><th>Phone</th><th>Type</th><th>Registered</th><th>Action</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php while($user = $recent_users->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['full_name'] ?? $user['org_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                            <td><span class="badge bg-info"><?php echo $user['account_type']; ?></span></td>
                                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                            <td><a href="user-details.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">View</a></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Verification Chart
        const ctx = document.getElementById('verificationChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['NIN Verifications', 'Phone Verifications', 'Demographic Verifications'],
                datasets: [{
                    data: [<?php echo $verifications['nin'] ?? 0; ?>, <?php echo $verifications['phone'] ?? 0; ?>, <?php echo $verifications['demo'] ?? 0; ?>],
                    backgroundColor: ['#667eea', '#764ba2', '#f093fb']
                }]
            }
        });
    </script>
</body>
</html>