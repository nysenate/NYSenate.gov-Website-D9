<?php

/**
 * @file
 * Hooks for the conditional_fields module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Build a list of available fields.
 *
 * Fields that use the Field API should be available to Conditional Fields
 * automatically. This hook provides a mechanism to register pseudo-fields
 * (such as those provided by Field Group.)
 *
 * @param string $entity_type
 *   Name of the entity type being configured.
 * @param string $bundle_name
 *   Name of the entity bundle being configured.
 *
 * @return array
 *   Fields provided by this module, keyed by machine name, with field labels as
 *   values.
 *
 * @see ConditionalFieldForm::getFields()
 * @see hook_conditional_fields_alter()
 * @see conditional_fields_conditional_fields()
 */
function hook_conditional_fields($entity_type, $bundle_name) {
  $fields = [];
  $groups = field_group_info_groups($entity_type, $bundle_name, 'form', 'default');
  foreach ($groups as $name => $group) {
    $fields[$name] = $group->label;
  }
  return $fields;
}

/**
 * Alter the list of available fields.
 *
 * @param string &$fields
 *   Fields available to this module, provided by modules that implement
 *   hook_conditional_fields().
 * @param string $entity_type
 *   Name of the entity type being configured.
 * @param string $bundle_name
 *   Name of the entity bundle being configured.
 *
 * @see ConditionalFieldForm::getFields()
 * @see hook_conditional_fields()
 * @see conditional_fields_conditional_fields_alter()
 */
function hook_conditional_fields_alter(&$fields, $entity_type, $bundle_name) {
  asort($fields);
}

/**
 * Return a list of fields contained within a given field.
 *
 * Various modules provide fields that themselves contain other fields (e.g.,
 * Field Group, Paragraphs, etc.) This hook allows those modules to provide the
 * logic necessary to determine which fields are contained within such a field.
 *
 * @param string $entity_type
 *   Name of the entity type being configured.
 * @param string $bundle_name
 *   Name of the entity bundle being configured.
 *
 * @return array
 *   Keys are parent fields, values are lists of children.
 *
 * @see DependencyHelper::getInheritingFieldNames()
 * @see hook_conditional_fields_children_alter()
 * @see field_group_conditional_fields_children()
 */
function hook_conditional_fields_children($entity_type, $bundle_name) {
  $groups = [];
  $group_info = field_group_info_groups($entity_type, $bundle_name, 'form', 'default');
  foreach ($group_info as $name => $info) {
    $groups[$name] = $info->children;
  }
  return $groups;
}

/**
 * Alter the list of fields contained within a given field.
 *
 * @param string &$fields
 *   Fields provided by hook_conditional_fields_children().
 * @param string $entity_type
 *   Name of the entity type being configured.
 * @param string $bundle_name
 *   Name of the entity bundle being configured.
 * @param string $field
 *   Name of the parent field to check for children fields.
 *
 * @see DependencyHelper::getInheritingFieldNames()
 * @see hook_conditional_fields_children()
 */
function hook_conditional_fields_children_alter(&$fields, $entity_type, $bundle_name, $field) {
  // Do something with the child fields.
}

/**
 * @} End of "addtogroup hooks".
 */
