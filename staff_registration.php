<?php
// =========================
// Scholar Hub - Staff Registration
// Staff gate code + email verification (PHPMailer) + strong password
// =========================

session_start();
require __DIR__ . '/db.php';
require_once __DIR__ . '/includes/mail_helper.php';

$STAFF_REGISTRATION_CODE = 'JOINSTAFF67';
$STAFF_CODE_TTL_SECONDS  = 300; // staff gate

const STAFF_EMAIL_REG_TTL_SECONDS = 300;
const STAFF_EMAIL_REG_COOLDOWN    = 60;

function staff_registration_password_errors(string $p): array
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

function staff_users_has_email_verified_column(mysqli $conn): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $res = $conn->query("SHOW COLUMNS FROM users LIKE 'email_verified'");
    $cache = ($res instanceof mysqli_result && $res->num_rows > 0);
    return $cache;
}

function staff_reg_clear_email_verify_session(): void
{
    unset(
        $_SESSION['staff_email_reg_verify_email'],
        $_SESSION['staff_email_reg_verify_secret'],
        $_SESSION['staff_email_reg_verify_hmac'],
        $_SESSION['staff_email_reg_verify_expires'],
        $_SESSION['staff_email_reg_verify_sent_at'],
        $_SESSION['staff_email_reg_verified'],
        $_SESSION['staff_email_reg_verified_email']
    );
}

/** Staff gate must be valid for email AJAX + registration form */
function staff_gate_ok(): bool
{
    $ok = !empty($_SESSION['staff_code_verified']);
    $at = (int) ($_SESSION['staff_code_verified_at'] ?? 0);
    if (!$ok || !$at) {
        return false;
    }
    return (time() - $at) <= $GLOBALS['STAFF_CODE_TTL_SECONDS'];
}

