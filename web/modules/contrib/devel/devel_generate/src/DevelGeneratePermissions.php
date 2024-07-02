<?php

namespace Drupal\devel_generate;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the filter module.
 */
class DevelGeneratePermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The plugin manager.
   */
  protected DevelGeneratePluginManager $develGeneratePluginManager;

  /**
   * Constructs a new DevelGeneratePermissions instance.
   *
   * @param \Drupal\devel_generate\DevelGeneratePluginManager $develGeneratePluginManager
   *   The plugin manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(
    DevelGeneratePluginManager $develGeneratePluginManager,
    TranslationInterface $string_translation
  ) {
    $this->develGeneratePluginManager = $develGeneratePluginManager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('plugin.manager.develgenerate'),
      $container->get('string_translation'),
    );
  }

  /**
   * A permissions callback.
   *
   * @see devel_generate.permissions.yml
   *
   * @return array
   *   An array of permissions for all plugins.
   */
  public function permissions(): array {
    $permissions = [];
    $devel_generate_plugins = $this->develGeneratePluginManager->getDefinitions();
    foreach ($devel_generate_plugins as $plugin) {

      $permission = $plugin['permission'];
      $permissions[$permission] = [
        'title' => $this->t('@permission', ['@permission' => $permission]),
      ];
    }

    return $permissions;
  }

}
