<?php

/*
 * Copyright (C) 2017-present, Facebook, Inc.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the License.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

/**
 * @file
 * Contains \Drupal\official_facebook_pixel\Form
 * \OfficialFacebookPixelSettingsForm.
 */

namespace Drupal\official_facebook_pixel\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\official_facebook_pixel\OfficialFacebookPixelConfig;
use Drupal\official_facebook_pixel\OfficialFacebookPixelOptions;

/**
 * Class OfficialFacebookPixelSettingsForm.
 *
 * @package Drupal\official_facebook_pixel\Form
 */
class OfficialFacebookPixelSettingsForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user.
   *
   * @var Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      // Load the service required to construct this class.
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $currentUser, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($config_factory);
    $this->currentUser = $currentUser;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return OfficialFacebookPixelConfig::FORM_ID;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      OfficialFacebookPixelConfig::CONFIG_NAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $optionsObj = OfficialFacebookPixelOptions::getInstance();
    $config = $this->config(OfficialFacebookPixelConfig::CONFIG_NAME);

    $form[OfficialFacebookPixelConfig::FORM_PIXEL_KEY] = [
      '#type' => 'textfield',
      '#title' => $this->t(OfficialFacebookPixelConfig::FORM_PIXEL_TITLE),
      '#description' => $this->t(OfficialFacebookPixelConfig::FORM_PIXEL_DESCRIPTION),
      '#default_value' => $optionsObj->getPixelId(),
      '#maxlength' => 64,
      '#size' => 64
    ];

    // Visibility settings.
    $form['tracking_scope'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Tracking scope'),
      '#attached' => [
        'library' => [
          'official_facebook_pixel/admin',
        ],
      ],
    ];

    // Page specific visibility configurations.
    $account = $this->currentUser;
    $php_access = $account->hasPermission('use PHP for official_facebook_pixel tracking visibility');
    $visibility_request_path_pages = $config->get('visibility.request_path_pages');

    $form['tracking']['page_visibility_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Pages'),
      '#group' => 'tracking_scope',
    ];

    if ($config->get('visibility.request_path_mode') == 2 && !$php_access) {
      $form['tracking']['page_visibility_settings'] = [];
      $form['tracking']['page_visibility_settings']['official_facebook_pixel_visibility_request_path_mode'] = ['#type' => 'value', '#value' => 2];
      $form['tracking']['page_visibility_settings']['official_facebook_pixel_visibility_request_path_pages'] = ['#type' => 'value', '#value' => $visibility_request_path_pages];
    }
    else {
      $page_options = [
        0 => $this->t('Every page except the listed pages'),
        1 => $this->t('The listed pages only'),
      ];
      $description = $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", ['%blog' => '/blog', '%blog-wildcard' => '/blog/*', '%front' => '<front>']);

      if ($this->moduleHandler->moduleExists('php') && $php_access) {
        $page_options[2] = $this->t('Pages on which this PHP code returns <code>TRUE</code> (experts only)');
        $title = $this->t('Pages or PHP code');
        $description .= ' ' . $this->t('If the PHP option is chosen, enter PHP code between %php. Note that executing incorrect PHP code can break your Drupal site.', ['%php' => '<?php ?>']);
      }
      else {
        $title = $this->t('Pages');
      }
      $form['tracking']['page_visibility_settings']['official_facebook_pixel_visibility_request_path_mode'] = [
        '#type' => 'radios',
        '#title' => $this->t('Add tracking to specific pages'),
        '#options' => $page_options,
        '#default_value' => $config->get('visibility.request_path_mode'),
      ];
      $form['tracking']['page_visibility_settings']['official_facebook_pixel_visibility_request_path_pages'] = [
        '#type' => 'textarea',
        '#title' => $title,
        '#title_display' => 'invisible',
        '#default_value' => !empty($visibility_request_path_pages) ? $visibility_request_path_pages : '',
        '#description' => $description,
        '#rows' => 10,
      ];
    }

    // Render the role overview.
    $visibility_user_role_roles = $config->get('visibility.user_role_roles');

    $form['tracking']['role_visibility_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Roles'),
      '#group' => 'tracking_scope',
    ];

    $form['tracking']['role_visibility_settings']['official_facebook_pixel_visibility_user_role_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Add tracking for specific roles'),
      '#options' => [
        0 => $this->t('Add to the selected roles only'),
        1 => $this->t('Add to every role except the selected ones'),
      ],
      '#default_value' => $config->get('visibility.user_role_mode'),
    ];
    $form['tracking']['role_visibility_settings']['official_facebook_pixel_visibility_user_role_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#default_value' => !empty($visibility_user_role_roles) ? $visibility_user_role_roles : [],
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', user_role_names()),
      '#description' => $this->t('If none of the roles are selected, all users will be tracked. If a user has any of the roles checked, that user will be tracked (or excluded, depending on the setting above).'),
    ];

    // Privacy specific configurations.
    $form['tracking']['privacy'] = [
      '#type' => 'details',
      '#title' => $this->t('Privacy'),
      '#group' => 'tracking_scope',
    ];
    $form['tracking']['privacy'][OfficialFacebookPixelConfig::FORM_PII_KEY] = [
      '#type' => 'checkbox',
      '#title' => $this->t(OfficialFacebookPixelConfig::FORM_PII_TITLE),
      '#description' => $this->t(sprintf(
        OfficialFacebookPixelConfig::FORM_PII_DESCRIPTION,
        OfficialFacebookPixelConfig::FORM_PII_DESCRIPTION_LINK)),
      '#default_value' => $optionsObj->getUsePii(),
    ];
    $form['tracking']['privacy']['official_facebook_pixel_privacy_donottrack'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Universal web tracking opt-out'),
      '#description' => $this->t('If enabled, if a user has <a href="@donottrack">Do-Not-Track</a> enabled in the browser, the Facebook Pixel module will not execute the tracking code on your site. Compliance with Do Not Track could be purely voluntary, enforced by industry self-regulation, or mandated by state or federal law. Please accept your visitors privacy. If they have opt-out from tracking and advertising, you should accept their personal decision.', ['@donottrack' => 'https://www.eff.org/issues/do-not-track']),
      '#default_value' => $config->get('privacy.donottrack'),
    ];
    $form['tracking']['privacy']['official_facebook_pixel_fb_optout'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable \'fb-disable\' JavaScript Opt-Out (fbOptout())'),
      '#description' => $this->t('If enabled, for enhanced privacy if Facebook Pixel user opt-out code "<i>window[\'fb-disable\']</i>" is true, the Facebook pixel module will not execute the Facebook Pixel tracking code on your site. Furthermore provides the global JavaScript function "fbOptout()" to set an opt-out cookie if called.'),
      '#default_value' => $config->get('privacy.fb_optout'),
    ];
    $form['tracking']['privacy']['official_facebook_pixel_eu_cookie_compliance'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('EU Cookie Compliance integration'),
      '#description' => $this->t('If enabled, the Facebook Pixel module will not track users as long as we do not have their consent. This option is designed to work with the module <a href="@eu_cookie_compliance">Eu Cookie Compliance</a>. Technically it checks for Drupal.eu_cookie_compliance.hasAgreed(). <strong>Important:</strong> Set "Script scope" to "Header" in the EU Cookie Compliance settings for this to work.', ['@eu_cookie_compliance' => 'https://www.drupal.org/project/eu_cookie_compliance']),
      '#default_value' => $this->moduleHandler->moduleExists('eu_cookie_compliance') ? $config->get('privacy.eu_cookie_compliance') : 0,
      '#disabled' => !$this->moduleHandler->moduleExists('eu_cookie_compliance'),
    ];
    $form['tracking']['privacy']['official_facebook_pixel_disable_noscript_img'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable noscript fallback tracking pixel'),
      '#description' => $this->t('Disable the &lt;noscript&gt; tracking pixel image, which does not respect any of these privacy settings.'),
      '#default_value' => $config->get('privacy.disable_noscript_img'),
    ];

    return parent::buildForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $form_state->setValue('official_facebook_pixel_visibility_request_path_pages', trim($form_state->getValue('official_facebook_pixel_visibility_request_path_pages')));
    $form_state->setValue('official_facebook_pixel_visibility_user_role_roles', array_filter($form_state->getValue('official_facebook_pixel_visibility_user_role_roles')));

    // Verify that every path is prefixed with a slash, but don't check PHP
    // code snippets and do not check for slashes if no paths configured.
    if ($form_state->getValue('official_facebook_pixel_visibility_request_path_mode') != 2 && !empty($form_state->getValue('official_facebook_pixel_visibility_request_path_pages'))) {
      $pages = preg_split('/(\r\n?|\n)/', $form_state->getValue('official_facebook_pixel_visibility_request_path_pages'));
      foreach ($pages as $page) {
        if (strpos($page, '/') !== 0 && $page !== '<front>') {
          $form_state->setErrorByName('official_facebook_pixel_visibility_request_path_pages', $this->t('Path "@page" not prefixed with slash.', ['@page' => $page]));
          // Drupal forms show one error only.
          break;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration
    $this->config(OfficialFacebookPixelConfig::CONFIG_NAME)
      // Set the submitted pixel_id setting
      ->set(OfficialFacebookPixelConfig::FORM_PIXEL_KEY, $form_state->getValue(OfficialFacebookPixelConfig::FORM_PIXEL_KEY))
      ->set(OfficialFacebookPixelConfig::FORM_PII_KEY, $form_state->getValue(OfficialFacebookPixelConfig::FORM_PII_KEY))
      ->set('visibility.request_path_mode', $form_state->getValue('official_facebook_pixel_visibility_request_path_mode'))
      ->set('visibility.request_path_pages', $form_state->getValue('official_facebook_pixel_visibility_request_path_pages'))
      ->set('visibility.user_role_mode', $form_state->getValue('official_facebook_pixel_visibility_user_role_mode'))
      ->set('visibility.user_role_roles', $form_state->getValue('official_facebook_pixel_visibility_user_role_roles'))
      ->set('visibility.user_role_roles', $form_state->getValue('official_facebook_pixel_visibility_user_role_roles'))
      ->set('privacy.donottrack', $form_state->getValue('official_facebook_pixel_privacy_donottrack'))
      ->set('privacy.fb_optout', $form_state->getValue('official_facebook_pixel_fb_optout'))
      ->set('privacy.eu_cookie_compliance', $form_state->getValue('official_facebook_pixel_eu_cookie_compliance'))
      ->set('privacy.disable_noscript_img', $form_state->getValue('official_facebook_pixel_disable_noscript_img'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
