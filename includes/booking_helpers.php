<?php
/**
 * Scholar Hub — Booking helpers (slots, courts, availability, inserts).
 */

/** Map display names (from facility pages / URL) to DB facility_type */
function booking_facility_name_aliases(): array
{
    return [
        'badminton court'     => 'badminton',
        'basketball court'    => 'basketball',
        'futsal court'        => 'futsal',
        'tennis court'        => 'tennis',
        'volleyball court'    => 'volleyball',
        'snooker room'        => 'snooker',
        'gym room'            => 'gym',
        'swimming pool'       => 'swimming',
        'track field'         => 'track',
    ];
}

/** Court-based facilities require court/table before time slots */
function booking_is_court_based(string $facility_type): bool
{
    return in_array($facility_type, ['badminton', 'basketball', 'futsal', 'tennis', 'volleyball', 'snooker'], true);
}

/**
 * Court table metadata per facility_type.
 * @return array{table: string, id_col: string, name_col: string}|null
 */
function booking_court_table_config(string $facility_type): ?array
{
    static $map = [
        'badminton'   => ['table' => 'badminton_court',   'id_col' => 'court_id',  'name_col' => 'court_name'],
        'basketball'  => ['table' => 'basketball_court',  'id_col' => 'court_id',  'name_col' => 'court_name'],
        'futsal'      => ['table' => 'futsal_court',      'id_col' => 'court_id',  'name_col' => 'court_name'],
        'tennis'      => ['table' => 'tennis_court',      'id_col' => 'court_id',  'name_col' => 'court_name'],
        'volleyball'  => ['table' => 'volleyball_court',  'id_col' => 'court_id',  'name_col' => 'court_name'],
        'snooker'     => ['table' => 'snooker_room',      'id_col' => 'table_id',  'name_col' => 'table_name'],
    ];
    return $map[$facility_type] ?? null;
}

/** Operating hours: 8:00 AM – 10:00 PM (1-hour slots) */
function booking_operating_start_hour(): int
{
    return 8;
}

function booking_operating_end_hour(): int
{
    return 22; // last slot ends at 22:00
}

/** @return list<array{start: string, end: string, label: string}> */
function booking_generate_hourly_slots(): array
{
    $slots = [];
    for ($h = booking_operating_start_hour(); $h < booking_operating_end_hour(); $h++) {
        $start = sprintf('%02d:00:00', $h);
        $end   = sprintf('%02d:00:00', $h + 1);
        $slots[] = [
            'start' => $start,
            'end'   => $end,
            'label' => booking_format_time_range($start, $end),
        ];
    }
    return $slots;
}

function booking_format_time_range(string $start, string $end): string
{
    return booking_format_time_12h($start) . ' – ' . booking_format_time_12h($end);
}

function booking_format_time_12h(string $time): string
{
    $ts = strtotime('1970-01-01 ' . $time);
    return $ts ? date('g:i A', $ts) : $time;
}

/**
 * Resolve facility_type from URL name or type slug.
 */
function booking_resolve_facility_type(string $facility_param, string $type_param = ''): ?string
{
    $type_param = strtolower(trim($type_param));
    if ($type_param !== '' && preg_match('/^[a-z]+$/', $type_param)) {
        return $type_param;
    }
    $key = strtolower(trim($facility_param));
    $aliases = booking_facility_name_aliases();
    return $aliases[$key] ?? null;
}

/**
 * Load facility row from `facilities` by name or type.
 * @return array<string, mixed>|null
 */
function booking_load_facility(mysqli $conn, string $facility_param, string $type_param = ''): ?array
{
    require_once __DIR__ . '/facility_admin_helpers.php';
    facilities_ensure_schema($conn);

    $type = booking_resolve_facility_type($facility_param, $type_param);
    if ($type !== null) {
        $sql = "SELECT facility_id, facility_name, facility_type, description, image, location,
                       opening_time, closing_time, status
                FROM facilities
                WHERE facility_type = ? AND status = 'active'
                ORDER BY facility_id ASC
                LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return null;
        }
        mysqli_stmt_bind_param($stmt, 's', $type);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    if (trim($facility_param) === '') {
        return null;
    }

    $sql = "SELECT facility_id, facility_name, facility_type, description, image, location,
                   opening_time, closing_time, status
            FROM facilities
            WHERE facility_name = ? AND status = 'active'
            LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, 's', $facility_param);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

/**
 * Fetch courts/tables for a court-based facility.
 * @return list<array{court_id: int, court_name: string}>
 */
