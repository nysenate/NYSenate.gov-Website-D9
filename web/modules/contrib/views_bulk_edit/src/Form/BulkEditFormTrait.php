<?php

namespace Drupal\views_bulk_edit\Form;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Common methods for Views Bulk Edit forms.
 */
trait BulkEditFormTrait {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Builds the bundle forms.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param array $bundle_data
   *   An array with all entity types and their bundles.
   *
   * @return array
   *   The bundle forms.
   */
  public function buildBundleForms(array $form, FormStateInterface $form_state, array $bundle_data) {

    // Store entity data.
    $form_state->set('vbe_entity_bundles_data', $bundle_data);

    $form['#attributes']['class'] = ['views-bulk-edit-form'];
    $form['#attached']['library'][] = 'views_bulk_edit/views_bulk_edit.edit_form';

    $bundle_count = 0;
    foreach ($bundle_data as $entity_type_id => $bundles) {
      foreach ($bundles as $bundle => $label) {
        $bundle_count++;
      }
    }

    foreach ($bundle_data as $entity_type_id => $bundles) {

      foreach ($bundles as $bundle => $label) {
        $form = $this->getBundleForm($entity_type_id, $bundle, $label, $form, $form_state, $bundle_count);
      }
    }

    return $form;
  }

  /**
   * Gets the form for this entity display.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle ID.
   * @param mixed $bundle_label
   *   Bundle label.
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state object.
   * @param int $bundle_count
   *   Number of bundles that may be affected.
   *
   * @return array
   *   Edit form for the current entity bundle.
   */
  protected function getBundleForm($entity_type_id, $bundle, $bundle_label, array $form, FormStateInterface $form_state, $bundle_count) {
    $entityType = $this->entityTypeManager->getDefinition($entity_type_id);
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->create([
      $entityType->getKey('bundle') => $bundle,
    ]);

    if (!isset($form[$entity_type_id])) {
      $form[$entity_type_id] = [
        '#type' => 'container',
        '#tree' => TRUE,
      ];
    }

    // If there is no bundle label, the entity has no bundles.
    if (empty($bundle_label)) {
      $bundle_label = $entityType->getLabel();
    }
    $form[$entity_type_id][$bundle] = [
      '#type' => 'details',
      '#open' => ($bundle_count === 1),
      '#title' => $entityType->getLabel() . ' - ' . $bundle_label,
      '#parents' => [$entity_type_id, $bundle],
    ];

    $form_display = EntityFormDisplay::collectRenderDisplay($entity, 'bulk_edit');
    $form_display->buildForm($entity, $form[$entity_type_id][$bundle], $form_state);

    $form[$entity_type_id][$bundle] += $this->getSelectorForm($entity_type_id, $bundle, $form[$entity_type_id][$bundle]);
    $form[$entity_type_id][$bundle] += $this->getRevisionForm($entity);

    return $form;
  }

  /**
   * Builds the revision form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The fake entity.
   *
   * @return array
   *   The revision form.
   */
  protected function getRevisionForm(EntityInterface $entity) {
    $revision_form = [];

    if (
      $entity instanceof RevisionLogInterface &&
      !empty($revision_key = $entity->getEntityType()->getKey('revision')) &&
      $entity->get($revision_key)->access('update')
    ) {
      $new_revision_default = $this->getNewRevisionDefault($entity);

      $revision_form['revision_information'] = [
        '#type' => 'fieldset',
        '#weight' => 200,
        '#title' => $this->t('Revision information'),
      ];

      $revision_form['revision_information']['revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => $new_revision_default,
      ];

      $revision_form['revision_information']['revision_log'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Revision log message'),
        '#description' => $this->t('Briefly describe the changes you have made.'),
        '#states' => [
          'visible' => [
            sprintf(':input[name="%s[%s][revision_information][revision]"]', $entity->getEntityTypeId(), $entity->bundle()) => ['checked' => TRUE],
          ],
        ],
      ];
    }
    return $revision_form;
  }

