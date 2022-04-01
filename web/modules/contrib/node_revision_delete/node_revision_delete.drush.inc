<?php

/**
 * @file
 * Drush commands related to the Node Revision Delete module.
 */

use Drupal\node_revision_delete\Utility\Time;
use Drupal\node\Entity\Node;

/**
 * Implements hook_drush_command().
 */
function node_revision_delete_drush_command() {
  $items['node-revision-delete'] = [
    'description' => dt('Deletes old node revisions for a given content type.'),
    'arguments' => [
      'type' => dt('Content type machine name.'),
    ],
    'options' => [
      'dry_run' => [
        'description' => dt('Test run without deleting revisions but seeing the output results.'),
      ],
    ],
    'aliases' => ['nrd'],
    'examples' => [
      'drush nrd article' => dt('Delete article revisions according to set configuration.'),
      'drush nrd page --dry_run' => dt('Execute the deletion process without delete the revisions, just to see the output result.'),
    ],
  ];

  $items['nrd-delete-cron-run'] = [
    'description' => dt('Configures how many revisions delete per cron run.'),
    'arguments' => [
      'quantity' => dt('Revisions quantity to delete per cron run.'),
    ],
    'aliases' => ['nrd-dcr'],
    'examples' => [
      'nrd-delete-cron-run' => dt('Show how many revisions the module will delete per cron run.'),
      'nrd-delete-cron-run 50' => dt('Configure the module to delete 50 revisions per cron run.'),
    ],
  ];
  $items['nrd-last-execute'] = [
    'description' => dt('Get the last time that the node revision delete was made.'),
    'aliases' => ['nrd-le'],
    'examples' => [
      'nrd-last-execute' => dt('Show the last time that the node revision delete was made.'),
    ],
  ];

  $items['nrd-set-time'] = [
    'description' => dt('Configures the frequency with which to delete revisions while cron is running.'),
    'aliases' => ['nrd-st'],
    'arguments' => [
      // Is not possible to call here Time::convertWordToTime() read more at
      // https://drupal.stackexchange.com/q/268239/28275 .
      'time' => dt('The time value (never, every_hour, every_time, everyday, every_week, every_10_days, every_15_days, every_month, every_3_months, every_6_months, every_year or every_2_years)'),
    ],
    'examples' => [
      'nrd-set-time' => dt('Show a list to select the frequency with which to delete revisions while cron is running.'),
      'nrd-set-time every_time' => dt('Configure the module to delete revisions every time the cron runs.'),
    ],
  ];
  $items['nrd-get-time'] = [
    'description' => dt('Shows the frequency with which to delete revisions while cron is running.'),
    'aliases' => ['nrd-gt'],
    'examples' => [
      'nrd-get-time' => dt('Shows the actual frequency with which to delete revisions while cron is running.'),
    ],
  ];
  $items['nrd-when-to-delete-time'] = [
    'description' => dt('Configures the time options for the inactivity time that the revision must have to be deleted.'),
    'arguments' => [
      'max_number' => dt('The maximum number for inactivity time configuration'),
      'time' => dt('The time value for inactivity time configuration (days, weeks or months)'),
    ],
    'aliases' => ['nrd-wtdt'],
    'examples' => [
      'nrd-when-to-delete-time ' => dt('Shows the time configuration for the inactivity time.'),
      'nrd-when-to-delete-time 30 days' => dt('Set the maximum inactivity time to 30 days.'),
      'nrd-when-to-delete-time 6 weeks' => dt('Set the maximum inactivity time to 6 weeks.'),
    ],
  ];
  $items['nrd-minimum-age-to-delete-time'] = [
    'description' => dt('Configures time options to know the minimum age that the revision must have to be delete.'),
    'arguments' => [
      'max_number' => dt('The maximum number for minimum age configuration'),
      'time' => dt('The time value for minimum age configuration (days, weeks or months)'),
    ],
    'aliases' => ['nrd-matdt'],
    'examples' => [
      'nrd-minimum-age-to-delete-time ' => dt('Shows the time configuration for the minimum age of revisions.'),
      'nrd-minimum-age-to-delete-time 30 days' => dt('Set the maximum time for the minimum age to 30 days.'),
      'nrd-minimum-age-to-delete-time 6 weeks' => dt('Set the maximum time for the minimum age to 6 weeks.'),
    ],
  ];
  $items['nrd-delete-prior-revisions'] = [
    'description' => dt('Delete all revisions prior to a revision.'),
    'arguments' => [
      'nid' => dt('The id of the node which revisions will be deleted.'),
      'vid' => dt('The revision id, all prior revisions to this revision will be deleted.'),
    ],
    'aliases' => ['nrd-dpr'],
    'examples' => [
      'nrd-delete-prior-revisions 1 3' => dt('Delete all revisions prior to revision id 3 of node id 1.'),
    ],
  ];
  return $items;
}

