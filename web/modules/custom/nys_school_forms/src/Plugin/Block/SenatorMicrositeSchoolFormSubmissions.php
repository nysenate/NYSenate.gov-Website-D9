<?php

namespace Drupal\nys_school_forms\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\nys_school_forms\SchoolFormsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SchoolFormsService $schoolFormsService, CacheBackendInterface $cache_backend, EntityTypeManagerInterface $entity_type_manager, CurrentRouteMatch $route_match, RequestStack $requestStack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->schoolFormsService = $schoolFormsService;
    $this->cache = $cache_backend;
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->requestStack = $requestStack;
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
          $container->get('current_route_match'),
          $container->get('request_stack')
      );
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The menu links array for the 'Senator Microsite Menu' template.
   */
  public function build() {
    /**
* @var \Drupal\node\Entity\Node $node
*/
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
        'from_date' => strtotime('this year January 1st'),
        'to_date' => NULL,
        'sort_by' => NULL,
        'sort_order' => NULL,
      ];

      $last_year_params['from_date'] = NULL;
      $last_year_params['to_date'] = strtotime('last year December 31st');

      $filter_options = [];
      $past_submissions = $this->schoolFormsService->getResults($last_year_params, FALSE);
      ksort($past_submissions);
      $filter_options[] = [
        'value' => 'All',
        'text' => '- Year -',
      ];
      $request = $this->requestStack->getCurrentRequest();
      $edit_type = $request->query->get('edit-type');
      $filter = FALSE;
      foreach (array_keys($past_submissions) as $option) {
        if (!empty($edit_type) && $option == $edit_type) {
          $filter = TRUE;
        }
        $filter_options[] = [
          'value' => $option,
          'text' => $option,
        ];
      }
      if ($filter === TRUE) {
        foreach (array_keys($past_submissions) as $option) {
          if ($option != $edit_type) {
            unset($past_submissions[$option]);
          }
        }
      }

      $build = [
        '#theme' => 'nys_school_forms__results_block',
        '#content' => [
          'panels' => [
        [
          'tab_text' => 'Current Year',
          'title' => date('Y') . ' Poster Submissions',
          'filter' => $filter,
          'years' => $this->schoolFormsService->getResults($params, FALSE),
        ],
        [
          'tab_text' => 'Past Submissions',
          'title' => 'Archived Submissions',
          'filter' => $filter,
          'filter_item' => [
            'label' => 'Select Year',
            'desc' => 'Archive only goes back to ' . end($filter_options)['text'],
            'select_options' => $filter_options,
          ],
          'years' => $past_submissions,
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