// -------------------------
// AJAX: staff email send / verify (after staff code; separate session keys from student register)
// -------------------------
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {
    header('Content-Type: application/json; charset=UTF-8');

    if (!staff_gate_ok()) {
        echo json_encode(['success' => false, 'message' => 'Staff access expired. Enter the staff code again.']);
        exit;
    }

    $ajax = $_POST['staff_reg_action'] ?? '';

    if ($ajax === 'send_email_code') {
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
        $same = strcasecmp($email, (string) ($_SESSION['staff_email_reg_verify_email'] ?? '')) === 0;
        if ($same && isset($_SESSION['staff_email_reg_verify_sent_at']) && ($now - (int) $_SESSION['staff_email_reg_verify_sent_at']) < STAFF_EMAIL_REG_COOLDOWN) {
            $wait = STAFF_EMAIL_REG_COOLDOWN - ($now - (int) $_SESSION['staff_email_reg_verify_sent_at']);
            echo json_encode(['success' => false, 'message' => 'Please wait before resending the code.', 'cooldown' => $wait]);
            exit;
        }

        $_SESSION['staff_email_reg_verified'] = false;
        unset($_SESSION['staff_email_reg_verified_email']);

        $code   = (string) random_int(100000, 999999);
        $secret = bin2hex(random_bytes(16));
        $_SESSION['staff_email_reg_verify_secret']  = $secret;
        $_SESSION['staff_email_reg_verify_hmac']      = hash_hmac('sha256', $code, $secret);
        $_SESSION['staff_email_reg_verify_email']     = $email;
        $_SESSION['staff_email_reg_verify_expires']   = $now + STAFF_EMAIL_REG_TTL_SECONDS;
        $_SESSION['staff_email_reg_verify_sent_at']   = $now;

        $body = "Your Scholar Hub staff registration email verification code is: {$code}\n\nThis code expires in 5 minutes.\nIf you did not request this, ignore this email.";
        $send = scholarhub_send_mail($email, 'Scholar Hub — Staff Email Verification Code', $body);
        if (!$send['success']) {
            staff_reg_clear_email_verify_session();
            echo json_encode(['success' => false, 'message' => $send['error'] ?? 'Failed to send email.']);
            exit;
        }

        echo json_encode([
            'success'    => true,
            'message'    => 'Verification code sent to your email.',
            'expires_in' => STAFF_EMAIL_REG_TTL_SECONDS,
            'cooldown'   => STAFF_EMAIL_REG_COOLDOWN,
        ]);
        exit;
    }

    if ($ajax === 'verify_email_code') {
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

        if (strcasecmp($email, (string) ($_SESSION['staff_email_reg_verify_email'] ?? '')) !== 0) {
            echo json_encode(['success' => false, 'message' => 'Send a code to this email first.']);
            exit;
        }

        if (time() > (int) ($_SESSION['staff_email_reg_verify_expires'] ?? 0)) {
            echo json_encode(['success' => false, 'message' => 'Code expired. Request a new code.']);
            exit;
        }

        $secret = (string) ($_SESSION['staff_email_reg_verify_secret'] ?? '');
        $expect = (string) ($_SESSION['staff_email_reg_verify_hmac'] ?? '');
        $calc   = hash_hmac('sha256', $code, $secret);

        if ($expect === '' || !hash_equals($expect, $calc)) {
            echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
            exit;
        }

        $_SESSION['staff_email_reg_verified']      = true;
        $_SESSION['staff_email_reg_verified_email'] = strtolower($email);
        unset($_SESSION['staff_email_reg_verify_hmac'], $_SESSION['staff_email_reg_verify_secret']);

        echo json_encode(['success' => true, 'message' => 'Email verified successfully']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// Reset staff gate + email verification
if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    unset($_SESSION['staff_code_verified'], $_SESSION['staff_code_verified_at']);
    staff_reg_clear_email_verify_session();
}

// Page state
$code_verified = $_SESSION['staff_code_verified'] ?? false;
$verified_at = $_SESSION['staff_code_verified_at'] ?? 0;

if ($code_verified && (time() - (int) $verified_at) > $STAFF_CODE_TTL_SECONDS) {
    $code_verified = false;
    unset($_SESSION['staff_code_verified'], $_SESSION['staff_code_verified_at']);
    staff_reg_clear_email_verify_session();
}
$code_error = '';

$full_name = $staff_id = $email = $phone = '';
$errors = [];
$success_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // -------------------------
    // Step 1: Verify staff code
    // -------------------------
    if ($action === 'verify_code') {
        $input_code = trim($_POST['staff_code'] ?? '');

        if ($input_code === $STAFF_REGISTRATION_CODE) {
            $_SESSION['staff_code_verified'] = true;
            $_SESSION['staff_code_verified_at'] = time();
            // Prevent session fixation after a privileged step
            session_regenerate_id(true);
            $code_verified = true;
        } else {
            $code_error = 'Invalid staff registration code';
            $_SESSION['staff_code_verified'] = false;
            unset($_SESSION['staff_code_verified_at']);
            $code_verified = false;
        }
    }

    // -------------------------
    // Step 2: Register staff
    // -------------------------
    if ($action === 'register_staff') {
        // IMPORTANT: enforce verification on the server
        $code_verified = $_SESSION['staff_code_verified'] ?? false;
        $verified_at = $_SESSION['staff_code_verified_at'] ?? 0;
        $is_expired = (!$verified_at) || ((time() - (int)$verified_at) > $STAFF_CODE_TTL_SECONDS);

        if (!$code_verified || $is_expired) {
            $code_error = 'Invalid staff registration code';
            unset($_SESSION['staff_code_verified'], $_SESSION['staff_code_verified_at']);
            staff_reg_clear_email_verify_session();
        } else {
            // Get and trim form values
            $full_name  = trim($_POST['full_name'] ?? '');
            $staff_id   = trim($_POST['staff_id'] ?? '');
            $email      = trim($_POST['email'] ?? '');
            $phone      = trim($_POST['phone'] ?? '');
            $password   = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            // Backend validation
            if ($full_name === '') {
                $errors['full_name'] = 'Full Name is required.';
            }

            if ($staff_id === '') {
                $errors['staff_id'] = 'Staff ID is required.';
            }

            if ($email === '') {
                $errors['email'] = 'Email is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Please enter a valid email address.';
            } else {
                $ev = !empty($_SESSION['staff_email_reg_verified']) && !empty($_SESSION['staff_email_reg_verified_email']);
                if ($ev && strtolower($email) !== (string) $_SESSION['staff_email_reg_verified_email']) {
                    $errors['email'] = 'Email changed after verification. Send and verify the code again.';
                } elseif (!$ev) {
                    $errors['email'] = 'Please verify your email before registering.';
                }
            }

            $pwErrs = staff_registration_password_errors((string) $password);
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

            // Check duplicates only if basic validation passed
            if (empty($errors)) {
                // Duplicate email check
                $sql = "SELECT id FROM users WHERE email = ? LIMIT 1";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $stmt->store_result();
                    if ($stmt->num_rows > 0) {
                        $errors['email'] = 'This email is already registered.';
                    }
                    $stmt->close();
                } else {
                    $errors['general'] = 'Database error. Please try again later.';
                }

                // Duplicate staff id check (stored in users.user_id)
                if (empty($errors['general']) && empty($errors['email'])) {
                    $sql = "SELECT id FROM users WHERE user_id = ? LIMIT 1";
                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("s", $staff_id);
                        $stmt->execute();
                        $stmt->store_result();
                        if ($stmt->num_rows > 0) {
                            $errors['staff_id'] = 'This Staff ID is already registered.';
                        }
                        $stmt->close();
                    } else {
                        $errors['general'] = 'Database error. Please try again later.';
                    }
                }

                if (empty($errors)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $role = 'staff';
                    $hasCol = staff_users_has_email_verified_column($conn);

                    if ($hasCol) {
                        $sql = "INSERT INTO users (role, full_name, user_id, email, phone, password, email_verified, verification_code, verification_expiry)
                                VALUES (?, ?, ?, ?, ?, ?, 1, NULL, NULL)";
                        $stmt = $conn->prepare($sql);
                        if ($stmt) {
                            $stmt->bind_param("ssssss", $role, $full_name, $staff_id, $email, $phone, $hashed_password);
                        }
                    } else {
                        $sql = "INSERT INTO users (role, full_name, user_id, email, phone, password)
                                VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        if ($stmt) {
                            $stmt->bind_param("ssssss", $role, $full_name, $staff_id, $email, $phone, $hashed_password);
                        }
                    }

                    if (!isset($stmt) || !$stmt) {
                        $errors['general'] = 'Database error. Run sql/alter_users_email_verification.sql if the schema is outdated.';
                    } elseif ($stmt->execute()) {
                        $full_name = $staff_id = $email = $phone = '';
                        $success_message = 'Registration successful! Redirecting to login page...';
                        unset($_SESSION['staff_code_verified'], $_SESSION['staff_code_verified_at']);
                        staff_reg_clear_email_verify_session();
                    } else {
                        $errors['general'] = 'Registration failed. Please try again.';
                    }
                    if (isset($stmt) && $stmt) {
                        $stmt->close();
                    }
                }
            }
        }
    }
}

