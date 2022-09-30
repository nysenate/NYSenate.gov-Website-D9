<?php

namespace Drupal\nys_subscriptions\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\nys_subscriptions\SubscriptionQueue;

/**
 * Configuration form nys_subscriptions.
 */
class SubscriptionSettingsForm extends ConfigFormBase {

  /**
   * A shortcut to the nys_subscriptions.settings config collection.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $localConfig;

  /**
   * An immutable copy of the config, to allow access to overrides.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $immutableConfig;

  /**
   * The system's site settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $siteSettings;

  /**
   * {@inheritdoc}
   *
   * Creates local copies of the module's config.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->localConfig = $this->config('nys_subscriptions.settings');
    $this->siteSettings = $this->config('system.site');
    $this->immutableConfig = $this->configFactory->get('nys_subscriptions.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'nys_subscriptions_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Remember that an immutable copy is needed to see overrides.
    $config = $this->localConfig;

    $form['summary'] = [
      '#markup' => $this->t('Default settings and behaviors for all queues.'),
    ];

    // Sender options.
    $form['sender_info'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Sender Information'),
      '#description' => $this->t("Addressing details for the default From and ReplyTo.  Each address will be formatted as '[Display Name] &lt;Email Address&gt;'."),
      '#description_display' => 'before',
    ];
    $form['sender_info']['from_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('From Address'),
      '#description' => $this->t("Email for the \"From\" address.  Leave blank to use site's email."),
      '#default_value' => $config->get('from_address') ?? $this->siteSettings->get('mail'),
    ];
    $form['sender_info']['from_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('From Name'),
      '#description' => $this->t("Plain-text name for the \"From\" address.  Leave blank to use the site's name."),
      '#default_value' => $config->get('from_name') ?? $this->siteSettings->get('name'),
    ];
    $form['sender_info']['reply_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reply-To Address'),
      '#description' => $this->t('Email for the "ReplyTo" address.'),
      '#default_value' => $config->get('reply_address') ?? '',
    ];
    $form['sender_info']['reply_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reply-To Name'),
      '#description' => $this->t('Plain-text name for the "ReplyTo" address.'),
      '#default_value' => $config->get('reply_name') ?? '',
    ];

    // Envelope options.
    $form['envelope'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Envelope Options'),
    ];
    $form['envelope']['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Subject'),
      '#default_value' => $config->get('subject') ?? SubscriptionQueue::DEFAULT_SUBJECT,
      '#description' => $this->t("The default subject.  Can be overridden by the queue, the item tokens event, or the Sendgrid template."),
    ];
    $form['envelope']['bcc_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('BCC Address'),
      '#default_value' => $config->get('bcc_address') ?? '',
      '#description' => $this->t('Enables a BCC address for all outbound emails, regardless of queue.<br /><b>NOTE:</b> This sets a BCC on every recipient/personalization.  Use with caution.'),
    ];
    $form['envelope']['max_recipients'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max Recipients'),
      '#default_value' => $config->get('max_recipients') ?? SubscriptionQueue::MAX_RECIPIENTS_DEFAULT,
      '#description' => $this->t("Maximum recipients on any outbound email.  SendGrid API's hard limit of 1000 is always enforced."),
    ];

    // Run-time options.
    $form['runtime'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Run-time Configuration'),
    ];
    $form['runtime']['max_runtime'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum Processing Time'),
      '#default_value' => $config->get('max_runtime') ?? SubscriptionQueue::MAX_RUNTIME_DEFAULT,
      '#description' => $this->t('When processing queues, the processor will not run longer than this setting (in seconds).  If the limit is exceeded, processing will stop when the current queue item finishes.  Set to zero to disable (always process all items).'),
    ];
    // @todo Make this an auto-complete and multi-value.
    $form['runtime']['suppress_tid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Suppress Alerts'),
      '#description' => $this->t('NOT IMPLEMENTED YET<br />A comma-delimited list of taxonomy term IDs for which alerts will not be sent out. Queue items will still be created for these items, but emails will not be generated.  Suppressed queue items will remain in queue until manually flushed, or suppression is removed.'),
      '#default_value' => $config->get('suppress_tid') ?? '',
      '#disabled' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    $fields = [
      'from_address',
      'from_name',
      'reply_address',
      'reply_name',
      'subject',
      'bcc_address',
      'max_recipients',
      'max_runtime',
      'suppress_tid',
    ];
    foreach ($fields as $field) {
      $this->localConfig->set($field, $values[$field] ?? '');
    }
    $this->localConfig->save();

    parent::submitForm($form, $form_state);

    $this->messenger()
      ->addStatus($this->t('The configuration has been updated.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['nys_subscriptions.settings'];
  }

}
