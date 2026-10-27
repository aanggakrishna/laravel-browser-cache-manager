(function () {
  'use strict';

  var script = document.currentScript;
  if (!script) return;
  var endpoint = script.dataset.endpoint;
  var recoveryEndpoint = script.dataset.recoveryEndpoint;
  if (!endpoint || !window.fetch) return;

  var versionKey = 'browser-cache-manager.version';
  var reloadKey = 'browser-cache-manager.reloaded-version';
  var etag = '';
  var lastState = null;
  var widget = null;
  var menu = null;
  var toggle = null;
  var message = null;
  var action = null;

  function safeStorage(storage, method, key, value) {
    try {
      if (method === 'get') return storage.getItem(key);
      storage.setItem(key, value);
      return null;
    } catch (_) {
      return null;
    }
  }

  function addStyles() {
    if (document.getElementById('bcm-widget-style')) return;
    var style = document.createElement('style');
    style.id = 'bcm-widget-style';
    style.textContent = [
      '.bcm-widget{position:fixed;z-index:2147483000;font:14px/1.45 system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#111827}',
      '.bcm-widget[hidden],.bcm-menu[hidden]{display:none!important}',
      '.bcm-toggle{width:48px;height:48px;border:0;border-radius:50%;background:#1f2937;color:#fff;display:grid;place-items:center;cursor:pointer;box-shadow:0 3px 12px #0003;transition:background-color .15s ease,transform .15s ease}',
      '.bcm-toggle:hover{background:#111827;transform:translateY(-1px)}',
      '.bcm-toggle:focus-visible,.bcm-action:focus-visible{outline:3px solid #2563eb;outline-offset:3px}',
      '.bcm-menu{position:absolute;width:min(300px,calc(100vw - 32px));padding:14px;background:#fff;border:1px solid #d1d5db;border-radius:12px;box-shadow:0 10px 32px #0003}',
      '.bcm-menu-title{margin:0 0 6px;font-size:15px;font-weight:650;color:#111827}',
      '.bcm-menu-copy{margin:0 0 12px;color:#4b5563;font-size:13px}',
      '.bcm-action{width:100%;min-height:44px;border:0;border-radius:8px;padding:10px 12px;background:#1d4ed8;color:#fff;font:inherit;font-weight:600;text-align:left;cursor:pointer}',
      '.bcm-action:hover{background:#1e40af}.bcm-action:disabled{opacity:.65;cursor:wait}',
      '.bcm-message{min-height:1.4em;margin:9px 0 0;color:#374151;font-size:12px}',
      '@media(prefers-reduced-motion:reduce){.bcm-toggle{transition:none}}'
    ].join('');
    document.head.appendChild(style);
  }

  function placeWidget(position, offsetX, offsetY) {
    var pos = ['bottom-right', 'bottom-left', 'top-right', 'top-left'].indexOf(position) >= 0 ? position : 'bottom-right';
    var top = pos.indexOf('top-') === 0;
    var left = pos.indexOf('-left') > -1;
    widget.style.top = top ? 'calc(' + offsetY + 'px + env(safe-area-inset-top, 0px))' : 'auto';
    widget.style.bottom = top ? 'auto' : 'calc(' + offsetY + 'px + env(safe-area-inset-bottom, 0px))';
    widget.style.left = left ? 'calc(' + offsetX + 'px + env(safe-area-inset-left, 0px))' : 'auto';
    widget.style.right = left ? 'auto' : 'calc(' + offsetX + 'px + env(safe-area-inset-right, 0px))';
    menu.style.top = top ? '58px' : 'auto';
    menu.style.bottom = top ? 'auto' : '58px';
    menu.style.left = left ? '0' : 'auto';
    menu.style.right = left ? 'auto' : '0';
  }

  function updateWidget(state) {
    if (!widget) return;
    var options = state && state.widget ? state.widget : {};
    widget.hidden = options.enabled === false || (state && state.package_enabled === false);
    placeWidget(options.position || script.dataset.widgetPosition || 'bottom-right', Number(options.offset_x ?? script.dataset.widgetOffsetX) || 0, Number(options.offset_y ?? script.dataset.widgetOffsetY) || 0);
    if (state && state.recovery && state.recovery.enabled) {
      message.textContent = 'Pembersihan mengikuti resource yang diizinkan admin.';
    } else {
      message.textContent = 'Pembersihan belum diaktifkan admin; tindakan hanya memuat ulang aplikasi.';
    }
  }

  function createWidget() {
    addStyles();
    widget = document.createElement('div');
    widget.className = 'bcm-widget';
    widget.setAttribute('data-browser-cache-manager', '');
    widget.innerHTML = '<button class="bcm-toggle" type="button" aria-label="Buka menu perbaikan aplikasi" aria-expanded="false" aria-controls="bcm-widget-menu"><svg width="23" height="23" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><path d="M14.7 6.3a5 5 0 0 0-6.4 6.4L3.5 17.5a2.1 2.1 0 0 0 3 3l4.8-4.8a5 5 0 0 0 6.4-6.4l-3 3-3-3 3-3Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button><section class="bcm-menu" id="bcm-widget-menu" aria-label="Perbaikan aplikasi" hidden><h2 class="bcm-menu-title">Perbaikan aplikasi</h2><p class="bcm-menu-copy">Bersihkan cache aplikasi yang dikonfigurasi, lalu muat ulang aplikasi.</p><button class="bcm-action" type="button">Bersihkan cache &amp; perbarui</button><p class="bcm-message" role="status" aria-live="polite"></p></section>';
    document.body.appendChild(widget);
    widget.hidden = script.dataset.widgetEnabled === 'false';
    toggle = widget.querySelector('.bcm-toggle');
    menu = widget.querySelector('.bcm-menu');
    action = widget.querySelector('.bcm-action');
    message = widget.querySelector('.bcm-message');
    placeWidget(script.dataset.widgetPosition || 'bottom-right', Number(script.dataset.widgetOffsetX) || 20, Number(script.dataset.widgetOffsetY) || 20);

    toggle.addEventListener('click', function () {
      var open = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!open));
      menu.hidden = open;
      if (!open) action.focus();
    });
    document.addEventListener('click', function (event) {
      if (!widget.contains(event.target)) {
        menu.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
    widget.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !menu.hidden) {
        menu.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
      }
    });
    action.addEventListener('click', runManualRecovery);
  }

  async function recover(settings) {
    if (!settings || !settings.enabled) return 0;
    var failures = 0;
    async function attempt(task) { try { await task(); } catch (_) { failures += 1; } }

    if (settings.clear_cache_storage && window.caches && Array.isArray(settings.cache_storage_names)) {
      await Promise.all(settings.cache_storage_names.map(function (name) { return attempt(function () { return window.caches.delete(name); }); }));
    }
    if (settings.unregister_service_workers && navigator.serviceWorker && Array.isArray(settings.service_worker_scopes)) {
      await attempt(async function () {
        var registrations = await navigator.serviceWorker.getRegistrations();
        var owned = registrations.filter(function (registration) {
          return settings.service_worker_scopes.some(function (scope) {
            try { return scope && registration.scope.indexOf(new URL(scope, window.location.origin).href) === 0; } catch (_) { return false; }
          });
        });
        await Promise.all(owned.map(function (registration) { return registration.unregister(); }));
      });
    }
    if (settings.delete_indexeddb && window.indexedDB && Array.isArray(settings.indexeddb_names)) {
      await Promise.all(settings.indexeddb_names.map(function (name) {
        return attempt(function () { return new Promise(function (resolve, reject) {
          var request = window.indexedDB.deleteDatabase(name);
          request.onsuccess = function () { resolve(); };
          request.onerror = function () { reject(request.error); };
          request.onblocked = function () { reject(new Error('IndexedDB delete blocked')); };
        }); });
      }));
    }
    if (settings.clear_local_storage && Array.isArray(settings.local_storage_prefixes)) {
      await attempt(function () {
        var remove = [];
        for (var i = 0; i < window.localStorage.length; i += 1) {
          var key = window.localStorage.key(i);
          if (key && settings.local_storage_prefixes.some(function (prefix) { return prefix && key.indexOf(prefix) === 0; })) remove.push(key);
        }
        remove.forEach(function (key) { window.localStorage.removeItem(key); });
      });
    }
    return failures;
  }

  async function requestHttpCacheClear(settings) {
    if (!settings || !settings.enabled || !settings.clear_http_cache || !recoveryEndpoint) return false;
    var csrf = document.querySelector('meta[name="csrf-token"]');
    if (!csrf || !csrf.content) return false;
    var response = await fetch(recoveryEndpoint, {
      method: 'POST', credentials: 'same-origin', cache: 'no-store',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf.content }
    });
    return response.ok;
  }

  async function runManualRecovery() {
    if (action.disabled) return;
    action.disabled = true;
    var settings = lastState && lastState.recovery ? lastState.recovery : { enabled: false };
    var failures = await recover(settings);
    var httpCacheRequested = false;
    try { httpCacheRequested = await requestHttpCacheClear(settings); } catch (_) { failures += 1; }

    if (!settings.enabled) {
      message.textContent = 'Pembersihan belum diaktifkan admin. Aplikasi akan dimuat ulang.';
    } else if (failures) {
      message.textContent = 'Sebagian pembersihan gagal atau diblokir browser. Aplikasi akan dimuat ulang.';
    } else if (settings.clear_http_cache && !httpCacheRequested) {
      message.textContent = 'Cache terpilih diproses. HTTP cache tidak dapat diminta; periksa meta CSRF dan dukungan browser.';
    } else {
      message.textContent = 'Pembersihan terpilih diproses. Aplikasi akan dimuat ulang.';
    }
    window.setTimeout(function () { window.location.reload(); }, 500);
  }

  async function poll() {
    try {
      var headers = {};
      if (etag) headers['If-None-Match'] = etag;
      var response = await fetch(endpoint, { method: 'GET', credentials: 'same-origin', cache: 'no-store', headers: headers });
      if (response.status === 304) {
        window.setTimeout(poll, Math.max(30, Number(lastState && lastState.poll_interval) || 300) * 1000);
        return;
      }
      if (!response.ok) {
        window.setTimeout(poll, 30000);
        return;
      }
      etag = response.headers.get('ETag') || '';
      var state = await response.json();
      lastState = state;
      updateWidget(state);
      if (!state.version) return;
      if (!state.enabled) {
        window.setTimeout(poll, Math.max(30, Number(state.poll_interval) || 300) * 1000);
        return;
      }

      var previous = safeStorage(window.localStorage, 'get', versionKey);
      if (previous && previous !== state.version) {
        await recover(state.recovery);
        safeStorage(window.localStorage, 'set', versionKey, state.version);
        var alreadyReloaded = safeStorage(window.sessionStorage, 'get', reloadKey) === state.version;
        if (state.recovery && state.recovery.enabled && state.recovery.force_reload && (!state.recovery.reload_once || !alreadyReloaded)) {
          safeStorage(window.sessionStorage, 'set', reloadKey, state.version);
          window.location.reload();
          return;
        }
      } else {
        safeStorage(window.localStorage, 'set', versionKey, state.version);
      }
      window.setTimeout(poll, Math.max(30, Number(state.poll_interval) || 300) * 1000);
    } catch (_) {
      window.setTimeout(poll, 30000);
    }
  }

  createWidget();
  poll();
}());
