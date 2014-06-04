<?php

/**
 * @file
 * Definition of Drupal\securelogin\Tests\SecureloginTest.
 */

namespace Drupal\securelogin\Tests;

use Drupal\simpletest\WebTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the functionality of the Secure Login module.
 */
class SecureloginTest extends WebTestBase {

  protected $request;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Secure login test',
      'description' => 'Test to redirect the user to HTTPS if on a login form.',
      'group' => 'Secure Login',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Ensure that Secure Login expects us to be redirected.
    \Drupal::config('securelogin.settings')
      ->set('secure_forms', TRUE)
      ->save();

    $this->request = Request::createFromGlobals();
    $this->container->set('request', $this->request);
  }

  /**
   * Test forcing https login.
   *
   * Ensure a request over HTTP gets 301 redirected to HTTPS
   */
  protected function testHttpSecureLogin() {
    global $base_url;

    $this->request->server->set('HTTPS', 'off');
    $url = $base_url . '/core/modules/system/tests/http.php/user/login';
    $this->drupalGet($url);
    $this->assertResponse(301);
    // @TODO ensure the user ends up on the login page
  }

  /**
   * Ensure HTTPS requests do not get redirected.
   */
  protected function testHttpsSecureLogin() {
    global $base_url;

    $this->request->server->set('HTTPS', 'on');
    $url = $base_url . '/core/modules/system/tests/https.php/user/login';
    $this->drupalGet($url);
    $this->assertResponse(200);
    // @TODO ensure the user ends up on the login page
  }
}
