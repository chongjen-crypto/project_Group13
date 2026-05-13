<?php
/**
 * Scholar Hub — Futsal Court detail
 */
$FACILITY = [
    'name'           => 'Futsal Court',
    'booking_name'   => 'Futsal Court',
    'tagline'        => 'Indoor 5-a-side pitch with perimeter boards.',
    'image'          => 'assets/futsalcourt.jpg',
    'description'    => 'The futsal court uses a sprung surface suitable for fast small-sided games. Goals are fixed with safety padding. Ideal for clubs, PE modules, and informal matches when booked as a group.',
    'hours_lines'    => [
        'Monday — Sunday: 8:00 AM — 10:00 PM',
        'Maintenance window: Friday 6:00 — 8:00 AM (court closed)',
    ],
    'location'       => 'Indoor Football Hall — West Annex\nGround floor; entrance next to equipment store.',
    'rules'          => [
        'Flat-soled or turf indoor shoes only; metal studs prohibited.',
        'Shin guards strongly recommended for all players.',
        'Ball size 4 futsal ball only (available for loan with deposit).',
        'Do not lean or hang on goal nets; goals are anchored — no moving.',
        'Keep doors closed during play to protect adjacent corridors.',
    ],
    'status'         => 'Maintenance',
    'status_class'   => 'bg-danger',
    'capacity'       => '5 vs 5 on court + substitutes on bench',
    'equipment'      => ['Perimeter boards', 'Substitution benches', 'Ball loan', 'Clock (manual)', 'PA (events only)'],
];
require __DIR__ . '/includes/facility_page.php';
