<?php
/**
 * FILE: mailer.php
 * PURPOSE: One place for PHPMailer setup and the e-mails the system sends.
 */
require_once __DIR__ . '/db_conn.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';         // installed with Composer
} else {
    require_once __DIR__ . '/PHPMailer/src/Exception.php';  // bundled copy
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
}

/** Returns a PHPMailer instance already configured from config.php */
function make_mailer() {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = cfg('mail.host', 'smtp.gmail.com');
    $mail->SMTPAuth   = true;
    $mail->Username   = cfg('mail.username');
    $mail->Password   = cfg('mail.password');
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = (int) cfg('mail.port', 587);
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom(cfg('mail.from', cfg('mail.username')), cfg('mail.from_name', 'NEU Library'));
    $mail->isHTML(true);
    return $mail;
}

/** Shared e-mail layout (works on phone mail apps too) */
function email_layout($headerBg, $title, $innerHtml) {
    $year = date('Y');
    $logo = app_url() . '/neu-logo.jpg';
    return "
    <div style='background:#f4f7f6;padding:32px 12px;font-family:Segoe UI,Roboto,Arial,sans-serif;'>
      <div style='max-width:560px;margin:auto;background:#fff;border-radius:16px;overflow:hidden;border:1px solid #e1e1e1;'>
        <div style='background:$headerBg;padding:32px 20px;text-align:center;'>
          <img src='$logo' alt='NEU' width='72' height='72' style='border-radius:50%;background:#fff;border:3px solid rgba(255,255,255,.4);'>
          <h1 style='color:#fff;margin:12px 0 0;font-size:22px;letter-spacing:1px;text-transform:uppercase;'>$title</h1>
        </div>
        <div style='padding:32px 24px;color:#2d3436;font-size:15px;line-height:1.6;'>$innerHtml</div>
        <div style='padding:18px;text-align:center;background:#fafafa;border-top:1px solid #eee;font-size:11px;color:#888;'>
          &copy; $year New Era University Library System &middot; Automated message, please do not reply.
        </div>
      </div>
    </div>";
}

function email_button($href, $label, $bg) {
    return "<div style='text-align:center;margin:28px 0;'>
              <a href='$href' style='background:$bg;color:#fff;padding:15px 36px;text-decoration:none;border-radius:8px;font-weight:bold;display:inline-block;'>$label</a>
            </div>";
}

/**
 * Creates a password-reset token for $email and e-mails the link.
 * Returns ['status' => 'success'|'error', 'message' => ...]
 */
function send_password_reset($email) {
    global $conn;

    $stmt = $conn->prepare("SELECT first_name FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return ['status' => 'error', 'message' => 'The provided email is not registered.'];
    }

    $minutes = (int) cfg('app.reset_minutes', 10);
    $token   = bin2hex(random_bytes(32));
    $expiry  = date("Y-m-d H:i:s", strtotime("+$minutes minutes"));

    $up = $conn->prepare("UPDATE users SET reset_token = ?, token_expiry = ? WHERE email = ?");
    $up->bind_param("sss", $token, $expiry, $email);
    $up->execute();
    $up->close();

    $fname = e($user['first_name']);
    $link  = app_url() . "/reset.php?token=$token&email=" . urlencode($email);

    $body = "<h2 style='color:#c62828;margin-top:0;font-size:20px;'>Hello, $fname!</h2>
             <p>We received a request to reset your NEU Library account password. Tap the button below to choose a new one:</p>"
          . email_button($link, 'RESET PASSWORD', '#c62828') .
            "<p style='background:#fff8f6;border-left:4px solid #ff5722;padding:12px;border-radius:4px;color:#b71c1c;font-size:14px;'>
               <strong>Security Alert:</strong> This link is only valid for <strong>$minutes minutes</strong>.
             </p>
             <p style='font-size:13px;color:#7f8c8d;'>If you did not request this, you can safely ignore this email.</p>";

    try {
        $mail = make_mailer();
        $mail->addAddress($email, $user['first_name']);
        $mail->Subject = 'Security Notice: Password Reset Request';
        $mail->Body    = email_layout('linear-gradient(135deg,#4b0000,#c62828 50%,#ff5722)', 'NEU Library Security', $body);
        $mail->AltBody = "Reset your NEU Library password (valid $minutes minutes): $link";
        $mail->send();
        return ['status' => 'success', 'message' => "Reset link sent to $email (valid for $minutes minutes)."];
    } catch (MailException $ex) {
        return ['status' => 'error', 'message' => 'Mailer Error: ' . $ex->getMessage()];
    }
}

/* ---------- SIGNED E-MAIL VERIFICATION TOKENS ---------- */

function b64url_encode($data) { return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); }
function b64url_decode($data) { return base64_decode(strtr($data, '-_', '+/')); }

/** Builds a tamper-proof token (HMAC-signed) holding the pending account data */
function make_signed_token(array $payload) {
    $body = b64url_encode(json_encode($payload));
    $sig  = b64url_encode(hash_hmac('sha256', $body, cfg('app.secret', ''), true));
    return "$body.$sig";
}

/** Returns the payload array, or null if the token was altered */
function read_signed_token($token) {
    $parts = explode('.', (string) $token);
    if (count($parts) !== 2) return null;
    [$body, $sig] = $parts;
    $expected = b64url_encode(hash_hmac('sha256', $body, cfg('app.secret', ''), true));
    if (!hash_equals($expected, $sig)) return null;
    $data = json_decode(b64url_decode($body), true);
    return is_array($data) ? $data : null;
}
