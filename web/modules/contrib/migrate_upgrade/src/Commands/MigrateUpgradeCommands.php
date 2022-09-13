<?php

namespace Drupal\migrate_upgrade\Commands;

use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\migrate_upgrade\MigrateUpgradeDrushRunner;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;
use Drupal\Core\State\StateInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Migrate Upgrade drush commands.
 */
class MigrateUpgradeCommands extends DrushCommands {

  /**
   * State service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * MigrateUpgradeCommands constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   State service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger.factory service.
   */
  public function __construct(StateInterface $state, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct();
    $this->state = $state;
    $this->logger = $logger_factory->get('drush');
  }

  /**
   * Perform one or more upgrade processes.
   *
   * @command migrate:upgrade
   *
   * @usage drush migrate-upgrade --legacy-db-url='mysql://root:pass@127.0.0.1/d6'
   *   Upgrade a Drupal 6 database to Drupal 8
   * @usage drush migrate-upgrade --legacy-db-key='drupal_7'
   *   Upgrade Drupal 7 database where the connection to Drupal 7 has already
   * been created in settings.php ($databases['drupal_7'])
   * @usage drush migrate-upgrade --legacy-db-url='mysql://root:pass@127.0.0.1/d7' --configure-only --migration-prefix=d7_custom_ --legacy-root=https://www.example.com
   *   Generate migrations for a custom migration from Drupal 7 to Drupal 8
   *
   * @validate-module-enabled migrate_upgrade
   *
   * @field-labels
   *   original: Original migrations
   *   generated: Generated migrations
   *
   * @aliases migrate-upgrade, mup
   *
   * @default-fields generated
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   The formatted results of the command.
   *
   * @throws \Exception
   *   When an error occurs.
   */
  public function upgrade(array $options = []) {
    $runner = new MigrateUpgradeDrushRunner($this->logger, $options);

    $runner->configure();
    if ($options['configure-only']) {
      $result = new RowsOfFields($runner->export());
    }
    else {
      $result = new RowsOfFields($runner->import());
      $this->state->set('migrate_drupal_ui.performed', \Drupal::time()->getRequestTime());
    }
    // Remove the global database state.
    $this->state->delete('migrate.fallback_state_key');
    return $result;
  }

  /**
   * Validation callback for password.
   *
   * @hook validate migrate:upgrade
   */
  public function validatePassword(CommandData $commandData) {
    $input = $commandData->input();
    $db_url = $input->getOption('legacy-db-url');
    $db_key = $input->getOption('legacy-db-key');

    if (!$db_url && !$db_key) {
      throw new \Exception('You must provide either a --legacy-db-url or --legacy-db-key.');
    }
  }

  /**
   * Legacy database url option.
   *
   * @hook option migrate:upgrade
   */
  public function legacyDatabaseUrl(Command $command, AnnotationData $annotationData) {
    $command->addOption(
      'legacy-db-url',
      '',
      InputOption::VALUE_OPTIONAL,
      'A Drupal 6 style database URL. Required if you do not set legacy-db-key.'
    );
  }

  /**
   * Legacy database key option.
   *
   * @hook option migrate:upgrade
   */
  public function legacyDatabaseKey(Command $command, AnnotationData $annotationData) {
    $command->addOption(
      'legacy-db-key',
      '',
      InputOption::VALUE_OPTIONAL,
      'A database connection key from settings.php. Use as an alternative to legacy-db-url.'
    );
  }

  /**
   * Legacy database prefix option.
   *
   * @hook option migrate:upgrade
   */
  public function legacyDatabasePrefix(Command $command, AnnotationData $annotationData) {
    $command->addOption(
      'legacy-db-prefix',
      '',
      InputOption::VALUE_OPTIONAL,
      'Database prefix of the legacy Drupal installation.'
    );
  }

  /**
   * Legacy file system root path option.
   *
   * @hook option migrate:upgrade
   */
  public function legacyRoot(Command $command, AnnotationData $annotationData) {
    $command->addOption(
      'legacy-root',
      '',
      InputOption::VALUE_OPTIONAL,
      'For files migrations. Site web address or file system root path (if files are local) of the legacy Drupal installation.'
    );
  }

  /**
   * Configure only option.
   *
   * @hook option migrate:upgrade
   */
  public function configureOnly(Command $command, AnnotationData $annotationData) {
    $command->addOption(
      'configure-only',
      '',
      InputOption::VALUE_NONE,
      'Set up the appropriate upgrade processes as migrate_plus config entities but do not perform them.'
    );
  }

  /**
   * Prefix all migrations.
   *
   * @hook option migrate:upgrade
   */
  public function migrationPrefix(Command $command, AnnotationData $annotationData) {
    $command->addOption(
      'migration-prefix',
      '',
      InputOption::VALUE_OPTIONAL,
      'With configure-only, a prefix to apply to generated migration ids.',
      'upgrade_'
    );
  }

  /**
   * Alter field labels for non configure-only.
   *
   * @hook init migrate:upgrade
   */
  public function initUpgrade(InputInterface $input, AnnotationData $annotationData) {
    // If configure option isn't specified, then rename the field label more
    // appropriately.
    if (!$input->getOption('configure-only')) {
      $annotationData->set('field-labels', 'original: Original migrations' . PHP_EOL . 'generated: Executed migrations');
    }
  }

  /**
   * Rolls back and removes upgrade migrations.
   *
   * @throws \Drush\Exceptions\UserAbortException
   *   If user chose to not perform the rollback.
   *
   * @command migrate:upgrade-rollback
   * @usage drush migrate-upgrade-rollback
   *   Rolls back a previously-run upgrade. It will not rollback migrations
   *   exported as migrate_plus config entities.
   * @validate-module-enabled migrate_upgrade
   * @aliases migrate-upgrade-rollback, mupr
   */
  public function upgradeRollback() {
    if ($date_performed = $this->state->get('migrate_drupal_ui.performed')) {
      if ($this->io()->confirm(dt('All migrations will be rolled back. Are you sure?'))) {
        $runner = new MigrateUpgradeDrushRunner($this->logger);

        $this->logger()->notice(dt('Rolling back the upgrades performed @date',
          ['@date' => \Drupal::service('date.formatter')->format($date_performed)]));
        $runner->rollback();
        $this->state->delete('migrate_drupal_ui.performed');
        $this->logger()->notice(dt('Rolled back upgrades'));
      }
      else {
        throw new UserAbortException();
      }
    }
    else {
      $this->logger()->warning(dt('No upgrade operation has been performed.'));
    }
  }

}
