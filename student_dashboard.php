<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body {
    background-color: #f5f5f5;
}

/* Navbar */
.navbar {
    background-color: black;
}

.navbar-brand {
    color: white !important;
    font-weight: bold;
}

/* Dashboard Card */
.dashboard-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

</style>
</head>

<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">

        <span class="navbar-brand">
            Scholar Hub - Student Dashboard
        </span>

        <a href="login.php" class="btn btn-light">
            Logout
        </a>

    </div>
</nav>

<!-- Main Content -->
<div class="container mt-5">

    <div class="dashboard-card">

        <h2>Welcome Student 👋</h2>

        <p class="mt-3">
            Welcome to Scholar Hub Sport Facility Booking System.
        </p>

        <hr>

        <h5>Quick Actions</h5>

        <div class="mt-4 d-flex gap-3">

            <button class="btn btn-dark">
                Book Facility
            </button>

            <button class="btn btn-secondary">
                View Bookings
            </button>

            <button class="btn btn-outline-dark">
                Wallet
            </button>

        </div>

    </div>

</div>

</body>
</html>