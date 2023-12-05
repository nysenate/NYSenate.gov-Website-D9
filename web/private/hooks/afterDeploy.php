<?php

/**
 * @file
 * A pantheon quicksilver script to run when code has been deployed via the UI.
 *
 * See https://docs.pantheon.io/guides/quicksilver.
 */

require_once 'inc/copyGinCss.php';

$drushCommands = [
  'deploy' => [
    '--yes',
  ],
];

// Iterate over each drush command and execute it on the system.
foreach ($drushCommands as $subCommand => $args) {
  $command = "drush {$subCommand} " . implode(' ', $args);
  echo sprintf('Running %s...', $command);
  passthru($command);
}

echo 'All drush commands have been executed!';

exit(0);
