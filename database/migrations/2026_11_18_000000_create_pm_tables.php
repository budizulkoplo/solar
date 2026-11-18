<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_projects', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('pm_stages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('pm_projects')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 140);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_done')->default(false);
            $table->timestamps();

            $table->unique(['project_id', 'slug']);
        });

        Schema::create('pm_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('pm_projects')->cascadeOnDelete();
            $table->foreignId('stage_id')->nullable()->constrained('pm_stages')->nullOnDelete();
            $table->string('title', 180);
            $table->string('priority', 20)->default('normal');
            $table->longText('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hidden_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'stage_id']);
            $table->index(['due_date', 'hidden_at']);
        });

        Schema::create('pm_task_assignees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('pm_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'user_id']);
        });

        Schema::create('pm_task_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('pm_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->timestamps();
        });

        Schema::create('pm_task_status_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('pm_tasks')->cascadeOnDelete();
            $table->foreignId('old_stage_id')->nullable()->constrained('pm_stages')->nullOnDelete();
            $table->foreignId('new_stage_id')->nullable()->constrained('pm_stages')->nullOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at')->useCurrent();
            $table->string('note', 255)->nullable();

            $table->index(['task_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_task_status_logs');
        Schema::dropIfExists('pm_task_comments');
        Schema::dropIfExists('pm_task_assignees');
        Schema::dropIfExists('pm_tasks');
        Schema::dropIfExists('pm_stages');
        Schema::dropIfExists('pm_projects');
    }
};
