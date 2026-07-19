<?php

namespace Drupal\nys_senators\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for live access to senators' JSON feed.
 */
class SenatorJsonFeed extends ControllerBase {

  /**
   * Returns a redirect to the nys_feeds 'senators' feed.
   *
   * @param string $shortname
   *   An optional senator shortname.  If available, only the matching
   *   senator will be included in the feed.
   */
  public function getFeed(string $shortname = ''): RedirectResponse {
    $options = $shortname ? ['query' => ['shortname' => $shortname]] : [];
    return $this->redirect('nys_feeds.feed_factory', ['series' => 'senators'], $options, 301);
  }

}
