<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$balance = getUserBalance($user_id);
$bank_name = getSetting('bank_name');
$bank_acc_num = getSetting('bank_account_number');
$bank_acc_name = getSetting('bank_account_name');
$paystack_pub = PAYSTACK_PUBLIC_KEY;

$db = getDB();
$user_stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fund Wallet - Murna Foundation</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://js.paystack.co/v1/inline.js"></script>
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
        --red: #c41922;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: 'DM Sans', sans-serif;
        background: var(--silver);
        color: var(--text-on-);
        min-height: 100vh;
        display: flex;
    }
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

        /* ════════════════════════════════════════
           MAIN LAYOUT
        ════════════════════════════════════════ */
      

        .page-title { font-family: 'Syne', sans-serif; font-size: 28px; font-weight: 800; color: var(--blue-deep); margin-bottom: 6px; }
        .page-subtitle { font-size: 14px; color: var(--blue-dark); margin-bottom: 36px; }

        .balance-mini {
            display: inline-flex; align-items: center; gap: 10px;
            background: var(--card); border: 1px solid var(--border);
            border-radius: 12px; padding: 14px 20px; margin-bottom: 32px;
        }
        .balance-mini-label { font-size: 12px; color: var(--white); text-transform: uppercase; letter-spacing: 1px; }
        .balance-mini-value { font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 800; color: var(--white); }

        .payment-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; max-width: 900px; }

        .payment-card {
            background: #1F82B6; border-radius: 20px; border: 1px solid var(--border); overflow: hidden;
        }

        .payment-card-header {
            padding: 24px 28px 20px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 14px;
        }

        .payment-icon {
            width: 46px; height: 46px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
        }

        .payment-icon svg { width: 24px; height: 24px; fill: white; }
        .payment-icon.green { background: linear-gradient(135deg, var(--green-dark), var(--green)); }
        .payment-icon.blue { background: linear-gradient(135deg, var(--silver-light), silver); }

        .payment-card-title { font-family: 'Syne', sans-serif; font-size: 18px; font-weight: 700; color: var(--white); }
        .payment-card-sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

        .payment-card-body { padding: 28px; }

        label { display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }

        input[type="number"], input[type="text"] {
            width: 100%; padding: 14px 16px;
            background: silver;
            border-radius: 10px; color: var(--blue-deep); font-size: 16px;
            font-family: 'DM Sans', sans-serif; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        input:focus { border-color: var(--green); box-shadow: 0 0 0 3px rgba(0,166,81,0.12); }

        .form-group { margin-bottom: 20px; }

        .preset-amounts { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
        .preset-btn {
            padding: 8px 16px; background: silver;
            border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
            color: var(--text); font-size: 13px; font-weight: 600;
            cursor: pointer; transition: all 0.2s; font-family: 'DM Sans', sans-serif;
        }
        .preset-btn:hover { border-color: var(--green); color: var(--green-light); }

        .btn-pay {
            width: 100%; padding: 15px; border: none; border-radius: 12px;
            font-family: 'Syne', sans-serif; font-size: 16px; font-weight: 700;
            cursor: pointer; transition: all 0.25s;
        }
        .btn-pay.green {
            background: linear-gradient(135deg, var(--blue-dark), var(--silver));
            color: white; box-shadow: 0 6px 20px rgba(238, 241, 240, 0.25);
        }
        .btn-pay.green:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(238, 241, 240, 0.25); }

        /* Bank Transfer Info */
        .bank-info-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 14px 0; border-bottom: 1px solid rgba(238, 241, 240, 0.25);
        }
        .bank-info-item:last-child { border-bottom: none; }
        .bank-info-key { font-size: 13px; color: var(--text-muted); }
        .bank-info-val { font-size: 15px; font-weight: 600; color: var(--white); }
        .copy-btn {
            background: rgba(0,166,81,0.15); border: none; color: var(--green-light);
            font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 6px;
            cursor: pointer; margin-left: 10px; font-family: 'DM Sans', sans-serif;
        }
        .balance-strip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #1F82B6;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px 24px;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 14px;
        }
