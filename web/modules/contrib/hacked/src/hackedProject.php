<?php

namespace Drupal\hacked;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Encapsulates a Hacked! project.
 *
 * This class should handle all the complexity for you, and so you should be able to do:
 * <code>
 * $project = hackedProject('context');
 * $project->compute_differences();
 * </code>
 *
 * Which is quite nice I think.
 */
class hackedProject {
  use StringTranslationTrait;

  var $name = '';

  var $project_info = array();

  var $remote_files_downloader;

  /* @var hackedFileGroup $remote_files */
  var $remote_files;

  /* @var hackedFileGroup $local_files */
  var $local_files;

  var $project_type = '';
  var $existing_version = '';

  var $result = array();

  var $project_identified = FALSE;
  var $remote_downloaded = FALSE;
  var $remote_hashed = FALSE;
  var $local_hashed = FALSE;

  /**
   * Constructor.
   */
  function __construct($name) {
    // Identify the project.
    $this->name = $name;
    $this->identify_project();

    // Choose an appropriate downloader.
    if ($this->isDevVersion()) {
      $this->remote_files_downloader = new hackedProjectWebDevDownloader($this);
    }
    else {
      $this->remote_files_downloader = new hackedProjectWebFilesDownloader($this);
    }
  }

  /**
   * Get the Human readable title of this project.
   */
  function title() {
    $this->identify_project();
    return isset($this->project_info['title']) ? $this->project_info['title'] : $this->name;
  }

  /**
   * Identify the project from the name we've been created with.
   *
   * We leverage the update (status) module to get the data we require about
   * projects. We just pull the information in, and make descisions about this
   * project being from CVS or not.
   */
  function identify_project() {
    // Only do this once, no matter how many times we're called.
    if (!empty($this->project_identified)) {
      return;
    }

    // Fetch the required data from the update (status) module.
    // TODO: clean this up.
    $available = update_get_available(TRUE);
    $data = update_calculate_project_data($available);
    $releases = \Drupal::keyValueExpirable('update_available_releases')
      ->getAll();

    foreach ($data as $key => $project) {
      if ($key == $this->name) {
        $this->project_info = $project;
        if (!isset($this->project_info['releases']) || !is_array($this->project_info['releases'])) {
          $this->project_info['releases'] = array();
        }
        if (isset($releases[$key]['releases']) && is_array($releases[$key]['releases'])) {
          $this->project_info['releases'] += $releases[$key]['releases'];
        }

        // Add in the additional info that update module strips out.
        // This is a really naff way of doing this, but update (status) module
        // ripped out a lot of useful stuff in issue:
        // http://drupal.org/node/669554

        $this->project_identified = TRUE;
        $this->existing_version = $this->project_info['existing_version'];
        $this->project_type = $this->project_info['project_type'];
        break;
      }
    }

    // Logging.
    if (!$this->project_identified) {
      $message = $this->t('Could not identify project: @name', array('@name' => $this->name));
      \Drupal::logger('hacked')->warning($message->render());
    }
  }

  /**
   * Determines if the project is a development version or has an explicit release.
   *
   * @return boolean
   *   TRUE if the project is a dev release; FALSE otherwise.
   */
  function isDevVersion() {
    // Grab the version string.
    $version = $this->existing_version;

    // Assume we have a dev version if the string ends with "-dev".
    return (strlen($version) < 4 || substr_compare($version, '-dev', -4, 4) !== 0) ? FALSE : TRUE;
  }

  /**
   * Downloads the remote project to be hashed later.
   */
  function download_remote_project() {
    // Only do this once, no matter how many times we're called.
    if (!empty($this->remote_downloaded)) {
      return;
    }

    $this->identify_project();
    $this->remote_downloaded = (bool) $this->remote_files_downloader->download();

    // Logging.
    if (!$this->remote_downloaded) {
      $message = $this->t('Could not download project: @title', array('@title' => $this->title()));
      \Drupal::logger('hacked')->error($message->render());
    }
  }

  /**
   * Hashes the remote project downloaded earlier.
   */
  function hash_remote_project() {
    // Only do this once, no matter how many times we're called.
    if (!empty($this->remote_hashed)) {
      return;
    }

    // Ensure that the remote project has actually been downloaded.
    $this->download_remote_project();

    // Set up the remote file group.
    $base_path = $this->remote_files_downloader->get_final_destination();
    $this->remote_files = hackedFileGroup::fromDirectory($base_path);
    $this->remote_files->compute_hashes();

    $this->remote_hashed = !empty($this->remote_files->files);

    // Logging.
    if (!$this->remote_hashed) {
      $message = $this->t('Could not hash remote project: @title', array('@title' => $this->title()));
      \Drupal::logger('hacked')->error($message->render());
    }
  }

