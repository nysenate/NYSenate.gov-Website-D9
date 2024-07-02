<?php

namespace Drupal\devel\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns response for Layout Info route.
 */
class LayoutInfoController extends ControllerBase {

  /**
   * The Layout Plugin Manager.
   */
  protected LayoutPluginManagerInterface $layoutPluginManager;

  /**
   * LayoutInfoController constructor.
   *
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $pluginManagerLayout
   *   The layout manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(
    LayoutPluginManagerInterface $pluginManagerLayout,
    TranslationInterface $string_translation
  ) {
    $this->layoutPluginManager = $pluginManagerLayout;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('plugin.manager.core.layout'),
      $container->get('string_translation'),
    );
  }

  /**
   * Builds the Layout Info page.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function layoutInfoPage(): array {
    $headers = [
      $this->t('Icon'),
      $this->t('Label'),
      $this->t('Description'),
      $this->t('Category'),
      $this->t('Regions'),
      $this->t('Provider'),
    ];

    $rows = [];

    foreach ($this->layoutPluginManager->getDefinitions() as $layout) {
      $rows[] = [
        'icon' => ['data' => $layout->getIcon()],
        'label' => $layout->getLabel(),
        'description' => $layout->getDescription(),
        'category' => $layout->getCategory(),
        'regions' => implode(', ', $layout->getRegionLabels()),
        'provider' => $layout->getProvider(),
      ];
    }

    $output['layouts'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No layouts available.'),
      '#attributes' => [
        'class' => ['devel-layout-list'],
      ],
    ];

    return $output;
  }

}
