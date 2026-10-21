<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->timestamp('converted_at')->nullable();
        });

        // Existing conversions remain consumed even if their customer is later deleted.
        DB::table('leads')->whereNotNull('converted_company_id')->update(['converted_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn('converted_at');
        });
    }
};
