<?php
/**
 * Scholar Hub — Booking checkout session (before payment.php).
 */

const PAYMENT_CHECKOUT_TTL = 1800; // 30 minutes

function payment_checkout_session_key(): string
{
    return 'booking_checkout';
}

/**
 * @param array<string, mixed> $data
 */
function payment_checkout_save(array $data): void
{
    $data['saved_at'] = time();
    $_SESSION[payment_checkout_session_key()] = $data;
}

/**
 * @return array<string, mixed>|null
 */
function payment_checkout_load(): ?array
{
    $key = payment_checkout_session_key();
    if (empty($_SESSION[$key]) || !is_array($_SESSION[$key])) {
        return null;
    }
    $data = $_SESSION[$key];
    $saved = (int) ($data['saved_at'] ?? 0);
    if ($saved > 0 && (time() - $saved) > PAYMENT_CHECKOUT_TTL) {
        payment_checkout_clear();
        return null;
    }
    return $data;
}

function payment_checkout_clear(): void
{
    unset($_SESSION[payment_checkout_session_key()]);
}

/** Allowed payment methods on payment.php */
function payment_method_options(): array
{
    return [
        'online' => ['label' => 'Online Payment', 'icon' => 'bi-globe2', 'short' => 'ToyyibPay', 'hint' => 'Pay securely via ToyyibPay'],
        'in_app' => ['label' => 'In-App Money', 'icon' => 'bi-wallet2', 'short' => 'Wallet', 'hint' => 'Scholar Hub wallet balance'],
    ];
}

function payment_method_is_valid(string $method): bool
{
    return isset(payment_method_options()[$method]);
}

function payment_method_display_label(string $method): string
{
    $options = payment_method_options();
    if (isset($options[$method])) {
        return (string) $options[$method]['label'];
    }
    if ($method === 'tng') {
        return 'Online Payment';
    }
    return ucfirst(str_replace('_', ' ', $method));
}
