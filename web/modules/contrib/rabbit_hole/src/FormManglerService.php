<?php

namespace Drupal\rabbit_hole;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\rabbit_hole\Entity\BehaviorSettings;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPlugin\DisplayPage;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides necessary form alterations.
 */
class FormManglerService {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  const RABBIT_HOLE_USE_DEFAULT = 'bundle_default';

  protected EntityHelper $entityHelper;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  private $entityTypeManager;

  /**
   * Behavior plugin manager.
   *
   * @var \Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager|null
   */
  private $rhBehaviorPluginManager;

  /**
   * Rabbit hole behavior invoker.
   *
   * @var \Drupal\rabbit_hole\BehaviorInvokerInterface
   */
  protected $behaviorInvoker;

  /**
   * The behavior settings manager.
   *
   * @var \Drupal\rabbit_hole\BehaviorSettingsManager
   */
  private $rhBehaviorSettingsManager;

  /**
   * Constructs a new FormManglerService instance.
   */
  public function __construct(
    EntityTypeManagerInterface $etm,
    RabbitHoleBehaviorPluginManager $behavior_plugin_manager,
    BehaviorSettingsManager $behavior_settings_manager,
    TranslationInterface $translation,
    BehaviorInvokerInterface $behavior_invoker,
    EntityHelper $entityHelper) {

    $this->entityTypeManager = $etm;
    $this->rhBehaviorPluginManager = $behavior_plugin_manager;
    $this->rhBehaviorSettingsManager = $behavior_settings_manager;
    $this->stringTranslation = $translation;
    $this->behaviorInvoker = $behavior_invoker;
    $this->entityHelper = $entityHelper;
  }

  /**
   * Builds Rabbit Hole settings for the entity form.
   */
  public function settingsForm(array &$form, FormStateInterface $form_state, array $settings) {
    $form['action'] = [
      '#type' => 'select',
      '#title' => $this->t('Behavior'),
      '#options' => $this->rhBehaviorPluginManager->getBehaviors(),
      '#default_value' => $settings['action'],
      '#description' => $this->t('What should happen when someone tries to visit entity canonical page?'),
      '#attributes' => ['class' => ['rabbit-hole-action-setting']],
    ];

    // Apply form modifications from plugins.
    foreach ($this->rhBehaviorPluginManager->getDefinitions() as $id => $definition) {
      $plugin = $this->rhBehaviorPluginManager->createInstance($id, $settings['configuration']);
      $plugin_form = $plugin->buildConfigurationForm([], $form_state);

      if ($plugin_form) {
        $form[$id] = [
          '#type' => 'details',
          '#title' => $this->t('@plugin settings', ['@plugin' => $definition['label']]),
          '#open' => TRUE,
          '#tree' => TRUE,
          '#states' => [
            'visible' => [
              ':input[name="' . $this->getInputSelector($form, 'action') . '"]' => ['value' => $id],
            ],
          ],
        ] + $plugin_form;
      }
    }
  }

  /**
   * Builds Rabbit Hole settings form for the given entity bundle.
   */
  public function bundleSettingsForm(array &$form, FormStateInterface $form_state, string $entity_type_id, string $bundle_name) {
    $entity_type = $this->entityTypeManager->getStorage($entity_type_id)->getEntityType();
    $config = BehaviorSettings::loadByEntityTypeBundle($entity_type_id, $bundle_name);
    $this->settingsForm($form, $form_state, $config->getSettings());

    $form['allow_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow these settings to be overridden for individual entities'),
      '#default_value' => $this->entityHelper->hasRabbitHoleField($entity_type->id(), $bundle_name),
      '#description' => $this->t('If checked, users with the %permission permission will be able to override these settings for individual entities.', [
        '%permission' => $this->t('Administer Rabbit Hole settings for @entity_type', ['@entity_type' => $entity_type->getLabel()]),
      ]),
    ];

    $form['no_bypass'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable permissions-based bypassing'),
      '#default_value' => $config->getNoBypass(),
      '#description' => $this->t("If checked, users won't be able to bypass configured Rabbit Hole behavior. It will be applied to Administrators and other users with bypass permissions."),
      '#states' => [
        'invisible' => [
          ':input[name="' . $this->getInputSelector($form, 'bypass_message') . '"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['bypass_message'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display a message when viewing the page'),
      '#default_value' => $config->getBypassMessage(),
      '#description' => $this->t("If checked, users who bypassed the Rabbit Hole action, will see a warning message when viewing the page."),
      '#states' => [
        'invisible' => [
          ':input[name="' . $this->getInputSelector($form, 'no_bypass') . '"]' => ['checked' => TRUE],
        ],
      ],
    ];
  }

