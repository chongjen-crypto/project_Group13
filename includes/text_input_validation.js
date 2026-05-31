(function (global) {
    'use strict';

    var MAX_CHARS = 50;
    var BAD_WORDS = [
        'damn', 'hell', 'shit', 'fuck', 'fucking', 'bitch', 'bastard', 'asshole',
        'crap', 'piss', 'dick', 'cock', 'pussy', 'whore', 'slut', 'idiot', 'stupid',
        'moron', 'retard', 'retarded', 'nigger', 'faggot', 'cunt', 'bollocks', 'wanker',
        'suck', 'noob', 'cibai', 'pundek', 'babi', 'masturbate'
    ];

    function countChars(text) {
        return (text || '').trim().length;
    }

    function containsBadWords(text) {
        var t = (text || '').toLowerCase().trim();
        if (!t) return false;
        for (var i = 0; i < BAD_WORDS.length; i++) {
            var word = BAD_WORDS[i];
            var re = new RegExp('\\b' + word.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\b', 'i');
            if (re.test(t)) return true;
        }
        return false;
    }

    function validateText(text, required, maxChars) {
        var limit = maxChars || MAX_CHARS;
        var t = (text || '').trim();
        var chars = countChars(t);

        if (required && !t) {
            return { ok: false, error: 'This field is required.', charCount: 0 };
        }
        if (t && chars > limit) {
            return { ok: false, error: 'Maximum ' + limit + ' characters allowed (you entered ' + chars + ').', charCount: chars };
        }
        if (t && containsBadWords(t)) {
            return { ok: false, error: 'Inappropriate language is not allowed. Please remove offensive words.', charCount: chars };
        }
        return { ok: true, error: '', charCount: chars };
    }

    function showFieldError(inputEl, errorEl, message) {
        if (errorEl) {
            errorEl.textContent = message || '';
            errorEl.classList.toggle('d-none', !message);
        }
        if (inputEl) {
            inputEl.classList.toggle('is-invalid', !!message);
        }
    }

    function bindLimitedTextInput(inputEl, errorEl, options) {
        if (!inputEl) return;
        var opts = options || {};
        var maxChars = opts.maxChars || MAX_CHARS;
        var required = !!opts.required;

        function refresh() {
            var result = validateText(inputEl.value, false, maxChars);
            var counter = opts.counterEl;
            if (counter) {
                counter.textContent = result.charCount + ' / ' + maxChars + ' characters';
                counter.classList.toggle('text-danger', result.charCount > maxChars);
            }
            if (opts.liveBadWords !== false && inputEl.value.trim() && containsBadWords(inputEl.value)) {
                showFieldError(inputEl, errorEl, 'Inappropriate language is not allowed.');
            } else if (result.charCount > maxChars) {
                showFieldError(inputEl, errorEl, result.error);
            } else {
                showFieldError(inputEl, errorEl, '');
            }
        }

        inputEl.addEventListener('input', refresh);
        refresh();

        return {
            validate: function () {
                var result = validateText(inputEl.value, required, maxChars);
                showFieldError(inputEl, errorEl, result.ok ? '' : result.error);
                return result;
            }
        };
    }

    global.TextInputValidation = {
        MAX_CHARS: MAX_CHARS,
        countChars: countChars,
        containsBadWords: containsBadWords,
        validateText: validateText,
        showFieldError: showFieldError,
        bindLimitedTextInput: bindLimitedTextInput
    };
})(typeof window !== 'undefined' ? window : this);
