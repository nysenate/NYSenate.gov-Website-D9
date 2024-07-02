<?php

/**
 * @file
 * Contains \Drupal\views_field_compare\Plugin\views\filter\FieldContained.
 */

namespace Drupal\views_field_compare\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\EntityField;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Plugin\views\join\Standard;

/**
 * Filter handler which allows to filter by a field value contained in or not
 * contained in another multi-valued field.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsFilter("field_contained")
 */
class FieldContained extends FilterPluginBase {

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
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['left_field'] = ['default' => ''];
    $options['right_field'] = ['default' => ''];
    return $options;
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
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $this->view->initStyle();

    // Allow to choose all fields as possible
    if ($this->view->style_plugin->usesFields()) {
      $left_options = [];
      $right_options = [];
      /** @var \Drupal\views\Plugin\views\field\EntityField $field */
      foreach ($this->view->display_handler->getHandlers('field') as $name => $field) {
        // Only allow clickSortable fields for left options.
        if ($field->clickSortable()) {
          $left_options[$name] = $field->adminLabel(TRUE);
        }
        elseif ($field->multiple) {
          // Only allow multi-valued fields for right options.
          $right_options[$name] = $field->adminLabel(TRUE);
        }
      }
      if (!empty($left_options)) {
        $form['left_field'] = [
          '#type' => 'select',
          '#title' => $this->t('Choose left field'),
          '#description' => $this->t("This filter doesn't work for very special field handlers."),
          '#options' => $left_options,
          '#default_value' => $this->options['left_field'],
        ];
      }
      if (!empty($right_options)) {
        $form['right_field'] = [
          '#type' => 'select',
          '#title' => $this->t('Choose right field'),
          '#description' => $this->t("This filter requires multi-valued field handlers."),
          '#options' => $right_options,
          '#default_value' => $this->options['right_field'],
        ];
      }
      if (empty($left_options) || empty($right_options)) {
        $form_state->setErrorByName('', $this->t('You have to add some fields to be able to use this filter.'));
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
                 $this->options['right_field'],
               ] as $id) {
        if (!isset($fields[$id])) {
          // Combined field filter only works with fields that are in the fields list.
          $errors[] = $this->t('Field %field set in %filter is not set in this display.', [
            '%field' => $id,
            '%filter' => $this->adminLabel(),
          ]);
          break;
        }
      }

      if (!empty($this->options['left_field'])
        && isset($fields[$this->options['left_field']])
        && !$fields[$this->options['left_field']]->clickSortable()) {
        // Combined field filter only works with simple fields. If the field is
        // not click sortable we can assume it is not a simple field.
        // @todo change this check to isComputed. See
        // https://www.drupal.org/node/2349465
        $errors[] = $this->t('Field %field set in %filter is not usable for this filter type. Fields contained filter only works for simple fields.', [
          '%field' => $fields[$id]->adminLabel(),
          '%filter' => $this->adminLabel(),
        ]);
      }

