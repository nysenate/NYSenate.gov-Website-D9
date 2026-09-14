<?php

namespace Drupal\nys_feeds\Plugin\NysFeed;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\nys_feeds\Attribute\NysFeed;
use Drupal\nys_feeds\FeedOutput;
use Drupal\nys_feeds\NysFeedPluginBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * NYS Feeds plugin to handle responses for internal exceptions.
 *
 * This expects the exception to be passed in as $configuration['exception'].
 */
#[NysFeed(
  label: new TranslatableMarkup("Feed Error"),
  description: new TranslatableMarkup("Internal only. Simulates a feed interface to return an error."),
  cacheable: FALSE,
  private: TRUE,
  id: "feed_error",
)]
class FeedError extends NysFeedPluginBase {

  /**
   * {@inheritDoc}
   */
  public function getFeed(): FeedOutput {
    $ret = new FeedOutput();
    $ret->status = "error";
    $ret->messages = [
      'exception' => $this->configuration['exception']?->getMessage() ?? "Unknown error",
    ];
    $ret->statusCode = 500;
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse(FeedOutput $output): JsonResponse {
    $ret = parent::getResponse($output);
    $ret->setStatusCode(500, "An error occurred while loading a feed");
    return $ret;
  }

}
