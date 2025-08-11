<?php

namespace Drupal\nys_senator_dashboard\Plugin\views\sort;

use Drupal\Core\Database\Connection;
use Drupal\views\Plugin\views\join\Standard;
use Drupal\views\Plugin\views\query\Sql;
use Drupal\views\Plugin\views\sort\SortPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sorts taxonomy terms by the number of 'follow_issue' type flaggings.
 *
 * @ViewsSort("nys_senator_dashboard_issue_followers_count_sort")
 */
class IssueFollowersCountSort extends SortPluginBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a TermFlaggingCountSort object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setRelationship() {
    if (!$this->query instanceof Sql) {
      return;
    }

    if (array_key_exists('flag_counts', $this->query->query()->getTables())) {
      return;
    }

    $this->ensureMyTable();
    $configuration = [
      'type' => 'LEFT',
      'table' => 'flag_counts',
      'field' => 'entity_id',
      'left_table' => $this->tableAlias,
      'left_field' => 'tid',
    ];
    $join = new Standard(
      $configuration,
      $this->getPluginId(),
      $this->getPluginDefinition(),
    );
    $this->query->addRelationship('flag_counts', $join, $this->tableAlias);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (!$this->query instanceof Sql) {
      return;
    }

    // Optimizes performance by ensuring a 1 to 1 relationship with term table.
    $this->query->addWhere(0, 'flag_counts.flag_id', 'follow_issue');

    $this->query->addOrderBy('flag_counts', 'count', $this->options['order']);
  }

}
