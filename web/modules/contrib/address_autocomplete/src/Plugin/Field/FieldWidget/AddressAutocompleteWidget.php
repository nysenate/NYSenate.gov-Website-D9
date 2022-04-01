<?php

namespace Drupal\address_autocomplete\Plugin\Field\FieldWidget;

use Drupal\address\Plugin\Field\FieldWidget\AddressDefaultWidget;
use Drupal\address_autocomplete\Form\SettingsForm;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'address_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "address_autocomplete",
 *   label = @Translation("Address autocomplete"),
 *   field_types = {
 *     "address"
 *   }
 * )
 */
class AddressAutocompleteWidget extends AddressDefaultWidget {

  /**
   * @inheritDoc
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['address']['#type'] = 'address_autocomplete';

    $config = $this->configFactory->get(SettingsForm::$configName);
    if (!$config->get('active_plugin')) {
      $element['message'] = [
        '#type' => 'item',
        '#markup' => t('Address autocomplete provider isn\'t selected. You can do it <a href="@url">here</a>.', [
          '@url' => Url::fromRoute('address_autocomplete.settings')
            ->toString(),
        ]),
        '#wrapper_attributes' => [
          'class' => ['messages', 'messages--warning'],
        ],
        '#weight' => -10,
      ];
    }

    return $element;
  }

}
