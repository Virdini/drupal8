<?php

namespace Drupal\vcustom\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure site information settings for this site.
 */
class ContactInformationForm extends ConfigFormBase {

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
    return 'vcustom_contact_information';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vcustom.contact'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->config('vcustom.contact');
    
    $telephones = $settings->get('telephones');
    $form['telephone'] = array(
      '#type' => 'textfield',
      '#title' => t('Telephone number'),
      '#default_value' => isset($telephones[0]) ? $telephones[0] : '',
    );
    $form['email'] = array(
      '#type' => 'email',
      '#title' => t('Email address'),
      '#default_value' => $settings->get('email'),
    );
    $form['copyright'] = array(
      '#type' => 'textfield',
      '#title' => t('Copyright'),
      '#default_value' => $settings->get('copyright'),
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
    $telephones = [$form_state->getValue('telephone')];
    $this->config('vcustom.contact')
      ->set('email', $form_state->getValue('email'))
      ->set('copyright', $form_state->getValue('copyright'))
      ->set('telephones', $telephones)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
