<?php

namespace Drupal\job_scheduler;

use Drupal\job_scheduler\Entity\JobSchedule;

/**
 * Provides an interface defining a job scheduler manager.
 */
interface JobSchedulerInterface {

  /**
   * Returns scheduler info.
   *
   * @param string $name
   *   Name of the schedule.
   *
   * @return array
   *   Information for the schedule.
   *
   * @see hook_cron_job_scheduler_info()
   *
   * @throws \Exception
   *   Exceptions thrown by code called by this method are passed on.
   */
  public function info($name);

  /**
   * Adds a job to the schedule, replace any existing job.
   *
   * A job is uniquely identified by $job = [type, id].
   *
   * @code
   * function worker_callback($job) {
   *   // Work off job.
   *   // Set next time to be called. If this portion of the code is not
   *   // reached for some reason, the scheduler will keep periodically invoking
   *   // the callback() with the period value initially specified.
   *   $scheduler->set($job);
   * }
   * @endcode
   *
   * @param array $job
   *   An array that must contain the following keys:
   *   'type'     - A string identifier of the type of job.
   *   'id'       - A numeric identifier of the job.
   *   'period'   - The time when the task should be executed.
   *   'periodic' - True if the task should be repeated periodically.
   *
   * @throws \Exception
   *   Exceptions thrown by code called by this method are passed on.
   */
  public function set(array $job);

  /**
   * Removes a job from the schedule, replace any existing job.
   *
   * A job is uniquely identified by $job = [type, id].
   *
   * @param array $job
   *   A job to remove.
   *
   * @throws \Exception
   *   Exceptions thrown by code called by this method are passed on.
   */
  public function remove(array $job);

  /**
   * Removes all jobs for a given type.
   *
   * @param string $name
   *   The job name to remove.
   * @param string $type
   *   The job type to remove.
   *
   * @throws \Exception
   *   Exceptions thrown by code called by this method are passed on.
   */
  public function removeAll($name, $type);

  /**
   * Dispatches a job.
   *
   * Executes a worker callback or if schedule declares a queue name, queues a
   * job for execution.
   *
   * @param \Drupal\job_scheduler\Entity\JobSchedule $job
   *   A $job entity as passed into set() or loaded.
   *
   * @throws \Exception
   *   Exceptions thrown by code called by this method are passed on.
   */
  public function dispatch(JobSchedule $job);

  /**
   * Executes a job.
   *
   * @param \Drupal\job_scheduler\Entity\JobSchedule $job
   *   A $job array as passed into set() or loaded.
   *
   * @throws \Exception
   *   Exceptions thrown by code called by this method are passed on.
   */
  public function execute(JobSchedule $job);

  /**
   * Re-schedules a job if intended to run again.
   *
   * If cannot determine the next time, drop the job.
   *
   * @param \Drupal\job_scheduler\Entity\JobSchedule $job
   *   The job to reschedule.
   *
   * @throws \Exception
   *   Exceptions thrown by code called by this method are passed on.
   */
  public function reschedule(JobSchedule $job);

  /**
   * Checks whether a job exists in the queue and update its parameters if so.
   *
   * @param array $job
   *   The job to reschedule.
   *
   * @return bool
   *   Execution result.
   *
   * @throws \Exception
   *   Exceptions thrown by code called by this method are passed on.
   */
  public function check(array $job);

  /**
   * Perform periodic jobs.
   *
   * @param string $name
   *   (optional) Name of the schedule to perform. Defaults to null.
   * @param int $limit
   *   (optional) The number of jobs to perform. Defaults to 200.
   * @param int $time
   *   (optional) How much time scheduler should spend on processing jobs in
   *   seconds. Defaults to 30.
   *
   * @return array
   *   Result of perform periodic jobs.
   *
   * @throws \Exception
   *   Exceptions thrown by code called by this method are passed on.
   */
  public function perform($name = NULL, $limit = 200, $time = 30);

  /**
   * Rebuilds a single scheduler.
   *
   * @param string $name
   *   The name of the schedule.
   * @param array $info
   *   (optional) The job info array. Defaults to null.
   *
   * @throws \Exception
   *   Exceptions thrown by code called by this method are passed on.
   */
  public function rebuild($name, array $info = NULL);

  /**
   * Rebuilds all schedulers.
   *
   * @throws \Exception
   *   Exceptions thrown by code called by this method are passed on.
   */
  public function rebuildAll();

}