function booking_fetch_courts(mysqli $conn, string $facility_type): array
{
    $cfg = booking_court_table_config($facility_type);
    if ($cfg === null) {
        return [];
    }

    $table = $cfg['table'];
    $idCol = $cfg['id_col'];
    $nameCol = $cfg['name_col'];

    // Whitelist table/column names only (from config above)
    $sql = "SELECT `{$idCol}` AS court_id, `{$nameCol}` AS court_name
            FROM `{$table}`
            WHERE status = 'available'
            ORDER BY `{$nameCol}` ASC";

    $res = mysqli_query($conn, $sql);
    if (!$res) {
        return [];
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $rows[] = [
            'court_id'   => (int) $row['court_id'],
            'court_name' => (string) $row['court_name'],
        ];
    }
    mysqli_free_result($res);
    return $rows;
}

/**
 * Booked slot start times for a date + facility (+ court).
 * @return list<string>  e.g. ['14:00:00', '15:00:00']
 */
function booking_fetch_booked_start_times(mysqli $conn, string $facility_type, string $booking_date, ?int $court_id): array
{
    if ($court_id === null) {
        $sql = "SELECT start_time FROM bookings
                WHERE facility_type = ? AND booking_date = ? AND court_id IS NULL
                AND booking_status IN ('pending','approved','completed')";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return [];
        }
        mysqli_stmt_bind_param($stmt, 'ss', $facility_type, $booking_date);
    } else {
        $sql = "SELECT start_time FROM bookings
                WHERE facility_type = ? AND booking_date = ? AND court_id = ?
                AND booking_status IN ('pending','approved','completed')";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return [];
        }
        mysqli_stmt_bind_param($stmt, 'ssi', $facility_type, $booking_date, $court_id);
    }

    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $starts = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $starts[] = substr((string) $row['start_time'], 0, 8);
        }
        mysqli_free_result($res);
    }
    mysqli_stmt_close($stmt);
    return $starts;
}

/**
 * Build slot list with availability flags.
 * @return list<array{start: string, end: string, label: string, available: bool}>
 */
function booking_slots_with_availability(mysqli $conn, string $facility_type, string $booking_date, ?int $court_id): array
{
    $booked = booking_fetch_booked_start_times($conn, $facility_type, $booking_date, $court_id);
    $bookedSet = array_flip($booked);
    $out = [];
    foreach (booking_generate_hourly_slots() as $slot) {
        $out[] = [
            'start'     => $slot['start'],
            'end'       => $slot['end'],
            'label'     => $slot['label'],
            'available' => !isset($bookedSet[$slot['start']]),
        ];
    }
    return $out;
}

/** Validate YYYY-MM-DD and not in the past */
function booking_validate_date(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        return false;
    }
    $today = new DateTime('today');
    return $d >= $today;
}

/**
 * Ensure selected starts are valid hourly slots and form one continuous block.
 * @param list<string> $starts
 */
function booking_validate_continuous_slots(array $starts): bool
{
    if ($starts === []) {
        return false;
    }
    $hours = [];
    foreach ($starts as $s) {
        if (!preg_match('/^(\d{2}):00:00$/', $s, $m)) {
            return false;
        }
        $h = (int) $m[1];
        if ($h < booking_operating_start_hour() || $h >= booking_operating_end_hour()) {
            return false;
        }
        $hours[] = $h;
    }
    $hours = array_values(array_unique($hours));
    sort($hours);
    if (count($hours) !== count($starts)) {
        return false; // duplicate
    }
    for ($i = 1; $i < count($hours); $i++) {
        if ($hours[$i] !== $hours[$i - 1] + 1) {
            return false;
        }
    }
    return true;
}

/**
 * Check if a single slot is still free (overlap / double-booking guard).
 */
function booking_slot_is_available(mysqli $conn, string $facility_type, string $booking_date, ?int $court_id, string $start_time, string $end_time): bool
{
    if ($court_id === null) {
        $sql = "SELECT booking_id FROM bookings
                WHERE facility_type = ? AND booking_date = ? AND court_id IS NULL
                AND booking_status IN ('pending','approved','completed')
                AND start_time < ? AND end_time > ?
                LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'ssss', $facility_type, $booking_date, $end_time, $start_time);
    } else {
        $sql = "SELECT booking_id FROM bookings
                WHERE facility_type = ? AND booking_date = ? AND court_id = ?
                AND booking_status IN ('pending','approved','completed')
                AND start_time < ? AND end_time > ?
                LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'ssiss', $facility_type, $booking_date, $court_id, $end_time, $start_time);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $taken = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return !$taken;
}

/**
 * Insert one booking row per selected hour.
 * @param list<string> $start_times  e.g. ['14:00:00','15:00:00']
 * @return array{success: bool, message: string, booking_ids: list<int>}
 */
