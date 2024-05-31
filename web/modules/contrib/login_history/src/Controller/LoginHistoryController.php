<?php

namespace Drupal\login_history\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for Login history routes.
 *
 * @deprecated in login_history:8.x-1.1 and is removed from login_history:8.x-2.0.
 *   There is no replacement.
 * @see https://www.drupal.org/project/login_history/issues/3185800
 */
class LoginHistoryController extends ControllerBase {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a LoginHistoryController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Database\Connection|null $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface|null $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(DateFormatterInterface $date_formatter, Connection $database = NULL, EntityTypeManagerInterface $entity_type_manager = NULL) {
    $this->dateFormatter = $date_formatter;
    $this->database = $database ?: \Drupal::database();
    $this->entityTypeManager = $entity_type_manager ?: \Drupal::entityTypeManager();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Displays a report of user logins.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   (optional) The user to display for individual user reports.
   *
   * @return array
   *   A render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function report(UserInterface $user = NULL) {
    $header = [
      ['data' => $this->t('Date'), 'field' => 'lh.login', 'sort' => 'desc'],
      ['data' => $this->t('Username'), 'field' => 'ufd.name'],
      ['data' => $this->t('IP Address'), 'field' => 'lh.hostname'],
      ['data' => $this->t('One-time login?'), 'field' => 'lh.one_time'],
      ['data' => $this->t('User Agent')],
    ];

    $query = $this->database->select('login_history', 'lh')
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');

    $query->join('users', 'u', 'lh.uid = u.uid');
    $query->join('users_field_data', 'ufd', 'u.uid = ufd.uid');

    if ($user) {
      $query->condition('lh.uid', $user->id());
    }

    $result = $query
      ->fields('lh')
      ->fields('u', ['uid'])
      ->fields('ufd', ['name'])
      ->orderByHeader($header)
      ->limit(50)
      ->execute()
      ->fetchAll();

    return $this->generateReportTable($result, $header);
  }

  /**
   * Renders login histories as a table.
   *
   * @param array $history
   *   A list of login history objects to output.
   * @param array $header
   *   An array containing table header data.
   *
   * @return array
   *   A table render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function generateReportTable(array $history, array $header) {
    // Load all users first.
    $uids = [];
    foreach ($history as $entry) {
      $uids[] = $entry->uid;
    }
    /** @var \Drupal\user\Entity\User[] $users */
    $users = $this->entityTypeManager->getStorage('user')->loadMultiple($uids);

    $rows = [];
    foreach ($history as $entry) {
      $rows[] = [
        $this->dateFormatter->format($entry->login, 'short'),
        $users[$entry->uid]->getAccountName(),
        $entry->hostname,
        empty($entry->one_time) ? $this->t('Regular login') : $this->t('One-time login'),
        $entry->user_agent,
      ];
    }
    $output['history'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No login history available.'),
    ];
    $output['pager'] = [
      '#type' => 'pager',
    ];

    return $output;
  }

  /**
   * Checks access for the user login report.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The user to check access for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Returns Allowed or Neutral.
   */
  public function checkUserReportAccess(UserInterface $user = NULL) {
    // Allow access if the user is viewing their own report and has permission
    // or if the user has permission to view all login history reports.
    $access = ($user->id() == $this->currentUser()->id() && $this->currentUser->hasPermission('view own login history'))
      || $this->currentUser->hasPermission('view all login histories');
    return AccessResult::allowedIf($access);
  }

}
