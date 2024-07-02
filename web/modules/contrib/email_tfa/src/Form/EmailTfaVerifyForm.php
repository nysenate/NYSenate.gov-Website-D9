<?php

namespace Drupal\email_tfa\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a user Email TFA form.
 *
 * @internal
 */
class EmailTfaVerifyForm extends FormBase {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * A request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a TFA verification form..
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   A request stack object.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, RequestStack $request_stack) {
    $this->tempStoreFactory = $temp_store_factory->get('email_tfa');
    $this->request = $request_stack->getCurrentRequest();

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'email_tfa_verify_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('email_tfa.settings');
    if ($this->request->query->get('destination')) {
      // save the destination in the $form_state to be used on submit.
      $form_state->set('destination', $this->request->query->get('destination'));
      // remove the destination from the query string.
      $this->request->query->remove('destination');
    }
    $form['email_tfa_verify'] = [
      '#type' => 'textfield',
      '#title' => $config->get('security_code_label_text'),
      '#description' => $config->get('security_code_description_text'),
      '#size' => 60,
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#name' => 'verify',
      '#value' => $config->get('security_code_verify_text'),
      '#button_type' => 'primary',
    ];
    $form['interrupt'] = [
      '#type' => 'submit',
      '#name' => 'interrupt',
      '#value' => $config->get('security_code_interrupt_text'),
      '#submit' => [[$this, 'interruptAuth']],
      '#limit_validation_errors' => [],
      '#button_type' => 'secondary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('email_tfa.settings');
    $tfa = $form_state->getValue('email_tfa_verify');
    if ($tfa == $this->tempStoreFactory->get('email_tfa_otp_number') && is_numeric($tfa)) {
      $this->tempStoreFactory->set('email_tfa_user_verify', 1);
      if ($message = $config->get('verification_succeeded_message')) {
        $this->messenger()->addStatus($message);
      }
      // get the destination from the $form_state.
      $destination = $form_state->get('destination');
      // use the destination from the $form_state if it exists
      if ($destination) {
        $url = Url::fromUserInput($destination);
      }
      else {
        $url = Url::fromRoute('<front>');
      }
      $form_state->setRedirectUrl($url);
    }
    else {
      $this->request->getSession()->clear();
      $url = Url::fromRoute('user.login.http');
      $form_state->setRedirectUrl($url);
      if ($message = $config->get('verification_failed_message')) {
        $this->messenger()->addError($message);
      }
    }
  }

  /**
   * Interrupt the two-factor authentication.
   */
  public function interruptAuth(array &$form, FormStateInterface $form_state) {
    $config = $this->config('email_tfa.settings');
    $this->request->getSession()->clear();
    $url = Url::fromRoute('user.login.http');
    $form_state->setRedirectUrl($url);
    if ($message = $config->get('verification_interrupted_message')) {
      $this->messenger()->addError($message);
    }
  }

}
