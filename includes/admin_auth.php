<?php
/**
 * Admin-only session guard (include at top of admin pages).
 */
session_start();

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$admin_name = (isset($_SESSION['full_name']) && trim((string) $_SESSION['full_name']) !== '')
    ? htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8')
    : 'Administrator';

$admin_email = isset($_SESSION['email'])
    ? htmlspecialchars((string) $_SESSION['email'], ENT_QUOTES, 'UTF-8')
    : '';
