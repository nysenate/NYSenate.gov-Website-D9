<?php

/**
 * @file
 * File (Field) Paths module integration.
 */

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;

/**
 * Implements hook_filefield_paths_field_settings().
 */
function filefield_paths_filefield_paths_field_settings($form) {
  return [
    'file_path' => [
      'title' => 'File path',
      'form' => [
        'value' => [
          '#type' => 'textfield',
          '#title' => t('File path'),
          '#maxlength' => 512,
          '#size' => 128,
          '#element_validate' => $form['settings']['file_directory']['#element_validate'],
          '#default_value' => $form['settings']['file_directory']['#default_value'],
        ],
      ],
    ],
    'file_name' => [
      'title' => 'File name',
      'form' => [
        'value' => [
          '#type' => 'textfield',
          '#title' => t('File name'),
          '#maxlength' => 512,
          '#size' => 128,
          '#default_value' => '[file:ffp-name-only-original].[file:ffp-extension-original]',
        ],
      ],
    ],
  ];
}

/**
 * Implements hook_filefield_paths_process_file().
 */
function filefield_paths_filefield_paths_process_file(ContentEntityInterface $entity, FileFieldItemList $field, array $settings = []) {
  /** @var \Drupal\field\Entity\FieldConfig $field_config */
  $field_config = $field->getFieldDefinition();
  /** @var \Drupal\field\Entity\FieldStorageConfig $field_storage */
  $field_storage = $field_config->getFieldStorageDefinition();

  $file_system = \Drupal::service('file_system');

  // Check that the destination is writeable.
  $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
  $wrappers = $stream_wrapper_manager->getWrappers(StreamWrapperInterface::WRITE);

  $destination_scheme_name = $field_storage->getSetting('uri_scheme');
  /** @var \Drupal\Core\StreamWrapper\StreamWrapperInterface $temporary_scheme */
  $temporary_scheme = $stream_wrapper_manager->getViaUri(\Drupal::config('filefield_paths.settings')
    ->get('temp_location'));
  $temporary_scheme_name = key($stream_wrapper_manager->getWrappers($temporary_scheme->getType()));
  $schemas = [$temporary_scheme_name, $destination_scheme_name];

  /** @var \Drupal\file\Entity\File $file */
  foreach ($field->referencedEntities() as $file) {
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperInterface $source_scheme */
    $source_scheme = $stream_wrapper_manager->getViaUri($file->getFileUri());
    $source_scheme_name = key($stream_wrapper_manager->getWrappers($source_scheme->getType()));
    if (in_array($source_scheme_name, $schemas) && !empty($wrappers[$destination_scheme_name])) {
      // Process file if this is a new entity, 'Active updating' is set or
      // file wasn't previously attached to the entity.
      if (!$entity->isNew() && isset($entity->original) && empty($settings['active_updating']) && !$entity->original->{$field->getName()}->isEmpty()) {
        /** @var \Drupal\file\Entity\File $original_file */
        foreach ($entity->original->{$field->getName()}->referencedEntities() as $original_file) {
          if ($original_file->id() == $file->id()) {
            continue(2);
          }
        }
      }

      $token_data = [
        'file' => $file,
        $entity->getEntityTypeId() => $entity,
      ];

      // Process filename.
      $settings['file_name']['options']['context'] = 'file_name';
      $name = $file->getFilename();
      if (!empty($settings['file_name']['value'])) {
        $name = filefield_paths_process_string($settings['file_name']['value'], $token_data, $settings['file_name']['options']);
      }

      // Process filepath.
      $settings['file_path']['options']['context'] = 'file_path';
      $path = filefield_paths_process_string($settings['file_path']['value'], $token_data, $settings['file_path']['options']);

      $destination = \Drupal::service('stream_wrapper_manager')
        ->normalizeUri($field_storage->getSetting('uri_scheme') . '://' . $path . '/' . $name);

      // Ensure file uri is no more than 255 characters.
      if (mb_strlen($destination) > 255) {
        \Drupal::logger('filefield_paths')->info(t('File path was truncated'));
        $pathinfo = pathinfo($destination);
        $destination = mb_substr($destination, 0, 254 - mb_strlen($pathinfo['extension'])) . ".{$pathinfo['extension']}";
      }

      // Finalize file if necessary.
      if ($file->getFileUri() !== $destination) {
        $dirname = $file_system->dirname($destination);
        if (
          \Drupal::service('file_system')->prepareDirectory($dirname, FileSystem::CREATE_DIRECTORY)
          && $new_file = \Drupal::service('file.repository')->move($file, $destination)
        ) {
          // Create redirect from old location.
          if (
            \Drupal::moduleHandler()->moduleExists('redirect')
            && !empty($settings['redirect']) && $settings['active_updating']
          ) {
            \Drupal::service('filefield_paths.redirect')
              ->createRedirect($file->getFileUri(), $new_file->getFileUri(), $file->language());
          }

          // Remove any old empty directories.
          // @TODO - Add test for this.
          $paths = explode('/', str_replace("{$source_scheme_name}://", '', $file_system->dirname($file->getFileUri())));
          while ($paths) {
            if (@$file_system->rmdir("{$source_scheme_name}://" . implode('/', $paths)) == TRUE) {
              array_pop($paths);
              continue;
            }
            break;
          }
        }
        else {
          \Drupal::logger('filefield_paths')
            ->notice(t('The file %old could not be moved to the destination of %new. Ensure your permissions are set correctly.', [
              '%old' => $file->getFileUri(),
              '%new' => $file->getFileUri(),
            ]));
        }
      }
    }
  }
}
