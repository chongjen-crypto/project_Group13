<?php
// =========================
// Scholar Hub - Forgot Password (3-step)
// Email -> Verify Code -> Reset Password
// =========================

session_start();
require 'db.php';

// -------------------------
// PHPMailer Setup
// -------------------------
// This project currently does NOT include PHPMailer.
// Choose ONE option below:
//
// OPTION A (Recommended): Install with Composer
//   composer require phpmailer/phpmailer
//   then keep: require __DIR__ . '/vendor/autoload.php';
//
// OPTION B: Manual download
//   Download PHPMailer and place it in: /PHPMailer/src/
//   then uncomment the 3 require lines below.
//
// require __DIR__ . '/vendor/autoload.php';
//
require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';
//
// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception;

// -------------------------
// Settings
// -------------------------
$CODE_TTL_SECONDS = 600; // 10 minutes
$MAX_ATTEMPTS = 5;

// Page state
$step = $_SESSION['fp_step'] ?? 1; // 1=email, 2=verify, 3=reset
$email = $_SESSION['fp_email'] ?? '';

$errors = [];
$success = '';

// Helper: safely move between steps
function set_fp_step(int $new_step): void {
    $_SESSION['fp_step'] = $new_step;
}

function clear_fp_session(): void {
    unset(
        $_SESSION['fp_step'],
        $_SESSION['fp_email'],
        $_SESSION['fp_code'],
        $_SESSION['fp_code_expires_at'],
        $_SESSION['fp_attempts'],
        $_SESSION['fp_verified']
    );
}

// Optional reset (user wants to restart flow)
if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    clear_fp_session();
    $step = 1;
    $email = '';
}

