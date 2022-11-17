<?php

namespace Drupal\node_revision_delete\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\node_revision_delete\NodeRevisionDeleteInterface;
use Drupal\node_revision_delete\Utility\Time;
use Drush\Commands\DrushCommands;

/**
 * The Node Revision Delete Commands.
 *
 * @package Drupal\node_revision_delete\Commands
 */
class NodeRevisionDeleteCommands extends DrushCommands {

  /**
   * The ConfigManager service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The NodeRevisionDelete service.
   *
   * @var \Drupal\node_revision_delete\NodeRevisionDeleteInterface
   */
  protected NodeRevisionDeleteInterface $nodeRevisionDelete;

  /**
   * The DateFormatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * The State service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * NodeRevisionDeleteCommands constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The ConfigManager service.
   * @param \Drupal\node_revision_delete\NodeRevisionDeleteInterface $nodeRevisionDelete
   *   The NodeRevisionDelete service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The DateFormatter service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The State service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The EntityTypeManager service.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    NodeRevisionDeleteInterface $nodeRevisionDelete,
    DateFormatterInterface $dateFormatter,
    StateInterface $state,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->configFactory = $configFactory;
    $this->nodeRevisionDelete = $nodeRevisionDelete;
    $this->dateFormatter = $dateFormatter;
    $this->state = $state;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Deletes old node revisions for a given content type.
   *
   * @param string $type
   *   Content type machine name.
   * @param array $options
   *   The options.
   *
   * @option dry_run Test run without deleting revisions but seeing the output.
   *
   * @usage nrd article
   *   Delete article revisions according to set configuration.
   * @usage nrd page --dry_run
   *   Execute the deletion process without delete the revisions, just to see
   *   the output result.
   *
   * @command node-revision-delete
   * @aliases nrd
   */
  public function nodeRevisionDelete(string $type, array $options = ['dry_run' => FALSE]): void {
    // Get all the candidate revisions.
    $candidate_revisions = $this->nodeRevisionDelete->getCandidatesRevisions($type);
    // Checking if this is a dry run.
    if ($options['dry_run']) {
      $this->io()->writeln(dt('This is a dry run. No revision will be deleted.'));
    }

    // Start the batch job.
    batch_set($this->nodeRevisionDelete->getRevisionDeletionBatch($candidate_revisions, $options['dry_run']));
    drush_backend_batch_process();
  }

  /**
   * Configures how many revisions delete per cron run.
   *
   * @param int|null $quantity
   *   Revisions quantity to delete per cron run.
   *
   * @usage nrd-delete-cron-run
   *   Show how many revisions the module will delete per cron run.
   * @usage nrd-delete-cron-run 50
   *   Configure the module to delete 50 revisions per cron run.
   *
   * @command nrd:delete-cron-run
   * @aliases nrd-dcr, nrd-delete-cron-run
   */
  public function deleteCronRun(?int $quantity = NULL): void {
    // Getting an editable config because we will get and set a value.
    $config = $this->configFactory->getEditable('node_revision_delete.settings');
    // If no argument found?
    if (!is_null($quantity)) {
      // Saving the values in the config.
      $config->set('node_revision_delete_cron', $quantity);
      $config->save();

      $message = dt('<info>The module was configured to delete @revisions revisions per cron run.</info>', ['@revisions' => $quantity]);
      $this->io()->writeln($message);
    }
    else {
      // Getting the values from the config.
      $revisions = $config->get('node_revision_delete_cron');
      $message = dt('<info>The revisions quantity to delete per cron run is: @revisions.</info>', ['@revisions' => $revisions]);
      $this->io()->writeln($message);
    }
  }

  /**
   * Get the last time that the node revision delete was made.
   *
   * @usage nrd-last-execute
   *   Show the last time that the node revision delete was made.
   *
   * @command nrd:last-execute
   * @aliases nrd-le, nrd-last-execute
   */
  public function lastExecute(): void {
    // Getting the value.
    $last_execute = $this->state->get('node_revision_delete.last_execute', 0);
    if (!empty($last_execute)) {
      $last_execute = $this->dateFormatter->format($last_execute);
      $message = dt('<info>The last time when node revision delete was made was: @last_execute.</info>', ['@last_execute' => $last_execute]);
    }
    else {
      $message = dt('<info>The removal of revisions through the module node revision delete has never been executed on this site.</info>');
    }
    $this->writeln($message);
  }

