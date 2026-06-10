<?php

namespace Drupal\nys_views\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to display the status of the referenced webform entity.
 *
 * Webforms are config entities so status is not in any DB table; this plugin
 * loads the entity per row to retrieve the raw status property ('open',
 * 'closed', or 'scheduled'). This reflects the webform's own setting, not
 * any node-level override (see webform_status for that).
 *
 * @ViewsField("webform_entity_status")
 */
class WebformEntityStatus extends FieldPluginBase {

  /**
   * Constructs a WebformEntityStatus field plugin.
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
    // $webform->status is a protected property; toArray() exposes raw config.
    $status = $webform->toArray()['status'] ?? '';
    return match($status) {
      'open' => $this->t('Open'),
      'closed' => $this->t('Closed'),
      'scheduled' => $this->t('Scheduled'),
      default => $status,
    };
  }

}