function booking_create_reservations(
    mysqli $conn,
    int $user_id,
    string $facility_type,
    string $booking_date,
    ?int $court_id,
    array $start_times,
    string $purpose = ''
): array {
    if (!booking_validate_date($booking_date)) {
        return ['success' => false, 'message' => 'Invalid booking date.', 'booking_ids' => []];
    }

    $starts = array_values(array_unique($start_times));
    if (!booking_validate_continuous_slots($starts)) {
        return ['success' => false, 'message' => 'Please select consecutive 1-hour slots within operating hours.', 'booking_ids' => []];
    }

    if (booking_is_court_based($facility_type) && ($court_id === null || $court_id <= 0)) {
        return ['success' => false, 'message' => 'Please select a court or table first.', 'booking_ids' => []];
    }

    if (!booking_is_court_based($facility_type)) {
        $court_id = null;
    }

    mysqli_begin_transaction($conn);

    try {
        if ($court_id === null) {
            $insertSql = 'INSERT INTO bookings (user_id, facility_type, court_id, booking_date, start_time, end_time, purpose, booking_status)
                          VALUES (?, ?, NULL, ?, ?, ?, ?, ?)';
            $stmt = mysqli_prepare($conn, $insertSql);
            $bindTypes = 'issssss';
        } else {
            $insertSql = 'INSERT INTO bookings (user_id, facility_type, court_id, booking_date, start_time, end_time, purpose, booking_status)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = mysqli_prepare($conn, $insertSql);
            $bindTypes = 'isisssss';
        }

        if (!$stmt) {
            throw new RuntimeException('Database error preparing booking.');
        }

        $status = 'pending';
        $ids = [];

        foreach ($starts as $start) {
            $h = (int) substr($start, 0, 2);
            $end = sprintf('%02d:00:00', $h + 1);

            if (!booking_slot_is_available($conn, $facility_type, $booking_date, $court_id, $start, $end)) {
                mysqli_rollback($conn);
                mysqli_stmt_close($stmt);
                return [
                    'success' => false,
                    'message' => 'One or more selected slots were just booked. Please refresh and try again.',
                    'booking_ids' => [],
                ];
            }

            if ($court_id === null) {
                mysqli_stmt_bind_param(
                    $stmt,
                    $bindTypes,
                    $user_id,
                    $facility_type,
                    $booking_date,
                    $start,
                    $end,
                    $purpose,
                    $status
                );
            } else {
                mysqli_stmt_bind_param(
                    $stmt,
                    $bindTypes,
                    $user_id,
                    $facility_type,
                    $court_id,
                    $booking_date,
                    $start,
                    $end,
                    $purpose,
                    $status
                );
            }

            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('Failed to save booking.');
            }
            $ids[] = (int) mysqli_insert_id($conn);
        }

        mysqli_stmt_close($stmt);
        mysqli_commit($conn);

        require_once __DIR__ . '/notification_helpers.php';
        notifications_send_new_booking_alert(
            $conn,
            $user_id,
            $facility_type,
            $booking_date,
            $starts,
            $ids,
            $purpose
        );

        $count = count($ids);
        $msg = $count === 1
            ? 'Your 1-hour booking was submitted and is pending approval.'
            : "Your {$count}-hour booking was submitted and is pending approval.";

        return ['success' => true, 'message' => $msg, 'booking_ids' => $ids];
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        return ['success' => false, 'message' => 'Booking failed. Please try again.', 'booking_ids' => []];
    }
}

/** Whether `bookings` table has payment columns (run sql/alter_bookings_payment.sql). */
function booking_has_payment_columns(mysqli $conn): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $res = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_method'");
    $cache = ($res instanceof mysqli_result && $res->num_rows > 0);
    return $cache;
}

/**
 * Resolve court/table display name for checkout summary.
 */
function booking_court_display_name(mysqli $conn, string $facility_type, int $court_id): string
{
    $cfg = booking_court_table_config($facility_type);
    if ($cfg === null || $court_id <= 0) {
        return '';
    }
    $table = $cfg['table'];
    $idCol = $cfg['id_col'];
    $nameCol = $cfg['name_col'];
    $sql = "SELECT `{$nameCol}` AS nm FROM `{$table}` WHERE `{$idCol}` = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return 'Court #' . $court_id;
    }
    mysqli_stmt_bind_param($stmt, 'i', $court_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    return $row ? (string) $row['nm'] : ('#' . $court_id);
}

/**
 * Create bookings after successful payment (same slot rules + payment fields).
 * @return array{success: bool, message: string, booking_ids: list<int>, total_paid: float}
 */
