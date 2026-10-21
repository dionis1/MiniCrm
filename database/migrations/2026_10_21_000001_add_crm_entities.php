<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Preserve legacy company/employee records. Unassigned legacy records
        // remain private until an administrator deliberately claims ownership.
        Schema::table('companies', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('phone', 40)->nullable();
            $table->string('status', 20)->default('active');
            $table->json('tags')->nullable();
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('position')->nullable();
            $table->json('tags')->nullable();
        });

        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('company_name', 160)->nullable();
            $table->string('stage', 30)->default('new');
            $table->decimal('deal_value', 12, 2)->default(0);
            $table->unsignedTinyInteger('probability')->default(10);
            $table->unsignedBigInteger('version')->default(0);
            $table->foreignId('converted_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'stage']);
        });

        Schema::create('crm_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('priority', 12)->default('normal');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'completed_at', 'due_at']);
        });

        Schema::create('crm_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject_type', 30);
            $table->unsignedBigInteger('subject_id');
            $table->string('type', 30);
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_activities');
        Schema::dropIfExists('crm_tasks');
        Schema::dropIfExists('leads');
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['position', 'tags']);
        });
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['phone', 'status', 'tags']);
        });
    }
};
