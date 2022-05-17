<?php

namespace Drupal\fontawesome\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\fontawesome\FontAwesomeManagerInterface;

/**
 * Plugin implementation of the 'fontawesome_icon' widget.
 *
 * @FieldWidget(
 *   id = "fontawesome_icon_widget",
 *   module = "fontawesome",
 *   label = @Translation("Font Awesome Icon"),
 *   field_types = {
 *     "fontawesome_icon"
 *   }
 * )
 */
class FontAwesomeIconWidget extends WidgetBase implements ContainerFactoryPluginInterface {
  /**
   * Drupal configuration service container.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Drupal Font Awesome manager service.
   *
   * @var \Drupal\fontawesome\FontAwesomeManagerInterface
   */
  protected $fontAwesomeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ConfigFactory $config_factory, FontAwesomeManagerInterface $font_awesome_manager, AccountInterface $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->configFactory = $config_factory;
    $this->fontAwesomeManager = $font_awesome_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('config.factory'),
      $container->get('fontawesome.font_awesome_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();

    // Load the configuration settings.
    $configuration_settings = $this->configFactory->get('fontawesome.settings');

    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $element['icon_name'] = [
      '#type' => 'textfield',
      '#title' => $cardinality == 1 ? $this->fieldDefinition->getLabel() : $this->t('Icon Name'),
      '#required' => $element['#required'],
      '#size' => 50,
      '#field_prefix' => 'fa-',
      '#default_value' => $items[$delta]->get('icon_name')->getValue(),
      '#description' => $this->t('Name of the Font Awesome Icon. See @iconsLink for valid icon names, or begin typing for an autocomplete list. Note that all four versions of the icon will be shown - Light, Regular, Solid, Duotone, and Thin respectively. If the icon shows a question mark, that icon version is not supported in your version of Fontawesome.', [
        '@iconsLink' => Link::fromTextAndUrl($this->t('the Font Awesome icon list'), Url::fromUri('https://fontawesome.com/icons', [
          'attributes' => [
            'target' => '_blank',
          ],
        ]))->toString(),
      ]),
      '#autocomplete_route_name' => 'fontawesome.autocomplete',
      '#element_validate' => [
        [static::class, 'validateIconName'],
      ],
    ];

    // Get current settings.
    $iconSettings = unserialize($items[$delta]->get('settings')->getValue() ?? '');
    // Build additional settings.
    $element['settings'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Additional Font Awesome Settings'),
      '#access' => $this->currentUser->hasPermission('access fontawesome additional settings'),
    ];

    // Allow user to determine style.
    $style_options = [
      'fas' => $this->t('Solid'),
      'far' => $this->t('Regular'),
      'fal' => $this->t('Light'),
      'fad' => $this->t('Duotone'),
      'fat' => $this->t('Thin'),
      'fak' => $this->t('Kit Uploads'),
    ];
    if (is_bool($configuration_settings->get('use_solid_file')) && !$configuration_settings->get('use_solid_file')) {
      unset($style_options['fas']);
    }
    if (is_bool($configuration_settings->get('use_regular_file')) && !$configuration_settings->get('use_regular_file')) {
      unset($style_options['far']);
    }
    if (is_bool($configuration_settings->get('use_light_file')) && !$configuration_settings->get('use_light_file')) {
      unset($style_options['fal']);
    }
    if (is_bool($configuration_settings->get('use_duotone_file')) && !$configuration_settings->get('use_duotone_file')) {
      unset($style_options['fad']);
    }
    if (is_bool($configuration_settings->get('use_thin_file')) && !$configuration_settings->get('use_thin_file')) {
      unset($style_options['fat']);
    }
    $element['settings']['style'] = [
      '#type' => 'select',
      '#title' => $this->t('Style'),
      '#description' => $this->t('This changes the style of the icon. Please note that this is not available for all icons, and for some of the icons this is only available in the pro version. If the icon does not render properly in the , the icon does not support that style. Notably, brands do not support any styles. See @iconLink for more information.', [
        '@iconLink' => Link::fromTextAndUrl($this->t('the Font Awesome icon list'), Url::fromUri('https://fontawesome.com/icons', [
          'attributes' => [
            'target' => '_blank',
          ],
        ]))->toString(),
      ]),
      '#options' => $style_options,
      '#default_value' => $items[$delta]->get('style')->getValue() ?? array_keys($style_options)[0],
    ];

    // Allow user to determine size.
    $element['settings']['size'] = [
      '#type' => 'select',
      '#title' => $this->t('Size'),
      '#description' => $this->t('This increases icon sizes relative to their container'),
      '#options' => [
        '' => $this->t('Default'),
        'fa-xs' => $this->t('Extra Small'),
        'fa-sm' => $this->t('Small'),
        'fa-lg' => $this->t('Large'),
        'fa-2x' => $this->t('2x'),
        'fa-3x' => $this->t('3x'),
        'fa-4x' => $this->t('4x'),
        'fa-5x' => $this->t('5x'),
        'fa-6x' => $this->t('6x'),
        'fa-7x' => $this->t('7x'),
        'fa-8x' => $this->t('8x'),
        'fa-9x' => $this->t('9x'),
        'fa-10x' => $this->t('10x'),
      ],
      '#default_value' => isset($iconSettings['size']) ? $iconSettings['size'] : '',
    ];
    // Set icon to fixed width.
    $element['settings']['fixed-width'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fixed Width?'),
      '#description' => $this->t('Use to set icons at a fixed width. Great to use when different icon widths throw off vertical alignment. Especially useful in things like nav lists and list groups.'),
      '#default_value' => isset($iconSettings['fixed-width']) ? $iconSettings['fixed-width'] : FALSE,
      '#return_value' => 'fa-fw',
    ];
    // Add border.
    $element['settings']['border'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Border?'),
      '#description' => $this->t('Adds a border to the icon.'),
      '#default_value' => isset($iconSettings['border']) ? $iconSettings['border'] : FALSE,
      '#return_value' => 'fa-border',
    ];
    // Invert color.
    $element['settings']['invert'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Invert color?'),
      '#description' => $this->t('Inverts the color of the icon (black becomes white, etc.)'),
      '#default_value' => isset($iconSettings['invert']) ? $iconSettings['invert'] : FALSE,
      '#return_value' => 'fa-inverse',
    ];
    // Animated the icon.
    $element['settings']['animation'] = [
      '#type' => 'select',
      '#title' => $this->t('Animation'),
      '#description' => $this->t('Use spin to get any icon to rotate, and pulse to have it rotate with 8 steps. Works especially well with fa-spinner & everything in the @iconLink.', [
        '@iconLink' => Link::fromTextAndUrl($this->t('spinner icons category'), Url::fromUri('https://fontawesome.com/icons?c=spinner-icons', [
          'attributes' => [
            'target' => '_blank',
          ],
        ]))->toString(),
      ]),
      '#options' => [
        '' => $this->t('None'),
        'fa-spin' => $this->t('Spin'),
        'fa-pulse' => $this->t('Pulse'),
      ],
      '#default_value' => isset($iconSettings['animation']) ? $iconSettings['animation'] : '',
    ];

    // Pull the icons.
    $element['settings']['pull'] = [
      '#type' => 'select',
      '#title' => $this->t('Pull'),
      '#description' => $this->t('This setting will pull the icon (float) to one side or the other in relation to its nearby content'),
      '#options' => [
        '' => $this->t('None'),
        'fa-pull-left' => $this->t('Left'),
        'fa-pull-right' => $this->t('Right'),
      ],
      '#default_value' => isset($iconSettings['pull']) ? $iconSettings['pull'] : '',
    ];

    // Allow to use CSS Classes for any purpose eg background color.
    $element['settings']['additional_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Additional Classes'),
      '#default_value' => isset($iconSettings['additional_classes']) ? $iconSettings['additional_classes'] : '',
      '#description' => $this->t('Use space separated classes for additional manual icon tagging / settings.'),
    ];

