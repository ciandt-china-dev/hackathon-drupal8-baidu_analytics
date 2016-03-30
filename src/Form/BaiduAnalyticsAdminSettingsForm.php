<?php

/**
 * @file
 * Contains \Drupal\baidu_analytics\Form\BaiduAnalyticsAdminSettingsForm.
 */

namespace Drupal\baidu_analytics\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class BaiduAnalyticsAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'baidu_analytics_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('baidu_analytics.settings');
    $values = $form_state->getValues();
    foreach ($values as $variable => $value) {
      $config->set($variable, $value);
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['baidu_analytics.settings'];
  }

  public function buildForm(array $form_state, \Drupal\Core\Form\FormStateInterface $form_state) {

    $form['#attached']['library'][] = 'baidu_analytics/baidu_analytics';
    $form['account'] = [
      '#type' => 'fieldset',
      '#title' => t('General settings'),
    ];

    // Web Property ID should have exactly 32 lower case hexadecimal characters.
    $form['account']['baidu_analytics_account'] = [
      '#title' => t('Web Property ID'),
      '#type' => 'textfield',
      '#default_value' => \Drupal::config('baidu_analytics.settings')->get('baidu_analytics_account'),
      '#size' => 32,
      '#maxlength' => 32,
      '#required' => TRUE,
      '#description' => t('This ID is unique to each site you want to track separately, and is in the form of <em>0123456789abcdef0123456789abcdef</em> with exactly 32 lower case hexadecimal characters (only allowed: 0 to 9 and a to f). To get a Web Property ID, <a href="@analytics">register your site with Baidu Analytics</a>, or if you already have registered your site, go to your Baidu Analytics tracker code page to <a href="@screenshot" title="How to find my site\'s Web Property ID">extract the ID inside the Javascript code provided <em>(screenshot)</em></a>. <a href="@webpropertyid">Find more information in the documentation</a>.', [
        '@analytics' => 'http://tongji.baidu.com/',
        '@webpropertyid' => 'http://tongji.baidu.com/open/api/more?p=guide_overview',
        '@screenshot' => 'https://drupal.org/files/project-images/20130823DO_baidu_analytics_tracking_code_rev1.jpg',
      ]),
    ];

    // Visibility settings.
    $form['tracking_title'] = [
      '#type' => 'item',
      '#title' => t('Tracking scope'),
    ];
    $form['tracking'] = [
      '#type' => 'vertical_tabs',
    ];
    // Page specific visibility configurations.
    $php_access = \Drupal::currentUser()->hasPermission('use PHP for tracking visibility');

    $visibility = \Drupal::config('baidu_analytics.settings')->get('baidu_analytics_visibility_pages', '0');
    $pages = \Drupal::config('baidu_analytics.settings')->get('baidu_analytics_pages');

    $form['page_vis_settings'] = [
      '#type' => 'details',
      '#title' => t('Pages'),
      '#collapsible' => TRUE,
      '#group' => 'tracking',
    ];

    if ($visibility == 2 && !$php_access) {
      $form['page_vis_settings'] = [];
      $form['page_vis_settings']['visibility'] = [
        '#type' => 'value',
        '#value' => 2,
      ];
      $form['page_vis_settings']['pages'] = [
        '#type' => 'value',
        '#value' => $pages,
      ];
    } else {
      $options = [
        t('Every page except the listed pages'),
        t('The listed pages only'),
      ];
      $description = t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", [
        '%blog' => 'blog',
        '%blog-wildcard' => 'blog/*',
        '%front' => '<front>',
      ]);

      if (\Drupal::moduleHandler()->moduleExists('php') && $php_access) {
        $options[] = t('Pages on which this PHP code returns <code>TRUE</code> (experts only)');
        $form['page_vis_settings']['#title'] = t('Pages or PHP code');
        $description .= ' ' . t('If the PHP option is chosen, enter PHP code between %php. Note that executing incorrect PHP code can break your Drupal site.', [
          '%php' => '<?php ?>'
          ]);
      }

      // Pages visibility settings exclude/include paths or PHP code.
      $form['page_vis_settings']['baidu_analytics_visibility_pages'] = [
        '#type' => 'radios',
        '#title' => t('Add tracking to specific pages'),
        '#options' => $options,
        '#default_value' => $visibility,
      ];

      // Pages visibility textarea could receive either paths or PHP code.
      $form['page_vis_settings']['baidu_analytics_pages'] = [
        '#type' => 'textarea',
        '#title' => $form['page_vis_settings']['#title'],
        '#title_display' => 'invisible',
        '#default_value' => $pages,
        '#description' => $description,
        '#rows' => 10,
      ];
    }
    // Render the role overview.
    $form['role_vis_settings'] = [
      '#type' => 'details',
      '#title' => t('Roles'),
      '#group' => 'tracking',
    ];
     $form['role_vis_settings']['baidu_analytics_visibility_roles'] = [
      '#type' => 'radios',
      '#title' => t('Add tracking for specific roles'),
      '#options' => [
        t('Add to the selected roles only'),
        t('Add to every role except the selected ones'),
      ],
      '#default_value' => \Drupal::config('baidu_analytics.settings')->get('baidu_analytics_visibility_roles'),
    ];

    $role_options = user_role_names();
    $form['role_vis_settings']['baidu_analytics_roles'] = [
      '#type' => 'checkboxes',
      '#title' => t('Roles'),
      '#default_value' => \Drupal::config('baidu_analytics.settings')->get('baidu_analytics_roles'),
      '#options' => $role_options,
      '#description' => t('If none of the roles are selected, all users will be tracked. If a user has any of the roles checked, that user will be tracked (or excluded, depending on the setting above).'),
    ];

    // Standard tracking configurations.
    $form['user_vis_settings'] = [
      '#type' => 'details',
      '#title' => t('Users'),
      '#group' => 'tracking',
    ];
    $t_permission = ['%permission' => t('opt-in or out of tracking')];
    $form['user_vis_settings']['baidu_analytics_custom'] = [
      '#type' => 'radios',
      '#title' => t('Allow users to customize tracking on their account page'),
      '#options' => [
        t('No customization allowed'),
        t('Tracking on by default, users with %permission permission can opt out', $t_permission),
        t('Tracking off by default, users with %permission permission can opt in', $t_permission),
      ],
      '#default_value' => \Drupal::config('baidu_analytics.settings')->get('baidu_analytics_custom'),
    ];

    // Link Tracking specific configurations.
    $form['linktracking'] = [
      '#type' => 'details',
      '#title' => t('Links and downloads'),
      '#group' => 'tracking',
    ];
    $form['linktracking']['baidu_analytics_trackoutbound'] = [
      '#type' => 'checkbox',
      '#title' => t('Track clicks on outbound links'),
      '#default_value' => \Drupal::config('baidu_analytics.settings')->get('baidu_analytics_trackoutbound'),
    ];
    $form['linktracking']['baidu_analytics_trackmailto'] = [
      '#type' => 'checkbox',
      '#title' => t('Track clicks on mailto links'),
      '#default_value' => \Drupal::config('baidu_analytics.settings')->get('baidu_analytics_trackmailto'),
    ];
    $form['linktracking']['baidu_analytics_trackfiles'] = [
      '#type' => 'checkbox',
      '#title' => t('Track downloads (clicks on file links) for the following extensions'),
      '#default_value' => \Drupal::config('baidu_analytics.settings')->get('baidu_analytics_trackfiles'),
    ];

    $form['linktracking']['baidu_analytics_trackfiles_extensions'] = [
      '#title' => t('List of download file extensions'),
      '#title_display' => 'invisible',
      '#type' => 'textfield',
      '#default_value' => \Drupal::config('baidu_analytics.settings')->get('baidu_analytics_trackfiles_extensions'),
      '#description' => t('A file extension list separated by the | character that will be tracked as download when clicked. Regular expressions are supported. For example: !extensions', [
        '!extensions' => BAIDU_ANALYTICS_TRACKFILES_EXTENSIONS
        ]),
      '#maxlength' => 255,
    ];


    // Message Tracking specific configurations.
    $form['messagetracking'] = [
      '#type' => 'details',
      '#title' => t('Messages'),
      '#group' => 'tracking',
    ];
    // @FIXME
    // Could not extract the default value because it is either indeterminate, or
    // not scalar. You'll need to provide a default value in
    // config/install/baidu_analytics.settings.yml and config/schema/baidu_analytics.schema.yml.
    $form['messagetracking']['baidu_analytics_trackmessages'] = [
      '#type' => 'checkboxes',
      '#title' => t('Track messages of type'),
      '#default_value' => \Drupal::config('baidu_analytics.settings')->get('baidu_analytics_trackmessages'),
      '#description' => t('This will track the selected message types shown to users. Tracking of form validation errors may help you identifying usability issues in your site. For each visit (user session), a maximum of approximately 500 combined BATC requests (both events and page views) can be tracked. Every message is tracked as one individual event. Note that - as the number of events in a session approaches the limit - additional events might not be tracked. Messages from excluded pages cannot tracked.'),
      '#options' => [
        'status' => t('Status message'),
        'warning' => t('Warning message'),
        'error' => t('Error message'),
      ],
    ];

    // Baidu Analytics already has many translations, otherwise a note is
    // displayed to change the language.
    $form['search_and_advertising'] = [
      '#type' => 'details',
      '#title' => t('Search and Advertising'),
      '#group' => 'tracking',
    ];

    // Search and Advertising configuration.
    $site_search_dependencies = '<div class="admin-requirements">';
    $site_search_dependencies .= t('Requires: !module-list', [
      '!module-list' => (\Drupal::moduleHandler()->moduleExists('search') ? t('@module (<span class="admin-enabled">enabled</span>)', [
        '@module' => 'Search'
        ]) : t('@module (<span class="admin-disabled">disabled</span>)', [
        '@module' => 'Search'
        ]))
      ]);
    $site_search_dependencies .= '</div>';

    $form['search_and_advertising']['baidu_analytics_site_search'] = [
      '#type' => 'checkbox',
      '#title' => t('Track internal search'),
      '#description' => t('If checked, internal search keywords are tracked with search results page urls. The query keywords and total items count are added as parameters to the search results page URL being tracked.') . $site_search_dependencies,
      '#default_value' => \Drupal::config('baidu_analytics.settings')->get('baidu_analytics_site_search'),
      '#disabled' => (\Drupal::moduleHandler()->moduleExists('search') ? FALSE : TRUE),
    ];

    // Privacy specific configurations.
    $form['privacy'] = [
      '#type' => 'details',
      '#title' => t('Privacy'),
      '#group' => 'tracking',
    ];
    $form['privacy']['baidu_analytics_privacy_donottrack'] = [
      '#type' => 'checkbox',
      '#title' => t('Universal web tracking opt-out'),
      '#description' => t('If enabled and your server receives the <a href="@donottrack">Do-Not-Track</a> header from the client browser, the Baidu Analytics module will not embed any tracking code into your site. Compliance with Do Not Track could be purely voluntary, enforced by industry self-regulation, or mandated by state or federal law. Please accept your visitors privacy. If they have opt-out from tracking and advertising, you should accept their personal decision. This feature is currently limited to logged in users and disabled page caching.', [
        '@donottrack' => 'http://donottrack.us/'
        ]),
      '#default_value' => \Drupal::config('baidu_analytics.settings')->get('baidu_analytics_privacy_donottrack'),
    ];

    // Custom variables.
    $form['baidu_analytics_custom_var'] = [
      '#collapsed' => TRUE,
      '#collapsible' => TRUE,
      '#description' => t('You can add Baidu Analytics <a href="@custom_var_documentation">Custom Variables</a> here. These will be added to every page that Baidu Analytics tracking code appears on. Baidu Analytics will only accept custom variables if the <em>name</em> and <em>value</em> combined are less than 128 bytes after URL encoding. Keep the names as short as possible and expect long values to get trimmed. You may use tokens in custom variable names and values. Global and user tokens are always available; on node pages, node tokens are also available.', [
        '@custom_var_documentation' => 'http://tongji.baidu.com/open/api/more?p=guide_setCustomVar'
        ]),
      '#title' => t('Custom variables'),
      '#tree' => TRUE,
      '#type' => 'details',
    ];

    $form['baidu_analytics_custom_var']['baidu_analytics_custom_var_table'] = [
      '#type' => 'table',
      '#header' => array(t('Slot'), t('Name'), t('Value'), t('Scope')),
    ];

    $baidu_analytics_custom_vars = \Drupal::config('baidu_analytics.settings')->get('baidu_analytics_custom_var');

    // Baidu Analytics supports up to 5 custom variables.
    for ($i = 1; $i < 6; $i++) {
      $form['baidu_analytics_custom_var']['baidu_analytics_custom_var_table'][$i]['slot'] = [
        '#default_value' => $i,
        '#description' => t('Slot number'),
        '#disabled' => TRUE,
        '#size' => 1,
        '#title' => t('Custom variable slot #@slot', [
          '@slot' => $i
          ]),
        '#title_display' => 'invisible',
        '#type' => 'textfield',
      ];
      $form['baidu_analytics_custom_var']['baidu_analytics_custom_var_table'][$i]['name'] = [
        '#default_value' => !empty($baidu_analytics_custom_vars['baidu_analytics_custom_var_table'][$i]['name']) ? $baidu_analytics_custom_vars['baidu_analytics_custom_var_table'][$i]['name'] : '',
        '#description' => t('The custom variable name.'),
        '#maxlength' => 255,
        '#size' => 20,
        '#title' => t('Custom variable name #@slot', [
          '@slot' => $i
          ]),
        '#title_display' => 'invisible',
        '#type' => 'textfield',
        '#element_validate' => [
          'baidu_analytics_token_element_validate'
          ],
        '#token_types' => ['node'],
      ];
      $form['baidu_analytics_custom_var']['baidu_analytics_custom_var_table'][$i]['value'] = [
        '#default_value' => !empty($baidu_analytics_custom_vars['baidu_analytics_custom_var_table'][$i]['value']) ? $baidu_analytics_custom_vars['baidu_analytics_custom_var_table'][$i]['value'] : '',
        '#description' => t('The custom variable value.'),
        '#maxlength' => 255,
        '#title' => t('Custom variable value #@slot', [
          '@slot' => $i
          ]),
        '#title_display' => 'invisible',
        '#type' => 'textfield',
        '#element_validate' => [
          'baidu_analytics_token_element_validate'
          ],
        '#token_types' => ['node'],
      ];
      if (\Drupal::moduleHandler()->moduleExists('token')) {
        $form['baidu_analytics_custom_var'][$i]['name']['#element_validate'][] = 'token_element_validate';
        $form['baidu_analytics_custom_var'][$i]['value']['#element_validate'][] = 'token_element_validate';
      }
      $form['baidu_analytics_custom_var']['baidu_analytics_custom_var_table'][$i]['scope'] = [
        '#default_value' => !empty($baidu_analytics_custom_vars['baidu_analytics_custom_var_table'][$i]['scope']) ? $baidu_analytics_custom_vars['baidu_analytics_custom_var_table'][$i]['scope'] : 3,
        '#description' => t('The scope for the custom variable.'),
        '#title' => t('Custom variable slot #@slot', [
          '@slot' => $i
          ]),
        '#title_display' => 'invisible',
        '#type' => 'select',
        '#options' => [
          1 => t('Visitor'),
          2 => t('Session'),
          3 => t('Page'),
        ],
      ];
    }

    $form['baidu_analytics_custom_var']['baidu_analytics_custom_var_description'] = [
      '#type' => 'item',
      '#description' => t('You can supplement Baidu Analytics\' basic IP address tracking of visitors by segmenting users based on custom variables. <a href="@ba_tos">Baidu Analytics terms of service</a> requires that You will not (and will not allow any third party to) use the Service to track, collect or upload any data that personally identifies an individual (such as a name, email address or billing information), or other data which can be reasonably linked to such information by Baidu. You will have and abide by an appropriate Privacy Policy and will comply with all applicable laws and regulations relating to the collection of information from Visitors. You must post a Privacy Policy and that Privacy Policy must provide notice of Your use of cookies that are used to collect traffic data, and You must not circumvent any privacy features (e.g., an opt-out) that are part of the Service.', [
        '@ba_tos' => 'http://www.baidu.com/duty/'
        ]),
    ];
    $form['baidu_analytics_custom_var']['baidu_analytics_custom_var_token_tree'] = [
      '#theme' => 'token_tree',
      '#token_types' => [
        'node'
        ],
      '#dialog' => TRUE,
    ];

    // Advanced feature configurations.
    $form['advanced'] = [
      '#type' => 'details',
      '#title' => t('Advanced settings'),
      '#open' => FALSE,

    ];
    // Baidu Analytics Tracking Code (BATC) type.
    $form['advanced']['baidu_analytics_code_type'] = [
      '#type' => 'radios',
      '#title' => t('Select the type of tracking code to insert in the page'),
      '#description' => t('Baidu Analytics supports <a href="@screenshot">different types of Javascript code (screenshot)</a> to be added to the page: The <strong>Standard</strong> code (also called <em>Legacy</em> code) and the <strong>Asynchronous</strong> code.<br/>By default, the <em>Asynchronous</em> code is selected since it is recommended for <strong>improved performance</strong> <em>(the page might load faster)</em>.<br/>However, the display of Baidu Analytics small images/logos is only supported with the Standard code type: <strong>small image/logo will not display if the Asynchronous code is selected</strong>.<br/><br/>If <em>Default</em> is selected for the <em>JavaScript scope</em> <em>(field below)</em>, the <em>Asynchronous</em> code would be added to the <em>header</em>, and the <em>Standard</em> code would be added to the <em>footer</em>, as recommended by Baidu Analytics.', [
        '@screenshot' => 'https://drupal.org/files/project-images/20130823DO_baidu_analytics_tracking_code_rev1.jpg'
        ]),
      '#options' => [
        'async' => t('Asynchronous <em>(Recommended)</em>'),
        'standard' => t('Standard'),
      ],
      '#default_value' => \Drupal::config('baidu_analytics.settings')->get('baidu_analytics_code_type'),
    ];

    // Enable caching of the Baidu Analytics JavaScript tracker file.
    $form['advanced']['baidu_analytics_cache'] = [
      '#type' => 'checkbox',
      '#title' => t('Locally cache tracking code file'),
      '#description' => t("If checked, the tracking code file is retrieved from Baidu Analytics and cached locally. It is updated daily from Baidu's servers to ensure updates to tracking code are reflected in the local copy. Do not activate this until after Baidu Analytics has confirmed that site tracking is working!"),
      '#default_value' => \Drupal::config('baidu_analytics.settings')->get('baidu_analytics_cache'),
    ];

    // Allow for tracking of the originating node when viewing translation sets.
    if (\Drupal::moduleHandler()->moduleExists('translation')) {
      $form['advanced']['baidu_analytics_translation_set'] = [
        '#type' => 'checkbox',
        '#title' => t('Track translation sets as one unit'),
        '#description' => t('When a node is part of a translation set, record statistics for the originating node instead. This allows for a translation set to be treated as a single unit.'),
        '#default_value' => \Drupal::config('baidu_analytics.settings')->get('baidu_analytics_translation_set'),
      ];
    }

    // Provide code snippets fields to allow inserting custom JavaScript logic.
    $form['advanced']['codesnippet'] = [
      '#type' => 'details',
      '#title' => t('Custom JavaScript code'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => t('You can add custom Baidu Analytics <a href="@snippets">code snippets</a> here. These will be added every time tracking is in effect. Before you add your custom code, you should read the <a href="@ba_concepts_overview">Baidu Analytics Tracking Code - Functional Overview</a> and the <a href="@ba_js_api">Baidu Analytics Tracking API</a> documentation. <strong>Do not include the &lt;script&gt; tags</strong>, and always end your code with a semicolon (;).', [
        '@snippets' => 'https://drupal.org/node/2076741',
        '@ba_concepts_overview' => 'http://tongji.baidu.com/open/api/more?p=ref_trackEvent',
        '@ba_js_api' => 'http://tongji.baidu.com/open/api/',
      ]),
    ];
    $form['advanced']['codesnippet']['baidu_analytics_codesnippet_before'] = [
      '#type' => 'textarea',
      '#title' => t('Code snippet (before)'),
      '#default_value' => \Drupal::config('baidu_analytics.settings')->get('baidu_analytics_codesnippet_before'),
      '#rows' => 5,
      '#description' => t("Code in this textarea will be added <strong>before</strong> _hmt.push(['_trackPageview'])."),
    ];
    $form['advanced']['codesnippet']['baidu_analytics_codesnippet_after'] = [
      '#type' => 'textarea',
      '#title' => t('Code snippet (after)'),
      '#default_value' => \Drupal::config('baidu_analytics.settings')->get('baidu_analytics_codesnippet_after'),
      '#rows' => 5,
      '#description' => t("Code in this textarea will be added <strong>after</strong> _hmt.push(['_trackPageview']). This is useful if you'd like to track a site in two accounts."),
    ];

    // Allow selection of the scope/region in which script should be inserted.
    // @FIXME
    // theme() has been renamed to _theme() and should NEVER be called directly.
    // Calling _theme() directly can alter the expected output and potentially
    // introduce security issues (see https://www.drupal.org/node/2195739). You
    // should use renderable arrays instead.
    // 
    // 
    // @see https://www.drupal.org/node/2195739
    // $js_scope_description_list = theme('item_list', array(
    //     'items' => array(
    //       t('<strong>Standard</strong> code in the <strong>footer</strong> of the page right before @body.', array('@body' => "</body>")),
    //       t('<strong>Asynchronous</strong> code, in the <strong>header</strong> of the page right before @head.', array('@head' => "</head>")),
    //     ),
    //   ));

    $form['advanced']['baidu_analytics_js_scope'] = [
      '#type' => 'select',
      '#title' => t('JavaScript scope'),
      '#description' => t('<strong>Default</strong> should be selected to follow Baidu Analytics\' recommended settings:!item_list Feel free to override this setting by selecting a specific scope, such as <em>header</em> or <em>footer</em>, in the dropdown.<br/>For more information, please check <a href="@ba_settings">Baidu Analytics Recommendations</a> or the <a href="@screenshot">different recommended positions for each type of code</a>.', [
        '!item_list' => $js_scope_description_list,
        '@ba_settings' => 'http://tongji.baidu.com/open/api/more?p=ref_setAccount',
        '@screenshot' => 'https://drupal.org/files/project-images/20130823DO_baidu_analytics_tracking_code_rev1.jpg',
      ]),
      '#options' => [
        'default' => t('Default'),
        'footer' => t('Footer'),
        'header' => t('Header'),
      ],
      '#default_value' => \Drupal::config('baidu_analytics.settings')->get('baidu_analytics_js_scope'),
    ];


    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // Custom variables validation.
    foreach ($form_state->getValue(['baidu_analytics_custom_var', 'slots']) as $custom_var) {
      $form_state->setValue(['baidu_analytics_custom_var', 'slots', $custom_var['slot'], 'name'], trim($custom_var['name']));
      $form_state->setValue(['baidu_analytics_custom_var', 'slots', $custom_var['slot'], 'value'], trim($custom_var['value']));

      // Validate empty names/values.
      if (empty($custom_var['name']) && !empty($custom_var['value'])) {
        $form_state->setErrorByName("baidu_analytics_custom_var][slots][" . $custom_var['slot'] . "][name", t('The custom variable @slot-number requires a <em>Name</em> if a <em>Value</em> has been provided.', [
          '@slot-number' => $custom_var['slot']
          ]));
      }
      elseif (!empty($custom_var['name']) && empty($custom_var['value'])) {
        $form_state->setErrorByName("baidu_analytics_custom_var][slots][" . $custom_var['slot'] . "][value", t('The custom variable @slot-number requires a <em>Value</em> if a <em>Name</em> has been provided.', [
          '@slot-number' => $custom_var['slot']
          ]));
      }
    }

    // Trim some text values.
    $form_state->setValue(['baidu_analytics_account'], trim($form_state->getValue(['baidu_analytics_account'])));
    $form_state->setValue(['baidu_analytics_pages'], trim($form_state->getValue(['baidu_analytics_pages'])));
    $form_state->setValue(['baidu_analytics_codesnippet_before'], trim($form_state->getValue(['baidu_analytics_codesnippet_before'])));
    $form_state->setValue(['baidu_analytics_codesnippet_after'], trim($form_state->getValue(['baidu_analytics_codesnippet_after'])));

    // Replace all type of dashes (n-dash, m-dash, minus) with the normal dashes.
    $form_state->setValue(['baidu_analytics_account'], str_replace([
      '–',
      '—',
      '−',
    ], '-', $form_state->getValue(['baidu_analytics_account'])));
    // Ensure the tracker ID contains exactly 32 hexadecimal characters.
    if (!preg_match('/^[a-f0-9]{32}$/', $form_state->getValue(['baidu_analytics_account']))) {
      $form_state->setErrorByName('baidu_analytics_account', t('A valid Baidu Analytics Web Property ID should have exactly 32 lower case hexadecimal characters (only allowed: 0 to 9 and a to f).'));
    }

    // Clear obsolete local cache if cache has been disabled.
    if (!$form_state->getValue(['baidu_analytics_cache']) && $form['advanced']['baidu_analytics_cache']['#default_value']) {
      baidu_analytics_clear_js_cache();
    }

    // This is for the Newbie's who cannot read a text area description.
    $pattern = '#hm\.baidu\.com/hm?\.js#i';
    // The URLs hm.baidu.com/h.js and hm.baidu.com/hm.js should not be found.
    if (preg_match($pattern, $form_state->getValue(['baidu_analytics_codesnippet_before']))) {
      $form_state->setErrorByName('baidu_analytics_codesnippet_before', t('Do not add the tracker code provided by Baidu into the javascript code snippets! This module already builds the tracker code based on your Baidu Analytics account number and settings.'));
    }
    if (preg_match($pattern, $form_state->getValue(['baidu_analytics_codesnippet_after']))) {
      $form_state->setErrorByName('baidu_analytics_codesnippet_after', t('Do not add the tracker code provided by Baidu into the javascript code snippets! This module already builds the tracker code based on your Baidu Analytics account number and settings.'));
    }
    // Any <script %> markup tags should not be found.
    if (preg_match('/(.*)<\/?script(.*)>(.*)/i', $form_state->getValue(['baidu_analytics_codesnippet_before']))) {
      $form_state->setErrorByName('baidu_analytics_codesnippet_before', t('Do not include the &lt;script&gt; tags in the javascript code snippets.'));
    }
    if (preg_match('/(.*)<\/?script(.*)>(.*)/i', $form_state->getValue(['baidu_analytics_codesnippet_after']))) {
      $form_state->setErrorByName('baidu_analytics_codesnippet_after', t('Do not include the &lt;script&gt; tags in the javascript code snippets.'));
    }
  }

}
