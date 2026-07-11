<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('service_fields', function (Blueprint $table) {
        $table->id();
        $table->foreignId('service_id')->constrained()->onDelete('cascade');
        $table->string('field_key');
        $table->string('label');
        $table->string('unit')->nullable();
        $table->enum('input_type', ['number', 'text', 'textarea'])->default('number');
        $table->string('normal_text')->nullable();
        $table->boolean('is_required')->default(true);
        $table->integer('sort_order')->default(0);
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('service_fields');
}
};
