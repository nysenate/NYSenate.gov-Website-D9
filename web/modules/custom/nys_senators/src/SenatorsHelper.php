<?php

namespace Drupal\nys_senators;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Session\AccountInterface;
use Drupal\nys_senators\Service\Microsites;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Collects "helper" functions for Senator entities.
 */
class SenatorsHelper {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The CacheBackend Interface.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * The current request from Drupal's Request Stack service.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * NYS Senators Microsites service.
   *
   * @var \Drupal\nys_senators\Service\Microsites
   */
  protected Microsites $microsites;

  /**
   * Constructor class for Bills Helper.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The backend cache.
   * @param \Drupal\Core\Http\RequestStack $request_stack
   *   A request stack service.
   * @param \Drupal\nys_senators\Service\Microsites $microsites
   *   NYS Senators Microsites service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $cache_backend, RequestStack $request_stack, Microsites $microsites) {
    $this->entityTypeManager = $entity_type_manager;
    $this->cache = $cache_backend;
    // Adding this condition because for some reason
    // it throws an error for anonymous users
    // when viewing a bill.
    if ($request_stack->getCurrentRequest() !== NULL) {
      $this->request = $request_stack->getCurrentRequest();
    }
    $this->microsites = $microsites;
  }

  /**
   * Defines a prefix for cache entries related to this class.
   */
  const CACHE_BIN_PREFIX = 'nys_senators';

  /**
   * Standardizes cache keys to this class.
   */
  protected function cacheName($name): string {
    return static::CACHE_BIN_PREFIX . ':' . $name;
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
   * Gets the microsite landing page URL for a given senator.
   */
  public function getMicrositeUrl(Term $senator): string {
    $sites = $this->microsites->getMicrosites();
    return $sites[$senator->id()] ?? '';
  }

  /**
   * Turns a string into a microsite landing page URL.
   *
   * This is deprecated in favor of compiling the URLs from the pathauto-
   * generated aliases.  Leaving this in for now as reference.
   *
   * @see static::getMicrosites()
   * @see static::getMicrositeUrl()
   * @see static::compileMicrosites()
   */
  protected function generateMicrositeUrl(string $name): string {
    return $this->request->getSchemeAndHttpHost() .
        '/senators/' . strtolower(str_replace([' ', '.'], ['-', ''], $name));
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
   */
  public function getNameMapping(bool $active_only = TRUE, bool $refresh = FALSE): array {
    // Check the cache first.
    $cache_key = $active_only ? 'name_map:active_only' : 'name_map';
    $cache = $this->cache->get($this->cacheName($cache_key));

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
      $this->cache->set($this->cacheName($cache_key), $senator_mappings);
      return $senator_mappings;
    }

    return $cache->data;
  }

  /**
   * Loads a senator taxonomy term by OpenLeg member ID.
   */
  public function getSenatorTidFromMemberId($member_id): ?EntityInterface {
    try {
      $ret = $this->getStorage()
        ->loadByProperties(
                [
                  'field_ol_member_id' => $member_id,
                  'vid' => 'senator',
                ]
            );
    }
    catch (\Throwable) {
      $ret = [];
    }
    return current($ret) ?: NULL;
  }

  /**
   * Get the Senator Sponsors of node.
   */
  public function getSenatorSponsors($node, $parent_type = NULL): array {
    $variables = [];
    $senator = $node->field_ol_sponsor->entity;
    if (!empty($senator)) {
      $variables['ol_sponsor'] = $this->entityTypeManager->getViewBuilder('taxonomy_term')
        ->view($senator, 'sponsor_list');
    }

    // Sponsor name.
    if (!empty($node->field_ol_sponsor_name->value) && $parent_type !== 'bill_default') {
      $variables['ol_sponsor_name'] = $node->field_ol_sponsor_name->value;
    }

    // Additional sponsor.
    if (!empty($ol_add_sponsors = $node->field_ol_add_sponsors->referencedEntities())) {
      $ol_add_sponsors = $this->entityTypeManager->getViewBuilder('taxonomy_term')
        ->view($ol_add_sponsors, 'sponsor_list');
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
      if ($node->hasField('field_microsite_page_type')
            && !$node->get('field_microsite_page_type')->isEmpty()
        ) {
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

  /**
   * Loads all active senator terms.
   */
  public function getActiveSenators(): array {
    try {
      $ret = $this->getStorage()
        ->loadByProperties(['field_active_senator' => 1, 'vid' => 'senator']);
    }
    catch (\Throwable) {
      $ret = [];
    }
    return $ret;
  }

  /**
   * Loads the Senator term assigned to a district.
   *
   * @param int|\Drupal\taxonomy\Entity\Term $district
   *   Either a district number (as in Senate District 34), or a taxonomy
   *   term representing a district.
   *
   * @return \Drupal\taxonomy\Entity\Term|null
   *   If an active senator assignment is found, then the taxonomy term
   *   representing that senator is returned.  Otherwise, NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function loadByDistrict(int|Term $district): ?Term {
    // If a district number was passed, load the district.
    if (!is_object($district)) {
      $loaded = $this->getStorage()
        ->loadByProperties(
                [
                  'field_district_number' => $district,
                  'vid' => 'districts',
                ]
            );
      $district = current($loaded);
    }

    return $district && property_exists($district, 'field_senator')
        ? $this->getStorage()->load($district->field_senator->target_id)
        : NULL;
  }

  /**
   * Loads the district assigned to a senator.
   *
   * @return \Drupal\taxonomy\Entity\Term|null
   *   Returns the taxonomy term representing the senator's district.  If one
   *   could not be found, NULL is returned.
   */
  public function loadDistrict(Term $senator): ?Term {
    try {
      $loaded = $this->getStorage()
        ->loadByProperties(
                [
                  'field_senator' => $senator->id(),
                  'vid' => 'districts',
                ]
            );
    }
    catch (\Throwable) {
      $loaded = [];
    }

    return current($loaded) ?: NULL;
  }

  /**
   * Translates party abbreviations to full name, per the available options.
   *
   * @return array
   *   In the form ['abbreviation' => 'full party name', ...]
   */
  public function getPartyNames(Term $senator): array {
    /*
     * This version ignores bad/unknown selections.  Commenting for reference.
     * @code
     *   $values = array_flip(array_map(
     *     function($v) { return $v['value']; },
     *     $senator->field_party->getValue()
     *   ));
     *   return array_intersect_key(
     *     $b->field_party->getSetting('allowed_values'),
     *     $values
     *   );
     */

    // This version returns "unknown" for a bad/unknown selection.
    $field = $senator->field_party;
    $allowed = $field->getSetting('allowed_values');
    $parties = [];
    foreach ($field->getValue() as $val) {
      $parties[$val['value']] = $allowed[$val['value']] ?? 'unknown';
    }
    return $parties;

  }

  /**
   * Sorts an array of Senator taxonomy terms by name.  Keys are not preserved.
   *
   * @param array $senators
   *   An array of Term objects belonging to the 'senator' bundle.
   * @param bool $last_first
   *   If true, sort by "<last> <first>".  Otherwise sort by "<first> <last>".
   */
  public static function sortByName(array &$senators, bool $last_first = TRUE): void {
    usort(
          $senators, function ($a, $b) use ($last_first) {
            foreach (['a', 'b'] as $var) {
                $first = ${$var}->field_senator_name->given ?? '';
                $last = ${$var}->field_senator_name->family ?? '';
                ${$var} = $last_first
                ? $last . ' ' . $first
                : $first . ' ' . $last;
            }
              return $a == $b ? 0 : ($a < $b ? -1 : 0);
          }
      );
  }

}
