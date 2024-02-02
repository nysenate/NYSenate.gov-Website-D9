<?php

namespace Drupal\turnstile\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure Turnstile settings for this site.
 */
class TurnstileAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'turnstile_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['turnstile.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('turnstile.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];

    $form['general']['turnstile_site_key'] = [
      '#default_value' => $config->get('site_key'),
      '#description' => $this->t('The site key given to you when you <a href=":url">register for Turnstile</a>.', [':url' => 'https://cloudflare.com']),
      '#maxlength' => 50,
      '#required' => TRUE,
      '#title' => $this->t('Site key'),
      '#type' => 'textfield',
    ];

    $form['general']['turnstile_secret_key'] = [
      '#default_value' => $config->get('secret_key'),
      '#description' => $this->t('The secret key given to you when you <a href=":url">register for Turnstile</a>.', [':url' => 'https://cloudflare.com']),
      '#maxlength' => 50,
      '#required' => TRUE,
      '#title' => $this->t('Secret key'),
      '#type' => 'textfield',
    ];

    $form['general']['turnstile_src'] = [
      '#default_value' => $config->get('turnstile_src'),
      '#description' => $this->t('Default URL is ":url".', [':url' => 'https://challenges.cloudflare.com/turnstile/v0/api.js']),
      '#maxlength' => 200,
      '#required' => TRUE,
      '#title' => $this->t('Turnstile JavaScript resource URL'),
      '#type' => 'textfield',
    ];

    // Widget configurations.
    $form['widget'] = [
      '#type' => 'details',
      '#title' => $this->t('Widget settings'),
      '#open' => TRUE,
    ];

    $form['widget']['turnstile_theme'] = [
      '#default_value' => $config->get('widget.theme'),
      '#description' => $this->t('Defines which theme to use for Turnstile.'),
      '#options' => [
        'light' => $this->t('Light (default)'),
        'dark' => $this->t('Dark'),
        'auto' => $this->t('Auto'),
      ],
      '#title' => $this->t('Theme'),
      '#type' => 'select',
    ];

    $form['widget']['turnstile_language'] = [
      '#default_value' => $config->get('widget.language'),
      '#description' => $this->t('Language to display, must be either: auto (default) to use the language that the visitor has chosen.'),
      '#options' => [
        'auto' => $this->t('Auto'),
        'ar-eg' => $this->t('Arabic (Egypt)'),
        'de' => $this->t('German'),
        'en' => $this->t('English'),
        'es' => $this->t('Spanish'),
        'fa' => $this->t('Farsi'),
        'fr' => $this->t('French'),
        'id' => $this->t('Indonesian'),
        'it' => $this->t('Italian'),
        'ja' => $this->t('Japanese'),
        'ko' => $this->t('Korean'),
        'nl' => $this->t('Dutch'),
        'pl' => $this->t('Polish'),
        'pt-br' => $this->t('Portuguese (Brazil)'),
        'ru' => $this->t('Russian'),
        'tr' => $this->t('Turkish'),
        'zh-cn' => $this->t('Chinese (Simplified)'),
        'zh-tw' => $this->t('Chinese (Traditional)'),
      ],
      '#title' => $this->t('Language'),
      '#type' => 'select',
    ];

    $form['widget']['turnstile_size'] = [
      '#default_value' => $config->get('widget.size'),
      '#description' => $this->t('The widget size.'),
      '#options' => [
        'normal' => $this->t('Normal'),
        'compact' => $this->t('Compact'),
      ],
      '#title' => $this->t('Size'),
      '#type' => 'select',
    ];

    $form['widget']['turnstile_retry'] = [
      '#default_value' => $config->get('widget.retry'),
      '#description' => $this->t('Controls whether the widget should automatically retry to obtain a token if it did not succeed. The default is auto, which will retry automatically. This can be set to never to disable retry upon failure.'),
      '#options' => [
        'auto' => $this->t('Auto'),
        'never' => $this->t('Never'),
      ],
      '#title' => $this->t('Retry'),
      '#type' => 'select',
    ];

    $form['widget']['turnstile_retry_interval'] = [
      '#default_value' => $config->get('widget.retry_interval'),
      '#description' => $this->t('When retry is set to auto, retry-interval controls the time between retry attempts in milliseconds. Value must be a positive integer less than 900000, defaults to 8000.'),
      '#maxlength' => 6,
      '#title' => $this->t('Retry Interval'),
      '#type' => 'number',
      '#min' => 1,
      '#max' => 900000,
      '#step' => '1',
      '#states' => [
        'visible' => [
          ':input[name="turnstile_retry"]' => [
            'value' => 'auto',
          ],
        ],
        'required' => [
          ':input[name="turnstile_retry"]' => [
            'value' => 'auto',
          ],
        ],
      ],
    ];

    $form['widget']['turnstile_appearance'] = [
      '#default_value' => $config->get('widget.appearance'),
      '#description' => $this->t('Appearance controls when the widget is visible. It can be always (default), execute, or interaction-only. Refer to <a href="https://developers.cloudflare.com/turnstile/get-started/client-side-rendering/#appearance-modes">Appearance Modes</a> for more information.'),
      '#options' => [
        'always' => $this->t('Always'),
        'execute' => $this->t('Execute'),
        'interaction-only' => $this->t('Interaction Only'),
      ],
      '#title' => $this->t('Appearance'),
      '#type' => 'select',
    ];

    $form['widget']['turnstile_tabindex'] = [
      '#default_value' => $config->get('widget.tabindex'),
      '#description' => $this->t('Set the <a href=":tabindex">tabindex</a> of the widget and challenge (Default = 0). If other elements in your page use tabindex, it should be set to make user navigation easier.', [':tabindex' => Url::fromUri('https://www.w3.org/TR/html4/interact/forms.html', ['fragment' => 'adef-tabindex'])->toString()]),
      '#maxlength' => 4,
      '#title' => $this->t('Tabindex'),
      '#type' => 'number',
      '#min' => -1,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('turnstile.settings');
    $config
      ->set('site_key', $form_state->getValue('turnstile_site_key'))
      ->set('secret_key', $form_state->getValue('turnstile_secret_key'))
      ->set('turnstile_src', $form_state->getValue('turnstile_src'))
      ->set('widget.theme', $form_state->getValue('turnstile_theme'))
      ->set('widget.tabindex', $form_state->getValue('turnstile_tabindex'))
      ->set('widget.language', $form_state->getValue('turnstile_language'))
      ->set('widget.size', $form_state->getValue('turnstile_size'))
      ->set('widget.retry', $form_state->getValue('turnstile_retry'))
      ->set('widget.retry_interval', $form_state->getValue('turnstile_retry_interval'))
      ->set('widget.appearance', $form_state->getValue('turnstile_appearance'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
