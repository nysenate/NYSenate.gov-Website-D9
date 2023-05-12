<?php

namespace Drupal\nys_senators;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base class for SenatorManagementPage plugins.
 */
abstract class ManagementPageBase implements ManagementPageInterface {

  use LoggerChannelTrait;

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
   * NYS Senators Helper service.
   *
   * @var \Drupal\nys_senators\SenatorsHelper
   */
  protected SenatorsHelper $helper;

  /**
   * Drupal's Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $manager;

  /**
   * Drupal's Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * A log channel for NYS Management Dashboard.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $manager, Connection $connection, SenatorsHelper $helper, $plugin_id, $definition, array $configuration) {
    $definition['id'] = (string) $plugin_id;
    $this->manager = $manager;
    $this->definition = $definition;
    $this->configuration = $configuration;
    $this->connection = $connection;
    $this->helper = $helper;
    $this->logger = $this->getLogger('nys_management_dashboard');
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ManagementPageInterface {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('nys_senators.senators_helper'),
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