// -------------------------
// Handle form posts
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // STEP 1: Request verification code
    if ($action === 'send_code') {
        $input_email = trim($_POST['email'] ?? '');
        $email = $input_email;

        if ($input_email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($input_email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        } else {
            // Check email exists
            $sql = "SELECT id FROM users WHERE email = ? LIMIT 1";
            $stmt = mysqli_prepare($conn, $sql);

            if (!$stmt) {
                $errors['general'] = 'Database error. Please try again.';
            } else {
                mysqli_stmt_bind_param($stmt, "s", $input_email);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $user = $result ? mysqli_fetch_assoc($result) : null;
                mysqli_stmt_close($stmt);

                if (!$user) {
                    $errors['general'] = 'Email not found';
                } else {
                    // Generate 6-digit code
                    $code = (string)random_int(100000, 999999);

                    $_SESSION['fp_email'] = $input_email;
                    $_SESSION['fp_code'] = $code;
                    $_SESSION['fp_code_expires_at'] = time() + $CODE_TTL_SECONDS;
                    $_SESSION['fp_attempts'] = 0;
                    $_SESSION['fp_verified'] = false;

                    // Send code email using PHPMailer
                    $mail_sent = false;
                    $mail_error = '';

                    try {
                        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'kuangkaize@gmail.com';
                            $mail->Password = 'fryuizoyslpdftvr';
                            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port = 587;

                            $mail->setFrom('kuangkaize@gmail.com', 'Scholar Hub');
                            $mail->addAddress($input_email);

                            $mail->isHTML(false);
                            $mail->Subject = 'Scholar Hub Password Reset Code';
                            $mail->Body = "Your verification code is: {$code}";

                            $mail->send();
                            $mail_sent = true;
                        } else {
                            $mail_error = "PHPMailer is not installed. Please install PHPMailer (Composer or manual) and try again.";
                        }
                    } catch (Throwable $e) {
                        $mail_error = $e->getMessage();
                    }

                    if (!$mail_sent) {
                        // Keep step 1, show error
                        $errors['general'] = $mail_error !== '' ? $mail_error : 'Failed to send email. Please try again.';
                    } else {
                        $success = 'Verification code sent successfully';
                        set_fp_step(2);
                        $step = 2;
                    }
                }
            }
        }
    }

    // STEP 2: Verify code
    if ($action === 'verify_code') {
        $stored_email = $_SESSION['fp_email'] ?? '';
        $stored_code = $_SESSION['fp_code'] ?? '';
        $expires_at = (int)($_SESSION['fp_code_expires_at'] ?? 0);
        $attempts = (int)($_SESSION['fp_attempts'] ?? 0);

        $input_code = trim($_POST['code'] ?? '');

        if ($stored_email === '' || $stored_code === '' || $expires_at === 0) {
            $errors['general'] = 'Session expired. Please request a new code.';
            clear_fp_session();
            $step = 1;
        } elseif (time() > $expires_at) {
            $errors['general'] = 'Session expired. Please request a new code.';
            clear_fp_session();
            $step = 1;
        } elseif ($attempts >= $MAX_ATTEMPTS) {
            $errors['general'] = 'Too many attempts. Please request a new code.';
            clear_fp_session();
            $step = 1;
        } elseif ($input_code === '') {
            $errors['code'] = 'Verification code is required.';
            set_fp_step(2);
            $step = 2;
        } else {
            $_SESSION['fp_attempts'] = $attempts + 1;

            if (!hash_equals($stored_code, $input_code)) {
                $errors['general'] = 'Invalid verification code';
                set_fp_step(2);
                $step = 2;
            } else {
                $_SESSION['fp_verified'] = true;
                set_fp_step(3);
                $step = 3;
            }
        }
    }

    // STEP 3: Reset password
    if ($action === 'reset_password') {
        $stored_email = $_SESSION['fp_email'] ?? '';
        $verified = (bool)($_SESSION['fp_verified'] ?? false);
        $expires_at = (int)($_SESSION['fp_code_expires_at'] ?? 0);

        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($stored_email === '' || !$verified || $expires_at === 0 || time() > $expires_at) {
            $errors['general'] = 'Session expired. Please restart password reset.';
            clear_fp_session();
            $step = 1;
        } else {
            if ($new_password === '') {
                $errors['new_password'] = 'New Password is required.';
            }
            if ($confirm_password === '') {
                $errors['confirm_password'] = 'Confirm Password is required.';
            } elseif ($new_password !== $confirm_password) {
                $errors['confirm_password'] = 'Passwords do not match.';
            }

            if (empty($errors)) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

                $sql = "UPDATE users SET password = ? WHERE email = ? LIMIT 1";
                $stmt = mysqli_prepare($conn, $sql);
                if (!$stmt) {
                    $errors['general'] = 'Database error. Please try again.';
                    set_fp_step(3);
                    $step = 3;
                } else {
                    mysqli_stmt_bind_param($stmt, "ss", $new_hash, $stored_email);
                    $ok = mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    if (!$ok) {
                        $errors['general'] = 'Failed to update password. Please try again.';
                        set_fp_step(3);
                        $step = 3;
                    } else {
                        $success = 'Password updated successfully';
                        clear_fp_session();
                        // Tell UI to redirect
                        $_SESSION['fp_redirect_login'] = true;
                    }
                }
            } else {
                set_fp_step(3);
                $step = 3;
            }
        }
    }
}

