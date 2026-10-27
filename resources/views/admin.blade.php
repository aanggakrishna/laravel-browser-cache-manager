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
        @foreach(['enabled'=>'Runtime enabled','auto_check'=>'Automatic version check','widget_enabled'=>'Show floating cache button','recovery_enabled'=>'Allow recovery actions','force_reload'=>'Reload after version change','clear_http_cache'=>'Request browser HTTP cache clearing','clear_cache_storage'=>'Clear named Cache Storage entries','unregister_service_workers'=>'Unregister configured service workers','delete_indexeddb'=>'Delete named IndexedDB databases','clear_local_storage'=>'Clear prefixed localStorage keys'] as $key=>$label)
            <label style="display:block;padding:.45rem"><input type="hidden" name="{{ $key }}" value="0"><input type="checkbox" name="{{ $key }}" value="1" @checked($settings[$key] ?? false)> {{ $label }}</label>
        @endforeach
        <label style="display:block;padding:.45rem">Button position
            <select name="widget_position">
                @foreach(['bottom-right'=>'Bottom right','bottom-left'=>'Bottom left','top-right'=>'Top right','top-left'=>'Top left'] as $value=>$label)
                    <option value="{{ $value }}" @selected(($settings['widget_position'] ?? 'bottom-right') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label style="display:block;padding:.45rem">Horizontal offset (px)
            <input type="number" name="widget_offset_x" min="0" max="500" value="{{ $settings['widget_offset_x'] ?? 20 }}">
        </label>
        <label style="display:block;padding:.45rem">Vertical offset (px)
            <input type="number" name="widget_offset_y" min="0" max="500" value="{{ $settings['widget_offset_y'] ?? 20 }}">
        </label>
        <button type="submit">Save settings</button>
    </form>
    <p>Recovery actions are opt-in and limited to the application resources configured by the deployer. HTTP cache clearing sends <code>Clear-Site-Data: "cache"</code> and can clear cached responses across this origin; browser support varies.</p>
</main>
</body></html>
