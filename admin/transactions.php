<?php
require_once 'config/admin_config.php';
requireAdminLogin();

$conn = getDBConnection();

// Get transaction statistics
$stats = [];
$result = $conn->query("
    SELECT 
        type,
        COUNT(*) as count,
        SUM(amount) as total,
        status
    FROM transactions 
    GROUP BY type, status
");

while ($row = $result->fetch_assoc()) {
    $stats[$row['type']][$row['status']] = $row;
}

// Get all transactions
$transactions = $conn->query("
    SELECT t.*, u.email, u.full_name, u.org_name, a.account_number 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    LEFT JOIN accounts a ON u.id = a.user_id 
    ORDER BY t.created_at DESC 
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Murna Foundation Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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
                    <a class="nav-link active" href="transactions.php"><i class="fas fa-money-bill-wave"></i> Transactions</a>
                    <a class="nav-link" href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-0">
                <nav class="navbar navbar-top navbar-expand-lg px-4">
                    <div class="container-fluid">
                        <span class="navbar-brand">Transactions Management</span>
                        <div class="ms-auto">
                            <span class="badge bg-info"><?php echo $_SESSION['admin_role']; ?></span>
                        </div>
                    </div>
                </nav>
                
                <div class="p-4">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h6>Total Credits</h6>
                                <h3 class="text-success">₦<?php echo number_format($stats['credit']['success']['total'] ?? 0, 2); ?></h3>
                                <small><?php echo $stats['credit']['success']['count'] ?? 0; ?> successful transactions</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h6>Total Debits</h6>
                                <h3 class="text-danger">₦<?php echo number_format($stats['debit']['success']['total'] ?? 0, 2); ?></h3>
                                <small><?php echo $stats['debit']['success']['count'] ?? 0; ?> successful transactions</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h6>Pending</h6>
                                <h3 class="text-warning">₦<?php echo number_format(($stats['credit']['pending']['total'] ?? 0) + ($stats['debit']['pending']['total'] ?? 0), 2); ?></h3>
                                <small>Awaiting confirmation</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h6>Failed</h6>
                                <h3 class="text-danger">₦<?php echo number_format(($stats['credit']['failed']['total'] ?? 0) + ($stats['debit']['failed']['total'] ?? 0), 2); ?></h3>
                                <small>Failed transactions</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5>All Transactions</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="transactionsTable" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>User</th>
                                            <th>Account</th>
                                            <th>Type</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Reference</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($trans = $transactions->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo date('Y-m-d H:i:s', strtotime($trans['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($trans['full_name'] ?? $trans['org_name']); ?></td>
                                            <td><?php echo $trans['account_number'] ?? 'N/A'; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $trans['type'] == 'credit' ? 'success' : 'danger'; ?>">
                                                    <?php echo strtoupper($trans['type']); ?>
                                                </span>
                                            </td>
                                            <td>₦<?php echo number_format($trans['amount'], 2); ?></td>
                                            <td><?php echo str_replace('_', ' ', ucfirst($trans['method'])); ?></td>
                                            <td><small><?php echo $trans['reference']; ?></small></td>
                                            <td>
                                                <span class="badge bg-<?php echo $trans['status'] == 'success' ? 'success' : ($trans['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                                    <?php echo $trans['status']; ?>
                                                </span>
                                            </td>
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
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#transactionsTable').DataTable({
                "order": [[0, "desc"]],
                "pageLength": 25
            });
        });
    </script>
</body>
</html>