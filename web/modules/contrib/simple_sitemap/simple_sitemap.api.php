<?php

/**
 * @file
 * Hooks provided by the Simple XML Sitemap module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the generated link data before the sitemap is saved.
 *
 * This hook gets invoked for every sitemap chunk generated.
 *
 * @param array &$links
 *   Array containing multilingual links generated for each path to be indexed.
 * @param \Drupal\simple_sitemap\Entity\SimpleSitemapInterface $sitemap
 *   Sitemap entity.
 */
function hook_simple_sitemap_links_alter(array &$links, \Drupal\simple_sitemap\Entity\SimpleSitemapInterface $sitemap) {

  // Remove German URL for a certain path in the hreflang sitemap.
  foreach ($links as $key => $link) {
    if ($link['meta']['path'] === 'node/1') {

      // Remove 'loc' URL if it points to a german site.
      if ($link['langcode'] === 'de') {
        unset($links[$key]);
      }

      // If this 'loc' URL points to a non-german site, make sure to remove
      // its german alternate URL.
      else {
        unset($links[$key]['alternate_urls']['de']);
      }
    }
  }
}

/**
 * Add arbitrary links to the sitemap.
 *
 * @param array &$arbitrary_links
 *   An array of arbitrary links.
 * @param \Drupal\simple_sitemap\Entity\SimpleSitemapInterface $sitemap
 *   Sitemap entity.
 */
function hook_simple_sitemap_arbitrary_links_alter(array &$arbitrary_links, \Drupal\simple_sitemap\Entity\SimpleSitemapInterface $sitemap) {

  // Add an arbitrary link to all sitemaps.
  $arbitrary_links[] = [
    'url' => 'https://some-arbitrary-link/',
    'priority' => '0.5',

    // An ISO8601 formatted date.
    'lastmod' => '2012-10-12T17:40:30+02:00',

    'changefreq' => 'weekly',
    'images' => [
      ['path' => 'https://path-to-image.png'],
    ],

    // Add alternate URLs for every language of a multilingual site.
    // Not necessary for monolingual sites.
    'alternate_urls' => [
      'en' => 'https://this-is-your-life.net/de/tyler',
      'de' => 'https://this-is-your-life.net/en/tyler',
    ],
  ];

  // Add an arbitrary link to the 'fight_club' sitemap variant only.
  switch ($sitemap->id()) {
    case 'fight_club':
      $arbitrary_links[] = [
        'url' => 'https://this-is-your-life.net/tyler',
      ];
      break;
  }
}

/**
 * Alters the sitemap attributes shortly before XML document generation.
 *
 * Attributes can be added, changed and removed.
 *
 * @param array &$attributes
 *   An array of attributes.
 * @param \Drupal\simple_sitemap\Entity\SimpleSitemapInterface $sitemap
 *   Sitemap entity.
 */
function hook_simple_sitemap_attributes_alter(array &$attributes, \Drupal\simple_sitemap\Entity\SimpleSitemapInterface $sitemap) {

  // Remove the xhtml attribute e.g. if no xhtml sitemap elements are present.
  unset($attributes['xmlns:xhtml']);
}

/**
 * Alters attributes of the sitemap index before XML document generation.
 *
 * Attributes can be added, changed and removed.
 *
 * @param array &$index_attributes
 *   An array of attributes.
 * @param \Drupal\simple_sitemap\Entity\SimpleSitemapInterface $sitemap
 *   Sitemap entity.
 */
function hook_simple_sitemap_index_attributes_alter(array &$index_attributes, \Drupal\simple_sitemap\Entity\SimpleSitemapInterface $sitemap) {

  // Add some attribute to the sitemap index.
  $index_attributes['name'] = 'value';
}

/**
 * Alter properties of and remove URL generator plugins.
 *
 * @param array $url_generators
 *   Array of URL generators.
 */
function hook_simple_sitemap_url_generators_alter(array &$url_generators) {

  // Remove the entity generator.
  unset($url_generators['entity']);
}

/**
 * Alter properties of and remove sitemap generator plugins.
 *
 * @param array $sitemap_generators
 *   Array of sitemap generators.
 */
function hook_simple_sitemap_sitemap_generators_alter(array &$sitemap_generators) {

  // Remove the default generator.
  unset($sitemap_generators['default']);
}

/**
 * @} End of "addtogroup hooks".
 */
