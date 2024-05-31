<?php

declare(strict_types = 1);

namespace Drupal\fontawesome_media\Form;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\fontawesome\Plugin\Field\FieldWidget\FontAwesomeIconWidget;
use Drupal\media_library\Form\AddFormBase;

/**
 * Media library add form.
 */
class FontawesomeMediaAddForm extends AddFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->getBaseFormId() . '_fontawesome';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildInputElement(array $form, FormStateInterface $form_state): array {
    $form['container'] = [
      '#type' => 'container',
    ];

    $form['container']['icon_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon Name'),
      '#required' => TRUE,
      '#size' => 50,
      '#field_prefix' => 'fa-',
      '#description' => $this->t('Name of the Font Awesome Icon. See @iconsLink for valid icon names, or begin typing for an autocomplete list. Note that all four versions of the icon will be shown - Light, Regular, Solid, Duotone, and Thin respectively. If the icon shows a question mark, that icon version is not supported in your version of Fontawesome.', [
        '@iconsLink' => Link::fromTextAndUrl($this->t('the Font Awesome icon list'), Url::fromUri('https://fontawesome.com/icons', [
          'attributes' => [
            'target' => '_blank',
          ],
        ]))->toString(),
      ]),
      '#autocomplete_route_name' => 'fontawesome.autocomplete',
      '#element_validate' => [
        [FontAwesomeIconWidget::class, 'validateIconName'],
      ],
    ];

    $form['container']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#button_type' => 'primary',
      '#submit' => ['::addButtonSubmit'],
      '#ajax' => [
        'callback' => '::updateFormCallback',
        'wrapper' => 'media-library-wrapper',
        'url' => Url::fromRoute('media_library.ui'),
        'options' => [
          'query' => $this->getMediaLibraryState($form_state)->all() + [
              FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
            ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * Submit handler for the add button.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addButtonSubmit(array $form, FormStateInterface $form_state): void {
    $this->processInputValues([[
      'icon_name' =>  $form_state->getValue('icon_name'),
    ]], $form, $form_state);
  }

}