  /**
   * Locate the base directory of the local project.
   */
  function locate_local_project() {
    // we need a remote project to do this :(
    $this->hash_remote_project();

    // Do we have at least some modules to check for:
    if (!is_array($this->project_info['includes']) || !count($this->project_info['includes'])) {
      return FALSE;
    }

    // If this project is drupal it, we need to handle it specially
    if ($this->project_type != 'core') {
      $includes = array_keys($this->project_info['includes']);
      $include = array_shift($includes);
      $include_type = $this->project_info['project_type'];
    }
    else {
      // Just use the system module to find where we've installed drupal
      $include = 'system';
      $include_type = 'module';
    }

    //$include = 'image_captcha';

    $path = drupal_get_path($include_type, $include);

    // Now we need to find the path of the info file in the downloaded package:
    $temp = '';
    foreach ($this->remote_files->files as $file) {
      if (preg_match('@(^|.*/)' . $include . '.info.yml$@', $file)) {
        $temp = $file;
        break;
      }
    }

    // How many '/' were in that path:
    $slash_count = substr_count($temp, '/');
    $back_track = str_repeat('/..', $slash_count);

    return realpath($path . $back_track);
  }

  /**
   * Hash the local version of the project.
   */
  function hash_local_project() {
    // Only do this once, no matter how many times we're called.
    if (!empty($this->local_hashed)) {
      return;
    }

    $location = $this->locate_local_project();

    $this->local_files = hackedFileGroup::fromList($location, $this->remote_files->files);
    $this->local_files->compute_hashes();

    $this->local_hashed = !empty($this->local_files->files);

    // Logging.
    if (!$this->local_hashed) {
      $message = $this->t('Could not hash local project: @title', ['@title' => $this->title()]);
      \Drupal::logger('hacked')->error($message->render());
    }
  }

  /**
   * Compute the differences between our version and the canonical version of the project.
   */
  function compute_differences() {
    // Make sure we've hashed both remote and local files.
    $this->hash_remote_project();
    $this->hash_local_project();

    $results = [
      'same'          => [],
      'different'     => [],
      'missing'       => [],
      'access_denied' => [],
    ];

    // Now compare the two file groups.
    foreach ($this->remote_files->files as $file) {
      if ($this->remote_files->files_hashes[$file] == $this->local_files->files_hashes[$file]) {
        $results['same'][] = $file;
      }
      elseif (!$this->local_files->file_exists($file)) {
        $results['missing'][] = $file;
      }
      elseif (!$this->local_files->is_readable($file)) {
        $results['access_denied'][] = $file;
      }
      else {
        $results['different'][] = $file;
      }
    }

    $this->result = $results;
  }

  /**
   * Return a nice report, a simple overview of the status of this project.
   */
  function compute_report() {
    // Ensure we know the differences.
    $this->compute_differences();

    // Do some counting

    $report = [
      'project_name' => $this->name,
      'status'       => HACKED_STATUS_UNCHECKED,
      'counts'       => [
        'same'          => count($this->result['same']),
        'different'     => count($this->result['different']),
        'missing'       => count($this->result['missing']),
        'access_denied' => count($this->result['access_denied']),
      ],
      'title'        => $this->title(),
    ];

    // Add more details into the report result (if we can).
    $details = array(
      'link',
      'name',
      'existing_version',
      'install_type',
      'datestamp',
      'project_type',
      'includes',
    );
    foreach ($details as $item) {
      if (isset($this->project_info[$item])) {
        $report[$item] = $this->project_info[$item];
      }
    }


    if ($report['counts']['access_denied'] > 0) {
      $report['status'] = HACKED_STATUS_PERMISSION_DENIED;
    }
    elseif ($report['counts']['missing'] > 0) {
      $report['status'] = HACKED_STATUS_HACKED;
    }
    elseif ($report['counts']['different'] > 0) {
      $report['status'] = HACKED_STATUS_HACKED;
    }
    elseif ($report['counts']['same'] > 0) {
      $report['status'] = HACKED_STATUS_UNHACKED;
    }

    return $report;
  }

  /**
   * Return a nice detailed report.
   */
  function compute_details() {
    // Ensure we know the differences.
    $report = $this->compute_report();

    $report['files'] = array();

    // Add extra details about every file.
    $states = array(
      'access_denied' => HACKED_STATUS_PERMISSION_DENIED,
      'missing'       => HACKED_STATUS_DELETED,
      'different'     => HACKED_STATUS_HACKED,
      'same'          => HACKED_STATUS_UNHACKED,
    );

    foreach ($states as $state => $status) {
      foreach ($this->result[$state] as $file) {
        $report['files'][$file] = $status;
        $report['diffable'][$file] = $this->file_is_diffable($file);
      }
    }

    return $report;
  }

  function file_is_diffable($file) {
    $this->hash_remote_project();
    $this->hash_local_project();
    return $this->remote_files->is_not_binary($file) && $this->local_files->is_not_binary($file);
  }

  function file_get_location($storage = 'local', $file) {
    switch ($storage) {
      case 'remote':
        $this->download_remote_project();
        return $this->remote_files->file_get_location($file);
      case 'local':
        $this->hash_local_project();
        return $this->local_files->file_get_location($file);
    }
    return FALSE;
  }

}
