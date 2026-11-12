<?php

/*
 * Interface text only: buttons, labels, accessibility text, form messages and
 * empty states. Page content (headlines, descriptions, services, projects,
 * articles, legal text) lives in the CMS, never here.
 */
return [
    'skip' => 'Skip to content',
    'descriptor' => 'Digital studio',
    'tagline' => 'Software, done properly.',
    'meta_description' => 'Jebbari is a digital studio in Zwolle and Amsterdam. We design and build websites and web applications for Dutch businesses.',
    'breadcrumb' => 'Breadcrumb',

    'live' => [
        'title' => 'Live status',
        'all_ok' => 'All systems online',
        'degraded' => 'Disruption measured',
        'measuring' => 'Measuring…',
        'note' => 'Really measured, from your browser.',
        'site' => 'Website',
        'pages' => 'Pages',
        'cms' => 'Admin',
        'ms' => 'ms',
    ],
    'marquee' => [
        'label' => 'Technology',
    ],
    'theme' => [
        'toggle' => 'Switch between light and dark',
        'light' => 'Light',
        'dark' => 'Dark',
    ],
    'hscroll' => [
        'progress' => 'Progress through the projects',
    ],
    'status' => [
        'open' => 'Taking on projects',
        'closed' => 'Fully booked',
    ],

    'nav' => [
        'label' => 'Main',
        'home' => 'Home',
        'work' => 'Work',
        'services' => 'Services',
        'about' => 'About',
        'blog' => 'Blog',
        'contact' => 'Contact',
        'menu' => 'Menu',
        'close' => 'Close',
        'language' => 'Language',
    ],

    'footer' => [
        'time' => 'Zwolle · Amsterdam, NL',
        'explore' => 'Explore',
        'contact' => 'Contact',
        'follow' => 'Elsewhere',
        'email' => 'Email',
        'privacy' => 'Privacy',
        'kvk' => 'KvK',
        'btw' => 'VAT',
    ],

    'cta' => [
        'view_case' => 'View case',
        'read_more' => 'Read more',
        'all_work' => 'All work',
        'about' => 'More about me',
        'contact' => 'Get in touch',
        'book' => 'Book a call',
    ],

    'field' => ['optional' => 'optional'],

    'empty' => [
        'work' => 'There are no projects to show yet.',
        'blog' => 'There are no articles yet.',
    ],

    'work' => ['all' => 'All work'],

    'project' => [
        'category' => 'Category',
        'year' => 'Year',
        'client' => 'Client',
        'stack' => 'Technology',
        'visit' => 'Visit',
        'brief' => 'The brief',
        'built' => 'What was built',
        'outcome' => 'Outcome',
        'overview' => 'Overview',
        'objectives' => 'Objectives',
        'features' => 'Features and implementation',
        'interface' => 'Interface',
        'next' => 'Next project',
        'gallery' => 'Gallery',
        'related' => 'Related work',
        'cta' => 'Have something similar in mind?',
    ],

    'service' => [
        'benefits' => 'Benefits',
        'included' => 'What you get',
        'not_included' => 'What we do not do',
        'timeline' => 'Typical timeline',
        'technology' => 'Technology',
        'other' => 'Other services',
        'cta' => 'Let’s talk about what you need.',
    ],

    'packages' => [
        'label' => 'Packages',
        'from' => 'From',
        'on_request' => 'On request',
    ],

    'blog' => [
        'articles' => 'Articles',
        'categories' => 'Categories',
        'all' => 'All',
        'pagination' => 'Pagination',
        'newer' => 'Newer',
        'older' => 'Older',
        'related' => 'Keep reading',
        'cta' => 'Want to talk it through?',
        'reading' => '{1} :count minute read|[2,*] :count minute read',
    ],

    'legal' => ['updated' => 'Last updated'],

    'preview' => [
        'label' => 'Preview, not public',
        'draft' => 'Draft, not published',
        'live' => 'Published',
        'live_changes' => 'Published, with unpublished changes',
        'scheduled' => 'Scheduled',
    ],

    'contact' => [
        'label' => 'Contact',
        'name' => 'Name',
        'email' => 'Email',
        'company' => 'Company',
        'budget' => 'Budget',
        'budget_choose' => 'Choose a range',
        'budget_unsure' => 'Not sure',
        'message' => 'What do you want to build?',
        'attr_name' => 'your name',
        'attr_email' => 'your email address',
        'attr_company' => 'your company',
        'attr_budget' => 'your budget',
        'attr_message' => 'your message',
        'send' => 'Send message',
        'sent_title' => 'Thank you. Your message has arrived.',
        'sent_text' => 'We will reply within one working day.',
        'fix_errors' => 'Please check the highlighted fields.',
        'too_fast' => 'That was very quick. Please check your message and send it again.',
        'leave_empty' => 'Leave this field empty',
        'privacy_note' => 'We use your details only to reply to your message.',
        'copy' => 'Copy email address',
        'copied' => 'Copied',
    ],

    'error' => [
        '404_title' => 'Page not found',
        '404_text' => 'The page you are looking for does not exist or has moved.',
        '429_title' => 'Too many requests',
        '429_text' => 'Please wait a moment and try again.',
        'home' => 'Back to the homepage',
    ],

    // The standard short bio (docs/AI-VISIBILITY.md): use it word for word everywhere.
    // /llms.txt is always written in English, so these lines exist only here.
    'llms' => [
        'pages' => 'Main pages',
        'about_nl' => 'Over mij',
        'about_note' => 'who Amir Jebbari is and what he does (Dutch)',
        'about_en' => 'About',
        'about_en_note' => 'the same page in English',
        'work' => 'Work',
        'work_note' => 'projects built by Jebbari',
        'services' => 'Services',
        'blog' => 'Blog',
        'contact' => 'Contact',
        'projects' => 'Projects',
        'related' => 'Related sites and profiles',
    ],

    'founder' => [
        'bio' => 'Amir Jebbari is the founder of Jebbari, based in Amsterdam and Zwolle, the Netherlands. He builds web applications with Laravel, Vue and TypeScript.',
    ],
];
