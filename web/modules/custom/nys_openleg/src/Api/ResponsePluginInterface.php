<?php

namespace Drupal\nys_openleg\Api;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Interface for Openleg Response objects.
 */
interface ResponsePluginInterface extends ContainerFactoryPluginInterface {

  /**
   * If the request generating this response was a success.
   */
  public function success(): bool;

  /**
   * Returns the response's message field.
   */
  public function message(): string;

  /**
   * Returns the response's responseType field.
   */
  public function type(): string;

  /**
   * Returns the response's result object.
   */
  public function result(): object;

  /**
   * Initializes the Response object.
   */
  public function init(object $response);

}
