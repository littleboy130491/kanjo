<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('resource_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('lockable_type');
            $table->unsignedBigInteger('lockable_id');
            $table->string('lock_identifier')->unique();
            $table->timestamp('locked_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['lockable_type', 'lockable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_locks');
    }
};
