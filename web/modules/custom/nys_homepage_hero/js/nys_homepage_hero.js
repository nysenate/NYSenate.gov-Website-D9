(function ($, Drupal, drupalSettings) {

    /**
     * @namespace
     */
    Drupal.behaviors.reloadHomepageHero = {
        attach: function (context, settings) {
            var active = drupalSettings.nys_homepage_hero.session_active;
            var interval = drupalSettings.nys_homepage_hero.poll_int;
            var intervalID = window.setInterval(compareCallback, interval);

            /**
             * Reloads the page when a session starts or ends.
             */
            function compareCallback()
            {
                $.ajax(
                    {
                        url: '/session/token',
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        success: function (data, status, xhr) {
                              sessionToken = data;
                            $.ajax(
                                {
                                    url: '/ajax/homepage-hero-status',
                                    method: 'GET',
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-CSRF-Token': sessionToken
                                    },
                                    success: function (data, status, xhr) {
                                        // Begin accessing JSON data here
                                        var session_in_progress = JSON.parse(data);
                                        // When a session is started or ended reload the page to add
                                        // or remove the video stream embed respectively.
                                        if (active ^ session_in_progress) {
                                            window.location.reload(true);
                                        }
                                    }
                                  }
                            );
                        }
                    }
                );
            }
        }
    }
})(jQuery, Drupal, drupalSettings);
