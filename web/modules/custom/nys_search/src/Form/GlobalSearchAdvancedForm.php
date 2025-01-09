<?php

namespace Drupal\nys_search\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\nys_search\GlobalSearchAdvancedHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Search advanced legislation form class.
 */
class GlobalSearchAdvancedForm extends FormBase {

  /**
   * Search Advanced Legislation helper service.
   *
   * @var \Drupal\nys_search\GlobalSearchAdvancedHelper
   */
  protected GlobalSearchAdvancedHelper $helper;

  /**
   * Drupal's Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Drupal's Database Connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Implements the create() method on the form controller.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   *   The form object.
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('nys_search.helper'),
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  /**
   * Search Advanced Legislation form constructor.
   *
   * @param \Drupal\nys_search\GlobalSearchAdvancedHelper $helper
   *   The GlobalSearchAdvancedHelper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Drupal's Entity Type Manager service.
   * @param \Drupal\Core\Database\Connection $database
   *   Drupal's Database Connection service.
   */
  public function __construct(GlobalSearchAdvancedHelper $helper, EntityTypeManagerInterface $entityTypeManager, Connection $database) {
    $this->helper = $helper;
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
  }

  /**
   * Returns a list of Senators.
   *
   * @return array
   *   Senators array.
   */
  public function getSenatorsList(): array {
    $query = $this->database
      ->select('taxonomy_term__field_senator_name', 's');
    $query->fields('s', ['entity_id', 'field_senator_name_given']);
    $query->addExpression("CONCAT(s.field_senator_name_given,' ',s.field_senator_name_family)", 'senator_full_name');
    $query->orderBy('s.field_senator_name_given');

    return $query->execute()->fetchAllKeyed(0, 2);
  }

  /**
   * Returns a list of committees.
   *
   * @return array
   *   Committee array.
   */
  public function getCommitteeList(): array {
    $result = ['all' => 'Filter By Committee'];

    try {
      $committees = $this->entityTypeManager
        ->getStorage('taxonomy_term')
        ->loadByProperties(['vid' => 'committees']);

      foreach ($committees as $committee) {
        $result[ucwords($committee->id())] = $committee->label();
      }
    }
    catch (\Throwable) {
    }
    return $result;
  }

  /**
   * Returns a list of content types.
   *
   * @return array
   *   Content Type array.
   */
  public function getTypeList(): array {
    $result = ['all' => 'Filter By Type'];
    try {
      $content_types = $this->entityTypeManager
        ->getStorage('node_type')
        ->loadMultiple();
      foreach ($content_types as $content_type) {
        $result[$content_type->id()] = $content_type->label();
      }
    }
    catch (\Throwable) {
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'nys_search_global_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Build your form here.
    $form['#cache']['contexts'][] = 'url.query_args';
    $results_page = $this->helper->isResultsPage();
    $markup = '';
    $form['global_search'] = [
      '#type' => 'block',
      '#attributes' => [
        'class' => ['adv-search-container'],
      ],
    ];
    if (!$results_page) {
      $form['global_search']['global_search_title'] = [
        '#type' => 'item',
        '#markup' => $this->t('<h1>Global Search</h1>'),
      ];
    }
    $args = $this->requestStack->getCurrentRequest()->query->all();

    $form['advanced_search']['advanced_search_text'] = [
      '#type' => 'item',
      '#markup' => $markup,
    ];

    $form['senator'] = [
      '#type' => 'select',
      '#options' => ['all' => 'Filter By Senator'] + $this->getSenatorsList(),
      '#default_value' => $args['senator'] ?? NULL,
      '#attributes' => [
        'class' => ['sponsor'],
      ],
      '#title_display' => 'invisible',
      '#title' => 'Filter By Senator',
    ];

    $form['committee'] = [
      '#type' => 'select',
      '#options' => $this->getCommitteeList(),
      '#default_value' => $args['committee'] ?? NULL,
      '#attributes' => [
        'class' => ['committee'],
      ],
      '#title_display' => 'invisible',
      '#title' => 'Filter By Committee',
    ];

    $form['type'] = [
      '#type' => 'select',
      '#options' => $this->getTypeList(),
      '#default_value' => $args['type'] ?? NULL,
      '#attributes' => [
        'class' => ['content-type'],
      ],
      '#title_display' => 'invisible',
      '#title' => 'Filter By Type',
    ];

    $form['full_text'] = [
      '#type' => 'hidden',
      '#default_value' => $args['full_text'] ?? NULL,
    ];

    $form['actions']['#type'] = 'actions';
    $form['#attached']['library'][] = 'nys_search/nys_search';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('FILTER RESULT'),
      '#button_type' => 'small',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $params = [
      'full_text' => $values['full_text'] ?: '',
      'senator' => $values['senator'] ?: '',
      'committee' => $values['committee'] ?: '',
      'type' => $values['type'] ?: '',
      'sort_order' => 'DESC',
    ];

    // Filter out any empty values.
    $params = array_filter(
      $params,
      function ($value) {
        return $value !== '';
      }
    );

    $url = Url::fromRoute('nys_search.globalSearch', [], ['query' => $params]);

    $form_state->setRedirectUrl($url);
  }

}
