<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image_url')->nullable();          // Curator media ID
            $table->string('image_url_external')->nullable(); // External image URL (e.g. CDN, direct link)
            $table->string('url_link')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // Pivot table for many-to-many relationship with proposals
        Schema::create('portfolio_proposal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['portfolio_id', 'proposal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_proposal');
        Schema::dropIfExists('portfolios');
    }
};