<?php

namespace Drupal\nys_feeds\Plugin\NysFeed;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\Entity\Node;
use Drupal\nys_feeds\Attribute\NysFeed;
use Drupal\nys_feeds\Traits\DateFormatterTrait;
use Drupal\nys_feeds\Traits\EntityFormatterTrait;
use Drupal\nys_feeds\Traits\LocationFormatterTrait;
use Drupal\nys_feeds\Traits\MediaFieldFormatterTrait;
use Drupal\nys_feeds\NysFeedPluginBase;

/**
 * NYS Feeds plugin for events.
 */
#[NysFeed(
  label: new TranslatableMarkup("Events"),
  description: new TranslatableMarkup("Returns all events for a provided 'date' parameter (YYYYMMDD)."),
  entity_type: 'node',
  bundle: 'event',
  id: "events",
)]
class Events extends NysFeedPluginBase {

  use DateFormatterTrait;
  use LocationFormatterTrait;
  use MediaFieldFormatterTrait;
  use EntityFormatterTrait;

  /**
   * {@inheritDoc}
   */
  protected function query(): array {
    // Add the passed date as a query condition.
    $date = $this->state->params['date_obj'];
    $start = $date->setTime(0, 0)->format('Y-m-d\TH:i:s');
    $end = $date->setTime(23, 59, 59)->format('Y-m-d\TH:i:s');

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
   * {@inheritDoc}
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
        'url' => $this->getUrl($data->field_committee?->entity),
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
   * {@inheritDoc}
   */
  protected function resolveParams(): self {
    return parent::resolveParams()->initDateParam();
  }

}