$staff_email_verified_ui = !empty($_SESSION['staff_email_reg_verified']) && !empty($_SESSION['staff_email_reg_verified_email']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Scholar Hub - Staff Registration</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
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

        @media (max-width: 767.98px) {
            body { background-attachment: scroll; }
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 0;
        }

        .page-wrapper {
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

        .card-box {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.25);
            max-width: min(520px, 100%);
            width: 100%;
            padding: clamp(1.15rem, 3.5vw, 2.25rem) clamp(1rem, 3vw, 2rem);
            box-sizing: border-box;
        }

        @media (min-width: 768px) {
            .card-box { padding: clamp(1.75rem, 2.5vw, 2.75rem) clamp(1.5rem, 2.5vw, 2.5rem); }
        }

        .card-box .app-title {
            font-size: clamp(1.2rem, 3.8vw, 1.75rem) !important;
        }

        .app-title {
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .app-subtitle {
            font-size: 0.95rem;
            color: #6c757d;
        }

        .divider-line {
            height: 2px;
            border-radius: 999px;
            background: linear-gradient(90deg, #000000, #6c757d);
            opacity: 0.2;
            margin: 0.75rem 0 1.5rem;
        }

        .form-label { font-weight: 500; font-size: 0.9rem; }
        .form-control, .form-control:focus { border-radius: 0.75rem; }

        .btn-black {
            background-color: #000;
            color: #fff;
            border-radius: 999px;
            padding: 0.7rem 1rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .btn-black:hover { background-color: #222; color: #fff; }

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

        /* Smooth appearance animation */
        .step-panel {
            transition: opacity 280ms ease, transform 280ms ease;
        }
        .step-panel.hidden {
            opacity: 0;
            transform: translateY(10px);
            pointer-events: none;
            height: 0;
            overflow: hidden;
        }
        .step-panel.visible {
            opacity: 1;
            transform: translateY(0);
        }

        @media (max-width: 575.98px) {
            .form-control { font-size: 16px; }
        }
    </style>
</head>
<body>
<div class="page-wrapper">
    <div class="card-box">
        <!-- Title -->
        <div class="text-center mb-3">
            <div class="d-inline-flex align-items-center justify-content-center mb-2">
                <i class="bi bi-mortarboard-fill fs-1 me-2 text-dark"></i>
                <span class="app-title fs-3">Scholar Hub</span>
            </div>
            <div class="app-subtitle">Sport Facility Booking System</div>
        </div>
        <div class="divider-line"></div>

        <!-- General errors -->
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger py-2" role="alert">
                <?php echo htmlspecialchars($errors['general']); ?>
            </div>
        <?php endif; ?>

        <!-- Step 1: Code verification -->
        <div id="codeStep" class="step-panel <?php echo $code_verified ? 'hidden' : 'visible'; ?>">
            <h5 class="mb-3">Enter Staff Registration Code</h5>

            <?php if (!empty($code_error) && !$code_verified): ?>
                <div class="alert alert-danger py-2" role="alert">
                    <?php echo htmlspecialchars($code_error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="codeForm" novalidate>
                <input type="hidden" name="action" value="verify_code">

                <label for="staff_code" class="form-label">Staff Registration Code</label>
                <div class="input-group mb-2">
                    <input
                        type="password"
                        class="form-control"
                        id="staff_code"
                        name="staff_code"
                        placeholder="Enter code"
                        required
                    >
                    <button class="btn btn-outline-secondary" type="button" id="toggleCode">
                        <i class="bi bi-eye-slash" id="codeIcon"></i>
                    </button>
                </div>
                <div class="text-danger error-text" id="codeError"></div>

                <div class="d-grid mt-3">
                    <button type="submit" class="btn btn-black">
                        <i class="bi bi-shield-lock me-1"></i> Verify Code
                    </button>
                </div>

                <div class="text-center mt-3">
                    <a href="login.php" class="text-primary text-link">Back to Login</a>
                </div>

                <div class="text-center mt-2">
                    <a href="staff_registration.php?reset=1" class="text-muted text-link" style="font-size: 0.9rem;">
                        Reset code verification
                    </a>
                </div>
            </form>
        </div>

        <!-- Step 2: Staff registration form -->
        <div id="registerStep" class="step-panel <?php echo $code_verified ? 'visible' : 'hidden'; ?>">
            <h5 class="mb-3">Staff Registration</h5>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success py-2" role="alert" id="successAlert">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($code_error) && !$code_verified): ?>
                <div class="alert alert-danger py-2" role="alert">
                    <?php echo htmlspecialchars($code_error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="staffRegisterForm" novalidate>
                <input type="hidden" name="action" value="register_staff">

                <div id="ajaxAlert" class="alert py-2 d-none" role="alert"></div>

                <!-- Full Name -->
                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input
                        type="text"
                        class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>"
                        id="full_name"
                        name="full_name"
                        value="<?php echo htmlspecialchars($full_name); ?>"
                        placeholder="Enter your full name"
                    >
                    <div class="text-danger error-text" id="fullNameError">
                        <?php echo isset($errors['full_name']) ? htmlspecialchars($errors['full_name']) : ''; ?>
                    </div>
                </div>

                <!-- Staff ID -->
                <div class="mb-3">
                    <label for="staff_id" class="form-label">Staff ID</label>
                    <input
                        type="text"
                        class="form-control <?php echo isset($errors['staff_id']) ? 'is-invalid' : ''; ?>"
                        id="staff_id"
                        name="staff_id"
                        value="<?php echo htmlspecialchars($staff_id); ?>"
                        placeholder="Enter your staff ID"
                    >
                    <div class="text-danger error-text" id="staffIdError">
                        <?php echo isset($errors['staff_id']) ? htmlspecialchars($errors['staff_id']) : ''; ?>
                    </div>
                </div>

                <!-- Email + verification -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <div class="input-group flex-nowrap">
                        <input
                            type="email"
                            class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                            id="email"
                            name="email"
                            value="<?php echo htmlspecialchars($email); ?>"
                            placeholder="your.email@university.edu"
                            autocomplete="email"
                        >
                        <button type="button" class="btn btn-outline-dark px-2 px-sm-3 flex-shrink-0" id="sendCodeBtn" title="Send verification code">
                            <span class="btn-label"><i class="bi bi-envelope me-1 d-none d-sm-inline"></i>Send Code</span>
                            <span class="btn-loading d-none spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                    <div class="text-danger error-text" id="emailError">
                        <?php echo isset($errors['email']) ? htmlspecialchars($errors['email']) : ''; ?>
                    </div>
                    <div class="form-text small" id="cooldownHint"></div>
                </div>

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
                    <div id="emailVerifiedBanner" class="alert alert-success py-2 mt-2 mb-0 small <?php echo $staff_email_verified_ui ? '' : 'd-none'; ?>">
                        <i class="bi bi-check-circle-fill me-1"></i> Email verified successfully
                    </div>
                </div>

                <!-- Phone -->
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone Number (optional)</label>
                    <input
                        type="text"
                        class="form-control"
                        id="phone"
                        name="phone"
                        value="<?php echo htmlspecialchars($phone); ?>"
                        placeholder="Enter your phone number"
                    >
                    <div class="text-danger error-text" id="phoneError"></div>
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input
                            type="password"
                            class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                            id="password"
                            name="password"
                            placeholder="Strong password"
                            autocomplete="new-password"
                        >
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
                    <div class="text-danger error-text" id="passwordError">
                        <?php echo isset($errors['password']) ? htmlspecialchars($errors['password']) : ''; ?>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <input
                            type="password"
                            class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Re-enter your password"
                            autocomplete="new-password"
                        >
                        <button class="btn btn-outline-secondary border-start-0" type="button" id="toggleConfirmPassword" aria-label="Show confirm password">
                            <i class="bi bi-eye-slash" id="confirmPasswordIcon"></i>
                        </button>
                    </div>
                    <div class="text-danger error-text" id="confirmPasswordError">
                        <?php echo isset($errors['confirm_password']) ? htmlspecialchars($errors['confirm_password']) : ''; ?>
                    </div>
                </div>

                <div class="d-grid mb-2">
                    <button type="submit" class="btn btn-black">
                        <i class="bi bi-person-badge me-1"></i> Register
                    </button>
                </div>

                <div class="text-center mt-2">
                    <span class="text-muted">Already have an account? </span>
                    <a href="login.php" class="text-primary text-link">Login</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function () {
    'use strict';

    const REG = <?php echo json_encode([
        'emailVerified' => $staff_email_verified_ui,
        'verifiedEmail' => $staff_email_verified_ui ? (string) ($_SESSION['staff_email_reg_verified_email'] ?? '') : '',
    ]); ?>;

    // Step 1: staff gate
    const staffGateInput = document.getElementById('staff_code');
    const toggleCodeBtn = document.getElementById('toggleCode');
    const codeIcon = document.getElementById('codeIcon');
    const codeForm = document.getElementById('codeForm');
    const codeErrorEl = document.getElementById('codeError');

    if (toggleCodeBtn && staffGateInput) {
        toggleCodeBtn.addEventListener('click', function () {
            const type = staffGateInput.type === 'password' ? 'text' : 'password';
            staffGateInput.type = type;
            codeIcon.classList.toggle('bi-eye');
            codeIcon.classList.toggle('bi-eye-slash');
        });
    }

    if (codeForm) {
        codeForm.addEventListener('submit', function (e) {
            if (!staffGateInput) return;
            codeErrorEl.textContent = '';
            const value = staffGateInput.value.trim();
            if (value === '') {
                e.preventDefault();
                codeErrorEl.textContent = 'Code is required.';
            }
        });
    }

    // Step 2: registration + email verify + password meter
    const sendBtn = document.getElementById('sendCodeBtn');
    const verifyBtn = document.getElementById('verifyCodeBtn');
    const emailInput = document.getElementById('email');
    const emailVerifyCodeInput = document.getElementById('verification_code');
    const ajaxAlert = document.getElementById('ajaxAlert');
    const emailError = document.getElementById('emailError');
    const verifyCodeError = document.getElementById('verifyCodeError');
    const cooldownHint = document.getElementById('cooldownHint');
    const banner = document.getElementById('emailVerifiedBanner');
    const staffRegisterForm = document.getElementById('staffRegisterForm');

    const passwordInput = document.getElementById('password');
    const togglePasswordBtn = document.getElementById('togglePassword');
    const passwordIcon = document.getElementById('passwordIcon');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const toggleConfirmPasswordBtn = document.getElementById('toggleConfirmPassword');
    const confirmPasswordIcon = document.getElementById('confirmPasswordIcon');

    const fullNameError = document.getElementById('fullNameError');
    const staffIdError = document.getElementById('staffIdError');
    const phoneError = document.getElementById('phoneError');
    const passwordError = document.getElementById('passwordError');
    const confirmPasswordError = document.getElementById('confirmPasswordError');

    let cooldownTimer = null;

    function showAjaxAlert(type, msg) {
        if (!ajaxAlert) return;
        ajaxAlert.className = 'alert py-2 ' + (type === 'success' ? 'alert-success' : type === 'danger' ? 'alert-danger' : 'alert-info');
        ajaxAlert.textContent = msg;
        ajaxAlert.classList.remove('d-none');
    }
    function hideAjaxAlert() {
        if (ajaxAlert) ajaxAlert.classList.add('d-none');
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
        return fetch('staff_registration.php', {
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
        if (!sendBtn || !cooldownHint) return;
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

    if (banner && REG.emailVerified) {
        banner.classList.remove('d-none');
    }

    if (emailVerifyCodeInput) {
        emailVerifyCodeInput.addEventListener('input', function () {
            emailVerifyCodeInput.value = emailVerifyCodeInput.value.replace(/\D/g, '').slice(0, 6);
        });
    }

    if (sendBtn && emailInput) {
        sendBtn.addEventListener('click', function () {
            hideAjaxAlert();
            if (emailError) emailError.textContent = '';
            if (verifyCodeError) verifyCodeError.textContent = '';
            const email = emailInput.value.trim();
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                if (emailError) emailError.textContent = 'Please enter a valid email address.';
                emailInput.classList.add('is-invalid');
                return;
            }
            emailInput.classList.remove('is-invalid');
            setBtnLoading(sendBtn, true);
            const body = 'staff_reg_action=send_email_code&email=' + encodeURIComponent(email);
            postJson(body).then(function (data) {
                setBtnLoading(sendBtn, false);
                if (data.success) {
                    showAjaxAlert('success', data.message || 'Code sent.');
                    if (banner) banner.classList.add('d-none');
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
    }

    if (verifyBtn && emailInput && emailVerifyCodeInput) {
        verifyBtn.addEventListener('click', function () {
            hideAjaxAlert();
            if (verifyCodeError) verifyCodeError.textContent = '';
            const email = emailInput.value.trim();
            const code = emailVerifyCodeInput.value.trim();
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                if (emailError) emailError.textContent = 'Enter the same email you sent the code to.';
                return;
            }
            if (code.length !== 6) {
                if (verifyCodeError) verifyCodeError.textContent = 'Enter the 6-digit code.';
                return;
            }
            setBtnLoading(verifyBtn, true);
            const body = 'staff_reg_action=verify_email_code&email=' + encodeURIComponent(email) + '&verification_code=' + encodeURIComponent(code);
            postJson(body).then(function (data) {
                setBtnLoading(verifyBtn, false);
                if (data.success) {
                    showAjaxAlert('success', data.message || 'Verified.');
                    REG.emailVerified = true;
                    REG.verifiedEmail = email.toLowerCase();
                    if (banner) banner.classList.remove('d-none');
                } else {
                    if (verifyCodeError) verifyCodeError.textContent = data.message || 'Verification failed.';
                }
            }).catch(function () {
                setBtnLoading(verifyBtn, false);
                showAjaxAlert('danger', 'Network error.');
            });
        });
    }

    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener('click', function () {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            passwordIcon.classList.toggle('bi-eye');
            passwordIcon.classList.toggle('bi-eye-slash');
        });
    }

    if (toggleConfirmPasswordBtn && confirmPasswordInput) {
        toggleConfirmPasswordBtn.addEventListener('click', function () {
            const type = confirmPasswordInput.type === 'password' ? 'text' : 'password';
            confirmPasswordInput.type = type;
            confirmPasswordIcon.classList.toggle('bi-eye');
            confirmPasswordIcon.classList.toggle('bi-eye-slash');
        });
    }

    const fill = document.getElementById('pwdStrengthFill');
    const strengthText = document.getElementById('pwdStrengthText');

    function updatePasswordMeter() {
        if (!passwordInput || !fill || !strengthText) return;
        const v = passwordInput.value;
        const len = v.length >= 8;
        const up = /[A-Z]/.test(v);
        const lo = /[a-z]/.test(v);
        const num = /[0-9]/.test(v);
        const sym = /[^A-Za-z0-9]/.test(v);
        const score = [len, up, lo, num, sym].filter(Boolean).length;

        function setRow(id, ok, text) {
            const el = document.getElementById(id);
            if (!el) return;
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

    if (passwordInput) {
        passwordInput.addEventListener('input', updatePasswordMeter);
        updatePasswordMeter();
    }

    function passwordStrongClient(v) {
        return v.length >= 8 && /[A-Z]/.test(v) && /[a-z]/.test(v) && /[0-9]/.test(v) && /[^A-Za-z0-9]/.test(v);
    }

    if (staffRegisterForm) {
        staffRegisterForm.addEventListener('submit', function (e) {
            hideAjaxAlert();

            const fullNameEl = document.getElementById('full_name');
            const staffIdEl = document.getElementById('staff_id');
            const phoneEl = document.getElementById('phone');

            if (fullNameError) fullNameError.textContent = '';
            if (staffIdError) staffIdError.textContent = '';
            if (emailError) emailError.textContent = '';
            if (phoneError) phoneError.textContent = '';
            if (passwordError) passwordError.textContent = '';
            if (confirmPasswordError) confirmPasswordError.textContent = '';
            if (verifyCodeError) verifyCodeError.textContent = '';

            let isValid = true;

            function setErr(inputEl, errEl, msg) {
                if (errEl) errEl.textContent = msg;
                if (inputEl) inputEl.classList.add('is-invalid');
                isValid = false;
            }
            function clearErr(inputEl, errEl) {
                if (errEl) errEl.textContent = '';
                if (inputEl) inputEl.classList.remove('is-invalid');
            }

            const fullNameValue = fullNameEl ? fullNameEl.value.trim() : '';
            const staffIdValue = staffIdEl ? staffIdEl.value.trim() : '';
            const emailValue = emailInput ? emailInput.value.trim() : '';
            const passwordValue = passwordInput ? passwordInput.value : '';
            const confirmPasswordValue = confirmPasswordInput ? confirmPasswordInput.value : '';

            if (fullNameEl) {
                if (fullNameValue === '') setErr(fullNameEl, fullNameError, 'Full Name is required.');
                else clearErr(fullNameEl, fullNameError);
            }

            if (staffIdEl) {
                if (staffIdValue === '') setErr(staffIdEl, staffIdError, 'Staff ID is required.');
                else clearErr(staffIdEl, staffIdError);
            }

            if (emailInput) {
                if (emailValue === '') setErr(emailInput, emailError, 'Email is required.');
                else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailValue)) {
                    setErr(emailInput, emailError, 'Please enter a valid email address.');
                } else clearErr(emailInput, emailError);
            }

            if (phoneEl && phoneError) {
                phoneError.textContent = '';
                phoneEl.classList.remove('is-invalid');
            }

            if (passwordInput) {
                if (passwordValue === '') setErr(passwordInput, passwordError, 'Password is required.');
                else if (!passwordStrongClient(passwordValue)) {
                    setErr(passwordInput, passwordError, 'Password does not meet strength requirements.');
                } else clearErr(passwordInput, passwordError);
            }

            if (confirmPasswordInput) {
                if (confirmPasswordValue === '') {
                    setErr(confirmPasswordInput, confirmPasswordError, 'Confirm Password is required.');
                } else if (passwordValue !== confirmPasswordValue) {
                    setErr(confirmPasswordInput, confirmPasswordError, 'Passwords do not match.');
                } else clearErr(confirmPasswordInput, confirmPasswordError);
            }

            if (emailInput && emailValue && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailValue)) {
                if (!REG.emailVerified || emailValue.toLowerCase() !== String(REG.verifiedEmail || '').toLowerCase()) {
                    setErr(emailInput, emailError, 'Please verify your email before registering.');
                }
            }

            if (!isValid) e.preventDefault();
        });
    }

    <?php if (!empty($success_message)) : ?>
    setTimeout(function () {
        window.location.href = 'login.php';
    }, 2500);
    <?php endif; ?>
})();
</script>
</body>
</html>

