<?php

namespace Drupal\nys_dashboard\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Dashboard Actionbar Block.
 *
 * @Block(
 *   id = "dashboard_action_bar",
 *   admin_label = @Translation("Dashboard Actionbar"),
 * )
 */
class DashboardActionbarBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['heading'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Heading'),
      '#default_value' => $this->configuration['heading'] ?? NULL,
    ];
    $form['subheading'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Subheading'),
      '#default_value' => $this->configuration['subheading'] ?? NULL,
    ];
    $form['featured_link'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Featured link'),
      'text' => [
        '#type' => 'textfield',
        '#title' => $this->t('Link text'),
        '#default_value' => $this->configuration['featured_link']['text'] ?? NULL,
      ],
      'url' => [
        '#type' => 'textfield',
        '#title' => $this->t('URL'),
        '#default_value' => $this->configuration['featured_link']['url'] ?? NULL,
      ],
      'icon' => [
        '#type' => 'textfield',
        '#title' => $this->t('Icon'),
        '#default_value' => $this->configuration['featured_link']['icon'] ?? NULL,
        '#description' => <<<DESC
            Icons are from the Phosphor icon library. Use the machine name of
            an icon in this field, e.g. "newspaper-clipping". See
            <a href="https://phosphoricons.com" target="_blank">
            https://phosphoricons.com</a>.
            DESC,
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $this->configuration['heading'] = $values['heading'];
    $this->configuration['subheading'] = $values['subheading'];
    $this->configuration['featured_link'] = $values['featured_link'];
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $heading = $this->configuration['heading'];
    $subheading = $this->configuration['subheading'];

    $featured_link = '';
    $featured_link_config = $this->configuration['featured_link'];
    if (!empty($featured_link_config['text'])) {
      $featured_link = '<a class="dashboard-action-bar-link" href="'
        . $featured_link_config['url'] . '"><i class="ph ph-'
        . $featured_link_config['icon'] . '"></i>'
        . $featured_link_config['text'] . '</a>';
    }

    return [
      '#markup' => <<<END
        <h1 class="dashboard-action-bar-heading">$heading</h1>
        <p class="dashboard-action-bar-subheading">
          <em>$subheading</em>
        </p>
        $featured_link
        END,
    ];
  }

}
