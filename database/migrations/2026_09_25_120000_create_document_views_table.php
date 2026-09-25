<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_views', function (Blueprint $table): void {
            $table->id();
            $table->string('viewable_type')->index();
            $table->unsignedBigInteger('viewable_id')->index();
            $table->timestamp('viewed_at')->index();
            $table->string('page_type', 10)->default('html');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->string('device')->nullable();
            $table->text('referer')->nullable();
            $table->timestamps();

            $table->index(['viewable_type', 'viewable_id', 'viewed_at'], 'document_views_viewable_viewed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_views');
    }
};
