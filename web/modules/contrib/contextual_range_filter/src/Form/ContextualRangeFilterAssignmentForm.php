<?php

namespace Drupal\contextual_range_filter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Convert selected contextual filters to contextual range filters.
 *
 * From a UI perspective it would make sense to simply have a tick-box on the
 * the Views UI contextual filter config panel. The problem is that at that
 * point the plugin class has already been selected and instantiated.
 * This is why we make the user define the contextual filter first, then have
 * them select on this page which contextual filters need to be converted to
 * range filters.
 */
class ContextualRangeFilterAssignmentForm extends ConfigFormBase {

  /**
   * Return the form id.
   */
  public function getFormId() {
    return 'contextual_range_filter_settings';
  }

  /**
   * Return the configuration route.
   */
  protected function getEditableConfigNames() {
    return [
      'contextual_range_filter.settings',
    ];
  }

  /**
   * Build the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $range_fields = [
      'date_field_names' => [],
      'numeric_field_names' => [],
      'string_field_names' => [],
    ];
    $class_path = 'Drupal\views\Plugin\views\argument';
    $argument_info = Views::pluginManager('argument')->getDefinitions();

    foreach (Views::getAllViews() as $view) {
      $view_name = $view->get('label');
      if (views_view_is_disabled($view)) {
        $view_name .= ' (' . $this->t('disabled') . ')';
      }
      foreach ($view->get('display') as $display) {
        if (!empty($display['display_options']['arguments'])) {

          foreach ($display['display_options']['arguments'] as $contextual_filter) {
            $plugin_id = $contextual_filter['plugin_id'];
            $class = $argument_info[$plugin_id]['class'];
            // Does this contextual filter class extend one of the base
            // contextual filter classes?
            // Note: lists have a class of Numeric or String, so nothing special
            // needs or can be done for lists...
            $is_date_handler = is_a($class, "$class_path\Date", TRUE);
            $is_string_handler = is_a($class, "$class_path\StringArgument", TRUE);

            // Anything that is not a date or string will be shown as numeric.
            $is_numeric_handler = !$is_date_handler && !$is_string_handler;

            if ($is_date_handler || $is_numeric_handler || $is_string_handler) {

              // For every View $display we get a number of fields.
              // Should we allow selection per display AND per field?
              // Currently we find, but don't add, the "duplicates".
              // @todo: Find something more human-readible than this.
              $title = "$plugin_id: " . $contextual_filter['id'];

              // @todo Taxonomy term depth has Views machine name
              // "taxonomy_term_data:tid", not "node:term_node_tid_depth".
              $machine_name = $contextual_filter['table'] . ':' . $contextual_filter['field'];

              if ($is_date_handler) {
                $title_used = isset($range_fields['date_field_names'][$machine_name][$title]);
                if (!$title_used || !in_array($view_name, $range_fields['date_field_names'][$machine_name][$title])) {
                  $range_fields['date_field_names'][$machine_name][$title][] = $view_name;
                }
              }
              elseif ($is_numeric_handler) {
                $title_used = isset($range_fields['numeric_field_names'][$machine_name][$title]);
                if (!$title_used || !in_array($view_name, $range_fields['numeric_field_names'][$machine_name][$title])) {
                  $range_fields['numeric_field_names'][$machine_name][$title][] = $view_name;
                }
              }
              elseif ($is_string_handler) {
                $title_used = isset($range_fields['string_field_names'][$machine_name][$title]);
                if (!$title_used || !in_array($view_name, $range_fields['string_field_names'][$machine_name][$title])) {
                  $range_fields['string_field_names'][$machine_name][$title][] = $view_name;
                }
              }
            }
          }
        }
      }
    }
    $form['field_names'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Select contextual filters to be converted to contextual range filters'),
    ];
    $config = $this->configFactory->get('contextual_range_filter.settings');
    $labels = [$this->t('date'), $this->t('numeric'), $this->t('string')];
    $label = reset($labels);
    foreach ($range_fields as $type => $data) {
      $options = [];
      foreach ($data as $machine_name => $view_names) {
        $title = key($view_names);
        $views_list = implode(', ', $view_names[$title]);
        $replace = [
          '%field' => $title,
          '@views' => $views_list,
        ];
        $options[$machine_name] = $this->t('%field in view @views', $replace);
        $form['#view_names'][$machine_name] = $view_names;
      }
      $form['field_names'][$type] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Select which of the below contextual <em>@label</em> filters should be converted to <em>@label range</em> filters:', ['@label' => $label]),
        '#default_value' => $config->get($type) ?: [],
        '#options' => $options,
      ];
      $label = next($labels);
    }
    $form['actions']['note'] = [
      '#markup' => '<p><em>' . $this->t('Caches will be cleared as part of this operation. This may take a while.') . '</em></p>',
      '#weight' => 1,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->configFactory->getEditable('contextual_range_filter.settings');

    foreach (['numeric', 'string', 'date'] as $type) {
      $field_names = $type . '_field_names';

      // Clear out the unticked boxes.
      $filters = array_filter($form_state->getValue($field_names));

      $saved_filters = $config->get($field_names) ?: [];
      $added_filters = array_diff($filters, $saved_filters);
      $removed_filters = array_diff($saved_filters, $filters);
      $changed_filters = array_merge($added_filters, $removed_filters);

      if (empty($changed_filters)) {
        continue;
      }
      $config->set($field_names, $filters);

      // Find corresponding Views, (un)set the (range) filter and save them.
      // $form['#view_names'][node__field_price:field_price_value] is an array.
      $changed_view_names = [];
      foreach ($changed_filters as $filter_name) {
        if (!empty($form['#view_names'][$filter_name])) {
          foreach ($form['#view_names'][$filter_name] as $view_names) {
            foreach ($view_names as $view_name) {
              if (!in_array($view_name, $changed_view_names)) {
                $changed_view_names[] = $view_name;
              }
            }
          }
        }
      }

      // We cycle through all the views. If the view is flagged as needing to be
      // edited, we check if any of the changed filters is present in that view.
      // If we find one, we (re)set the plugin id accordingly.
      $range_type = $type . '_range';
      foreach (Views::getAllViews() as $view) {
        $view_name = $view->get('label');
        if (in_array($view_name, $changed_view_names)) {
          $display = &$view->getDisplay('default');
          foreach ($changed_filters as $filter_name) {
            $field_name = substr($filter_name, strpos($filter_name, ":") + 1);
            if (isset($display['display_options']['arguments'][$field_name]['plugin_id'])) {
              $plugin_id = in_array($filter_name, $added_filters) ? $range_type : $type;
              $display['display_options']['arguments'][$field_name]['plugin_id'] = $plugin_id;
            }
            $this->messenger()->addStatus($this->t('Updated contextual filter(s) on view %view_name.', ['%view_name' => $view_name]));
            $view->save();
          }
        }
      }
    }
    $config->save();
    // We now need to invoke contextual_range_filter_views_data_alter() for the
    // changes to take effect. We do this by clearing the caches.
    drupal_flush_all_caches();

    parent::submitForm($form, $form_state);
  }

}
