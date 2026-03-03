<?php

namespace App\Models;

use App\Enums\ServiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'domain',
        'start_date',
        'renewal_date',
        'price',
        'currency',
        'status',
        'notes',
        'client_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'notes' => 'array',
        'status' => ServiceStatus::class,
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function proposal(): HasOneThrough
    {
        return $this->hasOneThrough(
            Proposal::class,
            Invoice::class,
            'service_id',
            'id',
            'id',
            'proposal_id',
        );
    }

    public function proposals(): HasManyThrough
    {
        return $this->hasManyThrough(
            Proposal::class,
            Invoice::class,
            'service_id',
            'id',
            'id',
            'proposal_id',
        )->distinct();
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
