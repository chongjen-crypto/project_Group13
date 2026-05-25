<?php
/**
 * Facility management — single source of truth in `facilities` table.
 */

function facility_canonical_meta(): array
{
    return [
        'badminton' => [
            'detail_url' => 'badminton.php',
            'default_name' => 'Badminton Court',
            'default_desc' => 'Indoor air-conditioned court',
            'default_image' => 'assets/badmintoncourt.webp',
            'default_location' => 'Sports Complex',
            'default_rules' => [
                'Non-marking indoor sports shoes only.',
                'Maximum session length follows your booking slot.',
                'Food and drinks (except sealed water) are not allowed on court.',
            ],
        ],
        'tennis' => [
            'detail_url' => 'tennis.php',
            'default_name' => 'Tennis Court',
            'default_desc' => 'Outdoor hard court with lighting',
            'default_image' => 'assets/tenniscourt.jpg',
            'default_location' => 'Sports Complex',
            'default_rules' => ['Proper tennis shoes required.', 'Respect booked court times.', 'Report equipment issues to staff.'],
        ],
        'swimming' => [
            'detail_url' => 'swimming_pool.php',
            'default_name' => 'Swimming Pool',
            'default_desc' => 'Olympic-size swimming pool',
            'default_image' => 'assets/swimmingpool.jpg',
            'default_location' => 'Aquatic Center',
            'default_rules' => ['Shower before entering the pool.', 'No running on pool deck.', 'Follow lifeguard instructions at all times.'],
        ],
        'gym' => [
            'detail_url' => 'gym_room.php',
            'default_name' => 'Gym Room',
            'default_desc' => 'Modern gym equipment',
            'default_image' => 'assets/gymroom.jpg',
            'default_location' => 'Block A',
            'default_rules' => ['Wipe equipment after use.', 'Proper athletic attire required.', 'Re-rack weights after use.'],
        ],
        'track' => [
            'detail_url' => 'track_field.php',
            'default_name' => 'Track Field',
            'default_desc' => '400m synthetic running track',
            'default_image' => 'assets/trackfield.webp',
            'default_location' => 'Stadium',
            'default_rules' => ['Stay in your assigned lane when busy.', 'No spikes on synthetic surface unless permitted.', 'Yield to official events.'],
        ],
        'volleyball' => [
            'detail_url' => 'volleyball_court.php',
            'default_name' => 'Volleyball Court',
            'default_desc' => 'Sand and indoor options',
            'default_image' => 'assets/volleyballcourt.webp',
            'default_location' => 'Sports Complex',
            'default_rules' => ['Indoor court shoes only.', 'Maximum players per court as posted.', 'Vacate on time for the next booking.'],
        ],
        'basketball' => [
            'detail_url' => 'basketball_court.php',
            'default_name' => 'Basketball Court',
            'default_desc' => 'Full-size indoor court',
            'default_image' => 'assets/basketballcourt.jpeg',
            'default_location' => 'Sports Complex',
            'default_rules' => ['Indoor basketball shoes only.', 'Share court fairly during open slots.', 'No dunking on portable hoops unless allowed.'],
        ],
        'snooker' => [
            'detail_url' => 'snooker_room.php',
            'default_name' => 'Snooker Room',
            'default_desc' => 'Quiet room with professional tables',
            'default_image' => 'assets/snookerroom.jpg',
            'default_location' => 'Sports Complex',
            'default_rules' => ['Keep noise to a minimum.', 'Return cues and balls after play.', 'No food at the tables.'],
        ],
        'futsal' => [
            'detail_url' => 'futsal.php',
            'default_name' => 'Futsal Court',
            'default_desc' => 'Indoor 5-a-side pitch',
            'default_image' => 'assets/futsalcourt.jpg',
            'default_location' => 'Sports Complex',
            'default_rules' => ['Indoor futsal shoes only.', 'Respect booked slot end times.', 'Report damaged turf to staff.'],
        ],
    ];
}

