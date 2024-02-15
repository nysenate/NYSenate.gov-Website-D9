<?php

/**
 * @file
 * Contains \Drupal\views_ical\Plugin\views\row\Fields.
 */

namespace Drupal\views_ical\Plugin\views\row;

use DateTimeZone;
use Drupal\views\Plugin\views\row\Fields;
use Drupal\views\ResultRow;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Url;
use Eluceo\iCal\Component\Event;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Timezone;
use Html2Text\Html2Text;
use Eluceo\iCal\Property\Event\RecurrenceRule;
use Eluceo\iCal\Property\Event\ExDate;
use Drupal\smart_date_recur\Entity\SmartDateRule;
use Drupal\smart_date\SmartDateTrait;
use Drupal\views_ical\ViewsIcalHelper;


/**
 * The 'Ical Fields' row plugin
 *
 * This displays fields one after another, giving options for inline
 * or not.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "ical_fields_wizard",
 *   title = @Translation("iCal fields row wizard"),
 *   help = @Translation("Generate ical events with the iCal library."),
 *   theme = "views_view_ical_fields",
 *   display_types = {"feed"}
 * )
 */
class IcalFieldsWizard extends Fields {

  /**
   * @var \Drupal\views_ical\ViewsIcalHelperInterface
   */
  private $helper;

  /**
   * Render a row object. This usually passes through to a theme template
   * of some form, but not always.
   *
   * @param object $row
   *   A single row of the query result, so an element of $view->result.
   *
   * @return string
   *   The rendered output of a single row, used by the style plugin.
   */
  public function render($row) {
    $renderer = $this->getRenderer();
    $style = $this->view->getStyle();
    $this->helper = $style->getHelper();
    $style_options = $style->options;
     /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $field_storage_definitions */
    // $field_storage_definitions = $style->entityFieldManager->getFieldStorageDefinitions($this->view->field[$options['date_field']]->definition['entity_type']);
    $entity_field_manager = $style->getEntityFieldManager();

    if(!isset($style_options['date_field'])) {
      // If this is not set for some reason (dev is just starting out to create
      // a view?), don't try to render. We can't have an event without a date.
      return;
    }

    $field_storage_definitions = $entity_field_manager->getFieldStorageDefinitions($this->view->field[$style_options['date_field']]->definition['entity_type']);


    //$date_field = $this->view->field[$options['date_field']];
    $date_field_definition = $field_storage_definitions[$this->view->field[$style_options['date_field']]->definition['field_name']];
    /** @var string $date_field_type */
    $date_field_type = $date_field_definition->getType();

    $events = [];
    $user_timezone = \date_default_timezone_get();

    // Make sure the events are made as per the configuration in view.
    /** @var string $timezone_override */
    $timezone_override = $this->view->field[$style_options['date_field']]->options['settings']['timezone_override'];
    if ($timezone_override) {
      $timezone = new \DateTimeZone($timezone_override);
    }
    else {
      $timezone = new \DateTimeZone($user_timezone);
    }

    // Provide an opportunity to

    // Use date_recur's API to generate the events.
    // Recurring events will be automatically handled here.
    if ($date_field_type === 'date_recur') {
      $this->addDateRecurEvent($events, $row, $timezone, $style_options);
    }
    // Datetime events are single dates without a time component.
    // Many content models might
    else if($date_field_type === 'datetime') {
      $this->addDateTimeEvent($events, $row, $timezone, $style_options);

    }
    else if ($date_field_type === 'daterange') {
      // TODO: are date ranges separate date field types?
      $this->addDateRangeEvent($events, $row, $timezone, $style_options);

    }
    // This field type is actually deprecated by the date_all_day module.
    else if ($date_field_type === 'daterange_all_day') {
      throw new \Exception('daterange_all_day fields not supported.');
    }
    else if($date_field_type === 'smartdate') {
      $this->addSmartDateEvent($events, $row, $timezone, $style_options);
    }

    $calendar = $this->view->getStyle()->getCalendar();

    if (!empty($events)) {
      foreach ($events as $event) {
        if ($event) {
          $calendar->addComponent($event);
        }
      }
    }


    return [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $row,
      '#field_alias' => isset($this->field_alias) ? $this->field_alias : '',
      '#event' => $events,
    ];
  }

