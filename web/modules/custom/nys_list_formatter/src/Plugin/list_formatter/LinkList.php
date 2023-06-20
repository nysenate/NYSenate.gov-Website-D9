<?php

namespace Drupal\nys_list_formatter\Plugin\list_formatter;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\nys_list_formatter\Plugin\ListFormatterListInterface;

/**
 * Plugin implementation of the 'link' list formatter.
 *
 * @ListFormatter(
 *   id = "link",
 *   module = "link",
 *   field_types = {"link"},
 *   settings = {
 *     "link_target" = "FALSE",
 *     "link_trim_length" = "80" ,
 *   }
 * )
 */
class LinkList implements ListFormatterListInterface {

  /**
   * Implements ListFormatterListInterface::createList().
   */
  public function createList(FieldItemListInterface $items, FieldDefinitionInterface $field_definition, FormatterInterface $formatter, $langcode) {
    $list_items = [];

    foreach ($items as $delta => $item) {
      $contrib_settings = $formatter->getSetting('list_formatter_contrib');

      $link_title = !empty($item->title) ? $item->title : $item->getUrl()->toString();
      $link_url = $item->getUrl();

      // Trim the link text to the desired length.
      if (!empty($contrib_settings['link_trim_length'])) {
        $link_title = Unicode::truncate($link_title, $contrib_settings['link_trim_length'], FALSE, TRUE);
      }

      $options = [];
      $settings_link = $contrib_settings['link_target'] ?? '';
      if ($settings_link) {
        $options['attributes']['target'] = '_blank';
      }

      $list_items[] = [
        '#type' => 'link',
        '#url' => $link_url,
        '#title' => $link_title,
        '#options' => $options,
      ];
    }

    return $list_items;
  }

  /**
   * Implements additional settings.
   */
  public function additionalSettings(array &$elements, FieldDefinitionInterface $field_definition, FormatterInterface $formatter) {
    if ($field_definition->getType() == 'link') {
      $settings = $formatter->getSetting('list_formatter_contrib');
      $elements['list_formatter_contrib']['link_trim_length'] = [
        '#type' => 'number',
        '#title' => t('Trim link text length'),
        '#description' => t('Leave blank to allow unlimited link text lengths.'),
        '#default_value' => $settings['link_trim_length'] ?? 80,
      ];
      $elements['list_formatter_contrib']['link_target'] = [
        '#type' => 'checkbox',
        '#title' => t('Open links in new window'),
        '#default_value' => $settings['link_target'] ?? FALSE,
      ];
    }
  }

}
