<?php

namespace Drupal\fontawesome\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Implementation of Font Awesome icon formatter.
 *
 * @FieldFormatter(
 *   id = "fontawesome_icon_formatter",
 *   label = @Translation("Font Awesome Icon"),
 *   field_types = {
 *     "fontawesome_icon"
 *   }
 * )
 */
class FontAwesomeIconFormatter extends FormatterBase implements ContainerFactoryPluginInterface {
  /**
   * Drupal configuration service container.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ConfigFactory $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->configFactory = $config_factory;
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Load the configuration settings.
    $configuration_settings = $this->configFactory->get('fontawesome.settings');

    // Setting for optional download link.
    $elements['layers'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display multi-value fields as layers?'),
      '#default_value' => $this->getSetting('layers'),
      '#description' => $this->t('Layers are the new way to place icons and text visually on top of each other, replacing the Font Awesome classic icons stacks. With this new approach you can use more than 2 icons. Layers are awesome when you don’t want your page’s background to show through, or when you do want to use multiple colors, layer several icons, layer text, or layer counters onto an icon. Note that layers only work with the SVG version of Font Awesome. For more information, see @layersLink.', [
        '@layersLink' => Link::fromTextAndUrl($this->t('the Font Awesome guide to layers'), Url::fromUri('https://fontawesome.com/how-to-use/on-the-web/styling/layering', [
          'attributes' => [
            'target' => '_blank',
          ],
        ]))->toString(),
      ]),
      // Disable power transforms for webfonts.
      '#disabled' => $configuration_settings->get('method') == 'webfonts',
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();

    $summary = [];

    // Load the configuration settings.
    $configuration_settings = $this->configFactory->get('fontawesome.settings');

    // Show whether or not we are layering the icons.
    $summary[] = $this->t('Display multi-value fields as layers: <strong>@layersSetting</strong>', [
      '@layersSetting' => (($settings['layers'] && $configuration_settings->get('method') != 'webfonts') ? 'Yes' : 'No'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'layers' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Early opt-out if the field is empty.
    if (count($items) <= 0) {
      return [];
    }

    // Load the configuration settings.
    $configurationSettings = $this->configFactory->get('fontawesome.settings');

    // Loop over each icon and build data.
    $icons = [];
    foreach ($items as $item) {
      // Get the icon settings.
      $iconSettings = unserialize($item->get('settings')->getValue());
      $cssStyles = [];

      // Format mask.
      $iconMask = '';
      if (!empty($iconSettings['masking']['mask'])) {
        $iconMask = $iconSettings['masking']['style'] . ' fa-' . $iconSettings['masking']['mask'];
      }
      unset($iconSettings['masking']);

      // Format power transforms.
      $iconTransforms = [];
      if (isset($iconSettings['power_transforms'])) {
        $powerTransforms = $iconSettings['power_transforms'];
        foreach ($powerTransforms as $transform) {
          if (!empty($transform['type'])) {
            $iconTransforms[] = $transform['type'] . '-' . $transform['value'];
          }
        }
        unset($iconSettings['power_transforms']);
      }

      // Move duotone settings into the render.
      if (isset($iconSettings['duotone'])) {
        // Handle swap opacity flag.
        if (!empty($iconSettings['duotone']['swap-opacity'])) {
          $iconSettings['swap-opacity'] = $iconSettings['duotone']['swap-opacity'];
        }
        // Handle custom CSS styles.
        if (!empty($iconSettings['duotone']['opacity']['primary'])) {
          $cssStyles[] = '--fa-primary-opacity: ' . $iconSettings['duotone']['opacity']['primary'] . ';';
        }
        if (!empty($iconSettings['duotone']['opacity']['secondary'])) {
          $cssStyles[] = '--fa-secondary-opacity: ' . $iconSettings['duotone']['opacity']['secondary'] . ';';
        }
        if (!empty($iconSettings['duotone']['color']['primary'])) {
          $cssStyles[] = '--fa-primary-color: ' . $iconSettings['duotone']['color']['primary'] . ';';
        }
        if (!empty($iconSettings['duotone']['color']['secondary'])) {
          $cssStyles[] = '--fa-secondary-color: ' . $iconSettings['duotone']['color']['secondary'] . ';';
        }

        unset($iconSettings['duotone']);
      }

      // Add additional CSS styles if needed.
      if (isset($iconSettings['additional_classes'])) {
        $cssStyles[] = $iconSettings['additional_classes'];
      }

      $icons[] = [
        '#theme' => 'fontawesomeicon',
        '#tag' => $configurationSettings->get('tag'),
        '#name' => 'fa-' . $item->get('icon_name')->getValue(),
        '#style' => $item->get('style')->getValue(),
        '#settings' => implode(' ', array_filter($iconSettings)),
        '#transforms' => implode(' ', $iconTransforms),
        '#mask' => $iconMask,
        '#css' => implode(' ', $cssStyles),
      ];
    }

    // Get the icon settings.
    $settings = $this->getSettings();

    return [
      [
        '#theme' => 'fontawesomeicons',
        '#icons' => $icons,
        '#layers' => $settings['layers'],
      ],
    ];
  }

}
