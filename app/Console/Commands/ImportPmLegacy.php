<?php

namespace App\Console\Commands;

use App\Models\PmEpic;
use App\Models\PmProject;
use App\Models\PmProjectNote;
use App\Models\PmStage;
use App\Models\PmTask;
use App\Models\PmTaskComment;
use App\Models\PmTaskStatusLog;
use App\Models\User;
use App\Support\PmHtml;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportPmLegacy extends Command
{
    protected $signature = 'pm:import-legacy {--dry-run : Tampilkan pemetaan tanpa mengubah database Solar}';

    protected $description = 'Import project management legacy dari database pm ke Solar System';

    private $source;
    private array $userMap = [];
    private array $projectMap = [];
    private array $stageMap = [];
    private array $epicMap = [];
    private array $taskMap = [];
    private array $unmatchedUsers = [];

    public function handle(): int
    {
        $this->source = DB::connection('pm');

        if (!$this->source->getSchemaBuilder()->hasTable('tickets')) {
            $this->error('Database legacy PM tidak memiliki tabel tickets.');
            return self::FAILURE;
        }

        $this->userMap = $this->buildUserMap();
        $this->line('Pemetaan user: '.count($this->userMap).' berhasil, '.count($this->unmatchedUsers).' belum ditemukan.');

        if ($this->option('dry-run')) {
            $this->printSourceSummary();
            $this->warn('Dry-run: tidak ada data yang ditulis ke database Solar.');
            return self::SUCCESS;
        }

        DB::transaction(function (): void {
            $this->importProjects();
            $this->importStages();
            $this->importEpics();
            $this->importProjectMembers();
            $this->importProjectNotes();
            $this->importTasks();
            $this->importComments();
            $this->importStatusHistories();
        });

        $this->info('Import legacy PM selesai.');
        if ($this->unmatchedUsers) {
            $this->warn('User PM tanpa pasangan di Solar: '.implode(', ', $this->unmatchedUsers));
        }
        $this->comment('Tabel external_access, roles, permissions, dan password PM sengaja tidak diimpor.');

        return self::SUCCESS;
    }

    private function buildUserMap(): array
    {
        $legacyUsers = $this->source->table('users')->get(['id', 'name', 'email']);
        $solarUsers = User::query()->get(['id', 'name', 'email']);
        $byEmail = $solarUsers->filter(fn ($user) => filled($user->email))
            ->keyBy(fn ($user) => strtolower(trim($user->email)));
        $byName = $solarUsers->keyBy(fn ($user) => $this->normalizeName($user->name));
        $map = [];

        foreach ($legacyUsers as $legacyUser) {
            $match = $legacyUser->email
                ? $byEmail->get(strtolower(trim($legacyUser->email)))
                : null;
            $match ??= $byName->get($this->normalizeName($legacyUser->name));

            if ($match) {
                $map[(int) $legacyUser->id] = (int) $match->id;
            } else {
                $this->unmatchedUsers[] = $legacyUser->name.' <'.$legacyUser->email.'>';
            }
        }

        return $map;
    }

    private function importProjects(): void
    {
        foreach ($this->source->table('projects')->orderBy('id')->get() as $legacy) {
            $project = PmProject::updateOrCreate(
                ['legacy_project_id' => $legacy->id],
                [
                    'name' => $legacy->name,
                    'slug' => $this->legacyProjectSlug($legacy->name, (int) $legacy->id),
                    'description' => PmHtml::sanitize($legacy->description),
                    'ticket_prefix' => $legacy->ticket_prefix,
                    'start_date' => $legacy->start_date,
                    'end_date' => $legacy->end_date,
                    'pinned_date' => $legacy->pinned_date,
                    'is_active' => true,
                ]
            );
            $this->projectMap[(int) $legacy->id] = $project->id;
        }
        $this->line('Projects: '.count($this->projectMap));
    }

    private function importStages(): void
    {
        foreach ($this->source->table('ticket_statuses')->orderBy('project_id')->orderBy('sort_order')->get() as $legacy) {
            $projectId = $this->projectMap[(int) $legacy->project_id] ?? null;
            if (!$projectId) {
                continue;
            }

            $stage = PmStage::updateOrCreate(
                ['legacy_status_id' => $legacy->id],
                [
                    'project_id' => $projectId,
                    'name' => $legacy->name,
                    'slug' => Str::slug($legacy->name).'-'.$legacy->id,
                    'sort_order' => (int) $legacy->sort_order,
                    'is_done' => (bool) $legacy->is_completed,
                    'color' => $legacy->color,
                ]
            );
            $this->stageMap[(int) $legacy->id] = $stage->id;
        }
        $this->line('Stages: '.count($this->stageMap));
    }

    private function importEpics(): void
    {
        foreach ($this->source->table('epics')->orderBy('id')->get() as $legacy) {
            $projectId = $this->projectMap[(int) $legacy->project_id] ?? null;
            if (!$projectId) {
                continue;
            }

            $epic = PmEpic::updateOrCreate(
                ['legacy_epic_id' => $legacy->id],
                [
                    'project_id' => $projectId,
                    'name' => $legacy->name,
                    'description' => PmHtml::sanitize($legacy->description),
                    'start_date' => $legacy->start_date,
                    'end_date' => $legacy->end_date,
                ]
            );
            $this->epicMap[(int) $legacy->id] = $epic->id;
        }
        $this->line('Epics: '.count($this->epicMap));
    }

    private function importProjectMembers(): void
    {
        foreach ($this->source->table('project_members')->get() as $legacy) {
            $projectId = $this->projectMap[(int) $legacy->project_id] ?? null;
            $userId = $this->userMap[(int) $legacy->user_id] ?? null;
            if (!$projectId || !$userId) {
                continue;
            }

            DB::table('pm_project_members')->updateOrInsert(
                ['project_id' => $projectId, 'user_id' => $userId],
                ['created_at' => $legacy->created_at, 'updated_at' => $legacy->updated_at]
            );
        }
    }

    private function importProjectNotes(): void
    {
        foreach ($this->source->table('project_notes')->get() as $legacy) {
            $projectId = $this->projectMap[(int) $legacy->project_id] ?? null;
            if (!$projectId) {
                continue;
            }

            PmProjectNote::updateOrCreate(
                ['legacy_note_id' => $legacy->id],
                [
                    'project_id' => $projectId,
                    'created_by' => $this->userMap[(int) $legacy->created_by] ?? null,
                    'title' => $legacy->title,
                    'content' => $this->plainText($legacy->content),
                    'note_date' => $legacy->note_date,
                ]
            );
        }
    }

    private function importTasks(): void
    {
        $priorities = $this->source->table('ticket_priorities')->pluck('name', 'id');

        foreach ($this->source->table('tickets')->orderBy('id')->get() as $legacy) {
            $projectId = $this->projectMap[(int) $legacy->project_id] ?? null;
            if (!$projectId) {
                continue;
            }

            $task = PmTask::updateOrCreate(
                ['legacy_ticket_id' => $legacy->id],
                [
                    'uuid' => $legacy->uuid,
                    'project_id' => $projectId,
                    'stage_id' => $this->stageMap[(int) $legacy->ticket_status_id] ?? null,
                    'legacy_priority_id' => $legacy->priority_id,
                    'legacy_epic_id' => $legacy->epic_id,
                    'epic' => $legacy->epic_id ? ($this->source->table('epics')->where('id', $legacy->epic_id)->value('name')) : null,
                    'title' => $legacy->name,
                    'priority' => $this->priorityValue($priorities[(int) $legacy->priority_id] ?? null),
                    'description' => $this->plainText($legacy->description),
                    'start_date' => $legacy->start_date,
                    'due_date' => $legacy->due_date,
                    'created_by' => $this->userMap[(int) $legacy->created_by] ?? null,
                ]
            );
            $this->taskMap[(int) $legacy->id] = $task->id;

            $assignees = $this->source->table('ticket_users')->where('ticket_id', $legacy->id)->pluck('user_id')
                ->map(fn ($userId) => $this->userMap[(int) $userId] ?? null)
                ->filter()
                ->unique()
                ->values()
                ->all();
            $task->assignees()->sync($assignees);
        }
        $this->line('Tasks: '.count($this->taskMap));
    }

    private function importComments(): void
    {
        foreach ($this->source->table('ticket_comments')->orderBy('id')->get() as $legacy) {
            $taskId = $this->taskMap[(int) $legacy->ticket_id] ?? null;
            $userId = $this->userMap[(int) $legacy->user_id] ?? null;
            if (!$taskId || !$userId) {
                continue;
            }

            PmTaskComment::updateOrCreate(
                ['legacy_comment_id' => $legacy->id],
                [
                    'task_id' => $taskId,
                    'user_id' => $userId,
                    'message' => PmHtml::sanitize($legacy->comment),
                ]
            );
        }
    }

    private function importStatusHistories(): void
    {
        foreach ($this->source->table('ticket_histories')->orderBy('ticket_id')->orderBy('created_at')->orderBy('id')->get()->groupBy('ticket_id') as $histories) {
            $oldStageId = null;
            foreach ($histories as $legacy) {
                $taskId = $this->taskMap[(int) $legacy->ticket_id] ?? null;
                $newStageId = $this->stageMap[(int) $legacy->ticket_status_id] ?? null;
                if (!$taskId || !$newStageId) {
                    continue;
                }

                PmTaskStatusLog::updateOrCreate(
                    ['legacy_history_id' => $legacy->id],
                    [
                        'task_id' => $taskId,
                        'old_stage_id' => $oldStageId,
                        'new_stage_id' => $newStageId,
                        'changed_by' => $this->userMap[(int) $legacy->user_id] ?? null,
                        'changed_at' => $legacy->created_at,
                        'note' => 'Diimpor dari riwayat PM legacy.',
                    ]
                );
                $oldStageId = $newStageId;
            }
        }
    }

    private function legacyProjectSlug(string $name, int $legacyId): string
    {
        $base = Str::slug($name) ?: 'project';
        $slug = $base.'-legacy-'.$legacyId;

        return $slug;
    }

    private function normalizeName(?string $name): string
    {
        return strtolower((string) preg_replace('/\s+/', ' ', trim($name ?? '')));
    }

    private function plainText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    private function priorityValue(?string $name): string
    {
        return match (strtolower(trim((string) $name))) {
            'low', 'rendah' => 'low',
            'high', 'tinggi' => 'high',
            'urgent', 'mendesak' => 'urgent',
            default => 'normal',
        };
    }

    private function printSourceSummary(): void
    {
        $this->table(['Data', 'Jumlah'], [
            ['Users', $this->source->table('users')->count()],
            ['Projects', $this->source->table('projects')->count()],
            ['Stages', $this->source->table('ticket_statuses')->count()],
            ['Epics', $this->source->table('epics')->count()],
            ['Tickets', $this->source->table('tickets')->count()],
            ['Ticket users', $this->source->table('ticket_users')->count()],
            ['Comments', $this->source->table('ticket_comments')->count()],
            ['Status histories', $this->source->table('ticket_histories')->count()],
            ['Project notes', $this->source->table('project_notes')->count()],
        ]);
    }
}
