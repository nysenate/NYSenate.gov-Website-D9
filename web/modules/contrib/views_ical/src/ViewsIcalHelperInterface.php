<?php

namespace Drupal\views_ical;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\views\ResultRow;

/**
 * Contract for ViewsIcalHelper.
 */
interface ViewsIcalHelperInterface {

  /**
   * Adds an event.
   *
   * This is used when the date_field type is `datetime` or `daterange`.
   *
   * @param \Eluceo\iCal\Component\Event[] $events
   *   Set of events where the new event will be added.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to be used for creating the event.
   * @param \DateTimeZone $timezone
   *   Timezone data to be specified to the event.
   * @param array $field_mapping
   *   Views field option and entity field name mapping.
   *   Example:
   *   [
   *     'date_field' => 'field_event_date',
   *     'summary_field' => 'field_event_summary',
   *     'description_field' => 'field_event_description',
   *   ]
   *   End of example.
   *
   * @throws \Exception
   *   Throws exception if it fails to parse the datetime data from entity.
   *
   * @see \Drupal\views_ical\Plugin\views\style\Ical::defineOptions
   */
  public function addEvent(array &$events, ResultRow $row, \DateTimeZone $timezone, array $field_mapping): void;

  /**
   * Adds an event.
   *
   * This is used when the date_field type is `date_recur`.
   *
   * @param \Eluceo\iCal\Component\Event[] $events
   *   Set of events where the new event will be added.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to be used for creating the event.
   * @param \DateTimeZone $timezone
   *   Timezone data to be specified to the event.
   * @param array $field_mapping
   *   Views field option and entity field name mapping.
   *   Example:
   *   [
   *     'date_field' => 'field_event_date',
   *     'summary_field' => 'field_event_summary',
   *     'description_field' => 'field_event_description',
   *   ]
   *   End of example.
   *
   * @see \Drupal\views_ical\Plugin\views\style\Ical::defineOptions
   */
  public function addDateRecurEvent(array &$events, ResultRow $row, \DateTimeZone $timezone, array $field_mapping): void;

}
