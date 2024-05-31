<?php

namespace Drupal\webform_node_analysis\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines local tasks.
 */
class WebformNodeAnalysisLocalTask extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * WebformNodeAnalysisLocalTasks constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    /** @var \Drupal\field\FieldConfigInterface[] $field_configs */
    $field_configs = $this->entityTypeManager->getStorage('field_config')->loadByProperties(['entity_type' => 'node']);
    foreach ($field_configs as $field_config) {
      if ($field_config->get('field_type') === 'webform') {
        $field_name = $field_config->get('field_name');
        $this->derivatives["entity.node.webform.results_analysis.$field_name"] = [
          'route_name' => 'entity.node.webform.results_analysis',
          'route_parameters' => ['field_name' => $field_name],
          'title' => $this->t('Analysis @field', ['@field' => $field_config->getLabel()]),
          'parent_id' => 'entity.node.webform.results',
        ] + $base_plugin_definition;
      }
    }

    return $this->derivatives;
  }

}
