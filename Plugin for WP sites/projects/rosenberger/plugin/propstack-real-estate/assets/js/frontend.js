/* global propstackRE */
(function ($) {
  'use strict';

  // -----------------------------------------------------------------------
  // Gallery
  // -----------------------------------------------------------------------
  $(document).on('click', '.propstack-gallery__thumb', function () {
    var $thumb   = $(this);
    var $gallery = $thumb.closest('.propstack-gallery');
    var propId   = $gallery.find('[id^="propstack-gallery-data-"]').attr('id').replace('propstack-gallery-data-', '');
    var dataEl   = document.getElementById('propstack-gallery-data-' + propId);
    if (!dataEl) return;

    var images = JSON.parse(dataEl.textContent);
    var idx    = parseInt($thumb.data('index'), 10);
    var $main  = $gallery.find('#propstack-gallery-main-' + propId);

    $main.attr({ src: images[idx].url, alt: images[idx].alt });
    $gallery.find('.propstack-gallery__thumb').removeClass('propstack-gallery__thumb--active');
    $thumb.addClass('propstack-gallery__thumb--active');
    $gallery.find('.propstack-gallery__current').text(idx + 1);
    $gallery.data('current', idx);
  });

  $(document).on('click', '.propstack-gallery__nav--next', function () {
    navigateGallery($(this).closest('.propstack-gallery'), 1);
  });
  $(document).on('click', '.propstack-gallery__nav--prev', function () {
    navigateGallery($(this).closest('.propstack-gallery'), -1);
  });

  function navigateGallery($gallery, dir) {
    var propId  = $gallery.find('[id^="propstack-gallery-data-"]').attr('id').replace('propstack-gallery-data-', '');
    var dataEl  = document.getElementById('propstack-gallery-data-' + propId);
    if (!dataEl) return;
    var images  = JSON.parse(dataEl.textContent);
    var current = parseInt($gallery.data('current') || 0, 10);
    var next    = (current + dir + images.length) % images.length;
    $gallery.find('.propstack-gallery__thumb[data-index="' + next + '"]').trigger('click');
  }

  // -----------------------------------------------------------------------
  // Contact Form
  // -----------------------------------------------------------------------
  $(document).on('submit', '.propstack-contact-form__form', function (e) {
    e.preventDefault();
    var $form    = $(this);
    var $btn     = $form.find('.propstack-btn--submit');
    var $feedback= $form.find('.propstack-form__feedback');

    $btn.prop('disabled', true);
    $btn.find('.propstack-btn__text').attr('hidden', true);
    $btn.find('.propstack-btn__loading').removeAttr('hidden');
    $feedback.hide().removeClass('success error').text('');
    $form.find('.propstack-form__error').text('');
    $form.find('.propstack-form__group').removeClass('has-error');

    var data = $form.serialize() + '&action=propstack_re_submit&nonce=' + propstackRE.nonce;

    $.ajax({
      url:    propstackRE.ajaxUrl,
      type:   'POST',
      data:   data,
      success: function (response) {
        if (response.success) {
          $feedback.addClass('success').text(response.data.message).show();
          $form[0].reset();
          if (response.data.redirect) {
            setTimeout(function () {
              window.location.href = response.data.redirect;
            }, 800);
          }
        } else {
          $feedback.addClass('error').text(response.data.message).show();
          // Feldspezifische Fehler
          if (response.data.errors) {
            $.each(response.data.errors, function (field, msg) {
              var $group = $form.find('[name="' + field + '"]').closest('.propstack-form__group');
              $group.addClass('has-error').find('.propstack-form__error').text(msg);
            });
          }
        }
      },
      error: function () {
        $feedback.addClass('error').text('Ein Fehler ist aufgetreten. Bitte erneut versuchen.').show();
      },
      complete: function () {
        $btn.prop('disabled', false);
        $btn.find('.propstack-btn__text').removeAttr('hidden');
        $btn.find('.propstack-btn__loading').attr('hidden', true);
      }
    });
  });

}(jQuery));
