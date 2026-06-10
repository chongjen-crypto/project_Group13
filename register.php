<?php
/**
 * Scholar Hub — Student registration
 * Email verification (PHPMailer) + strong password rules
 */

declare(strict_types=1);

session_start();

require __DIR__ . '/db.php';
require_once __DIR__ . '/includes/mail_helper.php';

// ---- Constants ----
const REG_CODE_TTL_SECONDS      = 300; // 5 minutes
const REG_RESEND_COOLDOWN_SEC   = 60;

// ---- Helpers ----
function registration_password_errors(string $p): array
{
    $errs = [];
    if (strlen($p) < 8) {
        $errs[] = 'At least 8 characters.';
    }
    if (!preg_match('/[A-Z]/', $p)) {
        $errs[] = 'At least one uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $p)) {
        $errs[] = 'At least one lowercase letter.';
    }
    if (!preg_match('/[0-9]/', $p)) {
        $errs[] = 'At least one number.';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $p)) {
        $errs[] = 'At least one special symbol.';
    }
    return $errs;
}

function users_has_email_verified_column(mysqli $conn): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $res = $conn->query("SHOW COLUMNS FROM users LIKE 'email_verified'");
    $cache = ($res instanceof mysqli_result && $res->num_rows > 0);
    return $cache;
}

function reg_clear_verification_session(): void
{
    unset(
        $_SESSION['reg_verify_email'],
        $_SESSION['reg_verify_secret'],
        $_SESSION['reg_verify_hmac'],
        $_SESSION['reg_verify_expires'],
        $_SESSION['reg_verify_sent_at'],
        $_SESSION['reg_email_verified'],
        $_SESSION['reg_verified_email']
    );
}

