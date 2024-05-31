<?php

namespace Drupal\webform_analysis\Plugin\Block;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\webform_analysis\WebformAnalysis;
use Drupal\webform_analysis\WebformAnalysisChart;
use Drupal\webform_analysis\WebformAnalysisInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Webform' block.
 *
 * @Block(
 *   id = "webform_analysis_block",
 *   admin_label = @Translation("Webform Analysis"),
 *   category = @Translation("Webform")
 * )
 */
class WebformAnalysisBlock extends BlockBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The FormBuilder object.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager = NULL, FormBuilderInterface $formBuilder = NULL) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager ? $entityTypeManager : \Drupal::entityTypeManager();
    $this->formBuilder = $formBuilder ? $formBuilder : \Drupal::formBuilder();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'entity_id'  => '',
      'component'  => '',
      'chart_type' => '',
    ];
  }

  /**
   * Get Element Entity Type.
   *
   * @return string
   *   Entity Type Id.
   */
  public static function elementEntityTypeId() {
    return 'webform';
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $weight = 2;

    $form['entity_id'] = [
      '#title'   => $this->entityTypeManager->getDefinition(static::elementEntityTypeId())->getLabel(),
      '#type'    => 'select',
      '#options' => $this->getEntities(),
      '#ajax'    => [
        'callback' => [$this, 'updateEntity'],
        'wrapper'  => 'edit-component-wrapper',
      ],
      '#weight'  => $weight++,
    ];

    $entity_id = $this->configuration['entity_id'];
    if (!$entity_id && count($form['entity_id']['#options']) > 0) {
      $entity_id = array_keys($form['entity_id']['#options'])[0];
    }

    $webform = $entity_id ? $this->entityTypeManager->getStorage(static::elementEntityTypeId())->load($entity_id) : NULL;

    $form['entity_id']['#default_value'] = $entity_id;

    $form['component'] = [
      '#title'         => $this->t('Component'),
      '#type'          => 'select',
      '#default_value' => $this->configuration['component'],
      '#prefix'        => '<div id="edit-component-wrapper">',
      '#suffix'        => '</div>',
      '#weight'        => $weight++,
    ];

    $form['chart_type'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Chart type'),
      '#default_value' => $this->configuration['chart_type'],
      '#options'       => WebformAnalysis::getChartTypeOptions(),
      '#weight'        => $weight++,
    ];

    if ($webform) {
      $analysis = new WebformAnalysis($webform);
      if (!$analysis->getWebform()) {
        return $form;
      }
      $form['component']['#options'] = static::getElements($analysis);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $settings = [
      'entity_id',
      'component',
      'chart_type',
    ];

    if (!$form_state->getErrors()) {
      foreach ($settings as $setting) {
        $this->configuration[$setting] = $form_state->getValue($setting);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $entity_id = $this->configuration['entity_id'];
    $entity = $this->entityTypeManager->getStorage(static::elementEntityTypeId())->load($entity_id);
    if (!$entity) {
      return $build;
    }

    $chart = new WebformAnalysisChart(
      $entity,
      null,
      [$this->configuration['component']],
      $this->configuration['chart_type']
    );

    $chart->build($build);

    return $build;
  }

  /**
   * Get Webforms.
   *
   * @return array
   *   Names.
   */
  public function getEntities() {
    $entity_storage = $this->entityTypeManager->getStorage(static::elementEntityTypeId());
    foreach ($entity_storage->loadMultiple() as $entity) {
      $entity_id = $entity->id();
      $label = $entity->label();
      if ($label) {
        $names[$entity_id] = new TranslatableMarkup('@label (@id)', [
          '@label' => $label,
          '@id' => $entity_id,
        ]);
      }
      else {
        $names[$entity_id] = $entity_id;
      }
    }
    return $names;
  }

  /**
   * Get Elements.
   *
   * @param \Drupal\webform_analysis\WebformAnalysisInterface $analysis
   *   Analaysis.
   *
   * @return array
   *   Options.
   */
  public static function getElements(WebformAnalysisInterface $analysis) {
    $options = [];
    foreach ($analysis->getElements() as $element_name => $element) {
      $options[$element_name] = isset($element['#title']) ? $element['#title'] : $element_name;
    }
    return $options;
  }

  /**
   * Handles switching the configuration type selector.
   */
  public static function updateEntity($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $form['component'] = [
      '#title'  => t('Component'),
      '#type'   => 'select',
      '#prefix' => '<div id="edit-component-wrapper">',
      '#suffix' => '</div>',
    ];

    $entity_id = $form_state->getValue(['settings', 'entity_id']);
    $entity = \Drupal::entityTypeManager()->getStorage(static::elementEntityTypeId())->load($entity_id);

    if ($entity) {
      $analysis = new WebformAnalysis($entity);
      $form['component']['#options'] = static::getElements($analysis);
    }

    $response->addCommand(new ReplaceCommand('#edit-component-wrapper', $form['component']));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
