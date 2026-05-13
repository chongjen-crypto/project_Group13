<?php
/**
 * Scholar Hub — Tennis Court detail
 */
$FACILITY = [
    'name'           => 'Tennis Court',
    'booking_name'   => 'Tennis Court',
    'tagline'        => 'Outdoor hard courts with evening floodlighting.',
    'image'          => 'assets/tenniscourt.jpg',
    'description'    => 'Hard-surface tennis courts designed for consistent bounce and durability. Floodlights allow evening play during semester terms. Courts are lined for singles and doubles and maintained weekly for player safety and comfort.',
    'hours_lines'    => [
        'Monday — Friday: 6:30 AM — 9:30 PM',
        'Saturday — Sunday: 7:00 AM — 8:00 PM',
        'Floodlights: automatically until closing (seasonal schedule may apply)',
    ],
    'location'       => 'Outdoor Sports Zone — North Campus\nAdjacent to the running track; follow signs to Tennis Courts T1 — T4.',
    'rules'          => [
        'Proper tennis footwear required; heeled or studded shoes are not allowed.',
        'One booking = one court; warm-up limited to 10 minutes when others are waiting.',
        'Return borrowed balls and equipment to the kiosk after your session.',
        'Pets, bicycles, and scooters are not permitted inside the court fencing.',
        'In wet weather, courts may be closed without notice — check status on the dashboard.',
    ],
    'status'         => 'Limited Slots',
    'status_class'   => 'bg-warning text-dark',
    'capacity'       => 'Singles or doubles (max 4 per court)',
    'equipment'      => ['Floodlighting', 'Wind screens (select courts)', 'Ball machine (on request)', 'Bench seating', 'Water fountain'],
];
require __DIR__ . '/includes/facility_page.php';