  /**
   * Validates bundle settings form.
   */
  public function bundleSettingsFormValidate($form, FormStateInterface $form_state) {
    $bundles = $form_state->getValue('bundles') ?? [];

    foreach ($bundles as $bundle_name => $form_values) {
      $action = $form_values['action'];

      if (isset($form['bundles'][$bundle_name]['settings'][$action])) {
        /** @var \Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginInterface $behavior_plugin */
        $behavior_plugin = $this->rhBehaviorPluginManager->createInstance($action);
        $subform_state = SubformState::createForSubform($form['bundles'][$bundle_name]['settings'][$action], $form, $form_state);
        $behavior_plugin->validateConfigurationForm($form['bundles'][$bundle_name]['settings'][$action], $subform_state);
      }
    }
  }

  /**
   * Saves bundle settings into the configuration.
   */
  public function bundleSettingsFormSubmit($form, FormStateInterface $form_state) {
    $entity_type_id = $form_state->get('entity_type_id');
    $bundles = $form_state->getValue('bundles') ?? [];

    foreach ($bundles as $bundle_name => $form_values) {
      $action = $form_values['action'];

      $config = BehaviorSettings::loadByEntityTypeBundle($entity_type_id, $bundle_name);
      $config->setAction($action)
        ->setNoBypass($form_values['no_bypass'])
        ->setBypassMessage($form_values['bypass_message']);

      // Get action settings if it exists in the form.
      if (isset($form['bundles'][$bundle_name]['settings'][$action])) {
        /** @var \Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginInterface $behavior_plugin */
        $behavior_plugin = $this->rhBehaviorPluginManager->createInstance($action);
        $subform_state = SubformState::createForSubform($form['bundles'][$bundle_name]['settings'][$action], $form, $form_state);
        $behavior_plugin->submitConfigurationForm($form['bundles'][$bundle_name]['settings'][$action], $subform_state);
        $config->setConfiguration($behavior_plugin->getConfiguration());
      }
      $config->save();

      // Create or remove field from the entity bundle.
      if (empty($form_values['allow_override'])) {
        $this->entityHelper->removeRabbitHoleField($entity_type_id, $bundle_name);
      }
      else {
        $this->entityHelper->createRabbitHoleField($entity_type_id, $bundle_name);
      }
    }
  }

  /**
   * Adds a notice about the new settings page.
   */
  public function addRabbitHoleChangeNotice(&$element) {
    $element['rabbit_hole'] = [
      '#type' => 'details',
      '#title' => $this->t('Rabbit Hole settings'),
      '#collapsed' => TRUE,
      '#collapsible' => TRUE,
      '#tree' => FALSE,
      '#weight' => 10,
      '#group' => 'additional_settings',
      '#attributes' => ['class' => ['rabbit-hole-settings-form']],
      'change_notice' => [
        '#markup' => $this->t('Rabbit Hole settings were moved into a dedicated @settings_page.', [
          '@settings_page' => Link::fromTextAndUrl('settings page', Url::fromRoute('rabbit_hole.settings'))->toString(),
        ]),
      ],
    ];
  }

  /**
   * Redirects back to entity edit form to prevent hitting error page.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function redirectToEntityEditForm(array $form, FormStateInterface $form_state) {
    $entity = $form_state->getFormObject()->getEntity();
    $plugin = $this->behaviorInvoker->getBehaviorPlugin($entity);

    // Set form redirect to entity edit page to prevent 403/404 errors if
    // Rabbit Hole is enabled and the user doesn't have the bypass access.
    if ($plugin !== NULL && !$plugin instanceof DisplayPage) {
      $redirect = $form_state->getRedirect();

      // Change redirect URL only if current one is set to canonical page.
      if ($redirect instanceof Url && $redirect->toString() === $entity->toUrl()->toString()) {
        $form_state->setRedirectUrl($entity->toUrl('edit-form'));
      }
    }
  }

  /**
   * Builds the full input selector based on available parents.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param string $name
   *   Input name.
   * @return string
   *   The full input path.
   */
  protected function getInputSelector(array $form, string $name): string {
    $parents = $form['#parents'] ?? [];
    $selector = $root = array_shift($parents);
    if ($parents) {
      $selector = $root . '[' . implode('][', array_merge($parents, [$name])) . ']';
    }
    return $selector;
  }

}
