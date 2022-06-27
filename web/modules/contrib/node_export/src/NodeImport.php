<?php

namespace Drupal\node_export;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;

/**
 * Provides a Node Import function.
 */
class NodeImport {

  /**
   * Prepares a field-value array for the node to be imported.
   *
   * @param array $nodeArray
   *   An exported node array.
   *
   * @return array|bool
   *   Returns an array containing field-value pairs of the node or FALSE.
   */
  protected static function unload(array $nodeArray) {
    $fieldValues = [];
    $contentType = $nodeArray['type'][0]['target_id'];
    if (self::contentTypeExists($contentType)) {
      $bundleFields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $contentType);
      foreach ($nodeArray as $fields => $value) {
        if (!array_key_exists($fields, $bundleFields)) {
          return FALSE;
        }
        $fieldValues[$fields] = $value;
      }
      $fieldValues['type'] = $contentType;
    }
    return $fieldValues;
  }

  /**
   * Checks whether a given content type exists or not.
   *
   * @param string $contentType
   *   Content type to be checked.
   *
   * @return bool
   *   Whether a given content type exists or not.
   */
  protected static function contentTypeExists($contentType) {
    $contentTypes = \Drupal::service('entity_type.manager')
      ->getStorage('node_type')
      ->loadMultiple();
    return array_key_exists($contentType, $contentTypes);
  }

  /**
   * Imports a node with given field-value array.
   *
   * @param array $nodeArray
   *   An exported node array.
   *
   * @return int|bool
   *   Returns the ID of the imported node or FALSE if import is not possible.
   */
  public static function import(array $nodeArray) {

    $fieldValues = self::unload($nodeArray);
    if (!$fieldValues) {
      return FALSE;
    }
    $config = \Drupal::config('node_export.settings');
    $operation = $config->get('node_export_import');
    $id = $fieldValues['nid'][0]['value'];

    unset($fieldValues['nid']);
    unset($fieldValues['vid']);
    unset($fieldValues['uuid']);

    switch ($operation) {
      case 'new':
        $newNode = Node::create($fieldValues);
        break;

      case 'replace':
        $newNode = Node::load($id);
        if ($newNode) {
          foreach ($fieldValues as $field => $values) {
            $newNode->set($field, $values);
          }
          $newNode->setNewRevision(TRUE);
          $newNode->setRevisionCreationTime(\Drupal::time()->getRequestTime());
          $newNode->setRevisionUserId($fieldValues['uid'][0]['target_id']);
        }
        else {
          $newNode = Node::create($fieldValues);
        }
        break;

      case 'skip':
        if (Node::load($id)) {
          return $id;
        }
        $newNode = Node::create($fieldValues);
        break;
    }

    try {
      $newNode->save();
      $id = $newNode->id();
    }
    catch (EntityStorageException $e) {
      \Drupal::logger('node_export')->error($e->getMessage());
    }
    return $id;
  }

  /**
   * Batch operations method to import a given node object.
   *
   * @param array $nodeArray
   *   An exported node array.
   * @param array $context
   *   An array of contextual key/value information for rebuild batch process.
   */
  public static function nodeImport(array $nodeArray, array &$context) {
    $message = 'Importing Nodes...';
    $context['message'] = $message;
    $id = NodeImport::import($nodeArray);
    if ($id) {
      $context['results']['imported'][] = $id;
    }
    else {
      $context['results']['not_imported'][] = $id;
    }
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
  public static function nodeImportFinishedCallback($success, array $results, array $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = '';
      if (array_key_exists('imported', $results) && count($results['imported']) > 0) {
        $message .= \Drupal::translation()->formatPlural(
          count($results['imported']),
          'One node imported.', '@count nodes imported.'
        );
      }
      if (array_key_exists('not_imported', $results) && count($results['not_imported']) > 0) {
        $message .= "\n" . \Drupal::translation()->formatPlural(
          count($results['not_imported']),
          'One node could not be imported.', '@count nodes could not be imported.'
        );
      }
    }
    else {
      $message = $this->t('Finished with an error.');
    }
    \Drupal::messenger()->addStatus($message);
  }

}
