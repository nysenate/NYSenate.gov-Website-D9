<?php

namespace Drupal\nys_views\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Combined checkbox filter for the bill current-cycle status milestones.
 *
 * Two checkboxes only - Passed Senate and Passed Assembly, AND'd together
 * when both are checked (each flag is an independent fact about the same
 * bill, not a mutually-exclusive category, so checking both narrows to
 * bills where both are true rather than broadening to either). Every
 * other current-cycle stage (on floor, delivered, signed, vetoed) is
 * already answerable via the "Current Status" dropdown
 * (field_ol_last_status), so isn't duplicated here. The intended
 * workflow: check "Passed Senate" to find bills that cleared the Senate
 * regardless of where they've moved on to since, optionally also check
 * "Passed Assembly" to narrow to bills that have cleared both chambers,
 * or use Current Status to narrow to a specific Assembly stage instead.
 *
 * field_passed_senate_current / field_passed_assembly_current are
 * populated by
 * \Drupal\nys_openleg_imports\Plugin\OpenlegImportProcessor\Bills::deriveCurrentCycleFields()
 * and reflect whether that milestone was reached at ANY point in the
 * bill's current cycle, not just its current status - which is exactly
 * what the Current Status dropdown alone can't express.
 */
#[ViewsFilter("bill_current_cycle_milestones")]
class BillCurrentCycleMilestonesFilter extends FilterPluginBase {

  /**
   * Checkbox key mapped to the underlying boolean field name.
   */
  const MILESTONE_FIELDS = [
    'passed_senate' => 'field_passed_senate_current',
    'passed_assembly' => 'field_passed_assembly_current',
  ];

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['value']['default'] = [];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function operators() {
    // Selected milestones are always OR'd together; there is no separate
    // operator concept to expose for this filter.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function operatorForm(&$form, FormStateInterface $form_state): void {
    // No operator to configure - see self::operators().
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state): void {
    $form['value'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Status milestones (any point this cycle)'),
      '#options' => $this->getMilestoneLabels(),
      '#default_value' => is_array($this->value) ? $this->value : [],
      // FilterPluginBase::exposedTranslate() converts exposed checkboxes
      // into a <select> by default ("checkboxes don't work so well in
      // exposed forms due to GET conversions") - #no_convert opts back
      // into real checkboxes, matching the expose.multiple = TRUE setting
      // on this filter.
      '#no_convert' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    $selected = is_array($this->value) ? array_filter($this->value) : [];
    if (!$selected) {
      return (string) $this->t('Any');
    }
    $labels = array_intersect_key($this->getMilestoneLabels(), $selected);
    return implode(', ', array_map('strval', $labels));
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    $selected = is_array($this->value) ? array_filter($this->value) : [];
    if (!$selected) {
      return;
    }

    // Each checked box adds a condition to the filter's own group, which
    // defaults to AND - checking both means "passed both chambers", not
    // "passed either one".
    foreach (array_keys($selected) as $key) {
      if (!isset(self::MILESTONE_FIELDS[$key])) {
        continue;
      }
      $field_name = self::MILESTONE_FIELDS[$key];
      $table = $this->query->ensureTable('node__' . $field_name, $this->relationship);
      if ($table) {
        $this->query->addWhere($this->options['group'], "$table.{$field_name}_value", 1, '=');
      }
    }
  }

  /**
   * Builds the checkbox key => translated label map.
   *
   * @return array
   *   Translated labels keyed by checkbox key, matching
   *   self::MILESTONE_FIELDS.
   */
  protected function getMilestoneLabels(): array {
    return [
      'passed_senate' => $this->t('Passed Senate'),
      'passed_assembly' => $this->t('Passed Assembly'),
    ];
  }

}
