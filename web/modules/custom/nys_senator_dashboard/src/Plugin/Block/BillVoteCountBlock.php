<?php

namespace Drupal\nys_senator_dashboard\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Bill Vote Count Block.
 */
#[Block(
  id: 'nys_senator_dashboard_bill_vote_count_block',
  admin_label: new TranslatableMarkup('NYS Senator Dashboard: Bill Vote Count Block'),
  context_definitions: [
    'node' => new EntityContextDefinition(
      'entity:node',
      label: new TranslatableMarkup('Node'),
      required: FALSE
    ),
  ]
)]
class BillVoteCountBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The Route Match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The managed senators handler service.
   *
   * @var \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler
   */
  protected $managedSenatorsHandler;

  /**
   * Constructs a new VoteCountBlock instance.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The Route Match service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   * @param \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler $managed_senators_handler
   *   The managed senators handler service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Connection $database,
    RouteMatchInterface $route_match,
    AccountProxyInterface $current_user,
    ManagedSenatorsHandler $managed_senators_handler,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
    $this->routeMatch = $route_match;
    $this->currentUser = $current_user;
    $this->managedSenatorsHandler = $managed_senators_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('current_route_match'),
      $container->get('current_user'),
      $container->get('nys_senator_dashboard.managed_senators_handler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return ['route'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return [
      'user:' . $this->currentUser->id(),
      'tempstore_user:' . $this->currentUser->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $ret = [
      '#type' => 'component',
      '#component' => 'nysenate_theme:bill-vote-count-block',
      '#props' => ['no_results' => TRUE],
    ];

    // Get bill NID from either node context or views arg_0.
    try {
      $nid = $this->getContextValue('node')?->id() ?? $this->routeMatch->getParameter('arg_0');
    }
    catch (\Exception) {
      return $ret;
    }
    if (empty($nid)) {
      return $ret;
    }

    // Get active managed senator district ID.
    $active_senator_district_id = $this->managedSenatorsHandler
      ->getActiveSenatorDistrictId();

    // Get in and out of district aye and nay vote counts.
    $yes_count = (int) $this->database
      ->select('votingapi_vote', 'v')
      ->condition('v.entity_id', $nid)
      ->condition('v.value', 1)
      ->countQuery()
      ->execute()
      ->fetchField();

    $no_count = (int) $this->database
      ->select('votingapi_vote', 'v')
      ->condition('v.entity_id', $nid)
      ->condition('v.value', 0)
      ->countQuery()
      ->execute()
      ->fetchField();

    $in_district_yes_count_query = $this->database
      ->select('votingapi_vote', 'v');
    $joined_table_yes_count_query = $in_district_yes_count_query
      ->innerJoin('user__field_district', 'u', 'u.entity_id = v.user_id');
    $in_district_yes_count = (int) $in_district_yes_count_query
      ->condition('v.entity_id', $nid)
      ->condition('v.value', 1)
      ->condition($joined_table_yes_count_query . '.field_district_target_id', $active_senator_district_id)
      ->countQuery()
      ->execute()
      ->fetchField();

    $in_district_no_count_query = $this->database
      ->select('votingapi_vote', 'v');
    $joined_table_no_count_query = $in_district_no_count_query
      ->innerJoin('user__field_district', 'u', 'u.entity_id = v.user_id');
    $in_district_no_count = (int) $in_district_no_count_query
      ->condition('v.entity_id', $nid)
      ->condition('v.value', 0)
      ->condition($joined_table_no_count_query . '.field_district_target_id', $active_senator_district_id)
      ->countQuery()
      ->execute()
      ->fetchField();

    // Setup and calculate component props.
    $props = [
      'no_results' => FALSE,
      'detail_link_nid' => $this->getContextValue('node') ? (int) $nid : 0,
      'total_count' => $yes_count + $no_count,
      'yes_count' => $yes_count,
      'no_count' => $no_count,
      'yes_percentage' => $yes_count + $no_count > 0 ? (int) floor(round(($yes_count / ($yes_count + $no_count)) * 100, 2)) : 0,
      'no_percentage' => $yes_count + $no_count > 0 ? (int) ceil(round(($no_count / ($yes_count + $no_count)) * 100, 2)) : 0,
      'in_district_total_count' => $in_district_yes_count + $in_district_no_count,
      'in_district_yes_count' => $in_district_yes_count,
      'in_district_yes_percentage' => ($in_district_yes_count + $in_district_no_count) > 0 ? (int) floor(round(($in_district_yes_count / ($in_district_yes_count + $in_district_no_count)) * 100, 2)) : 0,
      'in_district_no_percentage' => ($in_district_yes_count + $in_district_no_count) > 0 ? (int) ceil(round(($in_district_no_count / ($in_district_yes_count + $in_district_no_count)) * 100, 2)) : 0,
      'in_district_no_count' => $in_district_no_count,
      'out_district_total_count' => ($yes_count + $no_count) - ($in_district_yes_count + $in_district_no_count),
      'out_district_yes_count' => $yes_count - $in_district_yes_count,
      'out_district_no_count' => $no_count - $in_district_no_count,
      'out_district_yes_percentage' => ($yes_count + $no_count - ($in_district_yes_count + $in_district_no_count)) > 0
        ? (int) floor(round((($yes_count - $in_district_yes_count) / ($yes_count + $no_count - ($in_district_yes_count + $in_district_no_count))) * 100, 2))
        : 0,
      'out_district_no_percentage' => ($yes_count + $no_count - ($in_district_yes_count + $in_district_no_count)) > 0
        ? (int) ceil(round((($no_count - $in_district_no_count) / ($yes_count + $no_count - ($in_district_yes_count + $in_district_no_count))) * 100, 2))
        : 0,
    ];
    $ret['#props'] = $props;

    return $ret;
  }

}
