/**
 * Collapsible sections. The animation itself is pure CSS (see assets/css/shared/collapse.css);
 * this script only flips a class and keeps aria-expanded in step.
 *
 * Every <section data-collapse="key"> with a [data-collapse-toggle] button inside it is wired up.
 * The open / folded choice is remembered for the browser tab (sessionStorage), so a page that reloads itself
 * after an action (e.g. "Mark Paid" on Fines & Penalties) comes back with the sections as the person left them.
 *
 *   CECollapse.set('key', true|false)   fold / unfold a section from other code
 */
(function () {
    var PREFIX = 'ce-collapse:' + location.pathname + ':';

    function remember(key, folded) {
        try { sessionStorage.setItem(PREFIX + key, folded ? '1' : '0'); } catch (e) { /* storage blocked: fine */ }
    }

    function remembered(key) {
        try { return sessionStorage.getItem(PREFIX + key) === '1'; } catch (e) { return false; }
    }

    function apply(section, folded) {
        section.classList.toggle('is-collapsed', folded);
        var btn = section.querySelector('[data-collapse-toggle]');
        if (btn) btn.setAttribute('aria-expanded', folded ? 'false' : 'true');
    }

    function init(section) {
        var btn = section.querySelector('[data-collapse-toggle]');
        if (!btn) return;

        // Restore the last choice without playing the animation.
        if (remembered(section.dataset.collapse)) {
            section.classList.add('no-anim');
            apply(section, true);
            requestAnimationFrame(function () {
                requestAnimationFrame(function () { section.classList.remove('no-anim'); });
            });
        }

        btn.addEventListener('click', function () {
            var folded = !section.classList.contains('is-collapsed');
            apply(section, folded);
            remember(section.dataset.collapse, folded);
        });
    }

    document.querySelectorAll('[data-collapse]').forEach(init);

    window.CECollapse = {
        set: function (key, folded) {
            var section = document.querySelector('[data-collapse="' + key + '"]');
            if (!section) return;
            apply(section, !!folded);
            remember(key, !!folded);
        }
    };
})();
