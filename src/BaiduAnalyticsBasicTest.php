<?php
namespace Drupal\baidu_analytics;

/**
 * Basic tests for the Baidu Analytics module.
 */
class BaiduAnalyticsBasicTest extends BaiduAnalyticsTestCase {

  /**
   * Implements DrupalWebTestCase::getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => 'Baidu Analytics basic tests',
      'description' => 'Test Page Visibility, 403/404 and DNT configuration settings.<br/>Test Baidu Analytics Tracker Code (BATC) generation and page inclusion.',
      'group' => 'Baidu Analytics',
    );
  }

  /**
   * Test Page Visibility, 403/404 and DNT configuration settings.
   */
  public function testBaiduAnalyticsPageVisibility() {
    // Create and authenticate test user with enough permissions.
    $permissions = array('access administration pages');
    $this->privilegedUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->privilegedUser);

    // Show tracking on "every page except the listed pages".
    variable_set('baidu_analytics_visibility_pages', 0);
    // Disable tracking one "admin*" pages only.
    variable_set('baidu_analytics_pages', "admin\nadmin/*");
    // Enable tracking only for authenticated users only.
    variable_set('baidu_analytics_roles', array(\Drupal\Core\Session\AccountInterface::AUTHENTICATED_RID => \Drupal\Core\Session\AccountInterface::AUTHENTICATED_RID));

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertRaw($this->baCode, '[testBaiduAnalyticsPageVisibility]: Tracking code is displayed for authenticated users.');

    // Test whether tracking code is not included on pages to omit.
    $this->drupalGet('admin');
    $this->assertNoRaw($this->baCode, '[testBaiduAnalyticsPageVisibility]: Tracking code is not displayed on admin page.');
    $this->drupalGet('admin/config/system');
    // Checking for tracking code URI here, as baCode is displayed in the form.
    $this->assertNoRaw(BAIDU_ANALYTICS_ASYNC_LIBRARY_URL . "?{$this->baCode}", '[testBaiduAnalyticsPageVisibility]: Tracking code is not displayed on admin subpage.');

    // Test whether tracking code display is properly flipped.
    variable_set('baidu_analytics_visibility_pages', 1);
    $this->drupalGet('admin');
    $this->assertRaw($this->baCode, '[testBaiduAnalyticsPageVisibility]: Tracking code is displayed on admin page.');
    $this->drupalGet('admin/config/system/baidu_analytics');
    // Checking for tracking code URI here, as baCode is displayed in the form.
    $this->assertRaw(BAIDU_ANALYTICS_ASYNC_LIBRARY_URL . "?{$this->baCode}", '[testBaiduAnalyticsPageVisibility]: Tracking code is displayed on admin subpage.');
    $this->drupalGet('');
    $this->assertNoRaw($this->baCode, '[testBaiduAnalyticsPageVisibility]: Tracking code is NOT displayed on front page.');

    // Test whether tracking code is not display for anonymous.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertNoRaw($this->baCode, '[testBaiduAnalyticsPageVisibility]: Tracking code is NOT displayed for anonymous.');

    // Switch back to every page except the listed pages.
    variable_set('baidu_analytics_visibility_pages', 0);
    // Enable tracking code for all user roles.
    variable_set('baidu_analytics_roles', array());

    // Test whether 403 forbidden tracking code is shown if user has no access.
    $this->drupalGet('admin');
    $this->assertRaw('/403.html', '[testBaiduAnalyticsPageVisibility]: 403 Forbidden tracking code shown if user has no access.');

    // Test whether 404 not found tracking code is shown on non-existent pages.
    $this->drupalGet($this->randomName(64));
    $this->assertRaw('/404.html', '[testBaiduAnalyticsPageVisibility]: 404 Not Found tracking code shown on non-existent page.');

