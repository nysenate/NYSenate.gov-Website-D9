<?php

namespace Drupal\nys_senators;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base class for SenatorManagementPage plugins.
 */
abstract class ManagementPageBase implements ManagementPageInterface {

  /**
   * The plugin annotated definition.
   *
   * @var array
   */
  protected array $definition;

  /**
   * Plugin configuration.
   *
   * @var array
   */
  protected array $configuration;

  /**
   * Drupal's Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $manager;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $manager, $plugin_id, $definition, array $configuration) {
    $definition['id'] = (string) $plugin_id;
    $this->manager = $manager;
    $this->definition = $definition;
    $this->configuration = $configuration;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ManagementPageInterface {
    return new static(
      $container->get('entity_type.manager'),
      $plugin_id,
      $plugin_definition,
      $configuration
    );
  }

  /**
   * {@inheritDoc}
   */
  public function id(): string {
    return $this->definition['id'] ?? '';
  }

}
