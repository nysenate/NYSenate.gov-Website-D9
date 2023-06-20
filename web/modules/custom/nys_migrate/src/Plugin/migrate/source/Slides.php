<?php

namespace Drupal\nys_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * The 'chapters' source plugin.
 *
 * @MigrateSource(
 *   id = "slides",
 *   source_module = "nys_migrate"
 * )
 */
class Slides extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('field_data_field_pg_slider_images', 's');
    $query->fields(
          's', [
            'bundle',
            'entity_id',
            'delta',
            'field_pg_slider_images_fid',
          ]
      );
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'bundle' => $this->t('Slide ID'),
      'entity_id' => $this->t('Entity ID'),
      'delta' => $this->t('Delta'),
      'field_pg_slider_images_fid' => $this->t('FID'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    // Overriding so we are not using bundle.
    return [
      'bundle' => [
        'type' => 'string',
        'alias' => 's',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    // Overriding bundle for a bad workaround.
    // Concat the entity_id and delta to form a unique id.
    $slide_id = $row->getSourceProperty('entity_id') . '-' . $row->getSourceProperty('delta');

    // Add unique id to row by overriding what was bundle.
    $row->setSourceProperty('bundle', $slide_id);
    return parent::prepareRow($row);
  }

}
