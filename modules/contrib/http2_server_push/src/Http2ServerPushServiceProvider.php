<?php

namespace Drupal\http2_server_push;

use Drupal\Core\Config\BootstrapConfigStorageFactory;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

/**
 * @see \Drupal\http2_server_push\EventSubscriber\ConfigSubscriber::onSave()
 */
class Http2ServerPushServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    if (!$this->cssAggregationIsEnabled()) {
      $container->removeDefinition('asset.css.collection_renderer.http2_server_push');
    }
    if (!$this->jsAggregationIsEnabled()) {
      $container->removeDefinition('asset.js.collection_renderer.http2_server_push');
    }
  }

  /**
   * @return bool
   */
  protected function cssAggregationIsEnabled() {
    return BootstrapConfigStorageFactory::get()->read('system.performance')['css']['preprocess'] === TRUE;
  }

  /**
   * @return bool
   */
  protected function jsAggregationIsEnabled() {
    return BootstrapConfigStorageFactory::get()->read('system.performance')['js']['preprocess'] === TRUE;
  }

}
