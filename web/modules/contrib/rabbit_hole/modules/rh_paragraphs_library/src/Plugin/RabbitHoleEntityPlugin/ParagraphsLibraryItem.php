<?php

namespace Drupal\rh_paragraphs_library\Plugin\RabbitHoleEntityPlugin;

use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginBase;

/**
 * Implements rabbit hole behavior for paragraphs_library_item.
 *
 * @RabbitHoleEntityPlugin(
 *  id = "rh_paragraphs_library_item",
 *  label = @Translation("Paragraphs Library Item (deprecated)"),
 *  entityType = "paragraphs_library_item"
 * )
 *
 * @deprecated in rabbit_hole:2.0.0 and is removed from rabbit_hole:3.0.0. Content entity types are supported by default now.
 *
 * @see https://www.drupal.org/node/3359194
 */
class ParagraphsLibraryItem extends RabbitHoleEntityPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getGlobalConfigFormId() {
    return "paragraphs_library_item_settings";
  }

}
