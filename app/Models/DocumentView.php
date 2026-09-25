<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentView extends Model
{
    protected $fillable = [
        'viewable_type',
        'viewable_id',
        'viewed_at',
        'page_type',
        'ip_address',
        'user_agent',
        'browser',
        'platform',
        'device',
        'referer',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function viewable(): MorphTo
    {
        return $this->morphTo();
    }
}
