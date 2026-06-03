<?php
/**
 * send.php — receives the inquiry form and emails it via Gmail SMTP (PHPMailer).
 *
 * Requirements on the server:
 *   1. Upload PHPMailer's "src" folder to:  /phpmailer/src/  (see instructions below)
 *   2. Copy config.example.php to config.php and fill in your Gmail + App Password.
 *
 * Returns JSON: { "ok": true } on success, { "ok": false, "error": "..." } on failure.
 */

header('Content-Type: application/json; charset=utf-8');

// --- Only accept POST ---
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed.']);
    exit;
}

// --- Load credentials from a .env file kept OUTSIDE the web root ---
function load_env(array $paths) {
    foreach ($paths as $path) {
        if (!is_file($path) || !is_readable($path)) {
            continue;
        }
        $vars = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            $val = trim(substr($line, $pos + 1));
            // Strip optional surrounding quotes (App Password may contain spaces — keep them)
            if (strlen($val) >= 2 && ($val[0] === '"' || $val[0] === "'") && substr($val, -1) === $val[0]) {
                $val = substr($val, 1, -1);
            }
            $vars[$key] = $val;
        }
        return $vars;
    }
    return null;
}

$env = load_env([
    __DIR__ . '/../.env',  // preferred: above the web root, unreachable via browser
    __DIR__ . '/.env',     // fallback: same folder as send.php
]);

if ($env === null
    || empty($env['GMAIL_USER'])
    || empty($env['GMAIL_APP_PASSWORD'])
    || empty($env['MAIL_INBOX'])) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server is not configured yet.']);
    exit;
}

$config = [
    'gmail_user'         => $env['GMAIL_USER'],
    'gmail_app_password' => $env['GMAIL_APP_PASSWORD'],
    'inbox'              => $env['MAIL_INBOX'],
];

// --- Load PHPMailer ---
require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Honeypot: bots fill the hidden "company" field; silently accept & drop ---
if (!empty($_POST['company'] ?? '')) {
    echo json_encode(['ok' => true]);
    exit;
}

// --- Collect + trim input ---
$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$tour    = trim($_POST['tour']    ?? '');
$message = trim($_POST['message'] ?? '');

// --- Validate ---
if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Please fill in your name, a valid email and a message.']);
    exit;
}

// Strip line breaks from fields that go near headers
$name  = preg_replace('/[\r\n]+/', ' ', $name);
$email = preg_replace('/[\r\n]+/', ' ', $email);
$tour  = preg_replace('/[\r\n]+/', ' ', $tour);

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['gmail_user'];
    $mail->Password   = $config['gmail_app_password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    // From MUST be your Gmail address (Gmail rewrites it otherwise)
    $mail->setFrom($config['gmail_user'], 'Ragusa Tuk Tours — Website');
    $mail->addAddress($config['inbox']);   // where you receive inquiries
    $mail->addReplyTo($email, $name);       // reply goes straight to the visitor

    $mail->Subject = 'New inquiry — ' . ($tour !== '' ? $tour : 'Undecided');
    $mail->Body    =
        "New inquiry from the website\n" .
        "----------------------------------------\n" .
        "Name:           {$name}\n" .
        "Email:          {$email}\n" .
        "Preferred tour: " . ($tour !== '' ? $tour : 'Not specified') . "\n\n" .
        "Message:\n{$message}\n";

    $mail->send();
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    http_response_code(500);
    // Don't leak SMTP internals to the visitor
    echo json_encode(['ok' => false, 'error' => 'Mail could not be sent. Please email us directly.']);
}
