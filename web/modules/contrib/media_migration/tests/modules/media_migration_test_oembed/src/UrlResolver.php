<?php

namespace Drupal\media_migration_test_oembed;

use Drupal\media\OEmbed\UrlResolver as BaseUrlResolver;

/**
 * Simple oEmbed URL resolver replacement for remote media migration tests.
 */
class UrlResolver extends BaseUrlResolver {

  /**
   * {@inheritdoc}
   */
  public function getResourceUrl($url, $max_width = NULL, $max_height = NULL) {
    return $url;
  }

}
