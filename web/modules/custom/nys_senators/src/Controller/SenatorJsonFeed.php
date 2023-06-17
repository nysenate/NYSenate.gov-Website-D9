<?php

namespace Drupal\nys_senators\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\nys_senators\Service\SenatorsJson;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for live access to senators' JSON feed.
 */
class SenatorJsonFeed extends ControllerBase {

  /**
   * NYS Senators JSON service.
   *
   * @var \Drupal\nys_senators\Service\SenatorsJson
   */
  protected SenatorsJson $senatorsJson;

  /**
   * Constructor.
   */
  public function __construct(SenatorsJson $senatorsJson) {
    $this->senatorsJson = $senatorsJson;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static($container->get('nys_senators.json_feed'));
  }

  /**
   * Returns the JSON feed as an HTTP response.
   *
   * @param string $shortname
   *   An optional senator shortname.  If available, only the matching
   *   senator will be included in the feed.
   */
  public function getFeed(string $shortname = SenatorsJson::NYS_SENATORS_JSON_ALL_SENATORS): JsonResponse {
    return $this->senatorsJson->getFeedJson($shortname);
  }

}
