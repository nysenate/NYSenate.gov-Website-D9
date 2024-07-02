<?php

namespace Drupal\rabbit_hole\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\rabbit_hole\BehaviorSettingsInterface;

/**
 * Defines the Behavior settings entity.
 *
 * @ConfigEntityType(
 *   id = "behavior_settings",
 *   label = @Translation("Rabbit hole settings"),
 *   handlers = {},
 *   config_prefix = "behavior_settings",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "action" = "action"
 *   },
 *   config_export = {
 *     "id",
 *     "targetEntityType",
 *     "bundle",
 *     "action",
 *     "no_bypass",
 *     "bypass_message",
 *     "configuration"
 *   },
 *   links = {}
 * )
 */
class BehaviorSettings extends ConfigEntityBase implements BehaviorSettingsInterface {

  /**
   * The Behavior settings ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The configured action (e.g. display_page).
   *
   * @var string
   */
  protected $action;

  /**
   * Entity type to be displayed.
   *
   * @var string
   */
  protected $targetEntityType;

  /**
   * Bundle to be displayed.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The entity type id, eg. 'node_type'.
   *
   * @deprecated in rabbit_hole:2.0.0 and is removed from rabbit_hole:3.0.0.
   *   The 'entity_type_id' key can be removed from the configuration.
   * @see https://www.drupal.org/node/3374669
   *
   * @var string
   */
  protected $entity_type_id;

  /**
   * The entity id, eg. 'article'.
   *
   * @deprecated in rabbit_hole:2.0.0 and is removed from rabbit_hole:3.0.0.
   *   The 'entity_id' key can be removed from the configuration.
   * @see https://www.drupal.org/node/3374669
   *
   * @var string
   */
  protected $entity_id;

  /**
   * The bypass action.
   *
   * @var bool
   */
  protected $no_bypass;

  /**
   * The bypass message.
   *
   * @var bool
   */
  protected $bypass_message;

  /**
   * The action-specific configuration.
   *
   * @var array
   */
  protected array $configuration = [];

  /**
   * {@inheritdoc}
   */
  public function setAction($action) {
    $this->action = $action;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAction() {
    return $this->action;
  }

  /**
   * {@inheritdoc}
   */
  public function setNoBypass(bool $no_bypass) {
    $this->no_bypass = $no_bypass;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNoBypass(): bool {
    return $this->no_bypass;
  }

  /**
   * {@inheritdoc}
   */
  public function setBypassMessage(bool $bypass_message) {
    $this->bypass_message = $bypass_message;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBypassMessage(): bool {
    return $this->bypass_message;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings(): array {
    $settings = ['action', 'no_bypass', 'bypass_message', 'configuration'];
    $result = [];
    foreach ($settings as $property) {
      $result[$property] = $this->get($property);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $values = []) {
    if (!isset($values['targetEntityType']) || !isset($values['bundle'])) {
      throw new \InvalidArgumentException('Missing required properties for an EntityDisplay entity.');
    }

    if (!\Drupal::entityTypeManager()->hasDefinition($values['targetEntityType'])) {
      throw new \InvalidArgumentException(sprintf('Provided entity type "%entity_type" is not available.', $values['targetEntityType']));
    }

    return parent::create($values);
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    // Add default config values.
    $values += [
      'id' => static::generateId($values['targetEntityType'], $values['bundle']),
      'action' => 'display_page',
      'no_bypass' => FALSE,
      'bypass_message' => FALSE,
      'configuration' => [],
    ];
    parent::preCreate($storage, $values);
  }

  /**
   * Loads Rabbit Hole config entity based on the entity type and bundle.
   *
   * @param string $entity_type_id
   *   ID of the entity type.
   * @param string $bundle
   *   Bundle name.
   *
   * @return \Drupal\rabbit_hole\BehaviorSettingsInterface|null
   *   The Rabbit Hole config entity if one exists.
   */
  public static function loadByEntityTypeBundle(string $entity_type_id, string $bundle): ?BehaviorSettingsInterface {
    if ($entity_type_id == NULL || $bundle == NULL) {
      return NULL;
    }
    $id = static::generateId($entity_type_id, $bundle);
    $config = \Drupal::entityTypeManager()->getStorage('behavior_settings')->load($id);
    if ($config === NULL) {
      $values = [
        'targetEntityType' => $entity_type_id,
        'bundle' => $bundle,
      ];
      $config = BehaviorSettings::create($values);
    }
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    if ($this->targetEntityType && $this->bundle) {
      // Create dependency on the bundle.
      $target_entity_type = $this->entityTypeManager()->getDefinition($this->targetEntityType);
      $bundle_config_dependency = $target_entity_type->getBundleConfigDependency($this->bundle);
      $this->addDependency($bundle_config_dependency['type'], $bundle_config_dependency['name']);
    }

    return $this;
  }

  /**
   * Generate config ID based on entity type ID and bundle name.
   */
  protected static function generateId(string $entity_type_id, ?string $bundle = NULL): string {
    return $entity_type_id . (isset($bundle) ? '.' . $bundle : '');
  }

}
