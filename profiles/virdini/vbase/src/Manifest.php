<?php

namespace Drupal\vbase;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

class Manifest {

  /**
   * Cache backend instance to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  protected $config;

  protected $icons;

  /**
   * Constructs a Manifest object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $cache_backend) {
    $this->cache = $cache_backend;
    $this->config = $config_factory->get('vbase.settings.manifest');
  }

  public function getIcons() {
    if (!isset($this->icons)) {
      $this->icons = [];
      $cache = $this->cache->get('vbase.manifest');
      $cache = FALSE;
      if (!$cache) {
        $files = file_scan_directory('public://manifest', '/.*/', ['key' => 'name']);
        if (!empty($files)) {
          $mimetype = \Drupal::service('file.mime_type.guesser');
          uksort($files, function($a, $b) {
            $a = filter_var($a, FILTER_SANITIZE_NUMBER_INT);
            $b = filter_var($b, FILTER_SANITIZE_NUMBER_INT);
            return $a > $b;
          });
          foreach ($files as $file) {
            $sizes = strtr($file->name, ['square' => '']);
            $this->icons[$file->filename] = [
              'uri' => $file->uri,
              'type' => $mimetype->guess($file->uri),
              'sizes' => $sizes ?: '512x512',
            ];
          }
        }
        $this->cache->set('vbase.manifest', $this->icons, CacheBackendInterface::CACHE_PERMANENT);
      }
      else {
        $this->icons = $cache->data;
      }
    }
    return $this->icons;
  }

  public function getManifestIcons() {
    $allowed = [
      'square.svg',
      'square192x192.png',
      'square512x512.png',
    ];
    return array_intersect_key($this->getIcons(), array_flip($allowed));
  }

  public function setIconLinks(array &$attachments) {
    vbase_add_cacheable_dependency($attachments, $this->config);
    $icons = $this->getIcons();
    if (isset($icons['square180x180.png'])) {
      $attachments['#attached']['html_head_link'][] = [[
        'rel' => 'apple-touch-icon',
        'sizes' => '180x180',
        'href' => file_url_transform_relative(file_create_url($icons['square180x180.png']['uri'])),
      ]];
    }
    if (isset($icons['mask-icon.svg']) && $this->config->get('mask_icon_color')) {
      $attachments['#attached']['html_head_link'][] = [[
        'rel' => 'mask-icon',
        'href' => file_url_transform_relative(file_create_url($icons['mask-icon.svg']['uri'])),
        'color' => $this->config->get('mask_icon_color'),
      ]];
    }
    if (isset($icons['mstile-144x144.png'])) {
      $attachments['#attached']['html_head'][] = [[
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'msapplication-TileImage',
          'content' => file_create_url($icons['mstile-144x144.png']['uri']),
        ],
      ], 'msapplication-TileImage'];
    }
  }

}