// ---- AJAX: send / verify code ----
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {
    header('Content-Type: application/json; charset=UTF-8');
    $action = $_POST['reg_action'] ?? '';

    if ($action === 'send_code') {
        $email = trim($_POST['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
            exit;
        }

        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
            exit;
        }
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'This email is already registered.']);
            exit;
        }
        $stmt->close();

        $now = time();
        $sameEmail = strcasecmp($email, (string) ($_SESSION['reg_verify_email'] ?? '')) === 0;
        if ($sameEmail && isset($_SESSION['reg_verify_sent_at']) && ($now - (int) $_SESSION['reg_verify_sent_at']) < REG_RESEND_COOLDOWN_SEC) {
            $wait = REG_RESEND_COOLDOWN_SEC - ($now - (int) $_SESSION['reg_verify_sent_at']);
            echo json_encode([
                'success'   => false,
                'message'   => 'Please wait before resending the code.',
                'cooldown'  => $wait,
            ]);
            exit;
        }

        // New code invalidates previous verification
        $_SESSION['reg_email_verified'] = false;
        unset($_SESSION['reg_verified_email']);

        $code   = (string) random_int(100000, 999999);
        $secret = bin2hex(random_bytes(16));
        $_SESSION['reg_verify_secret']   = $secret;
        $_SESSION['reg_verify_hmac']     = hash_hmac('sha256', $code, $secret);
        $_SESSION['reg_verify_email']    = $email;
        $_SESSION['reg_verify_expires']  = $now + REG_CODE_TTL_SECONDS;
        $_SESSION['reg_verify_sent_at']  = $now;

        $body = "Your Scholar Hub email verification code is: {$code}\n\nThis code expires in 5 minutes.\nIf you did not request this, ignore this email.";

        $send = scholarhub_send_mail($email, 'Scholar Hub — Email Verification Code', $body);
        if (!$send['success']) {
            reg_clear_verification_session();
            echo json_encode(['success' => false, 'message' => $send['error'] ?? 'Failed to send email.']);
            exit;
        }

        echo json_encode([
            'success'     => true,
            'message'     => 'Verification code sent to your email.',
            'expires_in'  => REG_CODE_TTL_SECONDS,
            'cooldown'    => REG_RESEND_COOLDOWN_SEC,
        ]);
        exit;
    }

    if ($action === 'verify_code') {
        $email = trim($_POST['email'] ?? '');
        $code  = preg_replace('/\D/', '', (string) ($_POST['verification_code'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
            exit;
        }
        if (strlen($code) !== 6) {
            echo json_encode(['success' => false, 'message' => 'Enter the 6-digit code.']);
            exit;
        }

        if (strcasecmp($email, (string) ($_SESSION['reg_verify_email'] ?? '')) !== 0) {
            echo json_encode(['success' => false, 'message' => 'Send a code to this email first.']);
            exit;
        }

        if (time() > (int) ($_SESSION['reg_verify_expires'] ?? 0)) {
            echo json_encode(['success' => false, 'message' => 'Code expired. Request a new code.']);
            exit;
        }

        $secret = (string) ($_SESSION['reg_verify_secret'] ?? '');
        $expect = (string) ($_SESSION['reg_verify_hmac'] ?? '');
        $calc   = hash_hmac('sha256', $code, $secret);

        if ($expect === '' || !hash_equals($expect, $calc)) {
            echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
            exit;
        }

        $_SESSION['reg_email_verified'] = true;
        $_SESSION['reg_verified_email'] = strtolower($email);
        unset($_SESSION['reg_verify_hmac'], $_SESSION['reg_verify_secret']);

        echo json_encode(['success' => true, 'message' => 'Email verified successfully']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// ---- Form POST: final registration ----
$full_name = $student_id = $email = $phone = '';
$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $full_name        = trim($_POST['full_name'] ?? '');
    $student_id       = trim($_POST['student_id'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $password         = (string) ($_POST['password'] ?? '');
    $confirm_password = (string) ($_POST['confirm_password'] ?? '');

    if ($full_name === '') {
        $errors['full_name'] = 'Full Name is required.';
    }
    if ($student_id === '') {
        $errors['student_id'] = 'Student ID is required.';
    }
    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } else {
        $sessVerified = !empty($_SESSION['reg_email_verified']) && !empty($_SESSION['reg_verified_email']);
        if ($sessVerified && strtolower($email) !== (string) $_SESSION['reg_verified_email']) {
            $errors['email'] = 'Email changed after verification. Send and verify the code again.';
        } elseif (!$sessVerified) {
            $errors['email'] = 'Please verify your email before registering.';
        }
    }

    $pwErrs = registration_password_errors($password);
    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif ($pwErrs !== []) {
        $errors['password'] = 'Password must meet all rules: ' . implode(' ', $pwErrs);
    }

    if ($confirm_password === '') {
        $errors['confirm_password'] = 'Confirm Password is required.';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors['email'] = 'This email is already registered.';
            }
            $stmt->close();
        } else {
            $errors['general'] = 'Database error. Please try again later.';
        }
    }

    if (empty($errors['general']) && empty($errors['email'])) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE user_id = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $student_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors['student_id'] = 'This Student ID is already registered.';
            }
            $stmt->close();
        } else {
            $errors['general'] = 'Database error. Please try again later.';
        }
    }

    if (empty($errors)) {
        $hasCol = users_has_email_verified_column($conn);
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $role   = 'student';

        if ($hasCol) {
            $sql = 'INSERT INTO users (role, full_name, user_id, email, phone, password, email_verified, verification_code, verification_expiry)
                    VALUES (?, ?, ?, ?, ?, ?, 1, NULL, NULL)';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('ssssss', $role, $full_name, $student_id, $email, $phone, $hashed);
            }
        } else {
            $sql = 'INSERT INTO users (role, full_name, user_id, email, phone, password) VALUES (?, ?, ?, ?, ?, ?)';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('ssssss', $role, $full_name, $student_id, $email, $phone, $hashed);
            }
        }

        if (!isset($stmt) || !$stmt) {
            $errors['general'] = 'Database error. Please run sql/alter_users_email_verification.sql if the schema is outdated.';
        } elseif ($stmt->execute()) {
            reg_clear_verification_session();
            $full_name = $student_id = $email = $phone = '';
            $success_message = 'Registration successful! Redirecting to login page...';
        } else {
            $errors['general'] = 'Registration failed. Please try again.';
        }
        if (isset($stmt) && $stmt) {
            $stmt->close();
        }
    }
}

