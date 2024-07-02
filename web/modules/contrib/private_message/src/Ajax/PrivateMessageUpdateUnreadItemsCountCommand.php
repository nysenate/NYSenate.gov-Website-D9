<?php

namespace Drupal\private_message\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Ajax command to update the number of unread threads.
 */
class PrivateMessageUpdateUnreadItemsCountCommand implements CommandInterface {

  /**
   * The number of unread threads.
   *
   * @var int
   */
  protected $unreadItemsCount;

  /**
   * Constructs a PrivateMessageMembersAutocompleteResponseCommand object.
   *
   * @param int $unreadItemsCount
   *   The number of unread threads.
   */
  public function __construct($unreadItemsCount) {
    $this->unreadItemsCount = $unreadItemsCount;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'privateMessageUpdateUnreadItemsCount',
      'unreadItemsCount' => $this->unreadItemsCount,
    ];
  }

}
