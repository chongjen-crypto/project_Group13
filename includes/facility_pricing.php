<?php
/**
 * Scholar Hub — Facility pricing (per hour / per entry).
 */

/**
 * @return array<string, array{amount: float, mode: string, label: string, facility_name: string}>
 */
function facility_pricing_catalog(): array
{
    return [
        'badminton'   => ['amount' => 5.00,  'mode' => 'hourly', 'label' => 'Per Hour',  'facility_name' => 'Badminton Court'],
        'tennis'      => ['amount' => 8.00,  'mode' => 'hourly', 'label' => 'Per Hour',  'facility_name' => 'Tennis Court'],
        'swimming'    => ['amount' => 5.00,  'mode' => 'entry',  'label' => 'Per Entry', 'facility_name' => 'Swimming Pool'],
        'gym'         => ['amount' => 8.00,  'mode' => 'entry',  'label' => 'Per Entry', 'facility_name' => 'Gym Room'],
        'track'       => ['amount' => 6.00,  'mode' => 'entry',  'label' => 'Per Entry', 'facility_name' => 'Track Field'],
        'volleyball'  => ['amount' => 10.00, 'mode' => 'hourly', 'label' => 'Per Hour',  'facility_name' => 'Volleyball Court'],
        'basketball'  => ['amount' => 8.00,  'mode' => 'hourly', 'label' => 'Per Hour',  'facility_name' => 'Basketball Court'],
        'snooker'     => ['amount' => 6.00,  'mode' => 'hourly', 'label' => 'Per Hour',  'facility_name' => 'Snooker Room'],
        'futsal'      => ['amount' => 10.00, 'mode' => 'hourly', 'label' => 'Per Hour',  'facility_name' => 'Futsal Court'],
    ];
}

/**
 * @return array{amount: float, mode: string, label: string, facility_name: string}|null
 */
function facility_pricing_get(string $facility_type): ?array
{
    $type = strtolower(trim($facility_type));
    $catalog = facility_pricing_catalog();
    return $catalog[$type] ?? null;
}

/**
 * Resolve pricing from facility display name (dashboard / detail pages).
 * @return array{amount: float, mode: string, label: string, facility_name: string, facility_type: string}|null
 */
function facility_pricing_by_display_name(string $display_name): ?array
{
    require_once __DIR__ . '/booking_helpers.php';
    $type = booking_resolve_facility_type($display_name, '');
    if ($type === null) {
        return null;
    }
    $p = facility_pricing_get($type);
    if ($p === null) {
        return null;
    }
    $p['facility_type'] = $type;
    return $p;
}

function facility_pricing_format_rm(float $amount): string
{
    return 'RM ' . number_format($amount, 2);
}

/**
 * Calculate checkout total.
 * @return array{
 *   unit_price: float,
 *   mode: string,
 *   label: string,
 *   hours: int,
 *   total: float,
 *   breakdown: string
 * }
 */
function facility_pricing_calculate(string $facility_type, int $hours): array
{
    $pricing = facility_pricing_get($facility_type);
    if ($pricing === null) {
        return [
            'unit_price'  => 0.0,
            'mode'        => 'hourly',
            'label'       => 'Per Hour',
            'hours'       => max(1, $hours),
            'total'       => 0.0,
            'breakdown'   => 'Pricing not configured',
        ];
    }

    $hours = max(1, $hours);
    $unit = (float) $pricing['amount'];

    if ($pricing['mode'] === 'entry') {
        $total = $unit;
        $breakdown = facility_pricing_format_rm($unit) . ' (' . $pricing['label'] . ')';
    } else {
        $total = $unit * $hours;
        $breakdown = $hours . ' hour(s) × ' . facility_pricing_format_rm($unit) . ' = ' . facility_pricing_format_rm($total);
    }

    return [
        'unit_price' => $unit,
        'mode'       => $pricing['mode'],
        'label'      => $pricing['label'],
        'hours'      => $hours,
        'total'      => round($total, 2),
        'breakdown'  => $breakdown,
    ];
}

/**
 * Payment amount to store on a single booking row (for multi-row inserts).
 */
function facility_pricing_row_amount(string $facility_type, int $hours, int $row_index, int $total_rows): float
{
    $calc = facility_pricing_calculate($facility_type, $hours);
    if ($calc['mode'] === 'entry') {
        return $row_index === 0 ? $calc['total'] : 0.0;
    }
    return (float) $calc['unit_price'];
}
