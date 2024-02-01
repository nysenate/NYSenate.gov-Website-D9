<?php

/**
 * @file
 * Provides Twig extensions for the Basalt Twig renderer.
 */

use Twig\Environment;
use Twig\Extension\DebugExtension;

/**
 * Twig extensions.
 *
 * @param \Twig\Environment $env
 *   The Twig Environment.
 * @param mixed $config
 *   The environment config.
 */
function twig_extensions(Environment &$env, $config) {

  // Load the BasicTwigExtensions class so the extension can be added correctly.
  spl_autoload_register(function ($class_name) {
    include __DIR__ . '/' . $class_name . '.php';
  });

  $env->addExtension(new DebugExtension());
  $env->addExtension(new \BasicTwigExtensions());
}
