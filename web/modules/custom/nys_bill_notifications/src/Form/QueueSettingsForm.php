<?php

namespace Drupal\nys_bill_notifications\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\nys_subscriptions\SubscriptionQueue;

/**
 * Configuration form nys_subscriptions.
 */
class QueueSettingsForm extends ConfigFormBase {

  /**
   * A default subject for this queue.
   *
   * @todo This does not belong here.
   */
  const DEFAULT_SUBJECT = 'Changes registered on a subscribed bill';

  /**
   * A shortcut to the nys_bill_notifications.settings config collection.
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
   * {@inheritdoc}
   *
   * Creates local copies of the module's config.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->localConfig = $this->config('nys_bill_notifications.settings');
    $this->immutableConfig = $this->configFactory->get('nys_bill_notifications.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'nys_bill_notifications_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Remember that an immutable copy is needed to see overrides.
    $config = $this->localConfig;

    $form['summary'] = [
      '#markup' => $this->t('Options related to the bill notifications queue.'),
    ];

    // Envelope.
    $form['envelope'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Mail Properties'),
    ];
    $form['envelope']['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Subject'),
      '#default_value' => $config->get('subject') ?? SubscriptionQueue::DEFAULT_SUBJECT,
      '#description' => $this->t('A default subject for all bill notifications, as a fallback for the SendGrid template.'),
    ];
    // @todo Maybe make this select/auto-complete?
    $form['envelope']['inject_userid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Inject User ID'),
      '#default_value' => $config->get('inject_userid') ?? '',
      '#description' => $this->t('A user ID to inject as an authenticated subscriber.'),
    ];
    $form['envelope']['inject_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Inject User Email'),
      '#default_value' => $config->get('inject_email') ?? '',
      '#description' => $this->t('An email to inject as an unauthenticated subscriber.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    $fields = [
      'subject',
      'inject_userid',
      'inject_email',
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
    return ['nys_bill_notifications.settings'];
  }

}
