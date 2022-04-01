## CONTENTS OF THIS FILE ##

 * Introduction
 * Installation
 * Configuration
 * Usage
 * Debugging
 * Extending the module
 * How Can You Contribute?
 * Maintainers

## INTRODUCTION ##

Author and maintainer: Pawel Ginalski (gbyte)
 * Drupal: https://www.drupal.org/u/gbyte
 * Personal: https://gbyte.dev/

The module generates multilingual XML sitemaps which adhere to Google's new
hreflang standard. Out of the box the sitemaps index most of Drupal's
content entity types including:

 * nodes
 * taxonomy terms
 * menu links
 * users
 * ...

Contributed entity types like commerce products can be indexed
as well. On top of that custom links and view pages can be added to sitemaps.

To learn about XML sitemaps, see https://en.wikipedia.org/wiki/Sitemaps.

The module also provides an API allowing to create any type of sitemap (not
necessary an XML one) holding links to a local or remote source.

## INSTALLATION ##

See https://www.drupal.org/documentation/install/modules-themes/modules-8
for instructions on how to install or update Drupal modules.

## CONFIGURATION ##

### PERMISSIONS ###

The module permission 'administer sitemap settings' can be configured under
/admin/people/permissions.

### VARIANTS ###

It is possible to have several sitemap instances of different sitemap types with
specific links accessible under certain URLs. These sitemap variants can be
configured under admin/config/search/simplesitemap/variants.

### ENTITIES ###

Initially only the home page is indexed in the default sitemap variant. To
include content into a sitemap, visit
/admin/config/search/simplesitemap/entities to enable support for entity types
of your choosing. Bundleless entity types can be configured right on that page,
for bundles of entity types visit the bundle's configuration pages, e.g.

 * /admin/structure/types/manage/[content type] for nodes
 * /admin/structure/taxonomy/manage/[taxonomy vocabulary] for taxonomy terms
 * /admin/structure/menu/manage/[menu] for menu items
 * ...

When including an entity type or bundle into a sitemap, the priority setting
can be set which will set the 'priority' parameter for all entities of that
type. Same goes for the 'changefreq' setting. All Images referenced by the
entities can be indexed as well. See https://en.wikipedia.org/wiki/Sitemaps to
learn more about these parameters.

Inclusion settings of bundles can be overridden on a per-entity
basis. Just head over to a bundle instance edit form (e.g. node/1/edit) to
override its sitemap settings.

If you wish for the sitemap to reflect the new configuration instantly, check
'Regenerate sitemaps after clicking save'. This setting only appears if a change
in the settings has been detected.

Once variants are set up in admin/config/search/simplesitemap/variants, all the
above settings can be configured and overwritten on a per variant basis right
from the UI.

As the sitemaps are accessible to anonymous users, bear in mind that only links
will be included which are accessible to anonymous users. There are no access
checks for links added through the module's hooks (see below).

### VIEWS ###

To index views, enable the included, optional module Simple XML Sitemap (Views)
(simple_sitemap_views).

Simple views as well as views with arguments can be indexed on the view edit
page. For views with arguments, links to all view variants will be included in
the sitemap.

### CUSTOM LINKS ###

To include custom links into a sitemap, visit
/admin/config/search/simplesitemap/custom.

### AUTOMATIC SUBMISSION ###

It is possible to have the module automatically submit specific sitemap
variants to search engines. Google and Bing are preconfigured.

This functionality is available through the included simple_sitemap_engines
submodule. After enabling this module, go to
admin/config/search/simplesitemap/engines/settings to set it up.

### PERFORMANCE ###

The module can be tuned via UI for a vast improvement in generation speeds on
huge sites. To speed up generation, go to
admin/config/search/simplesitemap/engines/settings and increase
'Entities per queue item' and 'Sitemap generation max duration'.

Further things that can be tweaked are unchecking 'Exclude duplicate links' and
increasing 'Maximum links in a sitemap'.

These settings will increase the demand for PHP  execution time and memory, so
please make sure  to test the sitemap generation behaviour. See 'PERFORMANCE TEST'.

### OTHER SETTINGS ###

Other settings can be found under admin/config/search/simplesitemap/settings.

## USAGE ## 

The sitemaps are accessible to the whole world under [variant name]/sitemap.xml.
In addition to that, the default sitemap is accessible under /sitemap.xml. To
view the XML source, press ctrl+u.

If the cron generation is turned on, the sitemaps will be regenerated according
to the 'Sitemap generation interval' setting.

A manual generation is possible on admin/config/search/simplesitemap. This is
also the place that shows the overall and variant specific generation status.

The sitemap can be also generated via drush:
 * `simple-sitemap:generate` or `ssg`: Generates the sitemap (continues
   generating from queue, or rebuilds queue for all variants beforehand if
   nothing is queued).
   
 * `simple-sitemap:rebuild-queue` or `ssr`: Deletes queue and queues elements
   for all or specific sitemap variants. Add `--variants` flag and specify a
   comma separated list of variants if wanting to queue only specific variants
   for the upcoming generation.

Generation of hundreds of thousands of links can take time. Each variant gets
published as soon as all of its links have been generated. The previous version
of the sitemap variant is accessible during the generation process.

