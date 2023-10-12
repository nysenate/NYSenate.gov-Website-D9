<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Response;

use Drupal\nys_openleg_api\ResponsePluginBase;

/**
 * Openleg API Response plugin for an error response.
 *
 * Error responses have the standard properties for success, responseType, and
 * message.  The success property is enforced to FALSE here.  The responseType
 * property _should_ be the string 'error', but can be different if the response
 * is instantiated as a fallback.  API error responses will include an errorCode
 * property, which should yield an integer (as a string).  Additionally, they
 * _may_ have properties for errorDataType (string) and errorData (object).  The
 * contents of these properties are unique to the request.
 *
 * @OpenlegApiResponse(
 *   id = "error",
 *   label = @Translation("Error Response"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class Error extends ResponsePluginBase {

  /**
   * {@inheritDoc}
   *
   * Errors are never successful.
   */
  public function success(): bool {
    return FALSE;
  }

  /**
   * Gets the error code property - an integer as a string.
   */
  public function errorCode(): string {
    return $this->result()->errorCode ?? '';
  }

  /**
   * Gets the error code property.
   */
  public function errorData(): string {
    return $this->result()->errorData ?? '';
  }

  /**
   * Gets the errorData property.
   *
   * @return object|null
   *   NULL will be returned if the property does not exist.
   */
  public function errorDataType(): ?object {
    return $this->result()->errorDataType ?? NULL;
  }

}
