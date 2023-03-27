<?php

namespace Drupal\job_scheduler\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a JobSchedulerQueueWorker deriver.
 */
class JobSchedulerQueueWorker extends DeriverBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Reset the discovered definitions.
    $this->derivatives = [];

    foreach (job_scheduler_queue_info() as $queue_name => $info) {
      if (isset($info['title'])) {
        $title = $info['title'];
      }
      else {
        $title = $this->t('Job Scheduler Queue: %name', ['%name' => $queue_name]);
      }
      $time = $info['time'] ?? $base_plugin_definition['cron']['time'];
      $this->derivatives[$queue_name] = $base_plugin_definition;
      $this->derivatives[$queue_name]['id'] = $queue_name;
      $this->derivatives[$queue_name]['title'] = $title;
      $this->derivatives[$queue_name]['cron']['time'] = $time;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
