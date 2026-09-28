<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$user_id = $_SESSION['user_id'];

// Fetch user
$stmt = $db->prepare("SELECT u.*, a.balance, a.account_number FROM users u JOIN accounts a ON u.id = a.user_id WHERE u.id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Recent transactions
$txn_stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 8");
$txn_stmt->bind_param('i', $user_id);
$txn_stmt->execute();
$transactions = $txn_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Recent verifications
$ver_stmt = $db->prepare("SELECT * FROM verification_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$ver_stmt->bind_param('i', $user_id);
$ver_stmt->execute();
$verifications = $ver_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Stats
$total_verifications = count($verifications);
$ver_count_stmt = $db->prepare("SELECT COUNT(*) as cnt FROM verification_logs WHERE user_id = ?");
$ver_count_stmt->bind_param('i', $user_id);
$ver_count_stmt->execute();
$ver_count = $ver_count_stmt->get_result()->fetch_assoc()['cnt'];


// Fetch current verification costs from settings
$cost_stmt = $db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('nin_verification_cost', 'phone_verification_cost', 'demographic_verification_cost')");
$cost_stmt->execute();
$cost_result = $cost_stmt->get_result();
$costs = ['nin' => 100, 'phone' => 100, 'demographic' => 100]; // fallback defaults
while ($row = $cost_result->fetch_assoc()) {
    switch ($row['setting_key']) {
        case 'nin_verification_cost': $costs['nin'] = (float)$row['setting_value']; break;
        case 'phone_verification_cost': $costs['phone'] = (float)$row['setting_value']; break;
        case 'demographic_verification_cost': $costs['demographic'] = (float)$row['setting_value']; break;
    }
}
$min_required = min($costs); // smallest cost among the three


$display_name = $user['account_type'] === 'individual' ? $user['full_name'] : $user['org_name'];
$balance = floatval($user['balance']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Murna Foundation</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
    :root {
        /* ── Silver palette ── */
        --silver: #C0C0C0;
        --silver-light: #E8E8E8;
        --silver-dark: #8A8A8A;
        --silver-mid: #D4D4D4;

        /* ── Dark blue palette ── */
        --blue-deep: #0A1A2F;
        --blue-dark: #112B4F;
        --blue-mid: #1E3A5F;
        --blue-soft: #2A4A6F;

        /* ── Neutrals ── */
        --black: #0B0F14;
        --black-soft: #141A22;
        --card: #1A2432;
        --card-light: #1F2B3C;
        --border: rgba(192, 192, 192, 0.18);
        --text: #0A1A2F;
        --text-muted: #9AA8B9;
        --white: #FFFFFF;
        --input-bg: rgba(192, 192, 192, 0.06);

        /* ── Accent (silver as primary) ── */
        --accent: #C0C0C0;
        --accent-light: #D9D9D9;
        --accent-dark: #8A8A8A;

        /* ── High-contrast text tokens ── */
        --text-on-dark: #F5F8FC;        /* near-white on dark backgrounds */
        --text-on-dark-muted: #C8D4E4;  /* brighter muted for dark backgrounds */
        --text-on-light: #0A1A2F;       /* deep navy on silver */
        --text-on-light-muted: #1E3A5F; /* dark blue muted on silver */
        --accent-bright: #F0F4F8;       /* brightest silver for headings */
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: 'DM Sans', sans-serif;
        background: var(--black);
        color: var(--text-on-dark);
        min-height: 100vh;
        display: flex;
    }

    /* ── Sidebar ── */
    /* ══════════════════════════════════════════
   TOP NAVIGATION BAR
   ══════════════════════════════════════════ */
.topnav {
    position: fixed;
    top: 0; left: 0; right: 0;
    height: 72px;
    background: var(--blue-deep);
    border-bottom: 1px solid rgba(192, 192, 192, 0.15);
    z-index: 1000;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
}

.topnav-inner {
    height: 100%;
    max-width: 1600px;
    margin: 0 auto;
    padding: 0 28px;
    display: flex;
    align-items: center;
    gap: 32px;
}

/* ── Brand ── */
.topnav-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    flex-shrink: 0;
}

.topnav-brand .logo-icon {
    width: 100px; height: 46px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.topnav-brand .brand-img {
    width: 100px;
    height: 46px;
    object-fit: cover;
    border-radius: 12px;
    padding: 4px;
    background: #ffffff;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.25);
    border: 2px solid var(--silver);
    display: block;
}

.topnav-brand .brand-text {
    font-family: 'Syne', sans-serif;
    font-size: 14px;
    font-weight: 700;
    color: var(--white);
    line-height: 1.25;
    white-space: nowrap;
}

.topnav-brand .brand-text small {
    display: block;
    font-size: 11px;
    font-weight: 500;
    color: var(--silver-light);
    opacity: 0.95;
    letter-spacing: 0.5px;
}

/* ── Nav Links ── */
.topnav-links {
    display: flex;
    align-items: center;
    gap: 6px;
    flex: 1;
    min-width: 0;
}

.topnav-links .nav-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 16px;
    border-radius: 10px;
    color: #C8D4E4;
    text-decoration: none;
    font-size: 13.5px;
    font-weight: 600;
    white-space: nowrap;
    transition: all 0.2s;
    border: 1px solid transparent;
}

.topnav-links .nav-link svg {
    width: 17px;
    height: 17px;
    fill: currentColor;
    flex-shrink: 0;
}

.topnav-links .nav-link:hover {
    background: rgba(192, 192, 192, 0.12);
    color: var(--white);
    border-color: rgba(192, 192, 192, 0.2);
}

.topnav-links .nav-link.active {
    background: linear-gradient(135deg, var(--silver), var(--silver-dark));
    color: var(--blue-deep);
    border-color: var(--silver-light);
    box-shadow: 0 4px 14px rgba(192, 192, 192, 0.25);
}

.topnav-links .nav-link.active svg {
    fill: var(--blue-deep);
}

/* ── User Badge + Logout ── */
.topnav-user {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

.topnav-user .user-badge {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 14px 6px 6px;
    background: rgba(192, 192, 192, 0.08);
    border: 1px solid rgba(192, 192, 192, 0.18);
    border-radius: 30px;
}

.topnav-user .user-avatar {
    width: 34px; height: 34px;
    background: linear-gradient(135deg, var(--silver), var(--blue-mid));
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 14px;
    color: var(--blue-deep);
    flex-shrink: 0;
}

.topnav-user .user-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
    line-height: 1.2;
}

.topnav-user .user-name {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--white);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 140px;
}

.topnav-user .user-type {
    font-size: 10px;
    font-weight: 600;
    color: var(--silver-light);
    text-transform: uppercase;
    letter-spacing: 0.6px;
}

.logout-btn {
    width: 90px; height: 40px;
    display: flex; align-items: center; justify-content: center;
    background: rgba(220, 53, 69, 0.12);
    border: 1px solid rgba(220, 53, 69, 0.3);
    border-radius: 10px;
    color: #FF8A8A;
    text-decoration: none;
    transition: all 0.2s;
}

.logout-btn svg {
    width: 18px;
    height: 18px;
    fill: currentColor;
}

.logout-btn:hover {
    background: rgba(220, 53, 69, 0.22);
    color: #FFFFFF;
    border-color: rgba(220, 53, 69, 0.55);
}

/* ── Main content offset (replaces margin-left) ── */
.main {
    margin-top: 72px;   /* was: margin-left: 260px */
    margin-left: 0;
    flex: 1;
    padding: 32px;
    min-height: calc(100vh - 72px);
    background: linear-gradient(145deg, #E8E8E8 0%, #C0C0C0 100%);
}

/* ── Responsive: collapse nav links into second row on tablets ── */
@media (max-width: 1024px) {
    .topnav-inner {
        padding: 0 18px;
        gap: 18px;
    }
    .topnav-links .nav-link {
        padding: 8px 12px;
        font-size: 13px;
    }
    .topnav-user .user-name {
        max-width: 90px;
    }
}

@media (max-width: 768px) {
    .topnav {
        height: auto;
    }
    .topnav-inner {
        flex-wrap: wrap;
        padding: 12px 16px;
        gap: 10px;
    }
    .topnav-links {
        order: 3;
        width: 100%;
        overflow-x: auto;
        padding-bottom: 4px;
        -webkit-overflow-scrolling: touch;
    }
    .topnav-links .nav-link {
        flex-shrink: 0;
    }
    .topnav-user .user-info {
        display: none;
    }
    .main {
        margin-top: 128px; /* taller navbar on mobile */
        padding: 20px;
    }
}
    /* ── Main Content (SILVER background) ── */
   

    .page-header {
        margin-bottom: 32px;
    }

    .page-title {
        font-family: 'Syne', sans-serif;
        font-size: 28px;
        font-weight: 800;
        color: var(--blue-deep); /* deep navy on silver — maximum contrast */
        margin-bottom: 4px;
    }

    .page-subtitle {
        font-size: 14px;
        color: var(--blue-dark); /* dark blue on silver */
        font-weight: 500;
    }

    /* ── Balance Card (DARK blue background) ── */
    .balance-card {
        background: #1F82B6;
        border-radius: 20px;
        padding: 36px;
        margin-bottom: 28px;
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(192, 192, 192, 0.25);
           box-shadow:0 4px 12px rgba(0,0,0,0.15);
                    
    }

    .balance-card::before {
        content: '';
        position: absolute;
        top: -60px; right: -60px;
        width: 240px; height: 240px;
        background: rgba(192, 192, 192, 0.12);
        border-radius: 50%;
    }

    .balance-card::after {
        content: '';
        position: absolute;
        bottom: -40px; left: 40%;
        width: 180px; height: 180px;
        background: rgba(17, 43, 79, 0.4);
        border-radius: 50%;
    }

    .balance-content { position: relative; z-index: 2; }

    .balance-label {
        font-size: 13px;
        font-weight: 700;
        color: var(--silver-light); /* bright silver on dark */
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-bottom: 12px;
    }

    .balance-amount {
        font-family: 'Syne', sans-serif;
        font-size: 52px;
        font-weight: 800;
        color: var(--white);
        margin-bottom: 24px;
        line-height: 1;
        text-shadow: 0 2px 12px rgba(0, 0, 0, 0.4);
    }

    .balance-amount span {
        font-size: 24px;
        font-weight: 500;
        opacity: 0.9;
        color: var(--silver-light);
    }

    .balance-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .btn-credit {
        padding: 12px 24px;
        background: linear-gradient(135deg, var(--silver), var(--silver-dark));
        color: var(--blue-deep);
        border: none;
        border-radius: 10px;
        font-family: 'Syne', sans-serif;
        font-size: 14px;
        font-weight: 800;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 14px rgba(192, 192, 192, 0.3);
    }

    .btn-credit:hover {
        background: linear-gradient(135deg, var(--white), var(--silver-light));
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(192, 192, 192, 0.4);
    }

    .btn-outline-white {
        padding: 12px 24px;
        background: rgba(255, 255, 255, 0.12);
        color: var(--white);
        border: 1px solid rgba(192, 192, 192, 0.4);
        border-radius: 10px;
        font-family: 'Syne', sans-serif;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s;
    }

    .btn-outline-white:hover {
        background: rgba(192, 192, 192, 0.2);
        border-color: rgba(192, 192, 192, 0.6);
        color: var(--white);
    }

    .balance-meta {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: var(--silver-light); /* bright on dark */
        margin-top: 16px;
        font-weight: 500;
    }

    /* ── Stats Grid (dark cards on silver bg) ── */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 28px;
    }

    .stat-card {
        background: var(--card);
        border-radius: 16px;
        padding: 24px;
        border: 1px solid rgba(192, 192, 192, 0.2);
        transition: border-color 0.2s, transform 0.2s;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
    }

    .stat-card:hover {
        border-color: rgba(192, 192, 192, 0.45);
        transform: translateY(-2px);
    }

    .stat-label {
        font-size: 12px;
        font-weight: 700;
        color: var(--silver-light); /* bright on dark card */
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 10px;
    }

    .stat-value {
        font-family: 'Syne', sans-serif;
        font-size: 32px;
        font-weight: 800;
        color: var(--white);
        margin-bottom: 4px;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    .stat-note {
        font-size: 12px;
        color: #C8D4E4; /* bright muted on dark card */
        font-weight: 500;
    }

    /* ── Verify CTA (dark card on silver bg) ── */
    .verify-cta {
        background: #1F82B6;
        border-radius: 20px;
        padding: 28px;
        margin-bottom: 28px;
        border: 1px solid rgba(192, 192, 192, 0.2);
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
    }

    .verify-cta h3 {
        font-family: 'Syne', sans-serif;
        font-size: 20px;
        font-weight: 700;
        color: var(--white);
        margin-bottom: 8px;
    }

    .verify-cta p {
        font-size: 14px;
        color: #C8D4E4; /* bright muted on dark card */
        margin-bottom: 20px;
        font-weight: 500;
    }

    .verify-options {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }

    .verify-option {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        padding: 20px 14px;
        background: #1F82B6;
        border: 2px solid #C8D4E4;
        border-radius: 14px;
        text-decoration: none;
        color: var(--white);
        transition: all 0.25s;
        text-align: center;
    }

    .verify-option:hover {
        border-color: var(--silver);
        background: rgba(192, 192, 192, 0.1);
        color: var(--white);
        transform: translateY(-2px);
    }

    .verify-option-icon {
        width: 44px; height: 44px;
        background: rgba(192, 192, 192, 0.18);
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
    }

    .verify-option-icon svg {
        width: 22px; height: 22px;
        fill: var(--silver-light);
    }

    .verify-option-label {
        font-family: 'Syne', sans-serif;
        font-size: 14px;
        font-weight: 700;
        color: var(--white);
    }

    .verify-option-cost {
        font-size: 12px;
        color: var(--silver-light); /* bright on dark */
        font-weight: 600;
    }

    /* ── Tables (dark card on silver bg) ── */
    .section-title {
        font-family: 'Syne', sans-serif;
        font-size: 18px;
        font-weight: 700;
        color: var(--white); /* deep navy on silver */
        margin-bottom: 16px;
    }

    .table-card {
        background: #1F82B6;
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid rgba(192, 192, 192, 0.2);
        margin-bottom: 28px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
    }

    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 24px;
        border-bottom: 1px solid rgba(192, 192, 192, 0.15);
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead th {
        background: rgba(192, 192, 192, 0.08);
        padding: 12px 20px;
        text-align: left;
        font-size: 11px;
        font-weight: 800;
        color: var(--silver-light); /* bright on dark header */
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    tbody td {
        padding: 14px 20px;
        font-size: 14px;
        border-bottom: 1px solid rgba(192, 192, 192, 0.1);
        color: var(--white); /* pure white on dark card */
        font-weight: 500;
    }

    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover td { background: rgba(192, 192, 192, 0.06); }

    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    /* High-contrast badges */
    .badge-success {
        background: rgba(23, 214, 87, 0.25);
        color: #FFFFFF;
        border: 1px solid rgba(192, 192, 192, 0.5);
    }
    .badge-pending {
        background: rgba(255, 193, 7, 0.25);
        color: #FFE082;
        border: 1px solid rgba(255, 193, 7, 0.5);
    }
    .badge-failed {
        background: rgba(220, 53, 69, 0.25);
        color: #FF8A8A;
        border: 1px solid rgba(220, 53, 69, 0.5);
    }
    .badge-credit {
        background: rgba(192, 192, 192, 0.25);
        color: #FFFFFF;
        border: 1px solid rgba(192, 192, 192, 0.5);
    }
    .badge-debit {
        background: rgba(220, 53, 69, 0.25);
        color: #FF8A8A;
        border: 1px solid rgba(220, 53, 69, 0.5);
    }

    .empty-state {
        text-align: center;
        padding: 40px;
        color: #C8D4E4; /* bright muted on dark card */
        font-size: 14px;
        font-weight: 500;
    }

    @media (max-width: 1024px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .verify-options { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
        .sidebar { transform: translateX(-100%); }
        .main { margin-left: 0; padding: 20px; }
    }
    .auth-right {
      color: var(--text-on-light);
      flex: 1;
      display: flex;
      align-items: flex-start;
      justify-content: center;
      padding: 40px 32px;
      overflow-y: auto;
      background: #D9D9D9; /* solid silver base */
      background-image: linear-gradient(145deg, #E8E8E8 0%, #C0C0C0 100%);
    }
</style>
</head>
<body>

<!-- TOP NAVIGATION BAR -->
<nav class="topnav">
    <div class="topnav-inner">
        <!-- Brand -->
        <a href="dashboard.php" class="topnav-brand">
            <div class="logo-icon">
                <img 
                    src="company_logo.jpg" 
                    alt="Murna Logo"
                    class="brand-img"
                >
            </div>
            <div class="brand-text">
                Prestigious ICT Investment LTD 
                <small>NIN Portal</small>
            </div>
        </a>

        <!-- Primary Nav Links -->
        <div class="topnav-links">
            <a href="dashboard.php" class="nav-link active">
                <svg viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>
            <a href="verify.php" class="nav-link">
                <svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                NIN Verification
            </a>
            <a href="payment.php" class="nav-link">
                <svg viewBox="0 0 24 24"><path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Fund Wallet
            </a>
            <a href="dashboard.php#history" class="nav-link">
                <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                History
            </a>
        </div>

        <!-- User Badge + Logout -->
        <div class="topnav-user">
            <div class="user-badge">
                <div class="user-avatar"><?= strtoupper(substr($display_name, 0, 1)) ?></div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars(substr($display_name, 0, 22)) ?></div>
                    <div class="user-type"><?= $user['account_type'] ?></div>
                </div>
            </div>
            <a href="logout.php" class="logout-btn" title="Log out">
                Log out<svg viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </a>
        </div>
    </div>
</nav>

<!-- MAIN CONTENT -->
<main class="main">
    <div class="page-header">
        <div class="page-title">Good <?= date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening') ?>, <?= htmlspecialchars(explode(' ', $display_name)[0]) ?></div>
        <div class="page-subtitle">Welcome to your Prestigious NIN Verification dashboard &mdash; <?= date('l, d F Y') ?></div>
    </div>

   

   <!-- BALANCE CARD -->
    <div class="balance-card">
        <div class="balance-content">
            <div class="balance-label">Wallet Balance</div>
            <div class="balance-amount">
                <span>NGN</span> <?= number_format($balance, 2) ?>
            </div>
            <div class="balance-actions">
                <a href="payment.php" class="btn-credit">
                    + Fund Wallet
                </a>
               <?php if ($balance >= $min_required): ?>
                    <a href="verify.php" class="btn-outline-white">Start Verification</a>
                <?php else: ?>
                    <span style="font-size:13px; color:rgba(255,255,255,0.5); line-height:1.4; max-width:260px;">
                        Fund your wallet with at least NGN <?= number_format($costs['nin'], 2) ?> to start verifying NINs.
                    </span>
                <?php endif; ?>
            </div>
            <div class="balance-meta">
                Account No: <?= htmlspecialchars($user['account_number']) ?> &bull; <?= strtoupper($user['account_type']) ?> Account
            </div>
        </div>
    </div>

    <!-- VERIFY CTA -->
    <div class="verify-cta">
        <h3>Perform NIN Verification</h3>
       <p>Verification costs vary by method (NIN, Phone, Demographic) and are deducted from your wallet balance.</p>
        <div class="verify-options">
            <a href="verify.php?type=nin" class="verify-option">
                <div class="verify-option-icon">
                    <svg viewBox="0 0 24 24"><path d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/></svg>
                </div>
                <div class="verify-option-label">By NIN Number</div>
                <div class="verify-option-cost"><div class="verify-option-cost">NGN <?= number_format($costs['nin']) ?> per query</div>
</div>
            </a>
          <a href="verify.php?type=phone" class="verify-option">
                <div class="verify-option-icon">
                    <svg viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                </div>
                <div class="verify-option-label">By Phone Number</div>
                <div class="verify-option-cost"><div class="verify-option-cost">NGN <?= number_format($costs['phone']) ?> per query</div></div>
            </a>
            <a href="verify.php?type=demographic" class="verify-option">
                <div class="verify-option-icon">
                    <svg viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div class="verify-option-label">By Demographics</div>
                <div class="verify-option-cost"><div class="verify-option-cost">NGN <?= number_format($costs['demographic']) ?> per query</div></div>
            </a> 
        </div>
    </div>

    <!-- TRANSACTIONS -->
    <div class="table-card" id="history">
        <div class="table-header">
            <div class="section-title" style="margin:0;">Recent Transactions</div>
        </div>
        <?php if (empty($transactions)): ?>
            <div class="empty-state">No transactions yet. Fund your wallet to get started.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $txn): ?>
                <tr>
                    <td style="font-family:monospace; font-size:12px;"><?= htmlspecialchars($txn['reference']) ?></td>
                    <td><span class="badge badge-<?= $txn['type'] ?>"><?= strtoupper($txn['type']) ?></span></td>
                    <td>NGN <?= number_format($txn['amount'], 2) ?></td>
                    <td><?= ucfirst($txn['method']) ?></td>
                    <td><span class="badge badge-<?= $txn['status'] ?>"><?= strtoupper($txn['status']) ?></span></td>
                    <td style="font-size:12px; color:var(--text-muted);"><?= date('d M Y H:i', strtotime($txn['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- RECENT VERIFICATIONS -->
    <div class="table-card">
        <div class="table-header">
            <div class="section-title" style="margin:0;">Recent Verifications</div>
        </div>
        <?php if (empty($verifications)): ?>
            <div class="empty-state">No verifications performed yet.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Query</th>
                    <th>Cost</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($verifications as $v): ?>
                <tr>
                    <td><?= ucfirst($v['verification_type']) ?></td>
                    <td style="font-family:monospace; font-size:13px;"><?= htmlspecialchars(substr($v['query_input'], 0, 30)) ?>...</td>
                    <td>NGN <?= number_format($v['cost'], 2) ?></td>
                    <td><span class="badge badge-<?= $v['status'] === 'success' ? 'success' : 'failed' ?>"><?= strtoupper($v['status']) ?></span></td>
                    <td style="font-size:12px; color:var(--text-muted);"><?= date('d M Y H:i', strtotime($v['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
 <div class="auth-right">Contact us at example@gmail.com</div>
</main>
</body>
</html>
