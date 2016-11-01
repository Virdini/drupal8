<?php

namespace Drupal\vbase\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\NodeType;

/**
 * Configure site content protection settings for this site.
 */
class ContentProtectionSettingsForm extends ConfigFormBase {

  /**
   * Constructs a ContentProtectionSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vbase_content_protection_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vbase.settings.cp'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->config('vbase.settings.cp');
    
    $options = [];
    $nodeTypes = NodeType::loadMultiple();
    foreach ($nodeTypes as $nodeType) {
      $options[$nodeType->id()] = $nodeType->label();
    }
    
    $form['node_bundles'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Node Bundles'),
      '#options' => $options,
      '#default_value' => $settings->get('node_bundles'),
    ];
    
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('vbase.settings.cp')
      ->set('node_bundles', $form_state->getValue('node_bundles'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
