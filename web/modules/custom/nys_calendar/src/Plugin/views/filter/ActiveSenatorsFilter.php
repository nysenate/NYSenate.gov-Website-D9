<?php

namespace Drupal\nys_calendar\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Active senators filter.
 */
#[ViewsFilter("active_senators_filter")]
class ActiveSenatorsFilter extends FilterPluginBase {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  public $entityTypeManager;

  /**
   * Constructs a ActiveSenatorsFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
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
  public function query() {
    $value = is_array($this->value) ? reset($this->value) : $this->value;
    if ($value === NULL || $value === '' || $value === 'All') {
      return;
    }
    $this->ensureMyTable();
    $this->query->addWhere($this->options['group'], "$this->tableAlias.$this->realField", $value, '=');
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    try {
      $taxonomy_storage = $this->entityTypeManager
        ->getStorage('taxonomy_term');
    }
    catch (\Exception) {
      return;
    }
    $options = [];
    $active_senators_tids = $taxonomy_storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('vid', 'senator')
      ->condition('field_active_senator', TRUE)
      ->sort('field_senator_name.family')
      ->execute();
    if (!empty($active_senators_tids)) {
      $active_senators = $taxonomy_storage->loadMultiple($active_senators_tids);
      foreach ($active_senators as $senator) {
        $name = $senator->hasField('field_senator_name') ? $senator->get('field_senator_name')->first() : NULL;
        $family = $name?->family;
        $given = $name?->given;
        $options[$senator->id()] = ($family && $given) ? "$family, $given" : $senator->label();
      }
    }
    $form['value'] = [
      '#type' => 'select',
      '#title' => 'Active senators',
      '#options' => ['All' => '- Any -'] + $options,
      '#default_value' => 'All',
    ];
  }

}