function facilities_ensure_schema(mysqli $conn): void
{
    static $done = false;
    static $initializing = false;
    if ($done || $initializing) {
        return;
    }
    $initializing = true;

    $sql = "CREATE TABLE IF NOT EXISTS facilities (
        facility_id INT AUTO_INCREMENT PRIMARY KEY,
        facility_name VARCHAR(100) NOT NULL,
        facility_type VARCHAR(50) NOT NULL,
        description TEXT DEFAULT NULL,
        image VARCHAR(255) DEFAULT NULL,
        location VARCHAR(100) DEFAULT NULL,
        opening_time TIME DEFAULT '08:00:00',
        closing_time TIME DEFAULT '22:00:00',
        status ENUM('active','maintenance','closed') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_facility_type (facility_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($conn, $sql);
    facilities_ensure_columns($conn);

    foreach (facility_canonical_meta() as $type => $meta) {
        $stmt = mysqli_prepare($conn, 'SELECT facility_id FROM facilities WHERE facility_type = ? LIMIT 1');
        if (!$stmt) {
            continue;
        }
        mysqli_stmt_bind_param($stmt, 's', $type);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $exists = $res && mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
        if ($exists) {
            continue;
        }

        $name = $meta['default_name'];
        $desc = $meta['default_desc'];
        $image = $meta['default_image'];
        $location = $meta['default_location'];
        $status = 'active';
        $pricing = facility_catalog_pricing_defaults($type);
        $rulesText = facility_rules_to_text($meta['default_rules'] ?? []);
        $priceAmount = (float) $pricing['amount'];
        $priceMode = (string) $pricing['mode'];
        $ins = mysqli_prepare(
            $conn,
            'INSERT INTO facilities (facility_name, facility_type, description, image, location, status, price_amount, price_mode, rules)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        if ($ins) {
            mysqli_stmt_bind_param(
                $ins,
                'ssssssdss',
                $name,
                $type,
                $desc,
                $image,
                $location,
                $status,
                $priceAmount,
                $priceMode,
                $rulesText
            );
            mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);
        }
    }

    facilities_backfill_price_rules($conn);

    $initializing = false;
    $done = true;
}

function facilities_ensure_columns(mysqli $conn): void
{
    $alters = [
        'price_amount' => 'ADD COLUMN price_amount DECIMAL(10,2) NOT NULL DEFAULT 5.00',
        'price_mode' => "ADD COLUMN price_mode ENUM('hourly','entry') NOT NULL DEFAULT 'hourly'",
        'rules' => 'ADD COLUMN rules TEXT DEFAULT NULL',
    ];
    foreach ($alters as $column => $ddl) {
        $check = mysqli_query($conn, "SHOW COLUMNS FROM facilities LIKE '{$column}'");
        if ($check && mysqli_num_rows($check) === 0) {
            mysqli_query($conn, "ALTER TABLE facilities {$ddl}");
        }
    }
}

function facility_catalog_pricing_defaults(string $facility_type): array
{
    require_once __DIR__ . '/facility_pricing.php';
    $p = facility_pricing_catalog();
    if (isset($p[$facility_type])) {
        return ['amount' => (float) $p[$facility_type]['amount'], 'mode' => (string) $p[$facility_type]['mode']];
    }
    return ['amount' => 5.0, 'mode' => 'hourly'];
}

function facility_price_label(string $mode): string
{
    return $mode === 'entry' ? 'Per Entry' : 'Per Hour';
}

/**
 * @return list<string>
 */
function facility_rules_to_array(?string $text): array
{
    if ($text === null || trim($text) === '') {
        return [];
    }
    $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
    $out = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

/**
 * @param list<string> $rules
 */
function facility_rules_to_text(array $rules): string
{
    $clean = [];
    foreach ($rules as $rule) {
        $rule = trim((string) $rule);
        if ($rule !== '') {
            $clean[] = $rule;
        }
    }
    return implode("\n", $clean);
}

function facilities_backfill_price_rules(mysqli $conn): void
{
    foreach (facility_canonical_meta() as $type => $meta) {
        $stmt = mysqli_prepare(
            $conn,
            'SELECT price_amount, price_mode, rules FROM facilities WHERE facility_type = ? ORDER BY facility_id ASC LIMIT 1'
        );
        if (!$stmt) {
            continue;
        }
        mysqli_stmt_bind_param($stmt, 's', $type);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        if (!$row) {
            continue;
        }
        $pricing = facility_catalog_pricing_defaults($type);
        $rulesText = trim((string) ($row['rules'] ?? ''));
        if ($rulesText === '') {
            $rulesText = facility_rules_to_text($meta['default_rules'] ?? []);
        }
        $priceAmount = isset($row['price_amount']) ? (float) $row['price_amount'] : 0.0;
        if ($priceAmount <= 0) {
            $priceAmount = (float) $pricing['amount'];
        }
        $priceMode = (string) ($row['price_mode'] ?? '');
        if (!in_array($priceMode, ['hourly', 'entry'], true)) {
            $priceMode = (string) $pricing['mode'];
        }

        $stmt = mysqli_prepare(
            $conn,
            'UPDATE facilities SET price_amount = ?, price_mode = ?, rules = ?
             WHERE facility_type = ? AND (rules IS NULL OR rules = "" OR price_amount <= 0 OR price_mode = "")'
        );
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'dsss', $priceAmount, $priceMode, $rulesText, $type);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

    }
}

