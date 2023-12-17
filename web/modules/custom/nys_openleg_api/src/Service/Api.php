<?php

namespace Drupal\nys_openleg_api\Service;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\nys_openleg_api\Plugin\OpenlegApi\Response\StatuteDetail;
use Drupal\nys_openleg_api\Request;
use Drupal\nys_openleg_api\RequestPluginInterface;
use Drupal\nys_openleg_api\ResponsePluginInterface;
use Drupal\nys_openleg_api\Statute;
use Psr\Log\LoggerAwareTrait;

/**
 * Primary service for accessing Openleg API Request and Response managers.
 */
class Api {

  use LoggerAwareTrait;

  /**
   * Config object for nys_openleg_api.settings.
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
   * Gets a request plugin by type.
   */
  public function getRequest(string $type): ?RequestPluginInterface {
    return $this->requester->resolve($type);
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
    $this->logger->info('Requesting for "@name" (type: "@type")', [
      '@name' => $name,
      '@type' => $type,
      '@params' => $params,
    ]);
    return $this->getRequest($type)?->setParams($params)->retrieve($name);
  }

  /**
   * Wrapper around getJson() to get a Response plugin object.
   *
   * @return \Drupal\nys_openleg_api\ResponsePluginInterface
   *   The Response object, a contrived Error response, or NULL on failure.
   */
  public function get(string $type, string $name, array $params = []): ResponsePluginInterface {
    return $this->responder->resolve($this->getJson($type, $name, $params));
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
    $request = $this->getRequest($type)?->setParams($params);
    $this->logger->notice('Requesting "@type" updates (from: @from, to: @to)', [
      '@type' => $type,
      '@from' => $time_from,
      '@to' => $time_to,
    ]);
    return $this->responder->resolve($request?->retrieveUpdates($time_from, $time_to));
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
    $request = $this->getRequest($type)?->setParams($params);
    $this->logger->info('Requesting "@type" search (term: @term)', [
      '@type' => $type,
      '@term' => $term,
    ]);
    return $this->responder->resolve($request?->retrieveSearch($term));
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

    $this->logger->info('Requesting statute, book: "@book", location: "@location"', [
      '@book' => $book,
      '@location' => $location,
    ]);
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
