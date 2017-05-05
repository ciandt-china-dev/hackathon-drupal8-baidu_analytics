<?php
namespace Drupal\baidu_analytics;

/**
 * Test Baidu Analytics permissisons.
 */
class BaiduAnalyticsPermissionsTest extends BaiduAnalyticsTestCase {

  /**
   * Implements DrupalWebTestCase::getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => 'Baidu Analytics permissions tests',
      'description' => 'Test the permissions added by the module: <em>administer baidu analytics</em>, <em>opt-in or out of tracking</em>, <em>use PHP for tracking visibility</em>.<br/>Test the <em>Web Property ID</em> field validation: required and validate exactly 32 lower case hexadecimal characters.<br/>Test <em>opt-in or out of tracking</em> setting for users in the edit account page.',
      'group' => 'Baidu Analytics',
    );
  }

  /**
   * Enable modules and create user with specific permissions.
   */
  public function setUp() {
    // Include the PHP Filter module for this test case.
    parent::setUp('php');

    // Create and authenticate test user with all permissions.
    $permissions = array(
      'access administration pages',
      'opt-in or out of tracking',
      'administer baidu analytics',
      'use PHP for tracking visibility',
    );
    $this->privilegedUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->privilegedUser);
  }

  /**
   * Test Baidu Analytics permissions and settings form validation.
   */
  public function testBaiduAnalyticsPemissions() {
    // Browse to the Baidu Analytics settings form page.
    $this->drupalGet('admin/config/system/baidu_analytics');
    // Check if expected Web Property ID field is displayed.
    $this->assertFieldByName('baidu_analytics_account', '', '[testBaiduAnalyticsPemissions]: Settings page displayed and the <em>Web Property ID</em> field is displayed correctly.');
    // Check if expected PHP Filter option is displayed.
    $this->assertRaw('Pages on which this PHP code returns <code>TRUE</code> (experts only)', '[testBaiduAnalyticsPemissions]: <em>PHP code visibility</em> setting is allowed and displayed correctly.');

    // Check required Web Property ID field.
    $edit['baidu_analytics_account'] = '';
    $this->drupalPost('admin/config/system/baidu_analytics', $edit, t('Save configuration'));
    $this->assertRaw(t('Web Property ID field is required.'), '[testBaiduAnalyticsPemissions]: <em>Web Property ID</em> field is required.');

    // Check Web Property ID field validation.
    $edit['baidu_analytics_account'] = $this->randomName(2);
    $this->drupalPost('admin/config/system/baidu_analytics', $edit, t('Save configuration'));
    $this->assertRaw(t('A valid Baidu Analytics Web Property ID should have exactly 32 lower case hexadecimal characters (only allowed: 0 to 9 and a to f).'), format_string('[testBaiduAnalyticsPemissions]: Invalid <em>Web Property ID</em> field value, provided string is <strong>too short</strong>: @code.', array('@code' => $edit['baidu_analytics_account'])));
    // Test a string of 32 characters but with uppercase letters.
    $edit['baidu_analytics_account'] = 'F0123456789abcdef0123456789abcde';
    $this->drupalPost('admin/config/system/baidu_analytics', $edit, t('Save configuration'));
    $this->assertRaw(t('A valid Baidu Analytics Web Property ID should have exactly 32 lower case hexadecimal characters (only allowed: 0 to 9 and a to f).'), format_string('[testBaiduAnalyticsPemissions]: Invalid <em>Web Property ID</em> containing <strong>uppercase</strong> characters: @code.', array('@code' => $edit['baidu_analytics_account'])));

    // Test: use PHP for tracking visibility.
    // Logout and login with another user with fewer permissions.
    $this->drupalLogout();
    $permissions = array(
      'access administration pages',
      'opt-in or out of tracking',
      'administer baidu analytics',
    );
    $this->privilegedUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->privilegedUser);

    // Enough permissions to get to the settings form page.
    $this->drupalGet('admin/config/system/baidu_analytics');
    // Check if expected Web Property ID field is displayed.
    $this->assertFieldByName('baidu_analytics_account', '', '[testBaiduAnalyticsPemissions]: Settings page displayed and theWeb Property ID field is displayed correctly.');
    // No more permissions to use PHP code for page visibility settings.
    $this->assertNoRaw('Pages on which this PHP code returns <code>TRUE</code> (experts only)', '[testBaiduAnalyticsPemissions]: <em>PHP code visibility</em> setting is <strong>not</strong> allowed and <strong>not</strong> displayed. User does not have the permission: <em>use PHP for tracking visibility</em>.');

    // Test: administer baidu analytics.
    // Logout and login with another user with fewer permissions.
    $this->drupalLogout();
    $permissions = array(
      'access administration pages',
      'opt-in or out of tracking',
    );
    $this->privilegedUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->privilegedUser);

    // Not enough permissions to get to the settings form page.
    $this->drupalGet('admin/config/system/baidu_analytics');
    // Check if expected Web Property ID field is displayed.
    $this->assertNoFieldByName('baidu_analytics_account', '', '[testBaiduAnalyticsPemissions]: Settings page is <strong>not</strong> displayed and access to the page is denied. User does not have the permission: <em>administer baidu analytics</em>.');
    $this->assertResponse(403, '[testBaiduAnalyticsPemissions]: Received a <strong>403</strong> response code for the Baidu Analytics settings form: <strong>Access denied</strong>.');

    // Test: opt-in or out of tracking.
    // Tracking on by default, users with permission can opt out.
    variable_set('baidu_analytics_custom', 1);
    $this->drupalGet("user/{$this->privilegedUser->uid}/edit");
    $this->assertFieldByName('baidu_analytics[custom]', TRUE, '[testBaiduAnalyticsPemissions]: <em>Enable user tracking</em> (opt-in or out) setting is displayed correctly in user edit account page and <strong>enabled</strong> by default.');

    // Check tracking off by default, users with permission can opt in.
    variable_set('baidu_analytics_custom', 2);
    $this->drupalGet("user/{$this->privilegedUser->uid}/edit");
    $this->assertFieldByName('baidu_analytics[custom]', FALSE, '[testBaiduAnalyticsPemissions]: <em>Enable user tracking</em> (opt-in or out) setting is displayed correctly in user edit account page and <strong>disabled</strong> by default.');

    // Logout and login with another user with fewer permissions.
    $this->drupalLogout();
    $permissions = array(
      'access administration pages',
    );
    $this->privilegedUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->privilegedUser);

    // Not enough permissions to opt-in or out of tracking.
    $this->drupalGet("user/{$this->privilegedUser->uid}/edit");
    $this->assertNoFieldByName('baidu_analytics[custom]', TRUE, '[testBaiduAnalyticsPemissions]: <em>Enable user tracking</em> (opt-in or out) setting is <strong>not</strong> displayed in user edit account page. User does not have the permission: <em>opt-in or out of tracking</em>.');
  }
}
