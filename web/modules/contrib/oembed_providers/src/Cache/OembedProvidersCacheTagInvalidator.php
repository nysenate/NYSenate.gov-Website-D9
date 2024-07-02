<?php

namespace Drupal\oembed_providers\Cache;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\media\MediaSourceManager;

/**
 * Intercepts requests for cache tag invalidation.
 */
class OembedProvidersCacheTagInvalidator implements CacheTagsInvalidatorInterface {

  /**
   * Manages media source plugins.
   *
   * @var \Drupal\media\MediaSourceManager
   */
  protected $mediaSourceManager;

  /**
   * OembedProvidersCacheTagInvalidator constructor.
   *
   * @param \Drupal\media\MediaSourceManager $mediaSourceManager
   *   Manages media source plugins.
   */
  public function __construct(MediaSourceManager $mediaSourceManager) {
    $this->mediaSourceManager = $mediaSourceManager;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    // Whenever our settings change, clear media source plugin definitions so
    // \oembed_providers_media_source_info_alter() may be re-executed.
    if (in_array('config:oembed_providers.settings', $tags, TRUE)) {
      $this->mediaSourceManager->clearCachedDefinitions();
    }
  }

}
