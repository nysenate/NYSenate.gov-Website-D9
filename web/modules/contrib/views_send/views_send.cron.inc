<?php

/**
 * @file
 *   Views Send cron rotuines.
 *
 * @ingroup views_send
 */

use Drupal\Component\Utility\Timer;
use Drupal\views_send\Event\MailSentEvent;

/**
 * Process the spool queue at cron run.
 */
function views_send_send_from_spool() {
  $limit = \Drupal::config('views_send.settings')->get('throttle');
  $ok = $fail = $check = 0;

  // Reset a Drupal timer.
  Timer::start('views_send');

  // Retrieve messages to be send.
  $query = "SELECT * FROM {views_send_spool} WHERE status = :status ORDER BY tentatives ASC, timestamp ASC";
  $result = $limit ? \Drupal::database()->queryRange($query, 0, $limit, array(':status' => 0)) : \Drupal::database()->query($query, array(':status' => 0));
  foreach ($result as $message) {
    // Send the message.
    $status = views_send_deliver($message);

    if ($status) {
      // Update the spool status.
      \Drupal::database()->query("UPDATE {views_send_spool} SET status = :status WHERE eid = :eid", array(':status' => 1, ':eid' => $message->eid));
      if (\Drupal::config('views_send.settings')->get('debug')) {
        \Drupal::logger('views_send')->notice(t('Message sent to %mail.', array('%mail' => $message->to_mail)));
      }

      $event = new MailSentEvent($message);
      $event_dispatcher = \Drupal::service('event_dispatcher');
      $event_dispatcher->dispatch(MailSentEvent::EVENT_NAME, $event);

      $ok++;
    }
    else {
      // Increment tentatives so that next time this message
      // will be scheduled with low priority.
      \Drupal::database()->query("UPDATE {views_send_spool} SET tentatives = tentatives + 1 WHERE eid = :eid", array(':eid' => $message->eid));
      $fail++;
    }

    // Check the elapsed time and break if we've spent more than 80%.
    // We check every 50 messages.
    if (++$check >= 50) {
      // Reset the counter.
      $check = 0;

      // Break if exceded.
      if (Timer::read('views_send') / 1000 > .8 * ini_get('max_execution_time')) {
        \Drupal::logger('views_send')->notice(t('PHP maximum execution time almost exceeded. Remaining e-mail messages will be sent during the next cron run. If this warning occurs regularly you should reduce the cron throttle setting.'));
        break;
      }
    }
  }

  if ($ok + $fail > 0) {
    // Log results and exit.
    \Drupal::logger('views_send')->notice(t('%ok messages sent in %sec seconds, %fail failed sending.',
      array('%ok' => $ok, '%sec' => Timer::read('views_send') / 1000, '%fail' => $fail)
    ));
  }
}

/**
 * Clear items from spool.
 */
function views_send_clear_spool() {
  // Remove expired sent messages.
  $expiration_time = time() - \Drupal::config('views_send.settings')->get('spool_expire') * 86400;
  \Drupal::database()->query("DELETE FROM {views_send_spool} WHERE status = :status AND timestamp <= :expiry", array(':status' => 1, 'expiry' => $expiration_time));
  // Remove unsent messages which have failed more times than the retry limit.
  $retry_limit = \Drupal::config('views_send.settings')->get('retry');
  \Drupal::database()->query("DELETE FROM {views_send_spool} WHERE status = :status AND tentatives > :retry", array(':status' => 0, 'retry' => $retry_limit));
}
