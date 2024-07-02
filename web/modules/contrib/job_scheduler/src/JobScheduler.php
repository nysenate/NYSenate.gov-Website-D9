<?php

namespace Drupal\job_scheduler;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Utility\Error;
use Drupal\job_scheduler\Entity\JobSchedule;
use Psr\Log\LoggerInterface;

/**
 * Manage scheduled jobs.
 */
class JobScheduler implements JobSchedulerInterface {

  /**
   * The job scheduler crontab decorator.
   *
   * @var \Drupal\job_scheduler\JobSchedulerCronTabDecoratorInterface
   */
  protected $crontabDecorator;

  /**
   * The job schedule storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $jobScheduleStorage;

  /**
   * The queue object.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface|null
   */
  protected $logger;

  /**
   * Constructs a object.
   *
   * @param \Drupal\job_scheduler\JobSchedulerCronTabDecoratorInterface $crontab_decorator
   *   The job scheduler crontab decorator.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Queue\QueueFactory $queue
   *   The queue factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(JobSchedulerCronTabDecoratorInterface $crontab_decorator, EntityTypeManagerInterface $entityTypeManager, QueueFactory $queue, LoggerInterface $logger) {
    $this->crontabDecorator = $crontab_decorator;
    $this->jobScheduleStorage = $entityTypeManager->getStorage('job_schedule');
    $this->queue = $queue;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function info($name) {
    if ($info = job_scheduler_info($name)) {
      return $info;
    }
    throw new JobSchedulerException('Could not find Job Scheduler cron information for ' . $name . '.');
  }

  /**
   * {@inheritdoc}
   */
  public function set(array $job) {
    $storage = $this->jobScheduleStorage;
    $timestamp = time();
    $job['last'] = $timestamp;
    if (!empty($job['crontab'])) {
      $crontab = $this->crontabDecorator->decorate($job['crontab']);
      $job['next'] = $crontab->nextTime($timestamp);
    }
    else {
      $job['next'] = $timestamp + $job['period'];
    }

    $entity = $storage->create($job);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function remove(array $job) {
    $storage = $this->jobScheduleStorage;
    $query = $storage->getQuery();
    $query->accessCheck(FALSE);
    $query->condition('name', $job['name']);
    $query->condition('type', $job['type']);
    $query->condition('id', $job['id'] ?? 0);
    $entity_ids = $query->execute();
    if (!empty($entity_ids)) {
      $entities = $storage->loadMultiple($entity_ids);
      $storage->delete($entities);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function removeAll($name, $type) {
    $storage = $this->jobScheduleStorage;
    $query = $storage->getQuery();
    $query->accessCheck(FALSE);
    $query->condition('name', $name);
    $query->condition('type', $type);
    $entity_ids = $query->execute();
    if (!empty($entity_ids)) {
      $entities = $storage->loadMultiple($entity_ids);
      $storage->delete($entities);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function dispatch(JobSchedule $job) {
    $info = $this->info($job->getName());
    if (!empty($info['queue name'])) {
      $queue_name = 'job_scheduler_queue:' . $info['queue name'];
      if ($this->queue($queue_name)->createItem($job->id())) {
        $this->reserve($job);
      }
    }
    else {
      $this->execute($job);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute(JobSchedule $job) {
    $info = $this->info($job->getName());
    // If the job is periodic, re-schedule it before calling the worker.
    if ($job->getPeriodic()) {
      $this->reschedule($job);
    }
    else {
      $job->delete();
    }
    if (!empty($info['file']) && file_exists($info['file'])) {
      include_once $info['file'];
    }
    if (function_exists($info['worker callback'])) {
      call_user_func($info['worker callback'], $job);
    }
    else {
      throw new JobSchedulerException('Could not find worker callback function: ' . $info['worker callback']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function reschedule(JobSchedule $job) {
    $timestamp = time();
    $job->setScheduled(0);
    $job->setLast($timestamp);
    $crontab = $job->getCrontab();
    if (!empty($crontab)) {
      $crontab = $this->crontabDecorator->decorate($crontab);
      $next = $crontab->nextTime($timestamp);
    }
    else {
      $next = $timestamp + $job->getPeriod();
    }
    if ($next) {
      $job->setNext($next);
      $job->save();
    }
    else {
      // If no next time, it may mean it wont run again the next year (crontab).
      $job->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function check(array $job) {
    $storage = $this->jobScheduleStorage;
    $job += ['id' => 0, 'period' => 0, 'crontab' => ''];

    $query = $storage->getQuery();
    $query->accessCheck(FALSE);
    $query->condition('name', $job['name']);
    $query->condition('type', $job['type']);
    $query->condition('id', $job['id']);
    $entity_ids = $query->execute();

    // If existing, and changed period or crontab, reschedule the job.
    if ($entity_ids) {
      /** @var \Drupal\job_scheduler\Entity\JobSchedule $existing */
      $existing = $storage->load(reset($entity_ids));
      if ($job['period'] != $existing->getPeriod() || $job['crontab'] != $existing->getCrontab()) {
        $existing->setPeriod($job['period']);
        $existing->setCrontab($job['crontab']);
        $this->reschedule($existing);
      }

      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function perform($name = NULL, $limit = 200, $time = 30) {
    $storage = $this->jobScheduleStorage;
    $timestamp = time();

    // Reschedule stuck periodic jobs after one hour.
    $query = $storage->getQuery();
    $query->accessCheck(FALSE);
    $query->condition('scheduled', $timestamp - 3600, '<');
    $query->condition('periodic', 1);
    if (!empty($name)) {
      $query->condition('name', $name);
    }
    $entity_ids = $query->execute();
    if (!empty($entity_ids)) {
      $jobs = $storage->loadMultiple($entity_ids);
      foreach ($jobs as $job) {
        $job->setScheduled(0);
        $job->save();
      }
    }

    // Query and dispatch scheduled jobs.
    // Process a maximum of 200 jobs in a maximum of 30 seconds.
    $start = time();
    $total = 0;
    $failed = 0;
    $query = $storage->getQuery();
    $query->accessCheck(FALSE);
    $query->condition('scheduled', 0);
    $query->condition('next', $timestamp, '<=');
    if (!empty($name)) {
      $query->condition('name', $name);
    }
    $query->sort('next', 'ASC');
    $query->range(0, $limit);
    $entity_ids = $query->execute();
    if (!empty($entity_ids)) {
      $jobs = $storage->loadMultiple($entity_ids);
      foreach ($jobs as $job) {
        try {
          $this->dispatch($job);
        }
        catch (\Exception $e) {
          Error::logException($this->logger, $e);
          $failed++;
          // Drop jobs that have caused exceptions.
          $job->delete();
        }
        $total++;
        if (time() > ($start + $time)) {
          break;
        }
      }
    }

    return ['start' => $start, 'total' => $total, 'failed' => $failed];
  }

  /**
   * {@inheritdoc}
   */
  public function rebuild($name, array $info = NULL) {
    $info = $info ?: $this->info($name);

    if (!empty($info['jobs'])) {
      foreach ($info['jobs'] as $job) {
        $job['name'] = $name;
        if (!$this->check($job)) {
          $this->set($job);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function rebuildAll() {
    foreach (job_scheduler_info() as $name => $info) {
      $this->rebuild($name, $info);
    }
  }

  /**
   * Reserves a job.
   *
   * @param \Drupal\job_scheduler\Entity\JobSchedule $job
   *   The job to reserve.
   *
   * @see \Drupal\job_scheduler\JobScheduler::dispatch()
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures at the configuration storage level.
   */
  protected function reserve(JobSchedule $job) {
    $timestamp = time();
    $scheduled = $job->getPeriod() + $timestamp;
    $job->setScheduled($scheduled);
    $job->setLast($timestamp);
    $job->setNext($scheduled);
    $job->save();
  }

}
