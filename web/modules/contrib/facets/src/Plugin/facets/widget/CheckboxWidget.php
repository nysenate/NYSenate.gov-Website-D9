<?php

namespace Drupal\facets\Plugin\facets\widget;

/**
 * The checkbox / radios widget.
 *
 * @FacetsWidget(
 *   id = "checkbox",
 *   label = @Translation("List of checkboxes"),
 *   description = @Translation("A configurable widget that shows a list of checkboxes"),
 * )
 */
class CheckboxWidget extends LinksWidget {

  /**
   * {@inheritdoc}
   */
  protected function appendWidgetLibrary(array &$build) {
    $build['#attributes']['class'][] = 'js-facets-checkbox-links';
    $build['#attached']['library'][] = 'facets/drupal.facets.checkbox-widget';
  }

}
