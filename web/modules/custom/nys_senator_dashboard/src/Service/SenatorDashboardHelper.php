<?php

namespace Drupal\nys_senator_dashboard\Service;

use Drupal\comment\CommentStatisticsInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;

/**
 * Provides helper methods for the nys_senator_dashboard module.
 */
class SenatorDashboardHelper {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The managed senators handler service.
   *
   * @var \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler
   */
  protected ManagedSenatorsHandler $managedSenatorsHandler;

  /**
   * The comment statistics service.
   *
   * @var \Drupal\comment\CommentStatisticsInterface
   */
  protected CommentStatisticsInterface $commentStatistics;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs the SenatorDashboardHelper service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler $managed_senators_handler
   *   The managed senators handler service.
   * @param \Drupal\comment\CommentStatisticsInterface $comment_statistics
   *   The comment statistics service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    RouteMatchInterface $route_match,
    Connection $database,
    ManagedSenatorsHandler $managed_senators_handler,
    CommentStatisticsInterface $comment_statistics,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->routeMatch = $route_match;
    $this->database = $database;
    $this->managedSenatorsHandler = $managed_senators_handler;
    $this->commentStatistics = $comment_statistics;
    $this->logger = $logger_factory->get('nys_senator_dashboard');
  }

  /**
   * Gets the entity whose ID is passed into a contextual filter.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object, or NULL on failure.
   */
  public function getContextualEntity() {
    $entity_id = $this->routeMatch->getParameter('arg_0');
    if (!$entity_id) {
      return NULL;
    }
    $target_entity_types = ['node', 'taxonomy_term'];
    foreach ($target_entity_types as $entity_type) {
      try {
        $entity = $this->entityTypeManager
          ->getStorage($entity_type)
          ->load($entity_id);
      }
      catch (\Exception) {
        return NULL;
      }
      if (!empty($entity)) {
        break;
      }
    }
    return $entity ?? NULL;
  }

  /**
   * Count the number of in-district flaggings for a given entity and flag type.
   *
   * @param string $flag_id
   *   The ID of the flag type.
   * @param int $entity_id
   *   The ID of the entity being flagged.
   *
   * @return int
   *   The number of in-district flaggings for the given flag and entity IDs.
   *   Returns 0 if the query fails or no flaggings are found.
   */
  public function getInDistrictFlaggingCount(string $flag_id, int $entity_id): int {
    $active_senator_district_id = $this->managedSenatorsHandler->getActiveSenatorDistrictId();

    if (!$active_senator_district_id) {
      return 0;
    }

    $query = $this->database->select('flagging', 'f');
    $query->innerJoin(
      'user__field_district',
      'ufd',
      'f.uid = ufd.entity_id'
    );
    $query->condition('f.flag_id', $flag_id)
      ->condition('f.entity_id', $entity_id)
      ->condition('ufd.field_district_target_id', $active_senator_district_id);

    try {
      $count = $query->countQuery()->execute()->fetchField();
      return (int) ($count ?: 0);
    }
    catch (\Exception $e) {
      $this->logger->error('Error retrieving in-district flaggings: @message', ['@message' => $e->getMessage()]);
      return 0;
    }
  }

  /**
   * Gets count of unique in-district constituents who submitted to the webform.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity, expected to be of type "webform".
   *
   * @return int
   *   Count of unique in-district constituents who submitted to the webform.
   */
  public function getInDistrictWebformSubmissionCount(NodeInterface $node): int {
    $active_senator_district_id = $this->managedSenatorsHandler->getActiveSenatorDistrictId();

    if ($node->getType() !== 'webform' || !$node->hasField('webform')) {
      $this->logger->warning('Invalid node type or missing "webform" field.');
      return 0;
    }

    $webform_id = $node->webform?->target_id;
    if (!$webform_id) {
      $this->logger->warning('Missing webform ID or Active Senator District ID.');
      return 0;
    }

    $query = $this->database
      ->select('webform_submission', 'ws');
    $query->join('user__field_district', 'ufd', 'ws.uid = ufd.entity_id');
    $query->condition('ws.webform_id', $webform_id);
    $query->condition('ufd.field_district_target_id', $active_senator_district_id);
    $query->fields('ws', ['uid']);
    $query->distinct();
    $count = $query->countQuery()->execute()->fetchField();

    $count = $count !== FALSE ? (int) $count : 0;
    return $count;
  }

  /**
   * Gets vote counts for a bill.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node entity.
   * @param string $vote_type
   *   The type of votes to return. Possible values:
   *   - 'all': Return all vote types (default).
   *   - 'total_votes': Return only total votes.
   *   - 'in_district_votes': Return only in-district votes.
   *   - 'out_of_district_votes': Return only out-of-district votes.
   *
   * @return array
   *   An array of vote counts, structure depends on $vote_type parameter.
   */
  public function getBillVoteCounts(EntityInterface $node, string $vote_type = 'all'): array {
    $nid = $node->id();
    $active_senator_district_id = $this->managedSenatorsHandler->getActiveSenatorDistrictId();

    $yes_count = 0;
    $no_count = 0;
    $in_district_yes_count = 0;
    $in_district_no_count = 0;

    if ($vote_type === 'all' || $vote_type === 'total_votes' || $vote_type === 'out_of_district_votes') {
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

      if ($vote_type === 'total_votes') {
        return [$yes_count, $no_count];
      }
    }

    if ($vote_type === 'all' || $vote_type === 'in_district_votes' || $vote_type === 'out_of_district_votes') {
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

      if ($vote_type === 'in_district_votes') {
        return [$in_district_yes_count, $in_district_no_count];
      }
    }

    if ($vote_type === 'out_of_district_votes') {
      return [$yes_count - $in_district_yes_count, $no_count - $in_district_no_count];
    }

    return [
      'total_votes' => [$yes_count, $no_count],
      'in_district_votes' => [$in_district_yes_count, $in_district_no_count],
      'out_of_district_votes' => [$yes_count - $in_district_yes_count, $no_count - $in_district_no_count],
    ];
  }

  /**
   * Gets the comment count for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to get comment count for.
   *
   * @return int
   *   The number of comments on the entity.
   */
  public function getCommentCount(EntityInterface $entity): int {
    $statistics = $this->commentStatistics->read([$entity->id() => $entity], 'node');
    $count = 0;
    if (!empty($statistics)) {
      $count = $statistics[0]->comment_count;
    }
    return $count;
  }

}
