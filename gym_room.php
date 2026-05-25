<?php
/**
 * Scholar Hub — Gym Room detail
 */
$FACILITY = [
    'facility_type'  => 'gym',
    'name'           => 'Gym Room',
    'booking_name'   => 'Gym Room',
    'tagline'        => 'Strength and cardio equipment in a supervised training space.',
    'image'          => 'assets/gymroom.jpg',
    'description'    => 'The gym offers a balanced mix of resistance machines, free weights, and cardio equipment. Orientation is recommended for first-time users. The space is ventilated and cleaned on a fixed schedule between peak blocks.',
    'hours_lines'    => [
        'Monday — Sunday: 6:00 AM — 11:00 PM',
        'Deep cleaning closure: Tuesdays 2:00 — 4:00 PM (no bookings)',
    ],
    'location'       => 'Sports & Wellness Building — Level 1\nScan your student ID at the gym reception turnstile.',
    'rules'          => [
        'Towel required for all equipment contact; wipe down machines after use.',
        'Closed-toe training shoes only; no outdoor muddy footwear.',
        'Re-rack weights and return accessories to designated storage.',
        'No chalk or powder that damages equipment without staff approval.',
        'Headphones recommended; keep phone calls brief and quiet.',
    ],
    'status'         => 'Maintenance',
    'status_class'   => 'bg-danger',
    'capacity'       => 'Occupancy monitored (max safe capacity displayed on entry)',
    'equipment'      => ['Cable machines', 'Dumbbells & racks', 'Treadmills & bikes', 'Stretching zone', 'Drinking station'],
];
require __DIR__ . '/includes/facility_page.php';
