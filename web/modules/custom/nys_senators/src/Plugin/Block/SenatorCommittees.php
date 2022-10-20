<?php

namespace Drupal\nys_senators\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
    $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');
    $senator_tid = $node->field_senator_multiref->target_id;
    $committee_membership_ids = $paragraph_storage->getQuery()
      ->condition('type', 'members')
      ->condition('field_senator', $senator_tid)
      ->sort('field_committee_member_role', 'DESC')
      ->execute();

    $committees = [];
    foreach ($committee_membership_ids as $mpid) {
      $committee_membership = $paragraph_storage->load($mpid);
      $parent = $committee_membership->getParentEntity();

      switch ($committee_membership->field_committee_member_role->value) {
        case '0':
          $role = $this->t('Member');
          break;

        case '1':
          $role = $this->t('Co-Chair');
          break;

        case '1':
          $role = $this->t('Chair');
          break;

        case '3':
          $role = $committee_membership->field_other_member_role->value;
          break;

        default:
          $role = '';
          break;
      };
      $committees[] = [
        'text' => ['#plain_text' => $parent->label()],
        'link' => ['#plain_text' => $parent->toUrl()->toString()],
        'role' => ['#plain_text' => $role],
      ];
    }

    return $committees;

  }

}
