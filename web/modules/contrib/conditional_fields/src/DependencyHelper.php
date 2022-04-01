<?php

namespace Drupal\conditional_fields;

/**
 * Resolve conditional field's dependencies.
 */
class DependencyHelper {

  /**
   * The current entity type.
   *
   * @var string
   */
  protected $entity_type;

  /**
   * The current bundle name.
   *
   * @var string
   */
  protected $bundle;

  /**
   * Name of the current dependent field.
   *
   * @var string
   */
  protected $dependent;

  /**
   * The current dependent field.
   *
   * @var array
   */
  protected $dependent_field;

  /**
   * Full list of dependencies.
   *
   * @var array
   */
  protected $dependencies;

  /**
   * Dependent field definitions.
   *
   * @var array
   */
  protected $dependent_fields;

  /**
   * UUID of the current dependency.
   *
   * @var string
   */
  protected $uuid;

  /**
   * Name of the current dependee field.
   *
   * @var string
   */
  protected $dependee;

  /**
   * Options that define the current dependency.
   *
   * @var array
   */
  protected $settings;

  /**
   * Fields attached to a bundle's default form display.
   *
   * @var array
   */
  protected $form_fields;

  /**
   * Fields that support inheritance.
   *
   * @var array
   */
  protected $inheriting_fields;

  /**
   * Constructor method.
   *
   * @param string $entity_type
   *   An entity type name.
   * @param string $bundle
   *   A bundle name.
   */
  public function __construct(string $entity_type, string $bundle) {
    $this->entity_type = $entity_type;
    $this->bundle = $bundle;
    $this->module_handler = \Drupal::moduleHandler();
  }

  /**
   * Return a list of names of available fields.
   */
  public function getAvailableConditionalFields() {
    $hook = 'conditional_fields';
    $fields = $this->module_handler
      ->invokeAll($hook, [$this->entity_type, $this->bundle]);
    $this->module_handler
      ->alter($hook, $fields, $this->entity_type, $this->bundle);
    return $fields;
  }

  /**
   * Return dependencies for a given bundle.
   */
  public function getBundleDependencies() {
    if (!isset($this->dependencies[$this->entity_type][$this->bundle])) {
      $this->resolveBundleDependencies($this->getBundleDependentFields());
    }
    return $this->dependencies ? $this->dependencies[$this->entity_type][$this->bundle] : NULL;
  }

  /**
   * Determine whether a field contains other fields.
   */
  public function fieldHasChildren($field) {
    return (bool) count($this->getInheritingFieldNames($field));
  }

  /**
   * Resolve a bundle's dependencies.
   */
  protected function resolveBundleDependencies($dependent_fields) {
    foreach ($dependent_fields as $dependent => $field) {
      $this->dependent = $dependent;
      $this->dependent_field = $field;
      $this->resolveFieldDependencies();
    }
  }

  /**
   * Resolve a field's dependencies.
   */
  protected function resolveFieldDependencies() {
    foreach ($this->dependent_field['third_party_settings']['conditional_fields'] as $uuid => $conditional_field) {
      $this->uuid = $uuid;
      $this->dependee = $conditional_field['dependee'];
      $this->settings = $conditional_field['settings'];
      if ($this->fieldDependencyShouldPropagate()) {
        if ($this->fieldDependencyShouldApplyToParent()) {
          $this->registerFieldDependency();
        }
        $this->resolveBundleDependencies($this->getInheritingFields());
        continue;
      }
      $this->registerFieldDependency();
    }
  }

  /**
   * Determine whether a field dependency should be inherited.
   */
  protected function fieldDependencyShouldPropagate() {
    if (!isset($this->settings['inheritance']['propagate'])) {
      return FALSE;
    }
    return (bool) $this->settings['inheritance']['propagate'];
  }

  /**
   * Determine whether a field dependency should be apply to the parent field.
   */
  protected function fieldDependencyShouldApplyToParent() {
    if (!isset($this->settings['inheritance']['apply_to_parent'])) {
      return FALSE;
    }
    return (bool) $this->settings['inheritance']['apply_to_parent'];
  }

  /**
   * Determine whether a field dependency should be apply to the parent field.
   */
  protected function fieldDependencyShouldRecurse() {
    if (!isset($this->settings['inheritance']['recurse'])) {
      return FALSE;
    }
    return (bool) $this->settings['inheritance']['recurse'];
  }

