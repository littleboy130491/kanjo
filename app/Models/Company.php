<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Company extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'company_name', 'brand_name', 'logo', 'address',
        'email_1', 'email_2', 'phone_1', 'phone_2',
        'tax_id', 'website', 'default_currency',
        'color_primary', 'color_secondary',
        'footer_text', 'bank', 'pic',
    ];

    protected $translatable = [
        'footer_text',
    ];

    protected $casts = [
        'bank' => 'array',
        'pic'  => 'array',
    ];

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
