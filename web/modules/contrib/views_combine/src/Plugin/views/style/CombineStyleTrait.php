<?php

namespace Drupal\views_combine\Plugin\views\style;

/**
 * Trait to combine style display handlers.
 */
trait CombineStyleTrait {

  /**
   * {@inheritdoc}
   */
  public function elementPreRenderRow(array $data) {
    $cache = &drupal_static('views_combine');
    if (isset($data['#row']->_view_id) && isset($cache[$data['#row']->_view_id])) {
      // Use the appropriate view style handlers.
      foreach ($cache[$data['#row']->_view_id]->field as $id => $field) {
        $data[$id] = ['#markup' => $field->theme($data['#row'])];
      }
      foreach (array_diff_key($this->view->field, $data) as $id => $field) {
        $data[$id] = ['#markup' => NULL];
      }
      return $data;
    }
    else {
      return parent::elementPreRenderRow($data);
    }
  }

}
