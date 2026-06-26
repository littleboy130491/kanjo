<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory;
    use LogsModelActivity;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'company',
        'address',
        'email',
        'phone',
        'notes',
    ];

    protected $casts = [
        'notes' => 'array',
    ];

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }
}
