<?php

namespace Drupal\nys_school_forms\Plugin\Block;

use Drupal\node\NodeInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\nys_school_forms\SchoolFormsService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Submission results block on the School Forms for Senator Microsite Pages.
 *
 * @Block(
 *   id = "nys_senators_microsite_school_forms",
 *   admin_label = @Translation("Microsite School Form Submissions"),
 *   category = @Translation("NYS School Forms"),
 * )
 */
class SenatorMicrositeSchoolFormSubmissions extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The School Forms Service.
   *
   * @var \Drupal\nys_school_forms\SchoolFormsService
   */
  protected $schoolFormsService;

  /**
   * The CacheBackend Interface.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The Entity TypeManager Interfacee.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Current Route Match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SchoolFormsService $schoolFormsService, CacheBackendInterface $cache_backend, EntityTypeManagerInterface $entity_type_manager, CurrentRouteMatch $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->schoolFormsService = $schoolFormsService;
    $this->cache = $cache_backend;
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $configuration,
        $plugin_id,
        $plugin_definition,
        $container->get('nys_school_forms.school_forms'),
        $container->get('cache.default'),
        $container->get('entity_type.manager'),
        $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The menu links array for the 'Senator Microsite Menu' template.
   */
  public function build() {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->routeMatch->getParameter('node');
    $build = [];
    if ($node instanceof NodeInterface && $node->getType() === 'microsite_page') {
      $term_id = $node->get('field_microsite_page_type')->target_id;
      $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($term_id);
      $form_type = $term->getName();
      $senator_term = ($node->hasField('field_senator_multiref') && !$node->get('field_senator_multiref')->isEmpty()) ? $node->get('field_senator_multiref')->entity : [];

      $last_year_params = $params = [
        'form_type' => $form_type,
        'senator' => $senator_term->id(),
        'school' => NULL,
        'teacher_name' => NULL,
        'from_date' => date('Y-m-d', strtotime('first day of january this year')),
        'to_date' => NULL,
        'sort_by' => NULL,
        'sort_order' => NULL,
      ];

      $last_year_params['from_date'] = NULL;
      $last_year_params['to_date'] = date('Y-m-d', strtotime('first day of december last year'));

      $build = [
        '#theme' => 'nys_school_forms__results_block',
        '#content' => [
          'panels' => [
            [
              'tab_text' => 'Current Year',
              'title' => date('Y') . ' Poster Submissions',
              'schools' => $this->schoolFormsService->getResults($params, FALSE),
            ],
            [
              'tab_text' => 'Past Submissions',
              'title' => 'Archived Submissions',
              'schools' => $this->schoolFormsService->getResults($last_year_params, FALSE),
            ],
          ],
        ],
      ];

    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['nys_senators_microsite_school_forms'] = $form_state->getValue('nys_senators_microsite_school_forms');
  }

}
