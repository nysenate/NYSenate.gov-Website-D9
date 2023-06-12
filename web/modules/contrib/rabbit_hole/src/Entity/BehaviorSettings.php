<?php

namespace Drupal\rabbit_hole\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\rabbit_hole\BehaviorSettingsInterface;
use Drupal\rabbit_hole\Exception\InvalidBehaviorSettingException;

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
 *     "uuid" = "uuid",
 *     "action" = "action",
 *     "allow_override" = "allow_override",
 *     "redirect" = "redirect",
 *     "redirect_code" = "redirect_code",
 *     "redirect_fallback_action" = "redirect_fallback_action"
 *   },
 *   config_export = {
 *     "id",
 *     "entity_type_id",
 *     "entity_id",
 *     "uuid",
 *     "action",
 *     "allow_override",
 *     "redirect",
 *     "redirect_code",
 *     "redirect_fallback_action"
 *   },
 *   links = {}
 * )
 */
class BehaviorSettings extends ConfigEntityBase implements BehaviorSettingsInterface {
  const OVERRIDE_ALLOW = TRUE;
  const OVERRIDE_DISALLOW = FALSE;

  const REDIRECT_NOT_APPLICABLE = 0;
  const REDIRECT_MOVED_PERMANENTLY = 301;
  const REDIRECT_FOUND = 302;
  const REDIRECT_SEE_OTHER = 303;
  const REDIRECT_NOT_MODIFIED = 304;
  const REDIRECT_USE_PROXY = 305;
  const REDIRECT_TEMPORARY_REDIRECT = 307;

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
   * Whether inherited behaviors can be edited (if this is a bundle).
   *
   * @var bool
   */
  protected $allow_override;

  /**
   * The path to use for redirects (if the action is redirect).
   *
   * Todo: It may be possible to make this reliant on a plugin instead (i.e.
   *  the redirect plugin) - if so, we should probably do this.
   *
   * @var string
   */
  protected $redirect;

  /**
   * The code to use for redirects (if the action is redirect).
   *
   * Todo: It may be possible to make this reliant on a plugin instead (i.e.
   * the redirect plugin) - if so, we should probably do this.
   *
   * @var int
   */
  protected $redirect_code;

  /**
   * The entity type id, eg. 'node_type'.
   *
   * @var string
   */
  protected $entity_type_id;

  /**
   * The entity id, eg. 'article'.
   *
   * @var string
   */
  protected $entity_id;

  /**
   * {@inheritdoc}
   */
  public function setAction($action) {
    $this->action = $action;
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
  public function setAllowOverride($allow_override) {
    if (!\is_bool($allow_override)) {
      throw new InvalidBehaviorSettingException('allow_override');
    }
    $this->allow_override = $allow_override;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowOverride() {
    return $this->allow_override;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Possibly this should instead rely on the redirect plugin?
   */
  public function setRedirectCode($redirect_code) {
    if (!\in_array($redirect_code, [
      self::REDIRECT_NOT_APPLICABLE,
      self::REDIRECT_MOVED_PERMANENTLY,
      self::REDIRECT_FOUND,
      self::REDIRECT_SEE_OTHER,
      self::REDIRECT_NOT_MODIFIED,
      self::REDIRECT_USE_PROXY,
      self::REDIRECT_TEMPORARY_REDIRECT,
    ]
      )) {
      throw new InvalidBehaviorSettingException('redirect_code');
    }

    if ($this->action !== 'redirect'
      && $redirect_code !== self::REDIRECT_NOT_APPLICABLE) {
      throw new InvalidBehaviorSettingException('redirect_code');
    }
    $this->redirect_code = $redirect_code;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectCode() {
    return $this->redirect_code;
  }

  /**
   * {@inheritdoc}
   */
  public function setRedirectPath($redirect) {
    if ($this->action !== 'redirect' && $redirect != "") {
      throw new InvalidBehaviorSettingException('redirect');
    }
    $this->redirect = $redirect;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectPath() {
    return $this->redirect;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    if ($this->entity_type_id && $this->entity_id) {
      // Create dependency on the bundle.
      $bundle = \Drupal::entityTypeManager()->getDefinition($this->entity_type_id);
      $entity_type = \Drupal::entityTypeManager()->getDefinition($bundle->getBundleOf());
      $bundle_config_dependency = $entity_type->getBundleConfigDependency($this->entity_id);
      $this->addDependency($bundle_config_dependency['type'], $bundle_config_dependency['name']);
    }

    return $this;
  }

}
