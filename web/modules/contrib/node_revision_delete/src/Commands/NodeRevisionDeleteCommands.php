<?php

namespace Drupal\node_revision_delete\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\node_revision_delete\NodeRevisionDelete;
use Drush\Commands\DrushCommands;
use Consolidation\AnnotatedCommand\CommandData;
use Drupal\node_revision_delete\Utility\Time;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class NodeRevisionDeleteCommands.
 *
 * @package Drupal\node_revision_delete\Commands
 */
class NodeRevisionDeleteCommands extends DrushCommands {

  /**
   * The ConfigManager service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The NodeRevisionDelete service.
   *
   * @var \Drupal\node_revision_delete\NodeRevisionDelete
   */
  protected $nodeRevisionDelete;

  /**
   * The DateFormatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The State service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * NodeRevisionDeleteCommands constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The ConfigManager service.
   * @param \Drupal\node_revision_delete\NodeRevisionDelete $nodeRevisionDelete
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
    NodeRevisionDelete $nodeRevisionDelete,
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
  public function nodeRevisionDelete($type, $options = ['dry_run' => FALSE]) {
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
   * @param int $quantity
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
  public function deleteCronRun($quantity = NULL) {
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
  public function lastExecute() {
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
   *
   * @throws \Drush\Exceptions\UserAbortException
   */
  public function setTime($time = '') {
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
  public function getTime() {
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
   * @param int $max_number
   *   The maximum number for inactivity time configuration.
   * @param int $time
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
  public function whenToDeleteTime($max_number = NULL, $time = NULL) {
    // Getting an editable config because we will get and set a value.
    $config = $this->configFactory->getEditable('node_revision_delete.settings');
    // Getting or setting values?
    if (isset($max_number)) {
      // Saving the values in the config.
      $node_revision_delete_when_to_delete_time['max_number'] = $max_number;
      $node_revision_delete_when_to_delete_time['time'] = $time;
      $config->set('node_revision_delete_when_to_delete_time', $node_revision_delete_when_to_delete_time);
      $config->save();

      // We need to update the max_number in the existing content type
      // configuration if the new value is lower than the actual.
      $this->nodeRevisionDelete->updateTimeMaxNumberConfig('when_to_delete', $max_number);

      $time = $this->nodeRevisionDelete->getTimeNumberString($max_number, $time);
      $message = dt('<info>The maximum inactivity time was set to @max_number @time.</info>', ['@max_number' => $max_number, '@time' => $time]);
      $this->writeln($message);
    }
    else {
      // Getting the values from the config.
      $node_revision_delete_when_to_delete_time = $config->get('node_revision_delete_when_to_delete_time');
      $max_number = $node_revision_delete_when_to_delete_time['max_number'];
      $time = $node_revision_delete_when_to_delete_time['time'];

      $time = $this->nodeRevisionDelete->getTimeNumberString($max_number, $time);
      $message = dt('<info>The maximum inactivity time is: @max_number @time.</info>', ['@max_number' => $max_number, '@time' => $time]);
      $this->writeln($message);
    }
  }

  /**
   * Configures time options to know the minimum age.
   *
   * Configures time options to know the minimum age. that the revision must
   * have to be delete.
   *
   * @param int $max_number
   *   The maximum number for minimum age configuration.
   * @param int $time
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
  public function minimumAgeToDeleteTime($max_number = NULL, $time = NULL) {
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

      // Is singular or plural?
      $time = $this->nodeRevisionDelete->getTimeNumberString($max_number, $time);
      $message = dt('<info>The maximum time for the minimum age was set to @max_number @time.</info>', ['@max_number' => $max_number, '@time' => $time]);
      $this->writeln($message);
    }
    else {
      // Getting the values from the config.
      $node_revision_delete_minimum_age_to_delete_time = $config->get('node_revision_delete_minimum_age_to_delete_time');
      $max_number = $node_revision_delete_minimum_age_to_delete_time['max_number'];
      $time = $node_revision_delete_minimum_age_to_delete_time['time'];

      // Is singular or plural?
      $time = $this->nodeRevisionDelete->getTimeNumberString($max_number, $time);
      $message = dt('<info>The maximum time for the minimum age is: @max_number @time.</info>', ['@max_number' => $max_number, '@time' => $time]);
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
  public function deletePriorRevisions($nid = 0, $vid = 0) {
    // Get list of prior revisions.
    $previousRevisions = $this->nodeRevisionDelete->getPreviousRevisions($nid, $vid);

    if (count($previousRevisions) === 0) {
      $this->writeln(dt('<error>No prior revision(s) found to delete.</error>'));
      return;
    }

    if ($this->io()->confirm(dt("Confirm deleting @count revision(s)?", ['@count' => count($previousRevisions)]))) {
      // Check if current revision should be deleted, too.
      if ($this->io()->confirm(dt("Additionally, do you want to delete the revision @vid? @count revision(s) will be deleted.", ['@vid' => $vid, '@count' => count($previousRevisions) + 1]))) {
        $this->entityTypeManager->getStorage('node')->deleteRevision($vid);
      }

      foreach ($previousRevisions as $revision) {
        $this->entityTypeManager->getStorage('node')->deleteRevision($revision->getRevisionId());
      }
    }
  }

  /**
   * Validate inputs before executing the drush command.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The command data.
   *
   * @return bool
   *   Returns TRUE if the validations has passed FALSE otherwise.
   *
   * @hook validate nrd
   */
  public function nodeRevisionDeleteValidate(CommandData $commandData) {
    $input = $commandData->input();
    $type = $input->getArgument('type');

    // Make sure the content type exists and is configured.
    $available_content_types = array_map(function ($content_type) {
      /** @var \Drupal\node\NodeTypeInterface $content_type */
      return $content_type->id();
    }, $this->nodeRevisionDelete->getConfiguredContentTypes());

    if (!in_array($type, $available_content_types)) {
      $this->io()->error(dt('The following content type is not configured for revision deletion: @name',
        [
          '@name' => $type,
        ]
      ));
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
   * Validate inputs before executing the drush command.
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
  public function deletePriorRevisionsValidate(CommandData $commandData) {
    $input = $commandData->input();
    $nid = $input->getArgument('nid');
    $vid = $input->getArgument('vid');

    // Nid argument must be numeric.
    if (!is_numeric($nid)) {
      $this->io()->error(dt('Argument nid must be numeric.'));
      return FALSE;
    }

    // Vid argument must be numeric.
    if (!is_numeric($vid)) {
      $this->io()->error(dt('Argument vid must be numeric.'));
      return FALSE;
    }

    // Check if argument nid is a valid node id.
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    if (is_null($node)) {
      $this->io()->error(dt("@nid is not a valid node id.", ['@nid' => $nid]));
      return FALSE;
    }

    return TRUE;
  }

}