  /**
   * Configures the frequency with which to delete revisions while cron run.
   *
   * @param string $time
   *   The time value (never, every_hour, every_time, everyday, every_week,
   *   every_10_days, every_15_days, every_month, every_3_months,
   *   every_6_months, every_year or every_2_years)
   *
   * @usage nrd-set-time
   *   Show a list to select the frequency with which to delete revisions while
   *   cron is running.
   * @usage nrd-set-time every_time
   *   Configure the module to delete revisions every time the cron runs.
   *
   * @command nrd:set-time
   * @aliases nrd-st, nrd-set-time
   */
  public function setTime(string $time = ''): void {
    // Getting an editable config because we will get and set a value.
    $config = $this->configFactory->getEditable('node_revision_delete.settings');

    // Check for correct argument.
    $options = Time::convertWordToTime();
    $options_keys = array_keys($options);

    if (!in_array($time, $options_keys)) {
      if (!empty($time)) {
        $this->writeln(dt('"@time_value" is not a valid time argument.', ['@time_value' => $time]));
      }
      $choice = $this->io()->choice(dt('Choose the frequency with which to delete revisions while cron is running:'), $this->nodeRevisionDelete->getTimeValues());
      $time = $options[$options_keys[$choice]];
    }
    else {
      $time = $options[$time];
    }
    // Saving the values in the config.
    $config->set('node_revision_delete_time', $time);
    $config->save();
    // Getting the values from the config.
    $time_value = $this->nodeRevisionDelete->getTimeValues($time);
    $message = dt('<info>The frequency with which to delete revisions while cron is running was set to: @time.</info>', ['@time' => $time_value]);
    $this->writeln($message);
  }

  /**
   * Shows the frequency with which to delete revisions while cron is running.
   *
   * @usage nrd-get-time
   *   Shows the actual frequency with which to delete revisions while cron is
   *   running.
   *
   * @command nrd:get-time
   * @aliases nrd-gt, nrd-get-time
   */
  public function getTime(): void {
    // Getting the config.
    $config = $this->configFactory->get('node_revision_delete.settings');
    // Getting the values from the config.
    $time = $config->get('node_revision_delete_time');
    $time = $this->nodeRevisionDelete->getTimeValues($time);

    $message = dt('<info>The frequency with which to delete revisions while cron is running is: @time.</info>', ['@time' => $time]);
    $this->writeln($message);
  }

  /**
   * Configures the time options for the inactivity time.
   *
   * Configures the time options for the inactivity time that the revision must
   * have to be deleted.
   *
   * @param int|null $max_number
   *   The maximum number for inactivity time configuration.
   * @param int|null $time
   *   The time value for inactivity time configuration (days, weeks or months).
   *
   * @usage nrd-when-to-delete-time
   *   Shows the time configuration for the inactivity time.
   * @usage nrd-when-to-delete-time 30 days
   *   Set the maximum inactivity time to 30 days.
   * @usage nrd-when-to-delete-time 6 weeks
   *   Set the maximum inactivity time to 6 weeks.
   *
   * @command nrd:when-to-delete-time
   * @aliases nrd-wtdt, nrd-when-to-delete-time
   */
  public function whenToDeleteTime(?int $max_number = NULL, ?int $time = NULL): void {
    // Getting an editable config because we will get and set a value.
    $config = $this->configFactory->getEditable('node_revision_delete.settings');
    // Getting or setting values?
    if (isset($max_number)) {
      // Saving the values in the config.
      $node_revision_delete_when_to_delete_time['max_number'] = $max_number;
      $node_revision_delete_when_to_delete_time['time'] = $time;
      $config->set('node_revision_delete_when_to_delete_time', $node_revision_delete_when_to_delete_time);
      $config->save();

      $time = $this->nodeRevisionDelete->getTimeNumberString($time) == 1 ? $time['singular'] : $time['plural'];
      // We need to update the max_number in the existing content type
      // configuration if the new value is lower than the actual.
      $this->nodeRevisionDelete->updateTimeMaxNumberConfig('when_to_delete', $max_number);

      $message = dt('<info>The maximum inactivity time was set to @max_number @time.</info>', [
        '@max_number' => $max_number,
        '@time' => $time,
      ]);
      $this->writeln($message);
    }
    else {
      // Getting the values from the config.
      $node_revision_delete_when_to_delete_time = $config->get('node_revision_delete_when_to_delete_time');
      $max_number = $node_revision_delete_when_to_delete_time['max_number'];
      $time = $this->nodeRevisionDelete->getTimeNumberString($time) == 1 ? $time['singular'] : $time['plural'];

      $message = dt('<info>The maximum inactivity time is: @max_number @time.</info>', [
        '@max_number' => $max_number,
        '@time' => $time,
      ]);
      $this->writeln($message);
    }
  }

