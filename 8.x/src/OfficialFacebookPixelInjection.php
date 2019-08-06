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
 * Contains \Drupal\official_facebook_pixel
 * \OfficialFacebookPixelInjection.
 */

namespace Drupal\official_facebook_pixel;

use Drupal\official_facebook_pixel\OfficialFacebookPixelConfig;
use Drupal\official_facebook_pixel\OfficialFacebookPixelOptions;
use Drupal\Component\Serialization\Json;
use Drupal\official_facebook_pixel\Component\Render\JavaScriptSnippet;

/**
 * Class OfficialFacebookPixelInjection.
 *
 * @package Drupal\official_facebook_pixel
 */
class OfficialFacebookPixelInjection
{
  public static function injectPixelCode(array &$page)
  {
    $options = OfficialFacebookPixelOptions::getInstance();
    PixelScriptBuilder::initialize($options->getPixelId());

    self::injectScriptCode($page, $options);

    foreach (OfficialFacebookPixelConfig::integrationConfigFor8() as $key => $value) {
      $class_name = 'Drupal\\official_facebook_pixel\\integration\\'.$value;
      $class_name::injectPixelCode($page);
    }
  }

  public static function injectScriptCode(array &$page, $options)
  {
    $options = OfficialFacebookPixelOptions::getInstance();
    $config = \Drupal::config(OfficialFacebookPixelConfig::CONFIG_NAME);

    // Provide a list of events to run
    $eventsArray = [
      ['init', $options->getPixelId(), $options->getUserInfo(), ['agent' => $options->getAgentString()]]
    ];

    // Add page view by default:
    $eventsArray[] = PixelScriptBuilder::getPixelPageViewEventArray();
    $jsSettings = [
      'pixel_id' => $options->getPixelId(),
      'privacy_donottrack' => $config->get('privacy.donottrack'),
      'privacy_fb_optout' => $config->get('privacy.fb_optout'),
      'privacy_fb_optout_key' => $config->get('privacy.fb_optout_key'),
      'privacy_eu_cookie_compliance' => $config->get('privacy.eu_cookie_compliance') && \Drupal::service('module_handler')->moduleExists('eu_cookie_compliance'),
      'events' => $eventsArray,
    ];

    $page['#attached']['drupalSettings']['official_facebook_pixel'] = $jsSettings;
    $page['#attached']['library'][] = 'official_facebook_pixel/official_facebook_pixel';
  }

  public static function injectNoScriptCode(array &$page)
  {
    $config = \Drupal::config(OfficialFacebookPixelConfig::CONFIG_NAME);
    if ($config->get('privacy.disable_noscript_img')) {
      // Do no add the script img fallback if it is disabled due to privacy settings.
      return;
    }

    // Inject inline noscript code to head
    $pixel_noscript_code = PixelScriptBuilder::getPixelNoscriptCode();
    $page['official_facebook_pixel_noscript_code'] = [
      '#type' => 'html_tag',
      '#tag' => 'noscript',
      '#value' => new JavaScriptSnippet($pixel_noscript_code)
    ];
  }
}
