<?php

namespace Drupal\http2_server_push\EventSubscriber;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\DrupalKernelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Invalidates cache tags & rebuilds container when necessary.
 */
class ConfigSubscriber implements EventSubscriberInterface {

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * The Drupal kernel.
   *
   * @var \Drupal\Core\DrupalKernelInterface
   */
  protected $drupalKernel;

  /**
   * The config installer.
   *
   * @var \Drupal\Core\Config\ConfigInstallerInterface
   */
  protected $configInstaller;

  /**
   * Constructs a ConfigSubscriber object.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   * @param \Drupal\Core\DrupalKernelInterface $drupal_kernel
   *   The Drupal kernel.
   * @param \Drupal\Core\Config\ConfigInstallerInterface $config_installer
   *   The config installer.
   */
  public function __construct(CacheTagsInvalidatorInterface $cache_tags_invalidator, DrupalKernelInterface $drupal_kernel, ConfigInstallerInterface $config_installer) {
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->drupalKernel = $drupal_kernel;
    $this->configInstaller = $config_installer;
  }

  /**
   * Invalidates all render caches when CSS/JS aggregation gets toggled.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The Event to process.
   */
  public function onSave(ConfigCrudEvent $event) {
    if ($this->configInstaller->isSyncing()) {
      return;
    }

    if ($event->getConfig()->getName() !== 'system.performance') {
      return;
    }

    if (!$event->isChanged('css.preprocess') && !$event->isChanged('js.preprocess')) {
      return;
    }

    $this->cacheTagsInvalidator->invalidateTags([
      // Rendered output that is cached. (HTML containing Link headers.)
      'rendered',
    ]);

    // Rebuild the container whenever CSS/JS aggregation gets toggled.
    // @see \Drupal\http2_server_push\Http2ServerPushServiceProvider
    $this->drupalKernel->invalidateContainer();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onSave'];
    return $events;
  }

}
