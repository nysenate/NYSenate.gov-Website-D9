<?php

/**
 * @file
 * Hooks provided by the File (Field) Paths module.
 */

/**
 * Form settings hook.
 *
 * Define field(s) to be displayed on the File (Field) Paths settings form and
 * used during the processing of uploaded files.
 *
 * @param $field
 *   The field definition this File (Field) Paths settings field applies to.
 * @param $instance
 *   The field instance this File (Field) Paths settings field applies to.
 *
 * @return array
 *   An array whose keys are field names and whose values are arrays defining
 *   the field, with the following key/value pairs:
 *   - title: The title fo the field.
 *   - form: A keyed array of Form API elements.
 *
 * @see hook_filefield_paths_process_file()
 */
function hook_filefield_paths_field_settings($field, $instance) {
  return [
    'file_path' => [
      'title' => 'File path',
      'form' => [
        'value' => [
          '#type' => 'textfield',
          '#title' => t('File path'),
          '#maxlength' => 512,
          '#size' => 128,
          '#element_validate' => ['_file_generic_settings_file_directory_validate'],
          '#default_value' => $instance['settings']['file_directory'],
        ],
      ],
    ],
  ];
}

/**
 * Process the uploaded files.
 *
 * @param $type
 *   The entity type containing the files for processing.
 * @param $entity
 *   The entity containing the files for processing.
 * @param $field
 *   The definition of the field containing the files for processing.
 * @param $instance
 *   The instance of the field containing the files for processing.
 * @param $langcode
 *   The language code of the field containing the files for processing.
 * @param $items
 *   A pass-by-reference array of all the files for processing.
 *
 * @see filefield_paths_filefield_paths_process_file()
 */
function hook_filefield_paths_process_file($type, $entity, $field, $instance, $langcode, &$items) {
}
