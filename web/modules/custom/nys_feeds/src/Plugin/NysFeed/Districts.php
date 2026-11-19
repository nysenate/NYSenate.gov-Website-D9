<?php

namespace Drupal\nys_feeds\Plugin\NysFeed;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\nys_feeds\Attribute\NysFeed;
use Drupal\nys_feeds\Traits\DateFormatterTrait;
use Drupal\nys_feeds\Traits\EntityFormatterTrait;
use Drupal\nys_feeds\NysFeedPluginBase;
use Drupal\taxonomy\TermInterface;

/**
 * NYS Feeds plugin for districts.
 */
#[NysFeed(
  label: new TranslatableMarkup("Districts"),
  description: new TranslatableMarkup("Provides information on Senate districts"),
  entity_type: 'taxonomy_term',
  bundle: 'districts',
  params: ['district' => NULL],
  id: "districts",
)]
class Districts extends NysFeedPluginBase {

  use DateFormatterTrait;
  use EntityFormatterTrait;

  /**
   * {@inheritDoc}
   */
  protected function alterQuery(QueryInterface $query): void {
    $district = (int) $this->resolvedParams['district'];
    if ($district) {
      $query->condition('field_district_number.value', $district, '=');
    }
  }

  /**
   * {@inheritDoc}
   */
  protected function transcribeEntry(mixed $data): array {
    // Only do work on district terms.
    if (!(($data instanceof TermInterface) && $data->bundle() == 'districts')) {
      return ['error' => 'Require district taxonomy terms, received ' . get_class($data)];
    }

    // Some basic fields.
    $district = $data->field_district_number->value ?? '<error>';
    return [
      'id' => $data->id(),
      'title' => $data->label() ?? '<No Description>',
      'number' => $district,
      'ordinal' => $this->ordinalSuffix((int) $district),
      'url' => $this->getUrl($data),
      'body' => $data->body->value ?? "No description",
      'locality' => $data->field_subheading->value ?? '',
      'senator' => [
        'shortname' => strtolower($data->field_senator->entity?->field_ol_shortname->value ?? 'empty seat'),
        'member_id' => $data->field_senator->entity?->field_ol_member_id->value ?? -1,
      ],
      'updated' => $this->formatDate($data->changed->value),
      'map_url' => $data->field_map_url->value ?? '',
    ];
  }

}
