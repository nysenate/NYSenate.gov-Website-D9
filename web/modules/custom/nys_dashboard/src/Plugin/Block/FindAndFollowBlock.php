<?php

namespace Drupal\nys_dashboard\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Find and follow block class.
 *
 * Adopts pattern from: https://git.drupalcode.org/project/examples/-/blob/4.0.x/modules/form_api_example/src/Form/AjaxAddMore.php.
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
    $fieldset_from_config = $this->configuration['fandf_fieldset'];

    // Ensure $form_state 'fieldset_count' value updated from config.
    if (
      !empty($fieldset_from_config['items'])
      && count($fieldset_from_config['items']) > $form_state->get('fieldset_count')
    ) {
      $form_state->set('fieldset_count', count($fieldset_from_config['items']));
    }

    // Ensure $form_state 'fieldset_count' at least 1.
    $fieldset_count = $form_state->get('fieldset_count');
    if ($fieldset_count === NULL) {
      $form_state->set('fieldset_count', 1);
      $fieldset_count = 1;
    }

    // Build form.
    $form['#tree'] = TRUE;
    $form['fandf_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Add or remove find and follow links from this block.'),
      '#prefix' => '<div id="fandf-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];
    for ($i = 0; $i < $fieldset_count; $i++) {
      $form['fandf_fieldset']['items'][$i] = [
        '#type' => 'fieldset',
        'text' => [
          '#type' => 'textfield',
          '#title' => $this->t('Link text'),
          '#default_value' => empty($fieldset_from_config['items'][$i]['text']) ? '' : $fieldset_from_config['items'][$i]['text'],
        ],
        'url' => [
          '#type' => 'textfield',
          '#title' => $this->t('URL'),
          '#default_value' => empty($fieldset_from_config['items'][$i]['url']) ? '' : $fieldset_from_config['items'][$i]['url'],
        ],
        'icon' => [
          '#type' => 'textfield',
          '#title' => $this->t('Icon'),
          '#default_value' => empty($fieldset_from_config['items'][$i]['icon']) ? '' : $fieldset_from_config['items'][$i]['icon'],
        ],
      ];
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
    if ($fieldset_count > 1) {
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
    $form_state->set('fieldset_count', $form_state->get('fieldset_count') + 1);
    $form_state->setRebuild();
  }

  /**
   * Submit handler to remove a fieldset item.
   */
  public static function removeItem(array &$form, FormStateInterface $form_state): void {
    $form_state->set('fieldset_count', $form_state->get('fieldset_count') - 1);
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
    $this->configuration['fandf_fieldset'] = $form_state->getValue('fandf_fieldset');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $output = '';
    $items = $this->configuration['fandf_fieldset']['items'];
    foreach ($items as $item) {
      $output .= '<p>' . $item['text'] . ' ' . $item['url'] . ' ' . $item['icon'] . '</p>';
    }
    return [
      '#markup' => $output,
    ];
  }

}
