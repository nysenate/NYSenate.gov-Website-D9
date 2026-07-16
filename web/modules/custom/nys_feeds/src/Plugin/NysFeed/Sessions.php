<?php

namespace Drupal\nys_feeds\Plugin\NysFeed;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\Entity\Node;
use Drupal\nys_feeds\Attribute\NysFeed;
use Drupal\nys_feeds\NysFeedPluginBase;
use Drupal\nys_feeds\Traits\DateFormatterTrait;
use Drupal\nys_feeds\Traits\EntityFormatterTrait;
use Drupal\nys_feeds\Traits\LocationFormatterTrait;
use Drupal\nys_feeds\Traits\MediaFieldFormatterTrait;

/**
 * NYS Feeds plugin for sessions.
 */
#[NysFeed(
  label: new TranslatableMarkup("Sessions"),
  description: new TranslatableMarkup("NYS Feed for Sessions.  Takes a 'date' parameter and returns the next calendar week of sessions."),
  entity_type: 'node',
  bundle: 'session',
  id: "sessions",
)]
class Sessions extends NysFeedPluginBase {

  use DateFormatterTrait;
  use LocationFormatterTrait;
  use MediaFieldFormatterTrait;
  use EntityFormatterTrait;

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
      $query = $this->getQuery()
        ->condition('field_date_range.value', $start, '>=')
        ->condition('field_date_range.value', $end, '<=');
      $result = $query->execute();
      $ret = $this->entityTypeManager->getStorage('node')
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
      'field_live_message_override' => $data->field_live_message_override->value ?? '',
    ];

    // Add the calendar info.
    $ret['calendar'] = [
      'number' => $data->field_calendar_number->value ?? 0,
      'links' => array_map(
        function ($c) {
          return $this->getUrl($c);
        },
        $data->field_session_calendars->referencedEntities()
      ),
    ];

    // Compile issues.
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $issues */
    $issues = $data->get('field_issues');
    if ($issues) {
      $ret['issues'] = $this->getReferencedLabels($issues);
    }

    // Compile transcript references.
    $ret['transcripts'] = array_map(
      function ($val) {
        return [
          'name' => $val->label(),
          'url' => $this->getUrl($val),
        ];
      },
      $data->field_transcript->referencedEntities()
    );

    // Collect the media fields.
    $ret += $this->getMediaFields($data);

    return $ret;
  }

  /**
   * Does the work associated with normalizing operating parameters.
   */
  protected function resolveParams(): self {
    return parent::resolveParams()->initDateParam();
  }

}
