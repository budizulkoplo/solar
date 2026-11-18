<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pm_projects', function (Blueprint $table): void {
            $table->unsignedBigInteger('legacy_project_id')->nullable()->unique()->after('id');
            $table->string('ticket_prefix', 80)->nullable()->after('description');
            $table->date('start_date')->nullable()->after('ticket_prefix');
            $table->date('end_date')->nullable()->after('start_date');
            $table->dateTime('pinned_date')->nullable()->after('end_date');
        });

        Schema::table('pm_stages', function (Blueprint $table): void {
            $table->unsignedBigInteger('legacy_status_id')->nullable()->unique()->after('id');
            $table->string('color', 20)->nullable()->after('is_done');
        });

        Schema::table('pm_tasks', function (Blueprint $table): void {
            $table->unsignedBigInteger('legacy_ticket_id')->nullable()->unique()->after('id');
            $table->string('uuid', 120)->nullable()->index()->after('legacy_ticket_id');
            $table->unsignedBigInteger('legacy_priority_id')->nullable()->after('priority');
            $table->unsignedBigInteger('legacy_epic_id')->nullable()->after('legacy_priority_id');
            $table->string('epic', 160)->nullable()->after('legacy_epic_id');
        });

        Schema::table('pm_task_comments', function (Blueprint $table): void {
            $table->unsignedBigInteger('legacy_comment_id')->nullable()->unique()->after('id');
        });

        Schema::table('pm_task_status_logs', function (Blueprint $table): void {
            $table->unsignedBigInteger('legacy_history_id')->nullable()->unique()->after('id');
        });

        Schema::create('pm_epics', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_epic_id')->nullable()->unique();
            $table->foreignId('project_id')->constrained('pm_projects')->cascadeOnDelete();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('pm_project_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('pm_projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['project_id', 'user_id']);
        });

        Schema::create('pm_project_notes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_note_id')->nullable()->unique();
            $table->foreignId('project_id')->constrained('pm_projects')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->longText('content')->nullable();
            $table->date('note_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_project_notes');
        Schema::dropIfExists('pm_project_members');
        Schema::dropIfExists('pm_epics');

        Schema::table('pm_task_status_logs', fn (Blueprint $table) => $table->dropColumn('legacy_history_id'));
        Schema::table('pm_task_comments', fn (Blueprint $table) => $table->dropColumn('legacy_comment_id'));
        Schema::table('pm_tasks', fn (Blueprint $table) => $table->dropColumn(['legacy_ticket_id', 'uuid', 'legacy_priority_id', 'legacy_epic_id', 'epic']));
        Schema::table('pm_stages', fn (Blueprint $table) => $table->dropColumn(['legacy_status_id', 'color']));
        Schema::table('pm_projects', fn (Blueprint $table) => $table->dropColumn(['legacy_project_id', 'ticket_prefix', 'start_date', 'end_date', 'pinned_date']));
    }
};