function facility_display_name(string $facility_type): string
{
    $meta = facility_canonical_meta();
    return $meta[$facility_type]['default_name'] ?? ucfirst(str_replace('_', ' ', $facility_type));
}

function facility_type_from_display_name(string $name): string
{
    foreach (facility_canonical_meta() as $type => $meta) {
        if (strcasecmp($meta['default_name'], $name) === 0) {
            return $type;
        }
    }
    return strtolower(str_replace(' ', '_', trim($name)));
}

function facility_ui_status(string $db_status): string
{
    return $db_status === 'active' ? 'Available' : 'Unavailable';
}

function facility_ui_status_class(string $db_status): string
{
    return $db_status === 'active' ? 'bg-success' : 'bg-danger';
}

function facility_db_status_from_ui(string $ui): string
{
    return $ui === 'available' ? 'active' : 'closed';
}

function facility_is_bookable(string $db_status): bool
{
    return $db_status === 'active';
}

function facility_row_to_card(array $row, ?array $meta = null): array
{
    $type = (string) $row['facility_type'];
    $meta = $meta ?? (facility_canonical_meta()[$type] ?? []);
    $image = trim((string) ($row['image'] ?? ''));
    if ($image === '') {
        $image = $meta['default_image'] ?? 'assets/badmintoncourt.webp';
    }
    $dbStatus = (string) ($row['status'] ?? 'active');
    $opening = substr((string) ($row['opening_time'] ?? '08:00:00'), 0, 5);
    $closing = substr((string) ($row['closing_time'] ?? '22:00:00'), 0, 5);
    $defaults = facility_catalog_pricing_defaults($type);
    $priceAmount = isset($row['price_amount']) ? (float) $row['price_amount'] : (float) $defaults['amount'];
    if ($priceAmount <= 0) {
        $priceAmount = (float) $defaults['amount'];
    }
    $priceMode = (string) ($row['price_mode'] ?? $defaults['mode']);
    if (!in_array($priceMode, ['hourly', 'entry'], true)) {
        $priceMode = (string) $defaults['mode'];
    }
    $rulesText = trim((string) ($row['rules'] ?? ''));
    if ($rulesText === '') {
        $rulesText = facility_rules_to_text($meta['default_rules'] ?? []);
    }
    $rules = facility_rules_to_array($rulesText);

    return [
        'facility_id' => (int) $row['facility_id'],
        'facility_type' => $type,
        'name' => (string) ($row['facility_name'] ?? ($meta['default_name'] ?? $type)),
        'desc' => (string) ($row['description'] ?? ($meta['default_desc'] ?? '')),
        'location' => (string) ($row['location'] ?? ($meta['default_location'] ?? '')),
        'opening_time' => $opening,
        'closing_time' => $closing,
        'image' => $image,
        'detail_url' => $meta['detail_url'] ?? 'booking.php?type=' . rawurlencode($type),
        'db_status' => $dbStatus,
        'status' => facility_ui_status($dbStatus),
        'status_class' => facility_ui_status_class($dbStatus),
        'ui_status' => $dbStatus === 'active' ? 'available' : 'unavailable',
        'bookable' => facility_is_bookable($dbStatus),
        'price_amount' => $priceAmount,
        'price_mode' => $priceMode,
        'price_label' => facility_price_label($priceMode),
        'rules' => $rules,
        'rules_text' => $rulesText,
    ];
}

/**
 * Payload for admin edit modal (JSON).
 *
 * @return array<string, mixed>
 */
