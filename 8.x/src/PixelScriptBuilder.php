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
 * Contains \Drupal\official_facebook_pixel\PixelScriptBuilder.
 */

namespace Drupal\official_facebook_pixel;

use Drupal\Component\Serialization\Json;

/**
 * Pixel object
 */
class PixelScriptBuilder
{
  const ADDPAYMENTINFO = 'AddPaymentInfo';
  const ADDTOCART = 'AddToCart';
  const ADDTOWISHLIST = 'AddToWishlist';
  const COMPLETEREGISTRATION = 'CompleteRegistration';
  const CONTACT = 'Contact';
  const CUSTOMIZEPRODUCT = 'CustomizeProduct';
  const DONATE = 'Donate';
  const FINDLOCATION = 'FindLocation';
  const INITIATECHECKOUT = 'InitiateCheckout';
  const LEAD = 'Lead';
  const PAGEVIEW = 'PageView';
  const PURCHASE = 'Purchase';
  const SCHEDULE = 'Schedule';
  const SEARCH = 'Search';
  const STARTTRIAL = 'StartTrial';
  const SUBMITAPPLICATION = 'SubmitApplication';
  const SUBSCRIBE = 'Subscribe';
  const VIEWCONTENT = 'ViewContent';

  const FB_INTEGRATION_TRACKING_KEY = 'fb_integration_tracking';

  private static $pixelId = '';

  private static $pixelNoscriptCode = "<img height=\"1\" width=\"1\" style=\"display:none\" alt=\"fbpx\" src=\"https://www.facebook.com/tr?id=%s&ev=%s%s&noscript=1\" />";

  public static function initialize($pixel_id = '') {
    self::$pixelId = $pixel_id;
  }

  /**
   * Gets FB pixel ID
   */
  public static function getPixelId() {
    return self::$pixelId;
  }

  /**
   * Sets FB pixel ID
   */
  public static function setPixelId($pixel_id) {
    self::$pixelId = $pixel_id;
  }

  /**
   * Returns the prepared pixel event array from the given parameters
   * ready to be processed by JS.
   *
   * @param [type] $event
   * @param array $params
   * @param string $tracking_name
   * @return void
   */
  public static function getPixelEventArray($event, array $params = [], $tracking_name = '') {
    if (empty(self::$pixelId)) {
      return;
    }
    if (!empty($tracking_name)) {
      $params[self::FB_INTEGRATION_TRACKING_KEY] = $tracking_name;
    }
    $class = new \ReflectionClass(__CLASS__);
    $eventArray = [
      $class->getConstant(strtoupper($event)) !== false ? 'track' : 'trackCustom',
      $event
    ];
    if (!empty($params)) {
      $eventArray[] = Json::encode($params);
    }
    return $eventArray;
  }

  /**
   * Gets FB pixel AddToCart EventArray
   */
  public static function getPixelAddToCartEventArray($params = array(), $tracking_name = '') {
    return self::getPixelEventArray(
      self::ADDTOCART,
      $params,
      $tracking_name
    );
  }

  /**
   * Gets FB pixel InitiateCheckout EventArray
   */
  public static function getPixelInitiateCheckoutEventArray($params = array(), $tracking_name = '') {
    return self::getPixelEventArray(
      self::INITIATECHECKOUT,
      $params,
      $tracking_name
    );
  }

  /**
   * Gets FB pixel Lead EventArray
   */
  public static function getPixelLeadEventArray($params = array(), $tracking_name = '') {
    return self::getPixelEventArray(
      self::LEAD,
      $params,
      $tracking_name
    );
  }

  /**
   * Gets FB pixel PageView EventArray
   */
  public static function getPixelPageViewEventArray($params = array(), $tracking_name = '') {
    return self::getPixelEventArray(
      self::PAGEVIEW,
      $params,
      $tracking_name
    );
  }

  /**
   * Gets FB pixel Purchase EventArray
   */
  public static function getPixelPurchaseEventArray($params = array(), $tracking_name = '') {
    return self::getPixelEventArray(
      self::PURCHASE,
      $params,
      $tracking_name
    );
  }

  /**
   * Gets FB pixel ViewContent EventArray
   */
  public static function getPixelViewContentEventArray($params = array(), $tracking_name = '') {
    return self::getPixelEventArray(
      self::VIEWCONTENT,
      $params,
      $tracking_name
    );
  }

  /**
   * Gets FB pixel noscript EventArray
   */
  public static function getPixelNoscriptCode($event = 'PageView', $cd = array(), $tracking_name = '') {
    if (empty(self::$pixelId)) {
      return;
    }

    $data = '';
    foreach ($cd as $k => $v) {
      $data .= '&cd[' . $k . ']=' . $v;
    }
    if (!empty($tracking_name)) {
      $data .= '&cd[' . self::FB_INTEGRATION_TRACKING_KEY . ']=' . $tracking_name;
    }
    return sprintf(
      self::$pixelNoscriptCode,
      self::$pixelId,
      $event,
      $data
    );
  }
}
