# Job Scheduler

Simple API for scheduling tasks once at a predetermined time or periodically at
a fixed interval.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/job_scheduler).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/job_scheduler).


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Usage

Declare scheduler.

    function example_cron_job_scheduler_info() {
       $schedulers = [];
       $schedulers['example_unpublish'] = [
      'worker callback' => 'example_unpublish_nodes',
    ];
    return $schedulers;
    }

Add a job.

    $job = [
     'name' => 'example_unpublish',
     'type' => 'story',
     'id' => 12,
     'period' => 3600,
     'periodic' => TRUE,
    ];
    $service = \Drupal::service('job_scheduler.manager');
    $service->set($job);

Work off a job.

    function example_unpublish_nodes(
      \Drupal\job_scheduler\Entity\JobSchedule $job) {
      // Do stuff.
    }

Remove a job.

    $job = [
      'name' => 'example_unpublish',
      'type' => 'story',
      'id' => 12,
    ];
    $service = \Drupal::service('job_scheduler.manager');
    $service->remove($job);

Optionally jobs can declared together with a schedule in a
hook_cron_job_scheduler_info().

    function example_cron_job_scheduler_info() {
      $schedulers = [];
      $schedulers['example_unpublish'] = [
       'worker callback' => 'example_unpublish_nodes',
       'jobs' => [
         [
           'type' => 'story',
           'id' => 12,
           'period' => 3600,
           'periodic' => TRUE,
         ],
       ],
     ];
     return $schedulers;
    }

Jobs can have a 'crontab' instead of a period. Crontab syntax are Unix-like
formatted crontab lines.

Example of job with crontab.

  // This will create a job that will be triggered from monday to friday, from
  // january to july, every two hours.
    function example_cron_job_scheduler_info() {
      $schedulers = [];
      $schedulers['example_unpublish'] = [
        'worker callback' => 'example_unpublish_nodes',
       'jobs' => [
          [
           'type' => 'story',
           'id' => 12,
           'crontab' => '0 */2 * january-july mon-fri',
           'periodic' => TRUE,
         ],
        ],
      ];
      return $schedulers;
    }

Read more about crontab syntax, <http://linux.die.net/man/5/crontab>


## Drupal Queue integration

Optionally, at the scheduled time Job Scheduler can queue a job for execution,
rather than executing the job directly. This is useful when many jobs need to
be executed or when the job's expected execution time is very long.

More information on Drupal Queue: <https://api.drupal.org/api/drupal/core%21core.api.php/group/queue/8.0.x>

Declare a queue name and a worker callback.

    function example_cron_job_scheduler_info() {
      $schedulers = [];
      $schedulers['example_unpublish'] = [
      'queue name' => 'example_unpublish_queue',
      'worker callback' => 'example_unpublish_nodes',
     ];
     return $schedulers;
    }

    function example_unpublish_nodes(
     \Drupal\job_scheduler\Entity\JobSchedule $job) {
     // Do stuff.
    }

Optionally, can specify the name and the execution time of the queue.

    function example_cron_job_scheduler_queue_info() {
     $schedulers = [];
       $schedulers['example_unpublish_queue'] = [
      'title' => 'Example unpublish nodes',
      'time' => 120,
     ];
     return $schedulers;
    }


## Configuration

The module has no menu or modifiable settings. There is no configuration. When
enabled, the module will prevent the links from appearing. To get the links
back, disable the module and clear caches.
