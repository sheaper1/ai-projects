(function () {
    function generatePassword(length) {
        var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*';
        var result = '';
        var cryptoObj = window.crypto || window.msCrypto;
        var values;

        if (cryptoObj && cryptoObj.getRandomValues) {
            values = new Uint32Array(length);
            cryptoObj.getRandomValues(values);

            for (var i = 0; i < length; i++) {
                result += chars.charAt(values[i] % chars.length);
            }

            return result;
        }

        for (var j = 0; j < length; j++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }

        return result;
    }

    document.addEventListener('click', function (event) {
        var generateButton = event.target.closest('.df-generate-password');

        if (generateButton) {
            event.preventDefault();
            var passwordField = document.querySelector(generateButton.getAttribute('data-target'));

            if (passwordField) {
                passwordField.type = 'text';
                passwordField.value = generatePassword(16);
                passwordField.focus();
                passwordField.select();
            }

            return;
        }

        var button = event.target.closest('.df-select-media');

        if (!button || !window.wp || !window.wp.media) {
            return;
        }

        event.preventDefault();

        var target = document.querySelector(button.getAttribute('data-target'));
        var libraryType = button.getAttribute('data-library') || '';
        var frame = window.wp.media({
            title: button.textContent.trim(),
            button: { text: button.textContent.trim() },
            library: libraryType ? { type: libraryType } : {},
            multiple: false
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();

            if (target && attachment.url) {
                target.value = attachment.url;
                target.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        frame.open();
    });

    document.addEventListener('change', function (event) {
        if (!event.target.matches('[data-df-select-all]')) {
            return;
        }

        document.querySelectorAll('input[name="df_user_ids[]"]').forEach(function (checkbox) {
            checkbox.checked = event.target.checked;
        });
    });

    document.addEventListener('submit', function (event) {
        if (event.target.id !== 'df-bulk-subscribers') {
            return;
        }

        var action = event.target.querySelector('[name="df_bulk_action"]');

        var message = window.DFSubscriptionsAdmin && window.DFSubscriptionsAdmin.confirmDelete
            ? window.DFSubscriptionsAdmin.confirmDelete
            : 'Delete the selected subscriber accounts permanently?';

        if (action && action.value === 'delete' && !window.confirm(message)) {
            event.preventDefault();
        }
    });
})();
