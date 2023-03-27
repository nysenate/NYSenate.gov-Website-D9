<?php

declare(strict_types = 1);

namespace Drupal\Tests\linkit\Kernel\CKEditor4To5Upgrade;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\ckeditor5\Kernel\SmartDefaultSettingsTest;

/**
 * @covers \Drupal\linkit\Plugin\CKEditor4To5Upgrade\Linkit
 * @group linkit
 * @group ckeditor5
 * @requires module ckeditor5
 * @internal
 */
class UpgradePathTest extends SmartDefaultSettingsTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'linkit',
    // Because modules/linkit/config/optional/linkit.linkit_profile.default.yml
    // will only then get installed.
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['linkit']);

    $filter_config = [
      'filter_html' => [
        'status' => 1,
        'settings' => [
          'allowed_html' => '<p> <br> <strong> <a href>',
        ],
      ],
    ];
    FilterFormat::create([
      'format' => 'linkit_disabled',
      'name' => 'Linkit disabled',
      'filters' => $filter_config,
    ])->setSyncing(TRUE)->save();
    FilterFormat::create([
      'format' => 'linkit_enabled_misconfigured_format',
      'name' => 'Linkit enabled on a misconfigured format',
      'filters' => $filter_config,
    ])->setSyncing(TRUE)->save();
    FilterFormat::create([
      'format' => 'linkit_enabled',
      'name' => 'Linkit enabled on a well-configured format',
      'filters' => [
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<p> <br> <strong> <a href data-entity-type data-entity-uuid data-entity-substitution>',
          ],
        ],
      ],
    ])->setSyncing(TRUE)->save();

    $generate_editor_settings = function (array $linkit_cke4_settings) {
      return [
        'toolbar' => [
          'rows' => [
            0 => [
              [
                'name' => 'Basic Formatting',
                'items' => [
                  'Bold',
                  'Format',
                  'DrupalLink'
                ],
              ],
            ],
          ],
        ],
        'plugins' => [
          'drupallink' => $linkit_cke4_settings,
        ],
      ];
    };

    Editor::create([
      'format' => 'linkit_disabled',
      'editor' => 'ckeditor',
      'settings' => $generate_editor_settings([
        'linkit_enabled' => FALSE,
        'linkit_profile' => '',
      ]),
    ])->setSyncing(TRUE)->save();
    Editor::create([
      'format' => 'linkit_enabled_misconfigured_format',
      'editor' => 'ckeditor',
      'settings' => $generate_editor_settings([
        'linkit_enabled' => TRUE,
        'linkit_profile' => 'default',
      ]),
    ])->setSyncing(TRUE)->save();
    Editor::create([
      'format' => 'linkit_enabled',
      'editor' => 'ckeditor',
      'settings' => $generate_editor_settings([
        'linkit_enabled' => TRUE,
        'linkit_profile' => 'default',
      ]),
    ])->setSyncing(TRUE)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function provider() {
    $expected_ckeditor5_toolbar = [
      'items' => [
        'bold',
        'link',
      ],
    ];

    yield "linkit disabled" => [
      'format_id' => 'linkit_disabled',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => $expected_ckeditor5_toolbar,
        'plugins' => [],
      ],
      'expected_superset' => '',
      'expected_fundamental_compatibility_violations' => [],
      'expected_db_logs' => [],
      'expected_messages' => [],
    ];

    yield "linkit enabled on a misconfigured text format" => [
      'format_id' => 'linkit_enabled_misconfigured_format',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => $expected_ckeditor5_toolbar,
        'plugins' => [
          'linkit_extension' => [
            'linkit_enabled' => TRUE,
            'linkit_profile' => 'default',
          ],
        ],
      ],
      'expected_superset' => '<a data-entity-type data-entity-uuid data-entity-substitution>',
      'expected_fundamental_compatibility_violations' => [],
      'expected_db_logs' => [],
      'expected_messages' => [
        'warning' => [
          'Updating to CKEditor 5 added support for some previously unsupported tags/attributes. A plugin introduced support for the following:   These attributes: <em class="placeholder"> data-entity-type (for &lt;a&gt;), data-entity-uuid (for &lt;a&gt;), data-entity-substitution (for &lt;a&gt;)</em>; Additional details are available in your logs.',
        ],
      ],
    ];

    yield "linkit enabled on a well-configured text format" => [
      'format_id' => 'linkit_enabled',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => $expected_ckeditor5_toolbar,
        'plugins' => [
          'linkit_extension' => [
            'linkit_enabled' => TRUE,
            'linkit_profile' => 'default',
          ],
        ],
      ],
      'expected_superset' => '',
      'expected_fundamental_compatibility_violations' => [],
      'expected_db_logs' => [],
      'expected_messages' => [],
    ];

    // Verify that none of the core test cases are broken; especially important
    // for Linkit since it extends the behavior of Drupal core.
    foreach (parent::provider() as $label => $case) {
      yield $label => $case;
    }
  }

}
