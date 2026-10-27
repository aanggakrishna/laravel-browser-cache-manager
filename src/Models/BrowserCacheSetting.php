<?php

namespace Aanggakrishna\BrowserCacheManager\Models;

use Illuminate\Database\Eloquent\Model;

class BrowserCacheSetting extends Model
{
    protected $table = 'browser_cache_settings';
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
}
