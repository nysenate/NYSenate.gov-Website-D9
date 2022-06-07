<?php

namespace Drupal\nys_senators\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Block to generate the menu for all of the Senator Microsite Pages.
 *
 * @Block(
 *   id = "nys_senators_microsite_menu",
 *   admin_label = @Translation("Senator Microsite Menu"),
 *   category = @Translation("NYS Senators")
 * )
 */
class SenatorMicrositeMenu extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var Node $node */
    $node = $this->getContextValue('node');
    if (!empty($node) && $node->getType() == 'microsite_page') {
      $senator_terms = $node->get('field_senator_multiref')->getValue();
      $nodes = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
        ->latestRevision()
        ->condition('field_senator_multiref', $senator_terms)
        ->execute();
      // Query to get all the "Microsite Pages" with the same Senator reference
      // Get the Senator taxonomy reference from there populate links.
      foreach ($nodes as $node) {
        // Get the url alias for each and populate links for menu block.
        $links[] = $node->toUrl()->toString();
      }
      // Extract the Senator term from the node or taxonomy page to set the
      // district populate the district link from the taxonomy term's url alias.
      // Set up links so they can render the menu block template.
      return [
        '#markup' => $this->t('Welcome page!'),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['nys_senators_microsite_menu'] = $form_state->getValue('nys_senators_microsite_menu');
  }

}