function facility_admin_edit_payload(array $card): array
{
    return [
        'id' => (int) ($card['facility_id'] ?? 0),
        'name' => (string) ($card['name'] ?? ''),
        'description' => (string) ($card['desc'] ?? ''),
        'location' => (string) ($card['location'] ?? ''),
        'opening' => (string) ($card['opening_time'] ?? ''),
        'closing' => (string) ($card['closing_time'] ?? ''),
        'image' => (string) ($card['image'] ?? ''),
        'status' => (string) ($card['ui_status'] ?? 'available'),
        'price_amount' => (float) ($card['price_amount'] ?? 0),
        'price_mode' => (string) ($card['price_mode'] ?? 'hourly'),
        'rules_text' => (string) ($card['rules_text'] ?? ''),
    ];
}

/**
 * Canonical row per facility_type (lowest facility_id).
 */
function facility_fetch_by_type(mysqli $conn, string $facility_type): ?array
{
    $stmt = mysqli_prepare(
        $conn,
        'SELECT * FROM facilities WHERE facility_type = ? ORDER BY facility_id ASC LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, 's', $facility_type);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    return $row ?: null;
}

function facility_booking_counts(mysqli $conn, string $facility_type): array
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) AS c FROM bookings
         WHERE facility_type = ? AND booking_date = CURDATE()
         AND booking_status NOT IN ('cancelled', 'rejected')"
    );
    $today = 0;
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $facility_type);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res && ($r = mysqli_fetch_assoc($res))) {
            $today = (int) $r['c'];
        }
        mysqli_stmt_close($stmt);
    }

    $stmt2 = mysqli_prepare($conn, 'SELECT COUNT(*) AS c FROM bookings WHERE facility_type = ?');
    $total = 0;
    if ($stmt2) {
        mysqli_stmt_bind_param($stmt2, 's', $facility_type);
        mysqli_stmt_execute($stmt2);
        $res2 = mysqli_stmt_get_result($stmt2);
        if ($res2 && ($r2 = mysqli_fetch_assoc($res2))) {
            $total = (int) $r2['c'];
        }
        mysqli_stmt_close($stmt2);
    }

    $next = '—';
    $stmt3 = mysqli_prepare(
        $conn,
        "SELECT start_time FROM bookings
         WHERE facility_type = ? AND booking_date >= CURDATE()
         AND booking_status IN ('pending', 'approved')
         ORDER BY booking_date ASC, start_time ASC LIMIT 1"
    );
    if ($stmt3) {
        mysqli_stmt_bind_param($stmt3, 's', $facility_type);
        mysqli_stmt_execute($stmt3);
        $res3 = mysqli_stmt_get_result($stmt3);
        if ($res3 && ($r3 = mysqli_fetch_assoc($res3)) && !empty($r3['start_time'])) {
            $next = substr((string) $r3['start_time'], 0, 5);
        }
        mysqli_stmt_close($stmt3);
    }

    return ['bookings_today' => $today, 'total_bookings' => $total, 'next_slot' => $next];
}