  /**
   * Should new revisions be created by default?
   *
   * @return bool
   *   As in the title.
   *
   * @see \Drupal\Core\Entity\ContentEntityForm::getNewRevisionDefault()
   */
  protected function getNewRevisionDefault($entity) {
    $new_revision_default = FALSE;
    $bundle_entity = $this->getBundleEntity($entity);
    if ($bundle_entity instanceof RevisionableEntityBundleInterface) {
      // Always use the default revision setting.
      $new_revision_default = $bundle_entity->shouldCreateNewRevision();
    }
    return $new_revision_default;
  }

  /**
   * Returns the bundle entity of the entity, or NULL if there is none.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The bundle entity.
   *
   * @see \Drupal\Core\Entity\ContentEntityForm::getBundleEntity()
   */
  protected function getBundleEntity($entity) {
    if ($bundle_entity_type = $entity->getEntityType()->getBundleEntityType()) {
      return $this->entityTypeManager->getStorage($bundle_entity_type)->load($entity->bundle());
    }
    return NULL;
  }

  /**
   * Builds the selector form.
   *
   * Given an entity form, create a selector form to provide options to update
   * values.
   *
   * @param string $entity_type_id
   *   Entity type ID.
   * @param string $bundle
   *   The bundle machine name.
   * @param array $form
   *   The form we're building the selection options for.
   *
   * @return array
   *   The new selector form.
   */
  protected function getSelectorForm($entity_type_id, $bundle, array &$form) {
    $selector['_field_selector'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Select fields to change'),
      '#weight' => -50,
      '#tree' => TRUE,
      '#attributes' => ['class' => ['vbe-selector-fieldset']],
    ];

    $definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);

    foreach (Element::children($form) as $key) {
      if (isset($form[$key]['#access']) && !$form[$key]['#access']) {
        continue;
      }
      if ($key == '_field_selector' || !$element = &$this->findFormElement($form[$key])) {
        continue;
      }

      if (!$definitions[$key]->isDisplayConfigurable('form')) {
        $element['#access'] = FALSE;
        continue;
      }

      // Modify the referenced element a bit so it doesn't
      // cause errors and returns correct data structure.
      $element['#required'] = FALSE;
      $element['#tree'] = TRUE;

      // Add the toggle field to the form.
      $selector['_field_selector'][$key] = [
        '#type' => 'checkbox',
        '#title' => $element['#title'],
        '#weight' => isset($form[$key]['#weight']) ? $form[$key]['#weight'] : 0,
        '#tree' => TRUE,
      ];

      // Force the original value to be hidden unless the checkbox is enabled.
      $form[$key]['#states'] = [
        'visible' => [
          sprintf('[name="%s[%s][_field_selector][%s]"]', $entity_type_id, $bundle, $key) => ['checked' => TRUE],
        ],
      ];

      // Add options.
      $options = [];
      $options['replace'] = $this->t('Replace the current value');
      if (in_array($definitions[$key]->getType(), [
        'string',
        'string_long',
        'text',
        'text_long',
      ])) {
        $options['append'] = $this->t('Append to the current value');
      }
      if ($definitions[$key]->getFieldStorageDefinition()->getCardinality() !== 1) {
        $options['new'] = $this->t('Add a new value to the multivalue field');
      }

      $option_keys = array_keys($options);
      $form["{$key}_change_method"] = [
        '#title' => $this->t('Change method'),
        '#type' => count($options) > 1 ? 'radios' : 'hidden',
        '#options' => $options,
        '#default_value' => reset($option_keys),
        '#states' => count($options) > 1 ? $form[$key]['#states'] : [],
        '#weight' => isset($form[$key]['#weight']) ? $form[$key]['#weight'] + 0.01 : 0,
      ];
    }

    if (empty(Element::children($selector['_field_selector']))) {
      $selector['_field_selector']['#title'] = $this->t('There are no fields available to modify');
    }

