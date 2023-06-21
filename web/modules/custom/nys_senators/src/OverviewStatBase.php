<?php

namespace Drupal\nys_senators;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base class for senator dashboard overview stat blocks.
 */
abstract class OverviewStatBase implements OverviewStatInterface {

  /**
   * The plugin definition.
   *
   * @var array
   */
  protected array $definition = [];

  /**
   * The HTML or plain text content.
   *
   * @var string|null
   */
  protected ?string $content = '';

  /**
   * Drupal's Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $manager;

  /**
   * NYS Senators Helper service.
   *
   * @var \Drupal\nys_senators\SenatorsHelper
   */
  protected SenatorsHelper $helper;

  /**
   * Drupal's Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Constructor.
   */
  public function __construct(array $definition, EntityTypeManagerInterface $manager, SenatorsHelper $helper, Connection $database) {
    $this->definition = $definition;
    $this->manager = $manager;
    $this->helper = $helper;
    $this->database = $database;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
          $plugin_definition,
          $container->get('entity_type.manager'),
          $container->get('nys_senators.senators_helper'),
          $container->get('database')
      );
  }

  /**
   * {@inheritDoc}
   */
  public function getDefinition(): array {
    return $this->definition;
  }

  /**
   * {@inheritDoc}
   *
   * @param \Drupal\taxonomy\TermInterface $senator
   *   The senator for whom stats should be generated.
   * @param bool $refresh
   *   Forces the content to be rebuilt.
   */
  public function getContent(TermInterface $senator, bool $refresh = FALSE): ?string {
    if (!$this->content) {
      $this->content = $this->buildContent($senator);
    }
    return $this->content;
  }

  /**
   * Override to build the HTML/plain-text content.
   */
  abstract protected function buildContent(TermInterface $senator): ?string;

  /**
   * Indicates if this stat block will be a link.
   */
  public function isLink(): bool {
    return (bool) ($this->definition['url'] ?? FALSE);
  }

}
