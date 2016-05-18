## What to place in this directory?

Placing downloaded and custom modules in this directory separates downloaded and
custom modules from Drupal core's modules. This allows Drupal core to be updated
without overwriting these files.

## Organizing modules in this directory

- **contrib** - contains modules contributed by [Drupal community](https://drupal.org)
- **virdini** - contains modules contributed by [Virdini.com](http://virdini.com)
- **custom** - contains modules contributed for the current project

There are number of directories that are ignored when looking for modules. These
are 'src', 'lib', 'vendor', 'assets', 'css', 'files', 'images', 'js', 'misc',
'templates', 'includes', 'fixtures' and 'Drupal'.

## Download additional modules

Contributed modules from the Drupal community may be downloaded at
https://www.drupal.org/project/project_module.

## Multisite configuration

In multisite configurations, modules found in this directory are available to
all sites. You may also put modules in the sites/all/modules directory, and the
versions in sites/all/modules will take precedence over versions of the same
module that are here. Alternatively, the sites/your_site_name/modules directory
pattern may be used to restrict modules to a specific site instance.
