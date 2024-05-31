<?php

declare(strict_types = 1);

namespace Drupal\Tests\linkit\Kernel;

use Drupal\Tests\ckeditor5\Kernel\ValidatorsTest as CKEditor5CoreValidatorsTest;

/**
 * @covers \Drupal\linkit\Plugin\CKEditor5Plugin\Linkit::validChoices
 * @covers \Drupal\linkit\Plugin\CKEditor5Plugin\Linkit::requireProfileIfEnabled
 * @covers linkit.schema.yml
 *
 * @group linkit
 */
class ValidatorsTest extends CKEditor5CoreValidatorsTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'linkit',
    // @see config/optional/linkit.linkit_profile.default.yml
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // @see config/optional/linkit.linkit_profile.default.yml
    $this->installConfig(['linkit']);
  }

  /**
   * {@inheritdoc}
   */
  public function provider(): array {
    $linkit_test_cases_toolbar_settings = ['items' => ['link']];

    $data = [];
    $data['VALID: installing the linkit module without configuring the existing text editors'] = [
      'settings' => [
        'toolbar' => $linkit_test_cases_toolbar_settings,
        'plugins' => [],
      ],
      'violations' => [],
    ];
    $data['INVALID: linkit — invalid manually created configuration'] = [
      'settings' => [
        'toolbar' => $linkit_test_cases_toolbar_settings,
        'plugins' => [
          'linkit_extension' => [
            'linkit_enabled' => 'no',
          ],
        ],
      ],
      'violations' => [
        'settings.plugins.linkit_extension.linkit_enabled' => 'This value should be of the correct primitive type.',
      ],
    ];
    $data['VALID: linkit off'] = [
      'settings' => [
        'toolbar' => $linkit_test_cases_toolbar_settings,
        'plugins' => [
          'linkit_extension' => [
            'linkit_enabled' => FALSE,
          ],
        ],
      ],
      'violations' => [],
    ];
    $data['VALID: linkit off, profile selected'] = [
      'settings' => [
        'toolbar' => $linkit_test_cases_toolbar_settings,
        'plugins' => [
          'linkit_extension' => [
            'linkit_enabled' => TRUE,
            'linkit_profile' => 'default',
          ],
        ],
      ],
      'violations' => [],
    ];
    $data['INVALID: linkit on, no profile selected'] = [
      'settings' => [
        'toolbar' => $linkit_test_cases_toolbar_settings,
        'plugins' => [
          'linkit_extension' => [
            'linkit_enabled' => TRUE,
          ],
        ],
      ],
      'violations' => [
        'settings.plugins.linkit_extension.linkit_profile' => 'Linkit is enabled, please select the Linkit profile you wish to use.',
      ],
    ];
    $data['INVALID: linkit on, non-existent profile selected'] = [
      'settings' => [
        'toolbar' => $linkit_test_cases_toolbar_settings,
        'plugins' => [
          'linkit_extension' => [
            'linkit_enabled' => TRUE,
            'linkit_profile' => 'nonexistent',
          ],
        ],
      ],
      'violations' => [
        'settings.plugins.linkit_extension.linkit_profile' => 'The value you selected is not a valid choice.',
      ],
    ];
    $data['VALID: linkit on, existing profile selected'] = [
      'settings' => [
        'toolbar' => $linkit_test_cases_toolbar_settings,
        'plugins' => [
          'linkit_extension' => [
            'linkit_enabled' => TRUE,
            'linkit_profile' => 'default',
          ],
        ],
      ],
      'violations' => [],
    ];
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function providerPair(): array {
    // Linkit is 100% independent of the text format, so no need for this test.
    return [];
  }

}
