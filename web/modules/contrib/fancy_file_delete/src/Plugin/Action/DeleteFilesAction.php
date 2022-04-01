<?php

namespace Drupal\fancy_file_delete\Plugin\Action;

use Drupal\fancy_file_delete\FancyFileDeleteBatch;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\fancy_file_delete\Entity\UnmanagedFiles;
use Drupal\file\Entity\File;

/**
 * Deletes Files.
 *
 * @Action(
 *   id = "delete_files_action",
 *   label = @Translation("Delete Files"),
 *   type = "",
 *   pass_view = TRUE
 * )
 */
class DeleteFilesAction extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface {

  /**
   * The Batch Service.
   *
   * @var \Drupal\fancy_file_delete\FancyFileDeleteBatch
   */
  protected $batch;

  /**
   * Constructs a new DeleteFilesAction action.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\fancy_file_delete\FancyFileDeleteBatch
   *   The Batch Service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FancyFileDeleteBatch $batch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->batch = $batch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('fancy_file_delete.batch')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    // Set entities to batch our way.
    $values = [];
    foreach ($entities as $entity) {
      if ($entity instanceof UnmanagedFiles) {
        $values[] = $entity->getPath();
      }
      elseif ($entity instanceof File) {
        $values[] = $entity->id();
      }
    }
    // Send to batch.
    $this->batch->setBatch($values, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $this->executeMultiple([$entity]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('delete', $account, $return_as_object);
  }
}
