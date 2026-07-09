<?php

namespace Drupal\nys_feeds;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Interface for NYS Feeds plugins.
 */
interface NysFeedPluginInterface extends ContainerFactoryPluginInterface {

  /**
   * Compiles the array-based structure of objects for a single feed request.
   */
  public function getFeed(FeedState $state): FeedState;

}
