<?php

/*
 * Structural site settings. Everything an editor can change (company details,
 * contact email, booking link, social links, status line, navigation, SEO
 * defaults) lives in the CMS (Filament > Site), not here.
 */
return [
    'name' => 'Jebbari',

    'locales' => ['nl', 'en'],
    'default_locale' => 'nl',
    'timezone' => 'Europe/Amsterdam',

    /*
     * The one business address, as given by the owner. Shown in the footer and
     * in structured data; no other address is published. No VAT number is
     * published either (owner decision).
     */
    'address' => [
        'street' => 'Professor W.H. Keesomlaan 12',
        'postal_code' => '1183 DJ',
        'locality' => 'Amstelveen',
        'country' => 'The Netherlands',
        'country_code' => 'NL',
    ],

    /*
     * The founder, as stated by the owner. Used for the Person in JSON-LD and
     * for /llms.txt. Never add education (schools, degrees, alumniOf): the
     * owner does not want it published. Profile links (LinkedIn, GitHub, ...)
     * come from Site > Social links in the CMS and are added to sameAs.
     */
    'founder' => [
        'name' => 'Amir Jebbari',
        'job_title' => ['nl' => 'Oprichter', 'en' => 'Founder'],
        'knows_about' => ['Laravel', 'PHP', 'Vue.js', 'TypeScript', 'Web application development', 'SaaS', 'Web security', 'Server security'],
        'same_as' => ['https://amirs.life'],
        // The owner's other sites, listed in /llms.txt. Each should link back to jebbari.nl.
        'related_sites' => [
            'https://amirs.life' => 'Personal site of Amir Jebbari',
            'https://barbercuts.nl' => 'Mobile-first booking web app for a barber, built by Amir Jebbari',
        ],
    ],
];
