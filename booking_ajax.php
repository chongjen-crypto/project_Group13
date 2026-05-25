<?php
/**
 * Scholar Hub — Booking AJAX API (slots, courts, submit).
 * JSON responses; student session required.
 */
session_start();

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'student') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in as a student.']);
    exit;
}

require __DIR__ . '/db.php';
require_once __DIR__ . '/includes/booking_helpers.php';
require_once __DIR__ . '/includes/facility_pricing.php';
require_once __DIR__ . '/includes/payment_checkout.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

/**
 * @param array<string, mixed> $payload
 */
function booking_json_response(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

// -------------------------------------------------------------------------
// GET courts for court-based facility
// -------------------------------------------------------------------------
if ($action === 'get_courts') {
    $facility_type = strtolower(trim((string) ($_GET['facility_type'] ?? '')));
    if (!booking_is_court_based($facility_type)) {
        booking_json_response(['success' => true, 'courts' => [], 'is_court_based' => false]);
    }

    $courts = booking_fetch_courts($conn, $facility_type);
    booking_json_response([
        'success'        => true,
        'courts'         => $courts,
        'is_court_based' => true,
    ]);
}

// -------------------------------------------------------------------------
// GET time slots (requires date; court required for court-based)
// -------------------------------------------------------------------------
if ($action === 'get_slots') {
    $facility_type = strtolower(trim((string) ($_GET['facility_type'] ?? '')));
    $booking_date  = trim((string) ($_GET['booking_date'] ?? ''));
    $court_raw     = $_GET['court_id'] ?? null;

    if ($facility_type === '') {
        booking_json_response(['success' => false, 'message' => 'Invalid facility.'], 400);
    }
    if (!booking_validate_date($booking_date)) {
        booking_json_response(['success' => false, 'message' => 'Please choose a valid date (today or later).'], 400);
    }

    $court_id = null;
    if ($court_raw !== null && $court_raw !== '' && $court_raw !== 'null') {
        $court_id = (int) $court_raw;
    }

    if (booking_is_court_based($facility_type)) {
        if ($court_id === null || $court_id <= 0) {
            booking_json_response([
                'success' => false,
                'message' => 'Select a court or table before loading time slots.',
                'require_court' => true,
            ], 400);
        }
    } else {
        $court_id = null;
    }

    $slots = booking_slots_with_availability($conn, $facility_type, $booking_date, $court_id);
    booking_json_response([
        'success' => true,
        'slots'   => $slots,
    ]);
}

// -------------------------------------------------------------------------
// POST prepare checkout → payment.php (validate slots, store session)
// -------------------------------------------------------------------------
if ($action === 'prepare_checkout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $facility_type = strtolower(trim((string) ($_POST['facility_type'] ?? '')));
    $booking_date  = trim((string) ($_POST['booking_date'] ?? ''));
    $court_raw     = $_POST['court_id'] ?? null;
    $purpose       = trim((string) ($_POST['purpose'] ?? ''));

    $slots_json = $_POST['slots'] ?? '[]';
    $starts = is_string($slots_json) ? json_decode($slots_json, true) : $slots_json;
    if (!is_array($starts)) {
        $starts = [];
    }
    $starts = array_values(array_filter(array_map('strval', $starts)));

    $court_id = null;
    if ($court_raw !== null && $court_raw !== '' && $court_raw !== 'null') {
        $court_id = (int) $court_raw;
    }

    if ($facility_type === '' || $starts === []) {
        booking_json_response(['success' => false, 'message' => 'Incomplete booking details.'], 400);
    }

    if (!booking_validate_date($booking_date) || !booking_validate_continuous_slots($starts)) {
        booking_json_response(['success' => false, 'message' => 'Invalid date or time slots.'], 400);
    }

    if (booking_is_court_based($facility_type) && ($court_id === null || $court_id <= 0)) {
        booking_json_response(['success' => false, 'message' => 'Please select a court or table.'], 400);
    }
    if (!booking_is_court_based($facility_type)) {
        $court_id = null;
    }

    // Re-check availability before checkout
    foreach ($starts as $start) {
        $h = (int) substr($start, 0, 2);
        $end = sprintf('%02d:00:00', $h + 1);
        if (!booking_slot_is_available($conn, $facility_type, $booking_date, $court_id, $start, $end)) {
            booking_json_response(['success' => false, 'message' => 'A selected slot is no longer available.'], 409);
        }
    }

    $pricing_meta = facility_pricing_get($facility_type, $conn);
    if ($pricing_meta === null) {
        booking_json_response(['success' => false, 'message' => 'Pricing not configured for this facility.'], 400);
    }

    $facility_row = booking_load_facility($conn, '', $facility_type);
    $facility_name = $facility_row['facility_name'] ?? $pricing_meta['facility_name'];

    $court_name = '';
    if ($court_id !== null && $court_id > 0) {
        $court_name = booking_court_display_name($conn, $facility_type, $court_id);
    }

    sort($starts);
    $slot_map = [];
    foreach (booking_generate_hourly_slots() as $slot) {
        $slot_map[$slot['start']] = $slot['label'];
    }
    $slot_labels = [];
    foreach ($starts as $st) {
        if (isset($slot_map[$st])) {
            $slot_labels[] = $slot_map[$st];
        }
    }

    $hours = count($starts);
    $calc = facility_pricing_calculate($facility_type, $hours);

    payment_checkout_save([
        'user_id'        => (int) $_SESSION['user_id'],
        'facility_type'  => $facility_type,
        'facility_name'  => $facility_name,
        'booking_date'   => $booking_date,
        'court_id'       => $court_id,
        'court_name'     => $court_name,
        'court_label'    => $facility_type === 'snooker' ? 'Table' : 'Court',
        'is_court_based' => booking_is_court_based($facility_type),
        'slots'          => $starts,
        'slot_labels'    => $slot_labels,
        'purpose'        => $purpose,
        'unit_price'     => $calc['unit_price'],
        'price_label'    => $calc['label'],
        'pricing_mode'   => $calc['mode'],
        'total_hours'    => $hours,
        'total_amount'   => $calc['total'],
        'breakdown'      => $calc['breakdown'],
    ]);

    booking_json_response([
        'success'  => true,
        'message'  => 'Proceed to payment.',
        'redirect' => 'payment.php',
    ]);
}

// -------------------------------------------------------------------------
// POST create booking(s) — legacy direct booking (kept for compatibility)
// -------------------------------------------------------------------------
if ($action === 'create_booking' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $facility_type = strtolower(trim((string) ($_POST['facility_type'] ?? '')));
    $booking_date  = trim((string) ($_POST['booking_date'] ?? ''));
    $court_raw     = $_POST['court_id'] ?? null;
    $purpose       = trim((string) ($_POST['purpose'] ?? ''));

    $slots_json = $_POST['slots'] ?? '[]';
    if (is_string($slots_json)) {
        $starts = json_decode($slots_json, true);
    } else {
        $starts = $slots_json;
    }
    if (!is_array($starts)) {
        $starts = [];
    }
    $starts = array_values(array_filter(array_map('strval', $starts)));

    $court_id = null;
    if ($court_raw !== null && $court_raw !== '' && $court_raw !== 'null') {
        $court_id = (int) $court_raw;
    }

    if ($facility_type === '') {
        booking_json_response(['success' => false, 'message' => 'Invalid facility.'], 400);
    }
    if ($starts === []) {
        booking_json_response(['success' => false, 'message' => 'Select at least one time slot.'], 400);
    }

    $user_id = (int) $_SESSION['user_id'];
    $result = booking_create_reservations($conn, $user_id, $facility_type, $booking_date, $court_id, $starts, $purpose);

    if ($result['success']) {
        booking_json_response([
            'success'     => true,
            'message'     => $result['message'],
            'booking_ids' => $result['booking_ids'],
            'redirect'    => 'student_dashboard.php?booked=1',
        ]);
    }

    booking_json_response(['success' => false, 'message' => $result['message']], 409);
}

booking_json_response(['success' => false, 'message' => 'Invalid request.'], 400);