/**
 * Implements hook_drush_help().
 */
function node_revision_delete_drush_help($section) {
  switch ($section) {
    case 'meta:node_revision_delete:title':
      return dt('Node Revision Delete commands');

    case 'meta:node_revision_delete:summary':
      return dt("Interacts with the Node Revision Delete module's functionalities.");

    case 'drush:node-revision-delete':
      return dt('Deletes old node revisions for a given content type.');

    case 'drush:nrd-delete-cron-run':
      return dt('Configures how many revisions delete per cron run.');

    case 'drush:nrd-last-execute':
      return dt('Get the last time that the node revision delete was made.');

    case 'drush:nrd-set-time':
      return dt('Configures the frequency with which to delete revisions while cron is running.');

    case 'drush:nrd-get-time':
      return dt('Shows the frequency with which to delete revisions while cron is running.');

    case 'drush:nrd-when-to-delete-time':
      return dt('Configures the time options for the inactivity time that the revision must have to be deleted.');

    case 'drush:nrd-minimum-age-to-delete-time':
      return dt('Configures time options to know the minimum age that the revision must have to be deleted.');

    case 'drush:nrd-delete-prior-revisions':
      return dt('Define which prior revisions to which revision of which node to delete.');
  }
}

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_node_revision_delete_validate() {
  $args = func_get_args();
  // Make sure the content type exists and is configured.
  $available_content_types = array_map(function ($content_type) {
    /** @var \Drupal\node\NodeTypeInterface $availables_content_types */
    return $content_type->id();
  }, Drupal::service('node_revision_delete')->getConfiguredContentTypes());

  $not_available_content_types = array_diff($args, $available_content_types);

  if (count($not_available_content_types)) {
    $names = implode(', ', $not_available_content_types);
    $singular = "The following content type is not configured for revision deletion: @names";
    $plural = "The following content types are not configured for revision deletion: @names";
    $message = _node_revision_delete_drush_plural(count($not_available_content_types), $singular, $plural, ['@names' => $names]);
    return drush_set_error('NODE_REVISION_DELETE_INVALID_ARGUMENT', $message);
  }

  // Checking if we have candidates nodes to delete.
  $candidates = count(\Drupal::service('node_revision_delete')->getCandidatesRevisions($args[0]));
  if (!$candidates) {
    drush_log(dt('There is not revisions to delete for @content_type.', ['@content_type' => $args[0]], 'warning'));
    return FALSE;
  }
}

/**
 * Callback for the node-revision-delete command.
 */
function drush_node_revision_delete() {
  $args = func_get_args();
  // Getting the --dry_run option.
  $dry_run = drush_get_option('dry_run');
  // Get all the candidate revisions.
  $candidate_revisions = \Drupal::service('node_revision_delete')->getCandidatesRevisions($args[0]);
  // Checking if this is a dry run.
  if ($dry_run) {
    drush_log(dt('This is a dry run. No revision will be deleted.'), 'warning');
  }
  // Start the batch job.
  batch_set(\Drupal::service('node_revision_delete')->getRevisionDeletionBatch($candidate_revisions, $dry_run));
  drush_backend_batch_process();
}

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_node_revision_delete_nrd_delete_cron_run_validate() {
  $args = func_get_args();
  // If we don't have arguments we will return the config values.
  if (!count($args)) {
    return;
  }
  // Check for only one argument.
  if (count($args) > 1) {
    return drush_set_error('NODE_REVISION_DELETE_INVALID_ARGUMENT', dt('This command use only one argument as quantity.'));
  }
  // Check for integer quantity.
  if (!ctype_digit($args[0])) {
    return drush_set_error('NODE_REVISION_DELETE_INVALID_ARGUMENT', dt('You must specify digits for the quantity.'));
  }
}

/**
 * Callback for the nrd-delete-cron-run command.
 */
