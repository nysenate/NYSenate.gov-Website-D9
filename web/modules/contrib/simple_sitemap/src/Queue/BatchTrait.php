<?php

namespace Drupal\simple_sitemap\Queue;

use Drupal\Core\StringTranslation\StringTranslationTrait;

trait BatchTrait {

  use StringTranslationTrait;

  /**
   * @var array
   */
  protected $batch;

  protected static $batchErrorMessage = 'The generation failed to finish. It can be continued manually on the module\'s settings page, or via drush.';

  /**
   * @param string $from
   * @param array|null $variants
   * @return bool
   */
  public function batchGenerateSitemap($from = self::GENERATE_TYPE_FORM, $variants = NULL) {
    $this->batch = [
      'title' => $this->t('Generating XML sitemaps'),
      'init_message' => $this->t('Initializing...'),
      'error_message' => $this->t(self::$batchErrorMessage),
      'progress_message' => $this->t('Processing items from the queue.<br>Each sitemap variant gets published after all of its items have been processed.'),
      'operations' => [[ __CLASS__ . '::' . 'doBatchGenerateSitemap', []]],
      'finished' => [__CLASS__, 'finishGeneration'],
    ];

    switch ($from) {

      case self::GENERATE_TYPE_FORM:
        // Start batch process.
        batch_set($this->batch);
        return TRUE;

      case self::GENERATE_TYPE_DRUSH:
        // Start drush batch process.
        batch_set($this->batch);

        // See https://www.drupal.org/node/638712
        $this->batch =& batch_get();
        $this->batch['progressive'] = FALSE;

        drush_backend_batch_process();
        return TRUE;
    }
    return FALSE;
  }

  /**
   * @param $context
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *
   * @todo Variants into generateSitemap().
   */
  public static function doBatchGenerateSitemap(&$context) {

    /** @var \Drupal\simple_sitemap\Queue\QueueWorker $queue_worker */
    $queue_worker = \Drupal::service('simple_sitemap.queue_worker');

    $queue_worker->generateSitemap();
    $processed_element_count = $queue_worker->getProcessedElementCount();
    $original_element_count = $queue_worker->getInitialElementCount();

    $context['message'] = t('@indexed out of @total total queue items have been processed.', [
      '@indexed' => $processed_element_count, '@total' => $original_element_count]);
    $context['finished'] = $original_element_count > 0 ? ($processed_element_count / $original_element_count) : 1;
  }

  /**
   * Callback function called by the batch API when all operations are finished.
   *
   * @param bool $success
   * @param array $results
   * @param array $operations
   *
   * @return bool
   *
   * @see https://api.drupal.org/api/drupal/core!includes!form.inc/group/batch/8
   */
  public static function finishGeneration($success, $results, $operations) {
    if ($success) {
      \Drupal::service('simple_sitemap.logger')
        ->m('The XML sitemaps have been regenerated.')
        ->log('info');
    }
    else {
      \Drupal::service('simple_sitemap.logger')
        ->m(self::$batchErrorMessage)
        ->display('error', 'administer sitemap settings')
        ->log('error');
    }

    return $success;
  }
}