  /**
   * Creates an event with default data.
   *
   * Event summary, location and description are set as defaults.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to be used for default data.
   * @param array $field_mapping
   *   Views field option and entity field name mapping.
   *   Example:
   *   [
   *     'date_field' => 'field_event_date',
   *     'summary_field' => 'field_event_summary',
   *     'description_field' => 'field_event_description',
   *   ]
   *   End of example.
   *  @param \Drupal\views\ResultRow $row
   *    The values retrieved from a single row of a view's query result.
   *
   * @return \Eluceo\iCal\Component\Event
   *   A new event.
   *
   * @see \Drupal\views_ical\Plugin\views\style\Ical::defineOptions
   */
  protected function createDefaultEvent(ContentEntityInterface $entity, array $field_mapping, ResultRow $row): Event {
    if(isset($field_mapping['uid_field'])
      && ($field_mapping['uid_field'] == 'nid'
        || $field_mapping['uid_field'] == 'nothing')) {
      // If the Uid field is the nid, access with the id method.
      $uid = $entity->id();
      if(isset($this->view->field[$field_mapping['uid_field']]->options['alter']['alter_text'])
        && $this->view->field[$field_mapping['uid_field']]->options['alter']['alter_text']) {
        // I need rewrite of the UID field to happen here.
        // This is really hacky, It would be really nice to find a way to render as the row.
        $alter_text = $this->view->field[$field_mapping['uid_field']]->options['alter']['text'];
        $fields = array_keys($this->view->field);
        foreach ($fields as $field) {
          if ($entity->hasField($field)) {
            if ($entity->get($field)->getDataDefinition()->getType() == 'created') {
              $settings = $this->view->field['created']->options['settings'];
              ['custom_date_format'];
              if($settings['date_format'] == 'custom') {
                $field_value =  \Drupal::service('date.formatter')->format($entity->get($field)->getString(), 'custom', $settings['custom_date_format']);
              }
              else {
                $field_value =  \Drupal::service('date.formatter')->format($entity->get($field)->getString(), $settings['date_format']);
              }
            }
            else {
              $field_value = $entity->get($field)->getString();
            }
            $alter_text= str_replace("{{ $field }}", $field_value, $alter_text);
          }
        }
        $uid = $alter_text;
      }
    }
    else if(isset($field_mapping['uid_field'])
      && $field_mapping['uid_field'] != 'none'
      && $entity->hasField($field_mapping['uid_field'])
      && !$entity->get($field_mapping['uid_field'])->isEmpty()) {
      $uid = $entity->get($field_mapping['uid_field'])->getString();
    }
    else {
      $uid = null;
    }

    $event = new Event($uid);

    // Summary field.
    if (isset($field_mapping['summary_field']) && isset($this->view->field[$field_mapping['summary_field']])) {
      // TODO: We repeat something similar to this 3 times. I like my code like I like my wine, DRY.
      $renderer = $this->getRenderer();
      $summaryField = $entity->{$field_mapping['summary_field']}->view();
      if ($renderer->hasRenderContext()) {
        $html = $renderer->render($summaryField);
      }
      else {
        $html = $renderer->renderPlain($summaryField);
      }
      $html = new Html2Text((string) $html);
      $event->setSummary($html->getText());

    }

    // Rrule field.
    if (isset($field_mapping['rrule_field'])) {

      if ($field_mapping['rrule_field'] == 'body') {
        $rrule = $entity->get('body')->getValue()[0]['value'];
        $event->addRecurrenceRule($rrule);
      }
      else {
        $rruleHelper = $entity->field_recur[0]->getHelper();
        $rrules = $rruleHelper->getRules();
        $exdates = $rruleHelper->getExcluded();

        // Parse EXDATEs.
        if ($exdates) {
          foreach ($exdates as $exdate) {
            if ($exdate) {
              $event->addExDate($exdate);
            }
          }
        }

        // Calculate 2 years from today for limiting infinitely recurring dates.
        $two_years_out = date('c', strtotime('+2 years'));

        // Parse rrules into usable bits.
        foreach ($rrules as $key => $rule) {
          $parts = $rule->getParts() ?? '';
          $frequency = $parts['FREQ'] ?? '';
          $byday = $parts['BYDAY'] ?? '';
          $until = $parts['UNTIL'] ?? date_create($two_years_out);
          $count = $parts['COUNT'] ?? '';
          $interval = $parts['INTERVAL'] ?? '';

          // Set recurrence rule
          $recurrenceRule = new RecurrenceRule();
          if ($frequency) {
            $recurrenceRule->setFreq($frequency);
          }
          if ($byday) {
            $recurrenceRule->setByDay($byday);
          }
          if ($until) {
            $recurrenceRule->setUntil($until);
          }
          if ($count) {
            $recurrenceRule->setCount($count);
          }
          if ($interval) {
            $recurrenceRule->setInterval($interval);
          }
          $event->addRecurrenceRule($recurrenceRule);
        }
      }
    }

    // URL field
    if (isset($field_mapping['url_field'])) {
      if ($entity->hasField($field_mapping['url_field'])) {
        if ($field_mapping['url_field'] == 'body'
          && !$entity->get('body')->isEmpty()) {
          $url = $entity->get('body')->getValue()[0]['value'];
        }
        else {
          $url = $entity->get($field_mapping['url_field'])
            ->getValue()[0]['uri'] ?? '';
        }
        $event->setUrl($url);
      }
      elseif ($field_mapping['url_field'] == 'view_node') {
        $urlObject = Url::fromRoute('entity.node.canonical', ['node' => $entity->id()], ['absolute' => TRUE]);
        $event->setUrl($urlObject->toString());
      }
    }

    // Location field
    if (isset($field_mapping['location_field'])
      && isset($this->view->field[$field_mapping['location_field']])
      && isset($entity->{$field_mapping['location_field']})) {

      $locationField = $entity->{$field_mapping['location_field']}->view();
      if ($renderer->hasRenderContext()) {
        $html = $renderer->render($locationField);
      }
      else {
        $html = $renderer->renderPlain($locationField);
      }
      $html = new Html2Text((string) $html);
      $event->setLocation($html->getText());
    }

    // Description field
    if (isset($field_mapping['description_field']) && isset($this->view->field[$field_mapping['description_field']])) {
      $descriptionField = $entity->{$field_mapping['description_field']}->view();
      if ($renderer->hasRenderContext()) {
        $html = $renderer->render($descriptionField);
      }
      else {
        $html = $renderer->renderPlain($descriptionField);
      }
      $html = new Html2Text((string) $html);
      $event->setDescription($html->getText());
    }

    // Transparency - This isn't a real field, but a default setting applied to all events.
    if (isset($field_mapping['default_transparency']) && $field_mapping['default_transparency']) {
      if($field_mapping['default_transparency'] == Event::TIME_TRANSPARENCY_OPAQUE)
        $event->setTimeTransparency(Event::TIME_TRANSPARENCY_OPAQUE);
      else
        $event->setTimeTransparency(Event::TIME_TRANSPARENCY_TRANSPARENT);
    }

    $event->setUseTimezone(TRUE);

    return $event;
  }

