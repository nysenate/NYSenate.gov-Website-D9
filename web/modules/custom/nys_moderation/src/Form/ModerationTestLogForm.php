<?php

namespace Drupal\nys_moderation\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to view/edit moderation test logs.
 */
class ModerationTestLogForm extends ContentEntityForm {

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\nys_moderation\Entity\ModerationTestLog $entity */
    $entity = $this->entity;

    $form['run_on'] = [
      '#markup' => '<div class="test-set-byline-container">' .
      'Run On ' . date('Y-m-d H:i', $entity->get('created')->value) .
      ' By ' . $entity->getRunByLink() . '</div>',
    ];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $entity->label(),
    ];

    $form['prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Prompt'),
      '#description' => $this->t('The prompt used to run this test (no tokens replaced)'),
      '#value' => $entity->get('prompt')->value,
      '#rows' => 5,
      '#disabled' => TRUE,
    ];

    $form['tags'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Tags/Notes'),
      '#description' => $this->t('Tags or notes about the test run, editable after the run.'),
      '#default_value' => $entity->get('tags')->value,
      '#rows' => 5,
      '#required' => FALSE,
    ];

    $form['item_summary'] = [
      '#type' => 'container',
      '#title' => 'Item Summary',
      '#description' => $this->t("The actual/expected states of each item's test."),
    ];
    $form['item_summary']['item_table'] = $this->buildItemTable();
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

    // Set the name and tags.
    $entity->set('name', $values['name']);
    $entity->set('tags', $values['tags']);

    // Save.
    try {
      $entity->save();
    }
    catch (\Throwable $e) {
      $this->messenger()->addError($e->getMessage());
      return;
    }
    $this->messenger()->addMessage($this->t('Name and tags have been saved.'));

  }

  /**
   * Builds the summary table for items in this run.
   */
  public function buildItemTable(): array {
    /** @var \Drupal\nys_moderation\Entity\ModerationTestLog $entity */
    $entity = $this->entity;
    $header = [
      'id' => 'Log Item ID',
      'tested' => "Entity Tested",
      'expected' => "Flag Expected?",
      'actual' => "Actual Result",
    ];
    $rows = [];
    /** @var \Drupal\nys_moderation\Entity\ModerationTestLogItem $logItem */
    foreach ($entity->logItems() as $logItem) {
      // The expected and passed fields use inverse logic.
      $expect = (bool) $logItem->get('expected')->value;
      $actual = (bool) $logItem->get('passed')->value;
      $class = [
        'expected-' . ($expect ? 'pass' : 'flag'),
        (($expect == $actual ? '' : 'un') . 'expected-result'),
      ];
      try {
        $target = $logItem->toLink($logItem->getTestedEntity()->label())->toString();
      }
      catch (\Throwable) {
        $target = "-- Could not load entity";
      }
      $rows[$logItem->id()] = [
        'id' => $logItem->id(),
        'tested' => $target,
        'expected' => $logItem->get('expected')->value ? 'Pass' : 'Flag',
        'actual' => $logItem->get('passed')->value ? 'Pass' : 'Flag',
        '#attributes' => ['class' => $class],
      ];
    }
    return [
      '#type' => 'tableselect',
      '#options' => $rows,
      '#header' => $header,
      '#empty' => "This log thread has no items.",
    ];

  }

}
