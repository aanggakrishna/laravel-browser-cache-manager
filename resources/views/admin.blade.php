<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Browser Cache Manager</title></head>
<body>
<main style="max-width:48rem;margin:2rem auto;font:16px system-ui,sans-serif">
    <h1>Browser Cache Manager</h1>
    @if(session('status'))<p role="status">{{ session('status') }}</p>@endif
    <p>Build version: <strong>{{ config('browser-cache-manager.version') }}</strong></p>
    <form method="post" action="{{ route('browser-cache-manager.admin.update') }}">
        @csrf @method('PUT')
        @foreach(['enabled'=>'Runtime enabled','auto_check'=>'Automatic version check','recovery_enabled'=>'Allow recovery actions','force_reload'=>'Reload after version change','clear_cache_storage'=>'Clear named Cache Storage entries','unregister_service_workers'=>'Unregister service workers','delete_indexeddb'=>'Delete named IndexedDB databases','clear_local_storage'=>'Clear prefixed localStorage keys'] as $key=>$label)
            <label style="display:block;padding:.45rem"><input type="hidden" name="{{ $key }}" value="0"><input type="checkbox" name="{{ $key }}" value="1" @checked($settings[$key] ?? false)> {{ $label }}</label>
        @endforeach
        <button type="submit">Save settings</button>
    </form>
    <p>Recovery actions are opt-in and limited to application-owned resources configured by the deployer. Browser-wide clearing is not possible.</p>
</main>
</body></html>
