/* global propstackREAdmin, $ */
(function ($) {
  'use strict';

  // Sync jetzt
  $('#propstack-sync-now, #propstack-sync-full').on('click', function () {
    var $btn    = $(this);
    var force   = $btn.attr('id') === 'propstack-sync-full';
    var $result = $('#propstack-sync-result');

    $btn.prop('disabled', true).text(propstackREAdmin.i18n.syncing);
    $result.text('');

    $.ajax({
      url:  propstackREAdmin.ajaxUrl,
      type: 'POST',
      data: {
        action: 'propstack_re_sync_now',
        nonce:  propstackREAdmin.nonce,
        force:  force ? 1 : 0,
      },
      success: function (response) {
        if (response.success) {
          var r = response.data;
          $result.html(
            '<strong>' + propstackREAdmin.i18n.syncDone + '</strong> — ' +
            'Neu: ' + r.created + ' | Aktualisiert: ' + r.updated +
            ' | Übersprungen: ' + r.skipped + ' | Deaktiviert: ' + r.deactivated +
            ' | Fehler: ' + r.errors
          );
        } else {
          $result.text(propstackREAdmin.i18n.syncFail);
        }
      },
      error: function () {
        $result.text(propstackREAdmin.i18n.syncFail);
      },
      complete: function () {
        $btn.prop('disabled', false);
        if (force) {
          $btn.text('Kompletter Re-Sync');
        } else {
          $btn.text('Jetzt synchronisieren');
        }
      }
    });
  });

  // API testen
  $('#propstack-test-api').on('click', function () {
    var $btn    = $(this);
    var $result = $('#propstack-api-test-result');
    $btn.prop('disabled', true).text('Teste…');
    $result.text('');

    $.ajax({
      url:  propstackREAdmin.ajaxUrl,
      type: 'POST',
      data: {
        action: 'propstack_re_test_api',
        nonce:  propstackREAdmin.nonce,
      },
      success: function (response) {
        if (response.success) {
          $result.css('color', '#00a32a').text('✓ ' + response.data.message);
        } else {
          $result.css('color', '#d63638').text('✗ ' + (response.data.message || 'Verbindung fehlgeschlagen'));
        }
      },
      error: function () {
        $result.css('color', '#d63638').text('✗ Verbindungsfehler');
      },
      complete: function () {
        $btn.prop('disabled', false).text('Verbindung testen');
      }
    });
  });

  // Logs löschen
  $('#propstack-clear-logs').on('click', function () {
    if (!confirm('Alle Logs löschen?')) return;
    var $btn = $(this);
    $btn.prop('disabled', true);
    $.ajax({
      url:  propstackREAdmin.ajaxUrl,
      type: 'POST',
      data: { action: 'propstack_re_clear_logs', nonce: propstackREAdmin.nonce },
      success: function () { location.reload(); },
      complete: function () { $btn.prop('disabled', false); }
    });
  });

}(jQuery));
