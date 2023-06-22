<?php

namespace Drupal\nys_messaging\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Block to generate the menu for all of the Senator Microsite Pages.
 *
 * @Block(
 *   id = "nys_messaging_senator_message_form_block",
 *   admin_label = @Translation("Senator Message Form Block"),
 *   category = @Translation("NYS Messaging"),
 * )
 */
class SenatorMessageFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Default form_builder object.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
          $configuration,
          $plugin_id,
          $plugin_definition,
          $container->get('form_builder')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      'form' => $this->formBuilder->getForm('Drupal\nys_messaging\Form\SenatorMessageForm'),
    ];
  }

}
