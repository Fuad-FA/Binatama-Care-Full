<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('medical_records', function (Blueprint $table) {
        $table->json('custom_data')->nullable()->after('catatan');
    });
}

public function down(): void
{
    Schema::table('medical_records', function (Blueprint $table) {
        $table->dropColumn('custom_data');
    });
}
};
