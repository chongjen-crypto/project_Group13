<?php
/**
 * Scholar Hub — Track Field detail
 */
$FACILITY = [
    'facility_type'  => 'track',
    'name'           => 'Track Field',
    'booking_name'   => 'Track Field',
    'tagline'        => '400 m synthetic track and infield for athletics training.',
    'image'          => 'assets/trackfield.webp',
    'description'    => 'The track and field facility supports sprint training, distance running, and athletics clubs. The infield may be sectioned for drills or events. Booking helps manage congestion during exam periods and varsity training windows.',
    'hours_lines'    => [
        'Track lanes: Monday — Sunday 5:30 AM — 8:00 PM',
        'Infield: subject to event schedule — check notices at gate',
        'Winter hours may end 1 hour earlier — announced on dashboard',
    ],
    'location'       => 'Main Stadium — East side of campus\nEnter via Gate 3; infield access through staff booth when open.',
    'rules'          => [
        'Inner lanes reserved for speed work when marked; jog in outer lanes when busy.',
        'Spikes allowed only on designated competition days unless otherwise posted.',
        'No wheeled vehicles on the track surface (except approved wheelchairs).',
        'Pets are not permitted on the track or infield.',
        'Take litter with you; bins are located at each gate.',
    ],
    'status'         => 'Available',
    'status_class'   => 'bg-success',
    'capacity'       => 'Track: multiple users; infield: session limits apply',
    'equipment'      => ['Synthetic surface', 'Lane markings', 'Long jump pit (seasonal)', 'Public address (events)', 'Floodlights (evenings)'],
];
require __DIR__ . '/includes/facility_page.php';
