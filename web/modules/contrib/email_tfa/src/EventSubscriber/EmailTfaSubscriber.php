<?php

namespace Drupal\email_tfa\EventSubscriber;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Redirect user to Email TFA.
 */
class EmailTfaSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The route match object for the current page.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;


  /**
   * The aggregator.settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;


  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * A request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new subscriber after login.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The Route Match.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   A request stack object.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The moduleHandler.
   */
  public function __construct(AccountInterface $account, ConfigFactoryInterface $config_factory, RouteMatchInterface $route_match, PrivateTempStoreFactory $temp_store_factory, MessengerInterface $messenger, RequestStack $request_stack, MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager, ModuleHandlerInterface $moduleHandler) {
    $this->account = $account;
    $this->config = $config_factory->get('email_tfa.settings');
    $this->routeMatch = $route_match;
    $this->tempStoreFactory = $temp_store_factory->get('email_tfa');
    $this->messenger = $messenger;
    $this->request = $request_stack->getCurrentRequest();
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Listen to kernel.request events and call emailTfaRedirection.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['emailTfaRedirection'];
    return $events;
  }

  /**
   * Redirect pattern based url.
   *
   * @param RequestEvent $event
   *   Response event.
   */
  public function emailTfaRedirection(RequestEvent $event) {
    $result = FALSE;
    $status = $this->config->get('status');
    // Load our pathways for this module.
    $tracks = $this->config->get('tracks');
    // We will exclude all rules coming from this setting,.
    $ignore_role = $this->config->get('ignore_role');
    // Check if pathway (Globally Enabled) selected && exclude roles selected.
    // Check if module is active from its settings and user is Authenticated.
    if ($status && $this->account->isAuthenticated()) {
      // User one status.
      $user_one = $this->config->get('user_one');
      $user = $this->account;
      // If user one is exclude and its the current one.
      if ($user_one == 1 && $user->id() == 1 && $tracks == 'globally_enabled') {
        $result = FALSE;
      }
      // Globally enabled pathway && some roles if selected will excluded.
      elseif ($tracks == 'globally_enabled' && !_email_tfa_in_array_any($this->account->getRoles(), $ignore_role)) {
        // Check if user is NOT already verified.
        if ($this->tempStoreFactory->get('email_tfa_user_verify') == 0) {
          $result = TRUE;
        }
      }
      elseif ($tracks == 'optionally_by_users') {
        // @todo https://www.drupal.org/project/email_tfa/issues/3202328
        if (!empty(_email_tfa_user_current_status()) && _email_tfa_user_current_status() == 1 && $this->tempStoreFactory->get('email_tfa_user_verify') == 0) {
          $result = TRUE;
        }
      }

      if ($this->moduleHandler->moduleExists('masquerade')) {
        if (\Drupal::service('masquerade')->isMasquerading()) {
          $result = FALSE;
        }
      }
    }
    // Continue if one of the conditions above succeeds ( res = true ).
    if ($result) {
      if ($this->tempStoreFactory->get('email_tfa_send_mail') == "send_mail") {
        $this->tempStoreFactory->set('email_tfa_user_verify', 0);
        $this->tempStoreFactory->set('email_tfa_mail_created', time());
        $this->tempStoreFactory->set('email_tfa_otp_number', $this->generateCode());
        $this->tempStoreFactory->set('email_tfa_send_mail', "not_send_mail");
        $this->mailManager->mail('email_tfa', 'send_email_tfa', $this->account->getEmail(), $this->languageManager->getDefaultLanguage()->getId(), [
          'user' => $this->account,
          'email_tfa' => $this->tempStoreFactory->get('email_tfa_otp_number'),
        ]);
      }
      $timeouts = $this->config->get('timeouts');
      // If time is passed/expired then redirects user to login page.
      if (time() - (int) $this->tempStoreFactory->get('email_tfa_mail_created') > $timeouts) {
        $this->request->getSession()->clear();
        $url = Url::fromRoute('user.page')->toString();
        $response = new RedirectResponse($url);
        $response->send();
        $this->messenger->addError($this->t('Two-factor Authentication is Expired.'));
        exit;
      }
      // Pages that excluded from the redirect from module settings.
      $routes = $this->config->get('routes');
      $routes = array_map('trim', explode("\n", $routes));
      if (!in_array($this->routeMatch->getRouteName(), $routes)) {
        $url = Url::fromRoute('email_tfa.verifiy');
        $url = $url->setOption('query', \Drupal::destination()->getAsArray());
        $url = $url->toString();
        $response = new RedirectResponse($url);
        $response->send();
        exit;
      }

    }

  }

  /**
   * Generates a random number with a configured length.
   *
   * @return int
   *   A random number with the configured length.
   */
  protected function generateCode() {
    $length = $this->config->get('security_code_length');
    // Cast result of pow to int because it can return float.
    $min = (int) pow(10, $length - 1);
    $max = (int) pow(10, $length) - 1;
    return mt_rand($min, $max);
  }

}
