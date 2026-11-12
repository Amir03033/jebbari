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
     * The founder, as stated by the owner. Used for the Person in JSON-LD and
     * for /llms.txt. Never add education (schools, degrees, alumniOf): the
     * owner does not want it published. Profile links (LinkedIn, GitHub, ...)
     * come from Site > Social links in the CMS and are added to sameAs.
     */
    'founder' => [
        'name' => 'Amir Jebbari',
        'job_title' => ['nl' => 'Oprichter', 'en' => 'Founder'],
        'home_location' => 'Zwolle',
        'knows_about' => ['Laravel', 'PHP', 'Vue.js', 'TypeScript', 'Web application development', 'SaaS', 'Web security', 'Server security'],
        'same_as' => ['https://amirs.life'],
    ],

    // Set to true once Satoshi woff2 files are added (see resources/fonts/README.md).
    'fonts' => ['satoshi' => false],
];
