<?php
namespace Drupal\baidu_analytics;

/**
 * Test Baidu Analytics tracking of status messages.
 */
class BaiduAnalyticsStatusMessagesTest extends BaiduAnalyticsTestCase {

  /**
   * Implements DrupalWebTestCase::getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => 'Baidu Analytics status messages tests',
      'description' => 'Test status messages configuration settings and ensure the expected Baidu Analytics JavaScript code is generated to allow tracking of <em>status</em>, <em>warning</em> or <em>error</em> messages.',
      'group' => 'Baidu Analytics',
    );
  }

  /**
   * Enable modules and create user with specific permissions.
   */
  public function setUp() {
    // Include the Baidu Analytics Test helper module to trigger messages.
    parent::setUp('baidu_analytics_test');
  }

  /**
   * Ensure expected JavaScript code is generated for tracking status messages.
   */
  public function testBaiduAnalyticsStatusMessages() {
    // Enable tracking of errors only.
    variable_set('baidu_analytics_trackmessages', array('error' => 'error'));

    $status_heading = array(
      'status' => t('Status message'),
      'warning' => t('Warning message'),
      'error' => t('Error message'),
    );

    // Programmatically test all messages: status, error and warning.
    $this->drupalPost('user/login', array(), 'Log in');
    // Check error messages from invalid form submission.
    $this->assertRaw('_hmt.push(["_trackEvent", "Messages", "Error message", ' . drupal_json_encode($status_heading['error'] . ': ' . t('Username field is required.')) . ']);', '[testBaiduAnalyticsStatusMessages]: _trackEvent "Username field is required." is shown.');
    $this->assertRaw('_hmt.push(["_trackEvent", "Messages", "Error message", ' . drupal_json_encode($status_heading['error'] . ': ' . t('Password field is required.')) . ']);', '[testBaiduAnalyticsStatusMessages]: _trackEvent "Password field is required." is shown.');

    // Check all the messages added by baidu_analytics_test.
    // Status/warning messages should not be found since they are not tracked.
    $this->assertNoRaw('_hmt.push(["_trackEvent", "Messages", "Status message", ' . drupal_json_encode(drupal_json_encode($status_heading['status']) . ': ' . t('Baidu Analytics Test status message.')) . ']);', '[testBaiduAnalyticsStatusMessages]: Baidu Analytics Test status message is <strong>not</strong> displayed because tracking of <em>status</em> messages is <strong>disabled</strong>.');
    $this->assertNoRaw('_hmt.push(["_trackEvent", "Messages", "Warning message", ' . drupal_json_encode(drupal_json_encode($status_heading['warning']) . ': ' . t('Baidu Analytics Test warning message.')) . ']);', '[testBaiduAnalyticsStatusMessages]: Baidu Analytics Test warning message is <strong>not</strong> displayed because tracking of <em>warning</em> messages is <strong>disabled</strong>.');

    // Check error messages should be tracked and the HTML stripped off.
    $this->assertRaw('_hmt.push(["_trackEvent", "Messages", "Error message", ' . drupal_json_encode($status_heading['error'] . ': ' . t('Baidu Analytics Test error message.')) . ']);', '[testBaiduAnalyticsStatusMessages]: Baidu Analytics Test error message is displayed.');
    $this->assertRaw('_hmt.push(["_trackEvent", "Messages", "Error message", ' . drupal_json_encode($status_heading['error'] . ': ' . t('Baidu Analytics Test error message with html tags and link.')) . ']);', '[testBaiduAnalyticsStatusMessages]: HTML has been stripped off successfully from Baidu Analytics Test error message with html tags and link.');

    // Enable all settings and test again for warning and status messages.
    variable_set('baidu_analytics_trackmessages', array(
      'error' => 'error',
      'warning' => 'warning',
      'status' => 'status',
    ));
    $this->drupalGet('');
    $this->assertRaw('_hmt.push(["_trackEvent", "Messages", "Status message", ' . drupal_json_encode($status_heading['status'] . ': ' . t('Baidu Analytics Test status message.')) . ']);', '[testBaiduAnalyticsStatusMessages]: Baidu Analytics Test status message is displayed because tracking of <em>status</em> messages is <strong>enabled</strong>.');
    $this->assertRaw('_hmt.push(["_trackEvent", "Messages", "Warning message", ' . drupal_json_encode($status_heading['warning'] . ': ' . t('Baidu Analytics Test warning message.')) . ']);', '[testBaiduAnalyticsStatusMessages]: Baidu Analytics Test warning message is displayed for tracking because tracking of <em>status</em> messages is <strong>enabled</strong>.');
  }
}