    // Allow user to edit duotone.
    if (is_bool($configuration_settings->get('use_duotone_file')) && $configuration_settings->get('use_duotone_file')) {
      $element['settings']['duotone'] = [
        '#type' => 'details',
        '#open' => FALSE,
        // Disable power transforms for webfonts.
        '#title' => $this->t('Duotone Settings'),
        '#description' => $this->t('Duotone provides a version of every icon in Font Awesome that has two distinct shades of color. They’re great for adding more of your brand or an illustrative quality to the icons in your project. See @duotoneLink for more information. Note that duotone only works with the Pro version of Font Awesome.', [
          '@duotoneLink' => Link::fromTextAndUrl($this->t('the Font Awesome guide to duotone'), Url::fromUri('https://fontawesome.com/how-to-use/on-the-web/styling/duotone-icons'))
            ->toString(),
        ]),
      ];
      $element['settings']['duotone']['swap-opacity'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Swap Opacity?'),
        '#description' => $this->t('Use to swap the default opacity of each duotone icon’s layers. This will make an icon’s primary layer have the default opacity of 40% rather than its secondary layer.'),
        '#default_value' => isset($iconSettings['duotone']['swap-opacity']) ? $iconSettings['duotone']['swap-opacity'] : '',
        '#return_value' => 'fa-swap-opacity',
      ];
      // Manual opacity.
      $element['settings']['duotone']['opacity'] = [
        '#type' => 'details',
        '#open' => TRUE,
        // Disable power transforms for webfonts.
        '#title' => $this->t('Layer Opacity'),
        '#description' => $this->t('By default the secondary layer in a duotone icon is set to 40% opacity (via an opacity 0.4; rule in Font Awesome’s support CSS). You can explicitly set the opacity of a duotone icon’s layer by using CSS custom properties either in your style sheets or by setting them manually below. New to custom properties? Here are some @cssLink.', [
          '@cssLink' => Link::fromTextAndUrl($this->t('places to set them'), Url::fromUri('https://fontawesome.com/how-to-use/on-the-web/styling/duotone-icons#using-in-a-project'))
            ->toString(),
        ]),
      ];
      $element['settings']['duotone']['opacity']['primary'] = [
        '#type' => 'number',
        '#title' => $this->t('Primary Layer Opacity'),
        '#step' => 0.01,
        '#default_value' => isset($iconSettings['duotone']['opacity']['primary']) ? $iconSettings['duotone']['opacity']['primary'] : '',
        '#description' => $this->t('Opacity of the primary duotone layer.'),
      ];
      $element['settings']['duotone']['opacity']['secondary'] = [
        '#type' => 'number',
        '#title' => $this->t('Secondary Layer Opacity'),
        '#step' => 0.01,
        '#default_value' => isset($iconSettings['duotone']['opacity']['secondary']) ? $iconSettings['duotone']['opacity']['secondary'] : '',
        '#description' => $this->t('Opacity of the secondary duotone layer.'),
      ];
      // Manual opacity.
      $element['settings']['duotone']['color'] = [
        '#type' => 'details',
        '#open' => TRUE,
        // Disable power transforms for webfonts.
        '#title' => $this->t('Layer Color'),
        '#description' => $this->t('Like all other Font Awesome icons, duotone icons automatically inherit CSS size and color. A duotone icon consists of a primary and secondary layer. By default, The secondary layer is given an opacity of 40% so that it appears as a lighter shade of the icon’s inherited or directly set color. Using CSS custom properties, we’ve also added some color hooks to a duotone icon’s primary and secondary layers. New to custom properties? Here are some @cssLink.', [
          '@cssLink' => Link::fromTextAndUrl($this->t('places to set them'), Url::fromUri('https://fontawesome.com/how-to-use/on-the-web/styling/duotone-icons#using-in-a-project'))
            ->toString(),
        ]),
      ];
      $element['settings']['duotone']['color']['primary'] = [
        '#type' => 'color',
        '#title' => $this->t('Primary Layer Color'),
        '#step' => 0.01,
        '#default_value' => isset($iconSettings['duotone']['color']['primary']) ? $iconSettings['duotone']['color']['primary'] : '',
        '#description' => $this->t('Opacity of the primary duotone layer.'),
      ];
      $element['settings']['duotone']['color']['secondary'] = [
        '#type' => 'color',
        '#title' => $this->t('Secondary Layer Color'),
        '#step' => 0.01,
        '#default_value' => isset($iconSettings['duotone']['color']['secondary']) ? $iconSettings['duotone']['color']['secondary'] : '',
        '#description' => $this->t('Opacity of the secondary duotone layer.'),
      ];
    }

