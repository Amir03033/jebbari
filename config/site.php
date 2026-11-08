<?php

/*
 * Site-level facts. Everything the brief marks as owner-supplied is null here
 * and renders as a visible [[ OWNER: ... ]] placeholder until it is filled in.
 */
return [
    'name' => 'Jebbari',

    // Drives the "● Taking on projects" status line in the header.
    'taking_projects' => true,

    'locales' => ['nl', 'en'],
    'default_locale' => 'nl',
    'timezone' => 'Europe/Amsterdam',

    // Set to true once Satoshi woff2 files are added (see resources/fonts/README.md).
    'fonts' => ['satoshi' => false],

    'company' => [
        'legal_name' => null,   // [[ OWNER: legal name ]]
        'kvk' => null,          // [[ OWNER: KvK number ]]
        'btw' => null,          // [[ OWNER: BTW number ]]
    ],

    'email' => null,            // [[ OWNER: contact email ]]
    'booking_url' => null,      // [[ OWNER: booking URL ]]

    // label => url. Rendered as plain text links.
    'social' => [],             // [[ OWNER: social links ]]
];
