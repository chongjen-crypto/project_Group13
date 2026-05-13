<?php
/**
 * Scholar Hub — Snooker Room detail
 */
$FACILITY = [
    'name'           => 'Snooker Room',
    'booking_name'   => 'Snooker Room',
    'tagline'        => 'Quiet cue-sports room with professional tables.',
    'image'          => 'assets/snookerroom.jpg',
    'description'    => 'The snooker room is kept at low noise levels for concentration and courtesy to neighbouring study zones. Tables are brushed and ironed on a maintenance schedule; peak evening slots may be limited.',
    'hours_lines'    => [
        'Monday — Friday: 11:00 AM — 10:00 PM',
        'Saturday — Sunday: 12:00 PM — 8:00 PM',
        'Exam weeks: may close earlier — check dashboard notices',
    ],
    'location'       => 'Student Union — Recreation Wing, Room R-204\nTake stairs or lift to Level 2; follow “Cue Sports” signage.',
    'rules'          => [
        'No running or loud music; conversation at moderate volume only.',
        'Chalk use over bins; clean hands before touching upholstery.',
        'Cue tips and rests must be returned to the rack after play.',
        'Outside food is discouraged; covered drinks only at side tables.',
        'Report torn cloth or missing equipment before starting your frame.',
    ],
    'status'         => 'Available',
    'status_class'   => 'bg-success',
    'capacity'       => '2 players per table (spectators max 2 per booking)',
    'equipment'      => ['Full-size tables', 'Overhead lighting', 'Cue rack & rests', 'Triangle & chalk', 'Scoreboard'],
];
require __DIR__ . '/includes/facility_page.php';
