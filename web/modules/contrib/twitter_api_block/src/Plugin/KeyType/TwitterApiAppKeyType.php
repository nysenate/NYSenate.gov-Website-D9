<?php

namespace Drupal\twitter_api_block\Plugin\KeyType;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyTypeBase;
use Drupal\key\Plugin\KeyTypeMultivalueInterface;

/**
 * Defines a custom key type for Twitter API v2 application.
 *
 * @KeyType(
 *   id = "twitter_api_app",
 *   label = @Translation("Twitter API app"),
 *   description = @Translation("A set of credentials for a Twitter application. Create a new application at <a href='https://apps.twitter.com/' target='_blank'>https://apps.twitter.com/</a>."),
 *   group = "authentication",
 *   key_value = {
 *     "plugin" = "twitter_api_app",
 *     "accepted" = FALSE,
 *   },
 *   multivalue = {
 *     "enabled" = true,
 *     "fields" = {
 *       "client_id" = {
 *         "label" = @Translation("Client ID"),
 *         "required" = true
 *       },
 *       "client_secret" = {
 *         "label" = @Translation("Client secret"),
 *         "required" = true
 *       },
 *       "bearer_token" = {
 *         "label" = @Translation("Bearer token"),
 *         "required" = false
 *       },
 *       "access_token" = {
 *         "label" = @Translation("Access token"),
 *         "required" = false
 *       },
 *       "access_secret" = {
 *         "label" = @Translation("Access secret"),
 *         "required" = false
 *       },
 *     }
 *   }
 * )
 */
class TwitterApiAppKeyType extends KeyTypeBase implements KeyTypeMultivalueInterface {

  /**
   * {@inheritdoc}
   */
  public static function generateKeyValue(array $configuration) {
    return Json::encode($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function validateKeyValue(array $form, FormStateInterface $form_state, $key_value) {
    if (empty($key_value)) {
      $form_state->setError($form, $this->t('The key value is empty.'));
      return;
    }

    $definition = $this->getPluginDefinition();
    $fields = $definition['multivalue']['fields'];

    foreach ($fields as $id => $field) {
      if (!is_array($field)) {
        $field = ['label' => $field];
      }

      if (isset($field['required']) && $field['required'] === FALSE) {
        continue;
      }

      if (!isset($key_value[$id])) {
        $form_state->setError($form, $this->t('The key value is missing the field %field.', ['%field' => $id]));
      }
      elseif (empty($key_value[$id])) {
        $form_state->setError($form, $this->t('The key value field %field is empty.', ['%field' => $id]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function serialize(array $array) {
    return Json::encode($array);
  }

  /**
   * {@inheritdoc}
   */
  public function unserialize($value) {
    return Json::decode($value);
  }

}
