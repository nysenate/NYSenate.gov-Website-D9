# CONTENTS OF THIS FILE

- Introduction
- Requirements
- Installation
- Permissions
- Global Configuration
- Provider Buckets
- Custom Providers
- Maintainers

# INTRODUCTION

The *oEmbed Providers* module extends core's oEmbed functionality:

- Add custom oEmbed providers via an admin user interface (providers are stored
  in configuration)
- Global enable/disable of providers
- Modify the provider list URL (which is defaulted
  to https://oembed.com/providers.json)
- Disable the fetching of the provider list (useful in an instance where only
  custom providers are used

For a full description of the module, visit the project page:
   https://www.drupal.org/project/oembed_providers

To submit bug reports and feature suggestions, or track changes:
   https://www.drupal.org/project/issues/oembed_providers

# REQUIREMENTS

This module has the following requirements:

- Drupal Drupal 9.0.0+
- Media (included in core)

# INSTALLATION

- Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.

# PERMISSIONS

Access to all functionality provided by this module is controlled with the
*Administer oEmbed providers* permission.

# GLOBAL CONFIGURATION

On the *oEmbed Providers* configuration page
(/admin/config/media/oembed-providers), global configuration is managed.

## External Fetch

By default, *Media* retrieves a JSON file with a listing of oEmbed providers
from an external URL. The *Enable external fetch of providers* setting allows
this fetch to be enabled or disabled. If disabled, then any providers will need
to be defined locally.

## oEmbed Providers URL

When *Media* retrieves the oEmbed providers JSON file, it uses
'https://oembed.com/providers.json' as its default URL. This URL can be
customized with the *oEmbed Providers URL* setting.

# PROVIDER BUCKETS

Provider buckets allow for oEmbed providers to be grouped and then exposed as
media sources.

Provider buckets can be added, edited, and deleted on the *Provider Buckets*
page (/admin/config/media/oembed-providers/buckets).

When adding a provider bucket, name the bucket and select providers that should
be allowed. The machine name is used to generate the media source machine name
using the following syntax: `oembed:[provider bucket machine name]`.
For example, a provider bucket with the machine name 'remote_image' would
result in a media source with the machine name 'oembed:remote_image'.

By default, core Media provides a 'Remote Video' media source with YouTube and
Vimeo as allowed providers. In order to override the default
'oembed:video' media source, create a provider bucket with a machine
name of 'video'.

A media source can be used for a given Media Type.

Provider buckets are stored in configuration as config entities.

## PROVIDER BUCKET MACHINE NAME CHARACTER LIMIT

The machine name of provider buckets should be no longer than 14 characters.

See https://www.drupal.org/project/oembed_providers/issues/3267697, which
references the following core bug:
https://www.drupal.org/project/drupal/issues/3276845.

# CUSTOM PROVIDERS

Custom oEmbed providers can be added, edited, and deleted on the *Custom oEmbed
Providers* page (/admin/config/media/oembed-providers/custom-providers).

When adding a custom provider, the provider must either 1) support discovery
or 2) explicitly define one or more formats.

An endpoint's 'Endpoint URL' may contain `{format}`, which will be replaced
with `json` when core Media processes the endpoint.

Custom providers are stored in configuration as config entities.

# MAINTAINERS

Current maintainers:
 * Chris Burge - https://www.drupal.org/u/chris-burge

This project has been sponsored by:
 * [University of Nebraska-Lincoln, Digital Experience Group](https://dxg.unl.edu)
