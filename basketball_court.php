<?php
/**
 * Scholar Hub — Basketball Court detail
 */
$FACILITY = [
    'name'           => 'Basketball Court',
    'booking_name'   => 'Basketball Court',
    'tagline'        => 'Full-size indoor court with FIBA-style markings.',
    'image'          => 'assets/basketballcourt.jpeg',
    'description'    => 'The main indoor basketball court supports full-court games, training sessions, and intramural leagues. Side hoops can be used for half-court when the centre booking is for training only — follow on-court signage.',
    'hours_lines'    => [
        'Monday — Friday: 7:00 AM — 10:30 PM',
        'Saturday — Sunday: 8:00 AM — 9:00 PM',
        'Varsity training blocks may reduce public slots — see booking calendar',
    ],
    'location'       => 'Arena Complex — Court 1 (Main)\nEnter through Arena lobby; elevators to Level 0.',
    'rules'          => [
        'Indoor basketball shoes only; change footwear at lobby benches.',
        'Dunking only on approved rims; report bent rims to staff immediately.',
        'Personal speakers not allowed; use facility PA for approved events only.',
        'Respect shot clock / timer if installed for your booking category.',
        'No food on court surface; bottles with lids permitted on sidelines only.',
    ],
    'status'         => 'Available',
    'status_class'   => 'bg-success',
    'capacity'       => 'Full court: up to 10 active players recommended',
    'equipment'      => ['Electronic scoreboard', 'Shot clock (league)', 'Ball racks', 'Bleacher seating', 'A/V hookup (events)'],
];
require __DIR__ . '/includes/facility_page.php';
