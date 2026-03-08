<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    'react' => [
        'version' => '19.2.3',
    ],
    'react-dom' => [
        'version' => '19.2.3',
    ],
    'react-dom/client' => [
        'version' => '19.2.3',
    ],
    'scheduler' => [
        'version' => '0.27.0',
    ],
    'mobile/MobileAppInitializer' => [
        'path' => 'mobile/MobileAppInitializer.js',
        'entrypoint' => true,
    ],
    'mobile/LeagueTrackerMobileApp' => [
        'path' => 'mobile/LeagueTrackerMobileApp.js',
    ],
];
