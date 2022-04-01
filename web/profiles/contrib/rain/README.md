# Mediacurrent Rain Install Profile

__IMPORTANT__: If you are updating from Rain 2.x please see [UPDATE.md](UPDATE.md) for upgrade instructions.

The Mediacurrent Rain install profile adds out-of-the-box editorial, administrative and media enhancements to the typical Drupal 8 installation.
Rain can be installed as a stand-alone profile or can be used as a base profile.

### Base features

* Content moderation (core)
* Revisions (core)
* Media library (core)
* Wysiwyg Linkit (linkit)
* Content scheduling (scheduler)
* Admin menu (admin_toolbar)
* Path aliases (pathauto)
* XML Sitemap config (simple_sitemap)
* Metatag configuration (metatag)
* Analytics (google_analytics)
* Taxonomy access fix for editors (taxonomy_access_fix)

### Installation

* The intended usage of this project is to be pulled into a full [Drupal composer-based project](https://bitbucket.org/mediacurrent/drupal-project/).
* See [MIS Rain Vagrant](https://bitbucket.org/mediacurrent/mis_rain_vagrant/src) project for example usage. Note that this project uses Rain as a base profile.
* To install as a stand-alone install, use the command: ```drush si -y rain```
* See [Drupal Sub-profiles thread](https://www.drupal.org/node/1356276) for details around using an install profile as a "subprofile."
* This profile can also be forked easily by renaming files and replacing project name with custom project name.

### Usage

The Rain install profile is primarily used to help with Drupal core/contrib dependency management and provide configuration that can be easily modified.
It is not necessarily recommended for applications using the profile to import downstream configuration updates. Configuration provided is intended as a
"starter" when the install profile is run for the very first time. Once configuration has been committed to git, downstream updates will not effect
the Drupal 8 application (by design). Downstream Composer updates however can be extremely beneficial as they reduce the maintenance burden for application owners to manage
dozens of dependencies provided by this install profile. Pulling down these updates is recommended.

### Making configuration updates to this project

* One aspect of making updates to this project is that its important to remove uuids/hashes from configuration. The base script below will do this for you.
```
#!/bin/bash
FILES=config/install/*
for f in $FILES
do
  echo "Processing $f file..."
  sed -i '' '/^uuid:/d' $f
  sed -i '' '/^\_core:/d' $f
  sed -i '' '/^  default_config_hash:/d' $f
done
```
### Contribution

* [Rain drupal.org project page](https://www.drupal.org/project/rain)
* [Rain issue queue](https://www.drupal.org/project/issues/rain)