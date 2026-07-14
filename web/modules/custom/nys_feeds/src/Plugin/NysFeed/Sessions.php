<?php

namespace Drupal\nys_feeds\Plugin\NysFeed;

use Drupal\node\Entity\Node;
use Drupal\nys_feeds\NysFeedPluginBase;

/**
 * NYS Feeds plugin for sessions.
 *
 * @NysFeed(
 *   id = "sessions",
 *   label = @Translation("Sessions"),
 *   description = @Translation("NYS Feed for Session meetings"),
 * )
 */
class Sessions extends NysFeedPluginBase {

  /**
   * Fetches event nodes scheduled to occur on the provided day.
   *
   * @return array
   *   Array of results, keyed by node id.  The return may be empty.
   */
  protected function query(): array {
    $date = $this->state->params['date_obj'];
    $start = $date->setTime(0, 0)->format('Y-m-d\TH:i:s');
    $end = $date->add(new \DateInterval('P7D'))
      ->setTime(23, 59, 59)
      ->format('Y-m-d\TH:i:s');

    try {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'session')
        ->condition('field_date_range.value', $start, '>=')
        ->condition('field_date_range.value', $end, '<=')
        ->accessCheck();
      $result = $query->execute();
      $ret = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadMultiple($result);
    }
    catch (\Exception) {
      $ret = [];
    }
    return $ret;
  }

  /**
   * Transcribes a single event to an array appropriate for JSON delivery.
   */
  protected function transcribeEntry(mixed $data): array {
    // Only do work on session nodes.
    if (!(($data instanceof Node) && $data->bundle() == 'session')) {
      return ['error' => 'Require session nodes, received ' . get_class($data)];
    }

    // Some basic fields.
    $ret = [
      'id' => $data->id(),
      'title' => $data->getTitle() ?? '<No Title>',
      'url' => $this->getUrl($data),
      'date' => $this->formatDate($data->get('field_date_range')->start_date->getTimestamp()),
      'body' => $data->body->value ?? "No description",
      'updated' => $this->formatDate($data->changed->value),
      'place_type' => $data->field_event_place->value ?? '',
      'location' => $this->getLocation($data->field_location) +
        ['extra' => $data->field_meeting_location->value ?? ''],
      'field_live_message_status' => $data->field_live_message_status->value ?? '',
    ];

    // Add the calendar info.
    $calendars = $data->field_session_calendars->referencedEntities();
    $ret['calendar'] = [
      'number' => $data->field_calendar_number->value ?? 0,
      'links' => array_map(
        function ($c) {
          return $c->toUrl()->toString();
        },
        $calendars
      ),
    ];

    // Compile issues.
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $issues */
    $issues = $data->get('field_issues');
    if ($issues) {
      $ret['issues'] = $this->getReferencedLabels($issues);
    }

    // Compile transcript references.
    $source = $data->field_transcript->referencedEntities();
    $ret['transcripts'] = array_map(
      function ($val) {
        return [
          'name' => $val->label(),
          'url' => $val->toUrl()->toString(),
        ];
      },
      $source
    );

    // Collect the media fields.
    $ret += $this->getMediaFields($data);

    return $ret;
  }

  /**
   * Does the work associated with normalizing operating parameters.
   */
  protected function resolveParams(): self {
    // Basic validation of the date.
    $date = \DateTimeImmutable::createFromFormat(
      'Ymd', $this->state->params['date'] ?? date('Ymd', time())
    );
    if ($date === FALSE) {
      $date = \DateTimeImmutable::createFromFormat('Ymd', date('Ymd', time()));
      $this->state->messages[] = "Invalid date parameter (must be YYYYMMDD), using " . $date->format("Ymd");
      $this->state->code = 400;
    }
    $this->state->params['date_obj'] = $date;
    return $this;
  }

}
