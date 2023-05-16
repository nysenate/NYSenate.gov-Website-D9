<?php

namespace Drupal\oembed_providers\Form;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure oEmbed settings form.
 */
class OembedProvidersSettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'oembed_providers.settings';

  /**
   * Cache backend for default cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $defaultCache;

  /**
   * Constructs an OembedProvidersSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Cache\CacheBackendInterface $default_cache
   *   Cache backend for default cache.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $default_cache) {
    $this->setConfigFactory($config_factory);
    $this->defaultCache = $default_cache;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cache.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'oembed_providers_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['external_fetch'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable external fetch of providers'),
      '#description' => $this->t('If enabled, oEmbed providers will be fetched from the <em>oEmbed Providers URL</em>. If disabled, any oEmbed providers must be defined locally.'),
      '#default_value' => $config->get('external_fetch'),
    ];

    $form['oembed_providers_url'] = [
      '#type' => 'url',
      '#title' => $this->t('oEmbed Providers URL'),
      '#description' => $this->t('The URL where Media fetches the list of oEmbed providers'),
      '#default_value' => $this->config('media.settings')->get('oembed_providers_url'),
      '#states' => [
        'visible' => [
          ':input[name="external_fetch"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="external_fetch"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['provider_store_reset'] = [
      '#type' => 'details',
      '#title' => $this->t('Provider caching'),
    ];

    $form['provider_store_reset']['button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear Provider Cache'),
      '#name' => 'provider_store_reset',
    ];

    $form['provider_store_reset']['markup'] = [
      '#markup' => $this->t('<p>Drupal caches the oEmbed provider list with KeyValue storage, so normal cache clears won\'t clear the provider list.</p>'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#name'] == 'provider_store_reset') {
      return;
    }

    parent::validateForm($form, $form_state);

    if ($form_state->getValue('oembed_providers_url') === ''
      && $form_state->getValue('external_fetch') === 1) {

      $form_state->setErrorByName('oembed_providers_url', $this->t('The <em>oEmbed Providers URL</em> field is required.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#name'] == 'provider_store_reset') {
      \Drupal::service('keyvalue')->get('media')->delete('oembed_providers');
    }
    else {
      $this->configFactory->getEditable('media.settings')
        ->set('oembed_providers_url', $form_state->getValue('oembed_providers_url'))
        ->save();

      $this->configFactory->getEditable(static::SETTINGS)
        ->set('external_fetch', (bool) $form_state->getValue('external_fetch'))
        ->save();

      parent::submitForm($form, $form_state);
      $this->defaultCache->delete('oembed_providers:oembed_providers');
    }
  }

}
