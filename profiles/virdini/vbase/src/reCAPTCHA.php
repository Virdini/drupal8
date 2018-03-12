<?php

namespace Drupal\vbase;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Drupal\Component\Serialization\Json;

class reCAPTCHA {

  /**
   * URL to which requests are sent via cURL.
   */
  const API_URL = 'https://www.google.com/recaptcha/api/siteverify';

  /**
   * Cache backend instance to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  protected $config;

  /**
   * Constructs a reCAPTCHA object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $cache_backend) {
    $this->cache = $cache_backend;
    $this->config = $config_factory->get('vbase.settings.antispam');
  }

  /**
   * Calls the reCAPTCHA siteverify API to verify whether the user passes CAPTCHA test.
   *
   * @param string $response
   *  The value of 'g-recaptcha-response' in the submitted form.
   * @param string $ip
   *  The end user's IP address.
   *
   * @return bool
   *  If user passes CAPTCHA test.
   */
  public function verify($response, $ip = NULL) {
    if (empty($response)) {
      return FALSE;
    }
    // Check the reCAPTCHA response in Database.
    $cid = 'vbase:'. hash('sha256', $response);
    if ($this->cache->get($cid)) {
      return FALSE;
    }
    $this->cache->set($cid, [], time() + 86400);

    $params = ['secret' => $this->config->get('secret_key'), 'response' => $response];
    if (!is_null($ip)) {
      $params['remoteip'] = $ip;
    }
    $data = $this->submit($params);
    return isset($data['success']) && $data['success'] == TRUE;
  }

  /**
   * Submits the cURL request with the specified parameters.
   *
   * @param array $params
   *  Request parameters
   *
   * @return array
   *  Decoded body of the reCAPTCHA response
   */
  public function submit(array $params) {
    try {
      $response = $this->getClient()->request('POST', '', ['form_params' => $params]);
      $info = Json::decode((string)$response->getBody());
      if (is_array($info)) {
        return $info;
      }
    }
    catch (GuzzleException $e) {
      watchdog_exception('vbase', $e);
    }
    return [];
  }

  protected function getClient() {
    return new Client([
      'base_uri' => self::API_URL,
      'headers' => [
        'Content-Type' => 'application/x-www-form-urlencoded',
        'Accept' => 'application/json; charset=UTF-8',
        'Accept-Encoding' => 'gzip',
        'User-Agent' => 'Drupal '. \Drupal::VERSION .' (gzip)',
      ],
      'connect_timeout' => 3,
      'timeout' => 3,
    ]);
  }

}
