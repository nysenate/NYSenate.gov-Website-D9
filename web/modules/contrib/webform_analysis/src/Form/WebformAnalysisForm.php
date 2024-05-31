<?php

namespace Drupal\webform_analysis\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform_analysis\WebformAnalysis;
use Drupal\webform_analysis\WebformAnalysisChart;

/**
 * Webform Analysis settings form.
 */
class WebformAnalysisForm extends EntityForm {

  /**
   * The analysis variable.
   *
   * @var \Drupal\webform_analysis\WebformAnalysis
   */
  protected $analysis;

  /**
   * Get webform title.
   *
   * @return string
   *   Title.
   */
  public function getTitle() {
    return $this->entity->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    // Do not use seven_form_node_form_alter.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $field_name = $this->getRouteMatch()->getParameter('field_name');
    $this->analysis = new WebformAnalysis($this->entity, $field_name);

    $form['#title'] = $this->getTitle();

    if (!$this->analysis->getWebform()) {
      return $form;
    }

    $chart = new WebformAnalysisChart(
      $this->entity,
      $field_name,
      $this->analysis->getComponents(),
      $this->analysis->getChartType()
    );

    $chart->build($form);

    $form['components_settings'] = [
      '#type'               => 'details',
      '#title'              => $this->t('Add analysis components'),
      '#open'               => FALSE,
      'analysis_components' => $this->getComponents(),
    ];

    $form['analysis_chart_type'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Charts type'),
      '#default_value' => $this->analysis->getChartType(),
      '#options'       => WebformAnalysis::getChartTypeOptions(),
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type'        => 'submit',
      '#value'       => $this->t('Update analysis display'),
      '#button_type' => 'primary',
      '#submit' => ['::submitForm', '::save'],
    ];

    $form['#attached']['library'][] = 'webform_analysis/webform_analysis';

    return $form;
  }

  /**
   * Get Components.
   *
   * @return array
   *   Components renderable.
   */
  public function getComponents() {

    foreach ($this->analysis->getElements() as $element_name => $element) {
      $options[$element_name] = isset($element['#title']) ? $element['#title'] : $element_name;
    }

    return [
      '#type'          => 'checkboxes',
      '#options'       => $options,
      '#default_value' => (array) $this->analysis->getComponents(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->analysis->setChartType($form_state->getValue('analysis_chart_type'));

    $components = [];
    foreach ($form_state->getValue('analysis_components') as $name => $value) {
      if ($value) {
        $components[] = $name;
      }
    }
    $this->analysis->setComponents($components);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    return $this->analysis->getWebform()->save();
  }

}
