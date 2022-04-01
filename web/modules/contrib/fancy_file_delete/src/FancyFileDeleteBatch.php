<?php

namespace Drupal\fancy_file_delete;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Batch\BatchBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\file\Entity\File;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class FancyFileDeleteBatch.
 */
class FancyFileDeleteBatch {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * The active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;


  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new FancyFileDeleteBatch.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The current database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entityTypeManager, MessengerInterface $messenger, TranslationInterface $string_translation, FileSystemInterface $file_system) {
    $this->database = $database;
    $this->entityTypeManager = $entityTypeManager;
    $this->messenger = $messenger;
    $this->stringTranslation = $string_translation;
    $this->fileSystem = $file_system;
  }

  /**
   * Sets the batch operations.
   *
   * @param array $values
   *   The array of values we are looking to set in the batch.
   * @param bool $force
   *   If we are forcing the delete.
   * @param bool $ui
   *   If we are running this through the UI or CLI.
   */
  public function setBatch($values, $force, $ui = TRUE) {
    // Sets up our batch.
    $batch_builder = new BatchBuilder();
    $batch_builder
      ->setTitle($this->t('Deleting Files...'))
      ->setInitMessage($this->t('Fun Stuff is Happening...'))
      ->setErrorMessage($this->t('Fancy File Delete has encountered an error.'))
      ->setFinishCallback([$this, 'finish']);

    foreach ($values as $value) {
      $batch_builder->addOperation([$this, 'process'], [$value, $force]);
    }

    // Engage.
    batch_set($batch_builder->toArray());

    if (!$ui) {
      $batch = &batch_get();
      $batch['progressive'] = FALSE;

      // Start the process.
      drush_backend_batch_process();
    }
  }

  /**
   * Batch process function.
   */
  public function process($fid, $force, &$context) {
    // Update our progress information.
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
    }
    $context['sandbox']['progress']++;

    // Manual / Orphan Delete.
    if (is_numeric($fid)) {
      $file = File::load($fid);

      if ($file) {
        if ($force) {
          // Remove these from the DB.
          $this->database->delete('file_managed')
            ->condition('fid', $fid)
            ->execute();
          $this->database->delete('file_usage')
            ->condition('fid', $fid)
            ->execute();
          // Now Delete the file completely.
          // Skip file api and just delete the entity, quicker.
          $controller = $this->entityTypeManager->getStorage('file');
          $entity = $controller->loadMultiple([$fid]);
          $controller->delete($entity);
        }
        else {
          $result = $file->delete();
          if (is_array($result)) {
            // The file is still being referenced.
            // So it can not be forcefully deleted.
            // Notify the user instead.
            $context['results']['error'][] = array(
              'fid' => $fid,
              'message' => $this->t('The file with fid#%fid cannot be delete because it
            is still referenced in the file_usage table. %file_usage', array(
                '%fid' => $fid,
                '%file_usage' => print_r($result, TRUE),
              )),
            );
          }
          else {
            $context['results'][] = $fid;
          }
        }
      }
    }
    // Delete unmanaged.
    else {
      // @todo fix this to be the new way.
      $this->database->delete('unmanaged_files')
        ->condition('path', $fid)
        ->execute();

      $this->fileSystem->delete($fid);
      $context['results'][] = $fid;
    }
    // Set the processing message.
    $context['message'] = $this->t('Now cleansing the system of fid#%fid', ['%fid' => $fid]);
  }

  /**
   * Batch finished.
   */
  public function finish($success, $results, $operations) {
    if ($success) {
      // Reset the cache.
      $this->entityTypeManager->getStorage('file')->resetCache();
      $message = $this->stringTranslation->formatPlural(count($results), 'One file cleansed.', '@count files cleansed.');
    }
    else {
      $message = $this->t('Assimilation was futile!');
    }

    $this->messenger->addMessage($message);

    return new RedirectResponse(Url::fromRoute('fancy_file_delete.info')->toString());
  }
}
