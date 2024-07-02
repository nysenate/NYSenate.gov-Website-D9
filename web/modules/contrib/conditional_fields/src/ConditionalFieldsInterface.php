<?php

namespace Drupal\conditional_fields;

/**
 * Provides an interface.
 */
interface ConditionalFieldsInterface {

  /**
   * Dependency is triggered if the dependee has a certain value.
   */
  const CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET = 1;

  /**
   * Dependency is triggered if the dependee has all values.
   */
  const CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND = 2;

  /**
   * Dependency is triggered if the dependee has any of the values.
   */
  const CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR = 3;

  /**
   * Dependency is triggered if the dependee has only one of the values.
   */
  const CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR = 4;

  /**
   * Dependency is triggered if the dependee does not have any of the values.
   */
  const CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT = 5;

  /**
   * Dependency is triggered if the dependee values match a regular expression.
   */
  const CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX = 6;

  /**
   * Field view setting.
   *
   * Dependent is shown only if the dependency is triggered.
   */
  const CONDITIONAL_FIELDS_FIELD_VIEW_EVALUATE = 1;

  /**
   * Field view setting.
   *
   * Dependent is shown only if the dependee is shown as well.
   */
  const CONDITIONAL_FIELDS_FIELD_VIEW_HIDE_ORPHAN = 2;

  /**
   * Field view setting.
   *
   * Dependent is highlighted if the dependency is not triggered.
   */
  const CONDITIONAL_FIELDS_FIELD_VIEW_HIGHLIGHT = 3;

  /**
   * Field view setting.
   *
   * Dependent has a textual description of the dependency.
   */
  const CONDITIONAL_FIELDS_FIELD_VIEW_DESCRIBE = 4;

  /**
   * Field view setting.
   *
   * Dependent is shown only if the dependee is shown as well
   * and the dependency evaluates to TRUE.
   */
  const CONDITIONAL_FIELDS_FIELD_VIEW_HIDE_UNTRIGGERED_ORPHAN = 5;

  /**
   * Field edit setting.
   *
   * Dependent is shown only if the dependee is shown as well.
   */
  const CONDITIONAL_FIELDS_FIELD_EDIT_HIDE_ORPHAN = 1;

  /**
   * Field edit setting.
   *
   * Dependent is shown only if the dependee is shown as well
   * and the dependency evaluates to TRUE.
   */
  const CONDITIONAL_FIELDS_FIELD_EDIT_HIDE_UNTRIGGERED_ORPHAN = 2;

  /**
   * Field edit setting.
   *
   * Dependent is reset to its default values if the
   * dependency was not triggered when the form is submitted.
   */
  const CONDITIONAL_FIELDS_FIELD_EDIT_RESET_UNTRIGGERED = 3;

}