  /**
   * Return fields with conditional settings to inherit.
   */
  protected function getInheritingFields() {
    if (empty($this->dependent_field['third_party_settings']['conditional_fields'][$this->uuid])) {
      return [];
    }

    $propagating_settings = $this->dependent_field['third_party_settings']['conditional_fields'][$this->uuid];
    $inheriting_fields = [];
    foreach ($this->getInheritingFieldNames($this->dependent) as $field_name) {
      $inheriting_field = $this->getBundleFormField($field_name);
      $new_id = "{$this->uuid}+{$field_name}";
      $inheriting_field['third_party_settings']['conditional_fields'][$new_id] = $propagating_settings;
      if (!$this->fieldHasChildren($field_name) || !$this->fieldDependencyShouldRecurse()) {
        unset($inheriting_field['third_party_settings']['conditional_fields'][$new_id]['settings']['inheritance']);
      }
      $inheriting_fields[$field_name] = $inheriting_field;
    }
    return $inheriting_fields;
  }

  /**
   * Return a list of fields to inherit conditional settings.
   */
  protected function getInheritingFieldNames($parent_field) {
    if (!isset($this->inheriting_fields)) {
      $this->inheriting_fields = $this->getInheritingChildren();
    }
    if (!isset($this->inheriting_fields[$parent_field])) {
      return [];
    }
    return $this->inheriting_fields[$parent_field];
  }

  /**
   * Determine all fields that support inheritence, and their children.
   */
  protected function getInheritingChildren() {
    $hook = 'conditional_fields_children';
    $inheriting_fields = $this->module_handler
      ->invokeAll($hook, [$this->entity_type, $this->bundle]);
    $this->module_handler
      ->alter($hook, $inheriting_fields, $this->entity_type, $this->bundle);
    return $inheriting_fields;
  }

  /**
   * Register a specific conditional field dependency.
   */
  protected function registerFieldDependency() {
    $this->registerDependent();
    $this->registerDependee();
  }

  /**
   * Add a dependent field to the list of dependencies.
   */
  protected function registerDependent() {
    $this->dependencies[$this->entity_type][$this->bundle]['dependents'][$this->dependent][$this->uuid] = [
      'dependee' => $this->dependee,
      'options' => $this->settings,
    ];
  }

  /**
   * Add a dependee field to the list of dependencies.
   */
  protected function registerDependee() {
    $this->dependencies[$this->entity_type][$this->bundle]['dependees'][$this->dependee][$this->uuid] = [
      'dependent' => $this->dependent,
      'options' => $this->settings,
    ];
  }

  /**
   * Return all dependent fields attached to a bundle.
   */
  protected function getBundleDependentFields() {
    if (!$this->bundleHasRegisteredDependentFields()) {
      $this->registerBundleDependentFields();
    }
    return $this->dependent_fields[$this->entity_type][$this->bundle];
  }

  /**
   * Determine whether a bundle has registered any dependent fields.
   */
  protected function bundleHasRegisteredDependentFields() {
    if (!isset($this->dependent_fields[$this->entity_type][$this->bundle])) {
      return FALSE;
    }
    if (empty($this->dependent_fields[$this->entity_type][$this->bundle])) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Register all dependent fields attached to a bundle.
   */
  protected function registerBundleDependentFields() {
    $this->dependent_fields[$this->entity_type][$this->bundle] = [];
    foreach ($this->getBundleFormFields() as $name => $field) {
      if (!$this->hasConditionalFields($field)) {
        continue;
      }
      $this->dependent_fields[$this->entity_type][$this->bundle][$name] = $field;
    }
  }

  /**
   * Return a field attached to a bundle.
   */
  protected function getBundleFormField($field_name) {
    if (!isset($this->form_fields)) {
      $this->form_fields = $this->getBundleFormFields();
    }
    if (!isset($this->form_fields[$field_name])) {
      return [];
    }
    return $this->form_fields[$field_name];
  }

  /**
   * Return all fields attached to a bundle.
   */
  protected function getBundleFormFields() {
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity */
    $entity = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load($this->entity_type . '.' . $this->bundle . '.default');

    if (!$entity) {
      return [];
    }

    return $entity->getComponents();
  }

  /**
   * Determine whether a field has any conditional fields defined.
   */
  protected function hasConditionalFields($field) {
    return !empty($field['third_party_settings']['conditional_fields']);
  }

}
