<?php

namespace Drupal\nys_openleg\Plugin\OpenlegApi\Request;

use Drupal\nys_openleg\Api\RequestPluginBase;

/**
 * Openleg Request plugin for statutes.
 *
 * @see https://legislation.nysenate.gov/static/docs/html/laws.html#get-the-law-structure
 * @see https://legislation.nysenate.gov/static/docs/html/laws.html#get-a-law-sub-document
 *
 * @OpenlegApiRequest(
 *   id = "statute",
 *   label = @Translation("Statute"),
 *   description = @Translation("Openleg API Request plugin"),
 *   endpoint = "laws"
 * )
 */
class Statute extends RequestPluginBase {

  /**
   * Decoded JSON object representing an entry's detail.
   *
   * @var object
   */
  public object $detail;

  /**
   * Formats a date for usage as a history mark.
   */
  protected function formatHistoryTimestamp(string $date = ''): string {
    return $this->formatTimestamp($date, FALSE);
  }

  /**
   * {@inheritDoc}
   */
  public function prepParams(array $params = []): array {
    // This for statute tree request, but has no effect on detail requests.
    // It is easier just to include it all the time.
    $params = parent::prepParams($params) +
        [
          'depth' => 1,
          'fromLocation' => $this->params['location'] ?? '',
        ];
    return array_merge($params, $this->resolveHistoryParameter());
  }

  /**
   * Returns a key-value array for the history marker.
   */
  protected function resolveHistoryParameter(): array {
    return ($this->params['history'] ?? '')
        ? ['date' => $this->formatHistoryTimestamp($this->params['history'])]
        : [];
  }

}
