<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('brand_name');
            $table->unsignedBigInteger('logo')->nullable();
            $table->text('address')->nullable();
            $table->string('email_1');
            $table->string('email_2')->nullable();
            $table->string('phone_1');
            $table->string('phone_2')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('website')->nullable();
            $table->string('default_currency')->default('IDR');
            $table->string('color_primary')->nullable();
            $table->string('color_secondary')->nullable();
            $table->json('footer_text')->nullable();
            $table->json('bank')->nullable();
            $table->json('pic')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
