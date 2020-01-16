<?php

namespace Drupal\Tests\http2_server_push\Functional;

use Drupal\Component\Utility\Html;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * @group http2_server_push
 */
class LinkHeaderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['toolbar', 'http2_server_push'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Grant anonymous users access to the toolbar, so the anonymous user gets
    // some JavaScript.
    $this->grantPermissions(Role::load(RoleInterface::ANONYMOUS_ID), ['access toolbar']);
  }

  public function testCssJsLinkHeaders() {
    $session = $this->getSession();

    // No Server Push Link response headers should be present when CSS and JS
    // aggregation are disabled.
    $this->drupalGet('<front>');
    $this->assertNull($session->getResponseHeader('Link'));

    // \Drupal\Tests\BrowserTestBase::installDrupal() overrides some of the
    // defaults for easier test debugging. But for a CDN integration test, we do
    // want the defaults to be applied, because that is what we want to test.
    $this->config('system.performance')
      ->set('css.preprocess', TRUE)
      ->set('js.preprocess', TRUE)
      ->save();

    $html = $this->drupalGet('<front>');

    // Determine the expected link response headers.
    $expected_link_response_headers = [];
    $document = Html::load($html);
    $xpath = new \DOMXPath($document);
    $dom_nodes = $xpath->query('//link[@rel="stylesheet"]');
    foreach ($dom_nodes as $dom_node) {
      $expected_link_response_headers[] = '<' . $dom_node->getAttribute('href') . '>; rel=preload; as=style';
    }
    $dom_nodes = $xpath->query('//script[@src]');
    foreach ($dom_nodes as $dom_node) {
      $expected_link_response_headers[] = '<' . $dom_node->getAttribute('src') . '>; rel=preload; as=script';
    }

    // Assert that the expected Server Push Link response headers are present.
    $this->assertSame($expected_link_response_headers, $session->getResponseHeaders()['Link']);

    // @todo test more explicitly that browser-conditional CSS and JS does not get a Server Push Link header. This is a bit difficult because DOMXPath automatically ignores this HTML.
    $conditional_script_html = <<<HTML
<!--[if lte IE 8]>
<script
HTML;

    $this->assertSession()->responseContains($conditional_script_html);
  }

}
