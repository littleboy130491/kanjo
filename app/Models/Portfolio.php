<?php

namespace App\Models;

use Awcodes\Curator\Models\Media;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|null $image_url_external
 * @property-read string|null $portfolio_image_url
 */
class Portfolio extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'image_url',
        'image_url_external',
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

    protected function portfolioImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image?->url ?? $this->image_url_external,
        );
    }
}
