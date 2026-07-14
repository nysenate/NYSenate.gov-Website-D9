<?php

namespace Drupal\nys_feeds\Plugin\NysFeed;

use Drupal\node\Entity\Node;
use Drupal\nys_feeds\NysFeedPluginBase;

/**
 * NYS Feeds plugin for events.
 *
 * @NysFeed(
 *   id = "events",
 *   label = @Translation("Events"),
 *   description = @Translation("NYS Feed for Events"),
 * )
 */
class Events extends NysFeedPluginBase {

  /**
   * Fetches event nodes scheduled to occur on the provided day.
   *
   * @return array
   *   Array of results, keyed by node id.  The return may be empty.
   */
  protected function query(): array {
    $date = $this->state->params['date_obj'];
    $start = $date->setTime(0, 0)->format('Y-m-d\TH:i:s');
    $end = $date->setTime(23, 59, 59)->format('Y-m-d\TH:i:s');
    try {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'event')
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
    // Only do work on event nodes.
    if (!(($data instanceof Node) && $data->bundle() == 'event')) {
      return ['error' => 'Require event nodes, received ' . get_class($data)];
    }

    // Some basic fields.
    $ret = [
      'id' => $data->id(),
      'type' => $data->field_event_type->value ?? 'unknown',
      'title' => $data->getTitle() ?? '<No Title>',
      'url' => $this->getUrl($data),
      'date' => $this->formatDate($data->get('field_date_range')->start_date->getTimestamp()),
      'body' => $data->body->value ?? "No description",
      'senator' => $data->field_senator_multiref->entity->field_ol_shortname->value,
      'updated' => $this->formatDate($data->changed->value),
      'place_type' => $data->field_event_place->value ?? '',
      'online_link' => $data->field_event_online_link->value ?? '',
      'majority_issue' => $data->field_majority_issue_tag->value ?? '',
      'committee' => [
        'name' => $data->field_committee?->entity?->label() ?? '',
        'url' => $data->field_committee?->entity?->toUrl()->toString() ?? '',
      ],
      'location' => $this->getLocation($data->field_location) +
        ['extra' => $data->field_meeting_location->value ?? ''],
    ];

    // Compile issues.
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $issues */
    $issues = $data->get('field_issues');
    if ($issues) {
      $ret['issues'] = $this->getReferencedLabels($issues);
    }

    // Media fields.
    $ret += $this->getMediaFields($data);
    $ret['attachments'] = $data->field_attachment->count();

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
