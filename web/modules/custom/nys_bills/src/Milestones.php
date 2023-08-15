<?php

namespace Drupal\nys_bills;

use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\node\NodeInterface;
use Psr\Log\LoggerInterface;

/**
 * Middleman to assist with rendering bill milestones in a graphic.
 */
class Milestones {

  use LoggerChannelTrait;

  /**
   * Defines text, position, and visibility for bill status milestones.
   */
  const STATUS_TEXT = [
    'ADOPTED' => 'Adopted',
    'ASSEMBLY_FLOOR' => 'On Floor Calendar Assembly',
    'DELIVERED_TO_GOV' => 'Delivered to Governor',
    'INTRODUCED' => 'Introduced',
    'IN_ASSEMBLY_COMM' => 'In Committee Assembly',
    'IN_SENATE_COMM' => 'In Committee Senate',
    'LOST' => 'Lost',
    'PASSED_ASSEMBLY' => 'Passed Assembly',
    'PASSED_SENATE' => 'Passed Senate',
    'POCKET_APPROVAL' => 'Chaptered/Pocket Approval',
    'SENATE_FLOOR' => 'On Floor Calendar Senate',
    'SIGNED_BY_GOV' => 'Signed By Governor',
    'STRICKEN' => 'Stricken',
    'SUBSTITUTED' => 'Substituted',
    'VETOED' => 'Vetoed By Governor',
  ];

  /**
   * A bill or resolution node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node;

  /**
   * A logging facility for 'nys_bills:milestones'.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $log;

  /**
   * Constructor.
   *
   * @param \Drupal\node\NodeInterface|null $node
   *   Must be a bill or resolution node, or NULL.
   */
  public function __construct(?NodeInterface $node = NULL) {
    $this->log = $this->getLogger('nys_bills:milestones');

    if ($node) {
      $this->setNode($node);
    }
  }

  /**
   * Getter for Node.
   */
  public function getNode(): NodeInterface {
    return $this->node;
  }

  /**
   * Setter for Node.
   */
  public function setNode(NodeInterface $node): void {
    if (!(in_array($node->bundle(), ['bill', 'resolution']))) {
      throw new \InvalidArgumentException("Require a node from 'bill' or 'resolution' bundles");
    }
    $this->node = $node;
  }

  /**
   * Retrieve the milestone data from a bill node.
   *
   * @return array
   *   Returns an empty array on error.  Otherwise, an array keyed by
   *   milestone type, with values being the milestone object from OL API.
   *
   * @see self::STATUS_TEXT
   */
  public function getFromNode(): array {
    if (!($node = $this->getNode())) {
      $this->log->warning("Can't get milestones from NULL");
      return [];
    }

    try {
      $raw = json_decode($node->get('field_ol_all_statuses')->getString());
    }
    catch (\Throwable) {
      $raw = new \stdClass();
      $this->log->warning(
        "Could not decode bill milestones for @node",
        ['@node' => $node->id()]
      );
    }

    $ret = [];
    foreach (($raw->items ?? []) as $item) {
      if ($type = ($item->statusType ?? '')) {
        $ret[$type] = $item;
      }
    }

    return $ret;
  }

  /**
   * Initializes an array of all possible milestones.
   *
   * All milestones are initially set as 'not passed' ('pass' = FALSE).  The
   * position is set from the input parameter, or -1 if not available.  The
   * keys of the return are the same as in self::STATUS_TEXT.
   *
   * @param array $positions
   *   An array of ['key' => <position>], where position can be -1 for hidden,
   *   or an integer 0-5 for pip number.
   *
   * @return array
   *   An array of milestone templates, keyed by type name.
   *
   * @see self::STATUS_TEXT
   */
  protected function initialize(array $positions = []): array {
    $template = ['pass' => FALSE, 'data' => NULL];
    $ret = [];
    foreach (self::STATUS_TEXT as $name => $text) {
      $ret[$name] = ['text' => $text, 'pos' => ($positions[$name] ?? -1)]
        + $template;
    }
    return $ret;
  }

  /**
   * Prepares a milestone data structure appropriate for template renderings.
   *
   * The milestones are always used to render the bill status graph.  The graph
   * has 6 pips (numbered 0-5).  Each position may have multiple entries.
   *
   * In the current iteration, the INTRODUCED milestone is always set as having
   * been reached.  Pips 1-3 have two entries each - one for senate and one for
   * assembly.  Only the SIGNED_BY_GOV milestone is added to pip 5. Others
   * (e.g., ADOPTED) are hidden. If they are the actual last status, the text
   * of pip 5 is set appropriately.
   *
   * @return array
   *   An array with one element for each pip on the graph.  Each element is
   *   itself an array, holding all milestones to be rendered at that pip. On
   *   error, an empty array is returned.
   */
  public function calculate(): array {
    if (!($node = $this->getNode())) {
      return [];
    }

    // Which items are shown on which pip.  Any items not listed will be hidden.
    $positions = [
      'INTRODUCED' => 0,
      'IN_ASSEMBLY_COMM' => 1,
      'IN_SENATE_COMM' => 1,
      'ASSEMBLY_FLOOR' => 2,
      'SENATE_FLOOR' => 2,
      'PASSED_ASSEMBLY' => 3,
      'PASSED_SENATE' => 3,
      'DELIVERED_TO_GOV' => 4,
      'SIGNED_BY_GOV' => 5,
    ];

    // Get the starting list.
    $data = $this->initialize($positions);

    // For each node milestone, set the pass state and item data.
    $milestones = $this->getFromNode();
    foreach ($milestones as $key => $item) {
      if (array_key_exists($key, $data)) {
        $data[$key]['pass'] = TRUE;
        $data[$key]['data'] = $item;
      }
    }

    /*
     * Make any necessary changes:
     *   - 'INTRODUCED' is always passed.
     *   - Set the final step text based on last status.
     */
    $data['INTRODUCED']['pass'] = TRUE;
    $last_status = $this->findLastStatus($node);
    switch ($last_status) {
      case 'VETOED':
      case 'POCKET_APPROVAL':
      case 'ADOPTED':
        $data['DELIVERED_TO_GOV']['text'] = self::STATUS_TEXT[$last_status];
        break;

      default:
        break;
    }

    // Build the 6-pip array.
    $ret = [0 => [], 1 => [], 2 => [], 3 => [], 4 => [], 5 => []];
    foreach ($data as $item) {
      if (($item['pos'] ?? -1) !== -1) {
        $ret[$item['pos']][] = $item;
      }
    }

    return $ret;
  }

  /**
   * Find the last status of a bill or resolution node.
   *
   * @return string
   *   The text (key) of the detected last status.  On error, an empty string.
   */
  protected function findLastStatus(NodeInterface $node): string {
    try {
      switch ($node->bundle()) {
        // We don't track milestones on resolutions.  This field is the saved
        // JSON of all actions.  The detected status may not be an actionable
        // status at all.
        case 'resolution':
          $statuses = json_decode($node->get('field_ol_all_statuses')->value ?? '');
          $last_status = is_array($statuses->items) ? end($statuses->items) : NULL;
          $ret = $last_status->text ?? '';
          break;

        case 'bill':
          $ret = $node->get('field_ol_last_status')->value ?? '';
          break;

        default:
          $ret = '';
          break;
      }
    }
    catch (\Throwable) {
      $ret = '';
    }

    return $ret;
  }

}
