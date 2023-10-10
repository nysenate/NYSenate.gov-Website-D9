<?php

namespace Drupal\nys_openleg_api;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generic response wrapper class for Openleg responses.
 */
abstract class ResponsePluginBase implements ResponsePluginInterface {

  /**
   * The JSON-decoded response object from Openleg.
   *
   * @var object
   */
  protected object $response;

  /**
   * Magic getter to provide direct access to response properties.
   */
  public function __get($name): mixed {
    return $this->response->$name ?? NULL;
  }

  /**
   * Constructor.
   */
  public function __construct(object $response = NULL) {
    if ($response) {
      $this->init($response);
    }
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static($configuration['response'] ?? NULL);
  }

  /**
   * {@inheritDoc}
   */
  public function success(): bool {
    return $this->response->success ?? FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function message(): string {
    return $this->response->message ?? '';
  }

  /**
   * {@inheritDoc}
   */
  public function type(): string {
    return $this->response->responseType ?? '';
  }

  /**
   * {@inheritDoc}
   */
  public function result(): object {
    return $this->response->result ?? (new \stdClass());
  }

  /**
   * {@inheritDoc}
   */
  public function init(object $response): void {
    $this->response = $response;
  }

}