    return $selector;
  }

  /**
   * Finds the deepest most form element and returns it.
   *
   * @param array $form
   *   The form element we're searching.
   * @param string $title
   *   The most recent non-empty title from previous form elements.
   *
   * @return array|null
   *   The deepest most element if we can find it.
   */
  protected function &findFormElement(array &$form, $title = NULL) {
    $element = NULL;
    foreach (Element::children($form) as $key) {
      // Not all levels have both #title and #type.
      // Attempt to inherit #title from previous iterations.
      // Some #titles are empty strings.  Ignore them.
      if (!empty($form[$key]['#title'])) {
        $title = $form[$key]['#title'];
      }
      elseif (!empty($form[$key]['title']['#value']) && !empty($form[$key]['title']['#type']) && $form[$key]['title']['#type'] === 'html_tag') {
        $title = $form[$key]['title']['#value'];
      }
      if (isset($form[$key]['#type']) && !empty($title)) {
        // Fix empty or missing #title in $form.
        if (empty($form[$key]['#title'])) {
          $form[$key]['#title'] = $title;
        }
        $element = &$form[$key];
        break;
      }
      elseif (is_array($form[$key])) {
        $element = &$this->findFormElement($form[$key], $title);
      }
    }
    return $element;
  }

  /**
   * Provides same functionality as ARRAY_FILTER_USE_KEY for PHP 5.5.
   *
   * @param array $array
   *   The array of data to filter.
   * @param callable $callback
   *   The function we're going to use to determine the filtering.
   *
   * @return array
   *   The filtered data.
   */
  protected function filterOnKey(array $array, callable $callback) {
    $filtered_values = [];
    foreach ($array as $key => $value) {
      if ($callback($key)) {
        $filtered_values[$key] = $value;
      }
    }
    return $filtered_values;
  }

  /**
   * Save modified entity field values to action configuration.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state object.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $bundle_data = $storage['vbe_entity_bundles_data'];

    foreach ($bundle_data as $entity_type_id => $bundles) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      foreach ($bundles as $bundle => $label) {
        $field_data = $form_state->getValue([$entity_type_id, $bundle]);
        $modify = array_filter($field_data['_field_selector']);
        if (!empty($modify)) {
          $form_clone = $form;
          $form_clone['#parents'] = [$entity_type_id, $bundle];
          $entity = $this->entityTypeManager->getStorage($entity_type_id)->create([
            $entity_type->getKey('bundle') => $bundle,
          ]);
          $form_display = EntityFormDisplay::collectRenderDisplay($entity, 'bulk_edit');
          $form_display->extractFormValues($entity, $form_clone, $form_state);

          foreach (array_keys($modify) as $field) {
            $this->configuration[$entity_type_id][$bundle]['values'][$field] = $entity->{$field}->getValue();
            $this->configuration[$entity_type_id][$bundle]['change_method'][$field] = $field_data["{$field}_change_method"];
          }
          $this->configuration[$entity_type_id][$bundle]['revision_information'] = $field_data['revision_information'] ?? [];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $type_id = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $result = $this->t('No values changed');

    // Load the edit revision for safe editing.
    $entity = $this->entityRepository->getActive($type_id, $entity->id());

    if (isset($this->configuration[$type_id][$bundle])) {
      $values = $this->configuration[$type_id][$bundle]['values'];
      $change_method = $this->configuration[$type_id][$bundle]['change_method'];
      foreach ($values as $field => $value) {
        if (isset($change_method[$field])) {
          switch ($change_method[$field]) {
            case 'new':
              $current_value = $entity->{$field}->getValue();
              $value = array_unique(array_merge($current_value, $value), SORT_REGULAR);
              break;

            case 'append':
              $current_value = $entity->{$field}->getValue();
              if ($current_value) {
                $value[0]['value'] = $current_value[0]['value'] . ' ' . $value[0]['value'];
              }
              break;
          }
        }

        $entity->{$field}->setValue($value);
      }

      // Set up revision defaults if entity is revisionable.
      if (!empty($this->configuration[$type_id][$bundle]['revision_information']['revision'])) {
        $entity->setNewRevision();
        $entity->setRevisionCreationTime($this->time->getCurrentTime());
        $entity->setRevisionUserId($this->currentUser->id());

        if (empty($this->configuration[$type_id][$bundle]['revision_information']['revision_log'])) {
          $entity->setRevisionLogMessage($this->formatPlural(count($values), 'Edited as a part of bulk operation. Field changed: @fields', 'Edited as a part of bulk operation. Fields changed: @fields', [
            '@fields' => implode(', ', array_keys($values)),
          ]));
        }
        else {
          $entity->setRevisionLogMessage($this->configuration[$type_id][$bundle]['revision_information']['revision_log']);
        }
      }

      $entity->save();
      $result = $this->t('Modify field values');
    }
    return $result;
  }

}
