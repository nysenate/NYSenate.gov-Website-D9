<?php

namespace Drupal\nys_moderation\Form;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to add/edit moderation test sets.
 */
class ModerationTestSetForm extends EntityForm {

  /**
   * The Entity Storage handler for this set's entity type.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $storage;

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\nys_moderation\Entity\ModerationTestSet $entity */
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $entity->label(),
      '#required' => TRUE,
      '#description' => $this->t('Display Name for the test set.'),
    ];

    // @todo this needs to be selected before the entities can be listed.
    // Consider a prettier way to do this.
    $form['automator'] = [
      '#type' => 'select',
      '#options' => $this->getAutomatorOptions(),
      '#title' => $this->t('Automator'),
      '#default_value' => $entity->getAutomatorId(),
      '#description' => $this->t('Which field automation will be used for this test'),
    ];

    // @todo expand this to other entities.
    $form['entity_type'] = [
      '#type' => 'hidden',
      '#options' => ['node' => 'Node'],
      '#title' => $this->t('Entity type'),
      '#value' => 'node',
      '#description' => $this->t('Should always be "node" for now'),
    ];

    $form['entities'] = [
      '#type' => 'tableselect',
      '#options' => $this->getEntityOptions($form_state),
      '#default_value' => array_combine(
        $entity->get('entities') ?? [],
        $entity->get('entities') ?? []
      ),
      '#header' => [
        'type' => 'Type',
        'title' => 'Title',
        'senator' => 'Senator',
        'expected' => 'Expected',
      ],
      '#empty' => $this->t('Could not load any candidates.  Has an automator been selected and saved?'),
      '#title' => $this->t('Entities'),
      '#description' => $this->t('Which entities will be tested'),
      '#prefix' => '<div class="nys-moderation-entity-select-container">',
      '#suffix' => '</div>',
    ];

    $form['#attached']['library'][] = 'nys_moderation/nys_moderation_test_sets';

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'nys_moderation_test_set_form';
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\nys_moderation\Entity\ModerationTestSet $entity */
    $entity = $this->entity;
    $values = $form_state->getValues();

    // Format the entity list for storage.
    $entity->set('entities', array_keys(array_filter($values['entities'])));

    // Ensure the ID field is populated with the machine name version of label.
    if (!$entity->id()) {
      $entity->set('id', $this->getMachineName());
    }

    // Save and go somewhere reasonable.
    try {
      $entity->save();
    }
    catch (\Throwable $e) {
      $this->messenger()->addError($e->getMessage());
      return;
    }
    $this->messenger()
      ->addMessage($this->t('Saved Test Set "@name"', ['@name' => $entity->label()]));

    // If entities have been selected, go back to the list.
    if (count($entity->get('entities') ?? [])) {
      $redirect = 'entity.moderation_test_set.collection';
      $params = [];
    }
    // Otherwise, back to editing the new set.
    else {
      $redirect = 'entity.moderation_test_set.edit_form';
      $params = ['moderation_test_set' => $entity->id()];
    }
    $form_state->setRedirect($redirect, $params);
  }

  /**
   * Makes a "safe" version of the label, 150 characters max.
   */
  protected function getMachineName(): string {
    $ret = preg_replace('/[^- _[:alnum:]]/', '', $this->entity->label());
    $ret = preg_replace('/[^[:alnum:]]+/', '_', $ret);
    return substr(strtolower($ret), 0, 150);
  }

  /**
   * Instantiates the storage handler for the set's entity type.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function storage(): EntityStorageInterface {
    if (empty($this->storage)) {
      $this->storage = $this->entityTypeManager->getStorage($this->entity?->get('entity_type'));
    }
    return $this->storage;
  }

  /**
   * Retrieves a list of options based on known configured automators.
   */
  protected function getAutomatorOptions(): array {
    // Set a default return in case of error.
    $automators = ['' => 'Could not load automators!'];

    // Try to load all configured automators.
    try {
      $all = $this->entityTypeManager->getStorage('ai_automator')
        ->loadMultiple();
      $automators = ['' => 'Choose An Automator'];
    }
    catch (\Throwable $e) {
      $all = [];
      $this->messenger()
        ->addError('Could not load automators! @msg', ['@msg' => $e->getMessage()]);
    }

    // Build the options.
    foreach ($all as $id => $one) {
      /** @var \Drupal\ai_automators\Entity\AiAutomator $one */
      $type = ($one->get('entity_type') ?? 'Unknown Type');
      if ($type != 'automator_chain') {
        $bundle = $one->get('bundle') ?: ($one->get('entity_type') ?? 'Unknown Bundle');
        $field = $one->get('field_name') ?? 'Unknown Field';
        $automators[$id] = $one->label() . " ($type:$bundle:$field)";
      }
    }
    return $automators;
  }

  /**
   * Creates the option list for available entities to test.
   */
  protected function getEntityOptions(FormStateInterface $formState): array {
    // Load the possible entities.
    try {
      $nodes = $this->findCandidateEntities($formState);
    }
    catch (\Throwable) {
      $nodes = [];
    }

    // Build the options rows.
    $rows = [];
    foreach ($nodes as $entity) {
      $rows[$entity->id()] = $this->buildEntityOptionRow($entity);
    }

    return $rows;
  }

  /**
   * Loads all valid candidates for inclusion in a test set.
   *
   * Search is limited to the entity type configured for the set.
   *
   * @todo Consider filter/sort options from the form state.
   *
   * @todo Only loads nodes right now.  Needs expansion.
   */
  protected function findCandidateEntities(FormStateInterface $form_state): array {
    try {
      // Load only nodes marked for testing.
      $found = $this->storage()->getQuery()
        ->accessCheck(FALSE)
        ->condition('field_ai_test', 1)
        ->sort('nid')
        ->execute();
      $nodes = $this->storage()->loadMultiple($found);
    }
    catch (\Throwable) {
      $nodes = [];
    }
    return $nodes;
  }

  /**
   * Render a single row for the tableselect list of entities.
   */
  protected function buildEntityOptionRow(ContentEntityInterface $entity): array {
    // @todo this is specific to node:article.  Need a builder for this.
    // @todo This should be a field render.
    $senator = $entity->get('field_senator_multiref')
      ->referencedEntities()[0]
      ?->getName() ?? 'Unknown';

    // @todo Alternative to inline style?
    return [
      'type' => [
        'data' => $entity->id() . " (" . $entity->bundle() . ")",
        'style' => 'min-width:fit-content;white-space:nowrap',
      ],
      'title' => $entity->label(),
      'senator' => [
        'data' => $senator,
        'style' => 'min-width:fit-content;white-space:nowrap',
      ],
      'expected' => $entity->get('field_flag')->value == 1 ? 'Flag' : 'Pass',
      '#attributes' => [
        'class' => ($entity->get('field_flag')->value == 1 ? 'expected-fail' : 'expected-pass'),
      ],
    ];
  }

}
