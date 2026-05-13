<?php

session_start();

require 'db.php';

// If already logged in
if (isset($_SESSION['role'])) {

    if ($_SESSION['role'] == "student") {
        header("Location: student_dashboard.php");
        exit();
    }

    if ($_SESSION['role'] == "staff") {
        header("Location: staff_dashboard.php");
        exit();
    }

    if ($_SESSION['role'] == "admin") {
        header("Location: admin_dashboard.php");
        exit();
    }
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get and trim form values
    $role = trim($_POST['role'] ?? 'student');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Allow only valid roles from the UI (student/staff/admin)
    $allowed_roles = ['student', 'staff', 'admin'];
    if (!in_array($role, $allowed_roles, true)) {
        $role = 'student';
    }

    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Invalid email format";

    } else {

        // Check user in database (securely) and verify password hash
        $sql = "SELECT id, role, email, full_name, password FROM users WHERE role = ? AND email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            $error = "Database error. Please try again.";
        } else {
            mysqli_stmt_bind_param($stmt, "ss", $role, $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);

            if (!$user) {
                $error = "Invalid email or password";
            } else {
                $stored_password = $user['password'];

                // If password is hashed (recommended), verify using password_verify().
                // If some old accounts were saved as plain text, allow one-time login and upgrade to hash.
                $info = password_get_info($stored_password);
                $password_ok = false;
                $needs_upgrade_hash = false;

                if ($info['algo'] !== 0) {
                    $password_ok = password_verify($password, $stored_password);
                } else {
                    // Plain-text legacy password (not recommended). Upgrade it after successful login.
                    $password_ok = hash_equals($stored_password, $password);
                    $needs_upgrade_hash = $password_ok;
                }

                if (!$password_ok) {
                    $error = "Invalid email or password";
                } else {
                    // Upgrade legacy plain-text password to hashed password
                    if ($needs_upgrade_hash) {
                        $new_hash = password_hash($password, PASSWORD_DEFAULT);
                        $update_sql = "UPDATE users SET password = ? WHERE id = ?";
                        $update_stmt = mysqli_prepare($conn, $update_sql);
                        if ($update_stmt) {
                            mysqli_stmt_bind_param($update_stmt, "si", $new_hash, $user['id']);
                            mysqli_stmt_execute($update_stmt);
                            mysqli_stmt_close($update_stmt);
                        }
                    }

                    // Store session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['full_name'] = $user['full_name'];

                    // Redirect based on role
                    switch ($role) {

                        case "student":
                            header("Location: student_dashboard.php");
                            exit();

                        case "staff":
                            header("Location: staff_dashboard.php");
                            exit();

                        case "admin":
                            header("Location: admin_dashboard.php");
                            exit();
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
<title>Scholar Hub</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<style>

body {
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;

    background:
        linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)),
        url('assets/trackfield.webp') no-repeat center center/cover;
}

/* Login Box */
.login-box {
    background: white;
    padding: 50px;
    border-radius: 20px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    width: 500px;
    margin: auto;
}

/* Title */
.title {
    font-size: 40px;
    font-weight: bold;
    color: black;
    text-align: center;
}

.subtitle {
    margin-bottom: 25px;
    color: #666;
    text-align: center;
}

/* Role Selection */
.role-container {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.role-box {
    flex: 1;
    padding: 10px;
    text-align: center;
    border-radius: 10px;
    border: 1px solid #ccc;
    cursor: pointer;
    transition: 0.3s;
}

.role-box.active {
    background: black;
    color: white;
    border-color: black;
}

/* Login Button */
.btn-black {
    background-color: black;
    color: white;
}

.btn-black:hover {
    background-color: #333;
    color: white;
}

/* Password */
.password-wrapper {
    position: relative;
}

.toggle-password {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 18px;
}

/* Links */
.custom-link {
    color: #0d6efd;
    text-decoration: none;
    font-size: 14px;
}

.custom-link:hover {
    text-decoration: underline;
}

</style>
</head>

<body>

<div class="text-center">

    <div class="login-box">

        <!-- Title -->
        <div class="title">Scholar Hub</div>
        <div class="subtitle">Sport Facility Booking System</div>

        <hr>

        <!-- Error Message -->
        <?php if ($error): ?>
            <p style="color:red;">* <?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST">

            <!-- Role Selection -->
            <div class="role-container">
                <div class="role-box active" onclick="selectRole(this, 'student')">Student</div>
                <div class="role-box" onclick="selectRole(this, 'staff')">Staff</div>
                <div class="role-box" onclick="selectRole(this, 'admin')">Admin</div>
            </div>

            <!-- Hidden Role -->
            <input type="hidden" id="role" name="role" value="student">

            <!-- Email -->
            <div class="mb-3">
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="Email" 
                    required
                >

                <small id="emailError" class="text-danger" style="display:none;">
                    * Please enter a valid email
                </small>
            </div>

            <!-- Password -->
            <div class="mb-2 password-wrapper">
                <input 
                    type="password" 
                    id="password"
                    name="password" 
                    class="form-control" 
                    placeholder="Password" 
                    required
                >

                <span class="toggle-password" onclick="togglePassword()">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </span>
            </div>

            <!-- Forgot Password -->
            <div class="text-end mb-3">
                <a href="forgot_password.php" class="custom-link">
                    Forgot Password?
                </a>
            </div>

            <!-- Login Button -->
            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-black">
                    Login
                </button>
            </div>

            <!-- Register -->
            <div class="text-center">
                <span style="font-size:14px;">Don't have an account? </span>

                <a href="register.php" class="custom-link">
                    Register one
                </a>
            </div>

        </form>

    </div>

</div>

<script>

// Role Selection
function selectRole(element, role) {
    document.querySelectorAll('.role-box').forEach(box => {
        box.classList.remove('active');
    });

    element.classList.add('active');

    document.getElementById('role').value = role;
}

// Email Validation
document.querySelector("form").addEventListener("submit", function(e) {

    const email = document.getElementById("email").value;
    const error = document.getElementById("emailError");

    const emailPattern = /^[^ ]+@[^ ]+\.[a-z]{2,3}$/;

    if (!email.match(emailPattern)) {

        e.preventDefault();

        error.style.display = "block";

    } else {

        error.style.display = "none";
    }
});

// Password Toggle
function togglePassword() {

    const password = document.getElementById("password");
    const icon = document.getElementById("eyeIcon");

    if (password.type === "password") {

        password.type = "text";

        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");

    } else {

        password.type = "password";

        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
    }
}

</script>

</body>
</html>