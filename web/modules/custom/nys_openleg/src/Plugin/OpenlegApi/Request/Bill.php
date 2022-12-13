<?php

namespace Drupal\nys_openleg\Plugin\OpenlegApi\Request;

use Drupal\nys_openleg\Api\RequestPluginBase;
use Drupal\nys_openleg\Api\ResponsePluginBase;

/**
 * Wrapper around ApiRequest for requesting a bill or resolution.
 *
 * @OpenlegApiRequest(
 *   id = "bill",
 *   label = @Translation("Bills and Resolutions"),
 *   description = @Translation("Openleg API Request plugin"),
 *   endpoint = "bills"
 * )
 */
class Bill extends RequestPluginBase {

  /**
   * {@inheritDoc}
   */
  public function retrieve(string $name, $params = []): ResponsePluginBase {
    return parent::retrieve($this->normalizeName($name), $params);
  }

  /**
   * Normalizes a bill name to "<year>/<print>".
   */
  protected function normalizeName(string $name): string {
    return str_replace('-', '/', $name);
  }

}
