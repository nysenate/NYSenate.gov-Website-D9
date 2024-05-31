<?php

namespace Drupal\sophron;

/**
 * Provides an interface for FileMapManager.
 */
interface MimeMapManagerInterface {

  /**
   * Option to use Sophron's Drupal-compatible map.
   */
  const DRUPAL_MAP = 0;

  /**
   * Option to use MimeMap's default map.
   */
  const DEFAULT_MAP = 1;

  /**
   * Option to use a custom defined map.
   */
  const CUSTOM_MAP = 99;

  /**
   * Determines if a FQCN is a valid map class.
   *
   * Map classes muste extend from FileEye\MimeMap\Map\AbstractMap.
   *
   * @param string $map_class
   *   A FQCN.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function isMapClassValid($map_class);

  /**
   * Gets the FQCN of map currently in use by the manager.
   *
   * @return string
   *   A FQCN.
   */
  public function getMapClass();

  /**
   * Sets the map class to use by the manager.
   *
   * @param string $map_class
   *   A FQCN.
   *
   * @return $this
   */
  public function setMapClass($map_class);

  /**
   * Gets the initialization errors of a map class.
   *
   * @param string $map_class
   *   A FQCN.
   *
   * @return array
   *   The array of mapping errors.
   */
  public function getMappingErrors($map_class);

  /**
   * Gets the list of MIME types.
   *
   * @return string[]
   *   A simple array of MIME type strings.
   */
  public function listTypes();

  /**
   * Gets a MIME type.
   *
   * @param string $type
   *   A MIME type string.
   *
   * @return \FileEye\MimeMap\Type
   *   A Type object.
   *
   * @see \FileEye\MimeMap\Type
   */
  public function getType($type);

  /**
   * Gets the list of file extensions.
   *
   * @return string[]
   *   A simple array of file extension strings.
   */
  public function listExtensions();

  /**
   * Gets a file extension.
   *
   * @param string $extension
   *   A file extension string.
   *
   * @return \FileEye\MimeMap\Extension
   *   An Extension object.
   *
   * @see \FileEye\MimeMap\Extension
   */
  public function getExtension($extension);

  /**
   * Check installation requirements and do status reporting.
   *
   * @param string $phase
   *   The phase in which requirements are checked.
   *
   * @return array
   *   An associative array of requirements.
   */
  public function requirements($phase);

}
