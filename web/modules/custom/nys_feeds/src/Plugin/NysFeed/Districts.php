<?php

namespace Drupal\nys_feeds\Plugin\NysFeed;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\nys_feeds\Attribute\NysFeed;
use Drupal\nys_feeds\Traits\DateFormatterTrait;
use Drupal\nys_feeds\Traits\EntityFormatterTrait;
use Drupal\nys_feeds\NysFeedPluginBase;
use Drupal\taxonomy\Entity\Term;

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
    if (!(($data instanceof Term) && $data->bundle() == 'districts')) {
      return ['error' => 'Require district taxonomy terms, received ' . get_class($data)];
    }

    // Some basic fields.
    return [
      'id' => $data->id(),
      'title' => $data->label() ?? '<No Description>',
      'district_number' => $data->field_district_number->value ?? '<error>',
      'url' => $this->getUrl($data),
      'body' => $data->body->value ?? "No description",
      'locality' => $data->field_subheading->value ?? '',
      'senator' => $data->field_senator->entity?->field_ol_shortname->value ?? 'empty seat',
      'updated' => $this->formatDate($data->changed->value),
      'map_url' => $data->field_map_url->value ?? '',
    ];
  }

}
