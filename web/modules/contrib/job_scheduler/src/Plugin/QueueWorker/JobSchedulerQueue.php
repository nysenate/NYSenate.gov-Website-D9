<?php

namespace Drupal\job_scheduler\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Utility\Error;
use Drupal\job_scheduler\Entity\JobSchedule;

/**
 * Providing worker to reschedule the job or take care of cleanup.
 *
 * Note that as we run the execute() action, the job won't be queued again this
 * time.
 *
 * @QueueWorker(
 *   id = "job_scheduler_queue",
 *   title = @Translation("Job Scheduler Queue"),
 *   cron = {"time" = 60},
 *   deriver = "Drupal\job_scheduler\Plugin\Derivative\JobSchedulerQueueWorker"
 * )
 */
class JobSchedulerQueue extends QueueWorkerBase {

  /**
   * The name of this scheduler.
   *
   * @var \Drupal\job_scheduler\JobScheduler
   */
  protected $scheduler;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface|null
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->scheduler = \Drupal::service('job_scheduler.manager');
    $this->logger = \Drupal::service('logger.channel.job_scheduler');
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($id) {
    $job = JobSchedule::load($id);
    $scheduler = $this->scheduler;
    try {
      $scheduler->execute($job);
    }
    catch (\Exception $e) {
      Error::logException($this->logger, $e);
      // Drop jobs that have caused exceptions.
      $job->delete();
    }
  }

}
