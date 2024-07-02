<?php

namespace Drupal\node_export;

use Drupal\node\Entity\Node;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileRepository;

/**
 * Provides a Node Export function.
 */
class NodeExport {

  /**
   * Prepares a URI for the file to be saved.
   *
   * @param string $format
   *   The format of the file to be saved.
   *
   * @return string
   *   A URI for the file to be saved.
   */
  protected static function getFileUri($format) {
    return uniqid(\Drupal::config('system.file')->get('default_scheme') . '://node_export_') . '.' . strtolower($format);
  }

  /**
   * Prepares an array of field-value pairs of nodes.
   *
   * @param int[] $ids
   *   The ids of the nodes to be exported.
   *
   * @return array
   *   An array of field-value pairs of nodes.
   */
  protected static function getNodesArray(array $ids) {
    $nodes = empty($ids) ? Node::loadMultiple() : Node::loadMultiple($ids);
    $index = 0;
    $nodesArray = [];
    foreach ($nodes as $node) {
      foreach ($node as $key => $value) {
        $nodesArray[$index][$key] = $node->get($key)->getValue();
      }
      $index++;
    }
    return $nodesArray;
  }

  /**
   * Exports nodes to a specified format to a file/code.
   *
   * @param int[] $ids
   *   The ids of the nodes to be exported.
   * @param string $format
   *   The format of the file to be saved.
   * @param bool $save
   *   Whether to save the file or return the code.
   *
   * @return \Drupal\file\FileInterface|false|string
   *   Returns either the code to be exported, file entity or FALSE.
   */
  public static function export(array $ids, $format, $save) {
    $nodesArray = NodeExport::getNodesArray($ids);
    switch ($format) {
      case 'dsv':
        // TODO: Implement delimiter separated values.
        break;

      case 'json':
        $data = json_encode($nodesArray);
        break;

      case 'serialize':
        // TODO: Implement PHP serialized object.
        break;

      case 'xml':
        // TODO: Implement XML.
        break;
    }
    return $save ? \Drupal::service('file.repository')->writeData($data, NodeExport::getFileUri($format), FileSystemInterface::EXISTS_REPLACE) : $data;
  }

  /**
   * Batch operations method to export a node with given node ID.
   *
   * @param int $nid
   *   ID of node to be exported.
   * @param array $context
   *   An array of contextual key/value information for rebuild batch process.
   */
  public static function nodeExport($nid, array &$context) {
    $message = 'Exporting Nodes...';
    $context['results'][] = $nid;
    $context['message'] = $message;
  }

  /**
   * Batch finish callback method. Handles results & display messages to user.
   *
   * @param bool $success
   *   Whether or not any fatal PHP errors were detected.
   * @param array $results
   *   An array containing the results key of context array.
   * @param array $operations
   *   An array containing batch operations.
   */
  public static function nodeExportFinishedCallback($success, array $results, array $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One node exported ', '@count nodes exported '
      );
    }
    else {
      $message = $this->t('Finished with an error.');
    }
    $file = NodeExport::export($results, 'json', TRUE);
    if ($file) {
      $message = $message . ' to ' . \Drupal::service('file_system')->realpath($file->getFileUri());
      \Drupal::messenger()->addStatus($message);
    }
  }

}
