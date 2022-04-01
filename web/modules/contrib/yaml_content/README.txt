-- Summary --

The YAML Content module provides a framework for defining content in a
human-readable and writable YAML structure allowing for the flexible
and straight-forward creation of demo content within a site.

-- REQUIREMENTS --

No additional modules are explicitly required for yaml_content to function.

-- INSTALLATION --

* Install as usual, see https://www.drupal.org/documentation/install/modules-themes/modules-8
  for further information.

-- SETUP --

Recommended setup for the module is to determine a target location for import
content to be found. Most accessibly, this could be within either an enabled
custom profile or module.

Within the target directory, content files may be created within a `content/`
subdirectory and follow the naming convention `*.content.yml`. Referenced images
or data files may also be added in parallel directories named `images/` and
`data_files` respectively.

-- USAGE --

Once content is created for import, it may be imported through the one of the
custom Drush commands:

- `drush yaml-content-import <directory>`
- `drush yaml-content-import-module <module_name>`
- `drush yaml-content-import-profile <profile_name>`

-- EXAMPLES --

For some brief content examples, have a look in the `content` folder of this
module. In that folder there are example import files with inline commentary
describing how values are set and the data is structured. These content files
may also be imported into a site with the matching architecture for a demonstration.
In the case of these files, any site installed using the Standard install profile
should have the required content types and field structure to support the demo
content.

To run the import, ensure the yaml_content module is enabled and run the following
command through Drush:

    drush yaml-content-import-module yaml_content

-- INSTALLATION PROFILE USAGE --

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
