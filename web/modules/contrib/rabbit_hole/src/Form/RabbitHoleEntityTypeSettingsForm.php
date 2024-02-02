<?php

namespace Drupal\rabbit_hole\Form;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\rabbit_hole\BehaviorSettingsManager;
use Drupal\rabbit_hole\BehaviorSettingsManagerInterface;
use Drupal\rabbit_hole\EntityHelper;
use Drupal\rabbit_hole\FormManglerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides form to manage entity bundles settings.
 */
class RabbitHoleEntityTypeSettingsForm extends ConfigFormBase {

  protected EntityHelper $entityHelper;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected EntityTypeInterface $entityType;
  protected BehaviorSettingsManagerInterface $rhSettingsManager;
  protected FormManglerService $formMangler;

  protected bool $submitted = FALSE;
  protected array $bundlesToDisable = [];

  /**
   * RabbitHoleEntityTypeSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\rabbit_hole\BehaviorSettingsManager $rh_settings_manager
   *   The behavior settings manager.
   * @param \Drupal\rabbit_hole\FormManglerService $form_mangler
   *   The form mangler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityHelper $entity_helper, EntityTypeManagerInterface $entity_type_manager, BehaviorSettingsManager $rh_settings_manager, FormManglerService $form_mangler) {
    parent::__construct($config_factory);
    $this->entityHelper = $entity_helper;
    $this->entityTypeManager = $entity_type_manager;
    $this->rhSettingsManager = $rh_settings_manager;
    $this->formMangler = $form_mangler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('rabbit_hole.entity_helper'),
      $container->get('entity_type.manager'),
      $container->get('rabbit_hole.behavior_settings_manager'),
      $container->get('rabbit_hole.form_mangler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['rabbit_hole.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'rabbit_hole_entity_type_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL): array {
    try {
      $this->entityType = $this->entityTypeManager->getDefinition($entity_type_id);
    }
    catch (PluginNotFoundException $e) {
      throw new NotFoundHttpException();
    }
    if (!$this->entityHelper->entityTypeIsSupported($this->entityType) || !$this->rhSettingsManager->entityTypeIsEnabled($this->entityType->id())) {
      throw new NotFoundHttpException();
    }
    $form_state->set('entity_type_id', $entity_type_id);

    $form['#title'] = $this->t('Configure %label entity type', [
      '%label' => $this->entityType->getLabel() ?: $entity_type_id,
    ]);

    $form['bundles'] = [
      '#type' => 'table',
      '#header' => [
        $this->entityType->getBundleLabel(),
        $this->t('Configuration'),
      ],
    ];

    $bundles = $this->entityHelper->getBundleInfo($entity_type_id);
    if ($bundles) {
      foreach ($bundles as $bundle_name => $bundle_info) {
        $form['bundles'][$bundle_name]['name'] = array(
          '#type' => 'item',
          '#plain_text' => $bundle_info['label'],
        );
        $form['bundles'][$bundle_name]['settings'] = [
          '#type' => 'container',
          '#tree' => TRUE,
          '#parents' => ['bundles', $bundle_name],
        ];
        $this->formMangler->bundleSettingsForm($form['bundles'][$bundle_name]['settings'], $form_state, $entity_type_id, $bundle_name);
      }
    }
    else {
      $form['empty_message'] = [
        '#markup' => $this->t('No bundles available.'),
      ];
    }

    if ($this->bundlesToDisable) {
      $labels = [];
      foreach ($this->bundlesToDisable as $bundle_name) {
        $labels[] = $bundles[$bundle_name]['label'];
      }

      $this->messenger()->addWarning($this->t("Disabling overrides for %bundles entities will remove their existing values from the database. Save again to confirm or @cancel.", [
        '%bundles' => implode(', ', $labels),
        '@cancel' => Link::createFromRoute($this->t('cancel'), '<current>')->toString(),
      ]));
      $form['actions']['cancel'] = [
        '#type' => 'link',
        '#title' => $this->t('Cancel'),
        '#attributes' => ['class' => ['button']],
        '#url' => Url::fromRoute('<current>'),
        '#weight' => 10,
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $bundles = $form_state->getValue('bundles') ?? [];
    if (empty($bundles)) {
      return;
    }

    parent::validateForm($form, $form_state);
    $this->formMangler->bundleSettingsFormValidate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bundles = $form_state->getValue('bundles') ?? [];
    if (empty($bundles)) {
      return;
    }

    $entity_type_id = $form_state->get('entity_type_id');

    // If this form has not yet been confirmed and there are bundles with
    // field values, then trigger the rebuild that will show a confirmation.
    if (!$this->submitted) {
      // Get the list of bundles with disabled "allow_override" checkbox and
      // values in the database.
      $bundles = $form_state->getValue('bundles') ?? [];
      foreach ($bundles as $bundle => $form_values) {
        if (empty($form_values['allow_override']) && $this->entityHelper->hasFieldValues($entity_type_id, $bundle)) {
          $this->bundlesToDisable[] = $bundle;
        }
      }

      if (!empty($this->bundlesToDisable)) {
        $this->submitted = TRUE;
        $form_state->setRebuild();
        return;
      }
    }

    parent::submitForm($form, $form_state);
    $this->formMangler->bundleSettingsFormSubmit($form, $form_state);
  }

}
