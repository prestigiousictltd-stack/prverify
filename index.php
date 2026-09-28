<?php
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
   // header('Location: dashboard.php');
   header('Location: dashboard.php');
   
    exit();
}

$page = $_GET['page'] ?? 'login';
$error = '';
$success = '';

// ─── Handle Login ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['account_type'] === 'individual' ? $user['full_name'] : $user['org_name'];
            $_SESSION['account_type'] = $user['account_type'];
            $_SESSION['email'] = $user['email'];
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

// ─── Handle Individual Registration ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_individual') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $nin_input = $_POST['nin_input'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($full_name) || empty($email) || empty($password)|| empty($nin_input)) {
        $error = 'Please fill all required fields.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $db = getDB();
        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $db->begin_transaction();
            try {
                $stmt = $db->prepare("INSERT INTO users (account_type, full_name, email, phone, password, nin_input) VALUES ('individual', ?, ?, ?, ?,?)");
                $stmt->bind_param('sssss', $full_name, $email, $phone, $hashed, $nin_input);
                $stmt->execute();
                $user_id = $db->insert_id;

                $acc_num = generateAccountNumber();
                $stmt2 = $db->prepare("INSERT INTO accounts (user_id, account_number, balance) VALUES (?, ?, 0.00)");
                $stmt2->bind_param('is', $user_id, $acc_num);
                $stmt2->execute();

                $db->commit();
                $success = 'Account created successfully! You can now log in.';
                $page = 'login';
            } catch (Exception $e) {
                $db->rollback();
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

// ─── Handle Corporate Registration ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_corporate') {
    $org_name = trim($_POST['org_name'] ?? '');
    $rc_number = trim($_POST['rc_number'] ?? '');
    $org_address = trim($_POST['org_address'] ?? '');
    $org_state = trim($_POST['org_state'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($org_name) || empty($email) || empty($password) || empty($contact_person)) {
        $error = 'Please fill all required fields.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $db = getDB();
        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $db->begin_transaction();
            try {
                $stmt = $db->prepare("INSERT INTO users (account_type, org_name, rc_number, org_address, org_state, contact_person, contact_phone, email, password) VALUES ('corporate', ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('ssssssss', $org_name, $rc_number, $org_address, $org_state, $contact_person, $contact_phone, $email, $hashed);
                $stmt->execute();
                $user_id = $db->insert_id;

                $acc_num = generateAccountNumber();
                $stmt2 = $db->prepare("INSERT INTO accounts (user_id, account_number, balance) VALUES (?, ?, 0.00)");
                $stmt2->bind_param('is', $user_id, $acc_num);
                $stmt2->execute();

                $db->commit();
                $success = 'Corporate account created! You can now log in.';
                $page = 'login';
            } catch (Exception $e) {
                $db->rollback();
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$nigerian_states = [
    'Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno',
    'Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo',
    'Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos',
    'Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers',
    'Sokoto','Taraba','Yobe','Zamfara'
];
?>
<!DOCTYPE html>
<html lang="en">
    <style>
    /* ── Silver & Dark Blue Theme ── */
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

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--black);
      min-height: 100vh;
      display: flex;
      overflow-x: hidden;
      color: var(--text);
    }

    /* ── Left Panel ── */
    .auth-left {
      width: 42%;
      background: linear-gradient(145deg, var(--blue-dark) 0%, #1f82b6 60%, #060E18 100%);
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 50px 48px;
      overflow: hidden;
      flex-shrink: 0;
    }

    .auth-left::before {
      content: '';
      position: absolute;
      top: -100px; right: -100px;
      width: 400px; height: 400px;
      background: radial-gradient(circle, rgba(192,192,192,0.12) 0%, transparent 70%);
      border-radius: 50%;
    }

    .auth-left::after {
      content: '';
      position: absolute;
      bottom: -80px; left: -80px;
      width: 350px; height: 350px;
      background: radial-gradient(circle, rgba(192,192,192,0.08) 0%, transparent 70%);
      border-radius: 50%;
    }

    .brand {
      position: relative; z-index: 2;
    }

    .brand-logo {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 10px;
    }

    .logo-icon {
      width: 52px; height: 52px;
      background: linear-gradient(135deg, var(--silver), var(--silver-dark));
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Syne', sans-serif;
      font-size: 22px; font-weight: 800;
      color: var(--blue-deep);
      box-shadow: 0 8px 24px rgba(192,192,192,0.2);
      flex-shrink: 0;
    }

    .brand-name {
      font-family: 'Syne', sans-serif;
      font-size: 18px;
      font-weight: 700;
      line-height: 1.2;
      color: var(--white);
      margin-bottom: 20px;
    }

    .brand-name span {
      display: block;
      font-size: 12px;
      font-weight: 400;
      color: var(--silver-light);
      letter-spacing: 2px;
      text-transform: uppercase;
    }

    .brand-hero h1 {
      font-family: 'Syne', sans-serif;
      font-size: 42px;
      font-weight: 800;
      line-height: 1.15;
      color: var(--white);
      margin-bottom: 20px;
      text-shadow: 0 2px 8px rgba(0,0,0,0.4);
    }

    .brand-hero h1 em {
      font-style: normal;
      color: var(--silver-light);
      text-shadow: 0 0 12px rgba(192,192,192,0.3);
    }

    .brand-hero p {
      font-size: 16px;
      line-height: 1.7;
      color: #B8C7DC; /* lighter, higher contrast */
      max-width: 340px;
    }

    .features {
      position: relative; z-index: 2;
      display: flex;
      flex-direction: column;
      gap: 14px;
    }

    .feature-item {
      display: flex;
      align-items: center;
      gap: 14px;
      background: rgba(192,192,192,0.06);
      border: 1px solid rgba(192,192,192,0.2);
      border-radius: 12px;
      padding: 14px 18px;
    }

    .feature-icon {
      width: 36px; height: 36px;
      background: rgba(192,192,192,0.15);
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }

    .feature-icon svg {
      width: 18px; height: 18px;
      fill: var(--silver-light);
    }

    .feature-text {
      font-size: 14px;
      color: #E8F0FE; /* bright, high contrast */
      font-weight: 500;
      text-shadow: 0 1px 2px rgba(0,0,0,0.3);
    }

    /* ── Right Panel (SILVER BACKGROUND) ── */
    .auth-right {
      flex: 1;
      display: flex;
      align-items: flex-start;
      justify-content: center;
      padding: 40px 32px;
      overflow-y: auto;
      background: #D9D9D9; /* solid silver base */
      background-image: linear-gradient(145deg, #E8E8E8 0%, #C0C0C0 100%);
    }

    .auth-box {
      width: 100%;
      max-width: 520px;
    }

    .auth-tabs {
      display: flex;
      gap: 4px;
      background: rgba(0,0,0,0.06);
      border: 1px solid rgba(0,0,0,0.08);
      border-radius: 14px;
      padding: 6px;
      margin-bottom: 32px;
    }

    .auth-tab {
      flex: 1;
      padding: 12px;
      text-align: center;
      font-size: 14px;
      font-weight: 600;
      font-family: 'Syne', sans-serif;
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.25s;
      color: #1A2432; /* dark for silver background */
      text-decoration: none;
    }

    .auth-tab.active {
      background: linear-gradient(135deg, #112B4F, #1E3A5F);
      color: #FFFFFF;
      box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    }

    .auth-heading {
      font-family: 'Syne', sans-serif;
      font-size: 28px;
      font-weight: 800;
      color: #0A1A2F; /* deep blue for contrast */
      margin-bottom: 6px;
    }

    .auth-subheading {
      font-size: 14px;
      color: #2A3A4F; /* dark enough for silver */
      margin-bottom: 28px;
      font-weight: 500;
    }

    /* Register Type Toggle */
    .reg-type-tabs {
      display: flex;
      gap: 12px;
      margin-bottom: 28px;
    }

    .reg-type-btn {
      flex: 1;
      padding: 14px;
      border-radius: 12px;
      border: 2px solid rgba(0,0,0,0.12);
      background: transparent;
      color: #1A2A3F;
      cursor: pointer;
      font-family: 'Syne', sans-serif;
      font-size: 14px;
      font-weight: 600;
      transition: all 0.25s;
      text-align: center;
    }

    .reg-type-btn.active {
      border-color: #112B4F;
      color: #112B4F;
      background: rgba(17, 43, 79, 0.08);
    }

    .reg-type-btn .btn-label {
      display: block;
      font-size: 12px;
      font-weight: 400;
      color: inherit;
      opacity: 0.7;
      margin-top: 3px;
    }

    /* Form Styles */
    .form-group {
      margin-bottom: 18px;
    }

    label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: #1A2A3F; /* dark for silver bg */
      margin-bottom: 8px;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }

    input, select, textarea {
      width: 100%;
      padding: 14px 16px;
      background: rgba(255,255,255,0.85);
      border: 1px solid rgba(0,0,0,0.1);
      border-radius: 10px;
      color: #0A1A2F;
      font-family: 'DM Sans', sans-serif;
      font-size: 15px;
      outline: none;
      transition: border-color 0.25s, box-shadow 0.25s;
    }

    input:focus, select:focus, textarea:focus {
      border-color: #112B4F;
      box-shadow: 0 0 0 3px rgba(17, 43, 79, 0.15);
    }

    select option {
      background: #FFFFFF;
      color: #0A1A2F;
    }

    textarea { resize: vertical; min-height: 80px; }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    .btn-primary {
      width: 100%;
      padding: 16px;
      background: linear-gradient(135deg, #112B4F, #1E3A5F);
      color: #FFFFFF;
      border: none;
      border-radius: 12px;
      font-family: 'Syne', sans-serif;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      margin-top: 8px;
      transition: all 0.25s;
      letter-spacing: 0.5px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 28px rgba(0,0,0,0.3);
    }

    .alert {
      padding: 14px 18px;
      border-radius: 10px;
      margin-bottom: 20px;
      font-size: 14px;
      font-weight: 500;
    }

    .alert-error {
      background: rgba(220,53,69,0.15);
      border: 1px solid rgba(220,53,69,0.3);
      color: #B22222;
    }

    .alert-success {
      background: rgba(17, 43, 79, 0.1);
      border: 1px solid rgba(17, 43, 79, 0.3);
      color: #112B4F;
    }

    .divider {
      display: flex;
      align-items: center;
      gap: 14px;
      margin: 24px 0;
      color: #2A3A4F;
      font-size: 13px;
      font-weight: 500;
    }

    .divider::before, .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: rgba(0,0,0,0.1);
    }

    .hidden { display: none !important; }

    @media (max-width: 900px) {
      .auth-left { display: none; }
      .auth-right { padding: 28px 20px; }
      .form-row { grid-template-columns: 1fr; }
    }

    .auth-footer {
      display: flex;
      justify-content: flex-end;
      margin-top: 20px;
    }

    .auth-footer img {
      width: 200px;
      height: auto;
      opacity: 0.8;
      border-radius: 8px;
      filter: grayscale(0.6) brightness(1.2);
    }

    /* demo helpers */
    .demo-note {
      font-size: 13px;
      color: #2A3A4F;
      text-align: center;
      margin-top: 16px;
      border-top: 1px solid rgba(0,0,0,0.08);
      padding-top: 16px;
      font-weight: 500;
    }
  </style>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prestigious ICT - NIN Verification Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    
</head>
<body>

<!-- LEFT PANEL -->
<div class="auth-left">
    <div class="brand">
        <!-- Compact logo container -->
            <div class="brand-logo">
                            <img 
                src="company_logo.jpg" 
                alt="Murna Logo"
                style="
                    width:500px;
                    height:150px;
                    object-fit:cover;
                    border-radius:20px;
                    padding:9px;
                    background:#fff;
                    box-shadow:0 4px 12px rgba(0,0,0,0.15);
                    border:8px solid #E8E8E8;
                "
                >
            </div>
        <!-- Brand identity -->
        <div class="brand-name">
            PRESTIGIOUS ICT INVESTMENT LTD
            <span>NIN Verification Portal · Licensed by NIMC</span>
        </div>
        <!-- Hero text -->
       
    </div>

    <!-- Feature list (tighter) -->
    <div class="features">
        <div class="feature-item">
            <div class="feature-icon">
                <svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div class="feature-text">Secure &amp; Encrypted Verification</div>
        </div>
        <div class="feature-item">
            <div class="feature-icon">
                <svg viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div class="feature-text">Real-time NIN Database Access</div>
        </div>
        <div class="feature-item">
            <div class="feature-icon">
                <svg viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
            </div>
            <div class="feature-text">Individual &amp; Corporate Accounts</div>
        </div>
    </div>
</div>
<!-- RIGHT PANEL -->
<div class="auth-right">
    <div class="auth-box">
            <div class="auth-heading">
            PRESTIGIOUS ICT INVESTMENT LTD</div>
            <div class="auth-subheading">
            <span>NIN Verification Portal · Licensed by NIMC</span>
        </div>
        <!-- Tabs: Login / Register -->
        <div class="auth-tabs">
            <a href="?page=login" class="auth-tab <?= $page === 'login' ? 'active' : '' ?>">Sign In</a>
            <a href="?page=register" class="auth-tab <?= $page === 'register' ? 'active' : '' ?>">Create Account</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- ─── LOGIN FORM ──────────────────────────────────────── -->
        <?php if ($page === 'login'): ?>
        <div class="auth-heading">Welcome Back</div>
        <div class="auth-subheading">Sign in to your account</div>

        <form method="POST">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="example@gmail.com" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn-primary">Sign In to Portal</button>

        </form>
         <div class="divider">Don't have an account?</div>
        <a href="?page=register" style="display:block; text-align:center; color:var(--green-light); font-weight:600; font-size:15px; text-decoration:none;">Create Account &rarr;</a>

        <div class="divider">Can't access your account?</div>
        <a href="forgot_password.php" style="display:block; text-align:center; color:var(--green-light); font-weight:600; font-size:15px; text-decoration:none;">Forgot Password? &rarr;</a>
            
        <!-- ─── REGISTER FORM ───────────────────────────────────── -->
        <?php elseif ($page === 'register'): ?>
        <div class="auth-heading">Create Account</div>
        <div class="auth-subheading">Select your account type to get started</div>

        <div class="reg-type-tabs">
            <button class="reg-type-btn active" id="btn-individual" onclick="switchRegType('individual')">
                Individual
                <span class="btn-label">Personal account</span>
            </button>
            <button class="reg-type-btn" id="btn-corporate" onclick="switchRegType('corporate')">
                Corporate Body
                <span class="btn-label">Organisation account</span>
            </button>
        </div>

        <!-- Individual Form -->
        <div id="form-individual">
            <form method="POST">
                <input type="hidden" name="action" value="register_individual">
                <div class="form-group">
                    <label>Full Name <span style="color:var(--green)">*</span></label>
                    <input type="text" name="full_name" placeholder="e.g. Khadija Labaran" required>
                </div>
                <div class="form-group">
                        <label>Email Address <span style="color:var(--green)">*</span></label>
                        <input type="email" name="email" placeholder="you@example.com" required>
                    </div>
                
                <div class="form-row">
                     <div class="form-group">
                    <label>NIN <span style="color:var(--green)">*</span></label>
                    <input type="text" name="nin_input" placeholder="e.g. 11 digit NIN" required minlength="11" maxlength="11">
                </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" placeholder="080XXXXXXXX">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Password <span style="color:var(--green)">*</span></label>
                        <input type="password" name="password" placeholder="Min. 8 characters" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password <span style="color:var(--green)">*</span></label>
                        <input type="password" name="confirm_password" placeholder="Repeat password" required>
                    </div>
                </div>
                <div class="auth-right">
                   <div class="form-group">
        <input type="checkbox" name="consent_disclaimer" value="1" required style="margin-top: 0.2rem; flex-shrink: 0;">
        <span style="font-weight: 400; font-size: 0.95rem; line-height: 1.5; ">
            I confirm that the information provided is true and accurate to the best of my knowledge. 
            <strong>I understand that providing false information will result in immediate account deactivation.</strong> 
            I also acknowledge that I am solely liable for any damages, penalties, or legal actions that may arise from 
            <strong>non-compliance with the Nigeria Data Protection Act (NDPA)</strong> and other applicable security 
            and privacy laws while using this service.
            <span style="color: var(--green);"></span>
        </span>
        </div>
</div>                
                <button type="submit" class="btn-primary">Create Individual Account</button>
            </form>
        </div>

        <!-- Corporate Form -->
        <div id="form-corporate" class="hidden">
            <form method="POST">
                <input type="hidden" name="action" value="register_corporate">
                <div class="form-group">
                    <label>Organisation Name <span style="color:var(--green)">*</span></label>
                    <input type="text" name="org_name" placeholder="e.g. My Organisation" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>RC Number</label>
                        <input type="text" name="rc_number" placeholder="CAC RC Number">
                    </div>
                    <div class="form-group">
                        <label>State of Registration</label>
                        <select name="org_state">
                            <option value="">-- Select State --</option>
                            <?php foreach ($nigerian_states as $state): ?>
                                <option value="<?= $state ?>"><?= $state ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Organisation Address</label>
                    <textarea name="org_address" placeholder="Full registered address"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Contact Person <span style="color:var(--green)">*</span></label>
                        <input type="text" name="contact_person" placeholder="Authorised signatory" required>
                    </div>
                    <div class="form-group">
                        <label>Contact Phone</label>
                        <input type="tel" name="contact_phone" placeholder="080XXXXXXXX">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email Address <span style="color:var(--green)">*</span></label>
                        <input type="email" name="email" placeholder="org@example.com" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Password <span style="color:var(--green)">*</span></label>
                        <input type="password" name="password" placeholder="Min. 8 characters" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password <span style="color:var(--green)">*</span></label>
                        <input type="password" name="confirm_password" placeholder="Repeat password" required>
                    </div>
                </div>
                <div class="auth-right">
                   <div class="form-group">
  <input type="checkbox" name="consent_disclaimer" value="1" required style="margin-top: 0.2rem; flex-shrink: 0;">
        <span style="font-weight: 400; font-size: 0.95rem; line-height: 1.5; ">
            I confirm that the information provided is true and accurate to the best of my knowledge. 
            <strong>I understand that providing false information will result in immediate account deactivation.</strong> 
            I also acknowledge that I am solely liable for any damages, penalties, or legal actions that may arise from 
            <strong>non-compliance with the Nigeria Data Protection Act (NDPA)</strong> and other applicable security 
            and privacy laws while using this service.
            <span style="color: var(--green);"></span>
        </span>
                       </div> 
                            </div>
                <button type="submit" class="btn-primary">Create Corporate Account</button>
            </form>
        </div>

        <div class="divider">Already have an account?</div>
        <a href="?page=login" style="display:block; text-align:center; color:var(--green-light); font-weight:600; font-size:15px; text-decoration:none;">Sign In &rarr;</a>
        <?php endif; ?>
       <div class="auth-right">Contact us at example@gmail.com</div>
    </div>
</div>

<script>
function switchRegType(type) {
    document.getElementById('form-individual').classList.toggle('hidden', type !== 'individual');
    document.getElementById('form-corporate').classList.toggle('hidden', type !== 'corporate');
    document.getElementById('btn-individual').classList.toggle('active', type === 'individual');
    document.getElementById('btn-corporate').classList.toggle('active', type === 'corporate');
}
</script>
</body>
</html>
