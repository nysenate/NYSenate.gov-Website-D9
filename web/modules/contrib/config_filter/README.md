# Config Filter

[![Build Status](https://travis-ci.org/nuvoleweb/config_filter.svg?branch=8.x-1.x)](https://travis-ci.org/nuvoleweb/config_filter)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/nuvoleweb/config_filter/badges/quality-score.png?b=8.x-1.x)](https://scrutinizer-ci.com/g/nuvoleweb/config_filter/?branch=8.x-1.x)


## Introduction

Modules such as Config Split want to modify the configuration when it is
synchronized between the database and the exported yaml files.
This module provides the API to do so but does not influence a sites operation.

## How it works

Configuration Filter swaps the config.storage.sync service from Drupal 8 core.
The new service wraps the file storage and applies filters to it.
This allows other modules to change the configuration as it gets imported or
exported both in the Drupal UI and with drush.

## What is a ConfigFilter

A ConfigFilter is a plugin. This module provides the plugin definition, the
plugin manager and the storage factory.
A ConfigFilter can have the following annotation:

```php
/**
 * @ConfigFilter(
 *   id = "may_plugin_id",
 *   label = @Translation("An example configuration filter"),
 *   weight = 0,
 *   status = TRUE,
 *   storages = {"config.storage.sync"},
 * )
 */
```
See `\Drupal\config_filter\Annotation\ConfigFilter`.

The weight allows the filters to be sorted. The status allows the filter to be
active or inactive, the `ConfigFilterManagerInterface::getFiltersForStorages`
will only take active filters into consideration. The weight, status and
storages are optional and the above values are the default.

## Alternative Config Filter Managers

Plugins are only available from enabled modules. If you want to provide a
config filter from a php library, all you have to do is implement the
`\Drupal\config_filter\ConfigFilterManagerInterface` and add it to the
service container with a `config.filter` tag.
Services with higher priority will have their filters added first.
