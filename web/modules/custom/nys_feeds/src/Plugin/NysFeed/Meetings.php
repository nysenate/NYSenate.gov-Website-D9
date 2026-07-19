<?php

namespace Drupal\nys_feeds\Plugin\NysFeed;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\Entity\Node;
use Drupal\nys_feeds\Attribute\NysFeed;
use Drupal\nys_feeds\NysFeedPluginBase;
use Drupal\nys_feeds\Traits\DateFormatterTrait;
use Drupal\nys_feeds\Traits\EntityFormatterTrait;
use Drupal\nys_feeds\Traits\MediaFieldFormatterTrait;

/**
 * NYS Feeds plugin for committee meetings.
 */
#[NysFeed(
  label: new TranslatableMarkup("Committee Meetings"),
  description: new TranslatableMarkup("NYS Feed for Committee meetings.  Takes a 'date' parameter (YYYYMMDD)."),
  entity_type: 'node',
  bundle: 'meeting',
  id: "meetings",
)]
class Meetings extends NysFeedPluginBase {

  use DateFormatterTrait;
  use MediaFieldFormatterTrait;
  use EntityFormatterTrait;

  /**
   * {@inheritDoc}
   */
  protected function query(): array {
    $date = $this->state->params['date_obj'];
    $start = $date->setTime(0, 0)->format('Y-m-d\TH:i:s');
    $end = $date->setTime(23, 59, 59)->format('Y-m-d\TH:i:s');

    try {
      $query = $this->getQuery()
        ->condition('field_date_range.value', $start, '>=')
        ->condition('field_date_range.value', $end, '<=');
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
   * {@inheritDoc}
   */
  protected function transcribeEntry(mixed $data): array {

    // Only do work on session nodes.
    if (!(($data instanceof Node) && $data->bundle() == 'meeting')) {
      return ['error' => 'Require meeting nodes, received ' . get_class($data)];
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
      'location' => $data->field_meeting_location->value ?? '',
      'meeting_status' => $data->field_meeting_status->value ?? '',
      'off_floor' => $data->field_off_floor->value ?? '',
      'committee' => [
        'name' => $data->field_committee?->entity?->label() ?? '',
        'url' => $this->getUrl($data->field_committee?->entity),
      ],
    ];

    // If this is an online meeting, add the link.
    if ($link = $data->field_event_online_link->value) {
      $ret['online_link'] = $link;
    }

    // Add the agenda info.
    $ret['agenda'] = array_map(
      function ($a) {
        return [
          'number' => $a->field_ol_year->value . '/' . $a->field_ol_week->value,
          'title' => $a->label(),
          'url' => $this->getUrl($a),
        ];
      },
      $data->field_meeting_agenda->referencedEntities()
    );

    // Compile issues.
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $issues */
    $issues = $data->get('field_issues');
    if ($issues) {
      $ret['issues'] = $this->getReferencedLabels($issues);
    }

    // Compile majority issues.
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $issues */
    $majority = $data->get('field_majority_issue_tag');
    if ($majority) {
      $ret['majority_issues'] = $this->getReferencedLabels($majority);
    }

    // Add bill info.
    $ret['bills'] = array_map(
      function ($a) {
        return [
          'name' => $a->label(),
          'url' => $this->getUrl($a),
        ];
      },
      $data->field_bill->referencedEntities()
    );

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
   * {@inheritDoc}
   */
  protected function resolveParams(): self {
    return parent::resolveParams()->initDateParam();
  }

}
