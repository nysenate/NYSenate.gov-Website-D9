# Twig VarDumper for Drupal 9

Provides a way to display Twig PHP variables in a pretty way.

Twig VarDumper provides a better `{{ dump() }}` and `{{ vardumper() }}`
function that can help you debug Twig variables.

By default, the module display the var_dump output, just like the
other common debugging mode.

Make sure to have the required Symfony libraries to get this module working.

See the examples below on how to use it, it's very easy to use.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/twig_vardumper).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/twig_vardumper).


## Requirements

This module requires no modules outside of Drupal core.


## Installation

The module is relying on the VarDumper and http-foundation components of
the Symfony project.
There easiest way to install this module is with composer. Here are the
commands to run:

* `composer config repositories.drupal composer https://packages.drupal.org/9`
* `composer require drupal/twig_vardumper`
* `drush en twig_vardumper -y`
* Once the module and/or the submodules are enabled, don't forget to check
  for the new user permissions.


## Configuration

The module has no menu or modifiable settings. There is no configuration. When
enabled, the module will prevent the links from appearing. To get the links
back, disable the module and clear caches.


## How to use

Enable the module twig_vardumper then (e.g., page.html.twig)...

    <header class="header-mediador">
      {{ page.header }}
    </header>

    {{ dump(page.content) }}
    {{ vardumper(page.content) }}

    {{ page.content }}

    <footer class="seccion-footer">
      {{ page.footer }}
    </footer>


## Related modules

* Twig Tweak: with drupal_dump() etc...


## Related documentation

* [Debugging Twig templates](https://www.drupal.org/docs/8/theming/twig/debugging-twig-templates)
* [Drupal Template Helper](https://front.id/en/articles/drupal-template-helper)
* [Drupal Template Helper para Drupal 8](https://www.keopx.net/blog/drupal-template-helper-para-drupal-8) (Spanish)
