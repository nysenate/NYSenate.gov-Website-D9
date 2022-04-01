<?php

namespace Drupal\hacked\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\hacked\hackedProject;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile for Hacked! module.
 */
class HackedCommands extends DrushCommands {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * HackedCommands constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend, TranslationInterface $string_translation) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->cacheBackend = $cache_backend;
    $this->stringTranslation = $string_translation;
  }

  /**
   * List all projects that can be analysed by Hacked!
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option force-rebuild
   *   Rebuild the Hacked! report instead of getting a cached version.
   *
   * @command hacked:list-projects
   * @aliases hlp,hacked-list-projects
   *
   * @field-labels
   *   title: Title
   *   name: Name
   *   version: Version
   *   status: Status
   *   changed: Changed
   *   deleted: Deleted
   * @default-fields title,name,version,status,changed,deleted
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   The list of projects arranged for table display
   *
   * @validate-module-enabled hacked
   */
  public function listProjects(array $options = ['force-rebuild' => FALSE]) {
    // Go get the data.
    $this->moduleHandler->loadInclude('update', 'inc', 'update.report');
    $rows = [];
    if (($available = update_get_available(TRUE))) {
      $this->moduleHandler->loadInclude('update', 'inc', 'update.compare');
      $data = update_calculate_project_data($available);
      $force_rebuild = $options['force-rebuild'];
      $projects = $this->calculateProjectData($data, $force_rebuild);

      // Now print the data using drush.
      $rows = [];
      foreach ($projects as $project) {
        $row = [
          'title' => $project['title'],
          'name' => $project['name'],
          'version' => $project['existing_version'],
        ];

        // Now add the status:
        switch ($project['status']) {
          case HACKED_STATUS_UNHACKED:
            $row['status'] = $this->t('Unchanged');
            break;

          case HACKED_STATUS_HACKED:
            $row['status'] = $this->t('Changed');
            break;

          case HACKED_STATUS_UNCHECKED:
          default:
            $row['status'] = $this->t('Unchecked');
            break;
        }

        $row['changed'] = $project['counts']['different'];
        $row['deleted'] = $project['counts']['missing'];

        $rows[] = $row;
      }

    }

    return new RowsOfFields($rows);
  }

  /**
   * Since the pm-updatecode command was deprecated, there is nothing to lock.
   *
   * @command hacked:lock-modified
   * @aliases hacked-lock-modified
   * @validate-module-enabled hacked
   * @hidden
   * @obsolete
   */
  public function lockModified() {}

  /**
   * Show the Hacked! report about a specific project.
   *
   * @param string $machine_name
   *   The machine name of the project to report on.
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option include-unchanged
   *   Show the files that are unchanged too.
   * @command hacked:details
   * @aliases hd,hacked-details
   *
   * @validate-module-enabled hacked
   *
   * @field-labels
   *   status: Status
   *   file: File
   * @default-fields status,file
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   Report data arranged for table display.
   */
  public function details($machine_name, array $options = ['include-unchanged' => FALSE]) {
    $project = new hackedProject($machine_name);
    $report = $project->compute_details();

    $this->output()->writeln((string) $this->t('Details for project: @name', ['@name' => $project->title()]));
    $this->output()->writeln((string) $this->t('Total files: @total_files, files changed: @changed_files, deleted files: @deleted_files', [
      '@total_files'   => count($report['files']),
      '@changed_files' => $report['counts']['different'],
      '@deleted_files' => $report['counts']['missing'],
    ]));
    $this->output()->writeln('');

    $this->output()->writeln((string) $this->t('Detailed results:'));
    // Sort the results.
    arsort($report['files']);

    $rows = [];
    $show_unchanged = $options['include-unchanged'];
    foreach ($report['files'] as $file => $status) {
      if (!$show_unchanged && $status == HACKED_STATUS_UNHACKED) {
        continue;
      }
      $row = [];

      // Now add the status.
      switch ($status) {
        case HACKED_STATUS_UNHACKED:
          $row['status'] = $this->t('Unchanged');
          break;

        case HACKED_STATUS_HACKED:
          $row['status'] = $this->t('Changed');
          break;

        case HACKED_STATUS_DELETED:
          $row['status'] = $this->t('Deleted');
          break;

        case HACKED_STATUS_UNCHECKED:
        default:
          $row['status'] = $this->t('Unchecked');
          break;
      }

      $row['file'] = $file;
      $rows[] = $row;
    }

    return new RowsOfFields($rows);
  }

  /**
   * Validates the hacked:details command.
   *
   * @hook validate hacked:details
   */
  public function validateDetailsCommand(CommandData $command_data) {
    $machine_name = $command_data->arguments()['machine_name'];
    $this->validateProjectName($machine_name);
  }

  /**
   * Checks that machine_name is valid Drupal project.
   *
   * @param string $machine_name
   *   The machine name to be checked.
   *
   * @throw \Exception
   *   For empty for invalid project machine names.
   */
  protected function validateProjectName($machine_name) {
    $project = new hackedProject($machine_name);
    $project->identify_project();
    if (!$project->project_identified) {
      throw new \Exception((string) $this->t('Could not find project: @project', ['@project' => $machine_name]));
    }
  }

  /**
   * Output a unified diff of the project specified.
   *
   * You may specify the --include-unchanged option to show unchanged files too,
   * otherwise just the changed and deleted files are shown.
   *
   * @param string $machine_name
   *   The machine name of the project to report on.
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option diff-options
   *   Command line options to pass through to the diff command.
   *
   * @command hacked:diff
   * @aliases hacked-diff
   *
   * @validate-module-enabled hacked
   */
  public function diff($machine_name, array $options = ['diff-options' => NULL]) {
    $project = new hackedProject($machine_name);

    $local_location = $project->file_get_location('local', '');
    $clean_location = $project->file_get_location('remote', '');

    // If the hasher is our ignore line endings one, then ignore line endings.
    $hasher = $this->configFactory->get('hacked.settings')->get('selected_file_hasher');
    $hasher = is_null($hasher) ? HACKED_DEFAULT_FILE_HASHER : $hasher;
    if ($hasher == 'hacked_ignore_line_endings') {
      $default_options = '-uprb';
    }
    else {
      $default_options = '-upr';
    }

    $diff_options = isset($options['diff-options']) ? $options['diff-options'] : $default_options;
    drush_shell_exec("diff $diff_options $clean_location $local_location");

    $lines = drush_shell_exec_output();
    $local_location_trim = dirname($local_location . '/dummy.file') . '/';
    $clean_location_trim = dirname($clean_location . '/dummy.file') . '/';
    foreach ($lines as $line) {
      if (strpos($line, '+++') === 0) {
        $line = str_replace($local_location_trim, '', $line);
      }
      if (strpos($line, '---') === 0) {
        $line = str_replace($clean_location_trim, '', $line);
      }
      if (strpos($line, 'diff -upr') === 0) {
        $line = str_replace($clean_location_trim, 'a/', $line);
        $line = str_replace($local_location_trim, 'b/', $line);
      }

      $this->output()->writeln($line);
    }
  }

  /**
   * Validates the hacked:diff command.
   *
   * @hook validate hacked:diff
   */
  public function validateDiffCommand(CommandData $command_data) {
    $machine_name = $command_data->arguments()['machine_name'];
    $this->validateProjectName($machine_name);
  }

  /**
   * Compute the report data for hacked.
   *
   * WARNING: This function can invoke a batch process and end your current
   * page. So you'll want to be very careful if you call this!
   *
   * @param array $projects
   *   An array of Drupal projects.
   * @param bool|false $force
   *   If TRUE, force rebuild of project data.
   *
   * @return array
   *   The report data.
   */
  public function calculateProjectData(array $projects, $force = FALSE) {
    // Try to get the report form cache if we can.
    $cache = $this->cacheBackend->get('hacked:drush:full-report');
    if (!empty($cache->data) && !$force) {
      return $cache->data;
    }

    $op_callback = [$this, 'buildReportBatch'];
    $finished_callback = [$this, 'buildReportBatchFinished'];
    $title = $this->t('Building report');
    // If Drupal 8.6+, use BatchBuilder class.
    if (class_exists('\Drupal\Core\Batch\BatchBuilder')) {
      $batch_builder = (new BatchBuilder())
        ->setTitle($title)
        ->setFinishCallback($finished_callback);

      foreach ($projects as $project) {
        $batch_builder->addOperation($op_callback, [$project['name']]);
      }

      $batch = $batch_builder->toArray();
    }
    else {
      // Enter a batch to build the report.
      $operations = [];
      foreach ($projects as $project) {
        $operations[] = [
          $op_callback,
          [$project['name']],
        ];
      }

      $batch = [
        'operations' => $operations,
        'finished'   => $finished_callback,
        'title'      => $title,
      ];
    }

    $this->output()->writeln((string) $this->t('Rebuilding Hacked! report'));
    batch_set($batch);
    $batch = &batch_get();
    $batch['progressive'] = FALSE;
    drush_backend_batch_process();
    $this->output()->writeln((string) $this->t('Done.'));

    // Now we can get the data from the cache.
    $cache = $this->cacheBackend->get('hacked:drush:full-report');
    if (!empty($cache->data)) {
      return $cache->data;
    }

    return [];
  }

  /**
   * Batch callback to build Hacked! report.
   */
  public function buildReportBatch($project_name, &$context) {
    $this->moduleHandler->loadInclude('hacked', 'inc', 'hacked.report');
    hacked_build_report_batch($project_name, $context);
  }

  /**
   * Completion callback for the report batch.
   *
   * @param bool $success
   *   Boolean value of batch success.
   * @param array $results
   *   An array of batch results.
   */
  public function buildReportBatchFinished($success, array $results) {
    if ($success) {
      // Sort the results.
      usort($results['report'], '_hacked_project_report_sort_by_status');
      // Store them.
      $this->cacheBackend
        ->set('hacked:drush:full-report', $results['report'], strtotime('+1 day'));
    }
  }

}
