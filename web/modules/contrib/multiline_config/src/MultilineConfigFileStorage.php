<?php

namespace Drupal\multiline_config;

use Drupal\Core\Config\FileStorage;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;

/**
 * Defines the multiline config file storage.
 */
class MultilineConfigFileStorage extends FileStorage {

  /**
   * {@inheritdoc}
   *
   * @see https://www.drupal.org/project/drupal/issues/2844452
   */
  public function encode($data) {
    // \Symfony\Component\Yaml\Dumper::dump doesn't split lines with CRLF, so
    // we need to replace CRLF by LF and remove extra returns after arrays
    // delimiter.
    array_walk_recursive(
      $data,
      function (&$value) {
        if (is_string($value) && strpos($value, PHP_EOL) !== FALSE) {
          $value = preg_replace(["/\r\n/", "/\n+$/"], [PHP_EOL, ''], $value);
        }
      }
    );

    // Set the indentation to 2 to match Drupal's coding standards.
    $dumper = new Dumper(2);
    $yaml = $dumper->dump($data, PHP_INT_MAX, 0, SymfonyYaml::DUMP_EXCEPTION_ON_INVALID_TYPE | SymfonyYaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

    return $yaml;
  }

}
