<?php

namespace Drupal\nys_senator_dashboard\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Views field to generate flag link using contextual filter value.
 *
 * @ViewsField("contextual_filter_webform_id")
 */
class ContextualFilterWebformId extends FieldPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a ContextualFilterFlagLink object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $issue_id = $this->view?->argument['entity_id']?->argument;
    if (!empty($issue_id)) {
      try {
        $webform_id = $this->entityTypeManager
          ->getStorage('node')
          ->load($issue_id)
          ?->webform?->entity?->id();
      }
      catch (\Exception) {
      }
    }
    return $webform_id ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Intentionally do nothing here since this field doesn't need to query for
    // data.
  }

}