  /**
   * Configures time options to know the minimum age.
   *
   * Configures time options to know the minimum age. that the revision must
   * have to be delete.
   *
   * @param int|null $max_number
   *   The maximum number for minimum age configuration.
   * @param int|null $time
   *   The time value for minimum age configuration (days, weeks or months).
   *
   * @usage nrd-minimum-age-to-delete-time
   *   Shows the time configuration for the minimum age of revisions.
   * @usage nrd-minimum-age-to-delete-time 30 days
   *   Set the maximum time for the minimum age to 30 days.
   * @usage nrd-minimum-age-to-delete-time 6 weeks
   *   Set the maximum time for the minimum age to 6 weeks.
   *
   * @command nrd:minimum-age-to-delete-time
   * @aliases nrd-matdt, nrd-minimum-age-to-delete-time
   */
  public function minimumAgeToDeleteTime(?int $max_number = NULL, ?int $time = NULL): void {
    // Getting an editable config because we will get and set a value.
    $config = $this->configFactory->getEditable('node_revision_delete.settings');
    // Getting or setting values?
    if (isset($max_number)) {
      // Saving the values in the config.
      $node_revision_delete_minimum_age_to_delete_time['max_number'] = $max_number;
      $node_revision_delete_minimum_age_to_delete_time['time'] = $time;
      $config->set('node_revision_delete_minimum_age_to_delete_time', $node_revision_delete_minimum_age_to_delete_time);
      $config->save();

      // We need to update the max_number in the existing content type
      // configuration if the new value is lower than the actual.
      $this->nodeRevisionDelete->updateTimeMaxNumberConfig('minimum_age_to_delete', $max_number);
      $time = $this->nodeRevisionDelete->getTimeNumberString($time) == 1 ? $time['singular'] : $time['plural'];

      // Is singular or plural?
      $message = dt('<info>The maximum time for the minimum age was set to @max_number @time.</info>', [
        '@max_number' => $max_number,
        '@time' => $time,
      ]);
      $this->writeln($message);
    }
    else {
      // Getting the values from the config.
      $node_revision_delete_minimum_age_to_delete_time = $config->get('node_revision_delete_minimum_age_to_delete_time');
      $max_number = $node_revision_delete_minimum_age_to_delete_time['max_number'];
      $time = $this->nodeRevisionDelete->getTimeNumberString($time) == 1 ? $time['singular'] : $time['plural'];

      // Is singular or plural?
      $message = dt('<info>The maximum time for the minimum age is: @max_number @time.</info>', [
        '@max_number' => $max_number,
        '@time' => $time,
      ]);
      $this->writeln($message);
    }
  }

  /**
   * Delete all revisions prior to a revision.
   *
   * @param int $nid
   *   The id of the node which revisions will be deleted.
   * @param int $vid
   *   The revision id, all prior revisions to this revision will be deleted.
   *
   * @usage nrd-delete-prior-revisions 1 3
   *   Delete all revisions prior to revision id 3 of node id 1.
   * @command nrd:delete-prior-revisions
   * @aliases nrd-dpr,nrd-delete-prior-revisions
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function deletePriorRevisions(int $nid = 0, int $vid = 0): void {
    // Get list of prior revisions.
    $previousRevisions = $this->nodeRevisionDelete->getPreviousRevisions($nid, $vid);

    if (count($previousRevisions) === 0) {
      $this->writeln(dt('<error>No prior revision(s) found to delete.</error>'));
      return;
    }

    if ($this->io()->confirm(dt("Confirm deleting @count revision(s)?", ['@count' => count($previousRevisions)]))) {
      // Check if current revision should be deleted, too.
      if ($this->io()->confirm(dt("Additionally, do you want to delete the revision @vid? @count revision(s) will be deleted.", [
        '@vid' => $vid,
        '@count' => count($previousRevisions) + 1,
      ]))) {
        $this->entityTypeManager->getStorage('node')->deleteRevision($vid);
      }

      foreach ($previousRevisions as $revision) {
        $this->entityTypeManager->getStorage('node')->deleteRevision($revision->getRevisionId());
      }
    }
  }

  /**
   * Validate inputs before executing the drush command node-revision-delete.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The command data.
   *
   * @return bool
   *   Returns TRUE if the validations has passed FALSE otherwise.
   *
   * @hook validate nrd
   */
  public function nodeRevisionDeleteValidate(CommandData $commandData): bool {
    $input = $commandData->input();
    $type = $input->getArgument('type');

    if (!$this->configuredContentType($type)) {
      return FALSE;
    }

    // Checking if we have candidates nodes to delete.
    $candidates = count($this->nodeRevisionDelete->getCandidatesRevisions($type));
    if (!$candidates) {
      $this->io()->warning(dt('There are no revisions to delete for @content_type.', ['@content_type' => $type]));
    }

    return TRUE;
  }

