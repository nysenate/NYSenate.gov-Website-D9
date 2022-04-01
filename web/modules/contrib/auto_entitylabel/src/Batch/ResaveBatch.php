<?php

namespace Drupal\auto_entitylabel\Batch;

use Drupal\comment\Entity\Comment;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Processes entities in chunks to re-save their labels.
 *
 * @package Drupal\auto_entitylabel\Batch
 */
class ResaveBatch {

  /**
   * {@inheritdoc }
   */
  public static function batchOperation(array $chunk, array $bundle, array &$context) {
    foreach ($chunk as $id) {
      $entity = '';
      switch ($bundle[0]) {
        case 'node':
          $entity = Node::load($id);
          break;

        case 'taxonomy_term':
          $entity = Term::load($id);
          break;

        case 'media':
          $entity = Media::load($id);
          break;

        case 'comment':
          $entity = Comment::load($id);
          break;

      }
      $entity->save();
      $context['results'][] = $id;
    }

  }

  /**
   * {@inheritdoc }
   */
  public static function batchFinished($success, array $results, array $operations) {
    $messenger = \Drupal::messenger();

    if ($success) {
      $messenger->addMessage(t('Resaved @count labels.', [
        '@count' => count($results),
      ]));
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $message = t('An error occurred while processing %error_operation with arguments: @arguments', [
        '%error_operation' => $error_operation[0],
        '@arguments' => print_r($error_operation[1], TRUE),
      ]);
      $messenger->addError($message);
    }
  }

}
