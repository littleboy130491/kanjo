<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();

            // Document numbering
            $table->string('document_number')->nullable()->index();
            $table->string('slug')->nullable()->unique();
            $table->unsignedInteger('document_number_raw')->nullable();
            $table->unsignedTinyInteger('issue_month')->nullable();
            $table->unsignedSmallInteger('issue_year')->nullable();
            $table->boolean('document_number_override')->default(false);
            $table->string('document_number_manual')->nullable();
            $table->unique(
                ['document_number_raw', 'issue_month', 'issue_year'],
                'proposals_doc_unique',
            );

            // Client info
            $table->string('client_company');
            $table->string('client_name');
            $table->string('client_email')->nullable();
            $table->string('client_phone')->nullable();

            // Dates
            $table->date('issue_date');
            $table->date('valid_until')->nullable();

            // Financials
            $table->string('currency')->default('IDR');
            $table->decimal('tax_rate', 5, 2)->default(0);

            // Translatable JSON fields
            $table->json('brief')->nullable();
            $table->json('core_services')->nullable();
            $table->json('features')->nullable();
            $table->json('server')->nullable();
            $table->json('assets')->nullable();
            $table->json('security')->nullable();
            $table->json('support')->nullable();
            $table->json('additional_benefit')->nullable();
            $table->json('add_on')->nullable();
            $table->json('payment')->nullable();
            $table->json('terms_condition')->nullable();

            // Offer 1
            $table->string('offer_name_1')->nullable();
            $table->decimal('offer_1_price', 15, 2)->nullable();
            $table->decimal('offer_1_original_price', 15, 2)->nullable();
            $table->decimal('offer_1_renewal_price', 15, 2)->nullable();
            $table
                ->decimal('offer_1_original_renewal_price', 15, 2)
                ->nullable();
            $table->json('offer_1_project_timeline')->nullable();

            // Offer 2
            $table->string('offer_name_2')->nullable();
            $table->decimal('offer_2_price', 15, 2)->nullable();
            $table->decimal('offer_2_original_price', 15, 2)->nullable();
            $table->decimal('offer_2_renewal_price', 15, 2)->nullable();
            $table
                ->decimal('offer_2_original_renewal_price', 15, 2)
                ->nullable();
            $table->json('offer_2_project_timeline')->nullable();

            // Status & access
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->string('access_username')->nullable();
            $table->string('access_password')->nullable();

            // Internal
            $table->json('notes')->nullable();

            // Relations
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
