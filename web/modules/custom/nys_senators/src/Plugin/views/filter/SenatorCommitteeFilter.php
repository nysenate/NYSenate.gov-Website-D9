<?php

namespace Drupal\nys_senators\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filters by Generic product blog.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("senator_committee_filter")
 */
class SenatorCommitteeFilter extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $values = [];
    $filter_value = '';
    if (!empty($this->value) && is_array($this->value)) {
      $filter_value = $this->value[0] ?? '';
      $values = $this->getLinkedTerms($filter_value);
    }
    $operator = "IN";
    if (method_exists($this->query, 'addWhere')) {
      if (count($values) == 0 && $operator == "IN") {
        $this->query->addWhere($this->options['group'], 'type', 'none');
      }
      // @todo Rework temp fix or add a custom view field to pull the Senator district in the new view.
      elseif ($this->view->id() === 'senators' && $this->view->current_display === 'senators_list' && !empty($filter_value)) {
        $this->ensureMyTable();
        // Passing the current committee tid to the query.
        $this->query->addWhere($this->options['group'], "$this->tableAlias.$this->realField", [$filter_value], $operator);
      }
      elseif (count($values) > 0) {
        $this->ensureMyTable();
        $this->query->addWhere($this->options['group'], "$this->tableAlias.$this->realField", array_values($values), $operator);
      }
    }
  }

  /**
   * Gets the senator terms linked to the current committee term.
   *
   * @param int $tid
   *   The term id that is linked to other terms.
   *
   * @return array
   *   The node ids of the nodes.
   */
  public static function getLinkedTerms($tid) {
    $query = \Drupal::database()->select('taxonomy_term_field_data', 'ttfd');
    $query->addField('ttfd', 'tid');
    $query->leftJoin('paragraph__field_senator', 'pfs', 'ttfd.tid = pfs.field_senator_target_id');
    $query->leftJoin('paragraphs_item_field_data', 'pifd', "pfs.entity_id = pifd.id AND pifd.parent_type = 'taxonomy_term' AND pifd.parent_field_name = 'field_members'");
    $query->condition('ttfd.vid', 'senator');
    $query->isNotNull('pfs.entity_id');
    $query->condition('pifd.parent_id', $tid);
    $entity_ids = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    return array_column($entity_ids, 'tid');
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    $this->valueOptions = [];
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('committees', 0, NULL, TRUE);
    if (!empty($terms) && count($terms) > 1) {
      $value_options = [];
      foreach ($terms as $term) {
        if (!($term->field_committee_types->value === 'inactive')) {
          $value_options[$term->id()] = $term->getName();
        }
      }
      $this->valueOptions = $value_options;
    }
    return $this->valueOptions;
  }

}
