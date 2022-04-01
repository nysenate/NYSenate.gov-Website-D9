<?php

namespace Drupal\name\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\name\NameFormatterInterface;
use Drupal\name\Entity\NameListFormat;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form controller for name list formats.
 */
class NameListFormatForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('name.formatter')
    );
  }

  /**
   * Constructs a new NameListFormatForm object.
   *
   * @param \Drupal\name\NameFormatterInterface $formatter
   *   The name formatter.
   */
  public function __construct(NameFormatterInterface $formatter) {
    $this->formatter = $formatter;
  }

  /**
   * {@inheritdoc}
   */
  public function exists($entity_id, array $element, FormStateInterface $form_state) {
    return NameListFormat::load($entity_id);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $element = parent::form($form, $form_state);

    $element['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $this->entity->label(),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $element['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine-readable name'),
      '#description' => $this->t('A unique machine-readable name. Can only contain lowercase letters, numbers, and underscores.'),
      '#disabled' => !$this->entity->isNew(),
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
    ];

    $element['delimiter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Delimiter'),
      '#default_value' => $this->entity->delimiter,
      '#description' => $this->t('This specifies the delimiter between the second to last and the last name.'),
    ];
    $element['and'] = [
      '#type' => 'radios',
      '#title' => $this->t('Last delimiter type'),
      '#options' => $this->formatter->getLastDelimitorTypes(),
      '#default_value' => $this->entity->and,
      '#description' => $this->t('This specifies the delimiter between the second to last and the last name.'),
      '#required' => TRUE,
    ];
    $element['delimiter_precedes_last'] = [
      '#type' => 'radios',
      '#title' => $this->t('Standard delimiter precedes last delimiter'),
      '#options' => $this->formatter->getLastDelimitorBehaviors(),
      '#default_value' => $this->entity->delimiter_precedes_last,
      '#description' => $this->t('This specifies the delimiter between the second to last and the last name. Contextual means that the delimiter is only included for lists with three or more names.'),
      '#required' => TRUE,
    ];
    $options = range(1, 20);
    $options = array_combine($options, $options);
    $element['el_al_min'] = [
      '#type' => 'select',
      '#title' => $this->t('Reduce list and append <em>el al</em>'),
      '#options' => [0 => $this->t('Never reduce')] + $options,
      '#default_value' => $this->entity->el_al_min,
      '#description' => $this->t('This specifies a limit on the number of names to display. After this limit, names are removed and the abbrivation <em>et al</em> is appended. This Latin abbrivation of <em>et alii</em> means "and others".'),
      '#required' => TRUE,
    ];
    $element['el_al_first'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of names to display when using <em>el al</em>'),
      '#options' => $options,
      '#default_value' => $this->entity->el_al_first,
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save list format');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('name.name_list_format_list');
    if ($this->entity->isNew()) {
      $this->messenger()->addMessage($this->t('Name list format %label added.', ['%label' => $this->entity->label()]));
    }
    else {
      $this->messenger()->addMessage($this->t('Name list format %label has been updated.', ['%label' => $this->entity->label()]));
    }
    $this->entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, FormStateInterface $form_state) {
    $form_state['redirect_route'] = [
      'route_name' => 'name_list_format.delete_form',
      'route_parameters' => [
        'name_list_format' => $this->entity->id(),
      ],
    ];
  }

}
