<?php

namespace App\Models;

use Awcodes\Curator\Models\Media;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Portfolio extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'image_url',
        'url_link',
    ];

    public function proposals()
    {
        return $this->belongsToMany(Proposal::class);
    }

    public function image()
    {
        return $this->belongsTo(Media::class, 'image_url');
    }
}
