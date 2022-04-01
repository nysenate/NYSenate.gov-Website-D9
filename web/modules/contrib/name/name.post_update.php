<?php

/**
 * @file
 * Post update functions for Name.
 */

/**
 * Adds the default list format.
 */
function name_post_update_create_name_list_format() {
  /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $name_list_format_storage */
  $name_list_format_storage = \Drupal::entityTypeManager()->getStorage('name_list_format');

  $default_list = $name_list_format_storage->load('default');
  if ($default_list) {
    if (!$default_list->locked) {
      $default_list->locked = TRUE;
      $default_list->save();
      $message = t('Default name list format was set to locked.');
    }
    else {
      $message = t('Nothing required to action.');
    }
  }
  else {
    $name_list_format = $name_list_format_storage->create([
      'id' => 'default',
      'label' => 'Default',
      'locked' => TRUE,
      'status' => TRUE,
      'delimiter' => ', ',
      'and' => 'text',
      'delimiter_precedes_last' => 'never',
      'el_al_min' => 3,
      'el_al_first' => 1,
    ]);
    $name_list_format->save();
    $message = t('Default name list format was added.');
  }

  return $message;
}

/**
 * Corrects the field formatter settings for new name list type settings.
 */
function name_post_update_formatter_settings() {
  $field_storage_configs = \Drupal::entityTypeManager()
    ->getStorage('field_storage_config')
    ->loadByProperties(['type' => 'name']);
  $default_settings = [
    "format" => "default",
    "markup" => FALSE,
    "output" => "default",
    "list_format" => "default",
  ];

  foreach ($field_storage_configs as $field_storage) {
    $field_name = $field_storage->getName();
    $fields = \Drupal::entityTypeManager()
      ->getStorage('field_config')
      ->loadByProperties(['field_name' => $field_name]);
    foreach ($fields as $field) {
      $properties = [
        'targetEntityType' => $field->getTargetEntityTypeId(),
        'bundle' => $field->getTargetBundle(),
      ];
      $view_displays = \Drupal::entityTypeManager()
        ->getStorage('entity_view_display')
        ->loadByProperties($properties);
      foreach ($view_displays as $view_display) {
        if ($component = $view_display->getComponent($field_name)) {
          $settings = (array) $component->settings;
          $settings['list_format'] = isset($settings['multiple']) && $settings['multiple'] == 'default' ? '' : 'default';
          $settings = array_intersect_key($settings, $default_settings);
          $settings += $default_settings;
          $view_display->setComponent($field_name, [
            'type' => 'name_default',
            'settings' => $settings,
          ] + $component)->save();
        }
      }
    }
  }

  return t('New name list formatter settings are implemented. Please review any name display settings that used inline lists.');
}

/**
 * Updates the field formatter settings.
 *
 * Adds new link and alternative data sources settings.
 */
function name_post_update_formatter_settings_link_and_external_sources() {
  $new_settings = [
    "format" => "default",
    "markup" => "none",
    "list_format" => "",
    "link_target" => "",
    "preferred_field_reference" => "",
    "preferred_field_reference_separator" => ", ",
    "alternative_field_reference" => "",
    "alternative_field_reference_separator" => ", ",
  ];
  $field_storage_configs = \Drupal::entityTypeManager()
    ->getStorage('field_storage_config')
    ->loadByProperties(['type' => 'name']);
  foreach ($field_storage_configs as $field_storage) {
    $field_name = $field_storage->getName();
    $fields = \Drupal::entityTypeManager()
      ->getStorage('field_config')
      ->loadByProperties(['field_name' => $field_name]);
    foreach ($fields as $field) {
      $properties = [
        'targetEntityType' => $field->getTargetEntityTypeId(),
        'bundle' => $field->getTargetBundle(),
      ];
      $view_displays = \Drupal::entityTypeManager()
        ->getStorage('entity_view_display')
        ->loadByProperties($properties);
      foreach ($view_displays as $view_display) {
        /* @var \Drupal\Core\Entity\Entity\EntityViewDisplay $view_display */
        if ($component = $view_display->getComponent($field_name)) {
          $settings = (array) $component['settings'];
          if (empty($settings['markup']) || $settings['markup'] == '1') {
            $settings['markup'] = empty($settings['markup']) ? 'none' : 'simple';
          }
          if (isset($settings['output'])) {
            unset($settings['output']);
          }
          $settings += $new_settings;
          $view_display->setComponent($field_name, [
            'type' => 'name_default',
            'settings' => $settings,
          ] + $component)->save();
        }
      }
    }
  }
}

/**
 * Merges the custom field and storage settings together.
 */
function name_post_update_field_settings_merge() {
  $merged_fields = [
    'components',
    'minimum_components',
    'max_length',
    'labels',
    'allow_family_or_given',
    'autocomplete_source',
    'autocomplete_separator',
    'title_options',
    'generational_options',
    'sort_options',
  ];
  $merged_fields = array_combine($merged_fields, $merged_fields);

  $field_storage_configs = \Drupal::entityTypeManager()
    ->getStorage('field_storage_config')
    ->loadByProperties(['type' => 'name']);
  foreach ($field_storage_configs as $field_storage) {
    /* @var \Drupal\field\Entity\FieldStorageConfig $field_storage */
    $storage_settings = $field_storage->getSettings();
    $merged_settings = array_intersect_key($storage_settings, $merged_fields);
    $field_name = $field_storage->getName();
    $fields = \Drupal::entityTypeManager()
      ->getStorage('field_config')
      ->loadByProperties(['field_name' => $field_name]);
    foreach ($fields as $field) {
      /* @var \Drupal\field\Entity\FieldConfig $field */
      $field_settings = $field->getSettings();
      $field_settings += $merged_settings;
      $field->setSettings($field_settings)->save();
    }
    $storage_settings = array_diff_key($storage_settings, $merged_fields);
    $field_storage->setSettings($storage_settings)->save();
  }
}

/**
 * Removes the inline CSS settings and sets the widget layout.
 */
function name_post_update_field_settings_remove_inline_css() {
  $field_storage_configs = \Drupal::entityTypeManager()
    ->getStorage('field_storage_config')
    ->loadByProperties(['type' => 'name']);
  foreach ($field_storage_configs as $field_storage) {
    /* @var \Drupal\field\Entity\FieldStorageConfig $field_storage */
    $field_name = $field_storage->getName();
    $fields = \Drupal::entityTypeManager()
      ->getStorage('field_config')
      ->loadByProperties(['field_name' => $field_name]);
    foreach ($fields as $field) {
      /* @var \Drupal\field\Entity\FieldConfig $field */
      $field_settings = $field->getSettings();
      unset($field_settings['inline_css']);
      unset($field_settings['component_css']);
      if (empty($field_settings['widget_layout'])) {
        $field_settings['widget_layout'] = 'stacked';
      }
      $field->setSettings($field_settings)->save();
    }
  }

  \Drupal::service('config.factory')->getEditable('name.settings')
    ->clear('element_wrapper')
    ->clear('inline_styles')
    ->clear('inline_styles_rtl')
    ->save();
}
