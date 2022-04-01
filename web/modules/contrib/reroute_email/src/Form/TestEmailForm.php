<?php

namespace Drupal\reroute_email\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements a form to test Reroute Email.
 */
class TestEmailForm extends FormBase {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reroute_email_test_email_form';
  }

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.mail'),
      $container->get('language_manager'),
      $container->get('messenger')
    );
  }

  /**
   * Constructs a new object.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   Mail manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager, MessengerInterface $messenger) {
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    return [
      'addresses' => [
        '#type' => 'fieldset',
        '#description' => $this->t('A list of addresses separated by a comma could be submitted.<br/>Email addresses are not validated: any valid or invalid email address format could be submitted.'),
        'to' => [
          '#type' => 'textfield',
          '#title' => $this->t('To'),
        ],
        'cc' => [
          '#type' => 'textfield',
          '#title' => $this->t('cc'),
        ],
        'bcc' => [
          '#type' => 'textfield',
          '#title' => $this->t('bcc'),
        ],
      ],
      'subject' => [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#default_value' => $this->t('Reroute Email Test'),
      ],
      'body' => [
        '#type' => 'textarea',
        '#title' => $this->t('Body'),
        '#default_value' => $this->t('Reroute Email Body'),
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Send email'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $to = $form_state->getValue(['to']);
    $param_keys = ['cc', 'bcc', 'subject', 'body'];
    $params = array_intersect_key($form_state->getValues(), array_flip($param_keys));
    $langcode = $this->languageManager->getDefaultLanguage()->getId();

    // Send email with drupal_mail.
    $message = $this->mailManager->mail('reroute_email', 'test_email_form', $to, $langcode, $params);

    if (!empty($message['result'])) {
      $this->messenger->addMessage($this->t('Test email submitted for delivery from test form.'));
    }
  }

}
