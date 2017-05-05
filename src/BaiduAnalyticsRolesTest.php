<?php
namespace Drupal\baidu_analytics;

/**
 * Test Baidu Analytics configuration for different Roles.
 */
class BaiduAnalyticsRolesTest extends BaiduAnalyticsTestCase {

  /**
   * Implements DrupalWebTestCase::getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => 'Baidu Analytics role tests',
      'description' => 'Test roles functionality of Baidu Analytics module.',
      'description' => 'Test roles configuration settings and ensure Baidu Analytics JavaScript code is displayed or hidden depending on user\'s roles.',
      'group' => 'Baidu Analytics',
    );
  }

  /**
   * Ensure JavaScript code is hidden depending on configured roles settings.
   */
  public function testBaiduAnalyticsRolesTracking() {
    // Create and authenticate test user with all permissions.
    $permissions = array(
      'access administration pages',
      'administer baidu analytics',
    );
    $this->privilegedUser = $this->drupalCreateUser($permissions);

    // Test if the default settings are working as expected.
    // Add to the selected roles only.
    variable_set('baidu_analytics_visibility_roles', 0);
    // Enable tracking for all users.
    variable_set('baidu_analytics_roles', array());

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertRaw($this->baCode, '[testBaiduAnalyticsRoleVisibility]: Tracking code is displayed for anonymous users on frontpage with default settings.');
    $this->drupalGet('admin');
    $this->assertRaw('/403.html', '[testBaiduAnalyticsRoleVisibility]: 403 Forbidden tracking code is displayed for anonymous users in admin section with default settings.');

    $this->drupalLogin($this->privilegedUser);

    $this->drupalGet('');
    $this->assertRaw($this->baCode, '[testBaiduAnalyticsRoleVisibility]: Tracking code is displayed for authenticated users on frontpage with default settings.');
    $this->drupalGet('admin');
    $this->assertNoRaw($this->baCode, '[testBaiduAnalyticsRoleVisibility]: Tracking code is NOT displayed for authenticated users in admin section with default settings.');

    // Test if the non-default settings are working as expected.
    // Enable tracking only for authenticated users.
    variable_set('baidu_analytics_roles', array(\Drupal\Core\Session\AccountInterface::AUTHENTICATED_RID => \Drupal\Core\Session\AccountInterface::AUTHENTICATED_RID));

    $this->drupalGet('');
    $this->assertRaw($this->baCode, '[testBaiduAnalyticsRoleVisibility]: Tracking code is displayed for authenticated users only on frontpage.');

    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertNoRaw($this->baCode, '[testBaiduAnalyticsRoleVisibility]: Tracking code is NOT displayed for anonymous users on frontpage.');

    // Add to every role except the selected ones.
    variable_set('baidu_analytics_visibility_roles', 1);
    // Enable tracking for all users.
    variable_set('baidu_analytics_roles', array());

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertRaw($this->baCode, '[testBaiduAnalyticsRoleVisibility]: Tracking code is added to every role and displayed for anonymous users.');
    $this->drupalGet('admin');
    $this->assertRaw('/403.html', '[testBaiduAnalyticsRoleVisibility]: 403 Forbidden tracking code is shown for anonymous users if every role except the selected ones is selected.');

    $this->drupalLogin($this->privilegedUser);

    $this->drupalGet('');
    $this->assertRaw($this->baCode, '[testBaiduAnalyticsRoleVisibility]: Tracking code is added to every role and displayed on frontpage for authenticated users.');
    $this->drupalGet('admin');
    $this->assertNoRaw($this->baCode, '[testBaiduAnalyticsRoleVisibility]: Tracking code is added to every role and NOT displayed in admin section for authenticated users.');

    // Disable tracking for authenticated users.
    variable_set('baidu_analytics_roles', array(\Drupal\Core\Session\AccountInterface::AUTHENTICATED_RID => \Drupal\Core\Session\AccountInterface::AUTHENTICATED_RID));

    $this->drupalGet('');
    $this->assertNoRaw($this->baCode, '[testBaiduAnalyticsRoleVisibility]: Tracking code is NOT displayed on frontpage for excluded authenticated users.');
    $this->drupalGet('admin');
    $this->assertNoRaw($this->baCode, '[testBaiduAnalyticsRoleVisibility]: Tracking code is NOT displayed in admin section for excluded authenticated users.');

    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertRaw($this->baCode, '[testBaiduAnalyticsRoleVisibility]: Tracking code is displayed on frontpage for included anonymous users.');
  }
}
