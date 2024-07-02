<?php

namespace Drupal\entityqueue\Plugin\ECA\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\ECA\Condition\ConditionBase;
use Drupal\entityqueue\Entity\EntitySubqueue;

/**
 * Plugin implementation of the ECA condition "Entity is in Subqueue".
 *
 * @EcaCondition(
 *   id = "entityqueue_entity_is_in_subqueue",
 *   label = @Translation("Entity is in Subqueue"),
 *   description = @Translation("A particular entity is in a subqueue"),
 *   context_definitions = {
 *     "entity" = @ContextDefinition("entity", label = @Translation("Entity"))
 *   }
 * )
 */
class EntityIsInSubqueueCondition extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    $entity = $this->getValueFromContext('entity');
    $subqueue = EntitySubqueue::load($this->configuration['subqueue']);
    $result = $subqueue->hasItem($entity);
    return $this->negationCheck($result);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'subqueue' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['subqueue'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subqueue'),
      '#default_value' => $this->configuration['subqueue'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['subqueue'] = $form_state->getValue('subqueue');
    parent::submitConfigurationForm($form, $form_state);
  }

}
