<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Response;

/**
 * Openleg API Response plugin for a list of public hearing transcript updates.
 *
 * @OpenlegApiResponseNew(
 *   id = "public_hearing-update-token list",
 *   label = @Translation("Public Hearing Update List"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class PublicHearingUpdateList extends ResponseUpdate {

  /**
   * {@inheritDoc}
   */
  public function listIds(): array {
    return array_unique(
          array_filter(
              array_map(
                  function ($v) {
                        return $v->publicHearingId->id ?? '';
                  },
                  $this->items()
              )
          )
      );
  }

}
