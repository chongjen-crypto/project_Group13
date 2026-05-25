<?php
/**
 * Admin announcements / notifications (JSON file store — no DB table required).
 */

function admin_notifications_path(): string
{
    return __DIR__ . '/../data/admin_notifications.json';
}

/** @return list<array{title: string, time: string, type: string}> */
function admin_notifications_defaults(): array
{
    return [
        ['title' => 'Semester booking window opens Monday', 'time' => '2 hours ago', 'type' => 'announcement'],
        ['title' => 'Peak hours reminder: 4 PM – 8 PM', 'time' => '5 hours ago', 'type' => 'announcement'],
        ['title' => 'System update scheduled — 02:00 AM', 'time' => 'Yesterday', 'type' => 'system'],
    ];
}

/** @return list<array{title: string, time: string, type: string}> */
function admin_notifications_load(): array
{
    $path = admin_notifications_path();
    if (!is_readable($path)) {
        return admin_notifications_defaults();
    }
    $raw = file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return admin_notifications_defaults();
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return admin_notifications_defaults();
    }
    $out = [];
    foreach ($data as $row) {
        if (!is_array($row)) {
            continue;
        }
        $title = isset($row['title']) ? trim((string) $row['title']) : '';
        if ($title === '') {
            continue;
        }
        $out[] = [
            'title' => $title,
            'time' => isset($row['time']) ? (string) $row['time'] : '',
            'type' => isset($row['type']) ? (string) $row['type'] : 'announcement',
        ];
    }
    return $out !== [] ? $out : admin_notifications_defaults();
}

/**
 * @param list<array{title: string, time: string, type: string}> $items
 */
function admin_notifications_save(array $items): bool
{
    $dir = dirname(admin_notifications_path());
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true)) {
            return false;
        }
    }
    $json = json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }
    return @file_put_contents(admin_notifications_path(), $json, LOCK_EX) !== false;
}

function admin_notifications_add(string $title, string $type): bool
{
    $title = trim($title);
    if ($title === '') {
        return false;
    }
    $allowed = ['announcement', 'system'];
    if (!in_array($type, $allowed, true)) {
        $type = 'announcement';
    }
    $items = admin_notifications_load();
    array_unshift($items, [
        'title' => $title,
        'time'  => date('Y-m-d H:i'),
        'type'  => $type,
    ]);
    // Keep list reasonable for a student project
    $items = array_slice($items, 0, 50);
    return admin_notifications_save($items);
}
