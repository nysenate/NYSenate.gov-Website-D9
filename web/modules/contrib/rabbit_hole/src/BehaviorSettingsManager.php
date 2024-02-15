<?php

namespace Drupal\rabbit_hole;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rabbit_hole\Entity\BehaviorSettings;

/**
 * Provides operations for bundles configuration.
 */
class BehaviorSettingsManager implements BehaviorSettingsManagerInterface {

  /**
   * The field name where entity settings are stored.
   *
   * @var string
   */
  const FIELD_NAME = 'rabbit_hole__settings';

  protected ConfigFactoryInterface $configFactory;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected EntityHelper $entityHelper;

  /**
   * Constructs a new BehaviorSettingsManager instance.
   */
  public function __construct(ConfigFactory $config_factory, EntityTypeManagerInterface $entity_type_manager, EntityHelper $entity_helper) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityHelper = $entity_helper;
  }

  /**
   * {@inheritdoc}
   */
  public function entityTypeIsEnabled(string $entity_type_id): bool {
    $config = $this->configFactory->get('rabbit_hole.settings');
    return in_array($entity_type_id, $config->get('enabled_entity_types') ?? [], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function enableEntityType(string $entity_type_id): void {
    $config = $this->configFactory->getEditable('rabbit_hole.settings');
    $enabled_entity_types = $config->get('enabled_entity_types');

    if (!in_array($entity_type_id, $enabled_entity_types, TRUE)) {
      $enabled_entity_types[] = $entity_type_id;
      $config->set('enabled_entity_types', $enabled_entity_types);
      $config->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function disableEntityType(string $entity_type_id): void {
    $config = $this->configFactory->getEditable('rabbit_hole.settings');
    $enabled_entity_types = $config->get('enabled_entity_types');
    $config->set('enabled_entity_types', array_diff($enabled_entity_types, [$entity_type_id]));
    $config->save();

    // Delete all bundle settings for disabled entity type.
    $bundle_settings = $this->entityTypeManager->getStorage('behavior_settings')->loadByProperties([
      'targetEntityType' => $entity_type_id,
    ]);

    foreach ($bundle_settings as $bundle_config) {
      $bundle_config->delete();
    }

    // Remove entity fields if they exist.
    foreach (array_keys($this->entityHelper->getBundleInfo($entity_type_id)) as $bundle_name) {
      $this->entityHelper->removeRabbitHoleField($entity_type_id, $bundle_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function saveBehaviorSettings(array $settings, $entity_type_id, $bundle = NULL): void {
    $config = BehaviorSettings::loadByEntityTypeBundle($entity_type_id, $bundle);
    foreach ($settings as $key => $setting) {
      $config->set($key, $setting);
    }
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getBehaviorSettings(string $entity_type_id, string $bundle): array {
    // Since this method is deprecated, it should be used only in our update
    // functions. It prioritises config using the previous format.
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    $bundle_entity_type = $entity_type->getBundleEntityType();

    $id = ($bundle_entity_type ?? $entity_type_id) . (isset($bundle_entity_type) ? '_' . $bundle : '');
    $config = BehaviorSettings::load($id);
    if (empty($config)) {
      $config = BehaviorSettings::loadByEntityTypeBundle($entity_type_id, $bundle);
    }
    if (!empty($config) && !$config->isNew()) {
      $settings = $config->getSettings();
      $settings['allow_override'] = $config->get('allow_override');
      return $settings;
    }

    // @todo Remove usage of default config in 3.0.
    // Also, there shouldn't be a need for default configuration in the next
    // version. If configuration is not available, it should be simply ignored.
    $default = $this->configFactory->get('rabbit_hole.behavior_settings.default');
    return !$default->isNew() ? $default->get() : [
      'action' => 'display_page',
      'no_bypass' => FALSE,
      'bypass_message' => FALSE,
      'configuration' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityBehaviorSettings(ContentEntityInterface $entity): array {
    $config = BehaviorSettings::loadByEntityTypeBundle($entity->getEntityTypeId(), $entity->bundle());
    $values = [
      'no_bypass' => $config->getNoBypass(),
      'bypass_message' => $config->getBypassMessage(),
    ];

    // We trigger the default bundle action under the following circumstances:
    $trigger_default_bundle_action =
      // Entity does not have Rabbit Hole field.
      !$entity->hasField(self::FIELD_NAME)
      // Entity has the field, but it's null (hasn't been set).
      || $entity->get(self::FIELD_NAME)->action == NULL
      // Entity has been explicitly set to use the default bundle action.
      || $entity->get(self::FIELD_NAME)->action == 'bundle_default';

    if ($trigger_default_bundle_action) {
      $values['action'] = $config->getAction();
      // Other properties are stored in "configuration" array.
      $values = array_merge($values, $config->getConfiguration());
    }
    else {
      $rh_field = $entity->get(self::FIELD_NAME);
      $values['action'] = $rh_field->action;
      if ($action_settings = $rh_field->settings) {
        $values = array_merge($values, $action_settings);
      }
    }
    return $values;
  }

}
