/**
 * @file
 * Official Facebook Pixel admin behaviors.
 */

(function ($) {

  'use strict';

  /**
   * Provide the summary information for the tracking settings vertical tabs.
   */
  Drupal.behaviors.trackingSettingsSummary = {
    attach: function () {
      // Make sure this behavior is processed only if drupalSetSummary is defined.
      if (typeof jQuery.fn.drupalSetSummary === 'undefined') {
        return;
      }
      $('#edit-page-visibility-settings').drupalSetSummary(function (context) {
        var $radio = $('input[name="official_facebook_pixel_visibility_request_path_mode"]:checked', context);
        if ($radio.val() === '0') {
          if (!$('textarea[name="official_facebook_pixel_visibility_request_path_pages"]', context).val()) {
            return Drupal.t('Not restricted');
          }
          else {
            return Drupal.t('All pages with exceptions');
          }
        }
        else {
          return Drupal.t('Restricted to certain pages');
        }
      });

      $('#edit-role-visibility-settings').drupalSetSummary(function (context) {
        var vals = [];
        $('input[type="checkbox"]:checked', context).each(function () {
          vals.push($.trim($(this).next('label').text()));
        });
        if (!vals.length) {
          return Drupal.t('Not restricted');
        }
        else if ($('input[name="official_facebook_pixel_visibility_user_role_mode"]:checked', context).val() === '1') {
          return Drupal.t('Excepted: @roles', {'@roles': vals.join(', ')});
        }
        else {
          return vals.join(', ');
        }
      });

      $('#edit-privacy').drupalSetSummary(function (context) {
        var vals = [];
        if ($('input#edit-official-acebook-pixel-privacy-donottrack', context).is(':checked')) {
          vals.push(Drupal.t('Use Advanced Matching on pixel'));
        }
        if ($('input#edit-official-facebook-pixel-privacy-donottrack', context).is(':checked')) {
          vals.push(Drupal.t('Universal web tracking opt-out'));
        }
        if ($('input#edit-official-facebook-pixel-fb-optout', context).is(':checked')) {
          vals.push(Drupal.t('Advanced fb-disable user opt-out'));
        }
        if ($('input#edit-official-facebook-pixel-eu-cookie-compliance', context).is(':checked')) {
          vals.push(Drupal.t('EU Cookie Compliance integration'));
        }
        if ($('input#edit-official-facebook-pixel-disable-noscript-img', context).is(':checked')) {
          vals.push(Drupal.t('Disable noscript fallback tracking pixel'));
        }
        if (!vals.length) {
          return Drupal.t('No privacy');
        }
        return Drupal.t('@items enabled', {'@items': vals.join(', ')});
      });
    }
  };

})(jQuery);