function drush_node_revision_delete_nrd_delete_cron_run() {
  $args = func_get_args();
  // Getting an editable config because we will get and set a value.
  $config = \Drupal::service('config.factory')->getEditable('node_revision_delete.settings');
  // Getting or setting values?
  if (isset($args[0])) {
    // Saving the values in the config.
    $config->set('node_revision_delete_cron', $args[0]);
    $config->save();

    $message = dt('The module was configurated to delete @revisions revisions per cron run.', ['@revisions' => $args[0]]);
    drush_log($message, 'success');
  }
  else {
    // Getting the values from the config.
    $revisions = $config->get('node_revision_delete_cron');
    $message = dt('The revisions quantity to delete per cron run is: @revisions.', ['@revisions' => $revisions]);
    drush_print($message);
  }
}

/**
 * Callback for the nrd-last_execute command.
 */
function drush_node_revision_delete_nrd_last_execute() {
  // Getting the value.
  $last_execute = \Drupal::state()->get('node_revision_delete.last_execute', 0);
  if (!empty($last_execute)) {
    $last_execute = \Drupal::service('date.formatter')->format($last_execute);
    $message = dt('The last time when node revision delete was made was: @last_execute.', ['@last_execute' => $last_execute]);
  }
  else {
    $message = dt('The removal of revisions through the module node revision delete has never been executed on this site.');
  }
  drush_print($message);
}

/**
 * Callback for the nrd-delete-set-time command.
 */
function drush_node_revision_delete_nrd_set_time($time = '') {
  // Getting an editable config because we will get and set a value.
  $config = \Drupal::service('config.factory')->getEditable('node_revision_delete.settings');
  // Check for correct argument.
  $options = Time::convertWordToTime();
  if (!in_array($time, array_keys($options))) {
    if (!empty($time)) {
      drush_log(dt('"@time_value" is not a valid time argument.', ['@time_value' => $time]), 'warning');
    }
    $time = drush_choice(\Drupal::service('node_revision_delete')->getTimeValues(), dt('Choose the frequency with which to delete revisions while cron is running:'));
  }
  else {
    $time = $options[$time];
  }
  // Saving the values in the config.
  $config->set('node_revision_delete_time', $time);
  $config->save();
  $time_value = \Drupal::service('node_revision_delete')->getTimeValues($time);
  $message = dt('The frequency with which to delete revisions while cron is running was set to: @time.', ['@time' => $time_value]);
  drush_log($message, 'success');
}

/**
 * Callback for the nrd-delete-get-time command.
 */
function drush_node_revision_delete_nrd_get_time() {
  // Getting the config.
  $config = $config = \Drupal::config('node_revision_delete.settings');
  // Getting the values from the config.
  $time = $config->get('node_revision_delete_time');
  $time = \Drupal::service('node_revision_delete')->getTimeValues($time);
  $message = dt('The frequency with which to delete revisions while cron is running is: @time.', ['@time' => $time]);
  drush_print($message);
}

/**
 * Helper function to validate the maximum time.
 *
 * @param array $args
 *   An array with number and time.
 *
 * @return bool
 *   A boolean indicating the success of the validation.
 */
function _drush_node_revision_delete_validate_maximum_time(array $args) {
  // If we don't have arguments we will return the config values.
  if (!count($args)) {
    return TRUE;
  }
  // Check for only 2 arguments.
  if (count($args) != 2) {
    return drush_set_error('NODE_REVISION_DELETE_INVALID_ARGUMENT', dt('This command use two arguments.'));
  }
  // Check for integer number.
  if (!ctype_digit($args[0])) {
    return drush_set_error('NODE_REVISION_DELETE_INVALID_ARGUMENT', dt('You must enter digits for the maximum number.'));
  }
  // Check for valid integer number.
  if ($args[0] < 1) {
    return drush_set_error('NODE_REVISION_DELETE_INVALID_ARGUMENT', dt('You must enter a digit no more less than 1 for the maximum number.'));
  }
  // Check for time.
  if (!in_array($args[1], ['days', 'weeks', 'months'])) {
    return drush_set_error('NODE_REVISION_DELETE_INVALID_ARGUMENT', dt('The accepted time arguments are: days, weeks or months.'));
  }
}

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_node_revision_delete_nrd_when_to_delete_time_validate() {
  $args = func_get_args();
  _drush_node_revision_delete_validate_maximum_time($args);
}

