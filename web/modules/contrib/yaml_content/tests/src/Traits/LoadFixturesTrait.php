<?php

namespace Drupal\Tests\yaml_content\Traits;

use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\NestedArray;

/**
 * A trait to support loading test fixture data.
 */
trait LoadFixturesTrait {

  /**
   * Get the base path for fixture content.
   *
   * @return string
   *   The base path for fixture content.
   */
  protected function getFixturePath() {
    return realpath(dirname((__FILE__)) . '/../fixtures');
  }

  /**
   * Load content from a fixture file.
   *
   * @param $fixture_name
   *   The name of the file to load.
   * @param array $collection_path
   *   (optional) An array of keys to extract a subset of content from the file.
   *
   * @return array
   *   Content loaded from the fixture.
   */
  protected function loadFixtureContent($fixture_name, array $collection_path = []) {
    $fixture = Yaml::decode(file_get_contents($this->getFixturePath() . "/${fixture_name}.yml"));

    // Retrieve nested values if the option was provided.
    if (!empty($collection_path)) {
      $fixture = NestedArray::getValue($fixture, $collection_path);
    }

    return $fixture;
  }

}
