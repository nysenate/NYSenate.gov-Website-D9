<?php

namespace Drupal\nys_feeds\Plugin\NysFeed;

use Drupal\node\Entity\Node;
use Drupal\nys_feeds\FeedState;
use Drupal\nys_feeds\NysFeedPluginBase;

/**
 * NYS Feeds plugin for events, meetings, and public hearings.
 *
 * @NysFeed(
 *   id = "events",
 *   label = @Translation("Events"),
 *   description = @Translation("NYS Feed for Events, Meetings, and Public
 *   Hearings"),
 * )
 */
class Events extends NysFeedPluginBase {

  /**
   * The state monitor for the current request.
   *
   * @var \Drupal\nys_feeds\FeedState
   */
  protected FeedState $state;

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
  protected function transcribeToArray(Node $event): array {
    $event_date = $event->get('field_date_range')->start_date->getTimestamp();
    try {
      $url = $event->toUrl()->toString();
    }
    catch (\Exception) {
      $url = 'Error rendering URL';
    }

    // Some basic fields.
    $ret = [
      'id' => $event->id(),
      'type' => $event->field_event_type->value ?? 'unknown',
      'title' => $event->getTitle() ?? '<No Title>',
      'url' => $url,
      'date' => $this->formatDate($event_date),
      'body' => $event->body->value ?? "No description",
      'senator' => $event->field_senator_multiref->entity->field_ol_shortname->value,
      'updated' => $this->formatDate($event->changed->value),
      'place_type' => $event->field_event_place->value ?? '',
      'online_link' => $event->field_event_online_link->value ?? '',
      'majority_issue' => $event->field_majority_issue_tag->value ?? '',
      'committee' => [
        'name' => $event->field_committee?->entity?->label() ?? '',
        'url' => $event->field_committee?->entity?->toUrl()->toString() ?? '',
      ],
    ];

    // Collect the address fields.
    try {
      $location = array_map(
        fn($val) => $val ?? '',
        $event->get('field_location')->first()?->toArray() ?? []
      );
    }
    catch (\Exception) {
      $location = [];
    }
    $ret['location'] = $location;
    $ret['location']['extra'] = $event->field_meeting_location->value ?? '';

    // Compile issues.
    $ret['issues'] = array_filter(array_map(
      fn($entity) => $entity->label(),
      $event->get('field_issues')?->referencedEntities() ?? []
    ));

    // Final fields.
    $ret += [
      'teleconference_id' => $event->field_teleconference_id_number->value ?? '',
      'teleconference_number' => $event->field_teleconference_number->value ?? '',
      'ustream_id' => $event->field_ustream->value ?? '',
      'video_redirect' => $event->field_video_redirect->value ?? '',
      'video_status' => $event->field_video_status->value ?? '',
      'yt_archive_id' => $event->field_yt->value ?? '',
      'attachments' => $event->field_attachment->count(),
    ];

    return $ret;
  }

  /**
   * Does the work associated with normalizing operating parameters.
   */
  protected function resolveParams(): void {
    // Basic validation of the date.
    $date = \DateTimeImmutable::createFromFormat(
      'Ymd', $this->state->params['date'] ?? date('Ymd', time())
    );
    if ($date === FALSE) {
      $date = \DateTimeImmutable::createFromFormat('Ymd', date('Ymd', time()));
      $this->state->messages[] = "Invalid date parameter, using " . $date->format("Ymd");
      $this->state->code = 400;
    }
    $this->state->params['date_obj'] = $date;
  }

  /**
   * {@inheritDoc}
   *
   * Loads all events scheduled for a single day, which should be found in
   * $state->params['date'] as YYYYMMDD.  If it is missing, or if it cannot be
   * parsed, the current date is used.
   */
  public function getFeed(FeedState $state): FeedState {
    $this->state = $state;
    $this->resolveParams();

    // Compile the results.
    $events = $this->query();
    /** @var \Drupal\node\Entity\Node $val */
    foreach ($events as $val) {
      $state->data[] = $this->transcribeToArray($val);
    }
    if (!count($events)) {
      $state->messages[] = "No events found";
    }

    return $state;
  }

}