    // DNT Tests:
    // Enable caching of pages for anonymous users.
    variable_set('cache', TRUE);
    // Test whether DNT headers will fail to disable embedding of tracking code.
    $this->drupalGet('', array(), array('DNT: 1'));
    $this->assertRaw('_hmt.push(["_trackPageview"]);', '[testBaiduAnalyticsDNTVisibility]: DNT header send from client, but page caching is enabled and tracker cannot removed.');
    // DNT works only with caching of pages for anonymous users disabled.
    variable_set('cache', FALSE);
    $this->drupalGet('');
    $this->assertRaw('_hmt.push(["_trackPageview"]);', '[testBaiduAnalyticsDNTVisibility]: Tracking is enabled without DNT header.');
    // Test whether DNT header is able to remove the tracking code.
    $this->drupalGet('', array(), array('DNT: 1'));
    $this->assertNoRaw('_hmt.push(["_trackPageview"]);', '[testBaiduAnalyticsDNTVisibility]: DNT header received from client. Tracking has been disabled by browser.');
    // Disable DNT feature and see if tracker is still embedded.
    variable_set('baidu_analytics_privacy_donottrack', FALSE);
    $this->drupalGet('', array(), array('DNT: 1'));
    $this->assertRaw('_hmt.push(["_trackPageview"]);', '[testBaiduAnalyticsDNTVisibility]: DNT feature is disabled, DNT header from browser has been ignored.');
  }

  /**
   * Test Baidu Analytics Tracker Code (BATC) generation and page inclusion.
   */
  public function testBaiduAnalyticsTrackingCode() {
    // Show tracking code on every page except the listed pages.
    variable_set('baidu_analytics_visibility_pages', 0);
    // Enable tracking code for all user roles.
    variable_set('baidu_analytics_roles', array());

    /* Sample JS code as added to page:
    <script type="text/javascript" src=
    "/sites/all/modules/google_analytics/googleanalytics.js?w"></script>
    <script type="text/javascript">
    var _hmt = _hmt || [];
    _hmt.push(['_setAccount', '0123456789abcdef0123456789abcdef']);
    _hmt.push(['_trackPageview']);

    // For the Asynchronous code type.
    (function() {
    var hm = document.createElement('script');
    hm.src = '//hm.baidu.com/hm.js?0123456789abcdef0123456789abcdef';
    hm.type = 'text/javascript';
    var s = document.getElementsByTagName('script')[0];
    s.parentNode.insertBefore(hm, s);
    })();

    // For the Standard code type.
    document.write(unescape("%3Cscript src=
    'http://hm.baidu.com/h.js?0123456789abcdef0123456789abcdef'
    type='text/javascript'%3E%3C/script%3E"));
    </script>
    */

    // Test whether Asynchronous tracking code uses latest JS.
    variable_set('baidu_analytics_cache', FALSE);
    $this->drupalGet('');
    $this->assertRaw(BAIDU_ANALYTICS_ASYNC_LIBRARY_URL . "?{$this->baCode}", '[testBaiduAnalyticsTrackingCode]: Latest <em>Asynchronous</em> tracking code used.');
    // Check default scope, should be in header.
    $this->assertTrue($this->xpath("//head/script[contains(.,'" . BAIDU_ANALYTICS_ASYNC_LIBRARY_URL . "?{$this->baCode}')]"), '[testBaiduAnalyticsTrackingCode]: Default scope for <em>Asynchronous</em> code is header.');

    // Test whether Standard tracking code uses latest JS.
    variable_set('baidu_analytics_code_type', 'standard');
    $this->drupalGet('');
    $this->assertRaw(BAIDU_ANALYTICS_STANDARD_LIBRARY_URL . "?{$this->baCode}", '[testBaiduAnalyticsTrackingCode]: Latest <em>Standard</em> tracking code used.');
    // Check default scope, should be the last HTML tag in footer.
    $this->assertTrue($this->xpath("//body/*[last()][self::script][contains(.,'" . BAIDU_ANALYTICS_STANDARD_LIBRARY_URL . "?{$this->baCode}')]"), '[testBaiduAnalyticsTrackingCode]: Default scope for <em>Standard</em> code is footer.');

    // Change the scope to header and check again.
    variable_set('baidu_analytics_js_scope', 'header');
    $this->drupalGet('');
    $this->assertTrue($this->xpath("//head/script[contains(.,'" . BAIDU_ANALYTICS_STANDARD_LIBRARY_URL . "?{$this->baCode}')]"), '[testBaiduAnalyticsTrackingCode]: Overridden scope for <em>Standard</em> displays in header correctly.');

    // Check for Asynchronous code in footer.
    variable_set('baidu_analytics_js_scope', 'footer');
    variable_set('baidu_analytics_code_type', 'async');
    $this->drupalGet('');
    $this->assertTrue($this->xpath("//body/*[last()][self::script][contains(.,'" . BAIDU_ANALYTICS_ASYNC_LIBRARY_URL . "?{$this->baCode}')]"), '[testBaiduAnalyticsTrackingCode]: Cached <em>Asynchronous</em> tracking code displays correctly in overridden footer scope.');

    // Test whether the BEFORE and AFTER code is added to the tracker.
    variable_set('baidu_analytics_codesnippet_before', '_setDetectFlash(false);');
    variable_set('baidu_analytics_codesnippet_after', '_hmt.push(["t2._setAccount", "0123456789abcdef0123456789abcde0"]);_hmt.push(["t2._trackPageview"]);');
    $this->drupalGet('');
    $this->assertRaw('_setDetectFlash(false);', '[testBaiduAnalyticsTrackingCode]: Before codesnippet has been found with "Flash" detection disabled.');
    $this->assertRaw('t2._setAccount', '[testBaiduAnalyticsTrackingCode]: After codesnippet with "t2" tracker has been found.');
  }
}
