<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Drupal\facets\Result\Result;

/**
 * Provides a processor that only shows deepest level items.
 *
 * @FacetsProcessor(
 *   id = "show_siblings_processor",
 *   label = @Translation("Show siblings"),
 *   description = @Translation("Show all siblings of a hierarchical facet item. In 'Advanced settings' this processor should be executed early in the processor chain, for example before ids get converted into titles. It is recommended to enable 'Use hierarchy' and 'Ensure that only one result can be displayed', too."),
 *   stages = {
 *     "build" = 40
 *   }
 * )
 */
class ShowSiblingsProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    /** @var \Drupal\facets\Result\ResultInterface[] $results */
    if ($facet->getUseHierarchy()) {
      $rawValues = array_map(function ($result) {
        return $result->getRawValue();
      }, $results);
      foreach ($facet->getHierarchyInstance()->getSiblingIds($rawValues, $facet->getActiveItems()) as $siblingId) {
        $results[] = new Result($facet, $siblingId, $siblingId, 0);
      }
    }
    return $results;
  }

}
