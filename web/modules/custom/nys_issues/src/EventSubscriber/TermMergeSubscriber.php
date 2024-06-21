<?php

declare(strict_types=1);

namespace Drupal\nys_issues\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\term_merge\TermMergeEventNames;
use Drupal\term_merge\TermsMergedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for issue taxonomy events.
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
   * Event handler to ensure flags on merged terms are properly rewired.
   */
  public function onTermMerge(TermsMergedEvent $event): void {
    // @todo implement.
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
