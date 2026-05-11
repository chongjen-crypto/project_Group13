<?php
// =========================
// Scholar Hub - Staff Registration
// =========================

session_start();
require 'db.php';

// Secret staff registration code (server-side enforced)
$STAFF_REGISTRATION_CODE = 'JOINSTAFF67';

// Page state
$code_verified = $_SESSION['staff_code_verified'] ?? false;
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
            $code_verified = true;
        } else {
            $code_error = 'Invalid staff registration code';
            $_SESSION['staff_code_verified'] = false;
            $code_verified = false;
        }
    }

    // -------------------------
    // Step 2: Register staff
    // -------------------------
    if ($action === 'register_staff') {
        // IMPORTANT: enforce verification on the server
        $code_verified = $_SESSION['staff_code_verified'] ?? false;
        if (!$code_verified) {
            $code_error = 'Invalid staff registration code';
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
            }

            if ($password === '') {
                $errors['password'] = 'Password is required.';
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

                // Insert staff account
                if (empty($errors)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $role = 'staff';

                    $sql = "INSERT INTO users (role, full_name, user_id, email, phone, password)
                            VALUES (?, ?, ?, ?, ?, ?)";
                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("ssssss", $role, $full_name, $staff_id, $email, $phone, $hashed_password);
                        if ($stmt->execute()) {
                            // Clear form values
                            $full_name = $staff_id = $email = $phone = '';
                            $success_message = 'Registration successful! Redirecting to login page...';
                        } else {
                            $errors['general'] = 'Registration failed. Please try again.';
                        }
                        $stmt->close();
                    } else {
                        $errors['general'] = 'Database error. Please try again later.';
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholar Hub - Staff Registration</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: url('assets/bg.png') center center / cover no-repeat fixed;
            position: relative;
            color: #212529;
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .card-box {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.25);
            max-width: 520px;
            width: 100%;
            padding: 2.25rem 2rem;
        }

        @media (min-width: 768px) {
            .card-box { padding: 2.75rem 2.5rem; }
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

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input
                        type="email"
                        class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars($email); ?>"
                        placeholder="Enter your email"
                    >
                    <div class="text-danger error-text" id="emailError">
                        <?php echo isset($errors['email']) ? htmlspecialchars($errors['email']) : ''; ?>
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
                            placeholder="Create a password"
                        >
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="bi bi-eye-slash" id="passwordIcon"></i>
                        </button>
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
                        >
                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
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
    // ==========
    // Step transition (smooth show/hide)
    // ==========
    const codeStep = document.getElementById('codeStep');
    const registerStep = document.getElementById('registerStep');

    // ==========
    // Toggle staff code visibility
    // ==========
    const codeInput = document.getElementById('staff_code');
    const toggleCodeBtn = document.getElementById('toggleCode');
    const codeIcon = document.getElementById('codeIcon');

    if (toggleCodeBtn && codeInput) {
        toggleCodeBtn.addEventListener('click', function () {
            const type = codeInput.type === 'password' ? 'text' : 'password';
            codeInput.type = type;
            codeIcon.classList.toggle('bi-eye');
            codeIcon.classList.toggle('bi-eye-slash');
        });
    }

    // ==========
    // Toggle password visibility
    // ==========
    const passwordInput = document.getElementById('password');
    const togglePasswordBtn = document.getElementById('togglePassword');
    const passwordIcon = document.getElementById('passwordIcon');

    const confirmPasswordInput = document.getElementById('confirm_password');
    const toggleConfirmPasswordBtn = document.getElementById('toggleConfirmPassword');
    const confirmPasswordIcon = document.getElementById('confirmPasswordIcon');

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

    // ==========
    // Frontend validation: code form
    // ==========
    const codeForm = document.getElementById('codeForm');
    const codeError = document.getElementById('codeError');

    if (codeForm) {
        codeForm.addEventListener('submit', function (e) {
            if (!codeInput) return;
            codeError.textContent = '';

            const value = codeInput.value.trim();
            if (value === '') {
                e.preventDefault();
                codeError.textContent = 'Code is required.';
            }
        });
    }

    // ==========
    // Frontend validation: staff registration form
    // ==========
    const staffRegisterForm = document.getElementById('staffRegisterForm');

    const fullNameError = document.getElementById('fullNameError');
    const staffIdError = document.getElementById('staffIdError');
    const emailError = document.getElementById('emailError');
    const phoneError = document.getElementById('phoneError');
    const passwordError = document.getElementById('passwordError');
    const confirmPasswordError = document.getElementById('confirmPasswordError');

    function setError(inputElement, errorElement, message) {
        errorElement.textContent = message;
        inputElement.classList.add('is-invalid');
    }

    function clearError(inputElement, errorElement) {
        errorElement.textContent = '';
        inputElement.classList.remove('is-invalid');
    }

    if (staffRegisterForm) {
        staffRegisterForm.addEventListener('submit', function (e) {
            let isValid = true;

            // Clear previous errors
            if (fullNameError) fullNameError.textContent = '';
            if (staffIdError) staffIdError.textContent = '';
            if (emailError) emailError.textContent = '';
            if (phoneError) phoneError.textContent = '';
            if (passwordError) passwordError.textContent = '';
            if (confirmPasswordError) confirmPasswordError.textContent = '';

            const fullNameEl = document.getElementById('full_name');
            const staffIdEl = document.getElementById('staff_id');
            const emailEl = document.getElementById('email');
            const phoneEl = document.getElementById('phone');

            const fullNameValue = fullNameEl ? fullNameEl.value.trim() : '';
            const staffIdValue = staffIdEl ? staffIdEl.value.trim() : '';
            const emailValue = emailEl ? emailEl.value.trim() : '';
            const passwordValue = passwordInput ? passwordInput.value : '';
            const confirmPasswordValue = confirmPasswordInput ? confirmPasswordInput.value : '';

            // Full Name
            if (fullNameEl) {
                if (fullNameValue === '') {
                    setError(fullNameEl, fullNameError, 'Full Name is required.');
                    isValid = false;
                } else {
                    clearError(fullNameEl, fullNameError);
                }
            }

            // Staff ID
            if (staffIdEl) {
                if (staffIdValue === '') {
                    setError(staffIdEl, staffIdError, 'Staff ID is required.');
                    isValid = false;
                } else {
                    clearError(staffIdEl, staffIdError);
                }
            }

            // Email
            if (emailEl) {
                if (emailValue === '') {
                    setError(emailEl, emailError, 'Email is required.');
                    isValid = false;
                } else {
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test(emailValue)) {
                        setError(emailEl, emailError, 'Please enter a valid email address.');
                        isValid = false;
                    } else {
                        clearError(emailEl, emailError);
                    }
                }
            }

            // Phone is optional
            if (phoneEl && phoneError) {
                phoneError.textContent = '';
                phoneEl.classList.remove('is-invalid');
            }

            // Password
            if (passwordInput) {
                if (passwordValue === '') {
                    setError(passwordInput, passwordError, 'Password is required.');
                    isValid = false;
                } else {
                    clearError(passwordInput, passwordError);
                }
            }

            // Confirm Password
            if (confirmPasswordInput) {
                if (confirmPasswordValue === '') {
                    setError(confirmPasswordInput, confirmPasswordError, 'Confirm Password is required.');
                    isValid = false;
                } else if (passwordValue !== confirmPasswordValue) {
                    setError(confirmPasswordInput, confirmPasswordError, 'Passwords do not match.');
                    isValid = false;
                } else {
                    clearError(confirmPasswordInput, confirmPasswordError);
                }
            }

            if (!isValid) e.preventDefault();
        });
    }

    // ==========
    // After PHP success: redirect to login
    // ==========
    <?php if (!empty($success_message)) : ?>
    setTimeout(function () {
        window.location.href = 'login.php';
    }, 2500);
    <?php endif; ?>
</script>
</body>
</html>

