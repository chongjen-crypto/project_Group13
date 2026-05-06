<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Scholar Hub</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<style>
body {
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: #f5f5f5;
}

.title {
    font-size: 50px;
    font-weight: bold;
}

.subtitle {
    margin-bottom: 30px;
    color: #555;
}

.login-box {
    background: white;
    padding: 50px;
    border-radius: 20px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    width: 500px; /* bigger box */
    margin: auto;
}

/* Role selection */
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

.btn-black {
    background-color: black;
    color: white;
}

.btn-black:hover {
    background-color: #333;
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
</style>
</head>

<body>

<div class="text-center">

    <div class="title">Scholar Hub</div>
    <div class="subtitle">Sport Facility Booking System</div>

    <div class="login-box">

        <form>

            <!-- Role Selection -->
            <div class="role-container">
                <div class="role-box active" onclick="selectRole(this, 'student')">Student</div>
                <div class="role-box" onclick="selectRole(this, 'staff')">Staff</div>
                <div class="role-box" onclick="selectRole(this, 'admin')">Admin</div>
            </div>

            <!-- Hidden input (important for backend later) -->
            <input type="hidden" id="role" value="student">

            <!-- Email -->
            <div class="mb-3">
                <input type="email" class="form-control" placeholder="Email" required>
            </div>

            <!-- Password -->
            <div class="mb-3 password-wrapper">
                <input type="password" id="password" class="form-control" placeholder="Password" required>
                <span class="toggle-password" onclick="togglePassword()">
                <i class="bi bi-eye" id="eyeIcon"></i>
                </span>
            </div>

            <!-- Button -->
            <div class="d-grid">
                <button class="btn btn-black">Login</button>
            </div>

        </form>

    </div>

</div>

<script>
// Role selection
function selectRole(element, role) {
    document.querySelectorAll('.role-box').forEach(box => box.classList.remove('active'));
    element.classList.add('active');
    document.getElementById('role').value = role;
}

// Password toggle (eye icon)
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