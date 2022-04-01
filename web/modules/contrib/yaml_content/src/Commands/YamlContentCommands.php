<?php
namespace Drupal\yaml_content\Commands;

use Drupal\yaml_content\Service\LoadHelper;
use Drush\Commands\DrushCommands;

/**
 * YAML Content commands class for Drush 9.0.0-beta5 and above.
 */
class YamlContentCommands extends DrushCommands {

  /**
   * Content Loader.
   *
   * @var \Drupal\yaml_content\ContentLoader\ContentLoaderInterface
   */
  protected $loader;

  /**
   * ContentLoader constructor.
   *
   * @param \Drupal\yaml_content\Service\LoadHelper $loader
   *   YAML Content loader service.
   */
  public function __construct(LoadHelper $loader) {
    $this->loader = $loader;
  }

  /**
   * Import yaml content from a module.
   *
   * @param string $module
   *   The machine name of a module to be searched for content.
   * @param string $file
   *   (Optional) The name of a content file to be imported.
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @command yaml_content:import:module
   * @option create-new
   *   Set this to create content even if it is already in the system.
   * @aliases ycim,yaml-content-import-module
   */
  public function contentImportModule($module, $file = NULL, array $options = ['create-new' => NULL]) {
    $this->loader->importModule($module, $file);
  }

  /**
   * Import yaml content.
   *
   * @param string $directory
   *   The directory path where content files may be found.
   * @param string $file
   *   (Optional) The name of a content file to be imported.
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @command yaml_content:import
   * @option create-new
   *   Set this to create content even if it is already in the system.
   * @aliases yci,yaml-content-import
   */
  public function contentImport($directory, $file = NULL, array $options = ['create-new' => NULL]) {
    $this->loader->importDirectory($directory, $file);
  }

  /**
   * Import yaml content from a profile.
   *
   * @param string $profile
   *   The machine name of a profile to be searched for content.
   * @param string $file
   *   (Optional) The name of a content file to be imported.
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @command yaml_content:import:profile
   * @option create-new
   *   Set this to create content even if it is already in the system.
   * @aliases ycip,yaml-content-import-profile
   */
  public function contentImportProfile($profile, $file = NULL, array $options = ['create-new' => NULL]) {
    $this->loader->importProfile($profile, $file);
  }

}
