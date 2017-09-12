<?php

namespace Drupal\Tests\flood_unblock\Functional;


use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the Flood Unblock UI pages are reachable.
 *
 * @group flood_unblock
 */
class FloodUnblockUiPageTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['flood_unblock'];

  public function testFloodUnblockUiPage() {
    $account = $this->drupalCreateUser(['access flood unblock']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/config/system/flood-unblock');
    $this->assertSession()->statusCodeEquals(200);

    // Test that there is an empty flood list.
    $this->assertSession()->pageTextContains('There are no failed logins at this time.');
  }

}