    // Allow user to add masking.
    $element['settings']['masking'] = [
      '#type' => 'details',
      '#open' => FALSE,
      // Disable power transforms for webfonts.
      '#disabled' => $configuration_settings->get('method') == 'webfonts',
      '#title' => $this->t('Icon Mask'),
      '#description' => $this->t('Masking is used to combine two icons to create one single-color shape. Use it with Power Transforms for some really awesome effects. Masks are great when you do want your background color to show through. See @maskingLink for more information. Note that masking only works with the SVG version of Font Awesome.', [
        '@maskingLink' => Link::fromTextAndUrl($this->t('the Font Awesome guide to masking'), Url::fromUri('https://fontawesome.com/how-to-use/svg-with-js#masking', [
          'attributes' => [
            'target' => '_blank',
          ],
        ]))->toString(),
      ]),
    ];
    $element['settings']['masking']['mask'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon Name'),
      '#size' => 50,
      '#field_prefix' => 'fa-',
      '#default_value' => isset($iconSettings['masking']['mask']) ? $iconSettings['masking']['mask'] : '',
      '#description' => $this->t('Name of the Font Awesome Icon. See @iconsLink for valid icon names, or begin typing for an autocomplete list.', [
        '@iconsLink' => Link::fromTextAndUrl($this->t('the Font Awesome icon list'), Url::fromUri('https://fontawesome.com/icons', [
          'attributes' => [
            'target' => '_blank',
          ],
        ]))->toString(),
      ]),
      '#autocomplete_route_name' => 'fontawesome.autocomplete',
      '#element_validate' => [
        [static::class, 'validateIconName'],
      ],
    ];
    $element['settings']['masking']['style'] = [
      '#type' => 'select',
      '#title' => $this->t('Style'),
      '#description' => $this->t('This changes the style of the masking icon. Please note that this is not available for all icons, and for some of the icons this is only available in the pro version. If the icon does not render properly in the preview above, the icon does not support that style. Notably, brands do not support any styles. See @iconLink for more information.', [
        '@iconLink' => Link::fromTextAndUrl($this->t('the Font Awesome icon list'), Url::fromUri('https://fontawesome.com/icons', [
          'attributes' => [
            'target' => '_blank',
          ],
        ]))->toString(),
      ]),
      '#options' => $style_options,
      '#default_value' => isset($iconSettings['masking']['style']) ? $iconSettings['masking']['style'] : '',
    ];

