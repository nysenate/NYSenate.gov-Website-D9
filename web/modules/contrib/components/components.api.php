<?php

/**
 * @file
 * Hooks related to the Components module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the list of namespaces for a particular theme.
 *
 * @param array $namespaces
 *   The array of Twig namespaces where the key is the machine name of the
 *   namespace and the value is an array of directory paths that are relative to
 *   the Drupal root.
 * @param string $theme
 *   The name of the theme that the namespaces are defined for.
 *
 * @see https://www.drupal.org/node/3190969
 */
function hook_components_namespaces_alter(array &$namespaces, string $theme) {
  // Add a new namespace.
  $namespaces['new_namespace'] = [
    // Paths must be relative to the Drupal root.
    'libraries/new-components',
    'themes/contrib/zen/new-components',
    // Even paths adjacent to the Drupal root will work.
    '../vendor/newFangled/new-components',
  ];

  // If you only want to change namespaces for a specific theme the $theme
  // parameter has the name of the currently active theme.
  if ($theme === 'zen') {
    // Append a path to an existing namespace.
    $namespaces['components'][] = \Drupal::service('extension.list.theme')->getPath('zen') . '/components';
  }
}

/**
 * Alter the list of protected Twig namespaces.
 *
 * @param array $protectedNamespaces
 *   The array of protected Twig namespaces, keyed on the machine name of the
 *   namespace. Within each array entry, the following pieces of data are
 *   available:
 *   - name: While the array key is the default Twig namespace (which is also
 *     the machine name of the module/theme that owns it), this "name" is the
 *     friendly name of the module/theme used in Drupal's admin lists.
 *   - type: The extension type: module, theme, or profile.
 *   - package: The package name the module is listed under or an empty string.
 *
 * @see https://www.drupal.org/node/3190969
 */
function hook_protected_twig_namespaces_alter(array &$protectedNamespaces) {
  // Allow the "block" Twig namespace to be altered.
  unset($protectedNamespaces['block']);

  // Allow alteration of any Twig namespace for a "Core" module.
  foreach ($protectedNamespaces as $name => $info) {
    if ($info['package'] === 'Core') {
      unset($protectedNamespaces[$name]);
    }
  }

  // Allow alteration of any Twig namespace for any theme.
  foreach ($protectedNamespaces as $name => $info) {
    if ($info['type'] === 'theme') {
      unset($protectedNamespaces[$name]);
    }
  }

  // Allow alteration of all Twig namespaces.
  $protectedNamespaces = [];
}

/**
 * @} End of "addtogroup hooks".
 */
