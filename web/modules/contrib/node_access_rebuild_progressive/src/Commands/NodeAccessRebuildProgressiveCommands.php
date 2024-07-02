<?php

namespace Drupal\node_access_rebuild_progressive\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Consolidation\SiteAlias\SiteAliasManagerAwareInterface;
use Consolidation\SiteAlias\SiteAliasManagerAwareTrait;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 */
class NodeAccessRebuildProgressiveCommands extends DrushCommands implements SiteAliasManagerAwareInterface {

  use SiteAliasManagerAwareTrait;

  /**
   * Fully rebuild node access.
   *
   * @command node-access-rebuild-progressive:rebuild
   * @aliases node-access-rebuild-progressive
   */
  public function accessRebuildProgressive(): CommandResult {
    return _drush_node_access_rebuild_progressive_rebuild($this->siteAliasManager());
  }

}