/**
 * Callback for the nrd-when-to-delete-time command.
 */
function drush_node_revision_delete_nrd_when_to_delete_time() {
  $args = func_get_args();
  // Getting an editable config because we will get and set a value.
  $config = \Drupal::service('config.factory')->getEditable('node_revision_delete.settings');
  // Getting or setting values?
  if (isset($args[0])) {
    // Saving the values in the config.
    $node_revision_delete_when_to_delete_time['max_number'] = $args[0];
    $node_revision_delete_when_to_delete_time['time'] = $args[1];
    $config->set('node_revision_delete_when_to_delete_time', $node_revision_delete_when_to_delete_time);
    $config->save();

    // We need to update the max_number in the existing content type
    // configuration if the new value is lower than the actual.
    \Drupal::service('node_revision_delete')->updateTimeMaxNumberConfig('when_to_delete', $args[0]);

    $time = \Drupal::service('node_revision_delete')->getTimeNumberString($args[0], $args[1]);
    $message = dt('The maximum inactivity time was set to @max_number @time.', ['@max_number' => $args[0], '@time' => $time]);
    drush_log($message, 'success');
  }
  else {
    // Getting the values from the config.
    $node_revision_delete_when_to_delete_time = $config->get('node_revision_delete_when_to_delete_time');
    $max_number = $node_revision_delete_when_to_delete_time['max_number'];
    $time = $node_revision_delete_when_to_delete_time['time'];

    $time = \Drupal::service('node_revision_delete')->getTimeNumberString($max_number, $time);
    $message = dt('The maximum inactivity time is: @max_number @time.', ['@max_number' => $max_number, '@time' => $time]);
    drush_print($message);
  }
}

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_node_revision_delete_nrd_minimum_age_to_delete_time_validate() {
  $args = func_get_args();
  _drush_node_revision_delete_validate_maximum_time($args);
}

/**
 * Callback for the nrd-minimum-age-to-delete-time command.
 */
function drush_node_revision_delete_nrd_minimum_age_to_delete_time() {
  $args = func_get_args();
  // Getting an editable config because we will get and set a value.
  $config = \Drupal::service('config.factory')->getEditable('node_revision_delete.settings');
  // Getting or setting values?
  if (isset($args[0])) {
    // Saving the values in the config.
    $node_revision_delete_minimum_age_to_delete_time['max_number'] = $args[0];
    $node_revision_delete_minimum_age_to_delete_time['time'] = $args[1];
    $config->set('node_revision_delete_minimum_age_to_delete_time', $node_revision_delete_minimum_age_to_delete_time);
    $config->save();

    // We need to update the max_number in the existing content type
    // configuration if the new value is lower than the actual.
    \Drupal::service('node_revision_delete')->updateTimeMaxNumberConfig('minimum_age_to_delete', $args[0]);

    // Is singular or plural?
    $time = \Drupal::service('node_revision_delete')->getTimeNumberString($args[0], $args[1]);
    $message = dt('The maximum time for the minimum age was set to @max_number @time.', ['@max_number' => $args[0], '@time' => $time]);
    drush_log($message, 'success');
  }
  else {
    // Getting the values from the config.
    $node_revision_delete_minimum_age_to_delete_time = $config->get('node_revision_delete_minimum_age_to_delete_time');
    $max_number = $node_revision_delete_minimum_age_to_delete_time['max_number'];
    $time = $node_revision_delete_minimum_age_to_delete_time['time'];

    // Is singular or plural?
    $time = \Drupal::service('node_revision_delete')->getTimeNumberString($max_number, $time);
    $message = dt('The maximum time for the minimum age is: @max_number @time.', ['@max_number' => $max_number, '@time' => $time]);
    drush_print($message);
  }
}

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_node_revision_delete_nrd_delete_prior_revisions_validate() {
  $args = func_get_args();
  // Check for only 2 arguments.
  if (count($args) != 2) {
    return drush_set_error('NODE_REVISION_DELETE_INVALID_ARGUMENT', dt('This command use two arguments.'));
  }
  // Check if the first argument is a valid node id.
  $node = Node::load($args[0]);
  if (is_null($node)) {
    return drush_set_error('NODE_REVISION_DELETE_INVALID_ARGUMENT', dt('The first argument @nid is not a valid node id.', ['@nid' => $args[0]]));
  }
  // Check if the second argument is a valid revision id.
  $revision = \Drupal::entityTypeManager()->getStorage('node')->loadRevision($args[1]);
  if (is_null($revision)) {
    return drush_set_error('NODE_REVISION_DELETE_INVALID_ARGUMENT', dt('The second argument @vid is not a valid revision id.', ['@vid' => $args[1]]));
  }
  // Check if the nid and the vid match the same node.
  if ($node->id() !== $revision->id()) {
    return drush_set_error('NODE_REVISION_DELETE_INVALID_ARGUMENT', dt('The revision id @vid doesn\'t belong to the node id @nid.', ['@nid' => $args[0], '@vid' => $args[1]]));
  }
  // Check if exists prior revisions.
  if (!count(\Drupal::service('node_revision_delete')->getPreviousRevisions($args[0], $args[1]))) {
    drush_log(dt('There is no revision prior to revision @vid.', ['@vid' => $args[1]]), 'warning');
    return FALSE;
  }
}

