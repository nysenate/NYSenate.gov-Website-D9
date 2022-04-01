<?php

namespace Drupal\Core\Extension;

/**
 * Provides a list of installation profiles.
 *
 * @internal
 *   This class is not yet stable and therefore there are no guarantees that the
 *   internal implementations including constructor signature and protected
 *   properties / methods will not change over time. This will be reviewed after
 *   https://www.drupal.org/project/drupal/issues/2940481
 */
class ProfileExtensionList extends ExtensionList {

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    'dependencies' => [],
    'install' => [],
    'description' => '',
    'package' => 'Other',
    'version' => NULL,
    'php' => \Drupal::MINIMUM_PHP,
    'themes' => ['stark'],
    'hidden' => FALSE,
    'base profile' => '',
  ];

  /**
   * {@inheritdoc}
   */
  public function getExtensionInfo($extension_name) {
    $all_info = $this->getAllAvailableInfo();
    if (isset($all_info[$extension_name])) {
      return $all_info[$extension_name];
    }
    throw new \InvalidArgumentException("The {$this->type} $extension_name does not exist.");
  }

  /**
   * Returns a list comprised of the profile, its parent profile if it has one,
   * and any further ancestors.
   *
   * @param string $profile
   *   (optional) The name of profile. Defaults to the current install profile.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   An associative array of Extension objects, keyed by profile name in
   *   descending order of their dependencies (ancestors first). If the profile
   *   is not given and cannot be determined, returns an empty array.
   */
  public function getAncestors($profile = NULL) {
    $ancestors = [];

    if (empty($profile)) {
      $profile = $this->installProfile ?: \Drupal::installProfile();
    }
    if (empty($profile)) {
      return $ancestors;
    }

    $extension = $this->get($profile);

    foreach ($extension->ancestors as $ancestor) {
      $ancestors[$ancestor] = $this->get($ancestor);
    }
    $ancestors[$profile] = $extension;

    return $ancestors;
  }

  /**
   * Returns all available profiles which are distributions.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   Processed extension objects, keyed by machine name.
   */
  public function listDistributions() {
    return array_filter($this->getList(), function (Extension $profile) {
      return !empty($profile->info['distribution']);
    });
  }

  /**
   * Select the install distribution from the list of profiles.
   *
   * If there are multiple profiles marked as distributions, select the first.
   * If there is an inherited profile marked as a distribution, select it over
   * its base profile.
   *
   * @param string[] $profiles
   *   List of profile names to search.
   *
   * @return string|null
   *   The selected distribution profile name, or NULL if none is found.
   */
  public function selectDistribution(array $profiles = NULL) {
    $distributions = $this->listDistributions();

    if ($profiles) {
      $distributions = array_intersect_key($distributions, array_flip($profiles));
    }

    // Remove any distributions which are extended by another one.
    foreach ($distributions as $profile_name => $profile) {
      if (!empty($profile->info['base profile'])) {
        $base_profile = $profile->info['base profile'];
        unset($distributions[$base_profile]);
      }
    }

    return key($distributions) ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function doList() {
    $profiles = parent::doList();

    // Compute the ancestry of each profile before any further processing.
    foreach ($profiles as $profile) {
      // Maintain a list of profiles which depend on this one.
      $profile->children = [];

      // Maintain a list of profiles that this one depends on, in reverse
      // ancestral order (immediate parent first).
      $profile->ancestors = $this->computeAncestry($profiles, $profile);

      // Give the profile a heavy weight to ensure that its hooks run last.
      $profile->weight = count($profile->ancestors) + 1000;
    }

    // For each profile, merge in ancestors' module and theme lists.
    foreach ($profiles as $profile_name => $profile) {
      if (empty($profile->ancestors)) {
        continue;
      }
      // Reference the extension info here for readability.
      $info = &$profile->info;

      // Add the parent profile as a hard dependency.
      $info['dependencies'][] = reset($profile->ancestors);

      // Add all themes and extensions listed by ancestors.
      foreach ($profile->ancestors as $ancestor) {
        $ancestor = $profiles[$ancestor];

        // Add the current profile as a child of the ancestor.
        $ancestor->children[] = $profile_name;
        $info['install'] = array_merge($info['install'], $ancestor->info['install']);
        $info['themes'] = array_merge($info['themes'], $ancestor->info['themes']);
        // Add ancestor dependencies as our dependencies.
        $info['dependencies'] = array_merge($info['dependencies'], $ancestor->info['dependencies']);
      }
      $info['dependencies'] = array_unique($info['dependencies']);
      $info['install'] = array_unique($info['install']);
      $info['themes'] = array_unique($info['themes']);
    }
    return $profiles;
  }

  /**
   * Computes and returns the ancestral lineage of a profile.
   *
   * @param \Drupal\Core\Extension\Extension[] $profiles
   *   All discovered profiles.
   * @param \Drupal\Core\Extension\Extension $profile
   *   The profile for which to compute the ancestry.
   *
   * @return string[]
   *   The names of the ancestors of the given profile, in order.
   */
  protected function computeAncestry(array $profiles, Extension $profile) {
    $ancestors = [];

    while (!empty($profile->info['base profile'])) {
      array_unshift($ancestors, $profile->info['base profile']);
      $profile = $profile->info['base profile'];
      $profile = $profiles[$profile];
    }
    return $ancestors;
  }

  /**
   * {@inheritdoc}
   */
  protected function getInstalledExtensionNames() {
    return array_keys($this->getAncestors());
  }

}
