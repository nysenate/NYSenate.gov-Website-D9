<?php

namespace Drupal\simple_sitemap\Controller;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\simple_sitemap\Manager\Generator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routines for sitemap routes.
 */
class SimpleSitemapController extends ControllerBase {

  /**
   * The simple_sitemap.generator service.
   *
   * @var \Drupal\simple_sitemap\Manager\Generator
   */
  protected $generator;

  /**
   * SimpleSitemapController constructor.
   *
   * @param \Drupal\simple_sitemap\Manager\Generator $generator
   *   The simple_sitemap.generator service.
   */
  public function __construct(Generator $generator) {
    $this->generator = $generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): SimpleSitemapController {
    return new static(
      $container->get('simple_sitemap.generator')
    );
  }

  /**
   * Returns a specific sitemap, its chunk, or its index.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string|null $variant
   *   Optional name of sitemap variant.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Returns an XML response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function getSitemap(Request $request, ?string $variant = NULL): Response {
    $output = $this->generator->setVariants($variant)->getContent($request->query->get('page'));
    if ($output === NULL) {
      throw new NotFoundHttpException();
    }

    return new Response($output, Response::HTTP_OK, [
      'Content-type' => 'application/xml; charset=utf-8',
      'X-Robots-Tag' => 'noindex, follow',
    ]);
  }

  /**
   * Returns the XML stylesheet for a sitemap.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Returns an XSL response.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getSitemapXsl(string $sitemap_generator): Response {
    /** @var \Drupal\Component\Plugin\PluginManagerInterface $manager */
    $manager = \Drupal::service('plugin.manager.simple_sitemap.sitemap_generator');
    try {
      /** @var \Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\SitemapGeneratorInterface $sitemap_generator */
      $sitemap_generator = $manager->createInstance($sitemap_generator);
    }
    catch (PluginNotFoundException $ex) {
      throw new NotFoundHttpException();
    }

    if (NULL === ($xsl = $sitemap_generator->getXslContent())) {
      throw new NotFoundHttpException();
    }

    return new Response($xsl, Response::HTTP_OK, [
      'Content-type' => 'application/xml; charset=utf-8',
      'X-Robots-Tag' => 'noindex, nofollow',
    ]);
  }

}
