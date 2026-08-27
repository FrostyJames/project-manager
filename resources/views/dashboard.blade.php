
<x-layouts::app :title="__('Dashboard')">

    <livewire:pages::teams.pending-invitations-modal />

    @php
        $team = auth()->user()->currentTeam;

        $projectQuery = $team
            ? $team->projects()
            : null;

        $projectIds = $team
            ? $team->projects()->pluck('id')
            : collect();

        $taskQuery = $projectIds->isNotEmpty()
            ? \App\Models\Task::whereIn('project_id', $projectIds)
            : null;

        $totalProjects = $team
            ? $team->projects()->count()
            : 0;

        $totalTasks = $taskQuery
            ? $taskQuery->count()
            : 0;

        $todoTasks = $taskQuery
            ? (clone $taskQuery)->where('status', 'todo')->count()
            : 0;

        $inProgressTasks = $taskQuery
            ? (clone $taskQuery)->where('status', 'in_progress')->count()
            : 0;

        $completedTasks = $taskQuery
            ? (clone $taskQuery)->where('status', 'completed')->count()
            : 0;

        $highPriorityTasks = $taskQuery
            ? (clone $taskQuery)->where('priority', 'high')->count()
            : 0;

        $overdueTasks = $taskQuery
            ? (clone $taskQuery)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now())
                ->where('status', '!=', 'completed')
                ->count()
            : 0;

        $totalComments = $projectIds->isNotEmpty()
            ? \App\Models\Comment::whereHas('task', function ($query) use ($projectIds) {
                $query->whereIn('project_id', $projectIds);
            })->count()
            : 0;

        $completionPercentage = $totalTasks > 0
            ? round(($completedTasks / $totalTasks) * 100)
            : 0;

        $inProgressPercentage = $totalTasks > 0
            ? round(($inProgressTasks / $totalTasks) * 100)
            : 0;

        $todoPercentage = $totalTasks > 0
            ? round(($todoTasks / $totalTasks) * 100)
            : 0;
    @endphp


    {{-- ============================================================ --}}
    {{-- DASHBOARD --}}
    {{-- ============================================================ --}}

    <div class="min-h-full w-full">

        {{-- ======================================================== --}}
        {{-- HEADER --}}
        {{-- ======================================================== --}}

        <div class="mb-8">

            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">

                <div>

                    <div class="flex items-center gap-3">

                        <div class="flex size-11 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-400">

                            <flux:icon name="squares-2x2" class="size-6" />

                        </div>

                        <div>

                            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                                Dashboard
                            </h1>

                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                Overview of your team's projects and tasks.
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ======================================================== --}}
        {{-- TEAM NOTICE --}}
        {{-- ======================================================== --}}

        @if (!$team)

            <div class="mb-6 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">

                <flux:icon
                    name="exclamation-triangle"
                    class="mt-0.5 size-5 shrink-0"
                />

                <div>

                    <p class="font-semibold">
                        No active team
                    </p>

                    <p class="mt-1 text-sm">
                        Select or create a team to start managing projects and tasks.
                    </p>

                </div>

            </div>

        @endif


        {{-- ======================================================== --}}
        {{-- MAIN STATISTICS --}}
        {{-- ======================================================== --}}

        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

            {{-- Projects --}}
            <div class="group rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900">

                <div class="flex items-start justify-between">

                    <div>

                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                            Total Projects
                        </p>

                        <p class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                            {{ $totalProjects }}
                        </p>

                    </div>

                    <div class="flex size-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">

                        <flux:icon name="folder" class="size-5" />

                    </div>

                </div>

                <a
                    href="{{ $team ? route('projects.index', ['current_team' => $team->slug]) : '#' }}"
                    @if (!$team) aria-disabled="true" @endif
                    wire:navigate
                    class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                >
                    View projects

                    <flux:icon name="arrow-right" class="size-4" />

                </a>

            </div>


            {{-- Tasks --}}
            <div class="group rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900">

                <div class="flex items-start justify-between">

                    <div>

                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                            Total Tasks
                        </p>

                        <p class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                            {{ $totalTasks }}
                        </p>

                    </div>

                    <div class="flex size-11 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400">

                        <flux:icon name="clipboard-document-list" class="size-5" />

                    </div>

                </div>

                <a
                    href="{{ $team ? route('tasks.index', ['current_team' => $team->slug]) : '#' }}"
                    @if (!$team) aria-disabled="true" @endif
                    wire:navigate
                    class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                >
                    View tasks

                    <flux:icon name="arrow-right" class="size-4" />

                </a>

            </div>


            {{-- Completed --}}
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

                <div class="flex items-start justify-between">

                    <div>

                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                            Completed Tasks
                        </p>

                        <p class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                            {{ $completedTasks }}
                        </p>

                    </div>

                    <div class="flex size-11 items-center justify-center rounded-xl bg-green-50 text-green-600 dark:bg-green-500/10 dark:text-green-400">

                        <flux:icon name="check-circle" class="size-5" />

                    </div>

                </div>

                <div class="mt-4">

                    <div class="flex items-center justify-between text-xs">

                        <span class="text-zinc-500 dark:text-zinc-400">
                            Completion
                        </span>

                        <span class="font-semibold text-zinc-700 dark:text-zinc-300">
                            {{ $completionPercentage }}%
                        </span>

                    </div>

                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">

                        <div
                            class="h-full rounded-full bg-green-500 transition-all duration-500"
                            style="width: {{ $completionPercentage }}%"
                        ></div>

                    </div>

                </div>

            </div>


            {{-- Overdue --}}
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

                <div class="flex items-start justify-between">

                    <div>

                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                            Overdue Tasks
                        </p>

                        <p class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                            {{ $overdueTasks }}
                        </p>

                    </div>

                    <div class="flex size-11 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">

                        <flux:icon name="exclamation-triangle" class="size-5" />

                    </div>

                </div>

                <p class="mt-4 text-xs text-zinc-500 dark:text-zinc-400">
                    Uncompleted tasks past their due date.
                </p>

            </div>

        </div>


        {{-- ======================================================== --}}
        {{-- TASK OVERVIEW --}}
        {{-- ======================================================== --}}

        <div class="mt-8">

            <div class="mb-4">

                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                    Task Overview
                </h2>

                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    A breakdown of your team's current workload.
                </p>

            </div>


            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                {{-- To Do --}}
                <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">

                    <div class="flex items-center gap-3">

                        <div class="flex size-9 items-center justify-center rounded-lg bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">

                            <flux:icon name="clock" class="size-4" />

                        </div>

                        <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                            To Do
                        </span>

                    </div>

                    <div class="mt-4 flex items-end justify-between">

                        <span class="text-2xl font-bold text-zinc-900 dark:text-white">
                            {{ $todoTasks }}
                        </span>

                        <span class="text-xs font-medium text-zinc-400">
                            {{ $todoPercentage }}%
                        </span>

                    </div>

                </div>


                {{-- In Progress --}}
                <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">

                    <div class="flex items-center gap-3">

                        <div class="flex size-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">

                            <flux:icon name="arrow-path" class="size-4" />

                        </div>

                        <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                            In Progress
                        </span>

                    </div>

                    <div class="mt-4 flex items-end justify-between">

                        <span class="text-2xl font-bold text-zinc-900 dark:text-white">
                            {{ $inProgressTasks }}
                        </span>

                        <span class="text-xs font-medium text-blue-600 dark:text-blue-400">
                            {{ $inProgressPercentage }}%
                        </span>

                    </div>

                </div>


                {{-- High Priority --}}
                <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">

                    <div class="flex items-center gap-3">

                        <div class="flex size-9 items-center justify-center rounded-lg bg-orange-50 text-orange-600 dark:bg-orange-500/10 dark:text-orange-400">

                            <flux:icon name="flag" class="size-4" />

                        </div>

                        <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                            High Priority
                        </span>

                    </div>

                    <div class="mt-4">

                        <span class="text-2xl font-bold text-zinc-900 dark:text-white">
                            {{ $highPriorityTasks }}
                        </span>

                    </div>

                </div>


                {{-- Comments --}}
                <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">

                    <div class="flex items-center gap-3">

                        <div class="flex size-9 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400">

                            <flux:icon name="chat-bubble-left-right" class="size-4" />

                        </div>

                        <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                            Comments
                        </span>

                    </div>

                    <div class="mt-4">

                        <span class="text-2xl font-bold text-zinc-900 dark:text-white">
                            {{ $totalComments }}
                        </span>

                    </div>

                </div>

            </div>

        </div>


        {{-- ======================================================== --}}
        {{-- TASK PROGRESS --}}
        {{-- ======================================================== --}}

        <div class="mt-8 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                <div>

                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                        Task Progress
                    </h2>

                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Track how your team's tasks are moving toward completion.
                    </p>

                </div>

                @if ($totalTasks > 0)

                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                        {{ $completionPercentage }}%
                    </div>

                @endif

            </div>


            @if ($totalTasks > 0)

                {{-- Progress bar --}}
                <div class="mt-6">

                    <div class="h-3 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">

                        <div
                            class="h-full rounded-full bg-green-500 transition-all duration-700"
                            style="width: {{ $completionPercentage }}%"
                        ></div>

                    </div>

                </div>


                {{-- Progress details --}}
                <div class="mt-6 grid gap-4 sm:grid-cols-3">

                    {{-- Completed --}}
                    <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/60">

                        <div class="flex items-center justify-between">

                            <span class="text-sm text-zinc-500 dark:text-zinc-400">
                                Completed
                            </span>

                            <span class="font-semibold text-green-600 dark:text-green-400">
                                {{ $completionPercentage }}%
                            </span>

                        </div>

                        <p class="mt-2 text-lg font-semibold text-zinc-900 dark:text-white">
                            {{ $completedTasks }}
                            <span class="text-sm font-normal text-zinc-400">
                                tasks
                            </span>
                        </p>

                    </div>


                    {{-- In Progress --}}
                    <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/60">

                        <div class="flex items-center justify-between">

                            <span class="text-sm text-zinc-500 dark:text-zinc-400">
                                In Progress
                            </span>

                            <span class="font-semibold text-blue-600 dark:text-blue-400">
                                {{ $inProgressPercentage }}%
                            </span>

                        </div>

                        <p class="mt-2 text-lg font-semibold text-zinc-900 dark:text-white">
                            {{ $inProgressTasks }}
                            <span class="text-sm font-normal text-zinc-400">
                                tasks
                            </span>
                        </p>

                    </div>


                    {{-- To Do --}}
                    <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/60">

                        <div class="flex items-center justify-between">

                            <span class="text-sm text-zinc-500 dark:text-zinc-400">
                                To Do
                            </span>

                            <span class="font-semibold text-zinc-600 dark:text-zinc-300">
                                {{ $todoPercentage }}%
                            </span>

                        </div>

                        <p class="mt-2 text-lg font-semibold text-zinc-900 dark:text-white">
                            {{ $todoTasks }}
                            <span class="text-sm font-normal text-zinc-400">
                                tasks
                            </span>
                        </p>

                    </div>

                </div>

            @else

                <div class="mt-6 rounded-xl border border-dashed border-zinc-300 px-6 py-10 text-center dark:border-zinc-700">

                    <div class="mx-auto flex size-12 items-center justify-center rounded-xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">

                        <flux:icon name="clipboard-document-list" class="size-6" />

                    </div>

                    <h3 class="mt-4 text-sm font-semibold text-zinc-900 dark:text-white">
                        No tasks yet
                    </h3>

                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Create tasks to start tracking your team's progress.
                    </p>

                    @if ($team)

                        <a
                            href="{{ route('tasks.index', ['current_team' => $team->slug]) }}"
                            wire:navigate
                            class="mt-5 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700 dark:hover:bg-indigo-500"
                        >

                            <flux:icon name="plus" class="size-4" />

                            Create a Task

                        </a>

                    @endif

                </div>

            @endif

        </div>


        {{-- ======================================================== --}}
        {{-- QUICK ACTIONS --}}
        {{-- ======================================================== --}}

        <div class="mt-8">

            <div class="mb-4">

                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                    Quick Actions
                </h2>

                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Jump directly to the areas you use most.
                </p>

            </div>


            <div class="grid gap-5 md:grid-cols-2">

                {{-- Projects --}}
                <div class="group rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900">

                    <div class="flex items-start gap-4">

                        <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">

                            <flux:icon name="folder" class="size-6" />

                        </div>

                        <div class="min-w-0">

                            <h3 class="font-semibold text-zinc-900 dark:text-white">
                                Projects
                            </h3>

                            <p class="mt-1 text-sm leading-5 text-zinc-500 dark:text-zinc-400">
                                Create, manage, and track your team's projects.
                            </p>

                        </div>

                    </div>

                    @if ($team)

                        <a
                            href="{{ route('projects.index', ['current_team' => $team->slug]) }}"
                            wire:navigate
                            class="mt-5 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700 dark:hover:bg-indigo-500"
                        >

                            View Projects

                            <flux:icon name="arrow-right" class="size-4" />

                        </a>

                    @endif

                </div>


                {{-- Tasks --}}
                <div class="group rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900">

                    <div class="flex items-start gap-4">

                        <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400">

                            <flux:icon name="clipboard-document-list" class="size-6" />

                        </div>

                        <div class="min-w-0">

                            <h3 class="font-semibold text-zinc-900 dark:text-white">
                                Tasks
                            </h3>

                            <p class="mt-1 text-sm leading-5 text-zinc-500 dark:text-zinc-400">
                                Manage tasks, priorities, assignments, and deadlines.
                            </p>

                        </div>

                    </div>

                    @if ($team)

                        <a
                            href="{{ route('tasks.index', ['current_team' => $team->slug]) }}"
                            wire:navigate
                            class="mt-5 inline-flex items-center gap-2 rounded-xl bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-purple-700 dark:hover:bg-purple-500"
                        >

                            View Tasks

                            <flux:icon name="arrow-right" class="size-4" />

                        </a>

                    @endif

                </div>

            </div>

        </div>

    </div>

</x-layouts::app>

