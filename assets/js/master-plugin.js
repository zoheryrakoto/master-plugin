/* Master Plugin – Frontend Scripts */
(function ($) {
    'use strict';

    $(document).ready(function () {

        // Example: AJAX ping on page load (remove or adapt as needed)
        $.post(masterPluginData.ajaxUrl, {
            action: 'master_plugin_action',
            nonce:  masterPluginData.nonce,
        }, function (response) {
            if (response.success) {
                console.log('[Master Plugin]', response.data.message);
            }
        });

    });

}(jQuery));