  /**
   * Create an event based on a daterange field.
   *
   * @param array $events
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param \DateTimeZone $timezone
   * @param array $field_mapping
   */
  public function addDateRangeEvent(array &$events, ResultRow $row, \DateTimeZone $timezone, array $field_mapping): void {

    $entity = $this->getEntity($row);

    $utc_timezone = new \DateTimeZone('UTC');
    $datefield_values = $entity->get($field_mapping['date_field'])->getValue();

    // TODO: make these separate functions
    // Loop over the values to support multiple cardinality dates, which can
    // represent multiple events.
    foreach ($entity->get($field_mapping['date_field'])->getValue() as $date_entry) {

      // generate the event.
      $event = $this->createDefaultEvent($entity, $field_mapping, $row);

      // Set the start time
      $start_datetime = new \DateTime($date_entry['value'], $utc_timezone);
      $start_datetime->setTimezone($timezone);
      $event->setDtStart($start_datetime);
      $this->helper->addTimezone($timezone, $start_datetime);

      // Loop over field values so we can support daterange fields with multiple cardinality.
      if (!empty($date_entry['end_value'])) {
        $end_datetime = new \DateTime($date_entry['end_value'], $utc_timezone);
        $end_datetime->setTimezone($timezone);

        $event->setDtEnd($end_datetime);
        $this->helper->addTimezone($timezone, $end_datetime);

        // If this is a date_all_day field, pull the all day option from that.
        if($date_all_day = false) {
          // TODO: implement
        }
        else {
          if (isset($field_mapping['no_time_field']) && $field_mapping['no_time_field'] != 'none') {
            $all_day = $entity->get($field_mapping['no_time_field'])->getValue();
            if ($all_day && isset($all_day[0]['value']) && $all_day[0]['value']) {
              $event->setNoTime(true);
            }
          }
        }
      }
      //else {
      // is DTEND is not a required field, but if it is not included, nor
      // is duration (which we are not using here), then the event's duration
      // is taken to be one day. But do we need to explicitly define that here?
      // Do calendar apps handle that? https://tools.ietf.org/html/rfc5545#section-3.6.1
      //}

      $events[] = $event;
    }
  }


