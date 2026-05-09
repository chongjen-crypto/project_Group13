<?php

require 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $role = $_POST['role'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Invalid email format";

    } else {

        // Check user in database
        $sql = "SELECT * FROM users 
                WHERE role='$role' 
                AND email='$email' 
                AND password='$password'";

        $result = mysqli_query($conn, $sql);

        // Login success
        if (mysqli_num_rows($result) > 0) {

            // Redirect based on role
            if ($role == "student") {

                header("Location: student_dashboard.php");
                exit();

            } elseif ($role == "staff") {

                header("Location: staff_dashboard.php");
                exit();

            } elseif ($role == "admin") {

                header("Location: admin_dashboard.php");
                exit();
            }

        } else {

            $error = "Invalid email or password";
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
        url('assets/bg.png') no-repeat center center/cover;
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
            <p style="color:red;">* <?php echo $error; ?></p>
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