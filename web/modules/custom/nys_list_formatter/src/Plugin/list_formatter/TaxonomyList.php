<?php

namespace Drupal\nys_list_formatter\Plugin\list_formatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\nys_list_formatter\Plugin\ListFormatterListInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Plugin implementation of the 'taxonomy_term' list formatter.
 *
 * @ListFormatter(
 *   id = "taxonomy_term",
 *   module = "taxonomy",
 *   field_types = {"entity_reference"}
 * )
 */
class TaxonomyList implements ListFormatterListInterface {

  /**
   * Implements Create List.
   */
  public function createList(FieldItemListInterface $items, FieldDefinitionInterface $field_definition, FormatterInterface $formatter, $langcode) {
    $settings = $field_definition->getSettings();
    $list_items = $tids = [];

    // Get an array of tids only.
    foreach ($items as $item) {
      $tids[] = $item['tid'];
    }

    $terms = Term::loadMultiple($tids);

    foreach ($items as $delta => $item) {
      // Check the term for this item has actually been loaded.
      // @see http://drupal.org/node/1281114
      if (empty($terms[$item['tid']])) {
        continue;
      }
      // Use the item name if autocreating, as there won't be a term object yet.
      $term_name = ($item['tid'] === 'autocreate') ? $item['name'] : $terms[$item['tid']]->label();
      // Check if we should display as term links or not.
      if ($settings['term_plain'] || ($item['tid'] === 'autocreate')) {
        $list_items[$delta] = [
          '#markup' => $term_name,
          '#allowed_tags' => FieldFilteredMarkup::allowedTags(),
        ];
      }
      else {
        $url = $terms[$item['tid']]->toUrl();
        $list_items[$delta] = [
          '#type' => 'link',
          '#title' => $term_name,
          '#url' => $url,
          '#options' => [],
        ];
      }
    }

    return $list_items;
  }

  /**
   * Implements Additional Settings.
   */
  public function additionalSettings(array &$elements, FieldDefinitionInterface $field_definition, FormatterInterface $formatter) {
    if ($field_definition->getType() === 'taxonomy_term') {
      $elements['term_plain'] = [
        '#type' => 'checkbox',
        '#title' => t("Display taxonomy terms as plain text (Not term links)."),
        '#default_value' => $formatter->getSetting('term_plain'),
      ];
    }
  }

}
