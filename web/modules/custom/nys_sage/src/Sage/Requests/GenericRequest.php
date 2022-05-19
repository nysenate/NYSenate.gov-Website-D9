<?php

namespace Drupal\nys_sage\Sage\Requests;

use Drupal\nys_sage\Sage\Request;

/**
 * Represents a generic request.
 */
class GenericRequest extends Request {

  /**
   * Sets the group.  Chainable.
   */
  public function setGroup(string $group): GenericRequest {
    $this->group = $group;
    return $this;
  }

  /**
   * Sets the method.  Chainable.
   */
  public function setMethod(string $method): GenericRequest {
    $this->method = $method;
    return $this;
  }

}
