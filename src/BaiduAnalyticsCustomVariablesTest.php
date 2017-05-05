<?php
namespace Drupal\baidu_analytics;

/**
 * Test Baidu Analytics Custom Variables configuration.
 */
class BaiduAnalyticsCustomVariablesTest extends BaiduAnalyticsTestCase {

  /**
   * Implements DrupalWebTestCase::getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => 'Baidu Analytics Custom Variables tests',
      'description' => 'Test Custom Variables configuration settings and ensure the expected Baidu Analytics JavaScript code is generated to allow tracking of <em>five</em> Custom Variables.<br/>Check variables do not display if they are wrongly configured.',
      'group' => 'Baidu Analytics',
      'dependencies' => array('token'),
    );
  }

  /**
   * Enable modules and create user with specific permissions.
   */
  public function setUp() {
    // Include the Token module for testing tokens replacements in variables.
    parent::setUp('token');
  }

  /**
   * Ensure configured Custom Variables are included as expected in pages.
   */
  public function testBaiduAnalyticsCustomVariables() {
    // Basic test if the feature works.
    $custom_vars = array(
      'slots' => array(
        1 => array(
          'slot' => 1,
          'name' => 'Foo 1',
          'value' => 'Bar 1',
          'scope' => 3,
        ),
        2 => array(
          'slot' => 2,
          'name' => 'Foo 2',
          'value' => 'Bar 2',
          'scope' => 2,
        ),
        3 => array(
          'slot' => 3,
          'name' => 'Foo 3',
          'value' => 'Bar 3',
          'scope' => 3,
        ),
        4 => array(
          'slot' => 4,
          'name' => 'Foo 4',
          'value' => 'Bar 4',
          'scope' => 2,
        ),
        5 => array(
          'slot' => 5,
          'name' => 'Foo 5',
          'value' => 'Bar 5',
          'scope' => 1,
        ),
      ),
    );
    variable_set('baidu_analytics_custom_var', $custom_vars);
    $this->drupalGet('');

    foreach ($custom_vars['slots'] as $slot) {
      $this->assertRaw("_hmt.push(['_setCustomVar', " . $slot['slot'] . ", \"" . $slot['name'] . "\", \"" . $slot['value'] . "\", " . $slot['scope'] . "]);", '[testBaiduAnalyticsCustomVariables]: _setCustomVar ' . $slot['slot'] . ' is shown.');
    }

    // Test whether tokens are replaced in custom variable names.
    $site_slogan = $this->randomName(16);
    variable_set('site_slogan', $site_slogan);

    $custom_vars = array(
      'slots' => array(
        1 => array(
          'slot' => 1,
          'name' => 'Name: [site:slogan]',
          'value' => 'Value: [site:slogan]',
          'scope' => 3,
        ),
        2 => array(
          'slot' => 2,
          'name' => '',
          'value' => $this->randomName(16),
          'scope' => 1,
        ),
        3 => array(
          'slot' => 3,
          'name' => $this->randomName(16),
          'value' => '',
          'scope' => 2,
        ),
        4 => array(
          'slot' => 4,
          'name' => '',
          'value' => '',
          'scope' => 3,
        ),
        5 => array(
          'slot' => 5,
          'name' => '',
          'value' => '',
          'scope' => 3,
        ),
      ),
    );
    variable_set('baidu_analytics_custom_var', $custom_vars);
    $this->verbose('<pre>' . print_r($custom_vars, TRUE) . '</pre>');

    $this->drupalGet('');
    $this->assertRaw("_hmt.push(['_setCustomVar', 1, \"Name: $site_slogan\", \"Value: $site_slogan\", 3]", '[testBaiduAnalyticsCustomVariables]: Tokens have been replaced in custom variable.');
    $this->assertNoRaw("_hmt.push(['_setCustomVar', 2,", '[testBaiduAnalyticsCustomVariables]: Value with empty name is not shown.');
    $this->assertNoRaw("_hmt.push(['_setCustomVar', 3,", '[testBaiduAnalyticsCustomVariables]: Name with empty value is not shown.');
    $this->assertNoRaw("_hmt.push(['_setCustomVar', 4,", '[testBaiduAnalyticsCustomVariables]: Empty name and value is not shown.');
    $this->assertNoRaw("_hmt.push(['_setCustomVar', 5,", '[testBaiduAnalyticsCustomVariables]: Empty name and value is not shown.');

    // Test Baidu Analytics Tokens replacements.
    $custom_vars['slots'][1]['name'] = 'User Roles: [current-user:baidu_analytics:role-names]';
    $custom_vars['slots'][1]['value'] = 'Value: [current-user:baidu_analytics:role-names]';
    $custom_vars['slots'][2]['name'] = 'User Role IDs: [current-user:baidu_analytics:role-ids]';
    $custom_vars['slots'][2]['value'] = 'Value: [current-user:baidu_analytics:role-ids]';
    $custom_vars['slots'][3]['name'] = 'Combined: [current-user:baidu_analytics:role-names]-[current-user:baidu_analytics:role-ids]';
    $custom_vars['slots'][3]['value'] = 'Value: [current-user:baidu_analytics:role-names]-[current-user:baidu_analytics:role-ids]';

    variable_set('baidu_analytics_custom_var', $custom_vars);
    $this->verbose('<pre>' . print_r($custom_vars, TRUE) . '</pre>');

    // Create and authenticate test user with all permissions.
    $this->privilegedUser = $this->drupalCreateUser();
    $this->drupalLogin($this->privilegedUser);

    // Get user's roles ids and names to be compare with replacements.
    $role_names = 'authenticated user';
    $role_ids = 2;

    $this->drupalGet('');
    $this->assertRaw("_hmt.push(['_setCustomVar', 1, \"User Roles: $role_names\", \"Value: $role_names\", 3]", format_string('[testBaiduAnalyticsCustomVariables]: Baidu Analytics <em>Role names</em> token has been replaced in custom variable: @role_names.', array('@role_names' => $role_names)));
    $this->assertRaw("_hmt.push(['_setCustomVar', 2, \"User Role IDs: $role_ids\", \"Value: $role_ids\", 1]", format_string('[testBaiduAnalyticsCustomVariables]: Baidu Analytics <em>Role IDs</em> token has been replaced in custom variable: @role_ids.', array('@role_ids' => $role_ids)));
    $this->assertRaw("_hmt.push(['_setCustomVar', 3, \"Combined: $role_names-$role_ids\", \"Value: $role_names-$role_ids\", 2]", format_string('[testBaiduAnalyticsCustomVariables]: Baidu Analytics <em>Role names</em> and <em>Role IDs</em> tokens have been replaced in custom variable: @combined.', array('@combined' => "$role_names-$role_ids")));
  }
}
