<?php

namespace Drupal\nys_senators;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;

/**
 * Collects "helper" functions for Senator entities.
 */
class SenatorsHelper {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The CacheBackend Interface.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructor class for Bills Helper.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The backend cache.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $cache_backend) {
    $this->entityTypeManager = $entity_type_manager;
    $this->cache = $cache_backend;
  }

  /**
   * Defines a prefix for cache entries related to this class.
   */
  const CACHE_BIN_PREFIX = 'nys_senators';

  /**
   * Get the Cache.
   */
  protected function getCache(string $name): bool|object {
    return $this->cache->get(static::CACHE_BIN_PREFIX . ':' . $name);
  }

  /**
   * Sets a value in the nys_bills cache.
   */
  protected function setCache(string $name, $value): void {
    $this->cache->set(static::CACHE_BIN_PREFIX . ':' . $name, $value);
  }

  /**
   * Removes a value from the nys_bills cache.
   */
  protected function removeCache(string $name): void {
    $this->cache->delete(static::CACHE_BIN_PREFIX . ':' . $name);
  }

  /**
   * Provides a reference to Drupal's storage service for taxonomy terms.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getStorage(): EntityStorageInterface {
    return $this->entityTypeManager->getStorage('taxonomy_term');
  }

  /**
   * Retrieves a mapping of senator taxonomy term id to name data.
   *
   * @param bool $active_only
   *   If only the active senators should be included.
   * @param bool $refresh
   *   If a forced refresh should happen or not.
   *
   * @return array
   *   An array keyed by taxonomy term id.  Each element will have values for
   *   'short_name' and 'full_name'.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getNameMapping(bool $active_only = TRUE, bool $refresh = FALSE): array {
    // Check the cache first.
    $cache_key = $active_only ? 'name_map:active_only' : 'name_map';
    $cache = $this->cache->get($cache_key);

    // If no cache, or a forced refresh, build the map array.
    if ($refresh || !$cache) {
      // Try to load the senators.
      try {
        $props = ['vid' => 'senator'];
        if ($active_only) {
          $props['field_active_senator'] = 1;
        }
        $senators = $this->getStorage()->loadByProperties($props);
      }
      catch (\Throwable) {
        $senators = [];
      }

      // Build the mapping array and cache it.
      $senator_mappings = [];
      foreach ($senators as $tid => $term) {
        $senator_mappings[$tid] = [
          'short_name' => $term->field_ol_shortname->value ?? '',
          'full_name' => $term->name->value ?? '',
        ];
      }
      $this->setCache($cache_key, $senator_mappings);
      return $senator_mappings;
    }

    return $cache->data;
  }

  /**
   * Loads a senator taxonomy term by OpenLeg member ID.
   */
  public function getSenatorTidFromMemberId($member_id): ? EntityInterface {
    try {
      $ret = $this->getStorage()
        ->loadByProperties(['field_ol_member_id' => $member_id]);
    }
    catch (\Throwable) {
      $ret = [];
    }
    return current($ret) ?: NULL;
  }

  /**
   * Get the Senator Sponsors of node.
   */
  public function getSenatorSponsors($node, $parent_type = NULL) {
    $variables = [];
    $senator = $node->field_ol_sponsor->entity;
    if (!empty($senator)) {
      $variables['ol_sponsor'] = $this->entityTypeManager->getViewBuilder('taxonomy_term')->view($senator, 'sponsor_list');
    }

    // Sponsor name.
    if (!empty($node->field_ol_sponsor_name->value) && $parent_type !== 'bill_default') {
      $variables['ol_sponsor_name'] = $node->field_ol_sponsor_name->value;
    }

    // Additional sponsor.
    if (!empty($ol_add_sponsors = $node->field_ol_add_sponsors->referencedEntities())) {
      $ol_add_sponsors = $this->entityTypeManager->getViewBuilder('taxonomy_term')->view($ol_add_sponsors, 'sponsor_list');
      $variables['ol_add_sponsors'] = $ol_add_sponsors;
    }

    // Additional Sponsor Names.
    if (!empty($node->field_ol_add_sponsor_names->value)) {
      $sponsor_names = [];
      $ol_add_sponsor_names = json_decode($node->field_ol_add_sponsor_names->value);
      foreach ($ol_add_sponsor_names as $key => $sponsor) {
        $sponsor_names[] = $sponsor->fullName;
      }

      $variables['sponsor_names'] = implode(', ', $sponsor_names);
    }
    return $variables;
  }

  /**
   * Validates that the senator has an Inactive microsite page.
   *
   * @param array $nodes
   *   The microsites nodes.
   *
   * @return bool
   *   Returns TRUE if it has an Inactive microsite page.
   */
  public function hasMicroSiteInactive(array $nodes) {
    foreach ($nodes as $node) {
      if ($node->hasField('field_microsite_page_type') && !$node->get('field_microsite_page_type')->isEmpty()) {
        $tid = $node->field_microsite_page_type->getValue()[0]['target_id'] ?? '';
        $micrositeTerm = Term::load($tid);
        if ($micrositeTerm instanceof TermInterface && !empty($micrositeTerm->getName()) && $micrositeTerm->getName() === 'Inactive') {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Validates if passed in user is an Admin or not.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user account.
   *
   * @return bool
   *   Returns TRUE if user is an admin account.
   */
  public function senatorUserIsAdmin(AccountInterface $current_user) {
    if ($current_user->id() == 1) {
      return TRUE;
    }
    elseif (in_array('administrator', $current_user->getRoles()) || in_array('content_admin', $current_user->getRoles())) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
