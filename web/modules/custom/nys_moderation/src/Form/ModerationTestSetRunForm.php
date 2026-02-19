<?php

namespace Drupal\nys_moderation\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\nys_moderation\Service\PromptTest;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles execution of a test run, and creation of associated log entries.
 */
class ModerationTestSetRunForm extends EntityForm {

  /**
   * NYS Moderation PromptTest service.
   *
   * @var \Drupal\nys_moderation\Service\PromptTest
   */
  protected PromptTest $promptTest;

  /**
   * Constructor for dependency injection.
   */
  public function __construct(PromptTest $promptTest) {
    $this->promptTest = $promptTest;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('nys_moderation.prompt_test')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form = parent::buildForm($form, $form_state);

    $form['preface'] = [
      '#type' => 'markup',
      '#markup' => $this->getPreface(),
    ];

    $form['run_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#placeholder' => $this->getDefaultName(),
    ];

    $form['tags'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Tags/Notes'),
      '#description' => $this->t('Tags or notes about the test run, editable after the run.'),
      '#rows' => 5,
      '#required' => FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\nys_moderation\Entity\ModerationTestSet $test */
    $test = $this->entity;
    $name = $form_state->getValue('run_name') ?: $this->getDefaultName();
    $tags = $form_state->getValue('tags');

    $result = $this->promptTest->run($test, $name, $tags);

    if ($result) {
      $this->messenger()
        ->addMessage(
          $this->t('Completed run for test set @name.', ['@name' => $name]));
    }
    else {
      $this->messenger()
        ->addError(
          $this->t('Failed running test set @name.', ['@name' => $name]));
    }

    $form_state->setRedirect('entity.moderation_test_set.collection');
  }

  /**
   * A default name for a test run.
   */
  protected function getDefaultName(): string {
    return "Test Run " . date("Y-m-d H:i:s");
  }

  /**
   * Define the markup for the form preface.
   */
  protected function getPreface(): string {
    /** @var \Drupal\nys_moderation\Entity\ModerationTestSet $entity */
    $entity = $this->entity;
    return 'Running test set "' . $entity->label() .
      '" (id: ' . $entity->id() . '), entity count: ' .
      count($entity->getTargetList());
  }

}
