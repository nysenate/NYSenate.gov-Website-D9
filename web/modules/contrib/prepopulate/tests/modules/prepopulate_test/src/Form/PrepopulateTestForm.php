<?php

namespace Drupal\prepopulate_test\Form;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PrepopulateTestForm.
 */
class PrepopulateTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'prepopulate_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['checkboxes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Checkboxes'),
      '#options' => [
        'black' => $this->t('Black'),
        'blue' => $this->t('Blue'),
        'green' => $this->t('Green'),
        'red' => $this->t('Red'),
        'white' => $this->t('White'),
        'yellow' => $this->t('Yellow'),
      ],
    ];
    $form['date'] = [
      '#type' => 'date',
      '#title' => $this->t('Date'),
    ];
    $form['datelist'] = [
      '#type' => 'datelist',
      '#title' => $this->t('Datelist'),
    ];
    $form['datetime'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Datetime'),
    ];
    $form['entity_autocomplete'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#title' => $this->t('Node Autocomplete'),
    ];
    $form['entity_autocreate'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'taxonomy_term',
      '#title' => $this->t('Term Autocreate'),
      '#autocreate' => [
        'bundle' => 'tags',
      ],
    ];
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
    ];
    $form['machine_name'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine Name'),
      '#machine_name' => ['exists' => '\Drupal\views\Views::getView'],
      '#required' => FALSE,
    ];
    $form['number'] = [
      '#type' => 'number',
      '#title' => $this->t('Number'),
    ];
    $form['path'] = [
      '#type' => 'path',
      '#title' => $this->t('Path'),
    ];
    $form['radios'] = [
      '#type' => 'radios',
      '#title' => $this->t('Radios'),
      '#options' => [
        'africa' => $this->t('Africa'),
        'antarctica' => $this->t('Antarctica'),
        'asia' => $this->t('Asia'),
        'australia' => $this->t('Australia'),
        'europe' => $this->t('Europe'),
        'north america' => $this->t('North America'),
        'south america' => $this->t('South America'),
      ],
    ];
    $form['telephone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Telephone'),
    ];
    $form['textarea'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Textarea'),
    ];
    $form['textfield'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Textfield'),
    ];
    $form['select'] = [
      '#type' => 'select',
      '#title' => $this->t('Select'),
      '#options' => [
        'north' => $this->t('North'),
        'south' => $this->t('South'),
        'east' => $this->t('East'),
        'west' => $this->t('West'),
      ],
    ];
    $form['url'] = [
      '#type' => 'url',
      '#title' => $this->t('Url'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['#cache']['max-age'] = 0;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      if ($value instanceof MarkupInterface) {
        $value = (string) $value;
      }
      elseif ($value instanceof DrupalDateTime) {
        $value = (string) $value;
      }
      elseif (isset($value['entity']) && $value['entity'] instanceof EntityInterface) {
        $entity = $value['entity'];
        $entity->save();
        $value = "{$entity->label()} ({$entity->id()})";
      }
      else {
        $value = var_export($value, TRUE);
      }
      $this->messenger()->addStatus($key . ': ' . $value);
    }
  }

}
