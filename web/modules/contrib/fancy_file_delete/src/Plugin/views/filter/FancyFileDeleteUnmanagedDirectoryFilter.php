<?php

namespace Drupal\fancy_file_delete\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\fancy_file_delete\UnmanagedFilesService;

/**
 * Fancy File Delete Orphan Files Views Settings.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("ffd_unmanaged_directory_filter")
 */
class FancyFileDeleteUnmanagedDirectoryFilter extends FilterPluginBase implements ContainerFactoryPluginInterface {

  /**
   * This filter is always considered multiple-valued.
   *
   * @var bool
   */
  protected $alwaysMultiple = TRUE;

  /**
   * The active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The Unmanaged Files Service.
   *
   * @var \Drupal\fancy_file_delete\UnmanagedFilesService
   */
  protected $unmanagedFiles;

  /**
   * Constructs a new FancyFileDeleteUnmanagedDirectoryFilter.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The current database connection
   * @param \Drupal\fancy_file_delete\UnmanagedFilesService
   *   The Unmanaged Files Service
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, UnmanagedFilesService $unmanaged) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
    $this->unmanagedFiles = $unmanaged;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('database'),
      $container->get('fancy_file_delete.unmanaged_files')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $directories = $this->unmanagedFiles->getDirs();
    $chosen_dirs = $this->unmanagedFiles->getChosenDirs();

    $form['value'] = [
      '#type' => 'checkboxes',
      '#options' => array_combine($directories, $directories),
      '#default_value' => array_values($chosen_dirs),
      '#no_convert' => TRUE,
    ];
    $form['ffd_submitted'] = [
      '#type' => 'hidden',
      '#value' => 'true',
    ];

    // Set our initial value to be our saved preference.
    if (empty($form_state['input']['ffd_submitted'])) {
      $form['value']['#value'] = array_values($chosen_dirs);
      $form_state['input']['unmanaged_directories'] = array_combine($chosen_dirs, $chosen_dirs);
    }
    // Store our preference on submit.
    else {
      if (is_array($form_state['input']['unmanaged_directories'])) {
        $this->unmanagedFiles->setChosenDirs(array_keys($form_state['input']['unmanaged_directories']));
      }
      // clear the values.
      else {
        $this->unmanagedFiles->setChosenDirs([]);
      }

    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {}

}
