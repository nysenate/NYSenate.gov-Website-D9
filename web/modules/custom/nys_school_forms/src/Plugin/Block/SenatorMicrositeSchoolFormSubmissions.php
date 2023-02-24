<?php

namespace Drupal\nys_school_forms\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\nys_school_forms\SchoolFormsService;
use Drupal\Core\Routing\CurrentRouteMatch;

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
    if ($node instanceof NodeInterface && $node->getType() === 'microsite_page') {
      $term_id = $node->get('field_microsite_page_type')->target_id;
      $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($term_id);
      $form_type = $term->getName();
      $senator_terms = ($node->hasField('field_senator_multiref') && !$node->get('field_senator_multiref')->isEmpty()) ? $node->get('field_senator_multiref')->getValue() : [];
      $tids = [];
      foreach ($senator_terms as $tid) {
        $tids[] = (int) $tid['target_id'];
      }
      $senator = $tids[0];
      $results = $this->schoolFormsService->getResults($senator, $form_type, NULL, NULL, NULL, NULL, NULL);
      // Results come back in this format. First key is the school' name, second key is the grade.
      /*["SUCCESS ACADEMY BERGEN BEACH"]=> array(1) {
          [5]=> array(1) {
             ["student"]=> array(4) { 
                ["show_student"]=> string(2) "No" 
                ["student_name"]=> string(5) "test5" 
                ["student_submission"]=> string(7) "1900918"
                ["submission_type"]=> string(1) "0" 
              } 
            } 
          } 
        }*/
      return [
        '#theme' => 'senator_microsite_school_form_submission',
        '#results' => $results,
      ];
    }
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
