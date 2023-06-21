<?php

namespace Drupal\nys_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * The 'offices' source plugin.
 *
 * @MigrateSource(
 *   id = "offices",
 *   source_module = "nys_migrate"
 * )
 */
class Offices extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('field_data_field_offices', 'ofc')
      ->fields(
              'ofc', [
                'entity_id',
                'field_offices_lid',
              ]
          );

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'entity_id' => $this->t('Entity ID'),
      'field_offices_lid' => $this->t('Location LID'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'field_offices_lid' => [
        'type' => 'integer',
        'alias' => 'ofc',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $office_field[] = [
      'lid' => $row->getSourceProperty('field_offices_lid'),
    ];
    $row->setSourceProperty('field_offices', $office_field);

    return parent::prepareRow($row);
  }

}
