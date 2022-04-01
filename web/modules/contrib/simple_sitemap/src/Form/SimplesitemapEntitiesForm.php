<?php

namespace Drupal\simple_sitemap\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\simple_sitemap\EntityHelper;

/**
 * Class SimplesitemapEntitiesForm
 * @package Drupal\simple_sitemap\Form
 */
class SimplesitemapEntitiesForm extends SimplesitemapFormBase {

  /**
   * @var \Drupal\simple_sitemap\EntityHelper
   */
  protected $entityHelper;

  /**
   * SimplesitemapEntitiesForm constructor.
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   * @param \Drupal\simple_sitemap\Form\FormHelper $form_helper
   * @param \Drupal\simple_sitemap\EntityHelper $entity_helper
   */
  public function __construct(
    Simplesitemap $generator,
    FormHelper $form_helper,
    EntityHelper $entity_helper
  ) {
    parent::__construct(
      $generator,
      $form_helper
    );
    $this->entityHelper = $entity_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.form_helper'),
      $container->get('simple_sitemap.entity_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_sitemap_entities_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['simple_sitemap_entities']['#prefix'] = FormHelper::getDonationText();

    $form['simple_sitemap_entities']['entities'] = [
      '#title' => $this->t('Sitemap entities'),
      '#type' => 'fieldset',
      '#markup' => '<div class="description">' . $this->t('Simple XML Sitemap settings will be added only to entity forms of entity types enabled here. For all entity types featuring bundles (e.g. <em>node</em>) sitemap settings have to be set on their bundle pages (e.g. <em>page</em>).') . '</div>',
    ];

    $form['#attached']['library'][] = 'simple_sitemap/sitemapEntities';
    $form['#attached']['drupalSettings']['simple_sitemap'] = ['all_entities' => [], 'atomic_entities' => []];

    $variants = $this->generator->getSitemapManager()->getSitemapVariants(NULL, FALSE);
    $all_bundle_settings = $this->generator->setVariants(TRUE)->getBundleSettings(NULL, NULL, TRUE, TRUE);
    $indexed_bundles = [];
    foreach ($all_bundle_settings as $variant => $entity_types) {
      foreach ($entity_types as $entity_type_name => $bundles) {
        foreach ($bundles as $bundle_name => $bundle_settings) {
          if (!empty($bundle_settings['index'])) {
            $indexed_bundles[$entity_type_name][$bundle_name]['variants'][] = $this->t($variants[$variant]['label']);
            $indexed_bundles[$entity_type_name][$bundle_name]['bundle_label'] = $this->entityHelper->getBundleLabel($entity_type_name, $bundle_name);
          }
        }
      }
    }

    $entity_type_labels = [];
    foreach ($this->entityHelper->getSupportedEntityTypes() as $entity_type_id => $entity_type) {
      $entity_type_labels[$entity_type_id] = $entity_type->getLabel() ? : $entity_type_id;
    }
    asort($entity_type_labels);

    foreach ($entity_type_labels as $entity_type_id => $entity_type_label) {
      $enabled_entity_type = $this->generator->entityTypeIsEnabled($entity_type_id);
      $atomic_entity_type = $this->entityHelper->entityTypeIsAtomic($entity_type_id);
      $css_entity_type_id = str_replace('_', '-', $entity_type_id);

      $form['simple_sitemap_entities']['entities'][$entity_type_id] = [
        '#type' => 'details',
        '#title' => $entity_type_label,
        '#open' => $enabled_entity_type,
      ];

      $form['simple_sitemap_entities']['entities'][$entity_type_id][$entity_type_id . '_enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable @entity_type_label <em>(@entity_type_id)</em> support', ['@entity_type_label' => $entity_type_label, '@entity_type_id' => $entity_type_id]),
        '#description' => $atomic_entity_type
          ? $this->t('Sitemap settings for the entity type <em>@entity_type_label</em> can be set below and overridden on its entity pages.', ['@entity_type_label' => $entity_type_label])
          : $this->t('Sitemap settings for the entity type <em>@entity_type_label</em> can be set on its bundle pages and overridden on its entity pages.', ['@entity_type_label' => $entity_type_label]),
        '#default_value' => $enabled_entity_type,
      ];

      if ($form['simple_sitemap_entities']['entities'][$entity_type_id][$entity_type_id . '_enabled']['#default_value']) {

        $indexed_bundles_string = '';
        if (isset($indexed_bundles[$entity_type_id])) {
          foreach ($indexed_bundles[$entity_type_id] as $bundle => $bundle_data) {
            $indexed_bundles_string .= '<br><em>' . $bundle_data['bundle_label'] . '</em> <span class="description">(' . $this->t('sitemap variants') . ': <em>' . implode(', ', $bundle_data['variants']) . '</em>)</span>';
          }
        }

        $bundle_info = '';
        if (!$atomic_entity_type) {
          $bundle_info .= '<div id="indexed-bundles-' . $css_entity_type_id . '">'
            . (!empty($indexed_bundles_string)
              ? $this->t("<em>@entity_type_label</em> bundles set to be indexed:", ['@entity_type_label' => $entity_type_label]) . ' ' . $indexed_bundles_string
              : $this->t('No <em>@entity_type_label</em> bundles are set to be indexed yet.', ['@entity_type_label' => $entity_type_label]))
            . '</div>';
        }

        if (!empty($indexed_bundles_string)) {
          $bundle_info .= '<div id="warning-' . $css_entity_type_id . '">'
            . ($atomic_entity_type
              ? $this->t("<strong>Warning:</strong> This entity type's sitemap settings including per-entity overrides will be deleted after hitting <em>Save</em>.")
              : $this->t("<strong>Warning:</strong> The sitemap settings and any per-entity overrides will be deleted for the following bundles:" . $indexed_bundles_string))
            . '</div>';
        }

        $form['simple_sitemap_entities']['entities'][$entity_type_id][$entity_type_id . '_enabled']['#suffix'] = $bundle_info;
      }

      $form['#attached']['drupalSettings']['simple_sitemap']['all_entities'][] = $css_entity_type_id;

      if ($atomic_entity_type) {
        $form['simple_sitemap_entities']['entities'][$entity_type_id][$entity_type_id . '_settings']['#prefix'] = '<div id="indexed-bundles-' . $css_entity_type_id . '">';
        $form['simple_sitemap_entities']['entities'][$entity_type_id][$entity_type_id . '_settings']['#suffix'] = '</div>';

        $this->formHelper
          ->cleanUpFormInfo()
          ->setEntityCategory('bundle')
          ->setEntityTypeId($entity_type_id)
          ->setBundleName($entity_type_id)
          ->negotiateSettings()
          ->displayEntitySettings(
            $form['simple_sitemap_entities']['entities'][$entity_type_id][$entity_type_id . '_settings']
          );
      }
    }

    $this->formHelper->displayRegenerateNow($form['simple_sitemap_entities']['entities']);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach ($values as $field_name => $value) {
      if (substr($field_name, -strlen('_enabled')) === '_enabled') {
        $entity_type_id = substr($field_name, 0, -8);
        if ($value) {
          $this->generator->enableEntityType($entity_type_id);
          if ($this->entityHelper->entityTypeIsAtomic($entity_type_id)) {
            foreach ($this->generator->getSitemapManager()->getSitemapVariants(NULL, FALSE) as $variant => $definition) {
              if (isset($values['index_' . $variant . '_' . $entity_type_id . '_settings'])) {
                $this->generator
                  ->setVariants($variant)
                  ->setBundleSettings($entity_type_id, $entity_type_id, [
                    'index' => (bool) $values['index_' . $variant . '_' . $entity_type_id . '_settings'],
                    'priority' => $values['priority_' . $variant . '_' . $entity_type_id . '_settings'],
                    'changefreq' => $values['changefreq_' . $variant . '_' . $entity_type_id . '_settings'],
                    'include_images' => (bool) $values['include_images_' . $variant . '_' . $entity_type_id . '_settings'],
                    ]);
              }
            }
          }
        }
        else {
          $this->generator->disableEntityType($entity_type_id);
        }
      }
    }
    parent::submitForm($form, $form_state);

    // Regenerate sitemaps according to user setting.
    if ($form_state->getValue('simple_sitemap_regenerate_now')) {
      $this->generator->setVariants(TRUE)
        ->rebuildQueue()
        ->generateSitemap();
    }
  }

}
