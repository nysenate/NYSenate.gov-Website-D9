<?php

namespace Drupal\search_api_page\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Entity\Index;

/**
 * Class SearchApiPageForm.
 *
 * @package Drupal\search_api_page\Form
 */
class SearchApiPageForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\search_api_page\SearchApiPageInterface $search_api_page */
    $search_api_page = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $search_api_page->label(),
      '#required' => TRUE,
      '#description' => $this->t('This will also be used as the page title.'),
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $search_api_page->id(),
      '#machine_name' => [
        'exists' => '\Drupal\search_api_page\Entity\SearchApiPage::load',
      ],
      '#disabled' => !$search_api_page->isNew(),
    ];

    // Default index and states.
    $default_index = $search_api_page->getIndex();
    $default_index_states = [
      'visible' => [
        ':input[name="index"]' => ['value' => $default_index],
      ],
    ];

    $index_options = [];
    $search_api_indexes = $this->entityTypeManager->getStorage('search_api_index')->loadMultiple();
    /** @var  \Drupal\search_api\IndexInterface $search_api_index */
    foreach ($search_api_indexes as $search_api_index) {
      $index_options[$search_api_index->id()] = $search_api_index->label();
    }

    $form['index_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Index'),
    ];

    $form['index_fieldset']['index'] = [
      '#type' => 'select',
      '#title' => $this->t('Search API index'),
      '#options' => $index_options,
      '#default_value' => $default_index,
      '#required' => TRUE,
    ];

    $form['index_fieldset']['previous_index'] = [
      '#type' => 'value',
      '#value' => $default_index,
    ];

    $searched_fields = $search_api_page->getFullTextFields();
    $form['index_fieldset']['searched_fields'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => $searched_fields,
      '#size' => min(4, count($searched_fields)),
      '#title' => $this->t('Searched fields'),
      '#description' => $this->t('Select the fields that will be searched. If no fields are selected, all available fulltext fields will be searched.'),
      '#default_value' => $search_api_page->getSearchedFields(),
      '#access' => !empty($default_index),
      '#states' => $default_index_states,
    ];

    $form['page_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Page'),
      '#states' => [
        'visible' => [':input[name="index"]' => ['value' => $default_index]],
      ],
      '#access' => !empty($default_index),
    ];

    $form['page_fieldset']['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#maxlength' => 255,
      '#default_value' => $search_api_page->getPath(),
      '#description' => $this->t('Do not include leading or trailing forward slash.'),
      '#required' => TRUE,
      '#access' => !empty($default_index),
      '#states' => $default_index_states,
    ];

    $form['page_fieldset']['previous_path'] = [
      '#type' => 'value',
      '#value' => $search_api_page->getPath(),
      '#access' => !empty($default_index),
    ];

    $form['page_fieldset']['clean_url'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Use clean URL's"),
      '#default_value' => $search_api_page->getCleanUrl(),
      '#access' => !empty($default_index),
      '#states' => $default_index_states,
    ];

    $form['page_fieldset']['previous_clean_url'] = [
      '#type' => 'value',
      '#default_value' => $search_api_page->getCleanUrl(),
    ];

    $form['page_fieldset']['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit'),
      '#default_value' => $search_api_page->getLimit(),
      '#min' => 1,
      '#required' => TRUE,
      '#access' => !empty($default_index),
      '#states' => $default_index_states,
    ];

    $form['page_fieldset']['show_search_form'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show search form above results'),
      '#default_value' => $search_api_page->showSearchForm(),
      '#access' => !empty($default_index),
      '#states' => $default_index_states,
    ];

    $form['page_fieldset']['show_all_when_no_keys'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show all results when no search is performed'),
      '#default_value' => $search_api_page->showAllResultsWhenNoSearchIsPerformed(),
      '#access' => !empty($default_index),
      '#states' => $default_index_states,
    ];

    $form['page_fieldset']['style'] = [
      '#type' => 'radios',
      '#title' => $this->t('Style'),
      '#options' => [
        'view_modes' => $this->t('View modes'),
        'search_results' => $this->t('Search results'),
      ],
      '#default_value' => $search_api_page->getStyle(),
      '#required' => TRUE,
      '#access' => !empty($default_index),
      '#states' => $default_index_states,
    ];

    $plugin_manager = \Drupal::service('plugin.manager.search_api.parse_mode');
    $instances = $plugin_manager->getInstances();
    $options = [];
    foreach ($instances as $name => $instance) {
      if ($instance->isHidden()) {
        continue;
      }
      $options[$name] = $instance->label();
    }

    $form['page_fieldset']['parse_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Parse mode'),
      '#description' => $this->t('Parse mode for search keywords'),
      '#options' => $options,
      '#default_value' => $search_api_page->getParseMode(),
      '#required' => TRUE,
    ];

    if (empty($default_index)) {
      return $form;
    }

    $form['view_mode_configuration'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $this->t('View modes'),
      '#states' => [
        'visible' => [
          ':input[name="style"]' => ['value' => 'view_modes'],
          ':input[name="index"]' => ['value' => $default_index],
        ],
      ],
    ];

    /** @var \Drupal\search_api\IndexInterface $index */
    $index = Index::load($search_api_page->getIndex());
    $viewModeConfig = $search_api_page->getViewModeConfig();
    foreach ($index->getDatasources() as $datasource_id => $datasource) {
      $form['view_mode_configuration'][$datasource_id] = [
        '#type' => 'fieldset',
        '#title' => $datasource->label(),
      ];

      $form['view_mode_configuration'][$datasource_id]['default'] = [
        '#type' => 'select',
        '#title' => $this->t('Default View mode all %datasource bundles', ['%datasource' => $datasource->label()]),
        '#options' => $datasource->getViewModes(),
        '#default_value' => $viewModeConfig->getDefaultViewMode($datasource_id),
      ];

      $form['view_mode_configuration'][$datasource_id]['overrides'] = [
        '#type' => 'details',
        '#open' => $viewModeConfig->hasOverrides($datasource_id),
        '#title' => $this->t('%datasource view mode overrides', ['%datasource' => $datasource->label()]),
        '#options' => $datasource->getViewModes(),
      ];

      $bundles = $datasource->getBundles();
      foreach ($bundles as $bundle_id => $bundle_label) {
        $form['view_mode_configuration'][$datasource_id]['overrides'][$bundle_id] = [
          '#type' => 'select',
          '#title' => $this->t('View mode for %bundle', ['%bundle' => $bundle_label]),
          '#options' => $datasource->getViewModes($bundle_id),
          '#empty_option' => $this->t('-- Use default --'),
        ];
        if ($viewModeConfig->isOverridden($datasource_id, $bundle_id)) {
          $form['view_mode_configuration'][$datasource_id]['overrides'][$bundle_id]['#default_value'] = $viewModeConfig->getViewMode($datasource_id, $bundle_id);
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $path = $form_state->getValue('path');

    if (!empty($path)) {
      $leading_slash = $path[0] === '/';
      $trailing_slash = $path[strlen($path) - 1] === '/';
      if ($leading_slash || $trailing_slash) {
        $form_state->setErrorByName('path', $this->t('The path should not contain leading or trailing slashes.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    /** @var \Drupal\search_api_page\SearchApiPageInterface $search_api_page */
    $search_api_page = $this->entity;
    if ($search_api_page->isNew()) {
      $actions['submit']['#value'] = $this->t('Next');
    }

    $default_index = $search_api_page->getIndex();
    if (empty($default_index)) {
      return $actions;
    }

    // Add an update button that shows up when changing the index.
    $default_index_states_invisible = [
      'invisible' => [
        ':input[name="index"]' => ['value' => $default_index],
      ],
    ];
    $actions['update'] = $actions['submit'];
    $actions['update']['#value'] = $this->t('Update');
    $actions['update']['#states'] = $default_index_states_invisible;

    // Hide the Save button when the index changes.
    $default_index_states_visible = [
      'visible' => [
        ':input[name="index"]' => ['value' => $default_index],
      ],
    ];
    $actions['submit']['#states'] = $default_index_states_visible;

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\search_api_page\SearchApiPageInterface $search_api_page */
    $search_api_page = $this->entity;

    // Reset view mode configuration.
    if (!$search_api_page->renderAsViewModes()) {
      $search_api_page->set('view_mode_configuration', []);
    }

    // Check searched fields. In case nothing has been selected, select all
    // the available fields.
    $has_selection = FALSE;
    $searched_fields = $form_state->getValue('searched_fields');
    foreach ($searched_fields as $key => $value) {
      if ($key === $value) {
        $has_selection = TRUE;
        break;
      }
    }
    if (!$has_selection) {
      $key_values = array_keys($form['index_fieldset']['searched_fields']['#options']);
      $searched_fields = array_combine($key_values, $key_values);
      $search_api_page->set('searched_fields', $searched_fields);
    }

    $status = $search_api_page->save();

    switch ($status) {
      case SAVED_NEW:
        // Redirect to edit form so the rest can be configured.
        $form_state->setRedirectUrl($search_api_page->toUrl('edit-form'));
        break;

      default:
        $indexHasChanged = $form_state->getValue('index') !== $form_state->getValue('previous_index');
        if (!$indexHasChanged) {
          // Index is unchanged so we'll redirect to the overview.
          $form_state->setRedirectUrl($search_api_page->toUrl('collection'));
          $this->messenger()->addMessage($this->t('Saved the %label Search page.', [
            '%label' => $search_api_page->label(),
          ]));
        }
        else {
          // Index has changed so we'll redirect to the edit form.
          $form_state->setRedirectUrl($search_api_page->toUrl('edit-form'));
          $this->messenger()->addMessage($this->t('Updated the index for the %label Search page.', [
            '%label' => $search_api_page->label(),
          ]));
        }

    }

    $pathHasChanged = $form_state->getValue('path') != $form_state->getValue('previous_path');
    $cleanUrlHasChanged = $form_state->getValue('clean_url') != $form_state->getValue('previous_clean_url');
    if ($pathHasChanged || $cleanUrlHasChanged) {
      \Drupal::service('router.builder')->rebuild();
    }

    /** @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface $invalidator */
    $invalidator = \Drupal::service('cache_tags.invalidator');
    $invalidator->invalidateTags(['search_api_page.style']);
  }

}
