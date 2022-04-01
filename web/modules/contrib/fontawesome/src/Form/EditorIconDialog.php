<?php

namespace Drupal\fontawesome\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Config\ConfigFactory;
use Drupal\fontawesome\FontAwesomeManagerInterface;

/**
 * Provides a Font Awesome icon dialog for text editors.
 */
class EditorIconDialog extends FormBase {
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
   * {@inheritdoc}
   */
  public function __construct(ConfigFactory $config_factory, FontAwesomeManagerInterface $font_awesome_manager) {
    $this->configFactory = $config_factory;
    $this->fontAwesomeManager = $font_awesome_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('fontawesome.font_awesome_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fontawesome_icon_dialog';
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   * @param \Drupal\editor\Entity\Editor $editor
   *   The text editor to which this dialog corresponds.
   */
  public function buildForm(array $form, FormStateInterface $form_state, Editor $editor = NULL) {
    // Load the configuration settings.
    $configuration_settings = $this->configFactory->get('fontawesome.settings');

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';

    $form['#prefix'] = '<div id="fontawesome-icon-dialog-form">';
    $form['#suffix'] = '</div>';

    $form['information'] = [
      '#type' => 'container',
      '#attributes' => [],
      '#children' => $this->t('For more information on icon selection, see @iconLink. If an icon below is displayed with a question mark, it is likely a Font Awesome Pro icon, unavailable with the free version of Font Awesome.', [
        '@iconLink' => Link::fromTextAndUrl($this->t('the Font Awesome icon list'), Url::fromUri('https://fontawesome.com/icons'))->toString(),
      ]),
    ];

    $form['icon_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon Name'),
      '#size' => 50,
      '#field_prefix' => 'fa-',
      '#default_value' => '',
      '#description' => $this->t('Name of the Font Awesome Icon. See @iconsLink for valid icon names, or begin typing for an autocomplete list.', [
        '@iconsLink' => Link::fromTextAndUrl($this->t('the Font Awesome icon list'), Url::fromUri('https://fontawesome.com/icons'))->toString(),
      ]),
      '#autocomplete_route_name' => 'fontawesome.autocomplete',
      '#element_validate' => [
        [static::class, 'validateIconName'],
      ],
    ];

    // Build additional settings.
    $form['settings'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Additional Font Awesome Settings'),
    ];
    // Allow user to determine size.
    $form['settings']['style'] = [
      '#type' => 'select',
      '#title' => $this->t('Style'),
      '#description' => $this->t('This changes the style of the icon. Please note that this is not available for all icons, and for some of the icons this is only available in the pro version. If the icon does not render properly in the preview above, the icon does not support that style. Notably, brands do not support any styles. See @iconLink for more information.', [
        '@iconLink' => Link::fromTextAndUrl($this->t('the Font Awesome icon list'), Url::fromUri('https://fontawesome.com/icons'))->toString(),
      ]),
      '#options' => [
        'fas' => $this->t('Solid'),
        'far' => $this->t('Regular'),
        'fal' => $this->t('Light'),
        'fad' => $this->t('Duotone'),
        'fat' => $this->t('Thin'),
        'fak' => $this->t('Kit Uploads'),
      ],
      '#default_value' => 'fas',
    ];
    // Allow user to determine size.
    $form['settings']['size'] = [
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
      '#default_value' => '',
    ];
    // Set icon to fixed width.
    $form['settings']['fixed-width'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fixed Width?'),
      '#description' => $this->t('Use to set icons at a fixed width. Great to use when different icon widths throw off vertical alignment. Especially useful in things like nav lists and list groups.'),
      '#default_value' => FALSE,
      '#return_value' => 'fa-fw',
    ];
    // Add border.
    $form['settings']['border'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Border?'),
      '#description' => $this->t('Adds a border to the icon.'),
      '#default_value' => FALSE,
      '#return_value' => 'fa-border',
    ];
    // Invert color.
    $form['settings']['invert'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Invert color?'),
      '#description' => $this->t('Inverts the color of the icon (black becomes white, etc.)'),
      '#default_value' => FALSE,
      '#return_value' => 'fa-inverse',
    ];
    // Animated the icon.
    $form['settings']['animation'] = [
      '#type' => 'select',
      '#title' => $this->t('Animation'),
      '#description' => $this->t('Use spin to get any icon to rotate, and pulse to have it rotate with 8 steps. Works especially well with fa-spinner & everything in the @iconLink.', [
        '@iconLink' => Link::fromTextAndUrl($this->t('spinner icons category'), Url::fromUri('https://fontawesome.com/icons?d=gallery&c=spinners'))->toString(),
      ]),
      '#options' => [
        '' => $this->t('None'),
        'fa-spin' => $this->t('Spin'),
        'fa-pulse' => $this->t('Pulse'),
      ],
      '#default_value' => '',
    ];

    // Pull the icons.
    $form['settings']['pull'] = [
      '#type' => 'select',
      '#title' => $this->t('Pull'),
      '#description' => $this->t('This setting will pull the icon (float) to one side or the other in relation to its nearby content'),
      '#options' => [
        '' => $this->t('None'),
        'fa-pull-left' => $this->t('Left'),
        'fa-pull-right' => $this->t('Right'),
      ],
      '#default_value' => '',
    ];

    // Build new power-transforms.
    $form['settings']['power_transforms'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#disabled' => $configuration_settings->get('method') == 'webfonts',
      '#title' => $this->t('Power Transforms'),
      '#description' => $this->t('See @iconLink for additional information on Power Transforms. Note that these transforms only work with the SVG with JS version of Font Awesome and are disabled for Webfonts. See the @adminLink to set your version of Font Awesome.', [
        '@iconLink' => Link::fromTextAndUrl($this->t('the Font Awesome `Power Transforms` guide'), Url::fromUri('https://fontawesome.com/how-to-use/on-the-web/styling/power-transforms'))->toString(),
        '@adminLink' => Link::createFromRoute($this->t('admin page'), 'fontawesome.admin_settings')->toString(),
      ]),
    ];
    // Rotate the icon.
    $form['settings']['power_transforms']['rotate']['value'] = [
      '#type' => 'number',
      '#title' => $this->t('Rotate'),
      '#field_suffix' => '&deg;',
      '#default_value' => '',
      '#description' => $this->t('Power Transform rotating effects icon angle without changing or moving the container. To rotate icons use any arbitrary value. Units are degrees with negative numbers allowed.'),
    ];
    // Flip the icon.
    $form['settings']['power_transforms']['flip-h']['value'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Flip Horizontal?'),
      '#default_value' => FALSE,
      '#description' => $this->t('Power Transform flipping effects icon reflection without changing or moving the container.'),
      '#return_value' => 'h',
    ];
    $form['settings']['power_transforms']['flip-v']['value'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Flip Vertical?'),
      '#default_value' => FALSE,
      '#description' => $this->t('Power Transform flipping effects icon reflection without changing or moving the container.'),
      '#return_value' => 'v',
    ];
    // Scale the icon.
    $form['settings']['power_transforms']['scale'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Scale'),
      '#description' => $this->t('Power Transform scaling effects icon size without changing or moving the container. This field will scale icons up or down with any arbitrary value, including decimals. Units are 1/16em.'),
    ];
    $form['settings']['power_transforms']['scale']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Scale Type'),
      '#options' => [
        '' => $this->t('None'),
        'shrink' => $this->t('Shrink'),
        'grow' => $this->t('Grow'),
      ],
      '#default_value' => '',
      '#element_validate' => [
        [static::class, 'validatePowerTransforms'],
      ],
    ];
    $form['settings']['power_transforms']['scale']['value'] = [
      '#type' => 'number',
      '#title' => $this->t('Scale Value'),
      '#min' => 0,
      '#default_value' => '',
      '#states' => [
        'disabled' => [
          ':input[name="settings[power_transforms][scale][type]"]' => ['value' => ''],
        ],
      ],
    ];
    // Position the icon.
    $form['settings']['power_transforms']['position_y'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Position (Y Axis)'),
      '#description' => $this->t('Power Transform positioning effects icon location without changing or moving the container. This field will move icons up or down with any arbitrary value, including decimals. Units are 1/16em.'),
    ];
    $form['settings']['power_transforms']['position_y']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Position Type'),
      '#options' => [
        '' => $this->t('None'),
        'up' => $this->t('Up'),
        'down' => $this->t('Down'),
      ],
      '#default_value' => '',
      '#element_validate' => [
        [static::class, 'validatePowerTransforms'],
      ],
    ];
    $form['settings']['power_transforms']['position_y']['value'] = [
      '#type' => 'number',
      '#title' => $this->t('Position Value'),
      '#min' => 0,
      '#default_value' => '',
      '#states' => [
        'disabled' => [
          ':input[name="settings[power_transforms][position_y][type]"]' => ['value' => ''],
        ],
      ],
    ];
    $form['settings']['power_transforms']['position_x'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Position (X Axis)'),
      '#description' => $this->t('Power Transform positioning effects icon location without changing or moving the container. This field will move icons up or down with any arbitrary value, including decimals. Units are 1/16em.'),
    ];
    $form['settings']['power_transforms']['position_x']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Position Type'),
      '#options' => [
        '' => $this->t('None'),
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
      ],
      '#default_value' => '',
      '#element_validate' => [
        [static::class, 'validatePowerTransforms'],
      ],
    ];
    $form['settings']['power_transforms']['position_x']['value'] = [
      '#type' => 'number',
      '#title' => $this->t('Position Value'),
      '#min' => 0,
      '#default_value' => '',
      '#states' => [
        'disabled' => [
          ':input[name="settings[power_transforms][position_x][type]"]' => ['value' => ''],
        ],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Insert Icon'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitForm',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * Validate the Font Awesome power transforms.
   */
  public static function validatePowerTransforms($element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if (strlen($value) == 0) {
      $form_state->setValueForElement($element, '');
      return;
    }

    // Check the value of the power transform.
    $transformSettings = $form_state->getValues();
    foreach (array_slice($element['#parents'], 0, 3) as $key) {
      $transformSettings = $transformSettings[$key];
    }

    if (!is_numeric($transformSettings['value'])) {
      $form_state->setError($element, t("Invalid value for Font Awesome Power Transform %value. Please see @iconLink for information on correct values.", [
        '%value' => $value,
        '@iconLink' => Link::fromTextAndUrl(t('the Font Awesome guide to Power Transforms'), Url::fromUri('https://fontawesome.com/how-to-use/on-the-web/styling/power-transforms'))->toString(),
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

    // Remove the prefix if the user accidentally added it.
    if (substr($value, 0, 3) == 'fa-') {
      $value = substr($value, 3);
    }

    // Load the icon data so we can check for a valid icon.
    $iconData = \Drupal::service('fontawesome.font_awesome_manager')->getIconMetadata($value);

    if (!isset($iconData['name'])) {
      $form_state->setError($element, t("Invalid icon name %value. Please see @iconLink for correct icon names.", [
        '%value' => $value,
        '@iconLink' => Link::fromTextAndUrl(t('the Font Awesome icon list'), Url::fromUri('https://fontawesome.com/icons'))->toString(),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#fontawesome-icon-dialog-form', $form));
    }
    else {
      $item = $form_state->getValues();

      // Remove the prefix if the user accidentally added it.
      if (substr($item['icon_name'], 0, 3) == 'fa-') {
        $item['icon_name'] = substr($item['icon_name'], 3);
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
      // Determine the icon style - brands don't allow style.
      $metadata = $this->fontAwesomeManager->getIconMetadata($item['icon_name']);
      $item['style'] = $this->fontAwesomeManager->determinePrefix($metadata['styles'], $item['settings']['style']);
      unset($item['settings']['style']);

      // Remove blank data.
      $item['settings'] = array_filter($item['settings']);

      // Get power transforms.
      $item['power_transforms'] = [];
      foreach ($item['settings']['power_transforms'] as $transform) {
        if (!empty($transform['type'])) {
          $item['power_transforms'][] = $transform['type'] . '-' . $transform['value'];
        }
      }
      unset($item['settings']['power_transforms']);

      // Set the icon attributes.
      $icon_attributes = [
        'attributes' => [
          'class' => [
            trim($item['style'] . ' fa-' . $item['icon_name'] . ' ' . implode(' ', $item['settings'])),
          ],
        ],
      ];
      // If there are power transforms, add them.
      if (count($item['power_transforms']) > 0) {
        $icon_attributes['attributes']['data-fa-transform'] = [implode(' ', $item['power_transforms'])];
      }

      // Load the configuration settings.
      $configuration_settings = $this->configFactory->get('fontawesome.settings');

      // Set the user-selected tag type being used.
      $icon_attributes['tag'] = empty($configuration_settings->get('tag')) ? 'i' : $configuration_settings->get('tag');

      $response->addCommand(new EditorDialogSave($icon_attributes));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

}