.payment-card {
            background: #064f77; border-radius: 20px; border: 1px solid var(--border); overflow: hidden;
        }

        .notice {
            background: rgba(255,193,7,0.08); border: 1px solid rgba(255,193,7,0.2);
            border-radius: 10px; padding: 14px 16px; margin-top: 20px;
            font-size: 13px; color: #ffd60a; line-height: 1.6;
        }

        .bank-transfer-form { margin-top: 20px; border-top: 1px solid var(--border); padding-top: 20px; }

        @media (max-width: 900px) {
            .payment-grid { grid-template-columns: 1fr; }
            .main { margin-left: 0; padding: 20px; }
            .sidebar { display: none; }
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
           
            <a href="logout.php" class="logout-btn" title="Log out">
                Log out<svg viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </a>
        </div>
    </div>
</nav>

<main class="main">
    <div class="page-title">Fund Your Wallet</div>
    <div class="page-subtitle">Top up your account balance to perform NIN verifications</div>

    <div class="balance-strip">
        <div>
            <div class="balance-mini-label">Current Balance</div>
            <div class="balance-mini-value">NGN <?= number_format($balance, 2) ?></div>
        </div>
    </div>

    <div class="balance-strip">

        <!-- PAYSTACK CARD -->
        <div class="payment-card">
            <div class="payment-card-header">
                <div class="payment-icon green">
                    <svg viewBox="0 0 24 24"><path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
                <div>
                    <div class="payment-card-title">Pay with Paystack</div>
                    <div class="payment-card-sub">Card, Bank Transfer, USSD, QR Code</div>
                </div>
            </div>
            <div class="payment-card-body">
                <div class="form-group">
                    <label>Select or Enter Amount (NGN)</label>
                    <div class="preset-amounts">
                        <button class="preset-btn" onclick="setAmount(500)">500</button>
                        <button class="preset-btn" onclick="setAmount(1000)">1,000</button>
                        <button class="preset-btn" onclick="setAmount(2500)">2,500</button>
                        <button class="preset-btn" onclick="setAmount(5000)">5,000</button>
                        <button class="preset-btn" onclick="setAmount(10000)">10,000</button>
                    </div>
                    <input type="number" id="paystack_amount" min="100" step="50" placeholder="Enter amount (min NGN 100)" >
                </div>
                <button class="btn-pay green" onclick="payWithPaystack()">Pay Now via Paystack</button>
            </div>
        </div>

       
    </div>
</main>

<script>
function setAmount(val) {
    document.getElementById('paystack_amount').value = val;
}

function copyText(text) {
    navigator.clipboard.writeText(text).then(() => alert('Account number copied!'));
}

function payWithPaystack() {
    const amountInput = document.getElementById('paystack_amount');
    const amount = parseFloat(amountInput.value);

    if (!amount || amount < 100) {
        alert('Minimum amount is NGN 100.');
        return;
    }

    const ref = 'MF_' + Date.now() + '_' + Math.floor(Math.random() * 9999);

    const handler = PaystackPop.setup({
        key: '<?= $paystack_pub ?>',
        email: '<?= htmlspecialchars($user['email']) ?>',
        amount: Math.round(amount * 100), // Paystack uses kobo
        currency: 'NGN',
        ref: ref,
        metadata: {
            user_id: <?= $user_id ?>,
            custom_fields: [
                { display_name: "Payment For", variable_name: "payment_for", value: "Wallet Top-up" }
            ]
        },
        callback: function(response) {
            // Verify on backend
            window.location.href = 'payment_callback.php?reference=' + response.reference;
        },
        onClose: function() {
            alert('Payment window closed. Complete payment to fund your wallet.');
        }
    });
    handler.openIframe();
}
</script>
</body>
</html>
