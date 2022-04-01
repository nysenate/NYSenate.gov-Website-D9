<?php

namespace Drupal\security_review;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\Role;
use Drupal\user\UserInterface;

/**
 * Provides frequently used security-related data.
 */
class Security {

  use DependencySerializationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Drupal kernel.
   *
   * @var \Drupal\Core\DrupalKernelInterface
   */
  protected $kernel;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The security_review service.
   *
   * @var \Drupal\security_review\SecurityReview
   */
  protected $securityReview;

  /**
   * Constructs a Security instance.
   *
   * @param \Drupal\security_review\SecurityReview $security_review
   *   The SecurityReview service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\DrupalKernelInterface $kernel
   *   The Drupal kernel.
   */
  public function __construct(SecurityReview $security_review, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, DrupalKernelInterface $kernel) {
    // Store the dependencies.
    $this->securityReview = $security_review;
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
    $this->kernel = $kernel;
  }

  /**
   * Returns the IDs of untrusted roles.
   *
   * If the module hasn't been configured yet, it returns the default untrusted
   * roles.
   *
   * @return string[]
   *   Untrusted roles' IDs.
   */
  public function untrustedRoles() {
    // If the module hasn't been manually configured yet, return the untrusted
    // roles depending on Drupal's actual configuration.
    if (!$this->securityReview->isConfigured()) {
      return static::defaultUntrustedRoles();
    }

    // Else return the stored untrusted roles.
    return $this->securityReview->getUntrustedRoles();
  }

  /**
   * Returns the default untrusted roles.
   *
   * The default untrusted roles are:
   *   Anonymous      : always
   *   Authenticated  : if visitors are allowed to create accounts.
   *
   * @return string[]
   *   Default untrusted roles' IDs.
   */
  public function defaultUntrustedRoles() {
    // Add the Anonymous role to the output array.
    $roles = [AccountInterface::ANONYMOUS_ROLE];

    // Check whether visitors can create accounts.
    $user_register = $this->configFactory->get('user.settings')
      ->get('register');
    if ($user_register !== UserInterface::REGISTER_ADMINISTRATORS_ONLY) {
      // If visitors are allowed to create accounts they are considered
      // untrusted.
      $roles[] = AccountInterface::AUTHENTICATED_ROLE;
    }

    // Return the untrusted roles.
    return $roles;
  }

  /**
   * Returns the permission strings that a group of roles have.
   *
   * @param string[] $role_ids
   *   The array of roleIDs to check.
   * @param bool $group_by_role_id
   *   Choose whether to group permissions by role ID.
   *
   * @return array
   *   An array of the permissions untrusted roles have. If $groupByRoleId is
   *   true, the array key is the role ID, the value is the array of permissions
   *   the role has.
   */
  public function rolePermissions(array $role_ids, $group_by_role_id = FALSE) {
    // Get the permissions the given roles have, grouped by roles.
    $permissions_grouped = user_role_permissions($role_ids);

    // Fill up the administrative roles' permissions too.
    foreach ($role_ids as $role_id) {
      $role = Role::load($role_id);
      /** @var Role $role */
      if ($role->isAdmin()) {
        $permissions_grouped[$role_id] = $this->permissions();
      }
    }

    if ($group_by_role_id) {
      // If the result should be grouped, we have nothing else to do.
      return $permissions_grouped;
    }
    else {
      // Merge the grouped permissions into $untrusted_permissions.
      $untrusted_permissions = [];
      foreach ($permissions_grouped as $permissions) {
        $untrusted_permissions = array_merge($untrusted_permissions, $permissions);
      }

      // Remove duplicate elements and fix indexes.
      $untrusted_permissions = array_values(array_unique($untrusted_permissions));
      return $untrusted_permissions;
    }
  }

  /**
   * Returns the permission strings that untrusted roles have.
   *
   * @param bool $group_by_role_id
   *   Choose whether to group permissions by role ID.
   *
   * @return array
   *   An array of the permissions untrusted roles have. If $groupByRoleId is
   *   true, the array key is the role ID, the value is the array of permissions
   *   the role has.
   */
  public function untrustedPermissions($group_by_role_id = FALSE) {
    return $this->rolePermissions($this->untrustedRoles(), $group_by_role_id);
  }

