<?php

namespace Drupal\nys_sage\Sage\Responses;

use Drupal\nys_sage\Sage\Response;

/**
 * Response class for SAGE district:assign method.
 */
class DistrictAssignResponse extends Response {

  /**
   * Determines if a match was acceptable.
   *
   * Aside from being a successful response, acceptable means:
   *  - uspsValidated is TRUE, or
   *  - matchLevel is 'HOUSE'
   *
   * @return bool
   *   If the match is acceptable.
   */
  public function isMatchAcceptable(): bool {
    return ($this->response->status == 'SUCCESS' && (
      $this->response->uspsValidated
      || $this->response->matchLevel == 'HOUSE'
      ));
  }

  /**
   * {@inheritdoc}
   */
  public function getShortResponse() {
    return [
      'district' => $this->response->districts->senate->district ?? 0,
      'matchLevel' => $this->response->matchLevel ?? '',
      'isValidated' => $this->response->uspsValidated ?? FALSE,
    ];
  }

  /**
   * Convenience function to get senator district.
   */
  public function getSenatorDistrict(): int {
    return (int) ($this->response->districts->senate->district ?? 0);
  }

}
