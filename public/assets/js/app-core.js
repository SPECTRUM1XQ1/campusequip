/*
 * Loaded first on every page (see includes/html-head.php and the login pages).
 *  1. window.APP_BASE  - the app's base path, from <meta name="app-base"> (set once in config.php).
 *  2. CSRF            - every same-origin POST/PUT/PATCH/DELETE made with fetch() gets the
 *                       session's token in an X-CSRF-Token header, so pages need no changes.
 */
(function () {
  function meta(name) {
    var m = document.querySelector('meta[name="' + name + '"]');
    return m ? m.getAttribute('content') : '';
  }

  window.APP_BASE = meta('app-base');

  /*
   * Change a button's text without destroying its icon. Buttons are built as
   *   <button><i class="ph ph-..."></i><span class="btn-text">Save</span></button>
   * so use  setBtnText(btn, 'Saving...')  instead of  btn.textContent = 'Saving...'.
   */
  window.setBtnText = function (btn, text) {
    var label = btn.querySelector('.btn-label, .btn-text');
    (label || btn).textContent = text;
  };
  /*
   * POST a FormData to an API endpoint and return the parsed JSON ({success, message, ...}).
   * Shared by the page scripts in assets/js/pages/ so each one doesn't redefine it.
   */
  window.postForm = async function (url, formData) {
    var res = await fetch(url, { method: 'POST', body: formData });
    return res.json();
  };

  var token = meta('csrf-token');
  var nativeFetch = window.fetch;

  window.fetch = function (input, init) {
    init = init || {};
    var method = String(init.method || (input && input.method) || 'GET').toUpperCase();

    if (token && method !== 'GET' && method !== 'HEAD') {
      var url = typeof input === 'string' ? input : (input && input.url) || '';
      var sameOrigin = true;
      try { sameOrigin = new URL(url, window.location.href).origin === window.location.origin; } catch (e) {}

      if (sameOrigin) {
        var headers = new Headers(init.headers || (input && input.headers) || {});
        if (!headers.has('X-CSRF-Token')) headers.set('X-CSRF-Token', token);
        init.headers = headers;
      }
    }
    return nativeFetch.call(this, input, init);
  };
})();
