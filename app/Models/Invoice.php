<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Invoice extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    protected $translatable = [
        'items',
    ];

    protected $casts = [
        'document_number_override' => 'boolean',
        'issue_date'               => 'date',
        'due_date'                 => 'date',
        'paid_at'                  => 'datetime',
        'tax_rate'                 => 'decimal:2',
        'tax_amount'               => 'decimal:2',
        'subtotal'                 => 'decimal:2',
        'total'                    => 'decimal:2',
        'paid_amount'              => 'decimal:2',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
