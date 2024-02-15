<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the upgrade path for boolean filter.
 *
 * @see views_post_update_boolean_filter_accept_null()
 *
 * @group Update
 * @group legacy
 */
class BooleanFilterAcceptNullTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.8.0.filled.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/boolean-filter-accept-null.php',
    ];
  }

  /**
   * Tests that boolean filter values are updated properly.
   */
  public function testViewsPostUpdateBooleanFilterAcceptNull() {
    $view = View::load('node_link_update_test');
    $data = $view->toArray();
    // Check that the field is using the expected string value.
    $this->assertArrayNotHasKey('accept_null', $data['display']['default']['display_options']['filters']['status']);

    $this->runUpdates();

    // Load and initialize our test view.
    $view = View::load('node_link_update_test');
    $data = $view->toArray();
    // Check that the field is using the expected value.
    $this->assertArrayHasKey('accept_null', $data['display']['default']['display_options']['filters']['status']);
    $this->assertFalse($data['display']['default']['display_options']['filters']['status']['accept_null']);
  }

}
