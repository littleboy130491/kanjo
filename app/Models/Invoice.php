<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;
use App\Services\DocumentNumberService;
use Carbon\Carbon;

class Invoice extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    protected $fillable = [
        'document_number', 'document_number_raw', 'document_number_suffix', 'document_number_override',
        'issue_month', 'issue_year',
        'client_company', 'client_name', 'client_email',
        'issue_date', 'due_date',
        'currency', 'tax_rate', 'tax_amount', 'subtotal', 'total',
        'items',
        'status', 'payment_status',
        'paid_amount', 'paid_at', 'payment_method',
        'access_username', 'access_password',
        'notes', 'proposal_id', 'user_id', 'company_id',
    ];

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

    protected static function booted()
    {
        static::creating(function ($invoice) {
            // Always set issue_month and issue_year from issue_date
            if ($invoice->issue_date) {
                $date = Carbon::parse($invoice->issue_date);
                $invoice->issue_month = $date->month;
                $invoice->issue_year = $date->year;
            } else {
                $now = now();
                $invoice->issue_month = $now->month;
                $invoice->issue_year = $now->year;
            }

            // Always generate document number (even if overridden)
            $date = $invoice->issue_date ? Carbon::parse($invoice->issue_date) : now();
            $data = DocumentNumberService::generate('INV', $date);
            
            $invoice->document_number_raw = $data['document_number_raw'];
            
            // If not overridden, use the generated suffix and full number
            if (!$invoice->document_number_override) {
                $invoice->document_number = $data['document_number'];
                $invoice->document_number_suffix = $data['document_number_suffix'];
            } else {
                // If overridden but no suffix provided, use default
                if (!$invoice->document_number_suffix) {
                    $invoice->document_number_suffix = $data['document_number_suffix'];
                }
                // Generate the full number with the custom suffix
                $invoice->document_number = DocumentNumberService::regenerateWithSuffix(
                    'INV',
                    $data['document_number_raw'],
                    $date,
                    $invoice->document_number_suffix
                );
            }
        });

        static::updating(function ($invoice) {
            // Update issue_month and issue_year if issue_date changes
            if ($invoice->isDirty('issue_date') && $invoice->issue_date) {
                $date = Carbon::parse($invoice->issue_date);
                $invoice->issue_month = $date->month;
                $invoice->issue_year = $date->year;
            }

            // Regenerate document number with new suffix if overridden
            if ($invoice->document_number_override && $invoice->isDirty('document_number_suffix')) {
                $date = $invoice->issue_date ? Carbon::parse($invoice->issue_date) : now();
                $invoice->document_number = DocumentNumberService::regenerateWithSuffix(
                    'INV',
                    $invoice->document_number_raw,
                    $date,
                    $invoice->document_number_suffix
                );
            }
        });
    }

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
