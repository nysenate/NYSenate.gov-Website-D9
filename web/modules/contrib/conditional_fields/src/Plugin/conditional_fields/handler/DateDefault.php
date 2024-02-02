<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;
use Drupal\conditional_fields\ConditionalFieldsInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Provides states handler for date combos.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_datetime_default",
 * )
 */
class DateDefault extends ConditionalFieldsHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options) {

    $state = [];
    $value = $this->getWidgetValue($options['value_form']);
    $date_obj = new DrupalDateTime($value);
    switch ($options['values_set']) {
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
        // Just split DATETIME_DATETIME_STORAGE_FORMAT on date and time.
        $date = $date_obj->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);
        // @todo Support time.
        // Need to check selector and create one more state for it.
        // $time = $date_obj->tesformat('H:i:s');.
        $state[$options['state']][$options['selector']]['value'] = $date;
        break;

      default:
        break;
    }
    return $state;
  }

}
