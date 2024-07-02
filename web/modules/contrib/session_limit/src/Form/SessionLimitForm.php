<?php

namespace Drupal\session_limit\Form;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\session_limit\Services\SessionLimit;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Session limit action form.
 */
class SessionLimitForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'session_limit_form';
  }

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The SessionLimit.
   *
   * @var \Drupal\session_limit\Services\SessionLimit
   */
  protected $sessionLimit;

  /**
   * The sessionManager.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\session_limit\Services\SessionLimit $session_limit
   *   The SessionLimit.
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   The sessionManager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, SessionLimit $session_limit, SessionManagerInterface $session_manager, AccountInterface $current_user, DateFormatterInterface $date_formatter) {
    $this->configFactory = $config_factory;
    $this->sessionLimit = $session_limit;
    $this->sessionManager = $session_manager;
    $this->currentUser = $current_user;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('session_limit'),
      $container->get('session_manager'),
      $container->get('current_user'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\session_limit\Services\SessionLimit $session_limit */
    $session_limit = $this->sessionLimit;

    $form['title'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Your active sessions are listed below. You need to choose a session to end.') . '</p>',
    ];

    /** @var \Drupal\Core\Session\SessionManager $session_manager */
    $session_manager = $this->sessionManager;
    $current_session_id = Crypt::hashBase64($session_manager->getId());

    $user = $this->currentUser;

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
        '%time' => $this->dateFormatter->formatInterval(time() - $obj->timestamp),
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

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Session\SessionManager $session_manager */
    $session_manager = $this->sessionManager;
    $current_session_id = Crypt::hashBase64($session_manager->getId());

    /** @var \Drupal\session_limit\Services\SessionLimit $session_limit */
    $session_limit = $this->sessionLimit;
    $session_reference = $form_state->getValue(['session_reference']);
    $sid = isset($session_reference) ? $form['active_sessions']['#value'][$session_reference]->sid : '';

    if ($current_session_id == $sid) {
      // @todo the user is not seeing the message below.
      $session_limit->disconnectActiveSession($this->t('You chose to end this session.'));
      $form_state->setRedirect('user.login');
    }
    else {
      $session_limit->sessionDisconnect($sid, $this->t('Your session was deliberately ended from another session.'));
      $form_state->setRedirect('<front>');
    }
  }

}
