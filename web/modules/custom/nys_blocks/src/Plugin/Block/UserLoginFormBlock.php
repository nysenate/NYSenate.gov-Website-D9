<?php

namespace Drupal\nys_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Block to load the User Login Form.
 *
 * @Block(
 *   id = "nys_block_user_login_form_block",
 *   admin_label = @Translation("User Login Form Block"),
 *   category = @Translation("NYS Blocks"),
 * )
 */
class UserLoginFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
      'form' => $this->formBuilder->getForm('Drupal\user\Form\UserLoginForm'),
    ];
  }

}
