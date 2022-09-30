<?php

namespace Drupal\password_policy\Entity;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\password_policy\PasswordPolicyInterface;

/**
 * Defines a Password Policy configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "password_policy",
 *   label = @Translation("Password Policy"),
 *   label_singular = @Translation("Password Policy"),
 *   label_plural = @Translation("Password Policies"),
 *   label_count = @PluralTranslation(
 *     singular = @Translation("password policy"),
 *     plural = @Translation("password policies"),
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\password_policy\Controller\PasswordPolicyListBuilder",
 *     "form" = {
 *       "add" = "Drupal\password_policy\Form\PasswordPolicyFormAdd",
 *       "delete" = "Drupal\password_policy\Form\PasswordPolicyDeleteForm",
 *       "edit" = "Drupal\password_policy\Form\PasswordPolicyFormEdit"
 *     }
 *   },
 *   config_prefix = "password_policy",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/config/security/password-policy/add",
 *     "edit-form" = "/admin/config/security/password-policy/{machine_name}",
 *     "delete-form" = "/admin/config/security/password-policy/policy/delete/{password_policy}",
 *     "collection" = "/admin/config/security/password-policy"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "password_reset",
 *     "policy_constraints",
 *     "send_reset_email",
 *     "send_pending_email",
 *     "roles",
 *     "show_policy_table",
 *   }
 * )
 */
class PasswordPolicy extends ConfigEntityBase implements PasswordPolicyInterface {

  /**
   * The ID of the password policy.
   *
   * @var int
   */
  protected $id;

  /**
   * The policy title.
   *
   * @var string
   */
  protected $label;

  /**
   * The number of days between forced password resets.
   *
   * @var int
   */
  protected $password_reset = 30;

  /**
   * Send email notification upon reset.
   *
   * @var int
   */
  protected $send_reset_email = 0;

  /**
   * Send pending email days before.
   *
   * @var int[]
   */
  protected $send_pending_email = [0];

  /**
   * Constraint instance IDs.
   *
   * @var array
   */
  protected $policy_constraints = [];

  /**
   * Roles to which this policy applies.
   *
   * @var array
   */
  protected $roles = [];

  /**
   * The constraints as a collection.
   *
   * @var \Drupal\Core\Plugin\DefaultLazyPluginCollection
   */
  protected $constraintsCollection;

  /**
   * Indicate whether the policy table should get displayed.
   *
   * @var bool
   */
  protected $show_policy_table = TRUE;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    return $this->policy_constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraint($key) {
    if (!isset($this->policy_constraints[$key])) {
      return NULL;
    }
    return $this->policy_constraints[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function getPasswordReset() {
    return $this->password_reset;
  }

  /**
   * Get the plugin collections used by this entity.
   *
   * @return Drupal\Core\Plugin\DefaultLazyPluginCollection
   *   An array of plugin collections, keyed by the property name they use to
   *   store their configuration.
   */
  public function getConstraintsCollection() {
    if (!isset($this->constraintsCollection)) {
      $this->constraintsCollection = new DefaultLazyPluginCollection(\Drupal::service('plugin.manager.password_policy.password_constraint'), $this->getConstraints());
    }
    return $this->constraintsCollection;
  }

  /**
   * Return the password reset email value from the policy.
   *
   * @return int
   *   Whether to send email upon password resets.
   */
  public function getPasswordResetEmailValue() {
    return $this->send_reset_email;
  }

  /**
   * Return the number of days before expiration to send a pending email.
   *
   * @return int[]
   *   Number of days before expiration to send a pending email.
   */
  public function getPasswordPendingValue() {
    return $this->send_pending_email;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles() {
    return $this->roles;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $constraints_collection = $this->getConstraintsCollection();
    if (empty($constraints_collection)) {
      return $this;
    }
    $constraint_plugin_ids = $constraints_collection->getInstanceIds();
    foreach ($constraint_plugin_ids as $constraint_plugin_id) {
      $constraint_plugin = $constraints_collection->get($constraint_plugin_id);
      $constraint_plugin_dependencies = $this->getPluginDependencies($constraint_plugin);
      $this->addDependencies($constraint_plugin_dependencies);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPolicyTableShown() {
    return $this->show_policy_table;
  }

}
