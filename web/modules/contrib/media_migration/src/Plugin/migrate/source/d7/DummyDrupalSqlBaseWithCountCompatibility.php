<?php

/**
 * @file
 * Base class for working around SQL plugin count compatibility issues.
 *
 * @todo Remove after Drupal core 8.x, and 9.0.x and 9.1.x are unsupported.
 *
 * @see https://drupal.org/i/3190815
 * @see \Drupal\media_migration\Plugin\migrate\source\d7\DrupalSqlBaseWithCountCompatibility
 */

namespace Drupal\media_migration\Plugin\migrate\source\d7;

use Drupal\migrate\Plugin\migrate\source\DummyQueryTrait;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

if (method_exists(DummyQueryTrait::class, 'doCount')) {
  /**
   * Class for core where sql source plugin count caching is fully supported.
   */
  abstract class DummyDrupalSqlBaseWithCountCompatibility extends DrupalSqlBase {

    use DummyQueryTrait;

    /**
     * {@inheritdoc}
     */
    protected function doCount() {
      return (int) $this->initializeIterator()->count();
    }

  }

}
elseif (
  (
    version_compare(\Drupal::VERSION, '9.1.9', 'ge') &&
    version_compare(\Drupal::VERSION, '9.2', 'lt')
  ) ||
  version_compare(\Drupal::VERSION, '9.2.0-alpha2', 'ge')
) {
  /**
   * Class for core where sql source plugin count caching is supported.
   */
  abstract class DummyDrupalSqlBaseWithCountCompatibility extends DrupalSqlBase {

    use DummyQueryTrait;

    /**
     * {@inheritdoc}
     */
    public function count($refresh = FALSE) {
      return SourcePluginBase::count($refresh);
    }

    /**
     * {@inheritdoc}
     */
    protected function doCount() {
      return (int) $this->initializeIterator()->count();
    }

  }
}
else {
  /**
   * Class for core where sql source plugin count caching isn't supported.
   */
  abstract class DummyDrupalSqlBaseWithCountCompatibility extends DrupalSqlBase {

    use DummyQueryTrait;

    /**
     * {@inheritdoc}
     */
    public function count($refresh = FALSE) {
      return SourcePluginBase::count($refresh);
    }

    /**
     * {@inheritdoc}
     */
    protected function doCount() {
      return SourcePluginBase::doCount();
    }

  }
}