/**
 * Callback for nrd-delete-prior-revisions command.
 *
 * @param string|int $nid
 *   - The nid of the node to delete.
 * @param string|int $vid
 *   - The revision id which is used to find prior revisions.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function drush_node_revision_delete_nrd_delete_prior_revisions($nid, $vid) {

  if (drush_confirm(dt("Additionally, do you want to delete the revision @vid?", ['@vid' => $vid]))) {
    \Drupal::entityTypeManager()->getStorage('node')->deleteRevision($vid);
    drush_print(dt('The revision @vid was sucessfully deleted.', ['@vid' => $vid]));
  }

  // Get a list of revisions to delete.
  $revisions_before = \Drupal::service('node_revision_delete')->getPreviousRevisions($nid, $vid);

  // Loop through the list of revisions, then delete them one by one.
  foreach ($revisions_before as $revision) {
    \Drupal::entityTypeManager()->getStorage('node')->deleteRevision($revision->getRevisionId());
  }

  // Print out success message.
  // @TODO: Add a pluran message for the case only one revision is deleted.
  $message = dt('Successfully deleted @count revision(s) of node @nid.', ['@count' => count($revisions_before), '@nid' => $nid]);
  drush_print($message);
}

/**
 * Formats a plural string containing a count of items.
 *
 * This function ensures that the string is pluralized correctly. Since dt() is
 * called by this function, make sure not to pass already-localized strings to
 * it.
 *
 * For example:
 * @code
 *   $output = _node_revision_delete_drush_plural($node->comment_count, '1 comment', '@count comments');
 * @endcode
 *
 * @param int $count
 *   The item count to display.
 * @param string $singular
 *   The string for the singular case. Make sure it is clear this is singular,
 *   to ease translation (e.g. use "1 new comment" instead of "1 new"). Do not
 *   use @count in the singular string.
 * @param string $plural
 *   The string for the plural case. Make sure it is clear this is plural, to
 *   ease translation. Use @count in place of the item count, as in
 *   "@count new comments".
 * @param array $args
 *   An associative array of replacements to make after translation. Instances
 *   of any key in this array are replaced with the corresponding value.
 *   Based on the first character of the key, the value is escaped and/or
 *   themed. See format_string(). Note that you do not need to include @count
 *   in this array; this replacement is done automatically for the plural case.
 * @param array $options
 *   An associative array of additional options. See dt() for allowed keys.
 *
 * @return string
 *   A translated string.
 *
 * @see dt()
 */
function _node_revision_delete_drush_plural($count, $singular, $plural, array $args = [], array $options = []) {
  $args['@count'] = $count;
  if ($count == 1) {
    return dt($singular, $args, $options);
  }

  // Get the plural index through the gettext formula.
  $index = (function_exists('locale_get_plural')) ? locale_get_plural($count, isset($options['langcode']) ? $options['langcode'] : NULL) : -1;
  // If the index cannot be computed, use the plural as a fallback (which
  // allows for most flexiblity with the replaceable @count value).
  if ($index < 0) {
    return dt($plural, $args, $options);
  }
  else {
    switch ($index) {
      case "0":
        return dt($singular, $args, $options);

      case "1":
        return dt($plural, $args, $options);

      default:
        unset($args['@count']);
        $args['@count[' . $index . ']'] = $count;
        return dt(strtr($plural, ['@count' => '@count[' . $index . ']']), $args, $options);
    }
  }
}