// Pull redirect flag for UI, then clear
$redirect_login = (bool)($_SESSION['fp_redirect_login'] ?? false);
unset($_SESSION['fp_redirect_login']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Scholar Hub - Forgot Password</title>

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
        .app-subtitle { font-size: 0.95rem; color: #6c757d; }

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

        /* Step transitions */
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

        /* Button loading */
        .btn-loading {
            opacity: 0.85;
            pointer-events: none;
        }
        .spinner-mini {
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(255,255,255,0.35);
            border-top-color: rgba(255,255,255,0.95);
            border-radius: 50%;
            display: inline-block;
            animation: spin 0.8s linear infinite;
            vertical-align: -0.2rem;
            margin-right: 0.5rem;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
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

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger py-2" role="alert">
                <?php echo htmlspecialchars($errors['general']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success py-2" role="alert" id="successAlert">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- STEP 1: Email -->
        <div id="step1" class="step-panel <?php echo ($step === 1) ? 'visible' : 'hidden'; ?>">
            <h5 class="mb-3">Forgot Password</h5>
            <p class="text-muted mb-3" style="font-size: 0.95rem;">
                Enter your email to receive a 6-digit verification code.
            </p>

            <form method="POST" id="emailForm" novalidate>
                <input type="hidden" name="action" value="send_code">

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input
                        type="email"
                        class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars($email); ?>"
                        placeholder="Enter your email"
                        required
                    >
                    <div class="text-danger error-text" id="emailError">
                        <?php echo isset($errors['email']) ? htmlspecialchars($errors['email']) : ''; ?>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-black" id="sendCodeBtn">
                        <i class="bi bi-envelope-arrow-up me-1"></i> Send Verification Code
                    </button>
                </div>

                <div class="text-center mt-3">
                    <a href="login.php" class="text-primary text-link">Back to Login</a>
                </div>
            </form>
        </div>

        <!-- STEP 2: Verify code -->
        <div id="step2" class="step-panel <?php echo ($step === 2) ? 'visible' : 'hidden'; ?>">
            <h5 class="mb-3">Verify Code</h5>
            <p class="text-muted mb-3" style="font-size: 0.95rem;">
                Enter the 6-digit code sent to <strong><?php echo htmlspecialchars($_SESSION['fp_email'] ?? ''); ?></strong>.
            </p>

            <form method="POST" id="codeForm" novalidate>
                <input type="hidden" name="action" value="verify_code">

                <div class="mb-3">
                    <label for="code" class="form-label">Verification Code</label>
                    <input
                        type="text"
                        inputmode="numeric"
                        class="form-control <?php echo isset($errors['code']) ? 'is-invalid' : ''; ?>"
                        id="code"
                        name="code"
                        maxlength="6"
                        placeholder="e.g. 123456"
                        required
                    >
                    <div class="text-danger error-text" id="codeError">
                        <?php echo isset($errors['code']) ? htmlspecialchars($errors['code']) : ''; ?>
                    </div>
                </div>

                <div class="d-grid mb-2">
                    <button type="submit" class="btn btn-black" id="verifyBtn">
                        <i class="bi bi-shield-check me-1"></i> Verify Code
                    </button>
                </div>

                <div class="d-flex justify-content-between mt-2">
                    <a href="forgot_password.php?reset=1" class="text-muted text-link" style="font-size: 0.9rem;">
                        Use another email
                    </a>
                    <a href="login.php" class="text-primary text-link" style="font-size: 0.9rem;">
                        Back to Login
                    </a>
                </div>
            </form>
        </div>

        <!-- STEP 3: Reset password -->
        <div id="step3" class="step-panel <?php echo ($step === 3) ? 'visible' : 'hidden'; ?>">
            <h5 class="mb-3">Reset Password</h5>
            <p class="text-muted mb-3" style="font-size: 0.95rem;">
                Create a new password for <strong><?php echo htmlspecialchars($_SESSION['fp_email'] ?? ''); ?></strong>.
            </p>

            <form method="POST" id="resetForm" novalidate>
                <input type="hidden" name="action" value="reset_password">

                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <div class="input-group">
                        <input
                            type="password"
                            class="form-control <?php echo isset($errors['new_password']) ? 'is-invalid' : ''; ?>"
                            id="new_password"
                            name="new_password"
                            placeholder="Enter new password"
                            required
                        >
                        <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                            <i class="bi bi-eye-slash" id="newPassIcon"></i>
                        </button>
                    </div>
                    <div class="text-danger error-text" id="newPasswordError">
                        <?php echo isset($errors['new_password']) ? htmlspecialchars($errors['new_password']) : ''; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <input
                            type="password"
                            class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Re-enter new password"
                            required
                        >
                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                            <i class="bi bi-eye-slash" id="confirmPassIcon"></i>
                        </button>
                    </div>
                    <div class="text-danger error-text" id="confirmPasswordError">
                        <?php echo isset($errors['confirm_password']) ? htmlspecialchars($errors['confirm_password']) : ''; ?>
                    </div>
                </div>

                <div class="d-grid mb-2">
                    <button type="submit" class="btn btn-black" id="resetBtn">
                        <i class="bi bi-key me-1"></i> Update Password
                    </button>
                </div>

                <div class="d-flex justify-content-between mt-2">
                    <a href="forgot_password.php?reset=1" class="text-muted text-link" style="font-size: 0.9rem;">
                        Restart
                    </a>
                    <a href="login.php" class="text-primary text-link" style="font-size: 0.9rem;">
                        Back to Login
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // -------------------------
    // Button loading helper
    // -------------------------
    function setLoading(btn, loadingText) {
        if (!btn) return;
        btn.classList.add('btn-loading');
        const original = btn.innerHTML;
        btn.dataset.originalHtml = original;
        btn.innerHTML = '<span class="spinner-mini"></span>' + (loadingText || 'Please wait...');
    }

    // -------------------------
    // Email validation (step 1)
    // -------------------------
    const emailForm = document.getElementById('emailForm');
    const emailInput = document.getElementById('email');
    const emailError = document.getElementById('emailError');
    const sendCodeBtn = document.getElementById('sendCodeBtn');

    if (emailForm) {
        emailForm.addEventListener('submit', function(e) {
            emailError.textContent = '';
            emailInput.classList.remove('is-invalid');

            const value = (emailInput.value || '').trim();
            const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (value === '') {
                e.preventDefault();
                emailError.textContent = 'Email is required.';
                emailInput.classList.add('is-invalid');
                return;
            }
            if (!pattern.test(value)) {
                e.preventDefault();
                emailError.textContent = 'Please enter a valid email address.';
                emailInput.classList.add('is-invalid');
                return;
            }

            setLoading(sendCodeBtn, 'Sending...');
        });
    }

    // -------------------------
    // Code validation (step 2)
    // -------------------------
    const codeForm = document.getElementById('codeForm');
    const codeInput = document.getElementById('code');
    const codeError = document.getElementById('codeError');
    const verifyBtn = document.getElementById('verifyBtn');

    if (codeInput) {
        codeInput.addEventListener('input', function() {
            // Keep only digits
            codeInput.value = codeInput.value.replace(/\D/g, '').slice(0, 6);
        });
    }

    if (codeForm) {
        codeForm.addEventListener('submit', function(e) {
            codeError.textContent = '';
            codeInput.classList.remove('is-invalid');

            const value = (codeInput.value || '').trim();
            if (value.length !== 6) {
                e.preventDefault();
                codeError.textContent = 'Please enter the 6-digit code.';
                codeInput.classList.add('is-invalid');
                return;
            }
            setLoading(verifyBtn, 'Verifying...');
        });
    }

    // -------------------------
    // Password toggles (step 3)
    // -------------------------
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');

    const toggleNewPassword = document.getElementById('toggleNewPassword');
    const newPassIcon = document.getElementById('newPassIcon');

    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const confirmPassIcon = document.getElementById('confirmPassIcon');

    if (toggleNewPassword && newPasswordInput) {
        toggleNewPassword.addEventListener('click', function () {
            const type = newPasswordInput.type === 'password' ? 'text' : 'password';
            newPasswordInput.type = type;
            newPassIcon.classList.toggle('bi-eye');
            newPassIcon.classList.toggle('bi-eye-slash');
        });
    }

    if (toggleConfirmPassword && confirmPasswordInput) {
        toggleConfirmPassword.addEventListener('click', function () {
            const type = confirmPasswordInput.type === 'password' ? 'text' : 'password';
            confirmPasswordInput.type = type;
            confirmPassIcon.classList.toggle('bi-eye');
            confirmPassIcon.classList.toggle('bi-eye-slash');
        });
    }

    // -------------------------
    // Password match validation (step 3)
    // -------------------------
    const resetForm = document.getElementById('resetForm');
    const newPasswordError = document.getElementById('newPasswordError');
    const confirmPasswordError = document.getElementById('confirmPasswordError');
    const resetBtn = document.getElementById('resetBtn');

    if (resetForm) {
        resetForm.addEventListener('submit', function(e) {
            newPasswordError.textContent = '';
            confirmPasswordError.textContent = '';
            newPasswordInput.classList.remove('is-invalid');
            confirmPasswordInput.classList.remove('is-invalid');

            const p1 = newPasswordInput.value || '';
            const p2 = confirmPasswordInput.value || '';

            let ok = true;
            if (p1.trim() === '') {
                newPasswordError.textContent = 'New Password is required.';
                newPasswordInput.classList.add('is-invalid');
                ok = false;
            }
            if (p2.trim() === '') {
                confirmPasswordError.textContent = 'Confirm Password is required.';
                confirmPasswordInput.classList.add('is-invalid');
                ok = false;
            } else if (p1 !== p2) {
                confirmPasswordError.textContent = 'Passwords do not match.';
                confirmPasswordInput.classList.add('is-invalid');
                ok = false;
            }

            if (!ok) {
                e.preventDefault();
                return;
            }

            setLoading(resetBtn, 'Updating...');
        });
    }

    // -------------------------
    // Redirect after success
    // -------------------------
    <?php if ($redirect_login): ?>
    setTimeout(function () {
        window.location.href = 'login.php';
    }, 2200);
    <?php endif; ?>
</script>
</body>
</html>

