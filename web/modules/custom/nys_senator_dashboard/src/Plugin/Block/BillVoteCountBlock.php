<?php

namespace Drupal\nys_senator_dashboard\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
  id: 'senator_dashboard_bill_vote_count_block',
  admin_label: new TranslatableMarkup('Bill Vote Count Block'),
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
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager.
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
    EntityTypeManagerInterface $entity_type_manager,
    RouteMatchInterface $route_match,
    AccountProxyInterface $current_user,
    ManagedSenatorsHandler $managed_senators_handler,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager'),
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
    $user_id = $this->currentUser->id();
    return [
      'user:' . $user_id,
      'tempstore_user:' . $user_id,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $ret = [
      '#type' => 'component',
      '#component' => 'nysenate_theme:bill-vote-count',
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

    // Load all "vote" entities with vote type "nys_bill_vote" for bill NID.
    try {
      $vote_storage = $this->entityTypeManager->getStorage('vote');
      $vote_ids = $vote_storage->getQuery()
        ->condition('type', 'nys_bill_vote')
        ->condition('entity_id', $nid)
        ->accessCheck(FALSE)
        ->execute();
    }
    catch (\Exception) {
      return $ret;
    }
    if (empty($vote_ids)) {
      return $ret;
    }

    // Get all and in-district vote counts.
    try {
      $user_storage = $this->entityTypeManager->getStorage('user');
    }
    catch (\Exception) {
      return $ret;
    }
    $votes = $vote_storage->loadMultiple($vote_ids);
    $active_senator_district_id = $this->managedSenatorsHandler
      ->getActiveSenatorDistrictId();
    $yes_count = 0;
    $no_count = 0;
    $in_district_yes_count = 0;
    $in_district_no_count = 0;
    foreach ($votes as $vote) {
      if ($vote->getValue() == 1) {
        $yes_count++;
      }
      elseif ($vote->getValue() == 0) {
        $no_count++;
      }

      // If the voter is in the same district as user's active managed senator,
      // count as in-district vote.
      $vote_owner_id = $vote->getOwnerId();
      $user = $user_storage->load($vote_owner_id);
      if ($user && $user->hasField('field_district') && !$user->get('field_district')->isEmpty()) {
        $district_value = $user->field_district->target_id;
        if ($district_value == $active_senator_district_id) {
          if ($vote->getValue() == 1) {
            $in_district_yes_count++;
          }
          elseif ($vote->getValue() == 0) {
            $in_district_no_count++;
          }
        }
      }
    }

    // Setup component props based on vote counts.
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
