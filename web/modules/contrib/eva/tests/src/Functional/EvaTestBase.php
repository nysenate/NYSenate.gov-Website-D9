<?php

namespace Drupal\Tests\eva\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Browser testing for Eva.
 */
abstract class EvaTestBase extends BrowserTestBase {

  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'eva',
    'eva_test',
    'node',
    'views',
    'user',
    'text',
  ];

  /**
   * Number of articles to generate.
   *
   * @var int
   */
  protected $articleCount = 20;

  /**
   * Number of pages to generate.
   *
   * @var int
   */
  protected $pageCount = 10;

  /**
   * Hold the page NID.
   *
   * @var array
   */
  protected $nids = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->makeNodes();
  }

  /**
   * Create some example nodes.
   */
  protected function makeNodes() {
    // Single page for simple Eva test.
    $node = $this->createNode([
      'title' => 'Test Eva',
      'type' => 'just_eva',
    ]);
    $this->nids['just_eva'] = $node->id();

    // Pages for lists-in-lists.
    $this->nids['pages'] = [];
    for ($i = 0; $i < $this->pageCount; $i++) {
      $node = $this->createNode([
        'title' => sprintf('Page %d', $i + 1),
        'type' => 'page_with_related_articles',
      ]);
      $this->nids['pages'][] = $node->id();
    }

    // Articles.
    for ($i = 0; $i < $this->articleCount; $i++) {
      $node = $this->createNode([
        'title' => sprintf('Article %d', $i + 1),
        'type' => 'mini',
      ]);

      // Associate articles with assorted pages.
      $k = array_rand($this->nids['pages'], 1);
      $node->field_page[] = $this->nids['pages'][$k];
      $node->save();
    }
  }

}
