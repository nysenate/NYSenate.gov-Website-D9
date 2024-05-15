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
    $form['featured_link_1'] = $this->buildFeaturedLinkField(1);
    $form['featured_link_2'] = $this->buildFeaturedLinkField(2);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $this->configuration['heading'] = $values['heading'];
    $this->configuration['subheading'] = $values['subheading'];
    $this->configuration['featured_link_1'] = $values['featured_link_1'];
    $this->configuration['featured_link_2'] = $values['featured_link_2'];
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $featured_links = [];
    if (!empty($this->configuration['featured_link_1']['text'])) {
      $featured_links[] = $this->configuration['featured_link_1'];
    }
    if (!empty($this->configuration['featured_link_2']['text'])) {
      $featured_links[] = $this->configuration['featured_link_2'];
    }

    return [
      'heading' => $this->configuration['heading'],
      'subheading' => $this->configuration['subheading'],
      'featured_links' => $featured_links,
    ];
  }

  /**
   * Builds the featured link field.
   *
   * @param int $link_num
   *   The link number.
   *
   * @return array
   *   The featured link field.
   */
  public function buildFeaturedLinkField(int $link_num): array {
    return [
      '#type' => 'fieldset',
      '#title' => $this->t('Featured link @link_num', ['@link_num' => $link_num]),
      'text' => [
        '#type' => 'textfield',
        '#title' => $this->t('Link text'),
        '#default_value' => $this->configuration['featured_link_' . $link_num]['text'] ?? NULL,
      ],
      'url' => [
        '#type' => 'textfield',
        '#title' => $this->t('URL'),
        '#default_value' => $this->configuration['featured_link_' . $link_num]['url'] ?? NULL,
      ],
      'icon' => [
        '#type' => 'textfield',
        '#title' => $this->t('Icon'),
        '#default_value' => $this->configuration['featured_link_' . $link_num]['icon'] ?? NULL,
        '#description' => <<<DESC
            Icons are from the Phosphor icon library. Use the machine name of
            an icon in this field, e.g. "newspaper-clipping". See
            <a href="https://phosphoricons.com" target="_blank">
            https://phosphoricons.com</a>.
            DESC,
      ],
    ];
  }

}
