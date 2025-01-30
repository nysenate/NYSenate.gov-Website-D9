<?php

namespace Drupal\nys_senators\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Block to generate the Senator Committees for Senator Microsite Pages.
 *
 * @Block(
 *   id = "nys_blocks_senator_committees",
 *   admin_label = @Translation("Senator Committees"),
 *   category = @Translation("NYS Senators"),
 * )
 */
class SenatorCommittees extends BlockBase implements ContainerFactoryPluginInterface {

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
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->cache = $container->get('cache.default');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->routeMatch = $container->get('current_route_match');
    $instance->database = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The menu links array for the 'Senator Microsite Menu' template.
   */
  public function build() {
    $node = $this->routeMatch->getParameter('node');
    $committees = [];
    $memberships = [];
    if (!empty($node)) {
      $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');
      $senator_tid = $node->field_senator_multiref->target_id;
      $committee_membership_ids = $paragraph_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'members')
        ->condition('field_senator', $senator_tid)
        ->sort('field_committee_member_role', 'DESC')
        ->execute();

      // Filter out orphaned members paragraphs.
      $valid_committee_membership_ids = [];
      $committee_members = $paragraph_storage->loadMultiple($committee_membership_ids);
      foreach ($committee_members as $paragraph) {
        $parent_term = $paragraph->getParentEntity();
        if ($parent_term instanceof Term) {
          if (
            $parent_term->bundle() === 'committees'
            && $parent_term->hasField('field_members')
          ) {
            $linked_paragraphs = $parent_term->get('field_members')->referencedEntities();
            // Check if the paragraph still linked to the parent committee.
            if (in_array($paragraph, $linked_paragraphs, TRUE)) {
              $valid_committee_membership_ids[] = $paragraph->id();
            }
          }
        }
      }

      foreach ($valid_committee_membership_ids as $mpid) {
        /**
         * @var \Drupal\paragraphs\Entity\Paragraph $committee_membership
         */
        $committee_membership = $paragraph_storage->load($mpid);
        $parent = $committee_membership->getParentEntity();
        if (!empty($parent)) {
          switch ($committee_membership->field_committee_member_role->value) {
            case '0':
              $role = $this->t('Member');
              $label = 'Member';
              break;

            case '1':
              $role = $this->t('Co-Chair');
              $label = 'Co-Chair';
              break;

            case '2':
              $role = $this->t('Chair');
              $label = 'Chair';
              break;

            case '3':
              $role = $committee_membership->field_other_member_role->value;
              $label = $committee_membership->field_other_member_role->value;
              break;

            default:
              $role = '';
              $label = '';
              break;
          };

          $memberships[$label][] = [
            'parent' => $parent->label(),
            'text' => ['#plain_text' => $parent->label()],
            'link' => ['#plain_text' => $parent->toUrl()->toString()],
            'role' => ['#plain_text' => $role],
          ];
        }
      }

      // Sort based on parent label per role.
      foreach ($memberships as $roles) {
        asort($roles);
        foreach ($roles as $sorted) {
          unset($sorted['parent']);
          $committees[] = $sorted;
        }
      }
    }
    return $committees;
  }

}