  /**
   * Returns the trusted roles.
   *
   * @return array
   *   Trusted roles' IDs.
   */
  public function trustedRoles() {
    // Get the stored/default untrusted roles.
    $untrusted_roles = $this->untrustedRoles();

    // Iterate through all the roles, and store which are not untrusted.
    $trusted = [];
    foreach (user_roles() as $role) {
      if (!in_array($role->id(), $untrusted_roles)) {
        $trusted[] = $role->id();
      }
    }

    // Return the trusted roles.
    return $trusted;
  }

  /**
   * Returns the permission strings that trusted roles have.
   *
   * @param bool $group_by_role_id
   *   Choose whether to group permissions by role ID.
   *
   * @return array
   *   An array of the permissions trusted roles have. If $groupByRoleId is
   *   true, the array key is the role ID, the value is the array of permissions
   *   the role has.
   */
  public function trustedPermissions($group_by_role_id = FALSE) {
    return $this->rolePermissions($this->trustedRoles(), $group_by_role_id);
  }


  /**
   * Gets all the permissions.
   *
   * @param bool $meta
   *   Whether to return only permission strings or metadata too.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   *
   * @return array
   *   Array of every permission.
   */
  public function permissions($meta = FALSE) {
    // Not injected because of hard testability.
    $permissions = \Drupal::service('user.permissions')->getPermissions();

    if (!$meta) {
      return array_keys($permissions);
    }
    return $permissions;
  }

  /**
   * Gets the list of unsafe HTML tags.
   *
   * @return string[]
   *   List of unsafe tags.
   */
  public function unsafeTags() {
    $unsafe_tags = [
      'applet',
      'area',
      'audio',
      'base',
      'basefont',
      'body',
      'button',
      'comment',
      'embed',
      'eval',
      'form',
      'frame',
      'frameset',
      'head',
      'html',
      'iframe',
      'image',
      'img',
      'input',
      'isindex',
      'label',
      'link',
      'map',
      'math',
      'meta',
      'noframes',
      'noscript',
      'object',
      'optgroup',
      'option',
      'param',
      'script',
      'select',
      'style',
      'svg',
      'table',
      'td',
      'textarea',
      'title',
      'video',
      'vmlframe',
    ];

    // Alter data.
    $this->moduleHandler->alter('security_review_unsafe_tags', $unsafe_tags);

    return $unsafe_tags;
  }

  /**
   * Gets the list of unsafe file extensions.
   *
   * @return string[]
   *   List of unsafe extensions.
   */
  public function unsafeExtensions() {
    $unsafe_ext = [
      'swf',
      'exe',
      'html',
      'htm',
      'php',
      'phtml',
      'py',
      'js',
      'vb',
      'vbe',
      'vbs',
    ];

    // Alter data.
    $this->moduleHandler
      ->alter('security_review_unsafe_extensions', $unsafe_ext);

    return $unsafe_ext;
  }

  /**
   * Returns the site path.
   *
   * @return string
   *   Absolute site path.
   */
  public function sitePath() {
    return DRUPAL_ROOT . '/' . $this->kernel->getSitePath();
  }

  /**
   * Finds files and directories that are writable by the web server.
   *
   * @param string[] $files
   *   The files to iterate through.
   * @param bool $cli
   *   Whether it is being invoked in CLI context.
   *
   * @return string[]
   *   The files that are writable.
   */
  public function findWritableFiles(array $files, $cli = FALSE) {
    $writable = [];
    if (!$cli) {
      // Running from UI.
      foreach ($files as $file) {
        if (is_writable($file)) {
          $writable[] = $file;
        }
      }
    }
    else {
      // Get the web server's user data.
      $uid = $this->securityReview->getServerUid();
      $gids = $this->securityReview->getServerGids();

      foreach ($files as $file) {
        $perms = 0777 & fileperms($file);
        // Check write permissions for others.
        $ow = ($perms >> 1) & 1;
        if ($ow === 1) {
          $writable[] = $file;
          continue;
        }

        // Check write permissions for owner.
        $uw = ($perms >> 7) & 1;
        if ($uw === 1 && fileowner($file) == $uid) {
          $writable[] = $file;
          continue;
        }

        // Check write permissions for group.
        $gw = ($perms >> 4) & 1;
        if ($gw === 1 && in_array(filegroup($file), $gids)) {
          $writable[] = $file;
        }
      }
    }
    return $writable;
  }

}
