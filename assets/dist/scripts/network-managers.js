/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!************************************************!*\
  !*** ./assets/src/scripts/network-managers.js ***!
  \************************************************/
/* global PB_NetworkManagerToken */
jQuery(function ($) {
  $(document).ready(function () {
    $('div.row-actions .restrict a, div.row-actions .unrestrict a').on('click', function () {
      var link = $(this);
      var parent = link.parent('span');
      var parent_row = parent.parent('div').parent('td').parent('tr');
      var admin_id = parent_row.attr('id');
      var restrict_string = link.attr('data-restrict-text');
      var unrestrict_string = link.attr('data-unrestrict-text');
      var change_status_to = link.attr('data-restrict');
      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'pb_update_admin_status',
          admin_id: admin_id,
          status: change_status_to,
          _ajax_nonce: PB_NetworkManagerToken.networkManagerNonce
        },

        /**
         *
         */
        success: function success() {
          parent_row.toggleClass('restricted');

          if (change_status_to === '0') {
            parent.removeClass('unrestrict').addClass('restrict');
            link.attr('data-restrict', '1');
            link.text(restrict_string);
          } else if (change_status_to === '1') {
            parent.removeClass('restrict').addClass('unrestrict');
            link.attr('data-restrict', '0');
            link.text(unrestrict_string);
          }
        },

        /**
         * @param jqXHR
         * @param textStatus
         * @param errorThrown
         */
        error: function error(jqXHR, textStatus, errorThrown) {
          alert(jqXHR + ' :: ' + textStatus + ' :: ' + errorThrown);
        }
      });
    });
  });
});
/******/ })()
;