      //$left_entity_type = $fields[$this->options['left_field']]->definition['entity_type'];
      //$right_entity_type = $fields[$this->options['right_field']]->definition['entity_type'];
      //if ($left_entity_type != $right_entity_type) {
      // Both fields must be attached to the same entity type for the subquery expression
      // used in the query function to work correctly.
      // @TODO enhance the subquery expression to allow fields from different entity types
      //  $errors[] = $this->t('Left and right fields must be attached to same entity type. Left entity = %left, right entity = %right', [
      //    '%left' => $left_entity_type,
      //    '%right' => $right_entity_type,
      //  ]);
      //}

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
   * Function to return an array of details about the set of operators that can
   * be used to join the two operands for this filter.
   *
   * @return array
   */
  function operators() {
    $operators = [
      'in' => [
        'title' => $this->t('Is contained in'),
        'short' => $this->t('in'),
        'short_single' => $this->t('IN'),
        'method' => 'opContained',
        'values' => 1,
      ],
      'not in' => [
        'title' => $this->t('Is not contained in'),
        'short' => $this->t('not in'),
        'short_single' => $this->t('!IN'),
        'method' => 'opNotContained',
        'values' => 1,
      ],
    ];
    return $operators;
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
               $this->options['right_field'],
             ] as $id) {
      // Overridden fields can lead to fields missing from a display that are
      // still set in the non-overridden combined filter.
      if (!isset($this->view->field[$id])) {
        // If fields are no longer available that are needed to filter by, make
        // sure no results are shown to prevent displaying more then intended.
        $this->view->build_info['fail'] = TRUE;
        continue;
      }
      /** @var \Drupal\views\Plugin\views\field\EntityField $field */
      $field = $this->view->field[$id];
      // Always add the table of the selected fields to be sure a table alias exists.
      $field->ensureMyTable();
      if (!empty($field->tableAlias) && !empty($field->realField)) {
        $fields[] = $field;
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
   * Function to generate a subquery to select the set of values from the
   * right (multivalued) operand, and then to create an SQL snippet using the
   * subquery and the left (single-valued) operand.
   *
   * @param \Drupal\views\Plugin\views\field\EntityField $source_field
   * @param \Drupal\views\Plugin\views\field\EntityField $target_field
   *
   * @return void
   */
  protected function opContained(EntityField $source_field, EntityField $target_field) {
    $subquery = $this->generateSubquery($source_field, $target_field);
    if ($subquery) {
      $expression = "$source_field->tableAlias.$source_field->realField" . " IN (\n" . $subquery . "\n)";
      $this->query->addWhereExpression($this->options['group'], $expression);
    }
  }

  /**
   * Function to generate a subquery to select the set of values from the
   * right (multivalued) operand, and then to create an SQL snippet using the
   * subquery and the left (single-valued) operand.
   *
   * @param \Drupal\views\Plugin\views\field\EntityField $source_field
   * @param \Drupal\views\Plugin\views\field\EntityField $target_field
   *
   * @return void
   */
  protected function opNotContained(EntityField $source_field, EntityField $target_field) {
    $subquery = $this->generateSubquery($source_field, $target_field);
    if ($subquery) {
      $expression = "$source_field->tableAlias.$source_field->realField" . " NOT IN (\n" . $subquery . "\n)";
      $this->query->addWhereExpression($this->options['group'], $expression);
    }
  }

  /**
   * Function to generate a subquery to select the values from the right
   * (multivalued) operand.
   *
   * @param \Drupal\views\Plugin\views\field\EntityField $source_field
   * @param \Drupal\views\Plugin\views\field\EntityField $target_field
   *
   * @return string|bool
   */
  protected function generateSubquery(EntityField $source_field, EntityField $target_field) {

    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $target_field->query;
    $tableQueue = $query->getTableQueue();
    $joins = [];
    foreach ($tableQueue as $table_data) {
      if (isset($table_data['join'])) {
        /** @var \Drupal\views\Plugin\views\join\JoinPluginInterface $join */
        $join = $table_data['join'];
        $join_alias = $this->tableAlias($table_data['alias']);
        $left_alias = $join->leftTable;
        $left_alias = $this->tableAlias($left_alias);
        $join_sql = "$join->type JOIN [$join->table] $join_alias ON $join_alias.$join->field = $left_alias.$join->leftField";
        if ($join->extra) {
          $join_sql .= $this->joinExtra($join, $join_alias, $left_alias);
        }
        $joins[] = $join_sql;
      }
      else {
        // Base table so generate the initial sql subquery fragment.
        $base_table = $table_data['table'];
        $base_alias = $this->tableAlias($table_data['alias']);
        $target_alias = $this->tableAlias($target_field->tableAlias);
        $target_column = $target_field->realField;

        $sql[] = "SELECT {$target_alias}.{$target_column} FROM [{$base_table}] {$base_alias}";
      }
    }

    if ($joins) {
      // Generate the subquery sql components
      $sql[] = implode("\n", $joins);

      // Add contratint to subquery
      $source_data = $tableQueue[$source_field->tableAlias];
      $source_alias = $this->tableAlias($source_data['alias']);
      $sql[] = "WHERE {$source_alias}.{$source_data['join']->field} = {$source_data['alias']}.{$source_data['join']->field}";
      // Replace the square brackets around table names with curly braces to conform to Drupal standard.
      $sql = str_replace(['[', ']'], ['{', '}'], implode("\n", $sql));
      return $sql;
    }

    return FALSE;
  }

  /**
   * Function to generate a compact table alias to keep identifiers to less
   * then 63 characters.  Refer to https://www.drupal.org/node/571548 for
   * discussion when length exceeds allowed limit.
   *
   * @param string $table
   *
   * @return array|string|string[]
   */
  protected function tableAlias(string $table) {
    // Generate a table alias by stripping all underscore chars.
    return str_replace('_', '', $table);
  }

  /**
   * Function to handle the details in the join extra property of the query.
   *
   * @param \Drupal\views\Plugin\views\join\Standard $join
   * @param string $join_table
   * @param string $left_table
   *
   * @return string
   */
  protected function joinExtra(Standard $join, string $join_table, string $left_table) {
    // Deal with special cases separately
    switch ($join->getPluginId()) {
      case 'field_or_language_join':
        return $this->extraFieldOrLanguageJoin($join, $join_table, $left_table);
      default:
        return $this->extraStandard($join, $join_table, $left_table);
    }
  }

  /**
   * @param \Drupal\views\Plugin\views\join\Standard $join
   * @param string $join_table
   * @param string $left_table
   *
   * @return string
   */
  protected function extraStandard(Standard $join, string $join_table, string $left_table) {
    // Process a join using the "standard" plugin.
    $sql = '';
    foreach ($join->extra as $extra) {
      if (isset($extra['value'])) {
        if (isset($extra['numeric']) && $extra['numeric']) {
          $sql .= " {$join->extraOperator} {$join_table}.{$extra['field']} = {$extra['value']}";
        }
        else {
          if (is_array($extra['value']) && count($extra['value']) > 1) {
            $values = "'" . implode("','", $extra['value']) . "'";
            $sql .= " {$join->extraOperator} {$join_table}.{$extra['field']} IN ({$values})";
          }
          else {
            $value = is_array($extra['value']) ? array_shift($extra['value']) : $extra['value'];
            if (isset($extra['field'])) {
              $sql .= " {$join->extraOperator} {$join_table}.{$extra['field']} = '{$value}'";
            }
            else {
              $sql .= " {$join->extraOperator} {$left_table}.{$extra['left_field']} = '{$value}'";
            }
          }
        }
      }
      elseif (isset($extra['left_field'])) {
        $sql .= " {$join->extraOperator} {$join_table}.{$extra['field']} = {$left_table}.{$extra['left_field']}";
      }
    }

    return $sql;
  }

  /**
   * @param \Drupal\views\Plugin\views\join\Standard $join
   * @param string $join_table
   * @param string $left_table
   *
   * @return string
   */
  protected function extraFieldOrLanguageJoin(Standard $join, string $join_table, string $left_table) {
    // Process a join using the "field_or_language_join" plugin.
    $sql = '';
    $or_sql = [];
    foreach ($join->extra as $index => $extra) {
      if ($extra['field'] == 'langcode') {
        $or_sql[] = "{$join_table}.langcode = {$left_table}.langcode";
        unset($join->extra[$index]);
      }
      elseif ($extra['field'] == 'bundle') {
        if (is_array($extra['value']) && count($extra['value']) > 1) {
          $values = "'" . implode("','", $extra['value']) . "'";
          $or_sql[] = "{$join_table}.bundle IN ({$values})";
        }
        else {
          $value = is_array($extra['value']) ? array_shift($extra['value']) : $extra['value'];
          $or_sql[] = "{$join_table}.bundle = '{$value}'";
        }
        unset($join->extra[$index]);
      }
    }

    // check if there are additional extra conditions
    if (count($join->extra)) {
      $sql = $this->extraStandard($join, $join_table, $left_table);
    }
    // Append the OR conditions and return.
    if ($or_sql) {
      $sql .= " {$join->extraOperator} (" . implode(' OR ', $or_sql) . ")";
    }
    return $sql;
  }

}
