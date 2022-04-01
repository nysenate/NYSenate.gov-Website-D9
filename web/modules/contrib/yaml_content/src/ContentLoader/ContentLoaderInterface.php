<?php

namespace Drupal\yaml_content\ContentLoader;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Interface for loading and parsing content from YAML files.
 */
interface ContentLoaderInterface extends ContainerInjectionInterface {

  /**
   * Set a path prefix for all content files to be loaded from.
   *
   * @param string $path
   *   The path for where all content files will be loaded from.
   */
  public function setContentPath($path);

  /**
   * Get a path prefix for all content files to be loaded from.
   *
   * @return string
   *   The path for where all content files will be loaded from.
   */
  public function getContentPath();

  /**
   * Parse the given yaml content file into an array.
   *
   * @param string $content_file
   *   A file name for the content file to be loaded. The file is assumed to be
   *   located within a directory set by `setPath()`.
   *
   * @return array
   *   The parsed content array from the file.
   */
  public function parseContent($content_file);

  /**
   * Load all demo content for this loader.
   *
   * @param string $content_file
   *   A file name for the content file to be loaded. The file is assumed to be
   *   located within a directory set by `setPath()`.
   *
   * @return array
   *   An array of created entities.
   */
  public function loadContent($content_file);

  /**
   * Build an entity from the provided content data.
   *
   * @param string $entity_type
   *   The entity type.
   * @param array $content_data
   *   The parsed content data.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity returned after building from the parsed content data.
   */
  public function buildEntity($entity_type, array $content_data);

}
