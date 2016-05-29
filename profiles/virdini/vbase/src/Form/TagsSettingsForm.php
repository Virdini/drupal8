<?php

namespace Drupal\vbase\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure site information settings for this site.
 */
class TagsSettingsForm extends ConfigFormBase {

  /**
   * Constructs a TagsSettingsForm object.
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
    return 'vbase_tags_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vbase.settings.tags'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->config('vbase.settings.tags');
    
    $form['webmasters_verification'] = array(
      '#title' => t('Webmasters Verification'),
      '#type' => 'details',
      '#description' => t('One code per line'),
    );
    $form['webmasters_verification']['google_verification'] = array(
      '#type' => 'textarea',
      '#title' => t('Google'),
      '#default_value' => implode("\r\n", $settings->get('google_verification')),
    );
    $form['webmasters_verification']['yandex_verification'] = array(
      '#type' => 'textarea',
      '#title' => t('Yandex'),
      '#default_value' => implode("\r\n", $settings->get('yandex_verification')),
    );

    $form['generator'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show generator metatag'),
      '#default_value' => $settings->get('generator'),
    );
    $form['mobile'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show mobile optimized metatags'),
      '#default_value' => $settings->get('mobile'),
    );
    $form['viewport'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show viewport metatag'),
      '#default_value' => $settings->get('viewport'),
    );
    $form['ie_chrome'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show X-UA-Compatible'),
      '#default_value' => $settings->get('ie_chrome'),
    );
    $form['telephone'] = array(
      '#type' => 'checkbox',
      '#title' => t('Telephone detection'),
      '#default_value' => $settings->get('telephone'),
    );

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
    $google_verification = array_filter(explode("\n", str_replace("\r\n", "\n", $form_state->getValue('google_verification'))), 'trim');
    $yandex_verification = array_filter(explode("\n", str_replace("\r\n", "\n", $form_state->getValue('yandex_verification'))), 'trim');
    $this->config('vbase.settings.tags')
      ->set('generator', $form_state->getValue('generator'))
      ->set('mobile', $form_state->getValue('mobile'))
      ->set('viewport', $form_state->getValue('viewport'))
      ->set('ie_chrome', $form_state->getValue('ie_chrome'))
      ->set('telephone', $form_state->getValue('telephone'))
      ->set('google_verification', $google_verification)
      ->set('yandex_verification', $yandex_verification)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
