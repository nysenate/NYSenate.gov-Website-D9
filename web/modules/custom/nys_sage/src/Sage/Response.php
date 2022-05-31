<?php

namespace Drupal\nys_sage\Sage;

/**
 * Generic response class.
 */
abstract class Response {

  /**
   * The original response, decoded.
   *
   * @var object
   */
  protected object $response;

  /**
   * Constructor.
   *
   * @param string $curl_response
   *   Expected to be a valid JSON string.
   */
  public function __construct(string $curl_response = '') {
    if ($curl_response) {
      $this->setResponse(json_decode($curl_response));
    }
  }

  /**
   * Magic getter is wired to response properties.
   */
  public function __get($name) {
    return $this->response->{$name} ?? NULL;
  }

  /**
   * Getter for the response.
   */
  public function getResponse(): object {
    return $this->response ?? ((object) ['status' => 'JSON_ENCODING_ERROR']);
  }

  /**
   * Setter for the response.
   */
  public function setResponse($response = NULL): void {
    $this->response = $response ?? ((object) ['status' => 'JSON_ENCODING_ERROR']);
    $this->init();
  }

  /**
   * Generates the "short response" content for a log entry.
   */
  public function getShortResponse() {
    return '';
  }

  /**
   * Customizations for a specific response type.
   */
  protected function init() {
  }

}
