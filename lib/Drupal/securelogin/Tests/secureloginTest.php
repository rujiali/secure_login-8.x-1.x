<?php
/**
 * @file
 * Tests for securelogin module.
 */

namespace Drupal\abbrfilter\Tests;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the functionality of the securelogin module.
 */
class secureloginTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('securelogin');

  public static function getInfo() {
    return array(
      'name' => 'Secure login test',
      'description' => 'Test forcing user to login via https',
      'group' => 'securelogin',
    );
  }

  /**
   * Test forcing https login.
   */
  function testsecurelogin() {

  }
}
?>
