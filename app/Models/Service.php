<?php

namespace App\Models;

use App\Enums\ServiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'domain',
        'start_date',
        'renewal_date',
        'status',
        'notes',
        'client_id',
    ];

    protected $casts = [
        'notes' => 'array',
        'status' => ServiceStatus::class,
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}