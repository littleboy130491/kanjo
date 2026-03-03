<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->nullable();         // website URL
            $table->string('start_date')->nullable();     // when service became active
            $table->string('renewal_date')->nullable();   // date and month for renewal
            $table->decimal('price', 15, 2)->default(0);
            $table->string('currency')->default('IDR');
            $table->enum('status', ['terminated', 'on-going', 'suspended'])->default('on-going');
            $table->json('notes')->nullable();            // non-translatable JSON array
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