  /**
   * Create an event based on a datetime field
   *
   * @param array $events
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param \DateTimeZone $timezone
   * @param array $field_mapping
   */
  public function addDateTimeEvent(array &$events, ResultRow $row, \DateTimeZone $timezone, array $field_mapping): void {

    $entity = $this->getEntity($row);

    $utc_timezone = new \DateTimeZone('UTC');
    $datefield_values = $entity->get($field_mapping['date_field'])->getValue();

    // If an end date field was defined, then the content model is most likely
    // using two, single cardinality fields for a start and an end date.
    if (isset($field_mapping['end_date_field']) && $field_mapping['end_date_field'] != 'none') {

      // generate the event
      $event = $this->createDefaultEvent($entity, $field_mapping, $row);

      // set the start time.
      $date_entry = $datefield_values[0];
      $start_datetime = new \DateTime($date_entry['value'], $utc_timezone);
      $start_datetime->setTimezone($timezone);
      $event->setDtStart($start_datetime);
      $this->helper->addTimezone($timezone, $start_datetime);

      // Set the end time
      $end_date_field_values = $entity->get($field_mapping['end_date_field'])->getValue();
      $end_date_entry = $end_date_field_values[0];
      $end_datetime = new \DateTime($end_date_entry['value'], $utc_timezone);
      $end_datetime->setTimezone($timezone);
      $event->setDtEnd($end_datetime);
      $this->helper->addTimezone($timezone, $end_datetime);

      // All day events.
      if (isset($field_mapping['no_time_field']) && $field_mapping['no_time_field'] != 'none') {
        $all_day = $entity->get($field_mapping['no_time_field'])->getValue();
        if ($all_day && isset($all_day[0]['value']) && $all_day[0]['value']) {
          $event->setNoTime(TRUE);
        }
      }
      $events[] = $event;
    }


  }


