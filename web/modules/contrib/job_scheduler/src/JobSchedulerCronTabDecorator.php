<?php

namespace Drupal\job_scheduler;

/**
 * Provides a job scheduler crontab decorator.
 */
class JobSchedulerCronTabDecorator implements JobSchedulerCronTabDecoratorInterface {

  /**
   * The job scheduler crontab that is decorated.
   *
   * @var \Drupal\job_scheduler\JobSchedulerCronTabInterface
   */
  protected $crontab;

  /**
   * {@inheritdoc}
   */
  public function decorate($crontab) {
    $this->crontab = new JobSchedulerCronTab($crontab);
    return $this->crontab;
  }

}
