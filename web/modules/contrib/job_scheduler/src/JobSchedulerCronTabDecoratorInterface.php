<?php

namespace Drupal\job_scheduler;

/**
 * Provides an interface for JobSchedulerCronTabDecorator.
 */
interface JobSchedulerCronTabDecoratorInterface {

  /**
   * Job scheduler crontab decorator.
   *
   * @param string|array $crontab
   *   The job scheduler crontab.
   *
   * @return \Drupal\job_scheduler\JobSchedulerCronTabInterface
   *   Return job scheduler crontab interface.
   */
  public function decorate($crontab);

}
