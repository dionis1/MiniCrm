<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->index(['user_id', 'created_at', 'id']);
            $table->index(['user_id', 'name', 'id']);
        });
        Schema::table('companies', fn (Blueprint $table) => $table->index(['user_id', 'status', 'name']));
        Schema::table('employees', fn (Blueprint $table) => $table->index(['user_id', 'companie_id', 'first_name']));
        Schema::table('crm_tasks', fn (Blueprint $table) => $table->index(['user_id', 'priority', 'due_at']));
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'created_at', 'id']);
            $table->dropIndex(['user_id', 'name', 'id']);
        });
        Schema::table('companies', fn (Blueprint $table) => $table->dropIndex(['user_id', 'status', 'name']));
        Schema::table('employees', fn (Blueprint $table) => $table->dropIndex(['user_id', 'companie_id', 'first_name']));
        Schema::table('crm_tasks', fn (Blueprint $table) => $table->dropIndex(['user_id', 'priority', 'due_at']));
    }
};
