<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spks', function (Blueprint $table): void {
            $table->id();

            $table->string('document_number')->nullable()->index();
            $table->string('slug')->nullable()->unique();
            $table->unsignedInteger('document_number_raw')->nullable();
            $table->string('document_number_suffix')->default('NEW');
            $table->unsignedTinyInteger('issue_month')->nullable();
            $table->unsignedSmallInteger('issue_year')->nullable();
            $table->boolean('document_number_override')->default(false);
            $table->unique(['document_number_raw', 'issue_month', 'issue_year'], 'spks_doc_unique');

            $table->date('spk_date');

            $table->string('client_company');
            $table->string('client_pic_name');
            $table->string('client_pic_role')->nullable();
            $table->text('client_address')->nullable();

            $table->string('company_name');
            $table->string('company_pic_name');
            $table->string('company_pic_role')->nullable();
            $table->text('company_address')->nullable();

            $table->json('title')->nullable();
            $table->json('subject')->nullable();
            $table->json('content')->nullable();

            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->string('access_username')->nullable();
            $table->string('access_password')->nullable();
            $table->timestamp('access_credentials_updated_at')->nullable();

            $table->json('notes')->nullable();

            $table->foreignId('proposal_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spks');
    }
};
