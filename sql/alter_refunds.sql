-- Scholar Hub — Refund Management schema
-- Run in phpMyAdmin if tables are not auto-created on first page load.
-- Database: facility_booking_system

USE facility_booking_system;

-- Wallet balance lives on users (existing pattern)
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS wallet_balance DECIMAL(10,2) NOT NULL DEFAULT 0.00;

CREATE TABLE IF NOT EXISTS wallet_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    txn_type ENUM('topup','payment','refund') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    balance_after DECIMAL(10,2) NOT NULL,
    description VARCHAR(255) NOT NULL,
    booking_id INT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_wallet_user (user_id, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Extend txn_type if table already exists without 'refund'
ALTER TABLE wallet_transactions
    MODIFY txn_type ENUM('topup','payment','refund') NOT NULL;

CREATE TABLE IF NOT EXISTS refunds (
    refund_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    refund_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    refund_reason VARCHAR(255) NOT NULL DEFAULT '',
    refund_status ENUM('pending','completed','rejected') NOT NULL DEFAULT 'pending',
    admin_remarks TEXT DEFAULT NULL,
    approved_by INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_refunds_booking (booking_id),
    KEY idx_refunds_status (refund_status),
    KEY idx_refunds_user (user_id),
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS refund_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    refund_id INT NOT NULL,
    action_taken VARCHAR(50) NOT NULL,
    details TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_refund_audit_refund (refund_id),
    KEY idx_refund_audit_admin (admin_id),
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (refund_id) REFERENCES refunds(refund_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
