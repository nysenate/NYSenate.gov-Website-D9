<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Request;

use Drupal\nys_openleg_api\RequestPluginBase;

/**
 * Openleg API Request plugin for Bills.
 *
 * @OpenlegApiRequestNew(
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
  public function retrieve(string $name, $params = []): ?object {
    return parent::retrieve($this->normalizeName($name), $params);
  }

  /**
   * Normalizes a bill name to "<year>/<print>".
   */
  protected function normalizeName(string $name): string {
    return str_replace('-', '/', $name);
  }

}
