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
   * Import yaml content from an installation profile.
   *
   * @param string $profile
   *   (optional) The machine name of a profile to be searched for content. If
   *   not provided the site's install profile will be used. This command looks
   *   for files in a directory named `content` at the top of the profiles's
   *   main directory, sub directories are not supported; all files in this
   *   directory matching the pattern `*.content.yml` in this directory will be
   *   imported.
   * @param string $file
   *   (Optional) The name of a content file to be imported. If this argument is
   *   not provided then all files in the directory matching `*.content.yml`
   *   will be imported.
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @command yaml_content:import:profile
   * @option create-new
   *   Set this to create content even if it is already in the system.
   * @aliases ycip,yaml-content-import-profile
   */
  public function contentImportProfile($profile = NULL, $file = NULL, array $options = ['create-new' => NULL]) {
    if (empty($profile)) {
      $profile = \Drupal::installProfile();
    }
    $this->loader->importProfile($profile, $file);
  }

}
