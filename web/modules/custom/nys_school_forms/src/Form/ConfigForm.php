<?php

namespace Drupal\nys_school_forms\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Admin config page for nys_school_forms.
 */
class ConfigForm extends ConfigFormBase {

  const CONFIG_NAME = 'nys_school_forms.config';

  /**
   * Drupal's Entity Type Manager Service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entityTypeManager, $typedConfigManager = NULL) {
    $this->entityTypeManager = $entityTypeManager;
    parent::__construct($config_factory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('config.factory'), $container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'nys_school_forms_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [static::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(static::CONFIG_NAME);
    $types = $this->getFormTypes();

    $form['date_ranges'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Default Date Ranges'),
      '#description' => $this->t("Specifies the default beginning and ending date ranges for searches."),
      '#tree' => TRUE,
    ];

    foreach ($types as $val) {
      $safe = $val['safe_name'];
      $defaults = $config->get('default_search_range.' . $safe) ?? [
        'begin' => NULL,
        'end' => NULL,
      ];
      $one_form = [
        '#type' => 'fieldset',
        '#title' => $val['name'],
      ];
      $one_form['begin'] = [
        '#type' => 'date',
        '#title' => 'Start',
        '#date_date_format' => 'Y-m-d',
        '#default_value' => $defaults['begin'],
      ];
      $one_form['end'] = [
        '#type' => 'date',
        '#title' => 'End',
        '#date_date_format' => 'Y-m-d',
        '#default_value' => $defaults['end'],
      ];
      $form['date_ranges'][$safe] = $one_form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config(static::CONFIG_NAME);
    foreach ($form_state->getValue('date_ranges') as $key => $val) {
      $config->set('default_search_range.' . $key, $val);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Helper method to enumerate all defined form types.
   *
   * @return array
   *   Indexed by tid, each element is an array with 'name' and 'safe_name'.
   *
   * @todo This should be somewhere else a little more central.
   */
  protected function getFormTypes(): array {
    try {
      $types = $this->entityTypeManager->getStorage('taxonomy_term')
        ->loadTree('school_form_type');
    }
    catch (\Throwable $e) {
      $this->logger('nys_school_forms')
        ->error("Could not load school form types", ['@msg' => $e->getMessage()]);
      $types = [];
    }
    $ret = [];
    foreach ($types as $val) {
      $ret[$val->tid] = [
        'name' => $val->name,
        'safe_name' => strtolower(preg_replace('/\W/', '_', $val->name)),
      ];
    }
    return $ret;
  }

}
