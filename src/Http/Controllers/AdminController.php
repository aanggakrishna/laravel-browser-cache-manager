<?php

namespace Aanggakrishna\BrowserCacheManager\Http\Controllers;

use Aanggakrishna\BrowserCacheManager\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AdminController extends Controller
{
    public function index(Settings $settings)
    {
        return view('browser-cache-manager::admin', ['settings' => $settings->all()]);
    }

    public function update(Request $request, Settings $settings)
    {
        $validated = $request->validate([
            'enabled' => ['sometimes', 'boolean'], 'auto_check' => ['sometimes', 'boolean'],
            'recovery_enabled' => ['sometimes', 'boolean'], 'force_reload' => ['sometimes', 'boolean'],
            'clear_cache_storage' => ['sometimes', 'boolean'], 'unregister_service_workers' => ['sometimes', 'boolean'],
            'delete_indexeddb' => ['sometimes', 'boolean'], 'clear_local_storage' => ['sometimes', 'boolean'],
        ]);
        $settings->update(array_map(static fn ($value): bool => (bool) $value, $validated));

        return redirect()->back()->with('status', 'Browser cache settings saved.');
    }
}
