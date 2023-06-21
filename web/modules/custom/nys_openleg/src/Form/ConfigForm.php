<?php

namespace Drupal\nys_openleg\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\ProxyClass\Routing\RouteBuilder;
use Drupal\nys_openleg\StatuteHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for nys_openleg module.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * Drupal's route builder service.
   *
   * @var \Drupal\Core\ProxyClass\Routing\RouteBuilder
   */
  protected RouteBuilder $builder;

  /**
   * A shortcut to the nys_openleg.settings config collection.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $localConfig;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, RouteBuilder $builder) {
    $this->builder = $builder;
    parent::__construct($config_factory);

    $this->localConfig = $this->config('nys_openleg.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('config.factory'),
          $container->get('router.builder')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'openleg_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('nys_openleg.settings');

    $form['base_path'] = [
      '#type' => 'textfield',
      '#title' => 'Base Path',
      '#description' => $this->t('The base URL to which this module will respond.  This should be a complete relative path.  If this is left blank, it will default to "/legislation/laws".'),
      '#default_value' => $config->get('base_path'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Flag indicating if routes need to be rebuilt after save.
    $rebuild = FALSE;

    // Check for a modified base path.
    $current_path = $this->localConfig->get('base_path');
    $new_path = $form_state->getValue('base_path') ?: StatuteHelper::DEFAULT_LANDING_URL;
    if ($current_path !== $new_path) {
      $this->localConfig->set('base_path', $form_state->getValue('base_path'));
      $rebuild = TRUE;
    }

    $this->localConfig->save();

    if ($rebuild) {
      $this->builder->rebuild();
      StatuteHelper::clearCache('law-types');
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['nys_openleg.settings'];
  }

}
