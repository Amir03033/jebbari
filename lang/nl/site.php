<?php

/*
 * Alleen interfacetekst: knoppen, labels, toegankelijkheidstekst, formulierberichten
 * en lege toestanden. Paginainhoud (koppen, beschrijvingen, diensten, projecten,
 * artikelen, juridische tekst) staat in de CMS, nooit hier.
 */
return [
    'skip' => 'Ga naar inhoud',
    'descriptor' => 'Digitale studio',
    'tagline' => 'Software die klopt.',
    'meta_description' => 'Jebbari is een digitale studio in Zwolle en Amsterdam. Wij ontwerpen en bouwen websites en webapplicaties voor Nederlandse bedrijven.',
    'breadcrumb' => 'Kruimelpad',

    'live' => [
        'title' => 'Live status',
        'all_ok' => 'Alles online',
        'degraded' => 'Storing gemeten',
        'measuring' => 'Wordt gemeten…',
        'note' => 'Echt gemeten, vanuit jouw browser.',
        'site' => 'Website',
        'pages' => 'Pagina\'s',
        'cms' => 'Beheer',
        'ms' => 'ms',
    ],
    'marquee' => [
        'label' => 'Technologie',
    ],
    'theme' => [
        'toggle' => 'Wissel tussen licht en donker',
        'light' => 'Licht',
        'dark' => 'Donker',
    ],
    'hscroll' => [
        'progress' => 'Voortgang door de projecten',
    ],
    'status' => [
        'open' => 'Neemt projecten aan',
        'closed' => 'Volgeboekt',
    ],

    'nav' => [
        'label' => 'Hoofdmenu',
        'home' => 'Home',
        'work' => 'Werk',
        'services' => 'Diensten',
        'about' => 'Over mij',
        'blog' => 'Blog',
        'contact' => 'Contact',
        'menu' => 'Menu',
        'close' => 'Sluiten',
        'language' => 'Taal',
    ],

    'footer' => [
        'time' => 'Zwolle · Amsterdam, NL',
        'explore' => 'Ontdek',
        'contact' => 'Contact',
        'follow' => 'Elders',
        'email' => 'E-mail',
        'privacy' => 'Privacy',
        'kvk' => 'KvK',
        'btw' => 'BTW',
    ],

    'cta' => [
        'view_case' => 'Bekijk project',
        'read_more' => 'Lees meer',
        'all_work' => 'Al het werk',
        'about' => 'Meer over mij',
        'contact' => 'Neem contact op',
        'book' => 'Plan een gesprek',
    ],

    'field' => ['optional' => 'optioneel'],

    'empty' => [
        'work' => 'Er zijn nog geen projecten om te tonen.',
        'blog' => 'Er zijn nog geen artikelen.',
    ],

    'work' => ['all' => 'Al het werk'],

    'project' => [
        'category' => 'Categorie',
        'year' => 'Jaar',
        'client' => 'Klant',
        'stack' => 'Technologie',
        'visit' => 'Bekijk',
        'brief' => 'De opdracht',
        'built' => 'Wat is gebouwd',
        'outcome' => 'Resultaat',
        'overview' => 'Overzicht',
        'objectives' => 'Doelstellingen',
        'features' => 'Functies en uitwerking',
        'interface' => 'Interface',
        'next' => 'Volgend project',
        'gallery' => 'Galerij',
        'related' => 'Gerelateerd werk',
        'cta' => 'Iets vergelijkbaars in gedachten?',
    ],

    'service' => [
        'benefits' => 'Voordelen',
        'included' => 'Wat je krijgt',
        'not_included' => 'Wat we niet doen',
        'timeline' => 'Gebruikelijke doorlooptijd',
        'technology' => 'Technologie',
        'other' => 'Andere diensten',
        'cta' => 'Laten we bespreken wat je nodig hebt.',
    ],

    'packages' => [
        'label' => 'Pakketten',
        'from' => 'Vanaf',
        'on_request' => 'Op aanvraag',
    ],

    'blog' => [
        'articles' => 'Artikelen',
        'categories' => 'Categorieën',
        'all' => 'Alles',
        'pagination' => 'Paginering',
        'newer' => 'Nieuwer',
        'older' => 'Ouder',
        'related' => 'Lees verder',
        'cta' => 'Wil je het bespreken?',
        'reading' => '{1} :count minuut lezen|[2,*] :count minuten lezen',
    ],

    'legal' => ['updated' => 'Laatst bijgewerkt'],

    'preview' => [
        'label' => 'Voorbeeld, niet openbaar',
        'draft' => 'Concept, niet gepubliceerd',
        'live' => 'Gepubliceerd',
        'live_changes' => 'Gepubliceerd, met niet-gepubliceerde wijzigingen',
        'scheduled' => 'Gepland',
    ],

    'contact' => [
        'label' => 'Contact',
        'name' => 'Naam',
        'email' => 'E-mail',
        'company' => 'Bedrijf',
        'budget' => 'Budget',
        'budget_choose' => 'Kies een bereik',
        'budget_unsure' => 'Weet ik nog niet',
        'message' => 'Wat wil je laten bouwen?',
        'attr_name' => 'je naam',
        'attr_email' => 'je e-mailadres',
        'attr_company' => 'je bedrijf',
        'attr_budget' => 'je budget',
        'attr_message' => 'je bericht',
        'send' => 'Verstuur bericht',
        'sent_title' => 'Bedankt. Je bericht is aangekomen.',
        'sent_text' => 'We reageren binnen één werkdag.',
        'fix_errors' => 'Controleer de gemarkeerde velden.',
        'too_fast' => 'Dat ging erg snel. Controleer je bericht en verstuur het opnieuw.',
        'leave_empty' => 'Laat dit veld leeg',
        'privacy_note' => 'We gebruiken je gegevens alleen om op je bericht te reageren.',
        'copy' => 'Kopieer e-mailadres',
        'copied' => 'Gekopieerd',
    ],

    'error' => [
        '404_title' => 'Pagina niet gevonden',
        '404_text' => 'De pagina die je zoekt bestaat niet of is verplaatst.',
        '429_title' => 'Te veel verzoeken',
        '429_text' => 'Wacht even en probeer het opnieuw.',
        'home' => 'Terug naar de homepage',
    ],

    // The standard short bio (docs/AI-VISIBILITY.md): use it word for word everywhere.
    'founder' => [
        'bio' => 'Amir Jebbari is de oprichter van Jebbari, gevestigd in Amsterdam en Zwolle. Hij bouwt webapplicaties met Laravel, Vue en TypeScript.',
    ],
];
