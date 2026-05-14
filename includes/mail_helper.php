<?php
/**
 * Scholar Hub — reusable PHPMailer (Gmail SMTP) helper
 *
 * Configure SMTP using ONE of:
 *   1) Environment variables: SCHOLARHUB_SMTP_USER, SCHOLARHUB_SMTP_PASS
 *   2) Optional file: includes/mail_local.php defining SCHOLARHUB_SMTP_USER / SCHOLARHUB_SMTP_PASS
 *   3) Edit the fallback constants below (not recommended for production)
 */

declare(strict_types=1);

if (is_file(__DIR__ . '/mail_local.php')) {
    require_once __DIR__ . '/mail_local.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Load PHPMailer classes (manual install under /PHPMailer or Composer vendor).
 */
function scholarhub_load_phpmailer(): bool
{
    static $loaded = null;
    if ($loaded !== null) {
        return $loaded;
    }
    $base = dirname(__DIR__);
    if (is_file($base . '/vendor/autoload.php')) {
        require_once $base . '/vendor/autoload.php';
        $loaded = class_exists(PHPMailer::class);
        return $loaded;
    }
    $src = $base . '/PHPMailer/src/';
    if (is_file($src . 'PHPMailer.php')) {
        require_once $src . 'Exception.php';
        require_once $src . 'PHPMailer.php';
        require_once $src . 'SMTP.php';
        $loaded = class_exists(PHPMailer::class);
        return $loaded;
    }
    $loaded = false;
    return false;
}

/**
 * SMTP credentials (Gmail: use App Password with 2-Step Verification).
 *
 * @return array{host:string,user:string,pass:string,from_email:string,from_name:string}
 */
function scholarhub_mail_credentials(): array
{
    $user = defined('SCHOLARHUB_SMTP_USER')
        ? (string) SCHOLARHUB_SMTP_USER
        : (getenv('SCHOLARHUB_SMTP_USER') ?: 'YOUR_EMAIL@gmail.com');
    $pass = defined('SCHOLARHUB_SMTP_PASS')
        ? (string) SCHOLARHUB_SMTP_PASS
        : (getenv('SCHOLARHUB_SMTP_PASS') ?: 'YOUR_APP_PASSWORD');

    $from = defined('SCHOLARHUB_MAIL_FROM')
        ? (string) SCHOLARHUB_MAIL_FROM
        : (getenv('SCHOLARHUB_MAIL_FROM') ?: $user);

    return [
        'host'       => getenv('SCHOLARHUB_SMTP_HOST') ?: 'smtp.gmail.com',
        'user'       => $user,
        'pass'       => $pass,
        'from_email' => $from,
        'from_name'  => getenv('SCHOLARHUB_MAIL_FROM_NAME') ?: 'Scholar Hub',
    ];
}

/**
 * Send a plain-text email via Gmail SMTP.
 *
 * @return array{success:bool,error?:string}
 */
function scholarhub_send_mail(string $to, string $subject, string $plainBody): array
{
    if (!scholarhub_load_phpmailer()) {
        return ['success' => false, 'error' => 'PHPMailer is not installed. Add vendor/autoload.php or PHPMailer/src/.'];
    }

    $c = scholarhub_mail_credentials();
    if ($c['user'] === 'YOUR_EMAIL@gmail.com' || $c['pass'] === 'YOUR_APP_PASSWORD') {
        return ['success' => false, 'error' => 'SMTP is not configured. Set SCHOLARHUB_SMTP_USER/PASS, includes/mail_local.php, or edit includes/mail_helper.php.'];
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $c['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $c['user'];
        $mail->Password   = $c['pass'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($c['from_email'], $c['from_name']);
        $mail->addAddress($to);
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $plainBody;

        $mail->send();
        return ['success' => true];
    } catch (PHPMailerException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
