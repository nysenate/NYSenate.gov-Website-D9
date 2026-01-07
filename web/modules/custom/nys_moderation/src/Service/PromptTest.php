<?php

namespace Drupal\nys_moderation\Service;

use Drupal\ai_automators\Entity\AiAutomator;
use Drupal\ai_automators\PluginInterfaces\AiAutomatorTypeInterface;
use Drupal\ai_automators\PluginManager\AiAutomatorTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\nys_moderation\Exception\TestRunInitFailedException;
use Drupal\nys_moderation\ModerationTestLogInterface;
use Drupal\nys_moderation\ModerationTestSetInterface;

/**
 * Runs a moderation test set through its automator.
 */
class PromptTest {

  /**
   * Drupal's Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The currently running test.
   *
   * @var \Drupal\nys_moderation\ModerationTestSetInterface
   */
  protected ModerationTestSetInterface $currentTest;

  /**
   * The log thread for this run.
   *
   * @var \Drupal\nys_moderation\ModerationTestLogInterface
   */
  protected ModerationTestLogInterface $currentLog;

  /**
   * AI Module's Automator Type manager.
   *
   * @var \Drupal\ai_automators\PluginManager\AiAutomatorTypeManager
   */
  protected AiAutomatorTypeManager $aiAutomatorTypeManager;

  /**
   * The AiAutomator used by the current test set.
   *
   * @var \Drupal\ai_automators\Entity\AiAutomator
   */
  protected AiAutomator $automator;

  /**
   * The automator's configuration.
   *
   * @var array
   */
  protected array $aiConfig = [];

  /**
   * The display name for the current test run.
   *
   * @var string
   */
  protected string $runLabel;

  /**
   * The optional tags for a new test run.
   *
   * @var string
   */
  protected string $tags;

  /**
   * A Drupal logging facility.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected LoggerChannel $sysLogger;

  /**
   * The AI plugin used to send the prompts.
   *
   * Should be an instance of
   * ai_automators\PluginInterfaces\AiAutomatorTypeInterface after init()
   */
  protected AiAutomatorTypeInterface $plugin;

  /**
   * Drupal's Current User service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, AccountProxyInterface $currentUser, AiAutomatorTypeManager $aiAutomatorTypeManager, LoggerChannel $loggerChannel) {
    $this->entityTypeManager = $entityTypeManager;
    $this->aiAutomatorTypeManager = $aiAutomatorTypeManager;
    $this->sysLogger = $loggerChannel;
    $this->currentUser = $currentUser;
  }

  /**
   * Initializes a new test run.
   */
  public function init(ModerationTestSetInterface $testSet, string $run_label = '', string $tags = ''): bool {
    try {
      $this->currentTest = $testSet;

      // Grab the AI module integrations.
      $this->automator = $this->currentTest->getAutomator();
      $this->aiConfig = $this->currentTest->getAutomatorConfig();
      $plugin = $this->aiAutomatorTypeManager->createInstance($this->automator->get('rule'), $this->aiConfig);
      assert($plugin instanceof AiAutomatorTypeInterface);
      $this->plugin = $plugin;

      $this->runLabel = $run_label ?: ($testSet->label() . ' ' . date("Y-m-d H:i:s"));
      $this->tags = $tags;
      $this->currentLog = $this->generateNewLog();
    }
    catch (\Throwable $e) {
      $this->sysLogger->error('Failed to initialize @id', [
        '@id' => $testSet->id(),
        '@label' => $this->runLabel,
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Runs a single test set, with an optional custom label or tag text.
   */
  public function run(ModerationTestSetInterface $testSet, string $run_label = '', string $tags = ''): bool {
    if (!$this->init($testSet, $run_label, $tags)) {
      return FALSE;
    }

    // Set some references; load the entities.
    $field_name = $this->automator->get('field_name');
    $entities = $this->loadTargets();

    /** @var \Drupal\Core\Entity\ContentEntityBase $node */
    foreach ($entities as $entity) {
      // Execute the plugin.
      // @todo Is this really necessary?  Should always be the same...?
      $field_def = $entity->getFieldDefinition($field_name);
      $result = $this->plugin->generate($entity, $field_def, $this->aiConfig);
      // @todo This is limited to current return format.
      // This will need to be revisited if that ever changes.
      $passed = $result[0]['state'] == 'published';

      // Log the results.
      $log = [
        'log_id' => $this->currentLog->id(),
        'entity_type' => $entity->getEntityTypeId(),
        'entity_id' => $entity->id(),
        'created' => time(),
        'response' => serialize($result),
        'passed' => $passed,
        'expected' => !($entity->get('field_flag')->value ?? FALSE),
      ];
      $this->entityTypeManager->getStorage('moderation_test_log_item')
        ->create($log)
        ->save();
    }
    return TRUE;
  }

  /**
   * Load a test set by name and run it.
   *
   * The label and tags will be set to default values.
   */
  public function runByName(string $name): bool {
    return FALSE;
  }

  /**
   * Loads the target entities assigned to this test set.
   *
   * @return array
   *   In the form ['entity_id' => entity_object], as with loadMultiple().
   *   Returns an empty array on exception.
   */
  protected function loadTargets(): array {
    $ts = $this->currentTest;
    try {
      $entity_type = $ts->get('entity_type') ?? 'node';
      $entities = $ts->get('entities') ?? [];
      $storage = $this->entityTypeManager->getStorage($entity_type);
      $ret = $storage->loadMultiple($entities);
    }
    catch (\Throwable $e) {
      $this->sysLogger->error('Failed to load test targets for @id', [
        '@id' => $ts->id(),
        '@msg' => $e->getMessage(),
      ]);
      $ret = [];
    }
    return $ret;
  }

  /**
   * Generates a new test log thread for the current run.
   *
   * @throws \Drupal\nys_moderation\Exception\TestRunInitFailedException
   */
  protected function generateNewLog(): ModerationTestLogInterface {
    try {
      /** @var \Drupal\nys_moderation\ModerationTestLogInterface $ret */
      $ret = $this->entityTypeManager->getStorage('moderation_test_log')
        ->create([
          'created' => time(),
          'name' => $this->runLabel,
          'uid' => $this->currentUser->id(),
          'prompt' => $this->automator->get('prompt'),
          'tags' => $this->tags ?? '',
        ]);
      $ret->save();
    }
    catch (\Throwable $e) {
      $this->sysLogger->error("Failed to initialize log for test run", ['@msg' => $e->getMessage()]);
      throw new TestRunInitFailedException($e->getMessage(), $e->getCode(), $e);
    }
    return $ret;
  }

}
