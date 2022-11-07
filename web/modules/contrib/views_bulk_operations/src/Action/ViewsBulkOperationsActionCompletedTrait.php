<?php

namespace Drupal\views_bulk_operations\Action;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Defines action completion logic.
 */
trait ViewsBulkOperationsActionCompletedTrait {

  /**
   * Set message function wrapper.
   *
   * @see \Drupal\Core\Messenger\MessengerInterface
   */
  public static function message($message = NULL, $type = 'status', $repeat = TRUE): void {
    \Drupal::messenger()->addMessage($message, $type, $repeat);
  }

  /**
   * Translation function wrapper.
   *
   * @see \Drupal\Core\StringTranslation\TranslationInterface:translate()
   */
  public static function translate($string, array $args = [], array $options = []): TranslatableMarkup {
    return \Drupal::translation()->translate($string, $args, $options);
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Was the process successful?
   * @param array $results
   *   Batch process results array.
   * @param array $operations
   *   Performed operations array.
   */
  public static function finished($success, array $results, array $operations): ?RedirectResponse {
    if ($success) {
      foreach ($results['operations'] as $item) {
        // Default fallback to maintain backwards compatibility:
        // if api version equals to "1" and type equals to "status",
        // previous message is displayed, otherwise we display exactly what's
        // specified in the action.
        if ($item['type'] === 'status' && $results['api_version'] === '1') {
          $message = static::translate('Action processing results: @operation (@count).', [
            '@operation' => $item['message'],
            '@count' => $item['count'],
          ]);
        }
        else {
          $message = new FormattableMarkup('@message (@count)', [
            '@message' => $item['message'],
            '@count' => $item['count'],
          ]);
        }
        static::message($message, $item['type']);
      }
    }
    else {
      $message = static::translate('Finished with an error.');
      static::message($message, 'error');
    }
    return NULL;
  }

}