function booking_create_reservations_with_payment(
    mysqli $conn,
    int $user_id,
    string $facility_type,
    string $booking_date,
    ?int $court_id,
    array $start_times,
    string $purpose,
    string $payment_method,
    string $payment_status = 'paid',
    string $booking_status = 'pending'
): array {
    require_once __DIR__ . '/facility_pricing.php';

    if (!booking_has_payment_columns($conn)) {
        return [
            'success' => false,
            'message' => 'Payment columns missing. Run sql/alter_bookings_payment.sql in phpMyAdmin.',
            'booking_ids' => [],
            'total_paid' => 0.0,
        ];
    }

    require_once __DIR__ . '/payment_checkout.php';
    if (!payment_method_is_valid($payment_method)) {
        return ['success' => false, 'message' => 'Invalid payment method.', 'booking_ids' => [], 'total_paid' => 0.0];
    }

    $starts = array_values(array_unique(array_map('strval', $start_times)));
    $hours = count($starts);
    $calc = facility_pricing_calculate($facility_type, $hours, $conn);
    $total_paid = (float) $calc['total'];

    if (!booking_validate_date($booking_date)) {
        return ['success' => false, 'message' => 'Invalid booking date.', 'booking_ids' => [], 'total_paid' => 0.0];
    }
    if (!booking_validate_continuous_slots($starts)) {
        return ['success' => false, 'message' => 'Invalid time slots.', 'booking_ids' => [], 'total_paid' => 0.0];
    }
    if (booking_is_court_based($facility_type) && ($court_id === null || $court_id <= 0)) {
        return ['success' => false, 'message' => 'Court not selected.', 'booking_ids' => [], 'total_paid' => 0.0];
    }
    if (!booking_is_court_based($facility_type)) {
        $court_id = null;
    }

    mysqli_begin_transaction($conn);

    try {
        if ($court_id === null) {
            $insertSql = 'INSERT INTO bookings (user_id, facility_type, court_id, booking_date, start_time, end_time, purpose, booking_status, payment_method, payment_amount, payment_status)
                          VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = mysqli_prepare($conn, $insertSql);
            $bindOpen = 'issssssds';
        } else {
            $insertSql = 'INSERT INTO bookings (user_id, facility_type, court_id, booking_date, start_time, end_time, purpose, booking_status, payment_method, payment_amount, payment_status)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = mysqli_prepare($conn, $insertSql);
            $bindOpen = 'isissssssds';
        }

        if (!$stmt) {
            throw new RuntimeException('Prepare failed.');
        }

        $ids = [];
        $rowIndex = 0;
        $totalRows = count($starts);

        foreach ($starts as $start) {
            $h = (int) substr($start, 0, 2);
            $end = sprintf('%02d:00:00', $h + 1);

            if (!booking_slot_is_available($conn, $facility_type, $booking_date, $court_id, $start, $end)) {
                mysqli_rollback($conn);
                mysqli_stmt_close($stmt);
                return [
                    'success' => false,
                    'message' => 'One or more slots are no longer available. Please start again.',
                    'booking_ids' => [],
                    'total_paid' => 0.0,
                ];
            }

            $rowAmount = facility_pricing_row_amount($facility_type, $hours, $rowIndex, $totalRows, $conn);
            $rowIndex++;

            if ($court_id === null) {
                mysqli_stmt_bind_param(
                    $stmt,
                    $bindOpen,
                    $user_id,
                    $facility_type,
                    $booking_date,
                    $start,
                    $end,
                    $purpose,
                    $booking_status,
                    $payment_method,
                    $rowAmount,
                    $payment_status
                );
            } else {
                mysqli_stmt_bind_param(
                    $stmt,
                    $bindOpen,
                    $user_id,
                    $facility_type,
                    $court_id,
                    $booking_date,
                    $start,
                    $end,
                    $purpose,
                    $booking_status,
                    $payment_method,
                    $rowAmount,
                    $payment_status
                );
            }

            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('Insert failed.');
            }
            $ids[] = (int) mysqli_insert_id($conn);
        }

        mysqli_stmt_close($stmt);
        mysqli_commit($conn);

        require_once __DIR__ . '/notification_helpers.php';
        notifications_send_new_booking_alert(
            $conn,
            $user_id,
            $facility_type,
            $booking_date,
            $starts,
            $ids,
            $purpose
        );

        return [
            'success'     => true,
            'message'     => 'Payment successful. Your booking is pending staff approval.',
            'booking_ids' => $ids,
            'total_paid'  => $total_paid,
        ];
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        return ['success' => false, 'message' => 'Payment could not be completed.', 'booking_ids' => [], 'total_paid' => 0.0];
    }
}
