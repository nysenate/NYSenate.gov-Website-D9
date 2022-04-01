<?php

namespace Drupal\Tests\search_api_page\Unit;

use Drupal\search_api_page\Config\ViewMode;
use PHPUnit\Framework\TestCase;

/**
 * Class ViewModeTest.
 *
 * @group search_api_page
 */
class ViewModeTest extends TestCase {

  /**
   * Data provider for the getViewMode tests.
   *
   * @return array
   *   The test data.
   */
  public function getViewModeTestDataProvider() {
    $testData = [];

    $testData['No configuration defaults to the global default'] = [
      'input' => [],
      'dataSourceId' => 'entity:node',
      'bundle' => 'article',
      'expected' => ViewMode::DEFAULT_VIEW_MODE,
    ];

    $testData['Unknown data source defaults to the global default'] = [
      'input' => [
        'entity:taxonomy_term' => [
          'default' => 'full',
          'overrides' => [],
        ],
      ],
      'dataSourceId' => 'entity:node',
      'bundle' => 'article',
      'expected' => ViewMode::DEFAULT_VIEW_MODE,
    ];

    $testData['Unconfigured default defaults to the global default'] = [
      'input' => [
        'entity:node' => [
          'overrides' => [],
        ],
      ],
      'dataSourceId' => 'entity:node',
      'bundle' => 'article',
      'expected' => ViewMode::DEFAULT_VIEW_MODE,
    ];

    $testData['Unknown bundle defaults to the configured default'] = [
      'input' => [
        'entity:node' => [
          'default' => 'full',
          'overrides' => [],
        ],
      ],
      'dataSourceId' => 'entity:node',
      'bundle' => 'article',
      'expected' => 'full',
    ];

    $testData['Empty bundle override defaults to the configured default'] = [
      'input' => [
        'entity:node' => [
          'default' => 'full',
          'overrides' => [
            'article' => '',
          ],
        ],
      ],
      'dataSourceId' => 'entity:node',
      'bundle' => 'article',
      'expected' => 'full',
    ];

    $testData['Override is used when configured'] = [
      'input' => [
        'entity:node' => [
          'default' => 'full',
          'overrides' => [
            'article' => 'teaser',
          ],
        ],
      ],
      'dataSourceId' => 'entity:node',
      'bundle' => 'article',
      'expected' => 'teaser',
    ];

    return $testData;
  }

  /**
   * @dataProvider getViewModeTestDataProvider
   */
  public function testGetViewMode($input, $dataSourceId, $bundle, $expected) {
    $sut = new ViewMode($input);

    $actual = $sut->getViewMode($dataSourceId, $bundle);

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for the getDefaultViewMode tests.
   *
   * @return array
   *   The test data.
   */
  public function getDefaultViewModeTestDataProvider() {
    $testData = [];

    $testData['No configuration defaults to the global default'] = [
      'input' => [],
      'dataSourceId' => 'entity:node',
      'expected' => ViewMode::DEFAULT_VIEW_MODE,
    ];

    $testData['Unknown data source defaults to the global default'] = [
      'input' => [
        'entity:taxonomy_term' => [
          'default' => 'full',
        ],
      ],
      'dataSourceId' => 'entity:node',
      'expected' => ViewMode::DEFAULT_VIEW_MODE,
    ];

    $testData['Empty default defaults to the global default'] = [
      'input' => [
        'entity:node' => [
          'default' => '',
        ],
      ],
      'dataSourceId' => 'entity:node',
      'expected' => ViewMode::DEFAULT_VIEW_MODE,
    ];

    $testData['Gets the configured default'] = [
      'input' => [
        'entity:node' => [
          'default' => 'full',
        ],
      ],
      'dataSourceId' => 'entity:node',
      'expected' => 'full',
    ];

    return $testData;
  }

  /**
   * @dataProvider getDefaultViewModeTestDataProvider
   */
  public function testDefaultGetViewMode($input, $dataSourceId, $expected) {
    $sut = new ViewMode($input);

    $actual = $sut->getDefaultViewMode($dataSourceId);

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for the hasOverrides tests.
   *
   * @return array
   *   The test data.
   */
  public function hasOverridesTestDataProvider() {
    $testData = [];

    $testData['No configuration'] = [
      'input' => [],
      'dataSourceId' => 'entity:node',
      'expected' => FALSE,
    ];

    $testData['Unknown data source'] = [
      'input' => [
        'entity:taxonomy_term' => [
          'overrides' => [
            'tags' => 'full',
          ],
        ],
      ],
      'dataSourceId' => 'entity:node',
      'expected' => FALSE,
    ];

    $testData['No overrides configured'] = [
      'input' => [
        'entity:node' => [
          'overrides' => [],
        ],
      ],
      'dataSourceId' => 'entity:node',
      'expected' => FALSE,
    ];

    $testData['No overrides'] = [
      'input' => [
        'entity:node' => [
          'overrides' => [
            'article' => '',
            'page' => '',
          ],
        ],
      ],
      'dataSourceId' => 'entity:node',
      'expected' => FALSE,
    ];

    $testData['Override present'] = [
      'input' => [
        'entity:node' => [
          'overrides' => [
            'article' => 'full',
            'page' => '',
          ],
        ],
      ],
      'dataSourceId' => 'entity:node',
      'expected' => TRUE,
    ];

    return $testData;
  }

  /**
   * @dataProvider hasOverridesTestDataProvider
   */
  public function testHasOverrides($input, $dataSourceId, $expected) {
    $sut = new ViewMode($input);

    $actual = $sut->hasOverrides($dataSourceId);

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for the isOverridden tests.
   *
   * @return array
   *   The test data.
   */
  public function isOverriddenTestDataProvider() {
    $testData = [];

    $testData['No configuration'] = [
      'input' => [],
      'dataSourceId' => 'entity:node',
      'bundle' => 'article',
      'expected' => FALSE,
    ];

    $testData['Unknown data source'] = [
      'input' => [
        'entity:taxonomy_term' => [
          'overrides' => [
            'tags' => 'full',
          ],
        ],
      ],
      'dataSourceId' => 'entity:node',
      'bundle' => 'article',
      'expected' => FALSE,
    ];

    $testData['No overrides configured'] = [
      'input' => [
        'entity:node' => [
          'overrides' => [],
        ],
      ],
      'dataSourceId' => 'entity:node',
      'bundle' => 'article',
      'expected' => FALSE,
    ];

    $testData['No overrides'] = [
      'input' => [
        'entity:node' => [
          'overrides' => [
            'article' => '',
            'page' => '',
          ],
        ],
      ],
      'dataSourceId' => 'entity:node',
      'bundle' => 'article',
      'expected' => FALSE,
    ];

    $testData['Different bundle overridden'] = [
      'input' => [
        'entity:node' => [
          'overrides' => [
            'article' => '',
            'page' => 'full',
          ],
        ],
      ],
      'dataSourceId' => 'entity:node',
      'bundle' => 'article',
      'expected' => FALSE,
    ];

    $testData['Override present'] = [
      'input' => [
        'entity:node' => [
          'overrides' => [
            'article' => 'full',
            'page' => '',
          ],
        ],
      ],
      'dataSourceId' => 'entity:node',
      'bundle' => 'article',
      'expected' => TRUE,
    ];

    return $testData;
  }

  /**
   * @dataProvider isOverriddenTestDataProvider
   */
  public function testisOverridden($input, $dataSourceId, $bundle, $expected) {
    $sut = new ViewMode($input);

    $actual = $sut->isOverridden($dataSourceId, $bundle);

    $this->assertEquals($expected, $actual);
  }

}
