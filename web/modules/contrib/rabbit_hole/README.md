# Rabbit Hole

## CONTENTS OF THIS FILE

 * Introduction
 * Requirements
 * Installation
 * Configuration


## INTRODUCTION

Rabbit Hole adds the ability to control what should happen when an entity is
being viewed at its own page.

 * For a full description of the module, visit the project page:
   <http://drupal.org/project/rabbit_hole>

 * To submit bug reports and feature suggestions, or to track changes:
      <https://www.drupal.org/project/issues/rabbit_hole>


## REQUIREMENTS

This module requires no modules outside of Drupal core.


## INSTALLATION

 * Install as you would normally install a contributed Drupal module.
   See: <https://www.drupal.org/docs/extending-drupal/installing-modules> for further information.
 * Enable the specified submodule for Rabbit Hole functionality on specific
   entity types (Files, Group, Media entity, Nodes, Taxonomy, Users).


## CONFIGURATION

 * Configure the user permissions in Administration » People » Permissions:

    * Administer Rabbit Hole settings.
    * Bypass Rabbit Hole action.

 * Navigate to the entity configuration for the Rabbit Hole submodule(s)
   installed.
 * Define the Behavior for the 'Rabbit Hole Settings' vertical tab on the entity
   type 'Edit' page.

    * Access Denied: shows the Drupal default 'access denied' page.
    * Display the page: show the page as expected.
    * Page not found: present the Drupal 'page not found' page.
    * Page redirect: redirect the user to a specific path, and provide a
      Response code redirect type. Supports tokens.

* Enable or disable the ability to 'Allow these settings to be overridden for
  individual entities'.
* If overridden for individual entities, edit the entity and configure the
  'Rabbit Hole Settings'.
