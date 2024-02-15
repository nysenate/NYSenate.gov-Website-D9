<?php

/**
 * @file
 * Builds placeholder replacement tokens for node scheduler data.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function scheduler_token_info() {

  $plugin_types = \Drupal::service('scheduler.manager')->getPluginEntityTypes();
  // Initialise the array to avoid 'variable is undefined' phpcs error.
  $info = [];
  foreach ($plugin_types as $type) {
    $info['tokens'][$type]['scheduler-publish'] = [
      'name' => t('Publish on date'),
      'description' => t("The date the %type will be published.", ['%type' => $type]),
      'type' => 'date',
    ];
    $info['tokens'][$type]['scheduler-unpublish'] = [
      'name' => t('Unpublish on date'),
      'description' => t("The date the %type will be unpublished.", ['%type' => $type]),
      'type' => 'date',
    ];
  }

  return $info;
}

/**
 * Implements hook_tokens().
 */
function scheduler_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();
  $date_formatter = \Drupal::service('date.formatter');
  $language_code = $options['langcode'] ?? NULL;
  $replacements = [];

  $plugin_types = \Drupal::service('scheduler.manager')->getPluginEntityTypes();

  if (in_array($type, $plugin_types) && !empty($data[$type])) {
    $entity = $data[$type];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'scheduler-publish':
          if (isset($entity->publish_on->value)) {
            $replacements[$original] = $date_formatter->format($entity->publish_on->value, 'medium', '', NULL, $language_code);
          }
          break;

        case 'scheduler-unpublish':
          if (isset($entity->unpublish_on->value)) {
            $replacements[$original] = $date_formatter->format($entity->unpublish_on->value, 'medium', '', NULL, $language_code);
          }
          break;
      }
    }

    // Chained token replacement.
    if (isset($entity->publish_on->value) && $publish_tokens = $token_service->findWithPrefix($tokens, 'scheduler-publish')) {
      $replacements += $token_service->generate('date', $publish_tokens, ['date' => $entity->publish_on->value], $options, $bubbleable_metadata);
    }
    if (isset($entity->unpublish_on->value) && $unpublish_tokens = $token_service->findWithPrefix($tokens, 'scheduler-unpublish')) {
      $replacements += $token_service->generate('date', $unpublish_tokens, ['date' => $entity->unpublish_on->value], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
