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
 * NYS Feeds plugin for public hearings.
 */
#[NysFeed(
  label: new TranslatableMarkup("Public Hearings"),
  description: new TranslatableMarkup("NYS Feed for public hearings.  Takes a 'date' parameter (YYYYMMDD)."),
  entity_type: 'node',
  bundle: 'public_hearing',
  params: ['date' => NULL],
  id: "hearings",
)]
class Hearings extends NysFeedPluginBase {

  use DateFormatterTrait;
  use LocationFormatterTrait;
  use MediaFieldFormatterTrait;
  use EntityFormatterTrait;

  /**
   * {@inheritDoc}
   */
  protected function query(): array {
    $date = $this->state->vars['date_obj'];
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
    if (!(($data instanceof Node) && $data->bundle() == 'public_hearing')) {
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
      'location' => $this->getLocation($data->field_location) +
        [
          'place' => $data->field_event_place->value ?? '',
          'extra' => $data->field_meeting_location->value ?? '',
        ],
      'committee' => [
        'name' => $data->field_committee?->entity?->label() ?? '',
        'url' => $this->getUrl($data->field_committee?->entity),
      ],
    ];

    // If this is an online meeting, add the link.
    if ($link = $data->field_event_online_link->value) {
      $ret['online_link'] = $link;
    }

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