    // Build new power-transforms.
    $element['settings']['power_transforms'] = [
      '#type' => 'details',
      '#open' => FALSE,
      // Disable power transforms for webfonts.
      '#disabled' => $configuration_settings->get('method') == 'webfonts',
      '#title' => $this->t('Power Transforms'),
      '#description' => $this->t('See @iconLink for additional information on Power Transforms. Note that these transforms only work with the SVG with JS version of Font Awesome and are disabled for Webfonts. See the @adminLink to set your version of Font Awesome.', [
        '@iconLink' => Link::fromTextAndUrl($this->t('the Font Awesome `How to use` guide'), Url::fromUri('https://fontawesome.com/how-to-use/svg-with-js', [
          'attributes' => [
            'target' => '_blank',
          ],
        ]))->toString(),
        '@adminLink' => Link::createFromRoute($this->t('admin page'), 'fontawesome.admin_settings')->toString(),
      ]),
    ];
    // Rotate the icon.
    $element['settings']['power_transforms']['rotate']['value'] = [
      '#type' => 'number',
      '#title' => $this->t('Rotate'),
      '#step' => 0.01,
      '#field_suffix' => '&deg;',
      '#default_value' => isset($iconSettings['power_transforms']['rotate']['value']) ? $iconSettings['power_transforms']['rotate']['value'] : '',
      '#description' => $this->t('Power Transform rotating effects icon angle without changing or moving the container. To rotate icons use any arbitrary value. Units are degrees with negative numbers allowed.'),
    ];
    // Flip the icon.
    $element['settings']['power_transforms']['flip-h']['value'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Flip Horizontal?'),
      '#default_value' => isset($iconSettings['power_transforms']['flip-h']['value']) ? $iconSettings['power_transforms']['flip-h']['value'] : FALSE,
      '#description' => $this->t('Power Transform flipping effects icon reflection without changing or moving the container.'),
      '#return_value' => 'h',
    ];
    $element['settings']['power_transforms']['flip-v']['value'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Flip Vertical?'),
      '#default_value' => isset($iconSettings['power_transforms']['flip-v']['value']) ? $iconSettings['power_transforms']['flip-v']['value'] : FALSE,
      '#description' => $this->t('Power Transform flipping effects icon reflection without changing or moving the container.'),
      '#return_value' => 'v',
    ];
    // Scale the icon.
    $element['settings']['power_transforms']['scale'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Scale'),
      '#description' => $this->t('Power Transform scaling effects icon size without changing or moving the container. This field will scale icons up or down with any arbitrary value, including decimals. Units are 1/16em.'),
      '#element_validate' => [
        [static::class, 'validatePowerTransforms'],
      ],
    ];
    $element['settings']['power_transforms']['scale']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Scale Type'),
      '#options' => [
        '' => $this->t('None'),
        'shrink' => $this->t('Shrink'),
        'grow' => $this->t('Grow'),
      ],
      '#default_value' => isset($iconSettings['power_transforms']['scale']['type']) ? $iconSettings['power_transforms']['scale']['type'] : '',
    ];
    $element['settings']['power_transforms']['scale']['value'] = [
      '#type' => 'number',
      '#title' => $this->t('Scale Value'),
      '#min' => 0,
      '#step' => 0.01,
      '#default_value' => isset($iconSettings['power_transforms']['scale']['value']) ? $iconSettings['power_transforms']['scale']['value'] : '',
      '#states' => [
        'disabled' => [
          ':input[name="' . $field_name . '[' . $delta . '][settings][power_transforms][scale][type]"]' => ['value' => ''],
        ],
      ],
    ];
    // Position the icon.
    $element['settings']['power_transforms']['position_y'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Position (Y Axis)'),
      '#description' => $this->t('Power Transform positioning effects icon location without changing or moving the container. This field will move icons up or down with any arbitrary value, including decimals. Units are 1/16em.'),
      '#element_validate' => [
        [static::class, 'validatePowerTransforms'],
      ],
    ];
    $element['settings']['power_transforms']['position_y']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Position Type'),
      '#options' => [
        '' => $this->t('None'),
        'up' => $this->t('Up'),
        'down' => $this->t('Down'),
      ],
      '#default_value' => isset($iconSettings['power_transforms']['position_y']['type']) ? $iconSettings['power_transforms']['position_y']['type'] : '',
    ];
    $element['settings']['power_transforms']['position_y']['value'] = [
      '#type' => 'number',
      '#title' => $this->t('Position Value'),
      '#min' => 0,
      '#step' => 0.01,
      '#default_value' => isset($iconSettings['power_transforms']['position_y']['value']) ? $iconSettings['power_transforms']['position_y']['value'] : '',
      '#states' => [
        'disabled' => [
          ':input[name="' . $field_name . '[' . $delta . '][settings][power_transforms][position_y][type]"]' => ['value' => ''],
        ],
      ],
    ];
    $element['settings']['power_transforms']['position_x'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Position (X Axis)'),
      '#description' => $this->t('Power Transform positioning effects icon location without changing or moving the container. This field will move icons up or down with any arbitrary value, including decimals. Units are 1/16em.'),
      '#element_validate' => [
        [static::class, 'validatePowerTransforms'],
      ],
    ];
    $element['settings']['power_transforms']['position_x']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Position Type'),
      '#options' => [
        '' => $this->t('None'),
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
      ],
      '#default_value' => isset($iconSettings['power_transforms']['position_x']['type']) ? $iconSettings['power_transforms']['position_x']['type'] : '',
    ];
    $element['settings']['power_transforms']['position_x']['value'] = [
      '#type' => 'number',
      '#title' => $this->t('Position Value'),
      '#min' => 0,
      '#step' => 0.01,
      '#default_value' => isset($iconSettings['power_transforms']['position_x']['value']) ? $iconSettings['power_transforms']['position_x']['value'] : '',
      '#states' => [
        'disabled' => [
          ':input[name="' . $field_name . '[' . $delta . '][settings][power_transforms][position_x][type]"]' => ['value' => ''],
        ],
      ],
    ];

    return $element;
  }

  /**
   * Validate the Font Awesome power transforms.
   */
  public static function validatePowerTransforms($element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);

    if (!empty($values['type']) && empty($values['value'])) {
      $form_state->setError($element, t('Missing value for Font Awesome Power Transform %value. Please see @iconLink for information on correct values.', [
        '%value' => $values['type'],
        '@iconLink' => Link::fromTextAndUrl(t('the Font Awesome icon list'), Url::fromUri('https://fontawesome.com/how-to-use/svg-with-js', [
          'attributes' => [
            'target' => '_blank',
          ],
        ]))->toString(),
      ]));
    }
    elseif (empty($values['type']) && !empty($values['value'])) {
      $form_state->setError($element, t('Missing type value for Font Awesome Power Transform. Please see @iconLink for information on correct values.', [
        '@iconLink' => Link::fromTextAndUrl(t('the Font Awesome icon list'), Url::fromUri('https://fontawesome.com/how-to-use/svg-with-js', [
          'attributes' => [
            'target' => '_blank',
          ],
        ]))->toString(),
      ]));
    }
    if (!empty($values['value']) && !is_numeric($values['value'])) {
      $form_state->setError($element, t("Invalid value for Font Awesome Power Transform %value. Please see @iconLink for information on correct values.", [
        '%value' => $values['type'],
        '@iconLink' => Link::fromTextAndUrl(t('the Font Awesome icon list'), Url::fromUri('https://fontawesome.com/how-to-use/svg-with-js', [
          'attributes' => [
            'target' => '_blank',
          ],
        ]))->toString(),
      ]));
    }
  }

  /**
   * Validate the Font Awesome icon name.
   */
  public static function validateIconName($element, FormStateInterface $form_state) {
    // Load the configuration settings.
    $configuration_settings = \Drupal::config('fontawesome.settings');
    // Check if we need to bypass.
    if ($configuration_settings->get('bypass_validation')) {
      return;
    }

    $value = $element['#value'];
    if (strlen($value) == 0) {
      $form_state->setValueForElement($element, '');
      return;
    }

    // Load the icon data so we can check for a valid icon.
    $iconData = \Drupal::service('fontawesome.font_awesome_manager')->getIconMetadata($value);

    if (!isset($iconData['name'])) {
      $form_state->setError($element, t("Invalid icon name %value. Please see @iconLink for correct icon names, or turn off validation in the Font Awesome settings if you are trying to use custom icon names.", [
        '%value' => $value,
        '@iconLink' => Link::fromTextAndUrl(t('the Font Awesome icon list'), Url::fromUri('https://fontawesome.com/icons', [
          'attributes' => [
            'target' => '_blank',
          ],
        ]))->toString(),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Load the icon data so we can determine the icon type.
    $metadata = $this->fontAwesomeManager->getIcons();

    // Loop over each item and set the data properly.
    foreach ($values as &$item) {
      // Remove the prefix if the user accidentally added it.
      if (substr($item['icon_name'], 0, 3) == 'fa-') {
        $item['icon_name'] = substr($item['icon_name'], 3);
      }

      if (!empty($item['settings']['masking']['style'])) {
        $item['settings']['masking']['style'] = isset($metadata[$item['icon_name']]['styles']) ? $this->fontAwesomeManager->determinePrefix($metadata[$item['icon_name']]['styles'], $item['settings']['masking']['style']) : 'fas';
      }

      // Massage rotate and flip values to make them format properly.
      if (is_numeric($item['settings']['power_transforms']['rotate']['value'])) {
        $item['settings']['power_transforms']['rotate']['type'] = 'rotate';
      }
      else {
        unset($item['settings']['power_transforms']['rotate']);
      }
      if (!empty($item['settings']['power_transforms']['flip-h']['value'])) {
        $item['settings']['power_transforms']['flip-h']['type'] = 'flip';
      }
      else {
        unset($item['settings']['power_transforms']['flip-h']);
      }
      if (!empty($item['settings']['power_transforms']['flip-v']['value'])) {
        $item['settings']['power_transforms']['flip-v']['type'] = 'flip';
      }
      else {
        unset($item['settings']['power_transforms']['flip-v']);
      }

      // Massage the item style.
      if ($item['settings']['style'] == 'fak') {
        $item['style'] = 'fak';
      }
      else {
        // Determine the icon style - brands don't allow style.
        $item['style'] = isset($metadata[$item['icon_name']]['styles']) ? $this->fontAwesomeManager->determinePrefix($metadata[$item['icon_name']]['styles'], $item['settings']['style']) : 'fas';
      }
      unset($item['settings']['style']);

      $item['settings'] = serialize(array_filter($item['settings']));
    }

    return $values;
  }

}
