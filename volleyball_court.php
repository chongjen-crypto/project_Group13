<?php
/**
 * Scholar Hub — Volleyball Court detail
 */
$FACILITY = [
    'facility_type'  => 'volleyball',
    'name'           => 'Volleyball Court',
    'booking_name'   => 'Volleyball Court',
    'tagline'        => 'Indoor and sand options for recreational and club volleyball.',
    'image'          => 'assets/volleyballcourt.webp',
    'description'    => 'Volleyball courts are set to international dimensions where space allows. Nets are adjusted by staff for recreational vs competition height upon request. Sand court availability depends on weather and maintenance rotation.',
    'hours_lines'    => [
        'Indoor courts: Monday — Friday 8:00 AM — 10:00 PM',
        'Weekend indoor: 9:00 AM — 8:00 PM',
        'Sand court: daylight hours only (closes at dusk unless lit)',
    ],
    'location'       => 'Multi-Purpose Hall (indoor) + South Sand Court (outdoor)\nIndoor: Hall B; Outdoor: follow path behind tennis zone.',
    'rules'          => [
        'Clean sand off shoes before entering indoor halls from the sand court.',
        'Do not hang or climb on nets; report loose tension to reception.',
        'Knee pads recommended for indoor play on hard surface.',
        'Ball must be appropriate for surface type (indoor vs outdoor ball).',
        'Music only with low volume and staff approval in hall bookings.',
    ],
    'status'         => 'Limited Slots',
    'status_class'   => 'bg-warning text-dark',
    'capacity'       => 'Up to 12 players per court (recreational rotation)',
    'equipment'      => ['Adjustable nets', 'Antennae (indoor)', 'Ball pump at desk', 'Score sheets', 'First aid kit'],
];
require __DIR__ . '/includes/facility_page.php';
