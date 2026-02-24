<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            // Document numbering
            $table->string('document_number')->index();
            $table->unsignedInteger('document_number_raw');
            $table->unsignedTinyInteger('issue_month');
            $table->unsignedSmallInteger('issue_year');
            $table->string('document_number_suffix')->default('NEW');
            $table->boolean('document_number_override')->default(false);
            $table->unique(['document_number_raw', 'issue_month', 'issue_year'], 'invoices_doc_unique');

            // Client info (frozen snapshot)
            $table->string('client_company');
            $table->string('client_name');
            $table->string('client_email');

            // Dates
            $table->date('issue_date');
            $table->date('due_date');

            // Financials
            $table->string('currency')->default('IDR');
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            // Items (translatable sub-fields)
            $table->json('items')->nullable();

            // Status
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->enum('payment_status', ['unpaid', 'partially_paid', 'paid', 'overdue', 'cancelled'])->default('unpaid');

            // Payment tracking
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable();

            // Access
            $table->string('access_username')->nullable();
            $table->string('access_password')->nullable();

            // Internal
            $table->text('notes')->nullable();

            // Relations
            $table->foreignId('proposal_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
