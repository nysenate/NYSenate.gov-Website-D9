<?php

namespace Drupal\fontawesome_iconpicker_widget\Plugin\Field\FieldWidget;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\fontawesome\Plugin\Field\FieldWidget\FontAwesomeIconWidget;
use Drupal\fontawesome_iconpicker_widget\IconManagerServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\fontawesome\FontAwesomeManagerInterface;

/**
 * Fontawesome Iconpicker Widget.
 *
 * @FieldWidget(
 *   id = "fontawesome_iconpicker_widget",
 *   module = "fontawesome_iconpicker_widget",
 *   label = @Translation("Font Awesome Iconpicker Widget"),
 *   field_types = {
 *     "fontawesome_icon"
 *   }
 * )
 */
class FontAwesomeIconpickerWidget extends FontAwesomeIconWidget {

  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Drupal\fontawesome_iconpicker_widget\IconManagerInterface definition.
   *
   * @var \Drupal\fontawesome_iconpicker_widget\IconManagerInterface
   */
  protected $iconManager;

  /**
   * Drupal Font Awesome manager service.
   *
   * @var \Drupal\fontawesome\FontAwesomeManagerInterface
   */
  protected $fontAwesomeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ConfigFactory $config_factory, FontAwesomeManagerInterface $font_awesome_manager, AccountInterface $current_user, MessengerInterface $messenger, IconManagerServiceInterface $icon_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $config_factory, $font_awesome_manager, $current_user);
    $this->messenger = $messenger;
    $this->iconManager = $icon_manager;
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
      $container->get('current_user'),
      $container->get('messenger'),
      $container->get('fontawesome_iconpicker_widget.icon_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Throw error if library file not found.
    if (!fontawesome_iconpicker_widget_check_installed()) {
      $this->messenger->addWarning($this->t('The fontIconPicker library could not be found. To use the Font Awesome Iconpicker, please verify that the fontIconPicker library is installed correctly. Please see the Font Awesome Iconpicker submodule README file for more details.'));
    }

    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $icon_list = $this->iconManager->getFormattedIconList();
    $term_list = $this->iconManager->getFormattedTermList();

    $icon_style_default_value = $items[$delta]->get('style')->getValue();
    $icon_name_default_value = $items[$delta]->get('icon_name')->getValue();
    $icon_default = NULL;
    if ($icon_name_default_value && $icon_style_default_value) {
      $icon_default = $icon_style_default_value . ' fa-' . $icon_name_default_value;
    }

    $element['icon_name'] = [
      '#type' => 'textfield',
      '#title' => $cardinality == 1 ? $this->fieldDefinition->getLabel() : $this->t('Icon Name'),
      '#required' => $element['#required'],
      '#default_value' => $icon_default,
      '#attributes' => [
        'class' => ['fontawesome-iconpicker-icon'],
      ],
      '#attached' => [
        'library' => ['fontawesome_iconpicker_widget/fontawesome-iconpicker'],
        'drupalSettings' => [
          'fontawesomeIcons' => [
            'icons' => $icon_list,
            'terms' => $term_list,
          ],
        ],
      ],
      '#element_validate' => [
        [static::class, 'validateIconName'],
      ],
    ];

    $element['settings']['style'] = [
      '#type' => 'hidden',
      '#default_value' => $icon_style_default_value,
    ];
    // Get current settings.
    $settings = $items[$delta]->get('settings')->getValue() ?? '';
    $iconSettings = unserialize($settings);

    $mask_icon = '';
    $mask_style = NULL;
    if ((isset($iconSettings['masking']['mask']) && $iconSettings['masking']['mask'] !== '') && (isset($iconSettings['masking']['style']) && $iconSettings['masking']['style'] !== '')) {
      $mask_style = $iconSettings['masking']['style'];
      $mask_icon = $mask_style . ' fa-' . $iconSettings['masking']['mask'];
    }
    $element['settings']['masking']['mask'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon Name'),
      '#default_value' => $mask_icon,
      '#attributes' => [
        'class' => ['fontawesome-iconpicker-mask'],
      ],
      '#element_validate' => [
        [static::class, 'validateIconName'],
      ],
    ];

    $element['settings']['masking']['style'] = [
      '#type' => 'hidden',
      '#default_value' => $mask_style,
    ];

    return $element;
  }

  /**
   * Validate the Font Awesome icon name.
   */
  public static function validateIconName($element, FormStateInterface $form_state) {
    $iconManager = \Drupal::service('fontawesome_iconpicker_widget.icon_manager');
    $fontAwesomeManager = \Drupal::service('fontawesome.font_awesome_manager');
    $configuration_settings = \Drupal::config('fontawesome.settings');
    $value = $element['#value'];
    if (strlen($value) == 0) {
      $form_state->setValueForElement($element, '');
      return;
    }

    $icon_base = $iconManager->getIconBaseNameFromClass($value);
    $iconData = $fontAwesomeManager->getIconMetadata($icon_base);

    if (!isset($iconData['name']) && !$configuration_settings->get('bypass_validation')) {
      $form_state->setError($element, t("Invalid icon"));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Loop over each item and set the data properly.
    foreach ($values as &$item) {
      // Reset $item['icon_name'] to the base name.
      if (!empty($item['icon_name'])) {
        $class = $item['icon_name'];
        $item['icon_name'] = $this->iconManager->getIconBaseNameFromClass($class);
        $item['style'] = $this->iconManager->getIconPrefixFromClass($class);
      }
      unset($item['settings']['style']);

      if (!empty($item['settings']['masking']['mask'])) {
        $mask_class = $item['settings']['masking']['mask'];
        $item['settings']['masking']['mask'] = $this->iconManager->getIconBaseNameFromClass($mask_class);
        $item['settings']['masking']['style'] = $this->iconManager->getIconPrefixFromClass($mask_class);
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

      $item['settings'] = serialize(array_filter($item['settings']));
    }

    return $values;
  }

}
