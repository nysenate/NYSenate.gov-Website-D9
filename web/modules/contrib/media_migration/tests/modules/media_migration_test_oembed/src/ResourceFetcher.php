<?php

namespace Drupal\media_migration_test_oembed;

use Drupal\Component\Utility\Crypt;
use Drupal\media\OEmbed\ResourceException;
use Drupal\media\OEmbed\ResourceFetcher as BaseResourceFetcher;
use Drupal\media\OEmbed\Resource;

/**
 * Simple oEmbed resource fetcher replacement for remote media migration tests.
 */
class ResourceFetcher extends BaseResourceFetcher {

  /**
   * {@inheritdoc}
   */
  public function fetchResource($oembed_url) {
    $hashed = Crypt::hashBase64($oembed_url);
    $resource_array = \Drupal::state()->get("media_migration_test_oembed.$hashed", [
      'type' => 'video',
      'html' => urlencode($oembed_url),
      'width' => 320,
      'height' => 180,
    ]);
    $type = $resource_array['type'];
    unset($resource_array['type']);
    try {
      $resource = call_user_func_array([Resource::class, $type], $resource_array);
    }
    catch (\Exception $e) {
      throw new ResourceException(sprintf('Test media oembed resource %s cannot be fetched', $oembed_url), $oembed_url);
    }
    assert($resource instanceof Resource);
    return $resource;
  }

}
