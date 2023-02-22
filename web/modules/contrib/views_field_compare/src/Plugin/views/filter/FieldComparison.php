<?php

/**
 * @file
 * Contains \Drupal\views_field_compare\Plugin\views\filter\FieldComparison.
 */

namespace Drupal\views_field_compare\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filter handler which allows to filter by comparing two field values.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsFilter("field_comparison")
 */
class FieldComparison extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['left_field'] = ['default' => ''];
    $options['right_field'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $this->view->initStyle();

    // Allow to choose all fields as possible
    if ($this->view->style_plugin->usesFields()) {
      $options = [];
      foreach ($this->view->display_handler->getHandlers('field') as $name => $field) {
        // Only allow clickSortable fields. Fields without clickSorting will
        // probably break in the Combine filter.
        if ($field->clickSortable()) {
          $options[$name] = $field->adminLabel(TRUE);
        }
      }
      if ($options && count($options) >= 2) {
        $form['left_field'] = [
          '#type' => 'select',
          '#title' => $this->t('Choose left field'),
          '#description' => $this->t("This filter doesn't work for very special field handlers."),
          '#options' => $options,
          '#default_value' => $this->options['left_field'],
        ];
        $form['right_field'] = [
          '#type' => 'select',
          '#title' => $this->t('Choose right field'),
          '#description' => $this->t("This filter doesn't work for very special field handlers."),
          '#options' => $options,
          '#default_value' => $this->options['right_field'],
        ];
      }
      else {
        $form_state->setErrorByName('', $this->t('You have to add some fields to be able to use this filter.'));
      }
    }
  }

  /**
   * Function to return an array of details about the set of operators that can
   * be used to join the two operands for this filter.
   *
   * @return array
   */
  function operators() {
    return [
      '<' => [
        'title' => $this->t('Is less than'),
        'method' => 'opSimple',
        'short' => $this->t('<'),
        'values' => 1,
      ],
      '<=' => [
        'title' => $this->t('Is less than or equal to'),
        'method' => 'opSimple',
        'short' => $this->t('<='),
        'values' => 1,
      ],
      '=' => [
        'title' => $this->t('Is equal to'),
        'method' => 'opSimple',
        'short' => $this->t('='),
        'values' => 1,
      ],
      '!=' => [
        'title' => $this->t('Is not equal to'),
        'method' => 'opSimple',
        'short' => $this->t('!='),
        'values' => 1,
      ],
      '>=' => [
        'title' => $this->t('Is greater than or equal to'),
        'method' => 'opSimple',
        'short' => $this->t('>='),
        'values' => 1,
      ],
      '>' => [
        'title' => $this->t('Is greater than'),
        'method' => 'opSimple',
        'short' => $this->t('>'),
        'values' => 1,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function operatorOptions($which = 'title') {
    $options = [];
    foreach ($this->operators() as $id => $info) {
      $options[$id] = $info[$which];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function canBuildGroup() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->view->_build('field');
    $fields = [];
    // Only add the fields if they have a proper field and table alias.
    foreach ([
      $this->options['left_field'],
      $this->options['right_field']
    ] as $id) {
      // Overridden fields can lead to fields missing from a display that are
      // still set in the non-overridden combined filter.
      if (!isset($this->view->field[$id])) {
        // If fields are no longer available that are needed to filter by, make
        // sure no results are shown to prevent displaying more then intended.
        $this->view->build_info['fail'] = TRUE;
        continue;
      }
      $field = $this->view->field[$id];
      // Always add the table of the selected fields to be sure a table alias exists.
      $field->ensureMyTable();
      if (!empty($field->field_alias)) {
        $fields[] = "$field->tableAlias.$field->realField";
      }
    }
    if ($fields && count($fields) == 2) {
      $info = $this->operators();
      if (!empty($info[$this->operator]['method'])) {
        $this->{$info[$this->operator]['method']}($fields[0], $fields[1]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    $fields = $this->displayHandler->getHandlers('field');
    if ($this->displayHandler->usesFields()) {
      foreach ([
        $this->options['left_field'],
        $this->options['right_field']
      ] as $id) {
        if (!isset($fields[$id])) {
          // Combined field filter only works with fields that are in the field
          // settings.
          $errors[] = $this->t('Field %field set in %filter is not set in this display.', [
            '%field' => $id,
            '%filter' => $this->adminLabel()
          ]);
          break;
        }
        elseif (!$fields[$id]->clickSortable()) {
          // Combined field filter only works with simple fields. If the field is
          // not click sortable we can assume it is not a simple field.
          // @todo change this check to isComputed. See
          // https://www.drupal.org/node/2349465
          $errors[] = $this->t('Field %field set in %filter is not usable for this filter type. Fields comparison filter only works for simple fields.', [
            '%field' => $fields[$id]->adminLabel(),
            '%filter' => $this->adminLabel()
          ]);
        }
      }
    }
    else {
      $errors[] = $this->t('%display: %filter can only be used on displays that use fields.', [
        '%display' => $this->displayHandler->display['display_title'],
        '%filter' => $this->adminLabel()
      ]);
    }
    return $errors;
  }

  /**
   * Function to generate an SQL snippet using a simple comparison operation
   * between the two operands specified for the filter.
   *
   * @return void
   */
  protected function opSimple($left_field, $right_field) {
    $this->query->addWhereExpression($this->options['group'], "$left_field {$this->operator} $right_field", []);
  }

}
