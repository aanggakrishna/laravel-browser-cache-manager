(function () {
  'use strict';

  var script = document.currentScript;
  if (!script) return;
  var endpoint = script.dataset.endpoint;
  if (!endpoint || !window.fetch) return;

  var versionKey = 'browser-cache-manager.version';
  var reloadKey = 'browser-cache-manager.reloaded-version';
  var etag = '';

  function removePrefixedLocalStorage(prefixes) {
    if (!Array.isArray(prefixes) || !window.localStorage) return;
    var remove = [];
    for (var i = 0; i < window.localStorage.length; i += 1) {
      var key = window.localStorage.key(i);
      if (key && prefixes.some(function (prefix) { return prefix && key.indexOf(prefix) === 0; })) remove.push(key);
    }
    remove.forEach(function (key) { window.localStorage.removeItem(key); });
  }

  async function recover(settings) {
    if (!settings || !settings.enabled) return;
    if (settings.clear_cache_storage && window.caches && Array.isArray(settings.cache_storage_names)) {
      await Promise.all(settings.cache_storage_names.map(function (name) { return window.caches.delete(name); }));
    }
    if (settings.unregister_service_workers && navigator.serviceWorker && Array.isArray(settings.service_worker_scopes)) {
      var registrations = await navigator.serviceWorker.getRegistrations();
      await Promise.all(registrations.filter(function (registration) {
        return settings.service_worker_scopes.some(function (scope) { return scope && registration.scope.indexOf(scope) === 0; });
      }).map(function (registration) { return registration.unregister(); }));
    }
    if (settings.delete_indexeddb && window.indexedDB && Array.isArray(settings.indexeddb_names)) {
      await Promise.all(settings.indexeddb_names.map(function (name) {
        return new Promise(function (resolve) {
          var request = window.indexedDB.deleteDatabase(name);
          request.onsuccess = request.onerror = request.onblocked = function () { resolve(); };
        });
      }));
    }
    if (settings.clear_local_storage) removePrefixedLocalStorage(settings.local_storage_prefixes);
  }

  async function poll() {
    try {
      var headers = {};
      if (etag) headers['If-None-Match'] = etag;
      var response = await fetch(endpoint, { method: 'GET', credentials: 'same-origin', cache: 'no-store', headers: headers });
      if (response.status === 304) return;
      if (!response.ok) return;
      etag = response.headers.get('ETag') || '';
      var state = await response.json();
      if (!state.version) return;
      if (!state.enabled) {
        window.setTimeout(poll, Math.max(30, Number(state.poll_interval) || 300) * 1000);
        return;
      }

      var previous = localStorage.getItem(versionKey);
      if (previous && previous !== state.version) {
        await recover(state.recovery);
        localStorage.setItem(versionKey, state.version);
        var alreadyReloaded = sessionStorage.getItem(reloadKey) === state.version;
        if (state.recovery && state.recovery.enabled && state.recovery.force_reload && (!state.recovery.reload_once || !alreadyReloaded)) {
          sessionStorage.setItem(reloadKey, state.version);
          window.location.reload();
          return;
        }
      } else {
        localStorage.setItem(versionKey, state.version);
      }
      window.setTimeout(poll, Math.max(30, Number(state.poll_interval) || 300) * 1000);
    } catch (_) {
      window.setTimeout(poll, 30000);
    }
  }

  poll();
}());
