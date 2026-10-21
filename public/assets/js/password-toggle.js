/**
 * Adds a "show / hide password" eye button to every password field on the page.
 * Include this script on any page with <input type="password">.
 */
(function () {
    var EYE = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>';
    var EYE_OFF = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20C5 20 1 12 1 12a18.5 18.5 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12A3 3 0 1 1 9.88 9.88"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

    function enhance(input) {
        var wrapper = input.parentElement;
        if (!wrapper.classList.contains('password-wrapper')) {
            wrapper = document.createElement('div');
            wrapper.className = 'password-wrapper';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
        }
        input.style.paddingRight = '2.5rem';

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'password-toggle';
        btn.setAttribute('aria-label', 'Show password');
        btn.setAttribute('aria-pressed', 'false');
        btn.innerHTML = EYE;

        btn.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.innerHTML = show ? EYE_OFF : EYE;
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        });

        wrapper.appendChild(btn);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('input[type="password"]').forEach(enhance);
    });
})();
