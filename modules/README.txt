WHAT TO PLACE IN THIS DIRECTORY?
--------------------------------

Placing downloaded and custom modules in this directory separates downloaded and
custom modules from Drupal core's modules. This allows Drupal core to be updated
without overwriting these files.

ORGANIZING MODULES IN THIS DIRECTORY
------------------------------------

contrib - contains modules contributed by Drupal community
virdini - contains modules contributed by Virdini.com
custom - contains modules contributed for the current project

There are number of directories that are ignored when looking for modules. These
are 'src', 'lib', 'vendor', 'assets', 'css', 'files', 'images', 'js', 'misc',
'templates', 'includes', 'fixtures' and 'Drupal'.

DOWNLOAD ADDITIONAL MODULES
---------------------------

Contributed modules from the Drupal community may be downloaded at
https://www.drupal.org/project/project_module.

MULTISITE CONFIGURATION
-----------------------

In multisite configurations, modules found in this directory are available to
all sites. You may also put modules in the sites/all/modules directory, and the
versions in sites/all/modules will take precedence over versions of the same
module that are here. Alternatively, the sites/your_site_name/modules directory
pattern may be used to restrict modules to a specific site instance.

MORE INFORMATION
----------------

Refer to the “Developing for Drupal” section of the README.txt in the Drupal
root directory for further information on extending Drupal with custom modules.
