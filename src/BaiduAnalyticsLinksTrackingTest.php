<?php
namespace Drupal\baidu_analytics;

/**
 * Test Baidu Analytics tracking of links: download, outbound or email links.
 */
class BaiduAnalyticsLinksTrackingTest extends BaiduAnalyticsTestCase {

  /**
   * Implements DrupalWebTestCase::getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => 'Baidu Analytics links tracking tests',
      'description' => 'Test link tracking configuration settings and ensure the expected Baidu Analytics JavaScript code is generated to allow tracking for <em>download</em>, <em>outbound</em> or <em>email</em> links.',
      'group' => 'Baidu Analytics',
    );
  }

  /**
   * Ensure expected JavaScript code is generated for tracking links.
   */
  public function testBaiduAnalyticsLinksTracking() {
    // Initially, all links tracking should be enabled.
    $this->drupalGet('');
    // Ensure baidu_analytics.js is included.
    $this->assertTrue($this->xpath("//head/script[contains(@src,'baidu_analytics.js')]"), '[testBaiduAnalyticsLinksTracking]: Tracking of links is enabled: baidu_analytics.js is not included in the page.');

    // Check download links tracking setting code is displayed correctly.
    $this->assertRaw('"trackDownload":true,"trackDownloadExtensions":"', '[testBaiduAnalyticsLinksTracking]: Download link tracking and files extensions settings code is correctly displayed.');
    $this->assertRaw('"trackDownloadExtensions":"' . BAIDU_ANALYTICS_TRACKFILES_EXTENSIONS . '"', '[testBaiduAnalyticsLinksTracking]: Download extensions setting code is displayed correctly with <em>default list</em> of extensions.');

    // Change the list of extensions to be tracked for download links.
    variable_set('baidu_analytics_trackfiles_extensions', 'test1|test2');
    $this->drupalGet('');
    $this->assertRaw('"trackDownloadExtensions":"test1|test2"', '[testBaiduAnalyticsLinksTracking]: Download extensions setting code is displayed correctly with <em>overridden list</em> of custom file extensions.');

    // Disable tracking of download links.
    variable_set('baidu_analytics_trackfiles', FALSE);
    $this->drupalGet('');
    // Check download links tracking setting code is not displayed.
    $this->assertNoRaw('"trackDownload":true', '[testBaiduAnalyticsLinksTracking]: Download link tracking is disabled and settings code is not displayed.');
    $this->assertNoRaw('"trackDownloadExtensions":', '[testBaiduAnalyticsLinksTracking]: Download extensions setting code is not displayed because tracking of downloads is disabled.');

    // Check email links tracking setting code is displayed correctly.
    $this->assertRaw('"trackMailto":true', '[testBaiduAnalyticsLinksTracking]: Email links tracking setting code is correctly displayed.');

    // Disable tracking of email links.
    variable_set('baidu_analytics_trackmailto', FALSE);
    $this->drupalGet('');
    // Check email links tracking setting code is not displayed.
    $this->assertNoRaw('"trackMailto":true', '[testBaiduAnalyticsLinksTracking]: Email links tracking setting code is not displayed because it is disabled.');

    // Check outbound links tracking setting code is displayed correctly.
    $this->assertRaw('"trackOutbound":true', '[testBaiduAnalyticsLinksTracking]: Outbound links tracking setting code is correctly displayed.');
    // Check download links tracking setting code is not displayed.
    $this->assertNoRaw('"trackDownloadExtensions":', '[testBaiduAnalyticsLinksTracking]: Download extensions setting code is not displayed because tracking of downloads is disabled.');

    // Disable tracking of outbound links: Link tracking completely disabled.
    variable_set('baidu_analytics_trackoutbound', FALSE);
    $this->drupalGet('');
    // Check outbound links tracking setting code is not displayed.
    $this->assertNoRaw('"trackOutbound":true', '[testBaiduAnalyticsLinksTracking]: Outbound links tracking setting code is not displayed because outbound links tracking is disabled.');
    // Check email links tracking setting code is displayed correctly.
    $this->assertNoRaw('"trackDownloadExtensions":', '[testBaiduAnalyticsLinksTracking]: Download extensions setting code is not displayed because tracking of downloads is disabled.');
    // Ensure the baidu_analytics.js JavaScript file is not included.
    $this->drupalGet('');
    $this->assertFalse($this->xpath("//head/script[contains(@src,'baidu_analytics.js')]"), '[testBaiduAnalyticsLinksTracking]: Tracking of links is disabled: baidu_analytics.js is not included in the page.');
  }
}
