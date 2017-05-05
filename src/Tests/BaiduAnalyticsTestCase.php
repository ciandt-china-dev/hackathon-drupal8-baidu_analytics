<?php
namespace Drupal\baidu_analytics\Tests;

/**
 * Provides common functionality for the Baidu Analytics test classes.
 */
class BaiduAnalyticsTestCase extends \Drupal\simpletest\WebTestBase {

  protected $profile = 'standard';

  /**
   * Baidu Analytics Web Property ID with 32 lower case hexadecimal characters.
   * @var string
   */
  protected $baCode = '0123456789abcdef0123456789abcdef';

  /**
   * Enable modules and create user with specific permissions.
   */
  public function setUp() {
    // Merge inherited classes modules, see FieldUITestCase for an example.
    $modules = func_get_args();
    if (isset($modules[0]) && is_array($modules[0])) {
      $modules = $modules[0];
    }
    $modules[] = 'baidu_analytics';
    parent::setUp($modules);
    // Initialize the Baidu Analytics tracking account ID.
    $ba_code = variable_get('baidu_analytics_account', '');
    if (empty($ba_code)) {
      variable_set('baidu_analytics_account', $this->baCode);
    }
  }

}
