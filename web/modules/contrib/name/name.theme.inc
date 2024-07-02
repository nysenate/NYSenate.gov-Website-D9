<?php

/**
 * @file
 * Theme functions for name module.
 */

use Drupal\Component\Utility\Html;

/**
 * Prepares variables for name_item template.
 *
 * Default template: name-item.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *     - item: Keyed array of name components.
 *     - format: The machine name of the format.
 *     - settings: Additional settings to control the parser.
 */
function template_preprocess_name_item(array &$variables) {
  $variables['settings'] += [
    'markup' => 0,
  ];
  $format = name_get_format_by_machine_name($variables['format']);
  if (empty($format)) {
    $format = name_get_format_by_machine_name('default');
  }
  $variables['formatted_name'] = \Drupal::service('name.format_parser')
    ->parse($variables['item'], $format, $variables['settings']);
}

/**
 * Prepares variables for name_item_list template.
 *
 * Default template: name-item-list.html.twig.
 * Note: This function expects a list of sanitised name items.
 *
 * @param array $variables
 *   An associative array containing:
 *     - items: Keyed array of name components.
 *     - format: The machine name of the format.
 *     - settings: Additional settings to control the parser.
 */
function template_preprocess_name_item_list(array &$variables) {
  $items = $variables['items'];
  $variables['original_count'] = count($items);
  if ($variables['original_count'] == 1) {
    $variables['item'] = array_pop($items);
    return;
  }

  $settings = $variables['settings'];
  $settings += [
    // default, plain, or raw.
    'output' => 'default',
    'multiple_delimiter' => ', ',
    // And or symbol.
    'multiple_and' => 'text',
    // contextual, always, never.
    'multiple_delimiter_precedes_last' => 'never',
    'multiple_el_al_min' => 3,
    'multiple_el_al_first' => 1,
  ];
  $variables['settings'] = $settings;
  $variables['delimiter'] = $settings['multiple_delimiter'];
  if ($settings['output'] == 'default') {
    $variables['etal'] = t('<em>et al</em>', [], ['context' => 'name']);
    $variables['delimiter'] = Html::escape($variables['delimiter']);
  }
  else {
    $variables['etal'] = t('et al', [], ['context' => 'name']);
    if ($settings['output'] == 'plain') {
      $variables['delimiter'] = strip_tags($variables['delimiter']);
    }
  }

  if ($variables['original_count'] > $settings['multiple_el_al_min']) {
    $limit = min([
      $settings['multiple_el_al_min'],
      $settings['multiple_el_al_first'],
    ]);
    $items = array_slice($items, 0, $limit);
    $variables['items_count'] = count($items);
    if ($variables['items_count'] == 1) {
      $variables['name'] = $items[0];
    }
    else {
      $variables['names'] = implode($variables['delimiter'] . ' ', $items);
    }
  }
  else {
    $variables['lastname'] = array_pop($items);
    $variables['names'] = implode($variables['delimiter'] . ' ', $items);
    if ($settings['multiple_and'] == 'text') {
      $variables['and_'] = t('and', [], ['context' => 'name']);
    }
    else {
      $variables['and_'] = $settings['output'] == 'default' ? '&amp' : '&';
    }
  }
}
