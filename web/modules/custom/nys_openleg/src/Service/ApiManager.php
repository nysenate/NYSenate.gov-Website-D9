<?php

namespace Drupal\nys_openleg\Service;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\nys_openleg\Api\RequestPluginInterface;
use Drupal\nys_openleg\Api\ResponsePluginInterface;
use Drupal\nys_openleg\Api\Statute;

/**
 * Primary service for accessing Openleg API Request and Response managers.
 */
class ApiManager {

  /**
   * Preconfigured logging channel for Openleg API.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected LoggerChannel $logger;

  /**
   * Openleg API Request Manager service.
   *
   * @var \Drupal\nys_openleg\Service\ApiRequestManager
   */
  protected ApiRequestManager $requester;

  /**
   * Openleg API Response Manager service.
   *
   * @var \Drupal\nys_openleg\Service\ApiResponseManager
   */
  protected ApiResponseManager $responder;

  /**
   * Local cache for requester objects, keyed by item type.
   *
   * @var array
   */
  protected array $allRequesters = [];

  /**
   * Constructor.
   */
  public function __construct(LoggerChannel $logger, ApiRequestManager $requester, ApiResponseManager $responder) {
    $this->logger = $logger;
    $this->requester = $requester;
    $this->responder = $responder;
  }

  /**
   * Instantiates a requester.  Uses local cache to enforce singleton per type.
   *
   * @param string $item_type
   *   The plugin name.
   *
   * @return \Drupal\nys_openleg\Api\RequestPluginInterface|null
   *   The instantiated requester.
   */
  public function getRequest(string $item_type): ?RequestPluginInterface {
    if (!array_key_exists($item_type, $this->allRequesters)) {
      try {
        $ret = $this->requester->createInstance($item_type);
      }
      catch (\Throwable $e) {
        $this->logger->error('Failed to instantiate plugin @name (@type)', [
          '@name' => $item_type,
        ]);
        $ret = NULL;
      }
      $this->allRequesters[$item_type] = $ret;
    }
    return $this->allRequesters[$item_type];
  }

  /**
   * Gets an item object from Openleg.
   *
   * @param string $type
   *   The type of object, which must map to a request plugin name.
   * @param string $name
   *   The name of the resource (e.g., bill print number, transcript timestamp)
   * @param array $params
   *   Query string parameters to add to the API request.
   *
   * @return \Drupal\nys_openleg\Api\ResponsePluginInterface
   *   The Response object from Openleg.
   */
  public function get(string $type, string $name, array $params = []): ResponsePluginInterface {
    return $this->getRequest($type)->setParams($params)->retrieve($name);
  }

  /**
   * Gets a list of updates from Openleg.
   *
   * @param string $type
   *   The type of object, which must map to a request plugin name.
   * @param mixed $time_from
   *   A timestamp, as epoch time, or parsable by strtotime()
   * @param mixed $time_to
   *   A timestamp, as epoch time, or parsable by strtotime()
   * @param array $params
   *   Query string parameters to add to the API request.
   *
   * @return \Drupal\nys_openleg\Api\ResponsePluginInterface
   *   The Response object from Openleg.
   */
  public function listUpdates(string $type, $time_from, $time_to, array $params = []): ResponsePluginInterface {
    return $this->getRequest($type)
      ->setParams($params)
      ->retrieveUpdates($time_from, $time_to);
  }

  /**
   * Searches Openleg for resources by type and keyword.
   *
   * @param string $type
   *   The type of object, which must map to a request plugin name.
   * @param string $term
   *   The search term.
   * @param array $params
   *   Query string parameters to add to the API request.
   *
   * @return \Drupal\nys_openleg\Api\ResponsePluginInterface
   *   Plugin-dependent, but should be either a ResponseSearch or
   *   ResponseGeneric object.
   */
  public function getSearch(string $type, string $term, array $params = []): ResponsePluginInterface {
    return $this->getRequest($type)
      ->setParams($params)
      ->retrieveSearch($term);
  }

  /**
   * Gets an Statute document and associated tree from Openleg.
   *
   * @param string $book
   *   The law book to retrieve.
   * @param string $location
   *   The unique location within the law book.
   * @param string $history
   *   An optional history marker to retrieve the law as it was in the past.
   *
   * @return object
   *   An object with two properties, 'detail' and 'tree', with each being
   *   a response plugin object.
   */
  public function getStatuteFull(string $book, string $location, string $history = ''): object {
    $param = ['history' => $history];

    /** @var \Drupal\nys_openleg\Plugin\OpenlegApi\Response\StatuteDetail $detail */
    $detail = $this->get('statute_item', $book . '/' . $location, $param);

    /** @var \Drupal\nys_openleg\Plugin\OpenlegApi\Response\StatuteTree $tree */
    $tree = $this->get('statute_item', $book, $param + ['location' => $location]);

    return new Statute($detail, $tree);
  }

}
