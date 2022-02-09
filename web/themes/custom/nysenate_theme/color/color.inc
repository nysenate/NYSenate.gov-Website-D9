<?php

/**
 * @file
 * Lists available colors and color schemes for the collective theme.
 */

$info = [
  // Available colors and color labels used in theme.
  'fields' => [
    'top' => t('Header background top'),
    'link' => t('Link color'),
    'text' => t('Text color'),
    'base' => t('Base color'),
  ],
  // Pre-defined color schemes.
  'schemes' => [
    'default' => [
      'title' => t('Default'),
      'colors' => [
        'top' => '#404040',
        'base' => '#ffffff',
        'link' => '#003954',
        'text' => '#000000',
      ],
    ],
    'firehouse' => [
      'title' => t('Firehouse'),
      'colors' => [
        'top' => '#888888',
        'base' => '#ffffff',
        'link' => '#d6121f',
        'text' => '#000000',
      ],
    ],
    'ice' => [
      'title' => t('Ice'),
      'colors' => [
        'top' => '#333333',
        'base' => '#ffffff',
        'link' => '#019dbf',
        'text' => '#000000',
      ],
    ],
    'plum' => [
      'title' => t('Plum'),
      'colors' => [
        'top' => '#4c1c58',
        'base' => '#fffdf7',
        'secondary' => '#301313',
        'link' => '#9d408d',
        'text' => '#000000',
      ],
    ],
    'slate' => [
      'title' => t('Slate'),
      'colors' => [
        'top' => '#4a4a4a',
        'base' => '#ffffff',
        'link' => '#0073b6',
        'text' => '#000000',
      ],
    ],
  ],

  // CSS files (excluding @import) to rewrite with new color scheme.
  'css' => [
    'dist/css/accordion.css',
    'dist/css/breadcrumb.css',
    'dist/css/button.css',
    'dist/css/card.css',
    'dist/css/global.css',
    'dist/css/heading.css',
    'dist/css/main-menu.css',
    'dist/css/search-box.css',
    'dist/css/site-footer.css',
    'dist/css/user-account-nav.css',
  ],

  // Files to copy.
  'copy' => [
    'logo.svg',
  ],
];
