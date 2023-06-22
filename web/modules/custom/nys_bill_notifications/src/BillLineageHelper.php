<?php

namespace Drupal\nys_bill_notifications;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Helper methods for bill lineages.
 */
class BillLineageHelper {

  /**
   * Storage plugin for taxonomy term entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected static EntityStorageInterface $storage;

  /**
   * Gets the taxonomy term storage interface.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected static function storage(): EntityStorageInterface {
    if (!isset(static::$storage)) {
      static::$storage = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term');
    }
    return static::$storage;
  }

  /**
   * Loads a taxonomy term, enforcing ['vid' => 'prev_ver'].
   *
   * @return \Drupal\taxonomy\Entity\Term|null
   *   Will return the first of all found terms, or NULL if none are found.
   *
   * @see EntityStorageInterface::loadByProperties()
   */
  protected static function loadTermByProperties($properties): ?Term {
    $properties = ['vid' => 'prev_ver'] + $properties;
    try {
      $loaded = static::storage()->loadByProperties($properties);
    }
    catch (\Throwable $e) {
      $loaded = [];
    }
    return current($loaded) ?: NULL;
  }

  /**
   * Loads a term by id, enforcing ['vid' => 'prev_ver'].
   */
  public static function loadRootById(int $id): ?Term {
    return static::loadTermByProperties(['id' => $id]);
  }

  /**
   * Loads a term by name, enforcing ['vid' => 'prev_ver'].
   *
   * Name should be a full print number, such as '2017-S123'.
   */
  public static function loadRootByName(string $name): ?Term {
    return static::loadTermByProperties(['name' => $name]);
  }

  /**
   * Loads a term by session and print number, enforcing ['vid' => 'prev_ver'].
   */
  public static function loadRootByPrint(int $session, string $print): ?Term {
    $ret = NULL;
    $name = $session . '-' . $print;
    if ($name) {
      $ret = static::loadRootByName($name);
    }
    return $ret;
  }

  /**
   * Creates a new lineage root taxonomy term.  Returns NULL on any exception.
   */
  public static function createRoot(int $session, string $print): ?Term {
    $ret = NULL;
    if ($session && $print) {
      $name = $session . '-' . $print;
      try {
        /**
         * @var \Drupal\taxonomy\Entity\Term $ret
         */
        $ret = static::storage()->create(
              [
                'vid' => 'prev_ver',
                'name' => $name,
              ]
          );
        $ret->save();
      }
      catch (\Throwable $e) {
        $ret = NULL;
      }
    }
    return $ret;
  }

  /**
   * Calculates the name of the lineage root for a bill.
   *
   * @return string
   *   The "<session>-<print_number>" of the bill's lineage root.  If the bill
   *   has no previous versions, the bill's title is returned (i.e., its own
   *   lineage root).
   */
  public static function calculateLineageRoot(Node $bill): string {
    // Default return.
    $root = '';

    // Only act on bill nodes.
    if ($bill->bundle() == 'bill') {
      // Check the bill history.  Only concerned with same-chamber ancestors.
      $refs = [];
      $chamber = $bill->field_ol_chamber->value == 'senate' ? 'S' : 'A';
      $versions = json_decode($bill->field_ol_previous_versions->value ?? '') ?? [];
      foreach ($versions as $val) {
        if ($val->basePrintNo[0] == $chamber) {
          $refs[$val->session] = $val->basePrintNo;
        }
      }

      // Add this bill as a final backup (its own lineage root).
      $session = $bill->field_ol_session->value ?? '';
      if ($session && !isset($refs[$session])) {
        $refs[$session] = $bill->field_ol_base_print_no->value;
      }

      // Sort by year, grab the first one.
      ksort($refs, SORT_NUMERIC);
      $print_num = reset($refs);
      $session = key($refs);
      $root = "{$session}-{$print_num}";
    }

    return $root;
  }

  /**
   * Retrieves or creates a taxonomy term representing the bill's lineage root.
   */
  public static function getLineageRoot(Node $bill): ?Term {
    $root_name = static::calculateLineageRoot($bill);

    // Try to load that term.  If none is found, create one with that name.
    if (!($term = static::loadRootByName($root_name))) {
      if (($bill->hasField('field_ol_base_print_no') && !$bill->get('field_ol_base_print_no')->isEmpty())
            && ($bill->hasField('field_ol_session') && !$bill->get('field_ol_session')->isEmpty())
        ) {
        $term = static::createRoot($bill->field_ol_session->value, $bill->field_ol_print_no->value);
      }
    }

    // If something went wrong, tell somebody.
    if (!$term) {
      \Drupal::logger('nys_bill_notifications')
        ->error('Bill Lineage Helper could load/create a lineage root for @bill', ['@bill' => $bill->getTitle()]);
    }
    return $term;
  }

}
