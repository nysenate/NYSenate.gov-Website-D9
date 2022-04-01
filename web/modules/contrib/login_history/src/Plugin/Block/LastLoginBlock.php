<?php

namespace Drupal\login_history\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a block with information about the user's last login.
 *
 * @Block(
 *   id = "last_login_block",
 *   admin_label = @Translation("Last login"),
 *   category = @Translation("User"),
 * )
 */
class LastLoginBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The account proxy service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The account proxy service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Creates a LastLoginBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   The account proxy service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxy $currentUser, RequestStack $requestStack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $currentUser;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIf($account->isAuthenticated());
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $last_login = FALSE;
    if (!$this->currentUser->isAnonymous()) {
      // Get the previous login information.
      $last_login = \Drupal::database()->select('login_history', 'lh')
        ->fields('lh', ['login', 'hostname', 'one_time', 'user_agent'])
        ->condition('uid', $this->currentUser->id())
        ->orderBy('login', 'DESC')
        ->range(1, 1)
        ->execute()
        ->fetch();
    }

    if ($last_login) {
      $request = $this->requestStack->getCurrentRequest();
      $hostname = $last_login->hostname == $request->getClientIP() ? $this->t('this IP address') : $last_login->hostname;
      $user_agent = $last_login->user_agent == $request->server->get('HTTP_USER_AGENT') ? $this->t('this browser') : $last_login->user_agent;
      $build['last_login']['#markup'] = '<p>' . $this->t('You last logged in from @hostname using @user_agent.',
        ['@hostname' => $hostname, '@user_agent' => $user_agent]) . '</p>';
      if ($this->currentUser->hasPermission('view own login history')) {
        $build['view_report'] = [
          '#type' => 'more_link',
          '#title' => $this->t('View your login history'),
          '#url' => Url::fromRoute('login_history.user_report', ['user' => $this->currentUser->id()]),
        ];
      }
    }
    // Cache by session.
    $build['#cache'] = [
      'contexts' => [
        'session',
      ],
    ];
    return $build;
  }

}
