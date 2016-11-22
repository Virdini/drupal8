<?php

namespace Drupal\vbase\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure site information settings for this site.
 */
class DevelopersSettingsForm extends ConfigFormBase {

  /**
   * Constructs a DevelopersSettingsForm object.
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
    return 'vbase_developers_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vbase.settings.developers'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vbase.settings.developers');
    
    $form['logo'] = [
      '#required' => TRUE,
      '#type' => 'select',
      '#title' => $this->t('Logo'),
      '#default_value' => $config->get('logo'),
      '#options' => [
        'logo.svg',
        'logo.dark.svg',
        'virdini.black.svg',
        'virdini.white.svg',
        'virdini.black.t.svg',
        'virdini.white.t.svg',
      ],
    ];
    
    $form['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $config->get('width'),
    ];
    
    $form['developed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Developed'),
      '#default_value' => $config->get('developed'),
    ];
    
    $form['maintained'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Maintained'),
      '#default_value' => $config->get('maintained'),
    ];
    
    $form['label'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Label'),
      '#default_value' => $config->get('label'),
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
    $width = (int)$form_state->getValue('width');
    if ($width < 70) {
      $width = 70;
    }
    $this->config('vbase.settings.developers')
      ->set('logo', $form_state->getValue('logo'))
      ->set('width', $width)
      ->set('developed', $form_state->getValue('developed'))
      ->set('maintained', $form_state->getValue('maintained'))
      ->set('label', $form_state->getValue('label'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