function facility_fetch_one(mysqli $conn, int $facility_id): ?array
{
    $stmt = mysqli_prepare($conn, 'SELECT * FROM facilities WHERE facility_id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, 'i', $facility_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

/**
 * Admin facility cards with booking stats.
 *
 * @return list<array<string, mixed>>
 */
function facilities_fetch_admin_list(mysqli $conn): array
{
    facilities_ensure_schema($conn);
    $items = [];

    foreach (facility_canonical_meta() as $type => $meta) {
        $row = facility_fetch_by_type($conn, $type);
        if (!$row) {
            continue;
        }
        $card = facility_row_to_card($row, $meta);
        $counts = facility_booking_counts($conn, $type);
        $card['bookings_today'] = (string) $counts['bookings_today'];
        $card['total_bookings'] = (int) $counts['total_bookings'];
        $card['next_slot'] = $counts['next_slot'];
        $items[] = $card;
    }

    return $items;
}

/**
 * Student dashboard facility cards (same data as admin).
 *
 * @return list<array<string, mixed>>
 */
function facilities_fetch_student_cards(mysqli $conn): array
{
    facilities_ensure_schema($conn);
    $items = [];

    foreach (facility_canonical_meta() as $type => $meta) {
        $row = facility_fetch_by_type($conn, $type);
        if (!$row) {
            continue;
        }
        $items[] = facility_row_to_card($row, $meta);
    }

    return $items;
}

/**
 * Merge database facility into facility detail page config.
 */
function facility_merge_page_config(mysqli $conn, string $facility_type, array $defaults): array
{
    facilities_ensure_schema($conn);
    $row = facility_fetch_by_type($conn, $facility_type);
    if (!$row) {
        return $defaults;
    }

    $meta = facility_canonical_meta()[$facility_type] ?? [];
    $card = facility_row_to_card($row, $meta);
    $opening = $card['opening_time'];
    $closing = $card['closing_time'];

    $defaults['name'] = $card['name'];
    $defaults['booking_name'] = $card['name'];
    $defaults['description'] = $card['desc'] !== '' ? $card['desc'] : ($defaults['description'] ?? '');
    $defaults['image'] = $card['image'];
    $defaults['status'] = $card['status'];
    $defaults['status_class'] = $card['status_class'];
    $defaults['facility_type'] = $facility_type;
    $defaults['bookable'] = $card['bookable'];

    if ($card['location'] !== '') {
        $defaults['location'] = $card['location'];
    }

    $defaults['hours_lines'] = [
        'Daily: ' . $opening . ' — ' . $closing,
    ];

    if (!empty($card['rules'])) {
        $defaults['rules'] = $card['rules'];
    }

    return $defaults;
}

/**
 * Update all rows for this facility type so admin and student always match.
 *
 * @return array{success: bool, message: string}
 */
function facility_update(mysqli $conn, int $facility_id, array $data): array
{
    $row = facility_fetch_one($conn, $facility_id);
    if (!$row) {
        return ['success' => false, 'message' => 'Facility not found.'];
    }

    $facility_type = (string) $row['facility_type'];
    $name = trim((string) ($data['facility_name'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));
    $location = trim((string) ($data['location'] ?? ''));
    $opening = trim((string) ($data['opening_time'] ?? ''));
    $closing = trim((string) ($data['closing_time'] ?? ''));
    $image = trim((string) ($data['image'] ?? ''));
    $uiStatus = (string) ($data['ui_status'] ?? 'available');
    $dbStatus = facility_db_status_from_ui($uiStatus);
    $priceAmount = (float) ($data['price_amount'] ?? 0);
    $priceMode = (string) ($data['price_mode'] ?? 'hourly');
    $rulesText = trim((string) ($data['rules'] ?? ''));

    if ($name === '') {
        return ['success' => false, 'message' => 'Facility name is required.'];
    }
    if (!in_array($dbStatus, ['active', 'closed', 'maintenance'], true)) {
        $dbStatus = 'active';
    }
    if ($priceAmount < 0) {
        $priceAmount = 0;
    }
    if (!in_array($priceMode, ['hourly', 'entry'], true)) {
        $priceMode = 'hourly';
    }

    $openingSql = strlen($opening) === 5 ? $opening . ':00' : '08:00:00';
    $closingSql = strlen($closing) === 5 ? $closing . ':00' : '22:00:00';

    $stmt = mysqli_prepare(
        $conn,
        'UPDATE facilities SET facility_name = ?, description = ?, location = ?,
         opening_time = ?, closing_time = ?, image = ?, status = ?,
         price_amount = ?, price_mode = ?, rules = ?
         WHERE facility_type = ?'
    );
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)];
    }

    mysqli_stmt_bind_param(
        $stmt,
        'sssssssdsss',
        $name,
        $description,
        $location,
        $openingSql,
        $closingSql,
        $image,
        $dbStatus,
        $priceAmount,
        $priceMode,
        $rulesText,
        $facility_type
    );
    $ok = mysqli_stmt_execute($stmt);
    $affected = $ok ? mysqli_stmt_affected_rows($stmt) : 0;
    mysqli_stmt_close($stmt);

    if (!$ok) {
        return ['success' => false, 'message' => 'Could not save facility: ' . mysqli_error($conn)];
    }

    return [
        'success' => true,
        'message' => 'Facility updated (' . max(1, $affected) . ' record(s)). Students will see changes immediately.',
    ];
}

function facilities_status_map_by_type(mysqli $conn): array
{
    $map = [];
    foreach (facilities_fetch_student_cards($conn) as $f) {
        $map[$f['facility_type']] = [
            'status' => $f['status'],
            'status_class' => $f['status_class'],
        ];
    }
    return $map;
}
