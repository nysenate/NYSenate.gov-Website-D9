<?php

namespace Drupal\nys_dashboard\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Find and follow block class.
 *
 * @see: https://git.drupalcode.org/project/examples/-/blob/4.0.x/modules/form_api_example/src/Form/AjaxAddMore.php
 *
 * @Block(
 *    id = "find_and_follow",
 *    admin_label = @Translation("Find and follow block"),
 *  )
 */
class FindAndFollowBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $fandf_items_from_config = $this->configuration['fandf_items'];

    // Ensure $form_state 'fandf_items_count' value updated from config.
    if (
      $fandf_items_from_config
      && !$form_state->getCompleteFormState()->isRebuilding()
      && count($fandf_items_from_config) > $form_state->get('fandf_items_count')
    ) {
      $form_state->set('fandf_items_count', count($fandf_items_from_config));
    }

    // Ensure $form_state 'fandf_items_count' at least 1.
    $fandf_items_count = $form_state->get('fandf_items_count');
    if ($fandf_items_count === NULL) {
      $form_state->set('fandf_items_count', 1);
      $fandf_items_count = 1;
    }

    // Build form.
    $form['#tree'] = TRUE;
    $form['fandf_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Add or remove find and follow links from this block.'),
      '#prefix' => '<div id="fandf-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];
    for ($i = 0; $i < $fandf_items_count; $i++) {
      $form['fandf_fieldset']['items'][$i] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Find and follow link @num', ['@num' => $i + 1]),
        'text' => [
          '#type' => 'textfield',
          '#required' => TRUE,
          '#title' => $this->t('Link text'),
        ],
        'url' => [
          '#type' => 'textfield',
          '#required' => TRUE,
          '#title' => $this->t('URL'),
        ],
        'icon' => [
          '#type' => 'textfield',
          '#required' => TRUE,
          '#title' => $this->t('Icon'),
          '#description' => <<<DESC
            Icons are from the Phosphor icon library. Use the machine name of
            an icon in this field, e.g. "newspaper-clipping". See
            <a href="https://phosphoricons.com" target="_blank">
            https://phosphoricons.com</a>.
            DESC,
        ],
      ];
      if (!$form_state->getCompleteFormState()->isRebuilding()) {
        $form['fandf_fieldset']['items'][$i]['text']['#default_value'] =
          empty($fandf_items_from_config[$i]['text']) ? '' : $fandf_items_from_config[$i]['text'];
        $form['fandf_fieldset']['items'][$i]['url']['#default_value'] =
          empty($fandf_items_from_config[$i]['url']) ? '' : $fandf_items_from_config[$i]['url'];
        $form['fandf_fieldset']['items'][$i]['icon']['#default_value'] =
          empty($fandf_items_from_config[$i]['icon']) ? '' : $fandf_items_from_config[$i]['icon'];
      }
    }

    // Add form actions.
    $form['fandf_fieldset']['actions'] = [
      '#type' => 'actions',
    ];
    $form['fandf_fieldset']['actions']['add_item'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add item'),
      '#submit' => ['Drupal\nys_dashboard\Plugin\Block\FindAndFollowBlock::addItem'],
      '#ajax' => [
        'callback' => [$this, 'ajaxCallback'],
        'wrapper' => 'fandf-fieldset-wrapper',
      ],
    ];
    if ($fandf_items_count > 1) {
      $form['fandf_fieldset']['actions']['remove_item'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove item'),
        '#submit' => ['Drupal\nys_dashboard\Plugin\Block\FindAndFollowBlock::removeItem'],
        '#ajax' => [
          'callback' => [$this, 'ajaxCallback'],
          'wrapper' => 'fandf-fieldset-wrapper',
        ],
      ];
    }

    return $form;
  }

  /**
   * Submit handler to add a fieldset item.
   */
  public static function addItem(array &$form, FormStateInterface $form_state): void {
    $form_state->set('fandf_items_count', $form_state->get('fandf_items_count') + 1);
    $form_state->setRebuild();
  }

  /**
   * Submit handler to remove a fieldset item.
   */
  public static function removeItem(array &$form, FormStateInterface $form_state): void {
    $form_state->set('fandf_items_count', $form_state->get('fandf_items_count') - 1);
    $form_state->setRebuild();
  }

  /**
   * Ajax callback to inject re-rendered fieldset form element.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['settings']['fandf_fieldset'];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $fandf_fieldset = $form_state->getValue('fandf_fieldset');
    $this->configuration['fandf_items'] = !empty($fandf_fieldset['items']) ? $fandf_fieldset['items'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#theme' => 'nys_dashboard_find_and_follow',
      '#fandf_items' => $this->configuration['fandf_items'],
    ];
  }

}
