<?php

namespace Drupal\nys_slack\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for nys_openleg module.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * A shortcut to the nys_openleg.settings config collection.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $localConfig;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);

    $this->localConfig = $this->config('nys_slack.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'nys_slack_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['webhook_url'] = [
      '#type' => 'textfield',
      '#title' => 'Webhook URL',
      '#description' => $this->t('The URL to which messages are sent.'),
      '#default_value' => $this->localConfig->get('webhook_url'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['nys_slack.settings'];
  }

}
