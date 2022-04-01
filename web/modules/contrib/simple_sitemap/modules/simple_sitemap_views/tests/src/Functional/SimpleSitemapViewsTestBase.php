<?php

namespace Drupal\Tests\simple_sitemap_views\Functional;

use Drupal\Tests\simple_sitemap\Functional\SimplesitemapTestBase;
use Drupal\simple_sitemap_views\SimpleSitemapViews;
use Drupal\views\Views;

/**
 * Defines a base class for Simple XML Sitemap (Views) functional testing.
 */
abstract class SimpleSitemapViewsTestBase extends SimplesitemapTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'simple_sitemap_views',
    'simple_sitemap_views_test',
  ];

  /**
   * Views sitemap data.
   *
   * @var \Drupal\simple_sitemap_views\SimpleSitemapViews
   */
  protected $sitemapViews;

  /**
   * The cron service.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * Test view.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $testView;

  /**
   * Test view 2.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $testView2;

  /**
   * The sitemap variant.
   *
   * @var string
   */
  protected $sitemapVariant;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->sitemapViews = $this->container->get('simple_sitemap.views');
    $this->cron = $this->container->get('cron');
    $this->sitemapVariant = 'default';

    $this->testView = Views::getView('simple_sitemap_views_test_view');
    $this->testView->setDisplay('page_1');

    $this->testView2 = Views::getView('simple_sitemap_views_test_view');
    $this->testView2->setDisplay('page_2');
  }

  /**
   * Asserts the size of the arguments index.
   *
   * @param int $size
   *   The expected size.
   */
  protected function assertIndexSize($size) {
    $this->assertEquals($size, $this->sitemapViews->getArgumentsFromIndexCount());
  }

  /**
   * Adds a record to the arguments index.
   *
   * @param string $view_id
   *   The view ID.
   * @param string $display_id
   *   The view display ID.
   * @param array $args_ids
   *   A set of argument IDs.
   * @param array $args_values
   *   A set of argument values.
   */
  protected function addRecordToIndex($view_id, $display_id, array $args_ids, array $args_values) {
    $args_ids = implode(SimpleSitemapViews::ARGUMENT_SEPARATOR, $args_ids);
    $args_values = implode(SimpleSitemapViews::ARGUMENT_SEPARATOR, $args_values);

    // Insert a record into the index table.
    $query = $this->database->insert('simple_sitemap_views');
    $query->fields([
      'view_id' => $view_id,
      'display_id' => $display_id,
      'arguments_ids' => $args_ids,
      'arguments_values' => $args_values,
    ]);
    $query->execute();
  }

}
