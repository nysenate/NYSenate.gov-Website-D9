<?php

namespace Drupal\nys_openleg_api\Service;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\nys_openleg_api\Plugin\OpenlegApi\Response\StatuteDetail;
use Drupal\nys_openleg_api\Request;
use Drupal\nys_openleg_api\RequestPluginInterface;
use Drupal\nys_openleg_api\ResponsePluginInterface;
use Drupal\nys_openleg_api\Statute;

/**
 * Primary service for accessing Openleg API Request and Response managers.
 */
class Api {

  /**
   * Preconfigured logging channel for Openleg API.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected LoggerChannel $logger;

  /**
   * Config object for nys_openleg.settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * Openleg API Request Manager service.
   *
   * @var \Drupal\nys_openleg_api\Service\RequestManager
   */
  protected RequestManager $requester;

  /**
   * Openleg API Response Manager service.
   *
   * @var \Drupal\nys_openleg_api\Service\ResponseManager
   */
  protected ResponseManager $responder;

  /**
   * Local cache for requesters, to enforce singletons.  Keyed by plugin ID.
   *
   * @var array
   */
  protected array $allRequesters = [];

  /**
   * Constructor.
   */
  public function __construct(LoggerChannel $logger, ImmutableConfig $config, RequestManager $requester, ResponseManager $responder) {
    $this->logger = $logger;
    $this->requester = $requester;
    $this->responder = $responder;
    $this->config = $config;
    Request::useKey($this->config->get('api_key') ?? '');
  }

  /**
   * Instantiates a requester.  Uses local cache to enforce singleton per type.
   *
   * @param string $item_type
   *   The plugin name.
   *
   * @return \Drupal\nys_openleg_api\RequestPluginInterface|null
   *   The instantiated requester, or NULL on error.
   */
  protected function resolveRequest(string $item_type): ?RequestPluginInterface {
    if (!($this->allRequesters[$item_type] ?? NULL)) {
      try {
        $ret = $this->requester->createInstance($item_type);
      }
      catch (PluginException | PluginNotFoundException $e) {
        $this->logger->error(
          'Could not instantiate request plugin "@name"',
          ['@name' => $item_type, '@msg' => $e->getMessage()]
        );
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
   * @return object|null
   *   The decoded JSON object, or NULL on failure.
   */
  public function getJson(string $type, string $name, array $params = []): object|null {
    return $this->resolveRequest($type)?->setParams($params)->retrieve($name);
  }

  /**
   * Wrapper around getJson() to get a Response plugin object.
   *
   * @return \Drupal\nys_openleg_api\ResponsePluginInterface
   *   The Response object, a contrived Error response, or NULL on failure.
   */
  public function get(string $type, string $name, array $params = []): ResponsePluginInterface {
    return $this->responder->resolveResponse($this->getJson($type, $name, $params));
  }

  /**
   * Gets a list of updates from Openleg as a Response plugin.
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
   * @return \Drupal\nys_openleg_api\ResponsePluginInterface
   *   The Response object from Openleg.
   */
  public function getUpdates(string $type, mixed $time_from, mixed $time_to, array $params = []): ResponsePluginInterface {
    $request = $this->resolveRequest($type)?->setParams($params);
    return $this->responder->resolveResponse($request?->retrieveUpdates($time_from, $time_to));
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
   * @return \Drupal\nys_openleg_api\ResponsePluginInterface
   *   The Response object from Openleg.
   */
  public function getSearch(string $type, string $term, array $params = []): ResponsePluginInterface {
    $request = $this->resolveRequest($type)?->setParams($params);
    return $this->responder->resolveResponse($request?->retrieveSearch($term));
  }

  /**
   * Gets a Statute document and associated tree from Openleg.
   *
   * @param string $book
   *   The law book to retrieve.
   * @param string $location
   *   The unique location within the law book.
   * @param string $history
   *   An optional history marker to retrieve the law as it was in the past.
   *
   * @return \Drupal\nys_openleg_api\Statute
   *   An object with two properties, 'detail' and 'tree', with each being
   *   a response plugin object.
   */
  public function getStatute(string $book, string $location, string $history = ''): Statute {
    $param = ['history' => $history];

    /** @var \Drupal\nys_openleg_api\Plugin\OpenlegApi\Response\StatuteTree $tree */
    $tree = $this->get('statute', $book, $param + ['location' => $location]);

    // Enforce use of the returned location, if available.
    if (!$location && $tree->success()) {
      $location = $tree->location();
    }

    // If location is empty, there will be no detail.
    $detail = $this->get('statute', $book . '/' . $location, $param);
    if (!($detail instanceof StatuteDetail)) {
      $detail = NULL;
    }

    return new Statute($tree, $detail);
  }

  /**
   * Gets a session transcript.
   */
  public function getTranscript(string $name): ResponsePluginInterface {
    return $this->get('floor_transcript', $name);
  }

  /**
   * Gets a calendar document, identified by session year and number.
   */
  public function getCalendar(string $year, string $number): ResponsePluginInterface {
    return $this->get('calendar', "$year/$number", ['full' => TRUE]);
  }

  /**
   * Gets a Public Hearing Transcript.  Name is the filename or hearing number.
   */
  public function getHearing(string $name): ResponsePluginInterface {
    return $this->get('hearing', $name);
  }

  /**
   * Gets an agenda, identified by session year and number.
   */
  public function getAgenda(string $year, string $number): ResponsePluginInterface {
    return $this->get('agenda', "$year/$number");
  }

  /**
   * Gets a bill or resolution, identified by session and print number.
   */
  public function getBill(string $session, string $number): ResponsePluginInterface {
    return $this->get('bill', "$session/$number");
  }

}
