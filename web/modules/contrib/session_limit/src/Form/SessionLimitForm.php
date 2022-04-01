<?php

namespace Drupal\session_limit\Form;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\SessionManager;
use Drupal\session_limit\Services\SessionLimit;

class SessionLimitForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'session_limit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var SessionLimit $session_limit */
    $session_limit = \Drupal::service('session_limit');

    $form['title'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Your active sessions are listed below. You need to choose a session to end.') . '</p>',
    ];

    /** @var SessionManager $session_manager */
    $session_manager = \Drupal::service('session_manager');
    $current_session_id = Crypt::hashBase64($session_manager->getId());

    $user = \Drupal::currentUser();

    $form['active_sessions'] = [
      '#type' => 'value',
      '#value' => $session_limit->getUserActiveSessions($user),
    ];

    $session_references = [];

    foreach ($form['active_sessions']['#value'] as $session_reference => $obj) {
      $message = $current_session_id == $obj->sid ? $this->t('Your current session.') : '';

      $session_references[$session_reference] = $this->t('<strong>Host:</strong> %host (idle: %time) <b>@message</b>', [
        '%host' => $obj->hostname,
        '@message' => $message,
        '%time' => \Drupal::service("date.formatter")->formatInterval(time() - $obj->timestamp),
      ]);
    }

    $form['session_reference'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select a session to disconnect.'),
      '#options' => $session_references,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Disconnect session'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var SessionManager $session_manager */
    $session_manager = \Drupal::service('session_manager');
    $current_session_id = Crypt::hashBase64($session_manager->getId());

    /** @var SessionLimit $session_limit */
    $session_limit = \Drupal::service('session_limit');
    $session_reference = $form_state->getValue(['session_reference']);
    $sid = isset($session_reference) ? $form['active_sessions']['#value'][$session_reference]->sid : '';

    if ($current_session_id == $sid) {
      // @todo the user is not seeing the message below.
      $session_limit->sessionActiveDisconnect($this->t('You chose to end this session.'));
      $form_state->setRedirect('user.login');
    }
    else {
      $session_limit->sessionDisconnect($sid, $this->t('Your session was deliberately ended from another session.'));
      $form_state->setRedirect('<front>');
    }
  }

}
