<?php

namespace Drupal\node_revision_generate;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Database\Connection;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Batch\BatchBuilder;

/**
 * Class NodeRevisionGenerate.
 *
 * @package Drupal\node_revision_generate
 */
class NodeRevisionGenerate implements NodeRevisionGenerateInterface {

  use StringTranslationTrait;

  /**
   * A date time instance.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructor.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The date time instance.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(
    TimeInterface $time,
    Connection $connection,
    TranslationInterface $string_translation
  ) {
    $this->time = $time;
    $this->connection = $connection;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableNodesForRevisions(array $bundles, $revisions_age) {
    // Variable with the placeholders arguments needed for the expression.
    $interval_time = [
      ':interval' => $revisions_age,
      ':current_time' => $this->time->getRequestTime(),
    ];

    $query = $this->connection->select('node_field_data', 'node');
    // Get/check the last revision (vid).
    $query->leftJoin('node_revision', 'revision', 'node.vid = revision.vid');
    // Get the node id to generate revisions.
    $query->addField('node', 'nid');
    // Get the node id to generate revisions.
    $query->addField('revision', 'revision_timestamp');
    // Get nodes with title to avoid some error on save it.
    $query->isNotNull('node.title');
    // Get nodes of selected content types (bundles).
    $query->condition('node.type', $bundles, 'IN');
    // Get only the published nodes.
    $query->condition('node.status', 1);
    // Check the next date to generate the revision be <= current date.
    $query->where('revision.revision_timestamp + :interval <= :current_time', $interval_time);
    // Return the available nodes ids and its next revision date, as array.
    return $query->execute()->fetchAll();
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionCreationBatch(array $nodes_for_revisions, $revisions_number, $revisions_age) {
    // Defining the batch builder.
    $batch_builder = new BatchBuilder();
    $batch_builder->setTitle($this->t('Generating revisions'))
      ->setInitMessage($this->t('Starting to create revisions.'))
      ->setProgressMessage($this->t('Processed @current out of @total (@percentage%). Estimated time: @estimate.'))
      ->setErrorMessage($this->t('The revision creation process has encountered an error.'))
      ->setFinishCallback([NodeRevisionGenerateBatch::class, 'finish']);

    // Building batch operations, one per revision.
    foreach ($nodes_for_revisions as $node) {
      $revision_timestamp = $node->revision_timestamp;
      // Initializing variables.
      $i = 0;
      $revision_timestamp += $revisions_age;
      // Adding operations.
      while ($i < $revisions_number && $revision_timestamp <= $this->time->getRequestTime()) {
        // Adding the operation.
        $batch_builder->addOperation(
          [NodeRevisionGenerateBatch::class, 'generateRevisions'],
          [$node->nid, $revision_timestamp]
        );
        $revision_timestamp += $revisions_age;
        $i++;
      }
    }

    return $batch_builder->toArray();
  }

  /**
   * {@inheritdoc}
   */
  public function existsNodesContentType($content_type) {
    $query = $this->connection->select('node');
    // Get the node id to generate revisions.
    $query->addField('node', 'nid');
    // Get nodes of selected content types (bundles).
    $query->condition('type', $content_type);
    // Return the available nodes ids and its next revision date, as array.
    return (bool) $query->countQuery()->execute()->fetchField();
  }

}
