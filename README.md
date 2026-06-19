# Scholar Hub Facility Booking System

Scholar Hub is a PHP and MySQL web application for managing campus facility bookings. It supports student bookings, staff booking review, admin management, wallet top-ups, refund handling, notifications, and ToyyibPay online payment flow.

## Features

- Role-based login for students, staff, and admins.
- Student dashboard for facility browsing, booking requests, booking history, wallet balance, and notifications.
- Staff dashboard for reviewing booking requests and monitoring assigned workflows.
- Admin dashboard for users, facilities, booking reports, wallet activity, and refund processing.
- Facility pages for badminton, basketball, futsal, gym room, snooker room, swimming pool, tennis, track field, and volleyball.
- Booking payment support through wallet/in-app payments and ToyyibPay online payments.
- Email support through PHPMailer and optional SMTP environment settings.
- SQL schema and seed data included for local setup.

## Tech Stack

- PHP with MySQLi
- MySQL or MariaDB
- HTML, CSS, and JavaScript
- PHPMailer
- ToyyibPay payment gateway

## Requirements

- PHP 8.x
- MySQL or MariaDB
- Apache or another PHP-capable web server
- phpMyAdmin, MySQL CLI, or another database import tool
- XAMPP/WAMP/Laragon recommended for local development

## Local Setup

1. Clone or copy this project into your web server directory.

   Example for XAMPP:

   ```text
   C:\xampp\htdocs\project_Group13
   ```

2. Create a MySQL database named:

   ```sql
   facility_booking_system
   ```

3. Import the database dump:

   ```text
   database/facility_booking_system.sql
   ```

4. Configure the database connection.

   The recommended local setup is to copy:

   ```text
   includes/db_local.example.php
   ```

   to:

   ```text
   includes/db_local.php
   ```

   Then update the constants if your MySQL username, password, host, database name, or port are different.

5. Start Apache and MySQL.

6. Open the application in your browser:

   ```text
   http://localhost/project_Group13/login.php
   ```

## Environment Configuration

You can also configure the project with a `.env` file. Copy:

```text
.env.example
```

to:

```text
.env
```

Then fill in the database, ToyyibPay, and optional SMTP values.

Local-only files such as `.env`, `includes/db_local.php`, and `config/toyyibpay_local.php` should not be committed.

## ToyyibPay Setup

ToyyibPay credentials are optional for basic local browsing, but required for online payment testing.

1. Copy:

   ```text
   config/toyyibpay_local.example.php
   ```

   to:

   ```text
   config/toyyibpay_local.php
   ```

2. Add your ToyyibPay user secret key.

3. Keep sandbox mode enabled for testing:

   ```php
   define('TOYYIBPAY_USE_SANDBOX', true);
   ```

4. Set the public application base URL when testing callbacks or live payments:

   ```php
   define('TOYYIBPAY_APP_BASE_URL', 'http://localhost/project_Group13');
   ```

For hosted deployment, set the base URL to your public domain.

## Email Setup

Email features use PHPMailer. To enable SMTP, set these values in `.env`:

```text
SCHOLARHUB_SMTP_USER=your@gmail.com
SCHOLARHUB_SMTP_PASS=your-app-password
SCHOLARHUB_SMTP_HOST=smtp.gmail.com
SCHOLARHUB_MAIL_FROM=your@gmail.com
SCHOLARHUB_MAIL_FROM_NAME=Scholar Hub
```

For Gmail, use an app password instead of your normal account password.

## Sample Users

The main database dump includes sample data. Additional Gmail-based sample users are available in:

```text
sql/sample_users_gmail.sql
```

The sample users in that file use this password:

```text
ScholarHub123
```

## Project Structure

```text
admin/       Admin refund pages
assets/      Facility images and shared media
config/      Payment gateway configuration
data/        JSON-backed admin notification data
database/    Main SQL database dump
includes/    Shared PHP helpers, auth guards, layouts, and styles
logs/        ToyyibPay log output
PHPMailer/   Email library source
sql/         Extra SQL scripts and query references
```

## Useful Entry Points

```text
login.php                  Main login page
register.php               Student registration
student_dashboard.php      Student dashboard
staff_dashboard.php        Staff dashboard
admin_dashboard.php        Admin dashboard
booking.php                Booking workflow
booking_history.php        Student booking history
student_wallet.php         Student wallet
admin_booking_reports.php  Admin reports
admin_facilities.php       Admin facility management
```

## Notes

- Keep credentials out of Git by using `.env`, `includes/db_local.php`, and `config/toyyibpay_local.php`.
- Import `database/facility_booking_system.sql` before using the application.
- If ToyyibPay callback testing is needed from a local machine, use a public tunnel or deploy to a public host so ToyyibPay can reach `payment_callback.php`.
