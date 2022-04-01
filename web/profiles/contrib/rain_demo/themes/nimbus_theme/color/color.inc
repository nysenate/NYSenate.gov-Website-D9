<?php

/**
 * @file
 * Lists available colors and color schemes for the University theme.
 */

$info = [
  // Available colors and color labels used in theme.
  'fields' => [
    'top' => t('Header, Footer background'),
    'accent' => t('Accent color'),
    'link' => t('Link color'),
    'text' => t('Text color'),
    'base' => t('Base color'),
  ],
  // Pre-defined color schemes.
  'schemes' => [
    'default' => [
      'title' => t('Default'),
      'colors' => [
        'top' => '#000001',
        'accent' => '#1b2b34',
        'base' => '#ffffff',
        'link' => '#000002',
        'text' => '#000000',
      ],
    ],
    'firehouse' => [
      'title' => t('Firehouse'),
      'colors' => [
        'top' => '#888888',
        'accent' => '#888888',
        'base' => '#ffffff',
        'link' => '#d6121f',
        'text' => '#000000',
      ],
    ],
    'ice' => [
      'title' => t('Ice'),
      'colors' => [
        'top' => '#333333',
        'accent' => '#333333',
        'base' => '#ffffff',
        'link' => '#019dbf',
        'text' => '#000000',
      ],
    ],
    'plum' => [
      'title' => t('Plum'),
      'colors' => [
        'top' => '#4c1c58',
        'accent' => '#4c1c58',
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
        'accent' => '#4a4a4a',
        'base' => '#ffffff',
        'link' => '#0073b6',
        'text' => '#000000',
      ],
    ],
  ],

  // CSS files (excluding @import) to rewrite with new color scheme.
  'css' => [
    'dist/css/accordion.css',
    'dist/css/article-card.css',
    'dist/css/blog-card.css',
    'dist/css/button.css',
    'dist/css/card.css',
    'dist/css/card-list.css',
    'dist/css/content-card.css',
    'dist/css/content-carousel.css',
    'dist/css/embed.css',
    'dist/css/event-card.css',
    'dist/css/event-carousel.css',
    'dist/css/featured-card.css',
    'dist/css/featured-item.css',
    'dist/css/form.css',
    'dist/css/heading.css',
    'dist/css/hero.css',
    'dist/css/icons.css',
    'dist/css/latest-news.css',
    'dist/css/main-menu.css',
    'dist/css/menu.css',
    'dist/css/news-card.css',
    'dist/css/news-teaser.css',
    'dist/css/news-card.css',
    'dist/css/page-layout.css',
    'dist/css/product-card.css',
    'dist/css/quote.css',
    'dist/css/quote-card.css',
    'dist/css/quote-carousel.css',
    'dist/css/search-api-results.css',
    'dist/css/search-box.css',
    'dist/css/site-branding.css',
    'dist/css/site-footer.css',
    'dist/css/site-header.css',
    'dist/css/user-account-nav.css',
  ],

  // Files to copy.
  'copy' => [
    'logo.svg',
  ],
];
