<?php

namespace Drupal\search_api_page\Plugin\Block;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\search_api_page\Form\SearchApiPageBlockForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Search Api page form' block.
 *
 * @Block(
 *   id = "search_api_page_form_block",
 *   admin_label = @Translation("Search Api Page search block form"),
 *   category = @Translation("Forms")
 * )
 */
class SearchApiPageBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The search API page form.
   *
   * @var \Drupal\search_api_page\Form\SearchApiPageBlockForm
   */
  protected $searchApiPageBlockForm;

  /**
   * The search api page storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $searchApiPageStorage;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritDoc)
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('search_api_page'),
      $container->get('form_builder'),
      $container->get('block_form.search_api_page')
    );
  }

  /**
   * SearchApiPageBlock constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $searchApiStorage
   *   The entity type manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   * @param \Drupal\search_api_page\Form\SearchApiPageBlockForm $searchApiPageForm
   *   The search API page form.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $searchApiStorage, FormBuilderInterface $formBuilder, SearchApiPageBlockForm $searchApiPageForm) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->searchApiPageBlockForm = $searchApiPageForm;
    $this->searchApiPageStorage = $searchApiStorage;
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $options = [];

    $search_api_pages = $this->searchApiPageStorage->loadMultiple();
    foreach ($search_api_pages as $search_api_page) {
      $options[$search_api_page->id()] = $search_api_page->label();
    }

    $form['search_api_page'] = [
      '#type' => 'select',
      '#title' => $this->t('Search page'),
      '#default_value' => !empty($this->configuration['search_api_page']) ? $this->configuration['search_api_page'] : '',
      '#description' => $this->t('Select to which search page a submission of this form will redirect to'),
      '#options' => $options,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['search_api_page'] = $form_state->getValue('search_api_page');
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $search_api_page = $this->searchApiPageStorage->load($this->configuration['search_api_page']);
    if ($search_api_page === NULL) {
      return [];
    }
    return ['config' => [$search_api_page->getConfigDependencyName()]];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $this->searchApiPageBlockForm->setPageId($this->configuration['search_api_page']);
    return \Drupal::formBuilder()->getForm($this->searchApiPageBlockForm);
  }

}
