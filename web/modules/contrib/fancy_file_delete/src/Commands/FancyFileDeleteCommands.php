<?php

namespace Drupal\fancy_file_delete\Commands;

use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;
use Drupal\fancy_file_delete\FancyFileDeleteBatch;

/**
 * Class FancyFileDeleteCommands
 */
class FancyFileDeleteCommands extends DrushCommands {

  /**
   * The Batch Service.
   *
   * @var \Drupal\fancy_file_delete\FancyFileDeleteBatch
   */
  protected $batch;

  /**
   * Constructs a new FancyFileDeleteCommands.
   *
   * @param \Drupal\fancy_file_delete\FancyFileDeleteBatch
   *   The Batch Service.
   */
  public function __construct(FancyFileDeleteBatch $batch) {
    parent::__construct();
    $this->batch = $batch;
  }

  /**
   * Deletes any number of files by fid or path.
   *
   * @param $file_list
   *   A comma separate list of file ID's OR.
   *   relative paths to any files you wish to delete.
   * @param array $options
   *   An associative array of options whose values come from cli.
   * @option force
   *   Forcefully remove the file, even if it is still being referenced.
   *
   * @command fancy:file-delete
   * @aliases ffd,fancy-file-delete
   */
  public function fileDelete($file_list, array $options = ['force' => FALSE]) {

    // Prompt user for confirmation.
    $confirm = $this->io()->confirm('WARNING! Are you sure you want to delete these files?');
    if (!$confirm) {
      throw new UserAbortException();
    }

    // Initialize our batch operations.
    $files = explode(',', $file_list);

    $values = [];
    foreach ($files as $file) {
      $values[] = $file;
    }

    $this->batch->setBatch($values, $options['force'], FALSE);
  }
}
