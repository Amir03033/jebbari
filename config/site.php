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
     * The founder, for structured data (schema.org Person) and /llms.txt. Owner-
     * supplied facts only (docs/AI-VISIBILITY.md). Never add education
     * (alumniOf, hasCredential): the owner does not want it published.
     * same_as lists only profiles the owner confirmed.
     */
    'person' => [
        'name' => 'Amir Jebbari',
        'job_title' => ['nl' => 'Oprichter', 'en' => 'Founder'],
        'description' => [
            'nl' => 'Amir Jebbari is de oprichter van Jebbari, gevestigd in Amsterdam en Zwolle. Hij bouwt webapplicaties met Laravel, Vue en TypeScript.',
            'en' => 'Amir Jebbari is the founder of Jebbari, based in Amsterdam and Zwolle, the Netherlands. He builds web applications with Laravel, Vue and TypeScript.',
        ],
        'home_location' => 'Zwolle',
        'knows_about' => ['Laravel', 'PHP', 'Vue.js', 'TypeScript', 'Web application development', 'SaaS', 'Web security'],
        // The owner's other sites, listed in /llms.txt. Each should link back to jebbari.nl.
        'related_sites' => [
            'https://amirs.life' => 'Personal site of Amir Jebbari',
            'https://barbercuts.nl' => 'Mobile-first booking web app for a barber, built by Amir Jebbari',
        ],
        'same_as' => [
            'https://amirs.life',
            // LinkedIn: add the exact URL once the owner supplies it.
        ],
    ],

    // Set to true once Satoshi woff2 files are added (see resources/fonts/README.md).
    'fonts' => ['satoshi' => false],
];
