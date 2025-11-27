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
    'jquery' => [
        'version' => '3.6.0',
    ],
    'bootstrap' => [
        'version' => '5.1.3',
    ],
    'here-maps-core' => [
        'path' => 'https://js.api.here.com/v3/3.0/mapsjs-core.js',
    ],
    'here-maps-service' => [
        'path' => 'https://js.api.here.com/v3/3.0/mapsjs-service.js',
    ],
    'here-maps-ui' => [
        'path' => 'https://js.api.here.com/v3/3.0/mapsjs-ui.js',
    ],
    'here-maps-mapevents' => [
        'path' => 'https://js.api.here.com/v3/3.0/mapsjs-mapevents.js',
    ],
    'here' => [
        'path' => './assets/js/here.js',
    ],
    'scripts' => [
        'path' => './assets/js/scripts.js',
    ],
    'spectrum' => [
        'path' => './assets/js/spectrum.js',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/turbo' => [
        'version' => '7.3.0',
    ],
];
