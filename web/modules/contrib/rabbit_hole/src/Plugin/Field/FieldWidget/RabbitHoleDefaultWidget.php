<?php

namespace Drupal\rabbit_hole\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rabbit_hole\Entity\BehaviorSettings;
use Drupal\rabbit_hole\EntityHelper;
use Drupal\rabbit_hole\FormManglerService;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default field widget for 'Rabbit hole' field-type.
 *
 * @FieldWidget(
 *   id = "rabbit_hole_default",
 *   label = @Translation("Rabbit hole"),
 *   field_types = {
 *     "rabbit_hole"
 *   }
 * )
 */
class RabbitHoleDefaultWidget extends WidgetBase {

  /**
   * Rabbit hole behavior plugins manager.
   *
   * @var \Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager
   */
  protected $rhBehaviorPluginManager;

  /**
   * Provides operations for bundles configuration.
   *
   * @var \Drupal\rabbit_hole\EntityHelper
   */
  protected $rhEntityHelper;

  /**
   * Rabbit hole behaviours (actions) form alterations.
   *
   * @var \Drupal\rabbit_hole\FormManglerService
   */
  protected $rhFormManager;

  /**
   * Current active user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a RabbitHole object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager $rh_behavior_plugin_manager
   *   Rabbit hole behavior plugins manager.
   * @param \Drupal\rabbit_hole\EntityHelper $rh_entity_helper
   *   Rabbit hole behaviours (actions) form alterations.
   * @param \Drupal\rabbit_hole\FormManglerService $rh_form_manager
   *   Rabbit hole behaviours (actions) form alterations.
   */
  public function __construct($plugin_id,
                              $plugin_definition,
                              FieldDefinitionInterface $field_definition,
                              array $settings,
                              array $third_party_settings,
                              RabbitHoleBehaviorPluginManager $rh_behavior_plugin_manager,
                              EntityHelper $rh_entity_helper,
                              FormManglerService $rh_form_manager,
                              AccountProxyInterface $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->rhBehaviorPluginManager = $rh_behavior_plugin_manager;
    $this->rhEntityHelper = $rh_entity_helper;
    $this->rhFormManager = $rh_form_manager;
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
      $container->get('plugin.manager.rabbit_hole_behavior_plugin'),
      $container->get('rabbit_hole.entity_helper'),
      $container->get('rabbit_hole.form_mangler'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'advanced' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['advanced'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the setting field on the advanced sidebar.'),
      '#description' => $this->t('Option works only when an advanced sidebar exists.'),
      '#default_value' => $this->getSetting('advanced'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $advanced = $this->getSetting('advanced') ? $this->t('advanced sidebar') : $this->t('main form');
    $summary[] = $this->t('Field location: show on the @advanced', ['@advanced' => $advanced]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Prepare required variables.
    $entity = $form_state->getFormObject()->getEntity();
    $entity_type_id = $entity->getEntityTypeId();
    $form_state->set('entity_type_id', $entity_type_id);

    // User without 'rabbit hole administer *' permission should not be able to
    // see and administer Rabbit Hole settings.
    if (!$this->currentUser->hasPermission('rabbit hole administer ' . $entity_type_id)) {
      return $element;
    }

    $bundle = $entity->bundle();
    $bundle_settings = BehaviorSettings::loadByEntityTypeBundle($entity_type_id, $bundle);
    $action_options = $this->rhBehaviorPluginManager->getBehaviors();

    // Prepare default action.
    $input = $form_state->getUserInput();
    $action = NestedArray::getValue($input,
      [$items->getName(), $delta, 'action']);
    if (!$action) {
      $action = $items[$delta]->action ?? FormManglerService::RABBIT_HOLE_USE_DEFAULT;
    }

    // Prepare element wrapper.
    $id_prefix = implode('-', [$items->getName(), $delta]);
    $wrapper_id = $id_prefix . '-ajax-wrapper';
    $element = [
      '#type' => 'details',
      '#open' => FALSE,
      '#attributes' => [
        'class' => ['rabbit-hole-settings-form'],
      ],
      '#attached' => [
        'library' => ['rabbit_hole/field-ui'],
      ],
    ] + $element;

    if ($this->getSetting('advanced')) {
      $element['#group'] = 'advanced';
    }

    $element['action'] = [
      '#type' => 'select',
      '#title' => $this->t('Behaviour'),
      '#required' => FALSE,
      '#empty_option' => $this->t('Global @bundle behavior (@setting)', [
        '@bundle' => strtolower($this->rhEntityHelper->getBundleLabel($entity_type_id, $bundle)),
        '@setting' => $action_options[$bundle_settings->getAction()],
      ]),
      '#empty_value' => FormManglerService::RABBIT_HOLE_USE_DEFAULT,
      '#options' => $action_options,
      '#default_value' => $action,
      '#ajax' => [
        'callback' => [$this, 'ajaxRefresh'],
        'wrapper' => $wrapper_id,
      ],
    ];

    // Container for plugin-related settings.
    $element['settings'] = [
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];

    if ($action) {
      $settings = $items[$delta]->settings ?? [];
      if ($this->rhBehaviorPluginManager->getDefinition($action, FALSE)) {
        if ($settings_form = $this->rhBehaviorPluginManager->createInstance($action, $settings)->buildConfigurationForm([], $form_state)) {
          $element['settings'] += $settings_form;
        }
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * Override a parent function a bit - add a specific value extraction.
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    // Field always has only 1 value.
    $delta = 0;
    $field_name = $this->fieldDefinition->getName();

    // Extract the values from $form_state->getValues().
    $path = array_merge($form['#parents'], [$field_name, $delta]);
    $key_exists = NULL;
    $values = NestedArray::getValue($form_state->getValues(), $path, $key_exists);
    $values = [$values];

    if ($key_exists) {
      // Account for drag-and-drop reordering if needed.
      if (!$this->handlesMultipleValues()) {
        // Remove the 'value' of the 'add more' button.
        unset($values['add_more']);

        // The original delta, before drag-and-drop reordering, is needed to
        // route errors to the correct form element.
        foreach ($values as $delta => &$value) {
          $value['_original_delta'] = $delta;
        }

        usort($values, function ($a, $b) {
          return SortArray::sortByKeyInt($a, $b, '_weight');
        });
      }

      // Let the widget massage the submitted values.
      $values = $this->massageFormValues($values, $form, $form_state);

      // Assign the values and remove the empty ones.
      $items->setValue($values);
      $items->filterEmptyItems();

      // Put delta mapping in $form_state, so that flagErrors() can use it.
      $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
      foreach ($items as $delta => $item) {
        $field_state['original_deltas'][$delta] = $item->_original_delta ?? $delta;
        unset($item->_original_delta, $item->_weight);
      }
      static::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);
    }
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#array_parents'];
    // Find the path to settings fieldset, which should be on the same level.
    if ($action_index = array_search('action', $parents, TRUE)) {
      $parents[$action_index] = 'settings';
    }
    return NestedArray::getValue($form, $parents);
  }

}