## Debugging ##

### PERFORMANCE TEST ###

The module includes a script that can be used to test the sitemap generation
performance.

Run `drush scr --uri http://example.com modules/simple_sitemap/tests/scripts/performance_test.php`
on your production environment to calculate generation speed and the amount of
queries performed.

If testing on a non-production environment, you can generate dummy content prior
to generation:
`drush scr --uri http://example.com modules/simple_sitemap/tests/scripts/performance_test.php -- generate 500`

## EXTENDING THE MODULE ##

### API ###

There are API methods for altering stored inclusion settings, status queries and
programmatic sitemap generation. These include:

 * getSetting
 * saveSetting
 * setVariants
 * getSitemap
 * removeSitemap
 * queue
 * rebuildQueue
 * generateSitemap
 * enableEntityType
 * disableEntityType
 * setBundleSettings
 * getBundleSettings
 * removeBundleSettings
 * setEntityInstanceSettings
 * getEntityInstanceSettings
 * removeEntityInstanceSettings
 * bundleIsIndexed
 * entityTypeIsEnabled
 * addCustomLink
 * getCustomLinks
 * removeCustomLinks
 * getSitemapManager
    * getSitemapVariants
    * addSitemapVariant
    * removeSitemapVariants
 * getQueueWorker
    * getInitialElementCount
    * getQueuedElementCount
    * getStashedResultCount
    * getProcessedElementCount
    * generationInProgress


These service methods can be chained like so:

```php
$generator = \Drupal::service('simple_sitemap.generator');

$generator
  ->getSitemapManager()
  ->addSitemapVariant('test');
  
$generator
  ->saveSetting('remove_duplicates', TRUE)
  ->enableEntityType('node')
  ->setVariants(['default', 'test'])
  ->setBundleSettings('node', 'page', ['index' => TRUE, 'priority' => 0.5])
  ->removeCustomLinks()
  ->addCustomLink('/some/view/page', ['priority' => 0.5])
  ->generateSitemap();
```

See https://gbyte.dev/projects/simple-xml-sitemap and code documentation in 
Drupal\simple_sitemap\Simplesitemap for further details.

### API HOOKS ###

It is possible to hook into link generation by implementing
`hook_simple_sitemap_links_alter(&$links, $sitemap_variant){}` in a custom module and altering the
link array shortly before it is transformed to XML.

Adding arbitrary links is possible through the use of
`hook_simple_sitemap_arbitrary_links_alter(&$arbitrary_links, $sitemap_variant){}`. There are no
checks performed on these links (i.e. if they are internal/valid/accessible)
and parameters like priority/lastmod/changefreq have to be added manually.

Altering sitemap attributes and sitemap index attributes is possible through the
use of `hook_simple_sitemap_attributes_alter(&$attributes, $sitemap_variant){}` and
`hook_simple_sitemap_index_attributes_alter(&$index_attributes, $sitemap_variant){}`.

Altering URL generators is possible through
the use of `hook_simple_sitemap_url_generators_alter(&$url_generators){}`.

Altering sitemap generators is possible through
the use of `hook_simple_sitemap_sitemap_generators_alter(&$sitemap_generators){}`.

Altering sitemap types is possible through
the use of `hook_simple_sitemap_sitemap_types_alter(&$sitemap_types){}`.

### WRITING PLUGINS ###

There are three types of plugins that allow to create any type of sitemap. See
the generator plugins included in this module and check the API docs
(https://www.drupal.org/docs/8/api/plugin-api/plugin-api-overview) to learn how
to implement plugins.

#### SITEMAP TYPE PLUGINS ####

This plugin defines a sitemap type. A sitemap type consists of a sitemap
generator and several URL generators. This plugin is very simple, as it
only requires some class annotation to define which sitemap/URL plugins to use.

#### SITEMAP GENERATOR PLUGINS ####

This plugin defines how a sitemap type is supposed to look. It handles all
aspects of the sitemap except its links/URLs.

#### URL GENERATOR PLUGINS ####

This plugin defines a way of generating URLs for one or more sitemap types.

Note:
Overwriting the default EntityUrlGenerator for a single entity type is possible
through the flag "overrides_entity_type" = "[entity_type_to_be_overwritten]" in
the settings array of the new generator plugin's annotation. See how the
EntityUrlGenerator is overwritten by the EntityMenuLinkContentUrlGenerator to
facilitate a different logic for menu links.

See https://gbyte.dev/projects/simple-xml-sitemap for further details.

## HOW CAN YOU CONTRIBUTE? ##

 * Report any bugs, feature or support requests in the issue tracker; if
   possible help out by submitting patches.
   http://drupal.org/project/issues/simple_sitemap

 * Do you know a non-English language? Help translating the module.
   https://localize.drupal.org/translate/projects/simple_sitemap

 * If you would like to say thanks and support the development of this module, a
   donation will be much appreciated.
   https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=5AFYRSBLGSC3W
   
 * Feel free to contact me for paid support: https://gbyte.dev/contact

## MAINTAINERS ##

Current maintainers:
 * Pawel Ginalski (gbyte) - https://www.drupal.org/u/gbyte
