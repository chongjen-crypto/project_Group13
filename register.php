<?php
// Start session
session_start();

// Include database connection (assumes $conn = new mysqli(...) in db.php)
require 'db.php';

// Initialize variables
$full_name = $student_id = $email = $phone = "";
$errors = [];
$success_message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and trim form values
    $full_name  = trim($_POST['full_name'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic backend validation
    if ($full_name === '') {
        $errors['full_name'] = "Full Name is required.";
    }

    if ($student_id === '') {
        $errors['student_id'] = "Student ID is required.";
    }

    if ($email === '') {
        $errors['email'] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address.";
    }

    if ($password === '') {
        $errors['password'] = "Password is required.";
    }

    if ($confirm_password === '') {
        $errors['confirm_password'] = "Confirm Password is required.";
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    // Only proceed to check database if no basic validation errors
    if (empty($errors)) {
        // Check if email already exists
        $sql = "SELECT id FROM users WHERE email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors['email'] = "This email is already registered.";
            }
            $stmt->close();
        } else {
            $errors['general'] = "Database error. Please try again later.";
        }

        // Check if student ID already exists
        if (empty($errors['general']) && empty($errors['email'])) {
            $sql = "SELECT id FROM users WHERE user_id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $student_id);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $errors['student_id'] = "This Student ID is already registered.";
                }
                $stmt->close();
            } else {
                $errors['general'] = "Database error. Please try again later.";
            }
        }

        // If still no errors, insert new user
        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'student'; // Force role to 'student' for self-registration

            $sql = "INSERT INTO users (role, full_name, user_id, email, phone, password) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssssss", $role, $full_name, $student_id, $email, $phone, $hashed_password);
                if ($stmt->execute()) {
                    // Clear form values
                    $full_name = $student_id = $email = $phone = "";
                    $success_message = "Registration successful! Redirecting to login page...";
                } else {
                    $errors['general'] = "Registration failed. Please try again.";
                }
                $stmt->close();
            } else {
                $errors['general'] = "Database error. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholar Hub - Register</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS (inline for single-file requirement) -->
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: url('assets/bg.png') center center / cover no-repeat fixed;
            position: relative;
            color: #212529;
        }

        /* Dark overlay over background image */
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .register-card {
            background-color: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.25);
            max-width: 480px;
            width: 100%;
            padding: 2.25rem 2rem;
        }

        @media (min-width: 768px) {
            .register-card {
                padding: 2.75rem 2.5rem;
            }
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

        .form-label {
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-control,
        .form-control:focus {
            border-radius: 0.75rem;
        }

        .input-group-text {
            border-radius: 0.75rem;
            background-color: #f8f9fa;
        }

        .btn-register {
            border-radius: 999px;
            padding: 0.7rem 1rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .text-link {
            text-decoration: none;
        }

        .text-link:hover {
            text-decoration: underline;
        }

        .error-text {
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
<div class="register-page-wrapper">
    <div class="register-card">
        <!-- Title Section -->
        <div class="text-center mb-3">
            <div class="d-inline-flex align-items-center justify-content-center mb-2">
                <i class="bi bi-mortarboard-fill fs-1 me-2 text-dark"></i>
                <span class="app-title fs-3">Scholar Hub</span>
            </div>
            <div class="app-subtitle">Sport Facility Booking System</div>
        </div>
        <div class="divider-line"></div>

        <!-- General error or success messages -->
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger py-2" role="alert">
                <?php echo htmlspecialchars($errors['general']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success py-2" role="alert" id="successAlert">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Registration Form -->
        <form id="registerForm" method="POST" novalidate>
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

            <!-- Student ID -->
            <div class="mb-3">
                <label for="student_id" class="form-label">Student ID</label>
                <input
                    type="text"
                    class="form-control <?php echo isset($errors['student_id']) ? 'is-invalid' : ''; ?>"
                    id="student_id"
                    name="student_id"
                    value="<?php echo htmlspecialchars($student_id); ?>"
                    placeholder="Enter your student ID"
                >
                <div class="text-danger error-text" id="studentIdError">
                    <?php echo isset($errors['student_id']) ? htmlspecialchars($errors['student_id']) : ''; ?>
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

            <!-- Phone Number -->
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
                    <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePassword">
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
                    <button class="btn btn-outline-secondary border-start-0" type="button" id="toggleConfirmPassword">
                        <i class="bi bi-eye-slash" id="confirmPasswordIcon"></i>
                    </button>
                </div>
                <div class="text-danger error-text" id="confirmPasswordError">
                    <?php echo isset($errors['confirm_password']) ? htmlspecialchars($errors['confirm_password']) : ''; ?>
                </div>
            </div>

            <!-- Register Button -->
            <div class="d-grid mb-2">
                <button type="submit" class="btn btn-dark btn-register">
                    <i class="bi bi-person-plus me-1"></i> Register
                </button>
            </div>

            <!-- Login Link -->
            <div class="text-center mt-2">
                <span class="text-muted">Already have an account? </span>
                <a href="login.php" class="text-primary text-link">Login</a>
            </div>
        </form>
    </div>
</div>

<!-- Bootstrap 5 JS (for proper Bootstrap behavior, optional for this page but included) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Inline JavaScript for validation and interactions -->
<script>
    // Password visibility toggles
    const passwordInput = document.getElementById('password');
    const togglePasswordBtn = document.getElementById('togglePassword');
    const passwordIcon = document.getElementById('passwordIcon');

    const confirmPasswordInput = document.getElementById('confirm_password');
    const toggleConfirmPasswordBtn = document.getElementById('toggleConfirmPassword');
    const confirmPasswordIcon = document.getElementById('confirmPasswordIcon');

    togglePasswordBtn.addEventListener('click', function () {
        const type = passwordInput.type === 'password' ? 'text' : 'password';
        passwordInput.type = type;
        passwordIcon.classList.toggle('bi-eye');
        passwordIcon.classList.toggle('bi-eye-slash');
    });

    toggleConfirmPasswordBtn.addEventListener('click', function () {
        const type = confirmPasswordInput.type === 'password' ? 'text' : 'password';
        confirmPasswordInput.type = type;
        confirmPasswordIcon.classList.toggle('bi-eye');
        confirmPasswordIcon.classList.toggle('bi-eye-slash');
    });

    // Frontend validation
    const registerForm = document.getElementById('registerForm');
    const fullNameError = document.getElementById('fullNameError');
    const studentIdError = document.getElementById('studentIdError');
    const emailError = document.getElementById('emailError');
    const phoneError = document.getElementById('phoneError');
    const passwordError = document.getElementById('passwordError');
    const confirmPasswordError = document.getElementById('confirmPasswordError');

    registerForm.addEventListener('submit', function (e) {
        // Clear previous error messages
        fullNameError.textContent = '';
        studentIdError.textContent = '';
        emailError.textContent = '';
        phoneError.textContent = '';
        passwordError.textContent = '';
        confirmPasswordError.textContent = '';

        let isValid = true;

        // Simple helper to set error
        function setError(inputElement, errorElement, message) {
            errorElement.textContent = message;
            inputElement.classList.add('is-invalid');
            isValid = false;
        }

        function clearInvalid(inputElement) {
            inputElement.classList.remove('is-invalid');
        }

        // Get values
        const fullNameValue = document.getElementById('full_name').value.trim();
        const studentIdValue = document.getElementById('student_id').value.trim();
        const emailValue = document.getElementById('email').value.trim();
        const phoneValue = document.getElementById('phone').value.trim();
        const passwordValue = passwordInput.value;
        const confirmPasswordValue = confirmPasswordInput.value;

        // Full Name validation
        if (fullNameValue === '') {
            setError(document.getElementById('full_name'), fullNameError, 'Full Name is required.');
        } else {
            clearInvalid(document.getElementById('full_name'));
        }

        // Student ID validation
        if (studentIdValue === '') {
            setError(document.getElementById('student_id'), studentIdError, 'Student ID is required.');
        } else {
            clearInvalid(document.getElementById('student_id'));
        }

        // Email validation
        if (emailValue === '') {
            setError(document.getElementById('email'), emailError, 'Email is required.');
        } else {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(emailValue)) {
                setError(document.getElementById('email'), emailError, 'Please enter a valid email address.');
            } else {
                clearInvalid(document.getElementById('email'));
            }
        }

        // Phone validation (optional: here we just clear invalid class)
        clearInvalid(document.getElementById('phone'));

        // Password validation
        if (passwordValue === '') {
            setError(passwordInput, passwordError, 'Password is required.');
        } else {
            clearInvalid(passwordInput);
        }

        // Confirm Password validation
        if (confirmPasswordValue === '') {
            setError(confirmPasswordInput, confirmPasswordError, 'Confirm Password is required.');
        } else if (passwordValue !== confirmPasswordValue) {
            setError(confirmPasswordInput, confirmPasswordError, 'Passwords do not match.');
        } else {
            clearInvalid(confirmPasswordInput);
        }

        // Prevent submission if invalid
        if (!isValid) {
            e.preventDefault();
        }
    });

    // If PHP reported success, redirect to login after short delay
    <?php if (!empty($success_message)) : ?>
    setTimeout(function () {
        window.location.href = 'login.php';
    }, 2500);
    <?php endif; ?>
</script>
</body>
</html>