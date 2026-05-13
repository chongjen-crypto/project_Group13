<?php
/**
 * Scholar Hub — Badminton Court detail
 */
$FACILITY = [
    'name'           => 'Badminton Court',
    'booking_name'   => 'Badminton Court',
    'tagline'        => 'Indoor air-conditioned courts for training and competition.',
    'image'          => 'assets/badmintoncourt.webp',
    'description'    => 'Our badminton courts feature professional-grade flooring, high ceilings, and climate control for comfortable play year-round. Ideal for recreational rallies, club sessions, and inter-faculty tournaments. Bookings include standard court lighting and access to rest areas nearby.',
    'hours_lines'    => [
        'Monday — Friday: 7:00 AM — 10:00 PM',
        'Saturday — Sunday: 8:00 AM — 8:00 PM',
        'Public holidays: 9:00 AM — 6:00 PM (reduced slots)',
    ],
    'location'       => 'Sports Complex — Block A, Level 2\nCourt numbers A1 — A6 (signage at entrance).',
    'rules'          => [
        'Non-marking indoor sports shoes only; outdoor shoes are not permitted on court.',
        'Maximum session length follows your booking slot; vacate promptly for the next group.',
        'Shuttlecocks and racquets may be rented at reception or bring your own equipment.',
        'Food and drinks (except sealed water bottles) are not allowed inside the court hall.',
        'Report damaged nets or floor markings to staff before play begins.',
    ],
    'status'         => 'Available',
    'status_class'   => 'bg-success',
    'capacity'       => 'Up to 4 players per court',
    'equipment'      => ['Air conditioning', 'LED court lighting', 'Score flip boards', 'First aid station nearby', 'Equipment rental'],
];
require __DIR__ . '/includes/facility_page.php';
