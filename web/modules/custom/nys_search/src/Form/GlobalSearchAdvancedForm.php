<?php

namespace Drupal\nys_search\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Search advanced legislation form class.
 */
class GlobalSearchAdvancedForm extends FormBase {

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
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  /**
   * Search Advanced Legislation form constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Drupal's Entity Type Manager service.
   * @param \Drupal\Core\Database\Connection $database
   *   Drupal's Database Connection service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, Connection $database) {
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

    $args = $this->getRequest()->query->all();

    $form['full_text'] = [
      '#type' => 'textfield',
      '#title' => 'Search Term',
      '#default_value' => $args['full_text'] ?? NULL,
      '#attributes' => [
        'class' => ['full-text-search'],
        'placeholder' => 'Enter search term',
      ],
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

    $form['actions']['#type'] = 'actions';
    $form['#attached']['library'][] = 'nys_search/nys_search';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('FILTER RESULT'),
      '#button_type' => 'small',
    ];

    // Only show clear filters button if there are active filters/params.
    $has_filters = FALSE;
    foreach (['full_text', 'senator', 'committee', 'type'] as $param) {
      if (!empty($args[$param])) {
        $has_filters = TRUE;
        break;
      }
    }

    if ($has_filters) {
      $form['actions']['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('CLEAR FILTERS'),
        '#button_type' => 'small',
        '#submit' => ['::resetForm'],
      ];
    }

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

  /**
   * Reset form handler.
   */
  public function resetForm(array &$form, FormStateInterface $form_state): void {
    // Redirect to the search page without any query parameters.
    $url = Url::fromRoute('nys_search.globalSearch');
    $form_state->setRedirectUrl($url);
  }

}
