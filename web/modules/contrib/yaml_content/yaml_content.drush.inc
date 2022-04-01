<?php

/**
 * @file
 * Drush commands for the yaml_content module.
 *
 * @todo Re-implement as universal command compatible with Drupal console.
 */

/**
 * Implements hook_drush_command().
 */
function yaml_content_drush_command() {
  $items = [];

  $items['yaml-content-import'] = [
    'description' => dt('Import yaml content.'),
    'aliases' => ['yci'],
    'arguments' => [
      'directory' => dt('The directory path where content files may be found.'),
      'file' => dt('(Optional) The name of a content file to be imported.'),
    ],
    'options' => [
      'create-new' => dt('Set this to create content even if it is already in the system.'),
    ],
  ];

  $items['yaml-content-import-module'] = [
    'description' => dt('Import yaml content from a module.'),
    'aliases' => ['ycim'],
    'arguments' => [
      'module' => dt('The machine name of a module to be searched for content.'),
      'file' => dt('(Optional) The name of a content file to be imported.'),
    ],
    'options' => [
      'create-new' => dt('Set this to create content even if it is already in the system.'),
    ],
  ];

  $items['yaml-content-import-profile'] = [
    'description' => dt('Import yaml content from a profile.'),
    'aliases' => ['ycip'],
    'arguments' => [
      'profile' => dt('The machine name of a profile to be searched for content.'),
      'file' => dt('(Optional) The name of a content file to be imported.'),
    ],
    'options' => [
      'create-new' => dt('Set this to create content even if it is already in the system.'),
    ],
  ];

  return $items;
}

/**
 * Import specified yaml content file(s).
 *
 * @param string $directory
 *   The directory path containing the yaml content file(s) to be imported.
 * @param string $file
 *   (Optional) The name of a file to be imported or an array of files to
 *   import. If this argument is not provided then all files in the directory
 *   matching `*.content.yml` are queued for import.
 *
 * @see \Drupal\yaml_content\Service\LoadHelper::importDirectory()
 */
function drush_yaml_content_import($directory, $file = NULL) {
  $load_helper = \Drupal::service('yaml_content.load_helper');
  $load_helper->importDirectory($directory, $file);
}

/**
 * Import specified yaml content file(s) from a designated module.
 *
 * @param string $module
 *   The module to look for content files within.
 *
 *   This command assumes files will be contained within a `content/` directory
 *   at the top of the module's main directory. Any files within matching the
 *   pattern `*.content.yml` will then be imported.
 * @param string|string[] $file
 *   (Optional) The name of a file to be imported or an array of files to
 *   import. If this argument is not provided then all files in the directory
 *   matching `*.content.yml` are queued for import.
 *
 * @see \Drupal\yaml_content\Service\LoadHelper::importModule()
 */
function drush_yaml_content_import_module($module, $file = NULL) {
  $load_helper = \Drupal::service('yaml_content.load_helper');
  $load_helper->importModule($module, $file);
}

/**
 * Import specified yaml content file(s) from a designated profile.
 *
 * @param string $profile
 *   The profile to look for content files within.
 *
 *   This command assumes files will be contained within a `content/` directory
 *   at the top of the profiles's main directory. Any files within matching the
 *   pattern `*.content.yml` will then be imported.
 * @param string|string[] $file
 *   (Optional) The name of a file to be imported or an array of files to
 *   import. If this argument is not provided then all files in the directory
 *   matching `*.content.yml` are queued for import.
 *
 * @see \Drupal\yaml_content\Service\LoadHelper::importProfile()
 */
function drush_yaml_content_import_profile($profile, $file = NULL) {
  $load_helper = \Drupal::service('yaml_content.load_helper');
  $load_helper->importProfile($profile, $file);
}

