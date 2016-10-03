// reference — http://jqueryui.com/tabs/
jQuery(document).ready(function ($) {
    $('#tabs').tabs({
        collapsible: true,
        active: false,
        classes: {
            'ui-tabs-tab': 'ui-corner-top',
            'ui-tabs-panel': 'ui-corner-bottom'
        }
    });

    $('.ui-tabs-nav').removeClass('ui-corner-all');

});
