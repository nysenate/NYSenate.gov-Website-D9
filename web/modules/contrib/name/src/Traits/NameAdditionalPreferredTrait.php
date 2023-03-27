<?php

namespace Drupal\name\Traits;

use Drupal\Core\Form\FormStateInterface;

/**
 * Name form for preferred and alternative settings trait.
 */
trait NameAdditionalPreferredTrait {

  /**
   * Gets the default settings for alternative and preferred fields.
   *
   * @return array
   *   Default settings.
   */
  protected static function getDefaultAdditionalPreferredSettings() {
    return [
      "preferred_field_reference" => "",
      "preferred_field_reference_separator" => ", ",
      "alternative_field_reference" => "",
      "alternative_field_reference_separator" => ", ",
    ];
  }

  /**
   * Returns a form for the default settings defined above.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   *
   * @return array
   *   The form definition for the field settings.
   */
  protected function getNameAdditionalPreferredSettingsForm(array &$form, FormStateInterface $form_state) {
    $elements = [];
    $elements['preferred_field_reference'] = [
      '#type' => 'select',
      '#title' => $this->t('Preferred component source'),
      '#default_value' => $this->getSetting('preferred_field_reference'),
      '#empty_option' => $this->getEmptyOption(),
      '#options' => $this->getAdditionalSources(),
      '#description' => $this->t('A data source to use as the preferred given name within the name formats. A common use-case would be for a users nickname.<br>i.e. "q" and "v", plus the conditional "p", "d" and "D" name format options.'),
    ];
    $elements['preferred_field_reference_separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Preferred component source multivalue separator'),
      '#default_value' => $this->getSetting('preferred_field_reference_separator'),
      '#description' => $this->t('Used to separate multi-value items in an inline list.'),
      '#states' => [
        'invisible' => [
          ':input[name$="[preferred_field_reference]"]' => ['value' => ''],
        ],
      ],
    ];

    $elements['alternative_field_reference'] = [
      '#type' => 'select',
      '#title' => $this->t('Alternative component source'),
      '#default_value' => $this->getSetting('alternative_field_reference'),
      '#empty_option' => $this->getEmptyOption(),
      '#options' => $this->getAdditionalSources(),
      '#description' => $this->t('A data source to use as the alternative component within the name formats. Possible use-cases include; providing a custom fully formatted name alternative to use in citations; a separate field for a users creditatons / post-nominal letters.<br>i.e. "a" and "A" name format options.'),
    ];
    $elements['alternative_field_reference_separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Alternative component source multivalue separator'),
      '#default_value' => $this->getSetting('alternative_field_reference_separator'),
      '#description' => $this->t('Used to separate multi-value items in an inline list.'),
      '#states' => [
        'invisible' => [
          ':input[name$="[alternative_field_reference]"]' => ['value' => ''],
        ],
      ],
    ];

    return $elements;
  }

  protected function settingsNameAdditionalPreferredSummary(&$summary) {
    if ($type = $this->getSetting('preferred_field_reference')) {
      $targets = $this->getAdditionalSources();
      $summary[] = $this->t('Preferred: @label', [
        '@label' => empty($targets[$type]) ? t('-- invalid --') : $targets[$type],
      ]);
    }
    elseif (!$this->getTraitUsageIsField()) {
      if ($type = $this->fieldDefinition->getSetting('preferred_field_reference')) {
        $targets = $this->getAdditionalSources();
        $summary[] = $this->t('Preferred: field default (@label)', [
          '@label' => empty($targets[$type]) ? t('-- invalid --') : $targets[$type],
        ]);
      }
      else {
        $summary[] = $this->t('Preferred: field default (-- none --)');
      }
    }
    if ($type = $this->getSetting('alternative_field_reference')) {
      $targets = $this->getAdditionalSources();
      $summary[] = $this->t('Alternative: @label', [
        '@label' => empty($targets[$type]) ? t('-- invalid --') : $targets[$type],
      ]);
    }
    elseif (!$this->getTraitUsageIsField()) {
      if ($type = $this->fieldDefinition->getSetting('alternative_field_reference')) {
        $targets = $this->getAdditionalSources();
        $summary[] = $this->t('Alternative: field default (@label)', [
          '@label' => empty($targets[$type]) ? t('-- invalid --') : $targets[$type],
        ]);
      }
      else {
        $summary[] = $this->t('Alternative: field default (-- none --)');
      }
    }
  }

  /**
   * Helper function to find attached fields to use as alternative sources.
   *
   * Currently field items do not support dependencies injected.
   *
   * To refactor once https://www.drupal.org/node/2053415 gets in.
   *
   * @return array
   *   The discovered additional sources.
   */
  protected function getAdditionalSources() {
    if (!isset($this->entityTypeManager)) {
      $this->entityTypeManager = \Drupal::service('entity_type.manager');
    }
    if (!isset($this->entityFieldManager)) {
      $this->entityFieldManager = \Drupal::service('entity_field.manager');
    }
    // If a formatter, $this->fieldDefinition is set, otherwise we have a field.
    $field_definition = empty($this->fieldDefinition) ? $this->getFieldDefinition() : $this->fieldDefinition;
    $entity_type_id = $field_definition->getTargetEntityTypeId();
    $entity_type = $this->entityTypeManager
      ->getStorage($entity_type_id)
      ->getEntityType();
    $bundle = $field_definition->getTargetBundle();
    $entity_type_label = $entity_type->getBundleLabel();
    if (!$entity_type_label) {
      $entity_type_label = $entity_type->getLabel();
    }
    $sources = [
      '_self' => $this->t('@label label', ['@label' => $entity_type_label]),
    ];
    if ($entity_type_id == 'user') {
      $sources['_self_property_name'] = $this->t('@label login name', ['@label' => $entity_type_label]);
    }
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    foreach ($fields as $field_name => $field) {
      if (!$field->getFieldStorageDefinition()->isBaseField() && $field_name != $field_definition->getName()) {
        $sources[$field->getName()] = $field->getLabel();
      }
    }
    return $sources;
  }

  protected function getEmptyOption() {
    if ($this->getTraitUsageIsField()) {
      return $this->t('-- none --');
    }
    else {
      return $this->t('-- field default --');
    }
  }

  protected function getTraitUsageIsField() {
    return is_subclass_of($this, 'Drupal\Core\Field\FieldItemBase');
  }

}
