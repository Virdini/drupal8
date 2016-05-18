## What to place in this directory?

Placing downloaded and custom themes in this directory separates downloaded and
custom themes from Drupal core's themes. This allows Drupal core to be updated
without overwriting these files.

## Organizing themes in this directory

- **contrib** - contains themes contributed by [Drupal community](https://drupal.org)
- **virdini** - contains themes contributed by [Virdini.com](http://virdini.com)

## Download additional themes

Contributed themes from the Drupal community may be downloaded at
https://www.drupal.org/project/project_theme.

## Multisite configuration

In multisite configurations, themes found in this directory are available to
all sites. You may also put themes in the sites/all/themes directory, and the
versions in sites/all/themes will take precedence over versions of the same
themes that are here. Alternatively, the sites/your_site_name/themes directory
pattern may be used to restrict themes to a specific site instance.

## More information

Refer to the "Appearance" section of the README.txt in the Drupal root directory
for further information on customizing the appearance of Drupal with custom
themes.
