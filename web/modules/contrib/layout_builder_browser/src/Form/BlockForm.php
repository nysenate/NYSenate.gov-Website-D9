<?php

namespace Drupal\layout_builder_browser\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the block add and edit forms.
 */
class BlockForm extends EntityForm {

  /**
   * Constructs an layout_builder_browserForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);


    $definitions = \Drupal::service('plugin.manager.block')
      ->getFilteredDefinitions('layout_builder', NULL, ['list' => 'inline_blocks']);

    $blocks = [];
    $provider_options = [];
    $block_provider_map = [];


    foreach ($definitions as $id => $definition) {
      if ($definition["category"] instanceof TranslatableMarkup) {
        $catid = $definition["category"]->getUntranslatedString();
        $catlabel = $definition["category"]->render();
      }
      else {
        $catid = $catlabel = $definition["category"];
      }
      $blocks[$catid][$id] = $definition['admin_label'];
      $provider_options[$catid] = $catlabel;
      $block_provider_map[$id] = $catid;
    }

    $browser_block = $this->entity;

    $block_id = $browser_block->block_id;
    $provider = isset($provider_options['Inline blocks']) ? 'Inline blocks' : '';
    if ($block_id) {
      $provider = $block_provider_map[$block_id];
    }
    if ($form_state->getValue('provider')) {
      $provider = $form_state->getValue('provider');
    }

    // When no custom block types are defined the provider is empty, pick the first option as fallback.
    if (empty($block_provider_map)) {
      reset($block_provider_map);
      $provider = key($block_provider_map);
    }

    $form['provider'] = [
      '#title' => $this->t('Provider'),
      '#type' => 'select',
      '#options' => $provider_options,
      '#default_value' => $provider,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::providerChanged',
        'event' => 'change',
        'wrapper' => 'block-id-wrapper'
      ],
    ];

    $form['block_id'] = [
      '#suffix' => '</div>',
      '#prefix' => '<div id="block-id-wrapper">',
      '#title' => $this->t('Block'),
      '#type' => 'select',
      '#options' => $blocks[$provider],
      '#default_value' => $browser_block->block_id,
      '#required' => TRUE,

    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $browser_block->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $browser_block->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$browser_block->isNew(),
    ];


    $form['image_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image path'),
      '#maxlength' => 255,
      '#default_value' => $browser_block->image_path,
      '#description' => $this->t("Preview image path. E.g. /themes/mycustomtheme/images/lbb/text.jpg"),
    ];
    $form['image_alt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image alt'),
      '#maxlength' => 255,
      '#default_value' => $browser_block->image_alt,
    ];


    $blockcat_prefill = \Drupal::request()->query->get('blockcat');
    $block_categories = $this->entityTypeManager
      ->getStorage('layout_builder_browser_blockcat')
      ->loadMultiple();
    uasort($block_categories, [
      'Drupal\Core\Config\Entity\ConfigEntityBase',
      'sort',
    ]);
    $blockcatoptions = [];
    foreach ($block_categories as $block_category) {
      $blockcatoptions[$block_category->id] = $block_category->label;
    }
    $form['category'] = [
      '#title' => $this->t('Block category'),
      '#type' => 'select',
      '#options' => $blockcatoptions,
      '#default_value' => $blockcat_prefill ?: $browser_block->category,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $layout_builder_browser = $this->entity;
    $status = $layout_builder_browser->save();

    if ($status) {
      $this->messenger()
        ->addMessage($this->t('Saved the %label layout_builder_browser.', [
          '%label' => $layout_builder_browser->label(),
        ]));
    }
    else {
      $this->messenger()
        ->addMessage($this->t('The %label layout_builder_browser was not saved.', [
          '%label' => $layout_builder_browser->label(),
        ]), MessengerInterface::TYPE_ERROR);
    }

    $form_state->setRedirect('layout_builder_browser.admin_blocklisting');
  }

  /**
   * Check whether an layout_builder_browser configuration entity exists.
   *
   * @var int $id
   *   The id of the block to check.
   *
   * @return bool
   *   True if block exists.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('layout_builder_browser_block')
      ->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

  public function providerChanged(array &$form, FormStateInterface $form_state) {
    return $form['block_id'];
  }

}