$email_verified_session = !empty($_SESSION['reg_email_verified']) && !empty($_SESSION['reg_verified_email']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Scholar Hub - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        html { overflow-x: hidden; }
        body {
            min-height: 100vh;
            min-height: 100dvh;
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: url('assets/trackfield.webp') center center / cover no-repeat fixed;
            position: relative;
            color: #212529;
            overflow-x: hidden;
        }
        @media (max-width: 767.98px) { body { background-attachment: scroll; } }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 0;
        }
        .register-page-wrapper {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: max(0.75rem, env(safe-area-inset-top)) max(0.65rem, env(safe-area-inset-right)) max(0.75rem, env(safe-area-inset-bottom)) max(0.65rem, env(safe-area-inset-left));
            box-sizing: border-box;
        }
        .register-card {
            background-color: #fff;
            border-radius: 1rem;
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.25);
            max-width: min(520px, 100%);
            width: 100%;
            padding: clamp(1.15rem, 3.5vw, 2.25rem) clamp(1rem, 3vw, 2rem);
            box-sizing: border-box;
        }
        @media (min-width: 768px) {
            .register-card { padding: clamp(1.75rem, 2.5vw, 2.75rem) clamp(1.5rem, 2.5vw, 2.5rem); }
        }
        .register-card .app-title {
            font-size: clamp(1.2rem, 3.8vw, 1.75rem) !important;
            line-height: 1.2;
        }
        .app-title { font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
        .app-subtitle { font-size: 0.95rem; color: #6c757d; }
        .divider-line {
            height: 2px;
            border-radius: 999px;
            background: linear-gradient(90deg, #000, #6c757d);
            opacity: 0.2;
            margin: 0.75rem 0 1.5rem;
        }
        .form-label { font-weight: 500; font-size: 0.9rem; }
        .form-control, .form-control:focus { border-radius: 0.75rem; }
        .btn-register {
            border-radius: 999px;
            padding: 0.7rem 1rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .text-link { text-decoration: none; }
        .text-link:hover { text-decoration: underline; }
        .error-text { font-size: 0.8rem; }
        .pwd-strength-bar { height: 6px; border-radius: 999px; background: #e9ecef; overflow: hidden; }
        .pwd-strength-fill { height: 100%; width: 0%; transition: width 0.25s ease, background 0.25s ease; border-radius: 999px; }
        .pwd-check { font-size: 0.8rem; color: #6c757d; transition: color 0.2s ease; }
        .pwd-check.ok { color: #198754; font-weight: 600; }
        .pwd-check i { margin-right: 0.25rem; }
        #sendCodeBtn.is-loading, #verifyCodeBtn.is-loading { pointer-events: none; opacity: 0.75; }
        .spinner-border-sm { width: 1rem; height: 1rem; border-width: 0.15em; }
        .reg-details-section {
            position: relative;
            border-radius: 0.75rem;
            transition: opacity 0.25s ease;
        }
        .reg-details-section.is-locked {
            opacity: 0.55;
            pointer-events: none;
            user-select: none;
        }
        .reg-details-section.is-locked::after {
            content: 'Verify your email above to continue';
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: #495057;
            background: rgba(233, 236, 239, 0.72);
            border-radius: 0.75rem;
            z-index: 2;
        }
        .reg-details-section.is-locked .form-control,
        .reg-details-section.is-locked .btn {
            background-color: #e9ecef;
        }
        @media (max-width: 575.98px) {
            .form-control, .form-select { font-size: 16px; }
        }
    </style>
</head>
<body>
<div class="register-page-wrapper">
    <div class="register-card">
        <div class="text-center mb-3">
            <div class="d-inline-flex align-items-center justify-content-center mb-2 flex-wrap gap-2">
                <i class="bi bi-mortarboard-fill fs-1 text-dark"></i>
                <span class="app-title fs-3">Scholar Hub</span>
            </div>
            <div class="app-subtitle">Sport Facility Booking System</div>
        </div>
        <div class="divider-line"></div>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger py-2" role="alert"><?php echo htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($success_message !== ''): ?>
            <div class="alert alert-success py-2" role="alert" id="successAlert"><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div id="ajaxAlert" class="alert py-2 d-none" role="alert"></div>

        <form id="registerForm" method="POST" novalidate>
            <!-- Email first + Send code -->
            <div class="mb-3">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <div class="input-group flex-nowrap">
                    <input
                        type="email"
                        class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="your.email@university.edu"
                        autocomplete="email"
                    >
                    <button type="button" class="btn btn-outline-dark px-2 px-sm-3 flex-shrink-0" id="sendCodeBtn" title="Send verification code">
                        <span class="btn-label"><i class="bi bi-envelope me-1 d-none d-sm-inline"></i>Send Code</span>
                        <span class="btn-loading d-none spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    </button>
                </div>
                <div class="text-danger error-text" id="emailError"><?php echo isset($errors['email']) ? htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') : ''; ?></div>
                <div class="form-text small" id="cooldownHint"></div>
            </div>

            <!-- Verification code -->
            <div class="mb-3">
                <label for="verification_code" class="form-label">Verification Code</label>
                <div class="input-group flex-nowrap">
                    <input
                        type="text"
                        class="form-control"
                        id="verification_code"
                        inputmode="numeric"
                        maxlength="6"
                        placeholder="6-digit code"
                        autocomplete="one-time-code"
                    >
                    <button type="button" class="btn btn-primary px-2 px-sm-3 flex-shrink-0" id="verifyCodeBtn">
                        <span class="btn-label">Verify</span>
                        <span class="btn-loading d-none spinner-border spinner-border-sm text-light" role="status"></span>
                    </button>
                </div>
                <div class="text-danger error-text" id="verifyCodeError"></div>
                <div id="emailVerifiedBanner" class="alert alert-success py-2 mt-2 mb-0 small <?php echo $email_verified_session ? '' : 'd-none'; ?>">
                    <i class="bi bi-check-circle-fill me-1"></i> Email verified successfully
                </div>
            </div>

            <div id="registerDetailsSection" class="reg-details-section<?php echo $email_verified_session ? '' : ' is-locked'; ?>">
            <div class="mb-3">
                <label for="full_name" class="form-label">Full Name</label>
                <input type="text" class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter your full name">
                <div class="text-danger error-text" id="fullNameError"><?php echo isset($errors['full_name']) ? htmlspecialchars($errors['full_name'], ENT_QUOTES, 'UTF-8') : ''; ?></div>
            </div>

            <div class="mb-3">
                <label for="student_id" class="form-label">Student ID</label>
                <input type="text" class="form-control <?php echo isset($errors['student_id']) ? 'is-invalid' : ''; ?>" id="student_id" name="student_id" value="<?php echo htmlspecialchars($student_id, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter your student ID">
                <div class="text-danger error-text" id="studentIdError"><?php echo isset($errors['student_id']) ? htmlspecialchars($errors['student_id'], ENT_QUOTES, 'UTF-8') : ''; ?></div>
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label">Phone Number <span class="text-muted">(optional)</span></label>
                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter your phone number">
                <div class="text-danger error-text" id="phoneError"></div>
            </div>

            <div class="mb-2">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" placeholder="Strong password" autocomplete="new-password">
                    <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePassword" aria-label="Show password">
                        <i class="bi bi-eye-slash" id="passwordIcon"></i>
                    </button>
                </div>
                <div class="pwd-strength-bar mt-2 mb-1"><div class="pwd-strength-fill" id="pwdStrengthFill"></div></div>
                <div class="small mb-2" id="pwdStrengthLabel"><span class="text-muted">Strength:</span> <span id="pwdStrengthText">—</span></div>
                <div class="mb-2" id="pwdChecklist">
                    <div class="pwd-check" id="chk-len"><i class="bi bi-circle"></i> Minimum 8 characters</div>
                    <div class="pwd-check" id="chk-up"><i class="bi bi-circle"></i> Uppercase letter</div>
                    <div class="pwd-check" id="chk-lo"><i class="bi bi-circle"></i> Lowercase letter</div>
                    <div class="pwd-check" id="chk-num"><i class="bi bi-circle"></i> Number</div>
                    <div class="pwd-check" id="chk-sym"><i class="bi bi-circle"></i> Special symbol</div>
                </div>
                <div class="text-danger error-text" id="passwordError"><?php echo isset($errors['password']) ? htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') : ''; ?></div>
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="input-group">
                    <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" id="confirm_password" name="confirm_password" placeholder="Re-enter password" autocomplete="new-password">
                    <button class="btn btn-outline-secondary border-start-0" type="button" id="toggleConfirmPassword" aria-label="Show confirm password">
                        <i class="bi bi-eye-slash" id="confirmPasswordIcon"></i>
                    </button>
                </div>
                <div class="text-danger error-text" id="confirmPasswordError"><?php echo isset($errors['confirm_password']) ? htmlspecialchars($errors['confirm_password'], ENT_QUOTES, 'UTF-8') : ''; ?></div>
            </div>

            <div class="d-grid mb-2">
                <button type="submit" class="btn btn-dark btn-register" id="registerSubmitBtn"<?php echo $email_verified_session ? '' : ' disabled'; ?>>
                    <i class="bi bi-person-plus me-1"></i> Register
                </button>
            </div>
            </div>

            <div class="text-center mt-2">
                <span class="text-muted">Already have an account? </span>
                <a href="login.php" class="text-primary text-link">Login</a>
            </div>
            <div class="text-end mt-2">
                <a href="staff_registration.php" class="text-primary text-link" style="font-size: 0.9rem;">Staff registration</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    'use strict';

    const REG = <?php echo json_encode([
        'emailVerified' => $email_verified_session,
        'verifiedEmail' => $email_verified_session ? (string) ($_SESSION['reg_verified_email'] ?? '') : '',
    ]); ?>;

    const sendBtn = document.getElementById('sendCodeBtn');
    const verifyBtn = document.getElementById('verifyCodeBtn');
    const emailInput = document.getElementById('email');
    const codeInput = document.getElementById('verification_code');
    const ajaxAlert = document.getElementById('ajaxAlert');
    const emailError = document.getElementById('emailError');
    const verifyCodeError = document.getElementById('verifyCodeError');
    const cooldownHint = document.getElementById('cooldownHint');
    const banner = document.getElementById('emailVerifiedBanner');
    const form = document.getElementById('registerForm');
    const detailsSection = document.getElementById('registerDetailsSection');
    const registerSubmitBtn = document.getElementById('registerSubmitBtn');

    let emailVerified = !!REG.emailVerified;
    let cooldownTimer = null;

    function setDetailsLocked(locked) {
        emailVerified = !locked;
        if (detailsSection) {
            detailsSection.classList.toggle('is-locked', locked);
        }
        if (detailsSection) {
            detailsSection.querySelectorAll('input, button, select, textarea').forEach(function (el) {
                if (el.id === 'registerSubmitBtn' || el.closest('#registerDetailsSection')) {
                    el.disabled = locked;
                }
            });
        }
        if (registerSubmitBtn) {
            registerSubmitBtn.disabled = locked;
        }
    }

    setDetailsLocked(!emailVerified);

    function showAjaxAlert(type, msg) {
        ajaxAlert.className = 'alert py-2 ' + (type === 'success' ? 'alert-success' : type === 'danger' ? 'alert-danger' : 'alert-info');
        ajaxAlert.textContent = msg;
        ajaxAlert.classList.remove('d-none');
    }
    function hideAjaxAlert() {
        ajaxAlert.classList.add('d-none');
    }

    function setBtnLoading(btn, loading) {
        if (!btn) return;
        btn.classList.toggle('is-loading', loading);
        const label = btn.querySelector('.btn-label');
        const spin = btn.querySelector('.btn-loading');
        if (label) label.classList.toggle('d-none', loading);
        if (spin) spin.classList.toggle('d-none', !loading);
    }

    function postJson(body) {
        return fetch('register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body,
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); });
    }

    function startCooldown(seconds) {
        if (cooldownTimer) clearInterval(cooldownTimer);
        let s = seconds;
        sendBtn.disabled = true;
        function tick() {
            cooldownHint.textContent = s > 0 ? ('Resend available in ' + s + 's') : '';
            if (s <= 0) {
                clearInterval(cooldownTimer);
                cooldownTimer = null;
                sendBtn.disabled = false;
                cooldownHint.textContent = '';
                return;
            }
            s--;
        }
        tick();
        cooldownTimer = setInterval(tick, 1000);
    }

    if (REG.emailVerified) {
        banner.classList.remove('d-none');
    }

    emailInput.addEventListener('input', function () {
        if (emailVerified) {
            banner.classList.add('d-none');
            setDetailsLocked(true);
            REG.emailVerified = false;
            REG.verifiedEmail = '';
        }
    });

    codeInput.addEventListener('input', function () {
        codeInput.value = codeInput.value.replace(/\D/g, '').slice(0, 6);
    });

    sendBtn.addEventListener('click', function () {
        hideAjaxAlert();
        emailError.textContent = '';
        verifyCodeError.textContent = '';
        const email = emailInput.value.trim();
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            emailError.textContent = 'Please enter a valid email address.';
            emailInput.classList.add('is-invalid');
            return;
        }
        emailInput.classList.remove('is-invalid');
        setBtnLoading(sendBtn, true);
        const body = 'reg_action=send_code&email=' + encodeURIComponent(email);
        postJson(body).then(function (data) {
            setBtnLoading(sendBtn, false);
            if (data.success) {
                showAjaxAlert('success', data.message || 'Code sent.');
                banner.classList.add('d-none');
                setDetailsLocked(true);
                REG.emailVerified = false;
                REG.verifiedEmail = '';
                if (data.cooldown) startCooldown(parseInt(data.cooldown, 10));
            } else {
                showAjaxAlert('danger', data.message || 'Request failed.');
                if (data.cooldown) startCooldown(parseInt(data.cooldown, 10));
            }
        }).catch(function () {
            setBtnLoading(sendBtn, false);
            showAjaxAlert('danger', 'Network error. Try again.');
        });
    });

    verifyBtn.addEventListener('click', function () {
        hideAjaxAlert();
        verifyCodeError.textContent = '';
        const email = emailInput.value.trim();
        const code = codeInput.value.trim();
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            emailError.textContent = 'Enter the same email you sent the code to.';
            return;
        }
        if (code.length !== 6) {
            verifyCodeError.textContent = 'Enter the 6-digit code.';
            return;
        }
        setBtnLoading(verifyBtn, true);
        const body = 'reg_action=verify_code&email=' + encodeURIComponent(email) + '&verification_code=' + encodeURIComponent(code);
        postJson(body).then(function (data) {
            setBtnLoading(verifyBtn, false);
            if (data.success) {
                showAjaxAlert('success', data.message || 'Verified.');
                banner.classList.remove('d-none');
                REG.emailVerified = true;
                REG.verifiedEmail = email.toLowerCase();
                setDetailsLocked(false);
            } else {
                verifyCodeError.textContent = data.message || 'Verification failed.';
            }
        }).catch(function () {
            setBtnLoading(verifyBtn, false);
            showAjaxAlert('danger', 'Network error.');
        });
    });

    // Password toggles
    document.getElementById('togglePassword').addEventListener('click', function () {
        const p = document.getElementById('password');
        const i = document.getElementById('passwordIcon');
        p.type = p.type === 'password' ? 'text' : 'password';
        i.classList.toggle('bi-eye'); i.classList.toggle('bi-eye-slash');
    });
    document.getElementById('toggleConfirmPassword').addEventListener('click', function () {
        const p = document.getElementById('confirm_password');
        const i = document.getElementById('confirmPasswordIcon');
        p.type = p.type === 'password' ? 'text' : 'password';
        i.classList.toggle('bi-eye'); i.classList.toggle('bi-eye-slash');
    });

    // Live password strength + checklist
    const pwd = document.getElementById('password');
    const fill = document.getElementById('pwdStrengthFill');
    const strengthText = document.getElementById('pwdStrengthText');

    function updatePasswordMeter() {
        const v = pwd.value;
        const len = v.length >= 8;
        const up = /[A-Z]/.test(v);
        const lo = /[a-z]/.test(v);
        const num = /[0-9]/.test(v);
        const sym = /[^A-Za-z0-9]/.test(v);
        const score = [len, up, lo, num, sym].filter(Boolean).length;

        function setRow(id, ok, text) {
            const el = document.getElementById(id);
            const iconClass = ok ? 'bi-check-circle-fill' : 'bi-circle';
            el.className = 'pwd-check' + (ok ? ' ok' : '');
            el.innerHTML = '<i class="bi ' + iconClass + '"></i> ' + text;
        }
        setRow('chk-len', len, 'Minimum 8 characters');
        setRow('chk-up', up, 'Uppercase letter');
        setRow('chk-lo', lo, 'Lowercase letter');
        setRow('chk-num', num, 'Number');
        setRow('chk-sym', sym, 'Special symbol');

        let label = '—', pct = 0, color = '#e9ecef';
        if (v.length === 0) { label = '—'; pct = 0; }
        else if (score <= 2) { label = 'Weak'; pct = 33; color = '#dc3545'; }
        else if (score <= 4) { label = 'Medium'; pct = 66; color = '#ffc107'; }
        else { label = 'Strong'; pct = 100; color = '#198754'; }

        strengthText.textContent = label;
        fill.style.width = pct + '%';
        fill.style.background = v.length ? color : '#e9ecef';
    }
    pwd.addEventListener('input', updatePasswordMeter);
    updatePasswordMeter();

    function passwordStrongClient(v) {
        return v.length >= 8 && /[A-Z]/.test(v) && /[a-z]/.test(v) && /[0-9]/.test(v) && /[^A-Za-z0-9]/.test(v);
    }

    form.addEventListener('submit', function (e) {
        hideAjaxAlert();
        document.getElementById('fullNameError').textContent = '';
        document.getElementById('studentIdError').textContent = '';
        emailError.textContent = '';
        document.getElementById('phoneError').textContent = '';
        document.getElementById('passwordError').textContent = '';
        document.getElementById('confirmPasswordError').textContent = '';
        verifyCodeError.textContent = '';

        let ok = true;
        function err(el, id, msg) {
            document.getElementById(id).textContent = msg;
            el.classList.add('is-invalid');
            ok = false;
        }

        const fullName = document.getElementById('full_name').value.trim();
        const sid = document.getElementById('student_id').value.trim();
        const email = emailInput.value.trim();
        const pass = pwd.value;
        const pass2 = document.getElementById('confirm_password').value;

        if (!fullName) err(document.getElementById('full_name'), 'fullNameError', 'Full Name is required.');
        else document.getElementById('full_name').classList.remove('is-invalid');

        if (!sid) err(document.getElementById('student_id'), 'studentIdError', 'Student ID is required.');
        else document.getElementById('student_id').classList.remove('is-invalid');

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) err(emailInput, 'emailError', 'Valid email is required.');
        else if (!emailVerified || email.toLowerCase() !== String(REG.verifiedEmail || '').toLowerCase()) {
            err(emailInput, 'emailError', 'Please verify your email before registering.');
        } else emailInput.classList.remove('is-invalid');

        if (!pass) err(pwd, 'passwordError', 'Password is required.');
        else if (!passwordStrongClient(pass)) err(pwd, 'passwordError', 'Password does not meet strength requirements.');
        else pwd.classList.remove('is-invalid');

        if (!pass2) err(document.getElementById('confirm_password'), 'confirmPasswordError', 'Confirm Password is required.');
        else if (pass !== pass2) err(document.getElementById('confirm_password'), 'confirmPasswordError', 'Passwords do not match.');
        else document.getElementById('confirm_password').classList.remove('is-invalid');

        if (!ok) e.preventDefault();
    });

    <?php if ($success_message !== ''): ?>
    setTimeout(function () { window.location.href = 'login.php'; }, 2500);
    <?php endif; ?>
})();
</script>
</body>
</html>
