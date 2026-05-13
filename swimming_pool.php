<?php
/**
 * Scholar Hub — Swimming Pool detail
 */
$FACILITY = [
    'name'           => 'Swimming Pool',
    'booking_name'   => 'Swimming Pool',
    'tagline'        => 'Olympic-size pool with dedicated lap and recreational lanes.',
    'image'          => 'assets/swimmingpool.jpg',
    'description'    => 'The university swimming pool meets training standards for aquatic sports and general fitness. Lane swimming is available during peak hours; recreational areas open during off-peak sessions. Certified lifeguards are on duty during all opening times.',
    'hours_lines'    => [
        'Monday — Friday: 6:00 AM — 9:00 PM',
        'Saturday — Sunday: 7:00 AM — 7:00 PM',
        'Lane swim priority: weekday mornings 6:00 — 9:00 AM',
    ],
    'location'       => 'Aquatics Centre — Main Campus\nGround floor; entrance via turnstiles next to the gym.',
    'rules'          => [
        'Shower before entering the pool; swim caps required in lap lanes.',
        'Appropriate swimwear only; cotton clothing is not permitted in the water.',
        'No diving in shallow areas; follow lifeguard instructions at all times.',
        'Children under 12 must be accompanied by a responsible adult (see policy).',
        'Glass containers and food are prohibited on pool deck.',
    ],
    'status'         => 'Available',
    'status_class'   => 'bg-success',
    'capacity'       => 'Lane capacity managed per session (see booking slots)',
    'equipment'      => ['Lane dividers', 'Starting blocks', 'Accessibility lift', 'Locker rooms', 'Hair dryers (limited)'],
];
require __DIR__ . '/includes/facility_page.php';
