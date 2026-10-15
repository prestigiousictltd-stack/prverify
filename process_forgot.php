<?php
declare(strict_types=1);

require 'config.php';       // provides getDB()
require 'resend_mailer.php'; // our new mailer

set_time_limit(120);

// Ensure the response is HTML, not plain text
header('Content-Type: text/html; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed.';
    exit;
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));

// Enumeration-safe notice: identical whether the account exists or not.
$genericNotice = 'If an account exists with that email, a reset link has been sent.';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo $genericNotice;
    exit;
}

$conn = getDB();

// --- Look up the user --------------------------------------------------------
$stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo $genericNotice;
    exit;
}

// --- Generate a reset token --------------------------------------------------
$token       = bin2hex(random_bytes(32));
$expiresAt   = date('Y-m-d H:i:s', strtotime('+30 minutes'));
$hashedToken = password_hash($token, PASSWORD_DEFAULT);

// Invalidate any previous unused tokens for this user so old links stop working.
$cleanup = $conn->prepare('UPDATE password_resets SET used = TRUE WHERE user_id = ? AND used = FALSE');
if ($cleanup) {
    $cleanup->bind_param('i', $user['id']);
    $cleanup->execute();
    $cleanup->close();
}

// --- Insert the new token ----------------------------------------------------
$stmt2 = $conn->prepare(
    'INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)'
);
$stmt2->bind_param('iss', $user['id'], $hashedToken, $expiresAt);
$stmt2->execute();
$stmt2->close();

// --- Build the reset link ----------------------------------------------------
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$resetLink = $protocol . $host . '/reset_password.php?token=' . urlencode($token);

// --- Compose and send via Resend --------------------------------------------
$appName = 'Prestigious ICT Verifier';
$htmlBody = sprintf(
    '<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:auto;padding:24px;color:#0f172a;">'
    . '<h2 style="color:#1b6b4c;margin-bottom:8px;">Reset your %s password</h2>'
    . '<p style="color:#334155;line-height:1.6;">Someone requested a password reset for your account. This link is valid for 30 minutes.</p>'
    . '<p style="text-align:center;margin:32px 0;">'
    . '<a href="%s" style="background:#1b6b4c;color:#fff;padding:12px 24px;text-decoration:none;border-radius:8px;font-weight:bold;display:inline-block;">Reset password</a>'
    . '</p>'
    . '<p style="color:#64748b;font-size:13px;line-height:1.6;">If the button does not work, paste this link into your browser:<br>'
    . '<code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;">%s</code></p>'
    . '<p style="color:#94a3b8;font-size:12px;border-top:1px solid #e2e8f0;padding-top:12px;margin-top:32px;">'
    . 'If you did not request this, you can safely ignore this email.</p>'
    . '</div>',
    htmlspecialchars($appName, ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8')
);

$mailer = ResendMailer::fromEnv();
$sent   = $mailer->send($email, 'Reset your ' . $appName . ' password', $htmlBody);

if (!$sent) {
    error_log('[process_forgot] Resend send failed for email=' . $email);
    // Do not reveal failure to the caller — same generic notice either way.
}

echo $genericNotice;
