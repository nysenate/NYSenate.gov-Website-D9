<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Response;

/**
 * Openleg API Response plugin for an individual bill or resolution item.
 *
 * @OpenlegApiResponse(
 *   id = "bill",
 *   label = @Translation("Bill/Resolution"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class Bill extends ResponseItem {

  /**
   * TRUE if a non-original, published amendment exists.
   */
  public function isAmended(): bool {
    $ret = 0;
    foreach (($this->result()->publishStatusMap->items ?? []) as $v) {
      $ret += ((int) (($v->published ?? 0) && $v->version));
    }
    return (bool) $ret;
  }

}
