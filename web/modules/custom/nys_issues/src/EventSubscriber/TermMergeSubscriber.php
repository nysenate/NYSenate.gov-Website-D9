<?php

declare(strict_types=1);

namespace Drupal\nys_issues\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flag\FlagServiceInterface;
use Drupal\term_merge\TermMergeEventNames;
use Drupal\term_merge\TermsMergedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for term merge events on the 'issues' vocabulary.
 */
final class TermMergeSubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * Constructs a TermMergeSubscriber object.
   */
  public function __construct(
    private readonly FlagServiceInterface $flag,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
    private readonly MessengerInterface $messenger,
  ) {}

  /**
   * Recreate flags on to-delete branch terms pointing to preserved trunk terms.
   */
  public function onTermMerge(TermsMergedEvent $event): void {
    $flaggable_vid = 'issues';
    if ($event->getTargetTerm()->bundle() == $flaggable_vid) {
      $flag_id = 'follow_issue';
      $merged_tids = array_keys($event->getSourceTerms());
      $target_tid = $event->getTargetTerm()->id();

      try {
        $flag_storage = $this->entityTypeManager
          ->getStorage('flagging');
      }
      catch (\Throwable $e) {
        $message = 'Unable to recreate flags from term merge operation due to missing flag module.';
        $this->messenger->addError($message);
        $this->loggerFactory->get('nys_issues')->error($message);
        return;
      }

      $flagging_ids = $flag_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('flag_id', $flag_id)
        ->condition('entity_id', $merged_tids, 'IN')
        ->execute();

      /** @var \Drupal\flag\Entity\Flagging[] $flaggings_on_branch_terms */
      $flaggings_on_branch_terms = $flag_storage->loadMultiple($flagging_ids);
      foreach ($flaggings_on_branch_terms as $flagging) {
        // Skip if user has already flagged trunk term.
        $flagging_uid = $flagging->uid->target_id;
        $user_has_flag_on_trunk = $flag_storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('flag_id', $flag_id)
          ->condition('entity_id', $target_tid)
          ->condition('uid', $flagging_uid)
          ->execute();
        if (!empty($user_has_flag_on_trunk)) {
          continue;
        }

        // Otherwise, recreate flag for user on trunk term.
        $message_vars = [
          '@flagging_term_id' => $flagging->entity_id->value,
          '@flagging_uid' => $flagging_uid,
        ];
        try {
          $flag_interface = $this->flag->getFlagById($flag_id);
          $target_term = $this->entityTypeManager
            ->getStorage('taxonomy_term')
            ->load($target_tid);
          $flagging_user = $this->entityTypeManager
            ->getStorage('user')
            ->load($flagging_uid);
          $this->flag->flag($flag_interface, $target_term, $flagging_user);
        }
        catch (\Throwable $e) {
          $message_vars['@error_msg'] = $e->getMessage();
          $this->messenger->addError($this->t("There was an error re-creating flag for term ID @flagging_term_id for user ID @flagging_uid. Here's the full error: @error_msg", $message_vars));
          $this->loggerFactory->get('nys_issues')->error("There was an error re-creating flag for term ID @flagging_term_id for user ID @flagging_uid. Here's the full error: @error_msg", $message_vars);
          continue;
        }
        $this->messenger->addStatus($this->t("Successfully re-created flag for merged term ID @flagging_term_id for user ID @flagging_uid.", $message_vars));
        $this->loggerFactory->get('nys_issues')->info("Successfully re-created flag for merged term ID @flagging_term_id for user ID @flagging_uid.", $message_vars);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    if (!class_exists('Drupal\term_merge\TermMergeEventNames')) {
      return [];
    }

    return [
      TermMergeEventNames::TERMS_MERGED => ['onTermMerge'],
    ];
  }

}
