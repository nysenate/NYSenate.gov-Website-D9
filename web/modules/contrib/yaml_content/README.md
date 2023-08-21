# YAML Content

The YAML Content module provides a framework for defining content in a
human-readable and writable YAML structure allowing for the flexible
and straight-forward creation of demo content within a site.


## Requirements

No additional modules are required for YAML Content to function.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

No configuration is provided in the UI.

Recommended setup for the module is to determine a target location for import
content to be found. Most accessibly, this could be within either an enabled
custom profile or module.

Within the target directory, content files may be created within a `content/`
subdirectory and follow the naming convention `*.content.yml`. Referenced images
or data files may also be added in parallel directories named `images/` and
`data_files` respectively.


## Usage

To assist determining YAML structure for entities and fields, looking at the
structure within the corresponding entity_view_display configuration file is a
good place to start.

Once content is created for import, it may be imported through the one of the
custom Drush commands:

    drush yaml-content-import <directory>
    drush yaml-content-import-module <module_name>
    drush yaml-content-import-profile <profile_name>


## Examples

For some brief content examples, have a look in the `content` folder of this
module. In that folder there are example import files with inline commentary
describing how values are set and the data is structured. These content files
must be imported into a site with the matching architecture for a demonstration.
Any site installed using the Standard install profile should have the required
content types and field structure to support the demo content.

To run the import, ensure the yaml_content module is enabled and run the
following command through Drush:

    drush yaml-content-import-module yaml_content


## Installation profile usage

To trigger loading content during an installation profile just add an install
task.

    /**
     * Implements hook_install_tasks().
     */
    function MYPROFILE_install_tasks(&$install_state) {
      $tasks = [
        // Install the demo content using YAML Content.
        'MYPROFILE_install_content' => [
          'display_name' => t('Install demo content'),
          'type' => 'normal',
        ],
      ];
    
      return $tasks;
    }
    
    /**
     * Callback function to install demo content.
     *
     * @see MYPROFILE_install_tasks()
     */
    function MYPROFILE_install_content() {
      // Create default content.
      $loader = \Drupal::service('yaml_content.load_helper');
      $loader->importProfile('MYPROFILE');
    
      // Set front page to the page loaded above.
      \Drupal::configFactory()
        ->getEditable('system.site')
        ->set('page.front', '/home')
        ->save(TRUE);
    }


## Maintainers

- [Damien McKenna](https://www.drupal.org/u/damienmckenna)
- Mark Shropshire - [shrop](https://www.drupal.org/u/shrop)
- Stephen Lucero - [slucero](https://www.drupal.org/u/slucero)
