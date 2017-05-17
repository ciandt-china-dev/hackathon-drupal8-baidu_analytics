<?php

namespace Drupal\baidu_analytics\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Test basic functionality of Baidu Analytics module.
 *
 * @group Baidu Analytics
 */
class BaiduAnalyticsBasicTest extends WebTestBase {

  /**
   * User without permissions to use snippets.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $noSnippetUser;
  /**
   * Baidu Analytics Web Property ID with 32 lower case hexadecimal characters.
   * @var string
   */
  protected $baCode = '0123456789abcdef0123456789abcdef';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'baidu_analytics',
    'help',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer baidu analytics',
      'administer modules',
      'administer site configuration',
    ];

    // User to set up baidu_analytics.
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);

    // Place the block or the help is not shown.
    $this->drupalPlaceBlock('help_block', ['region' => 'help']);
  }

  /**
   * Tests if configuration is possible.
   */
  public function testBaiduAnalyticsConfiguration() {
    // Check if Configure link is available on 'Extend' page.
    // Requires 'administer modules' permission.
    $this->drupalGet('admin/modules');
    $this->assertRaw('admin/config/system/baidu_analytics', '[testBaiduAnalyticsConfiguration]: Configure link from Extend page to Baidu Analytics Settings page exists.');

    // Check if Configure link is available on 'Status Reports' page.
    // NOTE: Link is only shown without UA code configured.
    // Requires 'administer site configuration' permission.
    $this->drupalGet('admin/reports/status');
    $this->assertRaw('admin/config/system/baidu_analytics', '[testBaiduAnalyticsConfiguration]: Configure link from Status Reports page to Baidu Analytics Settings page exists.');

    // Check for setting page's presence.
    $this->drupalGet('admin/config/system/baidu_analytics');
    $this->assertRaw(t('Web Property ID'), '[testBaiduAnalyticsConfiguration]: Settings page displayed.');

    // Check for account code validation.
    $edit['baidu_analytics_account'] = $this->randomMachineName(2);
    $this->drupalPostForm('admin/config/system/baidu_analytics', $edit, t('Save configuration'));
    $this->assertRaw(t('A valid Baidu Analytics Web Property ID should have exactly 32 lower case hexadecimal characters (only allowed: 0 to 9 and a to f).'), '[testBaiduAnalyticsConfiguration]: Invalid Web Property ID number validated.');

    // User should have access to code snippets.
    $this->assertFieldByName('baidu_analytics_codesnippet_before');
    $this->assertFieldByName('baidu_analytics_codesnippet_after');
    $this->assertNoFieldByXPath("//textarea[@name='baidu_analytics_codesnippet_before' and @disabled='disabled']", NULL, '"Code snippet (before)" is enabled.');
    $this->assertNoFieldByXPath("//textarea[@name='baidu_analytics_codesnippet_after' and @disabled='disabled']", NULL, '"Code snippet (after)" is enabled.');
  }

  /**
   * Tests if help sections are shown.
   */
  public function testBaiduAnalyticsHelp() {
    // Requires help and block module and help block placement.
    $this->drupalGet('admin/config/system/baidu_analytics');
    $this->assertText('Baidu Analytics is a free (registration required) website traffic and marketing effectiveness service.', '[testBaiduAnalyticsHelp]: Baidu Analytics help text shown on module settings page.');

    // Requires help.module.
    $this->drupalGet('admin/help/baidu_analytics');
    $this->assertText('Baidu Analytics adds a web statistics tracking system to your website.', '[testBaiduAnalyticsHelp]: Baidu Analytics help text shown in help section.');
  }

  /**
   * Tests if page visibility works.
   */
  public function testBaiduAnalyticsPageVisibility() {
    $this->drupalGet('');
    $this->assertNoRaw('//hm.baidu.com/hm.js', '[testBaiduAnalyticsPageVisibility]: Tracking code is not displayed without BA code configured.');

    $this->config('baidu_analytics.settings')->set('baidu_analytics_account', "{$this->baCode}")->save();

    // Show tracking on "every page except the listed pages".
    $this->config('baidu_analytics.settings')->set('baidu_analytics_visibility_pages', 0)->save();
    // Disable tracking on "admin*" pages only.
    $this->config('baidu_analytics.settings')->set('baidu_analytics_pages', "/admin\n/admin/*")->save();
    // Enable tracking only for authenticated users only.
    $this->config('baidu_analytics.settings')->set('baidu_analytics_roles', [AccountInterface::AUTHENTICATED_ROLE => AccountInterface::AUTHENTICATED_ROLE])->save();

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertRaw("{$this->baCode}", '[testBaiduAnalyticsPageVisibility]: Tracking code is displayed for authenticated users.');

    // Test whether tracking code is not included on pages to omit.
    $this->drupalGet('admin');
    $this->assertNoRaw("{$this->baCode}", '[testBaiduAnalyticsPageVisibility]: Tracking code is not displayed on admin page.');
    $this->drupalGet('admin/config/system/baidu_analytics');
    // Checking for tracking URI here, as code is displayed in the form.
    $this->assertNoRaw('//hm.baidu.com/hm.js', '[testBaiduAnalyticsPageVisibility]: Tracking code is not displayed on admin subpage.');

    // Test whether tracking code display is properly flipped.
    $this->config('baidu_analytics.settings')->set('baidu_analytics_visibility_pages', 1)->save();
    $this->drupalGet('admin');
    $this->assertRaw("{$this->baCode}", '[testBaiduAnalyticsPageVisibility]: Tracking code is displayed on admin page.');
    $this->drupalGet('admin/config/system/baidu_analytics');
    // Checking for tracking URI here, as code is displayed in the form.
    $this->assertRaw('//hm.baidu.com/hm.js', '[testBaiduAnalyticsPageVisibility]: Tracking code is displayed on admin subpage.');
    $this->drupalGet('');
    $this->assertNoRaw("{$this->baCode}", '[testBaiduAnalyticsPageVisibility]: Tracking code is NOT displayed on front page.');

    // Test whether tracking code is not display for anonymous.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertNoRaw("{$this->baCode}", '[testBaiduAnalyticsPageVisibility]: Tracking code is NOT displayed for anonymous.');

    // Switch back to every page except the listed pages.
    $this->config('baidu_analytics.settings')->set('baidu_analytics_visibility_pages', 0)->save();
    // Enable tracking code for all user roles.
    $this->config('baidu_analytics.settings')->set('baidu_analytics_roles', [])->save();

    // Test whether 403 forbidden tracking code is shown if user has no access.
    $this->drupalGet('admin');
    $this->assertResponse(403);
    $this->assertRaw('/403.html', '[testBaiduAnalyticsPageVisibility]: 403 Forbidden tracking code shown if user has no access.');

    // Test whether 404 not found tracking code is shown on non-existent pages.
    $this->drupalGet($this->randomMachineName(64));
    $this->assertResponse(404);
    $this->assertRaw('/404.html', '[testBaiduAnalyticsPageVisibility]: 404 Not Found tracking code shown on non-existent page.');
  }

  /**
   * Tests if tracking code is properly added to the page.
   */
  public function testBaiduAnalyticsTrackingCode() {
    $this->config('baidu_analytics.settings')->set('baidu_analytics_account', "{$this->baCode}")->save();
    // Show tracking code on every page except the listed pages.
    $this->config('baidu_analytics.settings')->set('baidu_analytics_visibility_pages', 0)->save();
    // Enable tracking code for all user roles.
    $this->config('baidu_analytics.settings')->set('baidu_analytics_visibility_roles', [])->save();

    // Test whether Asynchronous tracking code uses latest JS.
    $this->config('baidu_analytics.settings')->set('baidu_analytics_cache', 0)->save();
    $this->drupalGet('');
    $this->assertRaw(BAIDU_ANALYTICS_ASYNC_LIBRARY_URL . "?{$this->baCode}", '[testBaiduAnalyticsTrackingCode]: Latest <em>Asynchronous</em> tracking code used.');
    // Check default scope, should be in header.
    $this->assertTrue($this->xpath("//head/script[contains(.,'" . BAIDU_ANALYTICS_ASYNC_LIBRARY_URL . "?{$this->baCode}')]"), '[testBaiduAnalyticsTrackingCode]: Default scope for <em>Asynchronous</em> code is header.');

    // Test whether Standard tracking code uses latest JS.
    $this->config('baidu_analytics.settings')->set('baidu_analytics_code_type', 'standard');
    $this->drupalGet('');
    $this->assertRaw(BAIDU_ANALYTICS_STANDARD_LIBRARY_URL . "?{$this->baCode}", '[testBaiduAnalyticsTrackingCode]: Latest <em>Standard</em> tracking code used.');
    // Check default scope, should be the last HTML tag in footer.
    $this->assertTrue($this->xpath("//body/*[last()][self::script][contains(.,'" . BAIDU_ANALYTICS_STANDARD_LIBRARY_URL . "?{$this->baCode}')]"), '[testBaiduAnalyticsTrackingCode]: Default scope for <em>Standard</em> code is footer.');

    // Change the scope to header and check again.
    $this->config('baidu_analytics.settings')->set('baidu_analytics_js_scope', 'header');
    $this->drupalGet('');
    $this->assertTrue($this->xpath("//head/script[contains(.,'" . BAIDU_ANALYTICS_STANDARD_LIBRARY_URL . "?{$this->baCode}')]"), '[testBaiduAnalyticsTrackingCode]: Overridden scope for <em>Standard</em> displays in header correctly.');

    // Check for Asynchronous code in footer.
    $this->config('baidu_analytics.settings')->set('baidu_analytics_js_scope', 'footer');
    $this->config('baidu_analytics.settings')->set('baidu_analytics_code_type', 'async');
    $this->drupalGet('');
    $this->assertTrue($this->xpath("//body/*[last()][self::script][contains(.,'" . BAIDU_ANALYTICS_ASYNC_LIBRARY_URL . "?{$this->baCode}')]"), '[testBaiduAnalyticsTrackingCode]: Cached <em>Asynchronous</em> tracking code displays correctly in overridden footer scope.');

    // Test whether the BEFORE and AFTER code is added to the tracker.
    $this->config('baidu_analytics.settings')->set('baidu_analytics_codesnippet_before', '_setDetectFlash(false);');
    $this->config('baidu_analytics.settings')->set('baidu_analytics_codesnippet_after', '_hmt.push(["t2._setAccount", "0123456789abcdef0123456789abcde0"]);_hmt.push(["t2._trackPageview"]);');
    $this->drupalGet('');
    $this->assertRaw('_setDetectFlash(false);', '[testBaiduAnalyticsTrackingCode]: Before codesnippet has been found with "Flash" detection disabled.');
    $this->assertRaw('t2._setAccount', '[testBaiduAnalyticsTrackingCode]: After codesnippet with "t2" tracker has been found.');
  }

}
