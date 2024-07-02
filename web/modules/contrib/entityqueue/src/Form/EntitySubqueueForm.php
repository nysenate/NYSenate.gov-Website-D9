<?php

namespace Drupal\entityqueue\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\entity_browser\Plugin\Field\FieldWidget\EntityReferenceBrowserWidget;
use Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the entity subqueue edit forms.
 */
class EntitySubqueueForm extends ContentEntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\entityqueue\EntitySubqueueInterface
   */
  protected $entity;

  /**
   * The element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('element_info')
    );
  }

  /**
   * Constructs a EntitySubqueueForm.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element info manager.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, ElementInfoManagerInterface $element_info) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->elementInfo = $element_info;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    if ($this->operation === 'add') {
      $form['#title'] = $this->t('Add subqueue');
    }
    else {
      $form['#title'] = $this->t('Edit subqueue %label', ['%label' => $this->entity->label()]);
    }

    // Since the form has ajax buttons, the $wrapper_id will change each time
    // one of those buttons is clicked. Therefore the whole form has to be
    // replaced, otherwise the buttons will have the old $wrapper_id and will
    // only work on the first click.
    if ($form_state->has('subqueue_form_wrapper_id')) {
      $wrapper_id = $form_state->get('subqueue_form_wrapper_id');
    }
    else {
      $wrapper_id = Html::getUniqueId($this->getFormId() . '-wrapper');
    }

    $form_state->set('subqueue_form_wrapper_id', $wrapper_id);
    $form['#prefix'] = '<div id="' . $wrapper_id . '">';
    $form['#suffix'] = '</div>';

    // @todo Use the 'Machine name' field widget when
    //   https://www.drupal.org/node/2685749 is committed.
    $element_info = $this->elementInfo->getInfo('machine_name');
    $form['name'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#source_field' => 'title',
      '#process' => array_merge([[get_class($this), 'processMachineNameSource']], $element_info['#process']),
      '#machine_name' => [
        'exists' => '\Drupal\entityqueue\Entity\EntitySubqueue::load',
      ],
      '#disabled' => !$this->entity->isNew(),
      '#weight' => -5,
      '#access' => !$this->entity->getQueue()->getHandlerPlugin()->hasAutomatedSubqueues(),
    ];

    return $form;
  }

  /**
   * Form API callback: Sets the 'source' property of a machine_name element.
   *
   * This method is assigned as a #process callback in formElement() method.
   */
  public static function processMachineNameSource($element, FormStateInterface $form_state, $form) {
    $source_field_state = WidgetBase::getWidgetState($form['#parents'], $element['#source_field'], $form_state);

    // Hide the field widget if the source field is not configured properly or
    // if it doesn't exist in the form.
    if (empty($element['#source_field']) || empty($source_field_state['array_parents'])) {
      $element['#access'] = FALSE;
    }
    else {
      $source_field_element = NestedArray::getValue($form_state->getCompleteForm(), $source_field_state['array_parents']);
      $element['#machine_name']['source'] = $source_field_element[0]['value']['#array_parents'];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $actions['reverse'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reverse'),
      '#submit' => ['::submitAction'],
      '#op' => 'reverse',
      '#ajax' => [
        'callback' => '::subqueueActionAjaxForm',
        'wrapper' => $form_state->get('subqueue_form_wrapper_id'),
      ],
    ];

    $actions['shuffle'] = [
      '#type' => 'submit',
      '#value' => $this->t('Shuffle'),
      '#submit' => ['::submitAction'],
      '#op' => 'shuffle',
      '#ajax' => [
        'callback' => '::subqueueActionAjaxForm',
        'wrapper' => $form_state->get('subqueue_form_wrapper_id'),
      ],
    ];

    $actions['clear'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear'),
      '#submit' => ['::submitAction'],
      '#op' => 'clear',
      '#ajax' => [
        'callback' => '::subqueueActionAjaxForm',
        'wrapper' => $form_state->get('subqueue_form_wrapper_id'),
      ],
    ];

    return $actions;
  }

  /**
   * Submit callback for the 'reverse', 'shuffle' and 'clear' actions.
   */
  public static function submitAction(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $op = $trigger['#op'];

    // Check if we have a form element for the 'items' field.
    $path = array_merge($form['#parents'], ['items']);
    $key_exists = NULL;
    NestedArray::getValue($form_state->getValues(), $path, $key_exists);

    if ($key_exists) {
      // Remove any user input for the 'items' element in order to allow the
      // values set below to be applied.
      $user_input = $form_state->getUserInput();
      NestedArray::setValue($user_input, $path, NULL);
      $form_state->setUserInput($user_input);

      $entity = $form_state->getFormObject()->getEntity();
      $items_widget = $form_state->getFormObject()->getFormDisplay($form_state)->getRenderer('items');

      $subqueue_items = $entity->get('items');
      $items_widget->extractFormValues($subqueue_items, $form, $form_state);
      $items_values = $subqueue_items->getValue();

      switch ($op) {
        case 'reverse':
          $subqueue_items->setValue(array_reverse($items_values));
          break;

        case 'shuffle':
          shuffle($items_values);
          $subqueue_items->setValue($items_values);
          break;

        case 'clear':
          // Set the items count to zero.
          $parents = NestedArray::getValue($form, $path)['widget']['#field_parents'];
          $field_state = WidgetBase::getWidgetState($parents, 'items', $form_state);
          $field_state['items_count'] = 0;
          WidgetBase::setWidgetState($parents, 'items', $form_state, $field_state);
          $subqueue_items->setValue(NULL);
          break;
      }

      // Handle 'inline_entity_form' widgets separately because they have a
      // custom form state storage for the current state of the referenced
      // entities.
      if (\Drupal::moduleHandler()->moduleExists('inline_entity_form') && $items_widget instanceof InlineEntityFormBase) {
        $items_form_element = NestedArray::getValue($form, $path);
        $ief_id = $items_form_element['widget']['#ief_id'];

        $entities = $form_state->get(['inline_entity_form', $ief_id, 'entities']);

        if (isset($entities)) {
          $form_state->set(['inline_entity_form', $ief_id, 'entities'], []);

          switch ($op) {
            case 'reverse':
              $entities = array_reverse($entities);
              break;

            case 'shuffle':
              shuffle($entities);
              break;

            case 'clear':
              $entities = [];
              break;
          }

          foreach ($entities as $delta => $item) {
            $item['weight'] = $delta;
            $form_state->set(['inline_entity_form', $ief_id, 'entities', $delta], $item);
          }
        }
      }

      // Handle 'entity_browser' widgets separately because they have a custom
      // form state storage for the current state of the referenced entities.
      if (\Drupal::moduleHandler()->moduleExists('entity_browser') && $items_widget instanceof EntityReferenceBrowserWidget) {
        $ids = array_column($subqueue_items->getValue(), 'target_id');
        $widget_id = $subqueue_items->getEntity()->uuid() . ':' . $subqueue_items->getFieldDefinition()->getName();
        $form_state->set(['entity_browser_widget', $widget_id], $ids);
      }

      $form_state->getFormObject()->setEntity($entity);

      $form_state->setRebuild();
    }
  }

  /**
   * AJAX callback; Returns the entire form element.
   */
  public static function subqueueActionAjaxForm(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $subqueue = $this->entity;
    $status = $subqueue->save();

    $edit_link = $subqueue->toLink($this->t('Edit'), 'edit-form')->toString();
    if ($status == SAVED_UPDATED) {
      $this->messenger()->addMessage($this->t('The entity subqueue %label has been updated.', ['%label' => $subqueue->label()]));
      $this->logger('entityqueue')->notice('The entity subqueue %label has been updated.', ['%label' => $subqueue->label(), 'link' => $edit_link]);
    }
    else {
      $this->messenger()->addMessage($this->t('The entity subqueue %label has been added.', ['%label' => $subqueue->label()]));
      $this->logger('entityqueue')->notice('The entity subqueue %label has been added.', ['%label' => $subqueue->label(), 'link' => $edit_link]);
    }

    $queue = $subqueue->getQueue();
    if ($queue->getHandlerPlugin()->supportsMultipleSubqueues()) {
      $form_state->setRedirectUrl($queue->toUrl('subqueue-list'));
    }
    else {
      $form_state->setRedirectUrl($queue->toUrl('collection'));
    }
  }

}
