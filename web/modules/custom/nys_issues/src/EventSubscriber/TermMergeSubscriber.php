<?php

declare(strict_types=1);

namespace Drupal\nys_issues\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\term_merge\TermMergeEventNames;
use Drupal\term_merge\TermsMergedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for term merge events on the 'issues' vocabulary.
 */
final class TermMergeSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a TermMergeSubscriber object.
   */
  public function __construct(
    private readonly FlagServiceInterface $flag,
    private readonly EntityTypeManagerInterface $entityTypeManager,
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
      $flag_storage = $this->entityTypeManager
        ->getStorage('flagging');
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
        $flag_interface = $this->flag->getFlagById($flag_id);
        $target_term = $this->entityTypeManager
          ->getStorage('taxonomy_term')
          ->load($target_tid);
        $flagging_user = $this->entityTypeManager
          ->getStorage('user')
          ->load($flagging_uid);
        $this->flag->flag($flag_interface, $target_term, $flagging_user);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      TermMergeEventNames::TERMS_MERGED => ['onTermMerge'],
    ];
  }

}
