<?php

namespace Drupal\fancy_file_delete;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\State\StateInterface;
use Drupal\fancy_file_delete\Entity\UnmanagedFiles;

/**
 * Class UnmanagedFilesService.
 */
class UnmanagedFilesService {

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new UnmanagedFilesService.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   * @param \Drupal\Core\Database\Connection $database
   *   The current database connection.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   */
  public function __construct(FileSystemInterface $file_system, Connection $database, StateInterface $state) {
    $this->fileSystem = $file_system;
    $this->database = $database;
    $this->state = $state;
  }

  /**
   * Updates the view and populates the unmanaged files table.
   */
  public function updateView() {
    // Get all files from default standard public & private directories.
    $directories = $this->getChosenDirs();
    $files = $this->getFiles($directories);

    // Remove files from the batch that are not in our latest check.
    if (count($files)) {
      $this->database->delete('unmanaged_files')
        ->condition('path', $files,'NOT IN')
        ->execute();
    }
    else {
      $this->database->delete('unmanaged_files')->execute();
    }

    // Go through and add this to the batch.
    if (count($files) > 0) {
      // I changed this to use array chunk & db query for performance.
      // see issue: https://www.drupal.org/node/2637028
      $files_chunk = array_chunk($files, ceil(count($files) / 4), TRUE);

      foreach ($files_chunk as $filez) {
        $result = $this->database->select('unmanaged_files', 'uf')
          ->fields('uf', ['path'])
          ->condition('path', $filez,'NOT IN')
          ->execute()
          ->fetchAll();

        // Check if this is a first run.
        if (count($result) === 0) {
          $new = TRUE;
        }
        else {
          $umsplit[] = $result;
          $new = FALSE;
        }
      }
      // Insert if new.
      if ($new) {
        foreach ($files_chunk as $chunk) {
          foreach ($chunk as $fpath) {
            $new_files[] = $fpath;
          }
        }

        if (isset($new_files)) {
          // Insert records
          foreach ($new_files as $value) {
            $un_file = UnmanagedFiles::create([
              'path' => $value,
            ]);
            $un_file->save();
          }

        }
      }
      else {
        $um = array_merge(...$umsplit);

        // Go in and check it and set it as an array to check.
        $um_check = [];
        foreach ($um as $res) {
          $um_check[] = $res->path;
        }
        // Again check the difference, only want ones not in the table.
        $um_final = array_diff($files, $um_check);

        if (count($um_final) > 0) {
          // Insert records
          foreach ($um_final as $value) {
            $un_file = UnmanagedFiles::create([
              'path' => $value,
            ]);
            $un_file->save();
          }
        }
      }
    }
  }

  /**
   * Set a list of chosen directories from which to delete unmanaged files from.
   *
   * @param array $chosen_dirs
   */
  public function setChosenDirs(array $chosen_dirs) {
    // Only include directories that currently exist.
    $all_dirs = $this->getDirs();
    $chosen_dirs = array_intersect($all_dirs, $chosen_dirs);
    natsort($chosen_dirs);
    $this->state->set('fancy_file_delete_unmanaged_chosen_dirs', array_values($chosen_dirs));
  }

  /**
   * Gets a list of chosen directories to delete unmanaged files from.
   * Defaults to all directories if no choice was previously made.
   *
   * @return mixed array
   */
  public function getChosenDirs() {
    $all_dirs = $this->getDirs();
    $chosen_dirs = $this->state->get('fancy_file_delete_unmanaged_chosen_dirs', FALSE);
    if ($chosen_dirs === FALSE) {
      // Return only public on first pass for performance.
      // see issue: https://www.drupal.org/node/2637028
      return ['public://'];
    }

    // Only include directories that currently exist.
    $chosen = array_intersect($all_dirs, $chosen_dirs);
    natsort($chosen);
    return array_values($chosen);
  }

  /**
   * Answer a list of directories to include/exclude.
   */
  public function getDirs() {
    $public_dir = $this->state->get('file_public_path', 'sites/default/files');
    $private_dir = $this->fileSystem->realpath("private://");

    // If the private path is a sub-path of the public path, exclude it.
    $exclude_paths = [];
    if (!empty($private_dir) && strpos($private_dir, $public_dir) === FALSE) {
      $exclude_paths[] = $private_dir;
    }

    // Get all files from default standard file dir.
    $directories = ['public://'];
    $directories = array_merge($directories, $this->getSubDirectories($public_dir, 'public://', $exclude_paths));

    // Get all files from the private file directory.
    if (!empty($private_dir)) {
      // If the public path is a sub-path of the private path, exclude it.
      $exclude_paths = [];
      if (!empty($public_dir) && strpos($public_dir, $private_dir) === FALSE) {
        $exclude_paths[] = $public_dir;
      }
      $directories[] = 'private://';
      $directories = array_merge($directories, $this->getSubDirectories($private_dir, 'private://', $exclude_paths));
    }
    natsort($directories);
    return array_values($directories);
  }

  /**
   * Answer an array of unmanaged files contained in the directories provided.
   *
   * @param array $paths Directory paths e.g. array("public://", "public://media")
   * @return array of file objects.
   */
  protected function getFiles(array $paths) {
    // Get all files from default standard file dir.
    foreach ($paths as $path) {
      $files[] = $this->getFileUris($path);
    }

    $file_check = array_merge(...$files);
    if ($file_check === NULL) {
      $file_check = [];
    }

    // All the files in the file_managed table
    // I changed this to use $this->database->query for performance.
    // see issue: https://www.drupal.org/node/2637028
    $query = $this->database->query('SELECT uri FROM {file_managed}');
    $db_check = [];
    // Set this to a numeric keyed array so we can check this easier.
    foreach ($query->fetchAll() as $result) {
      $db_check[] = $result->uri;
    }

    // Get the files not in the file_managed table.
    return array_diff($file_check, $db_check);
  }

  /**
   * Answer an array of directory paths and URI's.
   *
   * @param string $dir The file-system path of the directory.
   * @param string $uri_prefix The prefix, e.g. 'public://' or 'private://'
   * @param array $exclude_paths File-system paths to exclude from the results.
   * @return array
   */
  protected function getSubDirectories($dir, $uri_prefix, array $exclude_paths = []) {
    $results = [];
    $iterator = new \RecursiveIteratorIterator(
      new FancyFileDeleteDirectoryOnlyRecursiveFilterIterator(
        new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
        $exclude_paths
      ),
      \RecursiveIteratorIterator::SELF_FIRST, \RecursiveIteratorIterator::CATCH_GET_CHILD);

    // Go through each one and add a proper uri.
    foreach ($iterator as $file) {
      $results[] = str_replace($dir . '/', $uri_prefix, $file->getPathname());
    }
    return $results;
  }

  /**
   * Answer an array of file URI's to match against the database.
   *
   * @param string $dir The file-system path of the directory.
   * @return array
   */
  protected function getFileUris($dir) {
    $file_check = [];
    $files = $this->fileSystem->scanDirectory($dir, '(.*?)', ['recurse' => FALSE]);
    // Go through each one and replace this with a proper uri.
    foreach ($files as $file) {
      if (!is_dir($this->fileSystem->realpath($file->uri))) {
        $file_check[] = $file->uri;
      }
    }
    return $file_check;
  }
}
