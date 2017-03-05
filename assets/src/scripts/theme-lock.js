jQuery(function ($) {
	function lockTheme() {
		$.ajax({
			beforeSend: function () {
				$('.spinner').addClass('is-active');
			},
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'pb_lock_theme',
				_ajax_nonce: PB_ThemeLockToken.lockNonce
			},
			cache: false,
			dataType: 'html',
			error: function (obj, status, thrown) {
				// Theme has not been successfully locked.
			},
			success: function () {
				// Theme has been successfully locked.
				$('.lock').text('Unlock Theme');
				$('.lock').addClass('unlock');
				$('.unlock').removeClass('lock');
			},
			complete: function () {
				$('.spinner').removeClass('is-active');
			}
		});
	}

	function unlockTheme() {
		$.ajax({
			beforeSend: function () {
				$('.spinner').addClass('is-active');
			},
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'pb_unlock_theme',
				_ajax_nonce: PB_ThemeLockToken.unlockNonce
			},
			cache: false,
			dataType: 'html',
			error: function (obj, status, thrown) {
				// Theme has not been successfully unlocked.
			},
			success: function () {
				// Theme has been successfully unlocked.
				$('.unlock').text('Lock Theme');
				$('.unlock').addClass('lock');
				$('.lock').removeClass('unlock');
			},
			complete: function () {
				$('.spinner').removeClass('is-active');
			}
		});
	}

	$(document).on('click', '.lock', function() {
		lockTheme();
	});

	$(document).on('click', '.unlock', function() {
		unlockTheme();
	});
});
