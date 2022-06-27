<?php

namespace Drupal\node_export\Commands;

use Drush\Commands\DrushCommands;
use Drupal\node_export\NodeExport;
use Drupal\node_export\NodeImport;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class NodeExportCommands extends DrushCommands {

  /**
   * Export nodes.
   *
   * @param string $nodes
   *   IDs of the nodes to be exported.
   * @param array $options
   *   Array of options for the command.
   *
   * @options save
   *   An option to specify whether or not to save the file.
   *
   * @usage node-export-export 1,2,3,4,5 OR all
   *   Export all nodes or specify the nids.
   *
   * @command node-export-export
   *
   * @aliases ne-export
   */
  public function exportNode($nodes = 'all', array $options = ['save' => 'n']) {
    $save = substr(strtolower($options['save']), 0, 1) === 'y';
    $ids = $nodes === 'all' ? [] : explode(',', ltrim($nodes));
    $export = NodeExport::export($ids, 'json', $save);
    if ($save) {
      if ($export) {
        $this->logger()->success(dt('Nodes exported to ' .
          \Drupal::service('file_system')->realpath($export->getFileUri())));
      }
      else {
        $this->logger()->error('Could not export the nodes.');
      }
    }
    else {
      $this->writeln($export);
    }
  }

  /**
   * Import nodes.
   *
   * @param string $file
   *   File containing exported node code.
   *
   * @usage node-export-import '/path/to/file.json'
   *   Import nodes from a given file.
   *
   * @command node-export-import
   *
   * @aliases ne-import
   */
  public function importNode($file) {
    $data = file_get_contents($file);
    if ($data) {
      $nodes = json_decode($data, TRUE);
      $countImported = 0;
      $countNotImported = 0;
      foreach ($nodes as $node) {
        $id = NodeImport::import($node);
        $id ? $countImported++ : $countNotImported++;
      }
      if ($countImported > 0) {
        $this->logger()->success(dt('{count} nodes imported successfully.', ['count' => $countImported]));
      }
      if ($countNotImported > 0) {
        $this->logger()->error(dt('{count} nodes could not be imported.', ['count' => $countNotImported]));
      }
    }
    else {
      $this->logger()->error('Could not read the file.');
    }
  }

}
