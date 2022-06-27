CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Usage
 * Configuration
 * Maintainers


INTRODUCTION
------------

This module allows users to export nodes and then import it into another
Drupal installation, or on the same site.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/node_export

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/node_export


INSTALLATION
------------

 * Install the Node Export module as you would normally install a
   contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.


USAGE
------

 1. To export nodes, either:
    a) Use the 'Node export' tab on a node page.
    b) Use the content tab (admin/content) and select the Export Option and then
      there are two ways:
        1) You can export all nodes of a particular content type.
        2) You can provide all the node ids you want to export
           wish to export and then choose 'Node export' under the
           'Update options'.

 2. To import nodes that were exported with Node export, either:
    a) Use the form at 'Node export: import' under 'Admin/content'.
       Paste the export code you generated in export function and click submit.

 3. You can also export the nodes using drush command.

    $ drush ne-export 'nid' > 'filename'

       a) For eg. If you want to export node with node id 10 and the filename is
          node.json then the command will be like:

            $drush ne-export 10 > node.json

       b) For more than one nodes, you can write their nids seprated by space.

            $drush ne-export  1  3  4 > node.json


CONFIGURATION
-------------

The module provides a configuration form under:

    Configuration > Content authoring > Node Export Settings


MAINTAINERS
-----------

 * Gaurav Kapoor (gaurav.kapoor) - https://www.drupal.org/u/gauravkapoor

Supporting organizations:

 * OpenSense Labs - https://www.drupal.org/opensense-labs
