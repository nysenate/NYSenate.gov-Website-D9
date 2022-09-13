<?php

namespace Drupal\Tests\media_migration\Functional;

use Drupal\Core\Url;

/**
 * Functional test for testing the MediaWysiwyg plugin's fallback logic.
 *
 * @todo Replace with a test testing MediaWysiwygPluginManager.
 *
 * @group media_migration
 */
class MediaWysiwygTest extends MigrateMediaTestBase {

  /**
   * Tests that a "MediaWysiwyg plugin was not found" message is not shown.
   *
   * Content entity types whose entity type ID is not changed during the
   * migration should work with the "fallback" MediaWysiwyg plugin.
   */
  public function testNoMissingPluginForComment() {
    $this->submitMigrateUpgradeSourceConnectionForm();

    $this->assertSession()->pageTextNotContains('Could not find a MediaWysiwyg plugin');
    $this->drupalGet(Url::fromRoute('dblog.overview'));
    $this->assertSession()->pageTextNotContains('Could not find a MediaWysiwyg plugin');
  }

}
