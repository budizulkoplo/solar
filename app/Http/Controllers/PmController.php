<?php

namespace App\Http\Controllers;

use App\Models\PmProject;
use App\Models\PmStage;
use App\Models\PmTask;
use App\Models\PmTaskComment;
use App\Models\PmTaskStatusLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PmController extends Controller
{
    private const DEFAULT_STAGES = ['Backlog', 'To Do', 'In Progress', 'Review', 'Done'];

    public function projects(): View
    {
        $projects = $this->visibleProjects()->withCount('tasks')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('pm.projects.index', compact('projects'));
    }

    public function dashboard(): View
    {
        $projects = $this->visibleProjects()
            ->withCount('tasks')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        $visibleTaskIds = $this->visibleTasks()->pluck('id');

        $memberIds = DB::table('pm_project_members')
            ->whereIn('project_id', $projects->pluck('id'))
            ->pluck('user_id')
            ->merge(DB::table('pm_task_assignees')->whereIn('task_id', $visibleTaskIds)->pluck('user_id'))
            ->merge(PmTask::whereIn('id', $visibleTaskIds)->whereNotNull('created_by')->pluck('created_by'))
            ->filter()
            ->unique()
            ->values();

        $members = User::query()
            ->whereIn('id', $memberIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $projectCounts = DB::table('pm_project_members')
            ->select('user_id', DB::raw('COUNT(DISTINCT project_id) AS total'))
            ->whereIn('project_id', $projects->pluck('id'))
            ->whereIn('user_id', $memberIds)
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $assignedCounts = DB::table('pm_task_assignees')
            ->select('user_id', DB::raw('COUNT(DISTINCT task_id) AS total'))
            ->whereIn('task_id', $visibleTaskIds)
            ->whereIn('user_id', $memberIds)
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $monthStart = now()->startOfMonth()->subMonths(12);
        $monthEnd = now()->endOfMonth();
        $monthKeys = collect(range(0, 12))->map(fn (int $month) => $monthStart->copy()->addMonths($month));
        $createdByMonth = PmTask::query()
            ->whereIn('id', $visibleTaskIds)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->get(['created_at'])
            ->countBy(fn (PmTask $task) => Carbon::parse($task->created_at)->format('Y-m'));

        return view('pm.dashboard', [
            'stats' => [
                'projects' => $projects->count(),
                'activeProjects' => $projects->where('is_active', true)->count(),
                'tasks' => $visibleTaskIds->count(),
                'myTasks' => DB::table('pm_task_assignees')->whereIn('task_id', $visibleTaskIds)->where('user_id', auth()->id())->distinct()->count('task_id'),
                'members' => $members->count(),
            ],
            'projectChart' => [
                'labels' => $projects->pluck('name')->values(),
                'values' => $projects->pluck('tasks_count')->map(fn ($value) => (int) $value)->values(),
            ],
            'userChart' => [
                'labels' => $members->pluck('name')->values(),
                'projects' => $members->map(fn (User $member) => (int) ($projectCounts[$member->id] ?? 0))->values(),
                'assigned' => $members->map(fn (User $member) => (int) ($assignedCounts[$member->id] ?? 0))->values(),
            ],
            'trendChart' => [
                'labels' => $monthKeys->map(fn (Carbon $month) => $month->format('M Y'))->values(),
                'values' => $monthKeys->map(fn (Carbon $month) => (int) ($createdByMonth[$month->format('Y-m')] ?? 0))->values(),
            ],
        ]);
    }

    public function contributors(): View
    {
        $start = now()->subMonths(3)->startOfDay();
        $end = now()->endOfDay();
        $stats = $this->contributionStats($start, $end);
        $gridStart = $start->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $end->copy()->endOfWeek(Carbon::SATURDAY);

        foreach ($stats as &$stat) {
            $weeks = [];
            for ($week = $gridStart->copy(); $week <= $gridEnd; $week->addWeek()) {
                $days = [];
                for ($offset = 0; $offset < 7; $offset++) {
                    $date = $week->copy()->addDays($offset);
                    $count = (int) ($stat['days'][$date->format('Y-m-d')] ?? 0);
                    $days[] = [
                        'date' => $date->format('d M Y'),
                        'count' => $count,
                        'level' => min(4, $count),
                    ];
                }
                $weeks[] = $days;
            }
            $stat['weeks'] = $weeks;
        }
        unset($stat);

        return view('pm.contributors', [
            'contributors' => array_values($stats),
            'periodLabel' => 'Last 3 Months Activity',
        ]);
    }

    public function leaderboard(Request $request): View
    {
        $days = (int) $request->query('days', 7);
        $days = in_array($days, [7, 30, 90], true) ? $days : 7;
        $top = (int) $request->query('top', 10);
        $top = in_array($top, [5, 10, 20], true) ? $top : 10;
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();
        $contributors = array_values($this->contributionStats($start, $end));

        usort($contributors, function (array $left, array $right): int {
            return [$right['score'], $right['tickets'], $right['name']] <=> [$left['score'], $left['tickets'], $left['name']];
        });

        return view('pm.leaderboard', [
            'contributors' => array_slice($contributors, 0, $top),
            'days' => $days,
            'top' => $top,
            'periodLabel' => 'Last '.$days.' Days - Top '.$top.' Contributors',
        ]);
    }

    public function createProject(): View
    {
        abort_unless($this->isAdmin(), 403);

        return view('pm.projects.form', [
            'project' => new PmProject(['is_active' => true]),
            'stagesText' => implode(PHP_EOL, self::DEFAULT_STAGES),
            'users' => $this->pmUsers(),
            'action' => route('pm.projects.store'),
            'method' => 'POST',
        ]);
    }

    public function storeProject(Request $request): RedirectResponse
    {
        abort_unless($this->isAdmin(), 403);

        $data = $this->validateProject($request);
        $stages = $this->stageNames($request);
        $members = $data['members'] ?? [];

        $project = DB::transaction(function () use ($data, $stages, $members): PmProject {
            $project = PmProject::create([
                'name' => $data['name'],
                'slug' => $this->uniqueProjectSlug($data['name']),
                'description' => $data['description'] ?? null,
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);

            $this->syncStages($project, $stages);
            $project->members()->sync($members);

            return $project;
        });

        return redirect()->route('pm.projects.board', $project)->with('success', 'Project berhasil dibuat.');
    }

    public function editProject(PmProject $project): View
    {
        abort_unless($this->canManageProject($project), 403);
        $project->load('stages', 'members');

        return view('pm.projects.form', [
            'project' => $project,
            'stagesText' => $project->stages->pluck('name')->implode(PHP_EOL),
            'users' => $this->pmUsers(),
            'action' => route('pm.projects.update', $project),
            'method' => 'PUT',
        ]);
    }

    public function updateProject(Request $request, PmProject $project): RedirectResponse
    {
        abort_unless($this->canManageProject($project), 403);
        $data = $this->validateProject($request, $project);
        $stages = $this->stageNames($request);
        $members = $data['members'] ?? [];

        DB::transaction(function () use ($project, $data, $stages, $members): void {
            $project->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? false),
            ]);
            $this->syncStages($project, $stages);
            $project->members()->sync($members);
        });

        return redirect()->route('pm.projects.index')->with('success', 'Project berhasil diperbarui.');
    }

    public function destroyProject(PmProject $project): RedirectResponse
    {
        abort_unless($this->canManageProject($project), 403);

        if ($project->tasks()->exists()) {
            return back()->withErrors('Project masih memiliki tugas. Hapus tugas di project ini terlebih dahulu.');
        }

        $project->delete();

        return redirect()->route('pm.projects.index')->with('success', 'Project berhasil dihapus.');
    }

    public function boardIndex(Request $request): View|RedirectResponse
    {
        $project = $request->integer('project_id')
            ? $this->visibleProjects()->find($request->integer('project_id'))
            : $this->visibleProjects()->where('is_active', true)->orderBy('name')->first();

        if (!$project) {
            return redirect()->route('pm.projects.index')->with('error', 'Belum ada project PM.');
        }

        return $this->board($request, $project);
    }

    public function board(Request $request, PmProject $project): View
    {
        abort_unless($this->canViewProject($project), 403);

        $keyword = trim((string) $request->query('q'));
        $dueDate = trim((string) $request->query('due_date'));
        $showClosed = $request->boolean('show_closed');

        $project->load([
            'stages.tasks' => function ($query) use ($keyword, $dueDate, $showClosed): void {
                $query->with('assignees')
                    ->when(!$showClosed, fn ($taskQuery) => $taskQuery->whereNull('hidden_at'))
                    ->when($keyword !== '', fn ($taskQuery) => $taskQuery->where('title', 'like', "%{$keyword}%"))
                    ->when($dueDate === 'today', fn ($taskQuery) => $taskQuery->whereDate('due_date', today()))
                    ->when($dueDate === 'week', fn ($taskQuery) => $taskQuery->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()]))
                    ->when($dueDate === 'month', fn ($taskQuery) => $taskQuery->whereBetween('due_date', [now()->startOfMonth(), now()->endOfMonth()]))
                    ->latest();
            },
        ]);

        return view('pm.projects.board', [
            'project' => $project,
            'projects' => $this->visibleProjects()->where('is_active', true)->orderBy('name')->get(),
            'keyword' => $keyword,
            'dueDate' => $dueDate,
            'showClosed' => $showClosed,
            'editableTaskIds' => $project->stages->flatMap->tasks->filter(fn (PmTask $task) => $this->canManageTask($task))->pluck('id')->all(),
            'deletableTaskIds' => $project->stages->flatMap->tasks->filter(fn (PmTask $task) => $this->canDeleteTask($task))->pluck('id')->all(),
            'priorityLabels' => $this->priorityLabels(),
        ]);
    }

    public function tasks(Request $request): View
    {
        $projectId = $request->integer('project_id');
        $tasks = $this->visibleTasks()->with(['project', 'stage', 'assignees'])
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%'.$request->string('q').'%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('pm.tasks.index', [
            'tasks' => $tasks,
            'projects' => $this->visibleProjects()->where('is_active', true)->orderBy('name')->get(),
            'projectId' => $projectId,
            'editableTaskIds' => $tasks->getCollection()->filter(fn (PmTask $task) => $this->canManageTask($task))->pluck('id')->all(),
            'deletableTaskIds' => $tasks->getCollection()->filter(fn (PmTask $task) => $this->canDeleteTask($task))->pluck('id')->all(),
            'priorityLabels' => $this->priorityLabels(),
        ]);
    }

    public function createTask(Request $request): View
    {
        $project = $request->integer('project_id')
            ? $this->visibleProjects()->findOrFail($request->integer('project_id'))
            : $this->visibleProjects()->where('is_active', true)->orderBy('name')->first();

        return view('pm.tasks.form', $this->taskFormData(new PmTask([
            'project_id' => $project?->id,
            'stage_id' => $project?->stages()->first()?->id,
            'priority' => 'normal',
            'start_date' => today(),
        ]), 'Tambah Tugas', route('pm.tasks.store'), 'POST'));
    }

    public function storeTask(Request $request): RedirectResponse
    {
        $data = $this->validateTask($request);
        $project = $this->visibleProjects()->with('stages')->findOrFail($data['project_id']);
        $this->ensureAssigneesBelongToProject($data['assignees'] ?? [], $project);
        $stage = $this->stageForProject($project, $data['stage_id'] ?? null);

        $task = DB::transaction(function () use ($data, $project, $stage): PmTask {
            $task = PmTask::create([
                'project_id' => $project->id,
                'stage_id' => $stage?->id,
                'title' => $data['title'],
                'priority' => $data['priority'],
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $task->assignees()->sync($data['assignees'] ?? []);
            $this->writeStatusLog($task, null, $stage, 'Tugas dibuat.');

            return $task;
        });

        return redirect()->route('pm.projects.board', $task->project_id)->with('success', 'Tugas berhasil dibuat.');
    }

    public function editTask(PmTask $task): View
    {
        abort_unless($this->canManageTask($task), 403);
        $task->load('assignees');

        return view('pm.tasks.form', $this->taskFormData($task, 'Edit Tugas', route('pm.tasks.update', $task), 'PUT'));
    }

    public function updateTask(Request $request, PmTask $task): RedirectResponse
    {
        abort_unless($this->canManageTask($task), 403);
        $data = $this->validateTask($request);
        $project = $this->visibleProjects()->with('stages')->findOrFail($data['project_id']);
        $this->ensureAssigneesBelongToProject($data['assignees'] ?? [], $project);
        $targetStage = $this->stageForProject($project, $data['stage_id'] ?? null);
        $task->loadMissing('stage');

        DB::transaction(function () use ($task, $data, $project, $targetStage): void {
            $oldStage = $task->stage;
            $task->update([
                'project_id' => $project->id,
                'stage_id' => $targetStage?->id,
                'title' => $data['title'],
                'priority' => $data['priority'],
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
            ]);
            $task->assignees()->sync($data['assignees'] ?? []);

            if ((int) $oldStage?->id !== (int) $targetStage?->id) {
                $this->writeStatusLog($task, $oldStage, $targetStage, 'Status diubah dari form tugas.');
            }
        });

        return redirect()->route('pm.projects.board', $task->project_id)->with('success', 'Tugas berhasil diperbarui.');
    }

    public function showTask(PmTask $task): View
    {
        abort_unless($this->canViewTask($task), 403);

        $task->load(['project', 'stage', 'creator', 'assignees', 'comments.user', 'statusLogs.oldStage', 'statusLogs.newStage', 'statusLogs.changer']);

        return view('pm.tasks.show', [
            'task' => $task,
            'priorityLabels' => $this->priorityLabels(),
            'canEditTask' => $this->canManageTask($task),
            'canDeleteTask' => $this->canDeleteTask($task),
        ]);
    }

    public function destroyTask(PmTask $task): RedirectResponse
    {
        abort_unless($this->canDeleteTask($task), 403);
        $projectId = $task->project_id;
        $task->delete();

        return redirect()->route('pm.projects.board', $projectId)->with('success', 'Tugas berhasil dihapus.');
    }

    public function moveTask(Request $request, PmTask $task): JsonResponse|RedirectResponse
    {
        abort_unless($this->canManageTask($task), 403);
        $data = $request->validate(['stage_id' => ['required', 'integer']]);
        $targetStage = PmStage::where('project_id', $task->project_id)->findOrFail($data['stage_id']);
        $task->load('stage');

        if ((int) $task->stage_id !== (int) $targetStage->id) {
            $oldStage = $task->stage;
            $task->update(['stage_id' => $targetStage->id]);
            $this->writeStatusLog($task, $oldStage, $targetStage, 'Status dipindahkan dari board.');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'stage_id' => $targetStage->id,
                'stage_name' => $targetStage->name,
            ]);
        }

        return back()->with('success', 'Status tugas berhasil diperbarui.');
    }

    public function closeTask(PmTask $task): RedirectResponse
    {
        abort_unless($this->canManageTask($task), 403);
        $task->update(['hidden_at' => now()]);

        return back()->with('success', 'Tugas ditutup dari board.');
    }

    public function storeComment(Request $request, PmTask $task): RedirectResponse
    {
        abort_unless($this->canViewTask($task), 403);

        $data = $request->validate(['message' => ['required', 'string', 'max:5000']]);
        PmTaskComment::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'message' => trim(strip_tags($data['message'])),
        ]);

        return back()->with('success', 'Komentar berhasil ditambahkan.');
    }

    public function updateComment(Request $request, PmTask $task, PmTaskComment $comment): RedirectResponse
    {
        abort_unless($this->canViewTask($task), 403);
        $this->ensureCommentBelongsToTask($task, $comment);
        abort_unless((int) $comment->user_id === (int) auth()->id() || $this->isAdmin(), 403);
        $data = $request->validate(['message' => ['required', 'string', 'max:5000']]);
        $comment->update(['message' => trim(strip_tags($data['message']))]);

        return back()->with('success', 'Komentar berhasil diperbarui.');
    }

    public function destroyComment(PmTask $task, PmTaskComment $comment): RedirectResponse
    {
        abort_unless($this->canViewTask($task), 403);
        $this->ensureCommentBelongsToTask($task, $comment);
        abort_unless((int) $comment->user_id === (int) auth()->id() || $this->isAdmin(), 403);
        $comment->delete();

        return back()->with('success', 'Komentar berhasil dihapus.');
    }

    private function validateProject(Request $request, ?PmProject $project = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160', Rule::unique('pm_projects', 'name')->ignore($project?->id)],
            'description' => ['nullable', 'string', 'max:10000'],
            'stages' => ['required', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'members' => ['nullable', 'array'],
            'members.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);
    }

    private function validateTask(Request $request): array
    {
        return $request->validate([
            'project_id' => ['required', 'integer', 'exists:pm_projects,id'],
            'stage_id' => ['nullable', 'integer', 'exists:pm_stages,id'],
            'title' => ['required', 'string', 'max:180'],
            'priority' => ['required', Rule::in(array_keys($this->priorityLabels()))],
            'description' => ['nullable', 'string', 'max:20000'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'assignees' => ['nullable', 'array'],
            'assignees.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);
    }

    private function stageNames(Request $request): array
    {
        $stages = collect(preg_split('/\r\n|\r|\n/', (string) $request->input('stages')))
            ->map(fn ($stage) => trim($stage))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (!$stages) {
            abort(422, 'Minimal satu kolom Kanban harus diisi.');
        }

        return $stages;
    }

    private function syncStages(PmProject $project, array $stageNames): void
    {
        $existing = $project->stages()->get()->keyBy('slug');
        $keepIds = [];

        foreach ($stageNames as $index => $name) {
            $slug = Str::slug($name) ?: 'stage-'.($index + 1);
            $stage = $existing->get($slug) ?? new PmStage(['project_id' => $project->id, 'slug' => $slug]);
            $stage->fill([
                'name' => $name,
                'slug' => $slug,
                'sort_order' => ($index + 1) * 10,
                'is_done' => $index === count($stageNames) - 1,
            ])->save();
            $keepIds[] = $stage->id;
        }

        $project->stages()->whereNotIn('id', $keepIds)->whereDoesntHave('tasks')->delete();
    }

    private function uniqueProjectSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'project';
        $slug = $base;
        $suffix = 2;

        while (PmProject::where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function stageForProject(PmProject $project, ?int $stageId): ?PmStage
    {
        if ($stageId) {
            return $project->stages->firstWhere('id', $stageId) ?? abort(422, 'Kolom tugas tidak sesuai dengan project.');
        }

        return $project->stages->first();
    }

    private function taskFormData(PmTask $task, string $title, string $action, string $method): array
    {
        $task->loadMissing('project', 'assignees');

        return [
            'task' => $task,
            'pageTitle' => $title,
            'action' => $action,
            'method' => $method,
            'projects' => $this->visibleProjects()->where('is_active', true)->with(['stages', 'members'])->orderBy('name')->get(),
            'users' => $this->pmUsers(),
            'priorityLabels' => $this->priorityLabels(),
        ];
    }

    private function pmUsers()
    {
        return User::query()
            ->where(function ($query): void {
                $query->where('status', 'aktif')->orWhereNull('status');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'jabatan']);
    }

    private function pmMemberIds()
    {
        $visibleProjectIds = $this->visibleProjects()->pluck('id');
        $visibleTaskIds = $this->visibleTasks()->pluck('id');

        return DB::table('pm_project_members')->whereIn('project_id', $visibleProjectIds)->pluck('user_id')
            ->merge(DB::table('pm_task_assignees')->whereIn('task_id', $visibleTaskIds)->pluck('user_id'))
            ->merge(PmTask::whereIn('id', $visibleTaskIds)->whereNotNull('created_by')->pluck('created_by'))
            ->filter()
            ->unique()
            ->values();
    }

    private function contributionStats(Carbon $start, Carbon $end): array
    {
        $memberIds = $this->pmMemberIds();
        $visibleTaskIds = $this->visibleTasks()->pluck('id');
        $users = User::query()->whereIn('id', $memberIds)->orderBy('name')->get(['id', 'name']);
        $colors = ['#d9469e', '#22c55e', '#3b82f6', '#f59e0b', '#8b5cf6', '#06b6d4'];
        $stats = [];

        foreach ($users as $index => $user) {
            $stats[$user->id] = [
                'id' => $user->id,
                'name' => $user->name,
                'initials' => collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->join(''),
                'color' => $colors[$index % count($colors)],
                'tickets' => 0,
                'updates' => 0,
                'comments' => 0,
                'active_days' => 0,
                'score' => 0,
                'days' => [],
            ];
        }

        $record = function (?int $userId, $date, string $metric) use (&$stats): void {
            if (!$userId || !isset($stats[$userId]) || !$date) {
                return;
            }

            $day = Carbon::parse($date)->format('Y-m-d');
            $stats[$userId][$metric]++;
            $stats[$userId]['days'][$day] = ($stats[$userId]['days'][$day] ?? 0) + 1;
        };

        PmTask::query()
            ->whereIn('id', $visibleTaskIds)
            ->whereIn('created_by', $memberIds)
            ->whereBetween('created_at', [$start, $end])
            ->get(['created_by', 'created_at'])
            ->each(fn (PmTask $task) => $record((int) $task->created_by, $task->created_at, 'tickets'));

        PmTaskStatusLog::query()
            ->whereIn('task_id', $visibleTaskIds)
            ->whereIn('changed_by', $memberIds)
            ->whereBetween('changed_at', [$start, $end])
            ->get(['changed_by', 'changed_at'])
            ->each(fn (PmTaskStatusLog $log) => $record((int) $log->changed_by, $log->changed_at, 'updates'));

        PmTaskComment::query()
            ->whereIn('task_id', $visibleTaskIds)
            ->whereIn('user_id', $memberIds)
            ->whereBetween('created_at', [$start, $end])
            ->get(['user_id', 'created_at'])
            ->each(fn (PmTaskComment $comment) => $record((int) $comment->user_id, $comment->created_at, 'comments'));

        foreach ($stats as &$stat) {
            $stat['active_days'] = count($stat['days']);
            $stat['score'] = ($stat['updates'] * 5) + ($stat['tickets'] * 2) + ($stat['comments'] * 2) + $stat['active_days'];
        }
        unset($stat);

        return $stats;
    }

    private function ensureAssigneesBelongToProject(array $assigneeIds, PmProject $project): void
    {
        $assigneeIds = collect($assigneeIds)->map(fn ($id) => (int) $id)->filter()->unique();

        if ($assigneeIds->isEmpty()) {
            return;
        }

        $memberCount = DB::table('pm_project_members')
            ->where('project_id', $project->id)
            ->whereIn('user_id', $assigneeIds)
            ->count();

        abort_unless($memberCount === $assigneeIds->count(), 422, 'Assignee harus merupakan member project yang dipilih.');
    }

    private function writeStatusLog(PmTask $task, ?PmStage $oldStage, ?PmStage $newStage, string $note): void
    {
        PmTaskStatusLog::create([
            'task_id' => $task->id,
            'old_stage_id' => $oldStage?->id,
            'new_stage_id' => $newStage?->id,
            'changed_by' => auth()->id(),
            'changed_at' => now(),
            'note' => $note,
        ]);
    }

    private function ensureCommentBelongsToTask(PmTask $task, PmTaskComment $comment): void
    {
        abort_unless((int) $comment->task_id === (int) $task->id, 404);
    }

    /**
     * Projects visible to the current PM user.
     * Administrators retain global PM visibility; regular users must be a
     * project member or the project creator.
     */
    private function visibleProjects()
    {
        if ($this->isAdmin()) {
            return PmProject::query();
        }

        $userId = (int) auth()->id();

        return PmProject::query()->where(function ($query) use ($userId): void {
            $query->where('created_by', $userId)
                ->orWhereHas('members', fn ($memberQuery) => $memberQuery->whereKey($userId));
        });
    }

    /**
     * Tasks visible to the current PM user. Task visibility follows the
     * project membership boundary, with creator/assignee access included so
     * existing imported tasks remain reachable by their responsible users.
     */
    private function visibleTasks()
    {
        if ($this->isAdmin()) {
            return PmTask::query();
        }

        $userId = (int) auth()->id();

        return PmTask::query()->where(function ($query) use ($userId): void {
            $query->where('created_by', $userId)
                ->orWhereHas('assignees', fn ($assigneeQuery) => $assigneeQuery->whereKey($userId))
                ->orWhereHas('project', function ($projectQuery) use ($userId): void {
                    $projectQuery->where('created_by', $userId)
                        ->orWhereHas('members', fn ($memberQuery) => $memberQuery->whereKey($userId));
                });
        });
    }

    private function canViewProject(PmProject $project): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return (int) $project->created_by === (int) auth()->id()
            || $project->members()->whereKey(auth()->id())->exists();
    }

    private function canViewTask(PmTask $task): bool
    {
        if ($this->isAdmin() || (int) $task->created_by === (int) auth()->id()) {
            return true;
        }

        $task->loadMissing('project', 'assignees');

        return $task->assignees->contains('id', auth()->id())
            || ($task->project && $this->canViewProject($task->project));
    }

    private function canManageProject(PmProject $project): bool
    {
        return $this->isAdmin() || (int) $project->created_by === (int) auth()->id();
    }

    private function canManageTask(PmTask $task): bool
    {
        if ($this->isAdmin() || (int) $task->created_by === (int) auth()->id()) {
            return true;
        }

        $task->loadMissing('assignees');
        return $task->assignees->contains('id', auth()->id());
    }

    private function canDeleteTask(PmTask $task): bool
    {
        return $this->isAdmin() || (int) $task->created_by === (int) auth()->id();
    }

    private function isAdmin(): bool
    {
        return auth()->user()?->hasAnyRole(['superadmin', 'admin']) ?? false;
    }

    private function priorityLabels(): array
    {
        return ['low' => 'Rendah', 'normal' => 'Normal', 'high' => 'Tinggi', 'urgent' => 'Mendesak'];
    }
}
