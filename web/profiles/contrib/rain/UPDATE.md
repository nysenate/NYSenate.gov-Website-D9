This file contains instructions for updating your Rain-based Drupal site.

Once an Rain is installed, all configuration is "owned" by your site and will be left alone by subsequent updates to this project.

In cases where any manual steps are required to upgrade your instance of Rain, we will provide detailed instructions below under the "Update Instructions" heading.

## Update Instructions

These instructions describe how to update your site to bring it in line with a newer version of Rain.

#### Deprecated dependency
* multiline_config

### Update to 3.20
Deprecated packages removed:
* dropzonejs
* embed
* entity_browser
* entity_embed
* media_entity_browser

### Update to 3.18
Small refactor moves all of Rain dependencies into a new submodule called "rain_core." This opens the door to eventually have Rain Core as it's own project. The Rain profile
does not do a lot outisde of Rain Core and could be eventually sunset or made to be a simple "wrapper" for Rain Core. This should not have any effect negative or otherwise
on existing projects that use Rain.

### Update to 3.15
Several modules are now deprecated for this release as Rain has transitioned to the media_library module in Drupal core. We encourage project maintainers to either add deprecated dependencies to their own project composer or make the transition to media_library.

#### Deprecated dependencies
* dropzonejs
* embed
* entity_browser
* entity_embed
* media_entity_browser

### Update to 3.9
We moved drupal/svg_image, drupal/viewsreference, and drupal/webform to the rain_features project. If you are not using rain_features you will need to re-add this dependency to your project composer.

### Update to 3.8

We moved drupal/field_group to the rain_features project. If you are not using rain_features you will need to re-add this dependency to your project composer.

### Update to 3.7

We moved drupal/scheduler to the rain_features project. If you are not using rain_features you will need to re-add this dependency to your project composer.

### Update from 2.x to 3.x

The update from Rain 2.x to 3.x introduced a signifcant change to the organization of the Rain project. In Rain 3.x content features have been moved to a separate project named "rain_features."

To upgrade to 3.x you would need to run `composer require mediacurrent/rain_features` in order to upgrade to 3.x. See "Removed dependencies" for packages that were removed. If you are using any of these packages you will need to add them manually to your project composer, otherwise Drupal will report an error.

__Important__: any dependencies not being added to project composer will need to be __uninstalled__ prior to upgrading to 3.x.

There have also been a few new modules added which can be enabled but are not required (see below).

#### Added dependencies
* drupal/multiline_config
* drupal/twig_tweak
* drupal/twig_field_value

#### Removed dependencies
* drupal/addtoany
* drupal/allowed_formats
* drupal/ckeditor_media_embed
* drupal/colorbox
* drupal/crop
* drupal/entity
* drupal/focal_point
* drupal/libraries
* drupal/media_entity_actions
* drupal/slick
* drupal/slick_media
* drupal/slick_paragraphs
* drupal/slick_views
