<?php

namespace Drupal\nys_feeds;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Interface for NYS Feeds plugins.
 */
interface NysFeedPluginInterface extends ContainerFactoryPluginInterface {

  /**
   * Compiles the array-based structure of objects for a single feed request.
   */
  public function getFeed(): FeedOutput;

  /**
   * Generates an appropriate response object.
   */
  public function getResponse(FeedOutput $output): JsonResponse;

}
