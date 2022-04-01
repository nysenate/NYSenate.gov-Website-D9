<?php

namespace Drupal\fancy_file_delete\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;

/**
 * Fancy File Delete Orphan Files Views Settings.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("ffd_orphan_filter")
 */
class FancyFileDeleteOrphanFileFilter extends FilterPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new FancyFileDeleteOrphanFileFilter.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The current database connection.
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
      $configuration, $plugin_id, $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    // Hide default behavior just in case.
    $form['expose_button']['#access'] = FALSE;
    $form['more']['#access'] = FALSE;

    $form['orphan_text'] = [
      '#type' => 'item',
      '#markup' => $this->t('This is just a custom query filter no need for any configuration.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $table = $this->ensureMyTable();

    $query = "SELECT fm.* FROM {file_managed} AS fm LEFT OUTER JOIN {file_usage}
    AS fu ON (fm.fid = fu.fid) LEFT OUTER JOIN {node} AS n ON (fu.id = n.nid)
    WHERE fu.type = 'node' AND n.nid IS NULL";

    $results = $this->database->query($query)->fetchAll();

    if (isset($results) && count($results) > 0) {
      foreach ($results as $result) {
        $files[] = $result->fid;
      }
      $this->query->addWhere($this->options['group'], $table . '.fid', $files, 'IN');
    }
    else {
      // No Results, return NULL, carry on.
      $this->query->addWhere($this->options['group'], $table . '.fid', NULL, '=');
    }
  }

}