  /**
   * Create an event based on a smartdate field.
   *
   * @param array $events
   *   The array of events that will be output.
   * @param \Drupal\views\ResultRow $row
   *   The views result being processed.
   * @param \DateTimeZone $timezone
   *   A timezone object to use for output.
   * @param array $fieldMapping
   *   An array of mappings to specify which entity fields to use for output.
   */
  public function addSmartDateEvent(array &$events, ResultRow $row, \DateTimeZone $timezone, array $fieldMapping): void {

    $entity = $this->getEntity($row);

    $datefieldValues = $entity->get($fieldMapping['date_field'])->getValue();
    $processed_rules = [];

    foreach ($datefieldValues as $delta => $datefieldValue) {
      $dateValue = $datefieldValues[$delta]['value'];
      $dateEndValue = $datefieldValues[$delta]['end_value'];
      $dateRrule = $datefieldValues[$delta]['rrule'];
      $dateTZ = !empty($datefieldValues[$delta]['timezone']) ? new \DateTimeZone($datefieldValues[$delta]['timezone']) : $timezone;
      if (in_array($dateRrule, $processed_rules)) {
        continue;
      }

      // Generate the event.
      $event = $this->createDefaultEvent($entity, $fieldMapping, $row);

      // Set the start time.
      $startDatetime = new \DateTime();
      $startDatetime->setTimestamp(trim($dateValue));
      $startDatetime->setTimezone($dateTZ);
      $event->setDtStart($startDatetime);
      $this->helper->addTimezone($timezone, $startDatetime);

      // Set the end time.
      $endDatetime = new \DateTime();
      $endDatetime->setTimestamp(trim($dateEndValue));
      $endDatetime->setTimezone($dateTZ);
      $event->setDtEnd($endDatetime);
      $this->helper->addTimezone($timezone, $endDatetime);

      // Can the date be considered all-day?
      if (SmartDateTrait::isAllDay($startDatetime->getTimestamp(), $endDatetime->getTimestamp(), $dateTZ)) {
        $event->setNoTime(TRUE);
      }

      // Determine recurring rules.
      if ($dateRrule) {
        $smartDateRrule = SmartDateRule::load($dateRrule);
        // Gets the rule text.
        $recurRuleObject = $smartDateRrule->getAssembledRule();
        // eluceo/ical has functionality to support recurring rules, however,
        // that appears to require that we parse our already ical formatted rule
        // into components, and feed them into methods to set each one.
        // So for us it's more convenient to set it as a custom property.
        $icalRrule = new RecurrenceRule();
        if ($recurRuleObject->getFreq()) {
          $freqId = $recurRuleObject->getFreq();
          // Recurr/Rule stores freqs as numbers 0 - 6. We need to convert it
          // back to a string. There has to be something that does this for us...
          $freqsArray = [
            0 => 'YEARLY',
            1 => 'MONTHLY',
            2 => 'WEEKLY',
            3 => 'DAILY',
            4 => 'HOURLY',
            5 => 'MINUTELY',
            6 => 'SECONDLY'
          ];
          $freq = $freqsArray[$freqId];
          $icalRrule->setFreq($freq);
        }
        if ($recurRuleObject->getInterval()) {
          $icalRrule->setInterval($recurRuleObject->getInterval());
        }
        if ($recurRuleObject->getCount()) {
          $icalRrule->setCount($recurRuleObject->getCount());
        }
        if ($recurRuleObject->getByDay()) {
          $icalRrule->setByDay(implode(',', $recurRuleObject->getByDay()));
        }
        if ($recurRuleObject->getUntil()) {
          $icalRrule->setUntil($recurRuleObject->getUntil());
        }
        $event->addRecurrenceRule($icalRrule);
      }
      $events[] = $event;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addEvent(array &$events, ResultRow $row, \DateTimeZone $timezone, array $field_mapping): void {
    // All code moved to field-specific methods.
  }




  /**
   * {@inheritdoc}
   */
  public function addDateRecurEvent(array &$events, ResultRow $row, \DateTimeZone $timezone, array $field_mapping): void {
    /** @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem[] $field_items */
    $entity = $this->getEntity($row);
    $field_items = $entity->{$field_mapping['date_field']};

    foreach ($field_items as $index => $item) {
      /** @var \Drupal\date_recur\DateRange[] $occurrences */
      $occurrences = $item->getHelper()->getOccurrences();

      $future_events = [];

      foreach ($occurrences as $occurrence) {
        $event = $this->createDefaultEvent($entity, $field_mapping, $row);

        /** @var \DateTime $start_datetime */
        $start_datetime = $occurrence->getStart();
        $start_datetime->setTimezone($timezone);
        $event->setDtStart($start_datetime);
        $this->helper->addTimezone($timezone, $start_datetime);

        /** @var \DateTime $end_datetime */
        $end_datetime = $occurrence->getEnd();
        $end_datetime->setTimezone($timezone);
        $event->setDtEnd($end_datetime);
        $this->helper->addTimezone($timezone, $end_datetime);
        $current_date = date_create();

        // Only include future occurrences and only the first one because we will rely on rrules.
        if ($start_datetime > $current_date) {
          $future_events[] = $event;
        }
      }
    }

    // We only want the soonest upcoming date in the case of recurring dates.
    array_reverse($future_events);

    $events[] = array_shift($future_events);

  }

  /**
   * Gets the entity for a corresponding row.
   *
   * @param \Drupal\views\ResultRow $row
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  public function getEntity($row) {
    if ($this->view->storage->get('base_table') == 'node_field_data') {
      // TODO, Change how this is being accessed so it's not using private properties
      $entity = $row->_entity;
    }
    else if ($this->view->storage->get('base_table') == 'search_api_index_default_content_index') {
      $entity = $row->_object->getValue();
    }
    else {
      throw new \Exception('Base table type not supported. At the moment, Views iCal only supports nodes and Search API indexes');
    }

    return $entity;

  }

  /**
   * Gets the renderer services.
   * @return \Drupal\Core\Render\RendererInterface|mixed
   */
  protected function getRenderer() {
    if (!isset($this->renderer)) {
      $this->renderer = \Drupal::service('renderer');
    }

    return $this->renderer;
  }
}
