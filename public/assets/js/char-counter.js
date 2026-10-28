/**
 * Field limit indicators.
 *   - Every text box / textarea with a maxlength gets a live "42 / 150" counter under it.
 *     It turns amber near the limit and red at the limit.
 *   - Number boxes with a max get a small "Max 65,535" hint.
 * Works on fields that are filled by script too (edit modals): the value setter is wrapped, and form resets are handled.
 * Skip a field with data-no-counter. Password fields and the sign-in form are skipped on purpose.
 */
(function () {
    'use strict';

    var TEXT_TYPES = ['text', 'email', 'tel', 'search', 'url'];
    var WARN_AT = 0.9;

    function eligible(el) {
        if (el.hasAttribute('data-no-counter') || el.dataset.counterReady) return false;
        if (el.closest('#login-form')) return false;
        if (el.tagName === 'TEXTAREA') return el.maxLength > 0;
        if (el.tagName !== 'INPUT') return false;
        return TEXT_TYPES.indexOf(el.type) !== -1 && el.maxLength > 0;
    }

    // Counter goes right under the field; inside a flex/grid row it goes under the whole row instead.
    function anchorFor(el) {
        var parent = el.parentElement;
        var d = parent ? getComputedStyle(parent).display : '';
        return (d === 'flex' || d === 'inline-flex' || d === 'grid') ? parent : el;
    }

    function attachCounter(el) {
        el.dataset.counterReady = '1';
        var note = document.createElement('small');
        note.className = 'char-counter';
        note.setAttribute('aria-live', 'off');
        anchorFor(el).insertAdjacentElement('afterend', note);

        function update() {
            var len = el.value.length, max = el.maxLength;
            note.textContent = len + ' / ' + max;
            note.classList.toggle('is-near', len >= max * WARN_AT && len < max);
            note.classList.toggle('is-full', len >= max);
        }

        el.addEventListener('input', update);
        el.addEventListener('focus', update);
        if (el.form) el.form.addEventListener('reset', function () { setTimeout(update, 0); });

        // Edit modals fill fields with el.value = "..." (no input event), so refresh on that too.
        var proto = Object.getPrototypeOf(el);
        var desc = Object.getOwnPropertyDescriptor(proto, 'value');
        if (desc && desc.configurable !== false) {
            Object.defineProperty(el, 'value', {
                configurable: true,
                get: function () { return desc.get.call(this); },
                set: function (v) { desc.set.call(this, v); update(); }
            });
        }
        update();
    }

    function attachMaxHint(el) {
        if (el.type !== 'number' || el.max === '' || el.dataset.counterReady) return;
        if (el.hasAttribute('data-no-counter') || el.closest('.day-stepper')) return;
        var next = el.nextElementSibling;
        if (next && (next.tagName === 'SMALL' || next.classList.contains('field-hint'))) return;  // already explained
        el.dataset.counterReady = '1';
        var max = Number(el.max);
        var note = document.createElement('small');
        note.className = 'char-counter char-counter--hint';
        note.textContent = 'Max ' + max.toLocaleString('en-US');
        el.insertAdjacentElement('afterend', note);
    }

    function init(root) {
        (root || document).querySelectorAll('input, textarea').forEach(function (el) {
            if (eligible(el)) attachCounter(el);
            else attachMaxHint(el);
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { init(); });
    else init();

    window.initCharCounters = init;   // call after injecting new fields into the page
})();
