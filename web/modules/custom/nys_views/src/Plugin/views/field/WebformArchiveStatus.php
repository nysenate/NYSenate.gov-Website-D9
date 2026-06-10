<?php

namespace Drupal\nys_views\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to display whether a referenced webform entity is archived.
 *
 * Webforms are config entities so archive status is not in any DB table;
 * this plugin loads the entity per row to retrieve it.
 *
 * @ViewsField("webform_archive_status")
 */
class WebformArchiveStatus extends FieldPluginBase {

  /**
   * Constructs a WebformArchiveStatus field plugin.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
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
  public function clickSortable(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $webform_id = $this->getValue($values);
    if (!$webform_id) {
      return '';
    }
    $webform = $this->entityTypeManager->getStorage('webform')->load($webform_id);
    if (!$webform) {
      return '';
    }
    return $webform->isArchived() ? $this->t('Yes') : $this->t('No');
  }

}
