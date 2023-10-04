<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Response;

/**
 * Openleg API Response plugin for Statute detail, part of Statute items.
 *
 * @OpenlegApiResponse(
 *   id = "law-doc-info-detail",
 *   label = @Translation("Statute Detail"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class StatuteDetail extends ResponseItem {

  /**
   * Gets an array of the siblings associated with the current entry.
   *
   * @return array
   *   The return array has keys for 'previous' and 'next'.
   */
  public function siblings(): array {
    return [
      'previous' => $this->response->result->prevSibling ?? NULL,
      'next' => $this->response->result->nextSibling ?? NULL,
    ];
  }

  /**
   * Gets the array of parent objects from the most recent call.
   *
   * @return array
   *   An array of parent objects.
   */
  public function parents(): array {
    return $this->response->result->parents ?? [];
  }

  /**
   * Gets the text of the current entry.
   *
   * If $raw is TRUE, the text is mangled for proper presentation in HTML.
   *
   * @param bool $raw
   *   Indicates if raw text should be returned.
   *
   * @return string
   *   The text.
   */
  public function text(bool $raw = FALSE): string {
    $ret = $this->response->result->text ?? '';
    if (!$raw) {
      $ret = str_replace('\\n', '<br />', str_replace('\\n  ', '<br /><br />', htmlentities($ret, ENT_QUOTES)));
    }
    return $ret;
  }

}
