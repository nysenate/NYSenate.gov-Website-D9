<?php

namespace Drupal\rabbit_hole\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Url;
use Drupal\rabbit_hole\BehaviorSettingsManagerInterface;
use Drupal\rabbit_hole\Entity\BehaviorSettings;
use Drupal\rabbit_hole\EntityHelper;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides form to manage entity settings.
 */
class RabbitHoleSettingsForm extends ConfigFormBase {

  protected BehaviorSettingsManagerInterface $settingsManager;
  protected RabbitHoleBehaviorPluginManager $behaviorManager;
  protected EntityHelper $entityHelper;

  /**
   * Constructs a new RabbitHoleSettingsForm instance.
   */
  public function __construct(ConfigFactoryInterface $config_factory, BehaviorSettingsManagerInterface $settings_manager, EntityHelper $entity_helper, RabbitHoleBehaviorPluginManager $behavior_manager) {
    parent::__construct($config_factory);
    $this->settingsManager = $settings_manager;
    $this->entityHelper = $entity_helper;
    $this->behaviorManager = $behavior_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('rabbit_hole.behavior_settings_manager'),
      $container->get('rabbit_hole.entity_helper'),
      $container->get('plugin.manager.rabbit_hole_behavior_plugin')
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
    return 'rabbit_hole_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $table = [
      '#type' => 'table',
      '#header' => [
        'type' => $this->t('Entity type'),
        'enabled' => $this->t('Enabled'),
        'bundles' => $this->t('Bundles'),
        'operations' => $this->t('Operations'),
      ],
      '#empty' => $this->t('There are no supported entity types yet.'),
    ];

    foreach ($this->entityHelper->getSupportedEntityTypes() as $entity_type_id => $entity_type) {
      $is_enabled = $this->settingsManager->entityTypeIsEnabled($entity_type_id);

      $table[$entity_type_id]['type'] = [
        '#markup' => $entity_type->getLabel(),
      ];
      $table[$entity_type_id]['enabled'] = [
        '#type' => 'checkbox',
        '#default_value' => $is_enabled,
        '#parents' => ['entity_types', $entity_type_id],
      ];

      // If some bundles have values in the database, we ask to disable
      // overrides and clean-up the fields.
      if ($is_enabled && $this->entityHelper->hasFieldValues($entity_type_id)) {
        $table[$entity_type_id]['enabled']['#description'] = $this->t('Entities of this type has "Rabbit Hole" values in the database.</br> Disable overrides in affected bundles to unblock this option.');
        $table[$entity_type_id]['enabled']['#disabled'] = TRUE;
      }

      $table[$entity_type_id]['bundles'] = [
        '#markup' => $this->getBundlesStatus($entity_type_id),
      ];
      $table[$entity_type_id]['operations'] = [
        '#type' => 'operations',
        '#access' => $is_enabled,
        '#links' => [
          'edit' => [
            'title' => $this->t('Configure'),
            'url' => Url::fromRoute('rabbit_hole.settings.entity_type', ['entity_type_id' => $entity_type_id], [
              'query' => ['destination' => Url::fromRoute('<current>')->toString()],
            ]),
          ],
        ],
      ];
    }

    $form['table'] = $table;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_types = (array) $form_state->getValue('entity_types');

    foreach ($entity_types as $entity_type_id => $enabled) {
      if ($enabled) {
        $this->settingsManager->enableEntityType($entity_type_id);
      }
      else {
        $this->settingsManager->disableEntityType($entity_type_id);
      }
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * Gets Rabbit Hole information about all bundles of given entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *   String with Rabbit Hole status of entity type bundles.
   */
  protected function getBundlesStatus(string $entity_type_id): string {
    if (!$this->settingsManager->entityTypeIsEnabled($entity_type_id)) {
      return '';
    }
    $behaviors = $this->behaviorManager->getBehaviors();

    $bundles_info = [];
    foreach ($this->entityHelper->getBundleInfo($entity_type_id) as $bundle_name => $bundle_info) {
      $config = BehaviorSettings::loadByEntityTypeBundle($entity_type_id, $bundle_name);
      $bundles_info[] = $this->t('%bundle (Behavior: %behavior; Allow overrides: %override_status)', [
        '%bundle' => $bundle_info['label'],
        '%behavior' => $behaviors[$config->getAction()],
        '%override_status' => $this->entityHelper->hasRabbitHoleField($entity_type_id, $bundle_name) ? $this->t('Yes') : $this->t('No'),
      ]);
    }
    return implode('<br />', $bundles_info);
  }

}
