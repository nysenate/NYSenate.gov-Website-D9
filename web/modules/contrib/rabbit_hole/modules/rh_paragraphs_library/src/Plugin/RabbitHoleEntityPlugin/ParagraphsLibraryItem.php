<?php

namespace Drupal\rh_paragraphs_library\Plugin\RabbitHoleEntityPlugin;

use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginBase;

/**
 * Implements rabbit hole behavior for paragraphs_library_item.
 *
 * @RabbitHoleEntityPlugin(
 *  id = "rh_paragraphs_library_item",
 *  label = @Translation("Paragraphs Library Item"),
 *  entityType = "paragraphs_library_item"
 * )
 */
class ParagraphsLibraryItem extends RabbitHoleEntityPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getGlobalConfigFormId() {
    return "paragraphs_library_item_settings";
  }

}
