/**
 * @file
 * official_facebook_pixel.js
 *
 * Defines the behavior of the official_facebook_pixel.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.official_facebook_pixel = (typeof Drupal.official_facebook_pixel !== "undefined") ? Drupal.official_facebook_pixel : {};

  Drupal.official_facebook_pixel.init = function () {
    !function (f, b, e, v, n, t, s) {
      if (f.fbq) return; n = f.fbq = function () {
        n.callMethod ?
        n.callMethod.apply(n, arguments) : n.queue.push(arguments)
      }; if (!f._fbq) f._fbq = n;
      n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = []; t = b.createElement(e); t.async = !0;
      t.src = v; s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s)
    }(window,
      document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
  };

  Drupal.official_facebook_pixel.executeEvent = function (eventArray) {
    var cmd = eventArray[0];
    var event = eventArray[1];
    var paramsObj = eventArray[2];
    if (typeof paramsObj === "undefined") {
      fbq(cmd, event);
    } else {
      fbq(cmd, event, JSON.stringify(paramsObj));
    }
  };

  Drupal.behaviors.official_facebook_pixel = {
    attach: function (context) {
      $('body').once('official-facebook-pixel').each(function () {

        // Drupal.official_facebook_pixel.disabled allows other modules to set the functionality disabled.
        Drupal.official_facebook_pixel.disabled = Drupal.official_facebook_pixel.disabled || false;

        // If configured, check JSON callback to determine if in EU.
        if (drupalSettings.official_facebook_pixel.pixel_id > 0 && !Drupal.official_facebook_pixel.disabled) {
          var pixel_id = drupalSettings.official_facebook_pixel.pixel_id;
          var privacy_donottrack = drupalSettings.official_facebook_pixel.privacy_donottrack;
          var privacy_fb_optout = drupalSettings.official_facebook_pixel.privacy_fb_optout;
          var privacy_fb_optout_key = drupalSettings.official_facebook_pixel.privacy_fb_optout_key;
          var privacy_eu_cookie_compliance = drupalSettings.official_facebook_pixel.privacy_eu_cookie_compliance;
          var events = drupalSettings.official_facebook_pixel.events;

          // Define Drupal.official_facebook_pixel.disabled as a dynamic condition to
          // disable FB Pixel at runtime.
          // This is helpful for GDPR compliance module integration
          // and works even with static caching mechanisms like boost module.

          // Define Opt-out conditions check
          if (privacy_fb_optout) {
            // Facebook Pixel Opt-Out
            // Define global fbOptout() function to set opt-out.
            fbOptout = function (reload = false) {
              reload = (typeof reload !== 'undefined') ? reload : false;
              document.cookie = privacy_fb_optout_key + '=true; expires=Thu, 31 Dec 2999 23:59:59 UTC; path=/';
              window[privacy_fb_optout_key] = true;
              console.log(Drupal.t('Opted-out from offical_facebook_pixel by fbOptout()'));
              if (reload) {
                location.reload();
              }
            };
            // Check if opt-out cookie was already set:
            if (document.cookie.indexOf(privacy_fb_optout_key + '=true') !== -1) {
              window[privacy_fb_optout_key] = true;
              console.log(Drupal.t('Opted-out from offical_facebook_pixel by cookie: "' + privacy_fb_optout_key + '"'));
            }
            Drupal.official_facebook_pixel.disabled = Drupal.official_facebook_pixel.disabled || window[privacy_fb_optout_key] == true;
          }

          // Define eu_cookie_compliance conditions check (https://www.drupal.org/project/eu_cookie_compliance)
          if (privacy_eu_cookie_compliance) {
            if (typeof Drupal.eu_cookie_compliance === "undefined") { console.warn("official_facebook_pixel: official_facebook_pixel eu_cookie_compliance integration option is enabled, but eu_cookie_compliance javascripts seem to be loaded after official_facebook_pixel, which may break functionality."); }
            var eccHasAgreed = (typeof Drupal.eu_cookie_compliance !== "undefined" && Drupal.eu_cookie_compliance.hasAgreed());
            Drupal.official_facebook_pixel.disabled = Drupal.official_facebook_pixel.disabled || !eccHasAgreed;
          }

          // Define Do-not-track conditions check (see https://www.w3.org/TR/tracking-dnt/)
          if (privacy_donottrack) {
            var DNT = (typeof navigator.doNotTrack !== "undefined" && (navigator.doNotTrack === "yes" || navigator.doNotTrack == 1)) || (typeof navigator.msDoNotTrack !== "undefined" && navigator.msDoNotTrack == 1) || (typeof window.doNotTrack !== "undefined" && window.doNotTrack == 1);
            // If eccHasAgreed is true, it overrides DNT because eu_cookie_compliance contains a setting for opt-in with DNT:
            // "Automatic. Respect the DNT (Do not track) setting in the browser, if present. Uses opt-in when DNT is 1 or not set, and consent by default when DNT is 0."
            Drupal.official_facebook_pixel.disabled = Drupal.official_facebook_pixel.disabled || (DNT && (typeof eccHasAgreed == "undefined" || !eccHasAgreed));
          }

          if (!Drupal.official_facebook_pixel.disabled) {
            // Initialize:
            Drupal.official_facebook_pixel.init();
            // Run events:
            events.forEach(function (item, index) {
              Drupal.official_facebook_pixel.executeEvent(item);
            });
          }
        }
      });
    },
  };
})(jQuery, Drupal, drupalSettings);