  /**
   * Validate inputs before executing the drush command nrd-dpr.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The command data.
   *
   * @return bool
   *   Returns TRUE if the validations has passed FALSE otherwise.
   *
   * @hook validate nrd-delete-prior-revisions
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function deletePriorRevisionsValidate(CommandData $commandData): bool {
    $input = $commandData->input();
    $nid = $input->getArgument('nid');

    // Check if argument nid is a valid node id.
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    if (is_null($node)) {
      $this->io()->error(dt("@nid is not a valid node id.", ['@nid' => $nid]));
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Untrack a content type.
   *
   * @param string $type
   *   The content type name.
   *
   * @usage nrd-untrack article
   *   Untrack the content type article.
   * @command nrd:untrack
   * @aliases nrd-u, nrd-untrack
   */
  public function untrack(string $type): void {
    $this->nodeRevisionDelete->deleteContentTypeConfig($type);
    $message = dt('<info>The content type @type is now untracked.</info>', ['@type' => $type]);
    $this->writeln($message);
  }

  /**
   * Validate inputs before executing the drush command nrd-untrack.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The command data.
   *
   * @return bool
   *   Returns TRUE if the validations has passed FALSE otherwise.
   *
   * @hook validate nrd-u
   */
  public function untrackValidate(CommandData $commandData): bool {
    $input = $commandData->input();
    $type = $input->getArgument('type');

    if (!$this->configuredContentType($type)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Validate inputs before executing the drush command nrd-untrack.
   *
   * @param string $type
   *   The content type name.
   *
   * @return bool
   *   Returns if a content type is configured.
   */
  private function configuredContentType(string $type): bool {
    // Make sure the content type exists and is configured.
    $available_content_types = array_map(static function ($content_type) {
      /** @var \Drupal\node\NodeTypeInterface $content_type */
      return $content_type->id();
    }, $this->nodeRevisionDelete->getConfiguredContentTypes());

    if (!in_array($type, $available_content_types, TRUE)) {
      $this->io()->error(dt('The following content type is not configured for revision deletion: @name',
        [
          '@name' => $type,
        ]
      ));
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Track a content type into node revision delete system.
   *
   * @usage nrd-track article 50 1 15
   *   Track the article content type with:
   *   50 minimum number of revision to keep.
   *   1 minimum age of revision to delete.
   *   15 when to delete the revisions.
   *
   * @command nrd:track
   * @aliases nrd-t, nrd-track
   */
  public function track(string $content_type, int $minimum_revisions_to_keep, int $minimum_age_to_delete, int $when_to_delete): bool {
    // Validate for a valid content type.
    $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $content_types_ids = array_map(function (NodeType $object) {
      return $object->id();
    }, $content_types);

    if (!in_array($content_type, $content_types_ids)) {
      $this->io()->error(dt('Argument content type is not a valid content type.'));
      return FALSE;
    }

    // Validate for Maximum number allowed.
    $config = $this->configFactory->getEditable('node_revision_delete.settings');
    $node_revision_delete_minimum_age_to_delete_time = $config->get('node_revision_delete_minimum_age_to_delete_time');
    $node_revision_delete_when_to_delete_time = $config->get('node_revision_delete_when_to_delete_time');
    if ($minimum_age_to_delete > $node_revision_delete_minimum_age_to_delete_time['max_number']) {
      $this->io()->error(dt('Argument minimum_age_to_delete must lower or equal to @number', ['@number' => $node_revision_delete_minimum_age_to_delete_time['max_number']]));
      return FALSE;
    }

    if ($when_to_delete > $node_revision_delete_when_to_delete_time['max_number']) {
      $this->io()->error(dt('Argument when_to_delete must lower or equal to @number.', ['@number' => $node_revision_delete_when_to_delete_time['max_number']]));
      return FALSE;
    }

    $this->nodeRevisionDelete->saveContentTypeConfig($content_type, $minimum_revisions_to_keep, $minimum_age_to_delete, $when_to_delete);
    $message = dt('<info>The content type @content_type is now tracked.</info>', ['@content_type' => $content_type]);
    $this->writeln($message);

    return TRUE;
  }

}
