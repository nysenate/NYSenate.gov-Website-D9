<?php

namespace Drupal\webform_analysis;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\WebformInterface;

/**
 * Defines the handler for the webform analysis entity type.
 */
class WebformAnalysis implements WebformAnalysisInterface {

  use StringTranslationTrait;

  /**
   * The webform variable.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * The entity variable.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The elements variable.
   *
   * @var array
   */
  protected $elements;

  /**
   * Construct.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity of form.
   * @param string $field_name
   *   (optional) The webform field name to display. Required if $entity is not
   *   a Webform.
   */
  public function __construct(EntityInterface $entity, $field_name = NULL) {
    if ($entity instanceof WebformInterface) {
      $this->webform = $entity;
      $this->entity = NULL;
    }
    else {
      $this->entity = $entity;
      $this->webform = $entity->{$field_name}->entity;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWebform() {
    return $this->webform;
  }

  /**
   * {@inheritdoc}
   */
  public function setComponents(array $components = []) {
    $this->webform->setThirdPartySetting('webform_analysis', 'components', $components);
  }

  /**
   * {@inheritdoc}
   */
  public function getComponents() {
    return (array) $this->webform->getThirdPartySetting('webform_analysis', 'components');
  }

  /**
   * {@inheritdoc}
   */
  public function setChartType($chart_type = '') {
    $this->webform->setThirdPartySetting('webform_analysis', 'chart_type', $chart_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getChartType() {
    return (string) $this->webform->getThirdPartySetting('webform_analysis', 'chart_type');
  }

  /**
   * {@inheritdoc}
   */
  public function getElements() {
    if (!$this->elements) {
      $this->elements = $this->webform->getElementsInitializedFlattenedAndHasValue();
    }
    return $this->elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getComponentValuesCount($component) {

    $db = \Drupal::database();
    $query = $db->select('webform_submission_data', 'wsd');
    $query->fields('wsd', ['value']);
    $query->addExpression('COUNT(value)', 'quantity');
    if ($this->entity) {
      $query->leftJoin('webform_submission', 'ws', 'wsd.sid = ws.sid');
    }
    $query->condition('wsd.webform_id', $this->webform->id());
    $query->condition('name', $component);
    if ($this->entity) {
      $query->condition('entity_type', $this->entity->getEntityTypeId());
      $query->condition('entity_id', $this->entity->id());
    }
    $query->groupBy('wsd.value');
    $records = $query->execute()->fetchAll();

    $values = [];
    $allNumeric = TRUE;
    foreach ($records as $record) {
      if (is_numeric($record->value)) {
        $value = $this->castNumeric($record->value);
      }
      else {
        $value = $record->value;
        $allNumeric = FALSE;
      }
      $values[$value] = (int) $record->quantity;
    }

    if ($allNumeric) {
      ksort($values);
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getComponentRows($component, array $header = [], $value_label_with_count = FALSE) {
    $rows = [];
    foreach ($this->getComponentValuesCount($component) as $value => $count) {
      switch ($this->getElements()[$component]['#type']) {
        case 'checkbox':
          $value_label = $value ? $this->t('Yes') : $this->t('No');
          break;

        default:
          $value_label = isset($this->getElements()[$component]['#options'][$value]) ? $this->getElements()[$component]['#options'][$value] : $value;
          break;
      }
      if ($value_label_with_count) {
        $value_label .= ' : ' . $count;
      }

      $rows[] = [(string) $value_label, $count];
    }

    if ($header && $rows) {
      array_unshift($rows, $header);
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function getComponentTitle($component) {
    if (!isset($this->getElements()[$component]['#title'])) {
      return $component;
    }
    return $this->getElements()[$component]['#title'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getChartTypeOptions() {
    return [
      ''            => t('Table'),
      'PieChart'    => t('Pie Chart'),
      'ColumnChart' => t('Column Chart'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isInt($i = '') {
    return ($i === (string) (int) $i);
  }

  /**
   * {@inheritdoc}
   */
  public function castNumeric($i = '') {
    if (empty($i)) {
      return '';
    }

    return $this->isInt($i) ? $i : "{$i}";
  }

}
